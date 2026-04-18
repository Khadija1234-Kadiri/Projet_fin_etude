
<?php
session_start();
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" || !isset($_SESSION["idEncadrant"])) {
    header("location:AuthentificationEncadrant.php");
    exit();
}

$nomEncadrant = $_SESSION["prenomNom"] ?? 'Encadrant';
$idEncadrant = $_SESSION["idEncadrant"];

require_once 'Connexion.php';

$message_text = '';
$message_type = '';

if (isset($_SESSION['message_validation_text'])) {
    $message_text = $_SESSION['message_validation_text'];
    $message_type = $_SESSION['message_validation_type'] ?? 'info';
    unset($_SESSION['message_validation_text'], $_SESSION['message_validation_type']);
}

// Handle Feedback and Validation Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_validation_feedback') {
    $idRapport = filter_var($_POST['idRapport'] ?? 0, FILTER_VALIDATE_INT);
    
    $statutValidation = trim($_POST['statutValidation'] ?? '');
    $feedbackContenu = trim($_POST['feedbackContenu'] ?? '');
    $idFeedbackExistant = filter_var($_POST['idFeedbackExistant'] ?? null, FILTER_VALIDATE_INT);

        if (!$idRapport || empty($statutValidation)) {
        $_SESSION['message_validation_text'] = "Informations manquantes pour la mise à jour.";
        $_SESSION['message_validation_type'] = "error";
    } else {
        mysqli_autocommit($conn, FALSE); // Start transaction
        $success = true;
        $newIdFeedback = $idFeedbackExistant;

        // 1. Insert or Update Feedback
        if (!empty($feedbackContenu)) {
            if ($idFeedbackExistant) { // Update existing feedback
                $stmt_feedback = mysqli_prepare($conn, "UPDATE Feedback SET contenu = ?, dateFeedback = NOW(), idEncadrant = ? WHERE idFeedback = ?");
                mysqli_stmt_bind_param($stmt_feedback, "sii", $feedbackContenu, $idEncadrant, $idFeedbackExistant);
            } else { // Insert new feedback
                $stmt_feedback = mysqli_prepare($conn, "INSERT INTO Feedback (contenu, dateFeedback, idEncadrant) VALUES (?, NOW(), ?)");
                mysqli_stmt_bind_param($stmt_feedback, "si", $feedbackContenu, $idEncadrant);
            }

            if (mysqli_stmt_execute($stmt_feedback)) {
                if (!$idFeedbackExistant) {
                    $newIdFeedback = mysqli_insert_id($conn); // Get ID of new feedback
                }
            } else {
                $success = false;
                $_SESSION['message_validation_text'] = "Erreur lors de la sauvegarde du feedback: " . mysqli_stmt_error($stmt_feedback);
            }
            mysqli_stmt_close($stmt_feedback);
        } elseif ($idFeedbackExistant && empty($feedbackContenu)) { // If feedback content is cleared, delete existing feedback
            // Option: Delete feedback if content is empty, or just leave it. For now, let's assume we update Deposer with NULL if feedback is empty.
            // For simplicity, if feedback is emptied, we might want to disassociate it.
            // Let's assume for now that an empty feedback means no feedback or clear existing.
            // If we want to delete the feedback record:
            // $stmt_del_feedback = mysqli_prepare($conn, "DELETE FROM Feedback WHERE idFeedback = ?");
            // mysqli_stmt_bind_param($stmt_del_feedback, "i", $idFeedbackExistant);
            // mysqli_stmt_execute($stmt_del_feedback);
            // mysqli_stmt_close($stmt_del_feedback);
            $newIdFeedback = null; // No feedback associated
        }


        // 2. Update Rapport Status (if feedback operation was successful or no feedback to process)
        if ($success) {
            $stmt_rapport = mysqli_prepare($conn, "UPDATE Rapport SET statutValidation = ? WHERE idRapport = ?");
            mysqli_stmt_bind_param($stmt_rapport, "si", $statutValidation, $idRapport);
            if (!mysqli_stmt_execute($stmt_rapport)) {
                $success = false;
                $_SESSION['message_validation_text'] = "Erreur lors de la mise à jour du statut du rapport: " . mysqli_stmt_error($stmt_rapport);
            }
            mysqli_stmt_close($stmt_rapport);
        }

        // 3. Update Deposer table with newIdFeedback (if feedback operation was successful and rapport status update was successful)
        // This step is crucial if a new feedback was inserted or an existing one was "cleared" (by setting $newIdFeedback to null)
        if ($success && ($newIdFeedback !== $idFeedbackExistant || ($newIdFeedback === null && $idFeedbackExistant !== null))) {
             $stmt_update_deposer_link = mysqli_prepare($conn, "UPDATE Deposer SET idFeedback = ? WHERE idRapport = ?");
            if ($stmt_update_deposer_link) {
                mysqli_stmt_bind_param($stmt_update_deposer_link, "ii", $newIdFeedback, $idRapport); // $newIdFeedback can be null, $idRapport identifies the deposit
                if (!mysqli_stmt_execute($stmt_update_deposer_link)) {
                    $success = false;
                    $_SESSION['message_validation_text'] = "Erreur lors de la liaison du feedback au dépôt: " . mysqli_stmt_error($stmt_update_deposer_link);
                }
                mysqli_stmt_close($stmt_update_deposer_link);
            } else {
                $success = false;
                $_SESSION['message_validation_text'] = "Erreur de préparation (Deposer link): " . mysqli_error($conn);
            }
        }

        if ($success) {
            mysqli_commit($conn);
            $_SESSION['message_validation_text'] = "Validation et feedback mis à jour avec succès.";
            $_SESSION['message_validation_type'] = "success";
        } else {
            mysqli_rollback($conn);
            // Error message already set in session
            $_SESSION['message_validation_type'] = "error";
        }
        mysqli_autocommit($conn, TRUE); // End transaction
    }
    header("Location: valid&FeedEnc.php");
    exit();
}

// Fetch reports for groups supervised by this Encadrant
$rapports_soumis = [];
$sql_fetch_rapports = "SELECT r.idRapport, r.titreRapport, r.nom_fichier, r.fichier_pdf, r.statutValidation, r.dateDepot,
                              g.idGroupe, g.numGroupe, 
                              f.idFeedback, f.contenu AS feedbackContenu, f.dateFeedback AS feedbackDate
                       FROM Rapport r
                       JOIN Deposer d ON r.idRapport = d.idRapport
                       JOIN Groupe g ON d.idGroupe = g.idGroupe
                       LEFT JOIN Feedback f ON d.idFeedback = f.idFeedback
                       WHERE g.idEncadrant = ?
                       ORDER BY r.dateDepot DESC";
$stmt_fetch = mysqli_prepare($conn, $sql_fetch_rapports);
if ($stmt_fetch) {
    mysqli_stmt_bind_param($stmt_fetch, "i", $idEncadrant);
    mysqli_stmt_execute($stmt_fetch);
    $result_fetch = mysqli_stmt_get_result($stmt_fetch);
    while ($row = mysqli_fetch_assoc($result_fetch)) {
        $rapports_soumis[] = $row;
    }
    mysqli_stmt_close($stmt_fetch);
}

$statuts_possibles = ['En attente', 'Validé', 'Modifications demandées', 'Rejeté'];

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


     /* Styles for validation and feedback page */
        .validation-content { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top: 20px; margin-bottom: 20px; }
        .validation-content h2 { color: #333; border-bottom: 2px solid #2e916e; padding-bottom: 10px; margin-top: 0; margin-bottom: 15px; font-size: 1.8em; }
        .validation-content table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .validation-content th, .validation-content td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: top; word-break: break-word; }
        .validation-content th { background-color: #2e916e; color: white; }
        .validation-content tr:nth-child(even) { background-color: #f9f9f9; }
        .validation-content tr:hover { background-color: #f1f1f1; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .feedback-form div { margin-bottom: 10px; }
        .feedback-form label { display: block; margin-bottom: 5px; font-weight: bold;}
        .feedback-form select, .feedback-form textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .feedback-form textarea { min-height: 80px; resize: vertical; }
        .feedback-form button { background-color: #2e916e; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; margin-top: 10px; }
        .feedback-form button:hover { background-color: #257758; }
  


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
                <a href="ProposerSujetEnc.php" class="menu-item">Proposer les sujets PFE</a>
                <a href="valid&FeedEnc.php" class="menu-item is-active">validation et Feesback</a>
                 <a href="deconnexion.php" class="menu-item">Se déconnecter</a>
            </nav>
        </aside>

        <main class="content">
            <h1>Validation des Rapports PFE et Feedback</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($nomEncadrant); ?>. Gérez ici la validation des rapports PFE soumis par vos groupes et fournissez des feedbacks.</p>

            <?php if (!empty($message_text)): ?>
                <p class="message <?php echo htmlspecialchars($message_type); ?>">
                    <?php echo htmlspecialchars($message_text); ?>
                </p>
            <?php endif; ?>

            <div class="validation-content">
                <h2>Rapports Soumis par vos Groupes</h2>
                <?php if (empty($rapports_soumis)): ?>
                    <p>Aucun rapport n'a été soumis par vos groupes pour le moment.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Groupe</th>
                                <th>Titre Rapport</th>
                                <th>Date Dépôt</th>
                                <th>Fichier</th>
                                <th>Statut Actuel</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rapports_soumis as $rapport): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rapport['numGroupe']); ?></td>
                                    <td><?php echo htmlspecialchars($rapport['titreRapport']); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($rapport['dateDepot']))); ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($rapport['fichier_pdf']); ?>" target="_blank" title="Voir <?php echo htmlspecialchars($rapport['nom_fichier']); ?>">
                                            <?php echo htmlspecialchars(mb_strimwidth($rapport['nom_fichier'], 0, 25, "...")); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($rapport['statutValidation']); ?></td>
                                   
                                   
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                  </div>
                  <div class="validation-content">
                <h2>Validation du Rapport</h2>
 
                 
                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="feedback-form">
                                            <input type="hidden" name="action" value="update_validation_feedback">
                                            <input type="hidden" name="idRapport" value="<?php echo htmlspecialchars($rapport['idRapport']); ?>">
                                            
                                            <input type="hidden" name="idFeedbackExistant" value="<?php echo htmlspecialchars($rapport['idFeedback'] ?? ''); ?>">
                                            <div class="validation-section-form" style="margin-bottom: 15px; padding-bottom:10px; border-bottom: 1px solid #eee;">
                                              
                                                <div>
                                                    <label for="statutValidation_<?php echo $rapport['idRapport']; ?>">Nouveau Statut :</label>
                                                    <select id="statutValidation_<?php echo $rapport['idRapport']; ?>" name="statutValidation" required>
                                                        <?php foreach ($statuts_possibles as $statut): ?>
                                                            <option value="<?php echo htmlspecialchars($statut); ?>" <?php echo ($rapport['statutValidation'] == $statut) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($statut); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="feedback-section-form">
                                                <h4 style="margin-top:0; margin-bottom:5px; font-size:1em; color:#555;">Feedback pour le Groupe</h4>
                                                <div>
                                                    <label for="feedbackContenu_<?php echo $rapport['idRapport']; ?>">Commentaires :</label>
                                                    <textarea id="feedbackContenu_<?php echo $rapport['idRapport']; ?>" name="feedbackContenu" rows="3"><?php echo htmlspecialchars($rapport['feedbackContenu'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                            <button type="submit">Mettre à jour Statut et Feedback</button>
                                      
                                        </form>
                                    
                                

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