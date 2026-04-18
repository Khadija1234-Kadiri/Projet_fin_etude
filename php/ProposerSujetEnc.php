

<?php
session_start();
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" || !isset($_SESSION["idEncadrant"])) {
    header("location:AuthentificationEncadrant.php"); // Redirect to login if not authorized
    exit();
}

$nomEncadrant = $_SESSION["prenomNom"] ?? 'Encadrant';
$idEncadrant = $_SESSION["idEncadrant"];

require_once 'Connexion.php'; // Database connection

$message_text = '';
$message_type = ''; // 'success' or 'error'

if (isset($_SESSION['message_sujet_text'])) {
    $message_text = $_SESSION['message_sujet_text'];
    $message_type = $_SESSION['message_sujet_type'] ?? 'info';
    unset($_SESSION['message_sujet_text'], $_SESSION['message_sujet_type']);
}

// Fetch Domaines for the form
$domaines = [];
$sql_domaines = "SELECT idDomaine, nomDomaine FROM Domaine ORDER BY nomDomaine ASC";
$result_domaines_query = mysqli_query($conn, $sql_domaines);
if ($result_domaines_query) {
    while ($row = mysqli_fetch_assoc($result_domaines_query)) {
        $domaines[] = $row;
    }
}

// Handle ADD Subject
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_sujet') {
    $titreSujet = trim($_POST['titreSujetPFE'] ?? '');
    $descriptionSujet = trim($_POST['descriptions'] ?? '');
    $idDomaine = filter_var($_POST['idDomaine'] ?? '', FILTER_VALIDATE_INT);

    if (empty($titreSujet) || empty($descriptionSujet) || !$idDomaine) {
        $_SESSION['message_sujet_text'] = "Le titre, la description et le domaine sont requis.";
        $_SESSION['message_sujet_type'] = "error";
    } else {
        $stmt_insert = mysqli_prepare($conn, "INSERT INTO SujetPFE (titreSujetPFE, descriptions, idEncadrant, idDomaine, dateProposition, estChoisi)  VALUES (?, ?, ?, ?, NOW(), 0)");
        if ($stmt_insert) {
            mysqli_stmt_bind_param($stmt_insert, "ssii", $titreSujet, $descriptionSujet, $idEncadrant, $idDomaine);
            if (mysqli_stmt_execute($stmt_insert)) {
                $_SESSION['message_sujet_text'] = "Sujet PFE proposé avec succès.";
                $_SESSION['message_sujet_type'] = "success";
            } else {
                $_SESSION['message_sujet_text'] = "Erreur lors de la proposition du sujet : " . mysqli_stmt_error($stmt_insert);
                $_SESSION['message_sujet_type'] = "error";
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            $_SESSION['message_sujet_text'] = "Erreur de préparation de la requête : " . mysqli_error($conn);
            $_SESSION['message_sujet_type'] = "error";
        }
    }
    header("Location: ProposerSujetEnc.php");
    exit();
}

// Handle DELETE Subject
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_sujet') {
    $idSujetToDelete = filter_var($_POST['idSujetPFE'] ?? 0, FILTER_VALIDATE_INT);

    if ($idSujetToDelete > 0) {
        // Check if the subject is not chosen and belongs to the encadrant
        $stmt_check = mysqli_prepare($conn, "SELECT estChoisi FROM SujetPFE WHERE idSujetPFE = ? AND idEncadrant = ?");
        mysqli_stmt_bind_param($stmt_check, "ii", $idSujetToDelete, $idEncadrant);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        if ($sujet_data = mysqli_fetch_assoc($result_check)) {
            if ($sujet_data['estChoisi'] == 0) {
                $stmt_delete = mysqli_prepare($conn, "DELETE FROM SujetPFE WHERE idSujetPFE = ?");
                mysqli_stmt_bind_param($stmt_delete, "i", $idSujetToDelete);
                if (mysqli_stmt_execute($stmt_delete)) {
                    $_SESSION['message_sujet_text'] = "Sujet PFE supprimé avec succès.";
                    $_SESSION['message_sujet_type'] = "success";
                } else {
                    $_SESSION['message_sujet_text'] = "Erreur lors de la suppression du sujet : " . mysqli_stmt_error($stmt_delete);
                    $_SESSION['message_sujet_type'] = "error";
                }
                mysqli_stmt_close($stmt_delete);
            } else {
                $_SESSION['message_sujet_text'] = "Ce sujet a déjà été choisi par un groupe et ne peut pas être supprimé.";
                $_SESSION['message_sujet_type'] = "error";
            }
        } else {
            $_SESSION['message_sujet_text'] = "Sujet non trouvé ou non autorisé à supprimer.";
            $_SESSION['message_sujet_type'] = "error";
        }
        mysqli_stmt_close($stmt_check);
    }
    header("Location: ProposerSujetEnc.php");
    exit();
}

// Fetch existing subjects proposed by this Encadrant
$sujets_proposes = [];
$sql_fetch_sujets = "SELECT s.idSujetPFE, s.titreSujetPFE, s.descriptions, s.dateProposition, s.estChoisi, 
                            d.nomDomaine, 
                            g.numGroupe AS nomGroupeAyantChoisi
                     FROM SujetPFE s
                     JOIN Domaine d ON s.idDomaine = d.idDomaine
                      LEFT JOIN choisir c ON c.idSujetPFE = s.idSujetPFE
                     LEFT JOIN Groupe g ON c.idGroupe = g.idGroupe
                     WHERE s.idEncadrant = ?
                     ORDER BY s.dateProposition DESC";
$stmt_fetch = mysqli_prepare($conn, $sql_fetch_sujets);
if ($stmt_fetch) {
    mysqli_stmt_bind_param($stmt_fetch, "i", $idEncadrant);
    mysqli_stmt_execute($stmt_fetch);
    $result_fetch = mysqli_stmt_get_result($stmt_fetch);
    while ($row = mysqli_fetch_assoc($result_fetch)) {
        $sujets_proposes[] = $row;
    }
    mysqli_stmt_close($stmt_fetch);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>


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
/*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/

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
              position: fixed;
             top: 0;
             left: 0;
             height: 100vh; /* Full viewport height */
             z-index: 1000; /* Ensure sidebar is on top */
             overflow-y: auto; /* Scroll for sidebar content if it overflows */
           width: 300px ;
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
              margin-left: 300px; 
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

        /* Styles for form and table */
        .management-content { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top: 20px; margin-bottom: 20px; }
        .management-content h2 { color: #333; border-bottom: 2px solid #2e916e; padding-bottom: 10px; margin-top: 0; margin-bottom: 15px; font-size: 1.8em; }
        .management-content table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .management-content th, .management-content td { border: 1px solid #ddd; padding: 10px; text-align: left; word-break: break-word; }
        .management-content th { background-color: #2e916e; color: white; }
        .management-content tr:nth-child(even) { background-color: #f9f9f9; }
        .management-content tr:hover { background-color: #f1f1f1; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-section { margin-bottom: 30px; padding: 15px; border: 1px solid #eee; border-radius: 5px; background-color: #fdfdfd;}
        .form-section div { margin-bottom: 10px; }
        .form-section label { display: block; margin-bottom: 5px; font-weight: bold;}
        .form-section input[type="text"], .form-section textarea, .form-section select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-section textarea { min-height: 100px; resize: vertical; }
        .form-section button { background-color: #2e916e; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; margin-top: 10px; }
        .form-section button:hover { background-color: #257758; }
        .delete-button { background-color: #dc3545; color:white; padding: 5px 10px; border:none; border-radius:4px; cursor:pointer; font-size:0.9em; }
        .delete-button:hover { background-color: #c82333; }
   

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
                <a href="Encadrant.php" class="menu-item">Home</a>
                <a href="ProposerSujetEnc.php" class="menu-item is-active">Proposer les sujets PFE</a>
                <a href="valid&FeedEnc.php" class="menu-item">validation et Feesback</a>
               <a href="deconnexion.php" class="menu-item">Se déconnecter</a>
 
            </nav>
        </aside>

        <main class="content">
             <h1>Proposer des Sujets PFE</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($nomEncadrant); ?>. Utilisez cette page pour proposer de nouveaux sujets PFE à vos étudiants.</p>

            <?php if (!empty($message_text)): ?>
                <p class="message <?php echo htmlspecialchars($message_type); ?>">
                    <?php echo htmlspecialchars($message_text); ?>
                </p>
            <?php endif; ?>

            <div class="management-content">
                <div class="form-section">
                    <h2>Proposer un Nouveau Sujet PFE</h2>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <input type="hidden" name="action" value="add_sujet">
                        <div>
                            <label for="titreSujetPFE">Titre du Sujet :</label>
                            <input type="text" id="titreSujetPFE" name="titreSujetPFE" required>
                        </div>
                        <div>
                            <label for="descriptions">Description :</label>
                            <textarea id="descriptions" name="descriptions" required></textarea>
                        </div>
                        <div>
                            <label for="idDomaine">Domaine :</label>
                            <select id="idDomaine" name="idDomaine" required>
                                <option value="">-- Choisir un domaine --</option>
                                <?php foreach ($domaines as $domaine): ?>
                                    <option value="<?php echo htmlspecialchars($domaine['idDomaine']); ?>">
                                        <?php echo htmlspecialchars($domaine['nomDomaine']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit">Proposer le Sujet</button>
                    </form>
                </div>
            </div>

            <div class="management-content">
                <h2>Mes Sujets Proposés</h2>
                <?php if (empty($sujets_proposes)): ?>
                    <p>Vous n'avez proposé aucun sujet pour le moment.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr><th>Titre</th><th>Description</th><th>Domaine</th><th>Date Proposition</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sujets_proposes as $sujet): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sujet['titreSujetPFE']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars(mb_strimwidth($sujet['descriptions'], 0, 150, "..."))); ?></td>
                                    <td><?php echo htmlspecialchars($sujet['nomDomaine']); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($sujet['dateProposition']))); ?></td>
                                    <td>
                                        <?php if ($sujet['estChoisi']): ?>
                                            <span style="color: green; font-weight: bold;">Choisi</span>
                                            <?php if (!empty($sujet['nomGroupeAyantChoisi'])): ?>
                                                (par <?php echo htmlspecialchars($sujet['nomGroupeAyantChoisi']); ?>)
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: blue;">Disponible</span>
                                        <?php endif; ?>
                                    </td>
                                   
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

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