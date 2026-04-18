

<?php
session_start();
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" || !isset($_SESSION["idEtudiant"])) {
    header("location:AuthentificationEtudiant.php");
    exit();
}
$nomEtudiant = $_SESSION["prenomNom"] ?? 'Étudiant';
$idEtudiant = $_SESSION["idEtudiant"];

require_once 'Connexion.php';

$message_text = '';
$message_type = '';

if (isset($_SESSION['message_choix_sujet_text'])) {
    $message_text = $_SESSION['message_choix_sujet_text'];
    $message_type = $_SESSION['message_choix_sujet_type'] ?? 'info';
    unset($_SESSION['message_choix_sujet_text'], $_SESSION['message_choix_sujet_type']);
}

$idGroupe = null;
$idEncadrantDuGroupe = null;
$sujet_choisi_par_groupe = null;
$sujets_disponibles = [];

// 1. Get student's group ID
$stmt_etudiant_groupe = mysqli_prepare($conn, "SELECT idGroupe FROM Etudiant WHERE idEtudiant = ?");
if ($stmt_etudiant_groupe) {
    mysqli_stmt_bind_param($stmt_etudiant_groupe, "i", $idEtudiant);
    mysqli_stmt_execute($stmt_etudiant_groupe);
    $result_etudiant_groupe = mysqli_stmt_get_result($stmt_etudiant_groupe);
    if ($row_etudiant_groupe = mysqli_fetch_assoc($result_etudiant_groupe)) {
        $idGroupe = $row_etudiant_groupe['idGroupe'];
    }
    mysqli_stmt_close($stmt_etudiant_groupe);
}

if ($idGroupe) {
    // 2. Get the supervisor (Encadrant) of the student's group
    $stmt_groupe_encadrant = mysqli_prepare($conn, "SELECT idEncadrant FROM Groupe WHERE idGroupe = ?");
    if ($stmt_groupe_encadrant) {
        mysqli_stmt_bind_param($stmt_groupe_encadrant, "i", $idGroupe);
        mysqli_stmt_execute($stmt_groupe_encadrant);
        $result_groupe_encadrant = mysqli_stmt_get_result($stmt_groupe_encadrant);
        if ($row_groupe_encadrant = mysqli_fetch_assoc($result_groupe_encadrant)) {
            $idEncadrantDuGroupe = $row_groupe_encadrant['idEncadrant'];
        }
        mysqli_stmt_close($stmt_groupe_encadrant);
    }

    // 3. Check if the group has already chosen a subject
    $sql_check_choix = "SELECT s.idSujetPFE, s.titreSujetPFE, s.descriptions, d.nomDomaine, enc.nomEncadrant, enc.prenomEncadrant
                        FROM Choisir c
                        JOIN SujetPFE s ON c.idSujetPFE = s.idSujetPFE
                        JOIN Domaine d ON s.idDomaine = d.idDomaine
                        JOIN Encadrant enc ON s.idEncadrant = enc.idEncadrant
                        WHERE c.idGroupe = ?";
    $stmt_check_choix = mysqli_prepare($conn, $sql_check_choix);
    if ($stmt_check_choix) {
        mysqli_stmt_bind_param($stmt_check_choix, "i", $idGroupe);
        mysqli_stmt_execute($stmt_check_choix);
        $result_check_choix = mysqli_stmt_get_result($stmt_check_choix);
        if ($row_choix = mysqli_fetch_assoc($result_check_choix)) {
            $sujet_choisi_par_groupe = $row_choix;
        }
        mysqli_stmt_close($stmt_check_choix);
    }

    // Handle subject choice
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'choisir_sujet' && !$sujet_choisi_par_groupe) {
        $idSujetChoisi = filter_var($_POST['idSujetPFE'] ?? 0, FILTER_VALIDATE_INT);

        if ($idSujetChoisi > 0 && $idGroupe && $idEncadrantDuGroupe) {
            mysqli_autocommit($conn, FALSE); // Start transaction
            $success = true;

            // Check if subject is still available and belongs to the group's supervisor
            $stmt_verif_sujet = mysqli_prepare($conn, "SELECT estChoisi, idEncadrant FROM SujetPFE WHERE idSujetPFE = ?");
            mysqli_stmt_bind_param($stmt_verif_sujet, "i", $idSujetChoisi);
            mysqli_stmt_execute($stmt_verif_sujet);
            $res_verif_sujet = mysqli_stmt_get_result($stmt_verif_sujet);
            $sujet_data_verif = mysqli_fetch_assoc($res_verif_sujet);
            mysqli_stmt_close($stmt_verif_sujet);

            if ($sujet_data_verif && $sujet_data_verif['estChoisi'] == 0 && $sujet_data_verif['idEncadrant'] == $idEncadrantDuGroupe) {
                // Update SujetPFE
                $stmt_update_sujet = mysqli_prepare($conn, "UPDATE SujetPFE SET estChoisi = 1 WHERE idSujetPFE = ?");
                mysqli_stmt_bind_param($stmt_update_sujet, "i", $idSujetChoisi);
                if (!mysqli_stmt_execute($stmt_update_sujet)) {
                    $success = false;
                    $_SESSION['message_choix_sujet_text'] = "Erreur lors de la mise à jour du sujet: " . mysqli_stmt_error($stmt_update_sujet);
                }
                mysqli_stmt_close($stmt_update_sujet);

                // Insert into Choisir
                if ($success) {
                    $stmt_insert_choix = mysqli_prepare($conn, "INSERT INTO Choisir (idSujetPFE, idGroupe) VALUES (?, ?)");
                    mysqli_stmt_bind_param($stmt_insert_choix, "ii", $idSujetChoisi, $idGroupe);
                    if (!mysqli_stmt_execute($stmt_insert_choix)) {
                        $success = false;
                        $_SESSION['message_choix_sujet_text'] = "Erreur lors de l'enregistrement du choix: " . mysqli_stmt_error($stmt_insert_choix);
                    }
                    mysqli_stmt_close($stmt_insert_choix);
                }
            } else {
                $success = false;
                $_SESSION['message_choix_sujet_text'] = "Ce sujet n'est plus disponible, a déjà été choisi, ou n'est pas proposé par votre encadrant.";
            }

            if ($success) {
                mysqli_commit($conn);
                $_SESSION['message_choix_sujet_text'] = "Sujet PFE choisi avec succès!";
                $_SESSION['message_choix_sujet_type'] = "success";
            } else {
                mysqli_rollback($conn);
                $_SESSION['message_choix_sujet_type'] = "error"; // Message text already set
            }
            mysqli_autocommit($conn, TRUE);
            header("Location: ChoisirSujetEt.php");
            exit();
        }
    }

    // 4. If no subject chosen and has an encadrant, fetch available subjects from their encadrant
    if (!$sujet_choisi_par_groupe && $idEncadrantDuGroupe) {
        $sql_fetch_disponibles = "SELECT s.idSujetPFE, s.titreSujetPFE, s.descriptions, d.nomDomaine
                                 FROM SujetPFE s
                                 JOIN Domaine d ON s.idDomaine = d.idDomaine
                                 WHERE s.idEncadrant = ? AND s.estChoisi = 0
                                 ORDER BY s.dateProposition DESC";
        $stmt_fetch_disponibles = mysqli_prepare($conn, $sql_fetch_disponibles);
        if ($stmt_fetch_disponibles) {
            mysqli_stmt_bind_param($stmt_fetch_disponibles, "i", $idEncadrantDuGroupe);
            mysqli_stmt_execute($stmt_fetch_disponibles);
            $result_disponibles = mysqli_stmt_get_result($stmt_fetch_disponibles);
            while ($row_disponible = mysqli_fetch_assoc($result_disponibles)) {
                $sujets_disponibles[] = $row_disponible;
            }
            mysqli_stmt_close($stmt_fetch_disponibles);
        }
    }
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <title>Gestion des PFE</title>
    

    <style>
        body{
             background: linear-gradient(#EEE8AA,#F0E68C);
             background-repeat: no-repeat;
             background-attachment: fixed;
        }

        #myH1{
             color :#000;
             font-family: georgia;
             text-align: center;
             font-size: 45px;
             padding-left: 20px;
             /*margin-top: 9%;*/
             letter-spacing: 2px;
        }

        #all{
             margin: 100px;
        }

        /*:::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/

        *{
             margin: 0;
             padding: 0;
             box-sizing: border-box;
             font-family: "fire sans" ,"sans-serif";
        }

        .app{
             display: flex;
             min-height: 100vh;
        }

       .sidebar{
             flex: 1 1 0;
              position: fixed;
             top: 0;
             left: 0;
             height: 100vh; /* Full viewport height */
             z-index: 1000; /* Ensure sidebar is on top */
             overflow-y: auto; /* Scroll for sidebar content if it overflows */
              width : 300px ; 
             max-width: 300px;
             padding: 2rem 1rem;
             background-color: rgb(70, 139, 103);
        }

        .sidebar h3 {
             color:rgb(0, 0, 0);
             font-size: 1rem;   /*0.75rem*/
             text-transform: uppercase ;
             margin-bottom: 0.5em;
        }

        .sidebar .menu{
             margin: 0 -1rem;

        }

        .sidebar .menu .menu-item {
             display: block;
             padding: 1em;
             color: #F0E68C;
             text-decoration: none;
             transition: 0.4s linear;
        } 

        .sidebar .menu .menu-item:hover,
        .sidebar .menu .menu-item.is-active {
             color:rgb(18, 69, 50);
             border-right: 5px solid rgb(0, 0, 0);
        }

        .content{
             flex: 1 1 0;
             margin-left : 300px ;
             padding: 2rem;
        }

        .content h1{
             color: rgb(0, 0, 0);
             font-size: 2.5rem;
             margin-bottom: 1rem;
        }

        .content p {
             color: rgb(0, 0, 0);
        }

        /* controle the responsive sidebar*/

        .menu-toggle {
             display: none;
             position: fixed;
             top: 2rem;
             right: 2rem;
             width: 60px;
             height: 60px;
             border-radius: 99px;
             background-color: #2e3047;
             cursor: pointer;
        }

        .humburger{
             position: relative;
             top: calc(50% - 2px);
             left: 50%;
             transform: translate(-50% , -50% );
             width: 32px;
        }

        .humburger > span,
        .humburger > span::before,
        .humburger > span::after {
             display: block;
             position : absolute;
             width: 100px;
             height: 4px;
             border-radius: 99px;
             background-color: #FFF;
             transition-duration: .25s;
        }

        .humburger > span::before {
             content: '';
             top: -8px;
        }

       .humburger > span::after {
             content: '';
             top: 8px;
        }

        .menu-toggle.is-active .hamburger > span {
                 transform: rotate(450deg);
        }

        .menu-toggle.is-active .hamburger > span::before {
             top:0;
             transform: rotate(0deg);
        }

        .menu-toggle.is-active .hamburger > span::after {
             top: 0;
             transform: rotate(90deg);
        }

        @media (max-width: 1024px){
            .sidebar {
                 max-width: 200px;
            }
        }

        @media (max-width: 768px) {
            .menu-toggle{
                 display: block;
            }
            .content {
                 padding-top: 8rem;
            }
            .sidebar {
                 position: fixed;
                 top: 0;
                 left: -300px;
                 height: 100vh;
                 width: 100%;
                 max-width: 300px;
                 transition: 0.2s linear;
            }

            .sidebar.is-active{
                 left: 0;
            }
        }


        /* Styles for subject choice page */
        .choix-content { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top: 20px; margin-bottom: 20px; }
        .choix-content h2 { color: #333; border-bottom: 2px solid #2e916e; padding-bottom: 10px; margin-top: 0; margin-bottom: 15px; font-size: 1.8em; }
        .choix-content table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .choix-content th, .choix-content td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: top; word-break: break-word; }
        .choix-content th { background-color: #2e916e; color: white; }
        .choix-content tr:nth-child(even) { background-color: #f9f9f9; }
        .choix-content tr:hover { background-color: #f1f1f1; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.info { background-color: #e7f3fe; color: #31708f; border: 1px solid #bce8f1; }
        .btn-choisir { background-color: #28a745; color: white; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9em; }
        .btn-choisir:hover { background-color: #218838; }
        .sujet-details p { margin-bottom: 8px; line-height: 1.6; }
        .sujet-details strong { color: #2e916e; }
  



    </style>

</head>
<body>

    <div class="app">

        <div class="menu-toggle">
            <div class="hamburger">
                <span></span>
            </div>
        </div>

        <aside class="sidebar">
            <h3>Menu</h3>
            <nav class="menu">
                <a href="Etudiant.php" class="menu-item">Home</a>
                <a href="RechExempleEt.php" class="menu-item">Rechercher des exemples PFE</a>
                <a href="RechRessEt.php" class="menu-item">Rechercher des ressources pédagogiques</a>
                <a href="ChoisirSujetEt.php" class="menu-item is-active">Choisir un sujet parmi ceux proposés</a>
                <a href="DeposerRapEt.php" class="menu-item" >Déposer votre PFE</a>
                <a href="deconnexion.php" class="menu-item">Se déconnecter</a>
            </nav>
        </aside>

        <main class="content">
             <h1>Choisir un Sujet PFE</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($nomEtudiant); ?>. Sélectionnez ici le sujet PFE pour votre groupe.</p>

            <?php if (!empty($message_text)): ?>
                <p class="message <?php echo htmlspecialchars($message_type); ?>">
                    <?php echo htmlspecialchars($message_text); ?>
                </p>
            <?php endif; ?>

            <?php if (!$idGroupe): ?>
                <div class="choix-content">
                    <p class="message error">Vous n'êtes actuellement affecté à aucun groupe. Veuillez contacter l'administration pour être ajouté à un groupe.</p>
                </div>
            <?php elseif (!$idEncadrantDuGroupe && !$sujet_choisi_par_groupe): ?>
                <div class="choix-content">
                    <p class="message error">Votre groupe n'a pas encore d'encadrant PFE assigné. Vous ne pouvez pas choisir de sujet pour le moment.</p>
                </div>
            <?php elseif ($sujet_choisi_par_groupe): ?>
                <div class="choix-content">
                    <h2>Votre Sujet PFE Actuel</h2>
                    <div class="sujet-details">
                        <p><strong>Titre :</strong> <?php echo htmlspecialchars($sujet_choisi_par_groupe['titreSujetPFE']); ?></p>
                        <p><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($sujet_choisi_par_groupe['descriptions'])); ?></p>
                        <p><strong>Domaine :</strong> <?php echo htmlspecialchars($sujet_choisi_par_groupe['nomDomaine']); ?></p>
                        <p><strong>Proposé par :</strong> <?php echo htmlspecialchars($sujet_choisi_par_groupe['prenomEncadrant'] . ' ' . $sujet_choisi_par_groupe['nomEncadrant']); ?></p>
                        <p class="message success">Votre groupe a déjà choisi ce sujet.</p>
                    </div>
                </div>
            <?php else: // No subject chosen yet, and group has an encadrant ?>
                <div class="choix-content">
                    <h2>Sujets PFE Disponibles Proposés par Votre Encadrant</h2>
                    <?php if (empty($sujets_disponibles)): ?>
                        <p class="message info">Votre encadrant n'a pas encore proposé de sujets PFE, ou tous les sujets proposés ont déjà été choisis.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr><th>Titre</th><th>Description</th><th>Domaine</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sujets_disponibles as $sujet): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sujet['titreSujetPFE']); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars(mb_strimwidth($sujet['descriptions'], 0, 200, "..."))); ?></td>
                                        <td><?php echo htmlspecialchars($sujet['nomDomaine']); ?></td>
                                        <td>
                                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" onsubmit="return confirm('Êtes-vous sûr de vouloir choisir ce sujet ? Ce choix est définitif.');">
                                                <input type="hidden" name="action" value="choisir_sujet">
                                                <input type="hidden" name="idSujetPFE" value="<?php echo htmlspecialchars($sujet['idSujetPFE']); ?>">
                                                <button type="submit" class="btn-choisir">Choisir ce sujet</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
        
    <script>
        const menu_toggle = document.querySelector('.menu-toggle');
        const sidebar = document.querySelector('.sidebar');

        menu_toggle.addEventListener('click', () => {
            menu_toggle.classList.toggle('is-active');
            sidebar.classList.toggle('is-active');
        })
    </script>

    
    
    
    
   
    

</body>
</html>


<?php mysqli_close($conn); ?>