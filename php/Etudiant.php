
<?php
session_start();
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" || !isset($_SESSION["idEtudiant"])) {
    // Rediriger vers la page d'authentification si l'utilisateur n'est pas un étudiant autorisé
    header("location:AuthentificationEtudiant.php");
    exit();
}


$nomEtudiant = $_SESSION["prenomNom"] ?? 'Étudiant'; // Valeur par défaut si non défini
$idEtudiant = $_SESSION["idEtudiant"];

require_once 'Connexion.php'; // Pour les opérations de base de données

$message_info_text = '';
$message_info_type = ''; // 'success' or 'error'

if (isset($_SESSION['message_etudiant_info'])) {
    $message_info_text = $_SESSION['message_etudiant_info'];
    $message_info_type = $_SESSION['message_etudiant_type'] ?? 'info';
    unset($_SESSION['message_etudiant_info'], $_SESSION['message_etudiant_type']);
}

// Gérer la mise à jour du CNE et de la date de naissance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_verification_info') {
    $new_cne = trim($_POST['new_cne'] ?? '');
    $new_date_naissance = trim($_POST['new_date_naissance'] ?? '');
    $errors_update = [];

    if (empty($new_cne)) {
        $errors_update[] = "Le CNE ne peut pas être vide.";
    }
    // Vous pouvez ajouter une validation plus stricte pour le format du CNE ici

    if (empty($new_date_naissance)) {
        $errors_update[] = "La date de naissance ne peut pas être vide.";
    } else {
        // Valider le format de la date YYYY-MM-DD
        $d = DateTime::createFromFormat('Y-m-d', $new_date_naissance);
        if (!$d || $d->format('Y-m-d') !== $new_date_naissance) {
            $errors_update[] = "Format de date de naissance invalide. Utilisez YYYY-MM-DD.";
        }
    }

    if (empty($errors_update)) {
        $stmt_update = mysqli_prepare($conn, "UPDATE Etudiant SET cneEtudiant = ?, dateNaissance = ? WHERE idEtudiant = ?");
        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, "ssi", $new_cne, $new_date_naissance, $idEtudiant);
            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['message_etudiant_info'] = "Vos informations de vérification (CNE et date de naissance) ont été mises à jour avec succès.";
                $_SESSION['message_etudiant_type'] = "success";
            } else {
                $_SESSION['message_etudiant_info'] = "Erreur lors de la mise à jour des informations : " . mysqli_stmt_error($stmt_update);
                $_SESSION['message_etudiant_type'] = "error";
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $_SESSION['message_etudiant_info'] = "Erreur de préparation de la requête de mise à jour : " . mysqli_error($conn);
            $_SESSION['message_etudiant_type'] = "error";
        }
    } else {
        $_SESSION['message_etudiant_info'] = implode("<br>", $errors_update);
        $_SESSION['message_etudiant_type'] = "error";
    }
    header("Location: Etudiant.php"); // Recharger la page pour afficher le message et éviter la resoumission
    exit();
}

// Gérer la mise à jour du mot de passe
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';
    $errors_password = [];

    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $errors_password[] = "Tous les champs de mot de passe sont requis.";
    }
    if ($new_password !== $confirm_new_password) {
        $errors_password[] = "Le nouveau mot de passe et sa confirmation ne correspondent pas.";
    }
    if (strlen($new_password) < 6) { // Exemple de règle de complexité minimale
        $errors_password[] = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
    }

    if (empty($errors_password)) {
        // Récupérer le mot de passe actuel de l'étudiant
        $stmt_check_pass = mysqli_prepare($conn, "SELECT motpasseEtudiant FROM Etudiant WHERE idEtudiant = ?");
        if ($stmt_check_pass) {
            mysqli_stmt_bind_param($stmt_check_pass, "i", $idEtudiant);
            mysqli_stmt_execute($stmt_check_pass);
            $result_pass = mysqli_stmt_get_result($stmt_check_pass);
            $user_data = mysqli_fetch_assoc($result_pass);
            mysqli_stmt_close($stmt_check_pass);

            if ($user_data && password_verify($current_password, $user_data['motpasseEtudiant'])) {
                // Le mot de passe actuel est correct, procéder à la mise à jour
                $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_update_pass = mysqli_prepare($conn, "UPDATE Etudiant SET motpasseEtudiant = ? WHERE idEtudiant = ?");
                if ($stmt_update_pass) {
                    mysqli_stmt_bind_param($stmt_update_pass, "si", $new_password_hashed, $idEtudiant);
                    if (mysqli_stmt_execute($stmt_update_pass)) {
                        $_SESSION['message_etudiant_info'] = "Votre mot de passe a été mis à jour avec succès.";
                        $_SESSION['message_etudiant_type'] = "success";
                    } else {
                        $_SESSION['message_etudiant_info'] = "Erreur lors de la mise à jour du mot de passe : " . mysqli_stmt_error($stmt_update_pass);
                        $_SESSION['message_etudiant_type'] = "error";
                    }
                    mysqli_stmt_close($stmt_update_pass);
                } else {
                    $_SESSION['message_etudiant_info'] = "Erreur de préparation de la requête de mise à jour du mot de passe : " . mysqli_error($conn);
                    $_SESSION['message_etudiant_type'] = "error";
                }
            } else {
                $_SESSION['message_etudiant_info'] = "Le mot de passe actuel est incorrect.";
                $_SESSION['message_etudiant_type'] = "error";
            }
        } else {
            $_SESSION['message_etudiant_info'] = "Erreur lors de la vérification du mot de passe actuel : " . mysqli_error($conn);
            $_SESSION['message_etudiant_type'] = "error";
        }
    } else {
        $_SESSION['message_etudiant_info'] = implode("<br>", $errors_password);
        $_SESSION['message_etudiant_type'] = "error";
    }
    header("Location: Etudiant.php"); // Recharger la page
    exit();
} 

// Récupérer les informations complètes de l'étudiant
$etudiant_data = null;
$sql_etudiant_details = "SELECT e.nomEtudiant, e.prenomEtudiant, e.emailEtudiant, e.cinEtudiant, e.cneEtudiant, e.dateNaissance,
                                g.numGroupe, g.anneeUniversaire,
                                enc.nomEncadrant, enc.prenomEncadrant,
                                sp.titreSujetPFE , sp.descriptions
                         FROM Etudiant e
                         LEFT JOIN Groupe g ON e.idGroupe = g.idGroupe
                         LEFT JOIN Encadrant enc ON g.idEncadrant = enc.idEncadrant
                         LEFT JOIN SujetPFE sp ON g.idEncadrant = sp.idEncadrant
                         WHERE e.idEtudiant = ?";
$stmt_details = mysqli_prepare($conn, $sql_etudiant_details);
if ($stmt_details) {
    mysqli_stmt_bind_param($stmt_details, "i", $idEtudiant);
    mysqli_stmt_execute($stmt_details);
    $result_details = mysqli_stmt_get_result($stmt_details);
    $etudiant_data = mysqli_fetch_assoc($result_details);
    mysqli_stmt_close($stmt_details);
}


  // Fetch recently shared lists for notifications
    $recent_lists = [];
    $sql_recent_lists = "SELECT
                            l.idListe,
                            l.titreListe,
                            l.dateAjout,
                            l.nom_fichier,
                            rp.prenomResponsable,
                            rp.nomResponsable
                        FROM Liste l
                        JOIN ResponsablePFE rp ON l.idResponsable = rp.idResponsable
                        ORDER BY l.dateAjout DESC
                        LIMIT 3"; // Show the 3 most recent lists
    $result_recent_lists = mysqli_query($conn, $sql_recent_lists);
    if ($result_recent_lists) {
        while ($row_list = mysqli_fetch_assoc($result_recent_lists)) {
            $recent_lists[] = $row_list;
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
            width: 300px;
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
             padding: 2rem;
             margin-left : 300px ;  
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

       /* Styles pour les sections d'information */
        .info-section { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top: 20px; margin-bottom: 20px; }
        .info-section h2 { color: #333; border-bottom: 2px solid #2e916e; padding-bottom: 10px; margin-bottom: 15px; font-size: 1.5em; }
        .info-section p { margin-bottom: 10px; line-height: 1.6; }
        .info-section strong { color: #2e916e; }
        .info-section ul { list-style-type: none; padding-left: 0; }
        .info-section ul li { margin-bottom: 8px; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-section div { margin-bottom: 10px; }
         .form-section label { display: block; margin-bottom: 5px; font-weight: bold;}
        .form-section input[type="text"], .form-section input[type="date"], .form-section input[type="password"] { width: calc(100% - 22px); padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-section button { background-color: #2e916e; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; margin-top: 10px; }
        .form-section button:hover { background-color: #257758; }
   
              /* Styles for notifications section */
        .notifications-container {
            margin-top: 30px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .notifications-container h2 {
            color: #2e916e; /* Theme color */
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.5em;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .notification-item {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .notification-item:last-child { border-bottom: none; }
        .notification-item p { margin: 5px 0; font-size: 0.95em; line-height: 1.4; }
        .notification-item strong { color: #333; }
        .notification-item .meta-info { font-size: 0.85em; color: #666; margin-top: 3px; }
        .notification-item a { color: #2e916e; text-decoration: none; font-weight: 500; }
        .notification-item a:hover { text-decoration: underline; }
        .no-notifications { text-align: center; color: #777; padding: 20px 0; }
     

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
                <a href="Etudiant.php" class="menu-item is-active">Home</a>
                <a href="RechExempleEt.php" class="menu-item">Rechercher des exemples PFE</a>
                <a href="RechRessEt.php" class="menu-item">Rechercher des ressources pédagogiques</a>
                <a href="ChoisirSujetEt.php" class="menu-item">Choisir un sujet parmi ceux proposés</a>
                <a href="DeposerRapEt.php" class="menu-item" >Déposer votre PFE</a>
                <a href="deconnexion.php" class="menu-item">Se déconnecter</a>
            </nav>
        </aside>

        <main class="content">
                        <h1>L'espace d'etudiant</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($nomEtudiant); ?> !</p>

            <?php if (!empty($message_info_text)): ?>
                <p class="message <?php echo htmlspecialchars($message_info_type); ?>">
                    <?php echo $message_info_text; // HTML est permis ici si $message_info_text contient des <br> par ex. ?>
                </p>
            <?php endif; ?>

            <?php if ($etudiant_data): ?>
                <div class="info-section">
                    <h2>Mes Informations Personnelles</h2>
                    <p><strong>Nom Complet :</strong> <?php echo htmlspecialchars($etudiant_data['prenomEtudiant'] . ' ' . $etudiant_data['nomEtudiant']); ?></p>
                    <p><strong>Email :</strong> <?php echo htmlspecialchars($etudiant_data['emailEtudiant']); ?></p>
                    <p><strong>CIN :</strong> <?php echo htmlspecialchars($etudiant_data['cinEtudiant']); ?></p>
                    <p><strong>CNE :</strong> <?php echo htmlspecialchars($etudiant_data['cneEtudiant']); ?></p>
                    <p><strong>Date de Naissance :</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($etudiant_data['dateNaissance']))); ?></p>
                </div>
                

 <!-- Notifications Section -->
            <div class="notifications-container">
                <h2>Notifications Récentes (Listes Partagées)</h2>
                <?php if (empty($recent_lists)): ?>
                    <p class="no-notifications">Aucune liste n'a été partagée récemment.</p>
                <?php else: ?>
                    <?php foreach ($recent_lists as $list_item): ?>
                        <div class="notification-item">
                            <p>
                                <strong><?php echo htmlspecialchars($list_item['titreListe']); ?></strong><br>
                                <a href="view_liste_pdf.php?id=<?php echo htmlspecialchars($list_item['idListe']); ?>" target="_blank" title="Voir le PDF: <?php echo htmlspecialchars($list_item['nom_fichier']); ?>">
                                    <?php echo htmlspecialchars(mb_strimwidth($list_item['nom_fichier'], 0, 40, "...")); ?>
                                </a>
                            </p>
                            <p class="meta-info">
                                Partagé par: <?php echo htmlspecialchars($list_item['prenomResponsable'] . ' ' . $list_item['nomResponsable']); ?><br>
                                Le: <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($list_item['dateAjout']))); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

                <div class="info-section">
                    <h2>Mon Groupe PFE et Sujet</h2>
                    <?php if (!empty($etudiant_data['numGroupe'])): ?>
                        <p><strong>Nom du Groupe :</strong> <?php echo htmlspecialchars($etudiant_data['numGroupe']); ?></p>
                        <p><strong>Année Universitaire :</strong> <?php echo htmlspecialchars($etudiant_data['anneeUniversaire']); ?></p>
                        <p><strong>Encadrant :</strong> <?php echo htmlspecialchars($etudiant_data['prenomEncadrant'] . ' ' . $etudiant_data['nomEncadrant']); ?></p>
                        
                        <p><strong>Sujet PFE Choisi :</strong>
                        <?php if (!empty($etudiant_data['titreSujet'])): ?>
                            <p><strong>Titre :</strong> <?php echo htmlspecialchars($etudiant_data['titreSujet']); ?></p>
                            <p><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($etudiant_data['descriptionSujet'])); ?></p>
                        <?php else: ?>
                            <p>Aucun sujet PFE n'a encore été choisi par votre groupe.</p>
                        </p> 
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Vous n'êtes actuellement affecté à aucun groupe PFE. Veuillez contacter l'administration.</p>
                    <?php endif; ?>
                </div>

                <div class="info-section form-section">
                    <h2>Modifier mes informations de vérification</h2>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <input type="hidden" name="action" value="update_verification_info">
                        <div>
                            <label for="new_cne">Nouveau CNE :</label>
                            <input type="text" id="new_cne" name="new_cne" value="<?php echo htmlspecialchars($etudiant_data['cneEtudiant'] ?? ''); ?>" required>
                        </div>
                        <div>
                            <label for="new_date_naissance">Nouvelle Date de Naissance :</label>
                            <input type="date" id="new_date_naissance" name="new_date_naissance" value="<?php echo htmlspecialchars($etudiant_data['dateNaissance'] ?? ''); ?>" required>
                        </div>
                        <div>
                            <label for="current_password">Mot de passe actuel :</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div>
                            <label for="new_password">Nouveau mot de passe :</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        <div>
                            <label for="confirm_new_password">Confirmer le nouveau mot de passe :</label>
                            <input type="password" id="confirm_new_password" name="confirm_new_password" required>
                        </div>
                        <button type="submit">Mettre à jour mes informations</button>
                    </form>
                </div>
            <?php else: ?>
                <p class="message error">Impossible de charger vos informations. Veuillez réessayer plus tard ou contacter l'administration.</p>
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

    
    <?php
mysqli_close($conn);
    ?>
    
    
   
    

</body>
</html>