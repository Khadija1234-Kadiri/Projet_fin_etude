<?php
session_start();
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" || !isset($_SESSION["idEtudiant"])) {
    header("location:index.php");
    exit();
}
$nomEtudiant = $_SESSION["prenomNom"] ?? 'Étudiant';
$idEtudiant = $_SESSION["idEtudiant"];

require_once 'Connexion.php'; // For database operations

$message_text = '';
$message_type = ''; // 'success' or 'error'
if (isset($_SESSION['message_depot_text'])) {
    $message_text = $_SESSION['message_depot_text'];
    $message_type = $_SESSION['message_depot_type'] ?? 'info';
    unset($_SESSION['message_depot_text'], $_SESSION['message_depot_type']);
}

// Get student's group ID
$idGroupe = null;
$stmt_groupe = mysqli_prepare($conn, "SELECT idGroupe FROM Etudiant WHERE idEtudiant = ?");
if ($stmt_groupe) {
    mysqli_stmt_bind_param($stmt_groupe, "i", $idEtudiant);
    mysqli_stmt_execute($stmt_groupe);
    $result_groupe = mysqli_stmt_get_result($stmt_groupe);
    if ($row_groupe = mysqli_fetch_assoc($result_groupe)) {
        $idGroupe = $row_groupe['idGroupe'];
    }
    mysqli_stmt_close($stmt_groupe);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_rapport'])) {
    if (empty($idGroupe)) {
        $_SESSION['message_depot_text'] = "Erreur : Vous n'êtes affecté à aucun groupe. Dépôt impossible.";
        $_SESSION['message_depot_type'] = "error";
    } else {
        $titreRapport = trim($_POST['titreRapport'] ?? '');

        if (empty($titreRapport)) {
            $_SESSION['message_depot_text'] = "Erreur : Le titre du rapport est requis.";
            $_SESSION['message_depot_type'] = "error";
        } elseif (isset($_FILES['fichierRapport']) && $_FILES['fichierRapport']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['fichierRapport']['tmp_name'];
            $file_name_original = basename($_FILES['fichierRapport']['name']); // basename for security
            $file_size = $_FILES['fichierRapport']['size'];
            $file_type = $_FILES['fichierRapport']['type'];
            $file_extension = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));

            $allowed_extensions = ['pdf'];
            $max_file_size = 20 * 1024 * 1024; // 20 MB
            $upload_dir = 'uploads/rapportsPFE/'; 

            if (!in_array($file_extension, $allowed_extensions)) {
                $_SESSION['message_depot_text'] = "Erreur : Seuls les fichiers PDF sont autorisés.";
                $_SESSION['message_depot_type'] = "error";
            } elseif ($file_size > $max_file_size) {
                $_SESSION['message_depot_text'] = "Erreur : Le fichier est trop volumineux (max 20MB).";
                $_SESSION['message_depot_type'] = "error";
            } elseif ($file_type !== 'application/pdf') {
                $_SESSION['message_depot_text'] = "Erreur : Type de fichier incorrect. Seuls les PDF sont acceptés.";
                $_SESSION['message_depot_type'] = "error";
            } else {
                if (!is_dir($upload_dir)) { // Check if directory exists
                    if (!mkdir($upload_dir, 0777, true)) { // Create directory if it doesn't exist
                        $_SESSION['message_depot_text'] = "Erreur : Impossible de créer le dossier de téléversement.";
                        $_SESSION['message_depot_type'] = "error";
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit();
                    }
                }
                $file_name_stocke = uniqid('rapportPFE_' . $idGroupe . '_', true) . '.' . $file_extension;
                $destination_path = $upload_dir . $file_name_stocke;

                if (move_uploaded_file($file_tmp_path, $destination_path)) {
                    // Check if the group already has a report being processed or validated
                    $sql_check_existing = "SELECT r.idRapport 
                                           FROM rapport r
                                           JOIN deposer d ON r.idRapport = d.idRapport
                                           WHERE d.idGroupe = ? AND r.statutValidation IN ('En attente', 'Validé', 'Modifications demandées')";
                    $stmt_check = mysqli_prepare($conn, $sql_check_existing);
                    mysqli_stmt_bind_param($stmt_check, "i", $idGroupe);
                    mysqli_stmt_execute($stmt_check);
                    $res_check = mysqli_stmt_get_result($stmt_check);
                    if (mysqli_num_rows($res_check) > 0) {
                         $_SESSION['message_depot_text'] = "Votre groupe a déjà un rapport PFE en cours de traitement ou validé. Vous ne pouvez pas en déposer un nouveau.";
                         $_SESSION['message_depot_type'] = "error";
                         unlink($destination_path); 
                    } else {
                        mysqli_stmt_close($stmt_check);

                        mysqli_autocommit($conn, FALSE); // Start transaction

                        // Insert into 'rapport' table
                        // Assuming 'dateDepot' is handled by DB (e.g., DEFAULT CURRENT_TIMESTAMP) or use NOW()
                        // 'fichier_pdf' stores the path, 'nom_fichier' stores the original name
                        $sql_insert_rapport = "INSERT INTO rapport (titreRapport, nom_fichier, fichier_pdf, type_mime, taille, statutValidation, dateDepot) 
                                               VALUES (?, ?, ?, ?, ?, 'En attente', NOW())";
                        $stmt_insert_rapport = mysqli_prepare($conn, $sql_insert_rapport);
                        if ($stmt_insert_rapport) {
                            mysqli_stmt_bind_param($stmt_insert_rapport, "ssssi", $titreRapport, $file_name_original, $destination_path, $file_type, $file_size);
                            if (mysqli_stmt_execute($stmt_insert_rapport)) {
                                $idRapport = mysqli_insert_id($conn);

                                // Insert into 'deposer' table
                                $sql_insert_deposer = "INSERT INTO deposer (idGroupe, idRapport) VALUES (?, ?)";
                                $stmt_insert_deposer = mysqli_prepare($conn, $sql_insert_deposer);
                                if ($stmt_insert_deposer) {
                                    mysqli_stmt_bind_param($stmt_insert_deposer, "ii", $idGroupe, $idRapport);
                                    if (mysqli_stmt_execute($stmt_insert_deposer)) {
                                        mysqli_commit($conn);
                                        $_SESSION['message_depot_text'] = "Rapport PFE déposé avec succès.";
                                        $_SESSION['message_depot_type'] = "success";
                                    } else {
                                        mysqli_rollback($conn);
                                        $_SESSION['message_depot_text'] = "Erreur BD (deposer): " . mysqli_stmt_error($stmt_insert_deposer);
                                        $_SESSION['message_depot_type'] = "error";
                                        unlink($destination_path);
                                    }
                                    mysqli_stmt_close($stmt_insert_deposer);
                                } else {
                                    mysqli_rollback($conn);
                                    $_SESSION['message_depot_text'] = "Erreur préparation BD (deposer): " . mysqli_error($conn);
                                    $_SESSION['message_depot_type'] = "error";
                                    unlink($destination_path);
                                }
                            } else {
                                   mysqli_rollback($conn);
                                $_SESSION['message_depot_text'] = "Erreur BD (rapport): " . mysqli_stmt_error($stmt_insert_rapport);
                                $_SESSION['message_depot_type'] = "error";
                                unlink($destination_path); 
                            }
                             mysqli_stmt_close($stmt_insert_rapport);
                        } else {
                             mysqli_rollback($conn);
                            $_SESSION['message_depot_text'] = "Erreur préparation BD (rapport): " . mysqli_error($conn);
                            $_SESSION['message_depot_type'] = "error";
                            unlink($destination_path);
                        }
                         mysqli_autocommit($conn, TRUE); // End transaction
                    }
                } else {
                    $_SESSION['message_depot_text'] = "Erreur lors du déplacement du fichier téléversé.";
                    $_SESSION['message_depot_type'] = "error";
                }
            }
        } else {
            $_SESSION['message_depot_text'] = "Erreur lors du téléversement ou fichier non sélectionné. Code: " . ($_FILES['fichierRapport']['error'] ?? 'N/A');
            $_SESSION['message_depot_type'] = "error";
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}


// HANDLE DELETE ACTION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_rapport') {
    $idRapportToDelete = filter_var($_POST['idRapport'] ?? 0, FILTER_VALIDATE_INT);

    if ($idRapportToDelete > 0 && $idGroupe) {
        // Fetch report details for deletion (file path, feedback ID, status)
        // Ensure the report belongs to the student's group
        $sql_fetch_details_for_delete = "SELECT r.fichier_pdf, r.statutValidation, d.idFeedback
                                         FROM rapport r
                                         JOIN deposer d ON r.idRapport = d.idRapport
                                         WHERE r.idRapport = ? AND d.idGroupe = ?";
        $stmt_fetch_del = mysqli_prepare($conn, $sql_fetch_details_for_delete);

        if ($stmt_fetch_del) {
            mysqli_stmt_bind_param($stmt_fetch_del, "ii", $idRapportToDelete, $idGroupe);
            mysqli_stmt_execute($stmt_fetch_del);
            $result_del_details = mysqli_stmt_get_result($stmt_fetch_del);
            $report_details = mysqli_fetch_assoc($result_del_details);
            mysqli_stmt_close($stmt_fetch_del);

            if ($report_details) {
                if ($report_details['statutValidation'] == 'Validé') {
                    $_SESSION['message_depot_text'] = "Impossible de supprimer un rapport qui a déjà été validé.";
                    $_SESSION['message_depot_type'] = "error";
                } else {
                    mysqli_autocommit($conn, FALSE); // Start transaction
                    $success = true;

                    // 1. Delete from 'deposer'
                    $stmt_del_deposer = mysqli_prepare($conn, "DELETE FROM deposer WHERE idRapport = ? AND idGroupe = ?");
                    mysqli_stmt_bind_param($stmt_del_deposer, "ii", $idRapportToDelete, $idGroupe);
                    if (!mysqli_stmt_execute($stmt_del_deposer)) $success = false;
                    mysqli_stmt_close($stmt_del_deposer);

                    // 2. Delete from 'feedback' if linked
                    if ($success && $report_details['idFeedback']) {
                        $stmt_del_feedback = mysqli_prepare($conn, "DELETE FROM feedback WHERE idFeedback = ?");
                        mysqli_stmt_bind_param($stmt_del_feedback, "i", $report_details['idFeedback']);
                        if (!mysqli_stmt_execute($stmt_del_feedback)) $success = false;
                        mysqli_stmt_close($stmt_del_feedback);
                    }

                    // 3. Delete from 'rapport'
                    if ($success) {
                        $stmt_del_rapport = mysqli_prepare($conn, "DELETE FROM rapport WHERE idRapport = ?");
                        mysqli_stmt_bind_param($stmt_del_rapport, "i", $idRapportToDelete);
                        if (!mysqli_stmt_execute($stmt_del_rapport)) $success = false;
                        mysqli_stmt_close($stmt_del_rapport);
                    }

                    if ($success) {
                        mysqli_commit($conn);
                        if (file_exists($report_details['fichier_pdf'])) {
                            unlink($report_details['fichier_pdf']);
                        }
                        $_SESSION['message_depot_text'] = "Rapport (ID: " . $idRapportToDelete . ") supprimé avec succès.";
                        $_SESSION['message_depot_type'] = "success";
                    } else {
                        mysqli_rollback($conn);
                        $_SESSION['message_depot_text'] = "Erreur lors de la suppression du rapport en base de données.";
                        $_SESSION['message_depot_type'] = "error";
                    }
                    mysqli_autocommit($conn, TRUE); // End transaction
                }
            } else {
                $_SESSION['message_depot_text'] = "Rapport non trouvé ou vous n'êtes pas autorisé à le supprimer.";
                $_SESSION['message_depot_type'] = "error";
            }
        } else {
            $_SESSION['message_depot_text'] = "Erreur de préparation de la requête de suppression.";
            $_SESSION['message_depot_type'] = "error";
        }
    } else {
        $_SESSION['message_depot_text'] = "Informations invalides pour la suppression du rapport.";
        $_SESSION['message_depot_type'] = "error";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}




// Fetch existing submissions for the student's group
$rapports_deposes = [];
if ($idGroupe) {
    $sql_fetch_rapports = "SELECT r.idRapport, r.titreRapport, r.nom_fichier AS nomFichierOriginal, r.dateDepot,
                                  r.statutValidation, d.idFeedback, f.contenu AS feedbackContenu, f.dateFeedback, r.fichier_pdf AS cheminFichier
                           FROM rapport r
                           JOIN deposer d ON r.idRapport = d.idRapport
                           LEFT JOIN feedback f ON d.idFeedback = f.idFeedback
                           WHERE d.idGroupe = ?
                           ORDER BY r.dateDepot DESC";
    $stmt_fetch = mysqli_prepare($conn, $sql_fetch_rapports);
    if ($stmt_fetch) {
        mysqli_stmt_bind_param($stmt_fetch, "i", $idGroupe);
        mysqli_stmt_execute($stmt_fetch);
        $result_fetch = mysqli_stmt_get_result($stmt_fetch);
        while ($row = mysqli_fetch_assoc($result_fetch)) {
            $rapports_deposes[] = $row;
        }
        mysqli_stmt_close($stmt_fetch);
    }
}

// Find the latest report that can be deleted (not 'Validé')
$latest_deletable_report_id = null;
if (!empty($rapports_deposes)) {
    foreach ($rapports_deposes as $rep) { // $rapports_deposes is already sorted by dateDepot DESC
        if ($rep['statutValidation'] != 'Validé') {
            $latest_deletable_report_id = $rep['idRapport'];
            break; // Found the most recent one
        }
    }
}

?>


<!DOCTYPE html>
<html lang="fr">
<head>
      <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

  /* Styles for form and table */
        .depot-content { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top: 20px; }
        .depot-content h2 { color: #333; border-bottom: 2px solid #2e916e; padding-bottom: 10px; margin-top: 20px;}
        .depot-content table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .depot-content th, .depot-content td { border: 1px solid #ddd; padding: 10px; text-align: left; word-break: break-word; }
        .depot-content th { background-color: #2e916e; color: white; }
        .depot-content tr:nth-child(even) { background-color: #f9f9f9; }
        .depot-content tr:hover { background-color: #f1f1f1; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.info { background-color: #e7f3fe; color: #31708f; border: 1px solid #bce8f1; }
        .form-section { margin-bottom: 30px; padding: 15px; border: 1px solid #eee; border-radius: 5px; background-color: #fdfdfd;}
        .form-section div { margin-bottom: 10px; }
        .form-section label { display: block; margin-bottom: 5px; font-weight: bold;}
        .form-section input[type="text"], .form-section input[type="file"] { width: calc(100% - 22px); padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        
        .form-section button { background-color: #2e916e; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; margin-top: 10px; }
        
         .btn-delete { background-color: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size:0.9em; }
        .btn-delete:hover { background-color: #c82333; }
      
    
        .form-section button:hover { background-color: #257758; }
  


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
                <a href="ChoisirSujetEt.php" class="menu-item">Choisir un sujet parmi ceux proposés</a>
                <a href="DeposerRapEt.php" class="menu-item is-active" >Déposer votre PFE</a>
                <a href="deconnexion.php" class="menu-item">Se déconnecter</a>
            </nav>
        </aside>

        <main class="content">
                       <h1>Déposer votre Rapport PFE</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($nomEtudiant); ?>. Utilisez cette page pour déposer votre rapport de PFE.</p>

            <div class="depot-content">
                <?php if (!empty($message_text)): ?>
                    <p class="message <?php echo htmlspecialchars($message_type); ?>">
                        <?php echo htmlspecialchars($message_text); ?>
                    </p>
                <?php endif; ?>

                <?php if (empty($idGroupe)): ?>
                    <p class="message error">Vous n'êtes actuellement affecté à aucun groupe. Veuillez contacter l'administration.</p>
                <?php else: ?>
                    <div class="form-section">
                        <h2>Nouveau dépôt</h2>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                            <div>
                                <label for="titreRapport">Titre du Rapport :</label>
                                <input type="text" id="titreRapport" name="titreRapport" required>
                            </div>
                            <div>
                                <label for="fichierRapport">Fichier PDF du Rapport  :</label>
                                <input type="file" id="fichierRapport" name="fichierRapport" accept=".pdf" required>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; margin-top:15px;">
                                <button type="submit" name="submit_rapport">Déposer le Rapport</button>
                                <?php if ($latest_deletable_report_id): ?>
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer le dernier rapport déposé (non validé) ? Cette action est irréversible.');" style="margin:0;">
                                        <input type="hidden" name="action" value="delete_rapport">
                                        <input type="hidden" name="idRapport" value="<?php echo htmlspecialchars($latest_deletable_report_id); ?>">
                                        <button type="submit" name="delete_latest_rapport" class="btn-delete">Supprimer dernier dépôt</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <h2>Historique des dépôts de votre groupe</h2>
                <?php if (empty($rapports_deposes) && $idGroupe): ?>
                    <p>Aucun rapport n'a encore été déposé par votre groupe.</p>
                <?php elseif (!empty($rapports_deposes)): ?>
                    <table>
                        <thead>
                            <tr><th>Titre</th><th>Fichier</th><th>Date Dépôt</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rapports_deposes as $rapport): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rapport['titreRapport']); ?></td>
                                   <td><a href="<?php echo htmlspecialchars($rapport['cheminFichier']); ?>" target="_blank" title="Télécharger/Voir <?php echo htmlspecialchars($rapport['nomFichierOriginal']); ?>"><?php echo htmlspecialchars(mb_strimwidth($rapport['nomFichierOriginal'], 0, 30, "...")); ?></a></td>                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($rapport['dateDepot']))); ?></td>
                                    <td><?php echo htmlspecialchars($rapport['statutValidation']); ?></td>
                                    
                                    
                                      
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <table>
                        <thead>
                    <th>Feedback</th>
                </thead>
                    <tr><td><?php echo nl2br(htmlspecialchars($rapport['feedbackContenu'] ?? '')); ?></td></tr>
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

    
    
    
    <?php
mysqli_close($conn);
?>
   
    

</body>
</html>