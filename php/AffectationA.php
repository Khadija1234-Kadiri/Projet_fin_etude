
<?php 
session_start(); 
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" ||!isset($_SESSION["idAdministrateur"])) { 
    // Rediriger vers la page d'accueil si l'utilisateur n'est pas autorisé
    header("location:index.php"); 
    exit(); 
} 

       $nomAdministrateur = $_SESSION["nomAdministrateur"] ?? 'Administrateur'; // Standardisation de la variable de session
    $idAdministrateur = $_SESSION["idAdministrateur"];

    // Configuration de la base de données
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "gestionPFE";

    // Connexion à la base de données
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

    if (!$conn) {
        die("Erreur de connexion : " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, "utf8");

    $message = "";

    // Récupérer le message de la session s'il existe
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
    }

    // --- GESTION DES ACTIONS ---

    // AJOUTER UNE AFFECTATION (CRÉER UN GROUPE ET LUI ASSIGNER UN ENCADRANT ET DES ÉTUDIANTS)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'assign_group') {
        $idEncadrant = filter_var($_POST['idEncadrant'] ?? 0, FILTER_VALIDATE_INT);
        $idEtudiants = $_POST['idEtudiants'] ?? []; // Array of student IDs
        $anneeUniversitaire = trim($_POST['anneeUniversaire'] ?? '');
        $numGroupe = trim($_POST['numGroupe'] ?? ''); // Optionnel

        if (empty($numGroupe)) {
            $numGroupe = "Groupe PFE " . date("YmdHis"); // Auto-generate a name if empty
        }

        // Validation
        if ($idEncadrant > 0 && !empty($idEtudiants) && !empty($anneeUniversitaire)) {
            if (count($idEtudiants) > 0 && count($idEtudiants) <= 3) { // Limiter la taille du groupe (ex: 1 à 3)
                
                // Vérifier si les étudiants ne sont pas déjà dans un groupe pour cette année universitaire
                $can_assign = true;
                foreach ($idEtudiants as $idEtudiant) {
                    $idEtudiant = filter_var($idEtudiant, FILTER_VALIDATE_INT);
                    if (!$idEtudiant) {
                        $can_assign = false;
                        $_SESSION['message'] = "Erreur : ID étudiant invalide.";
                        break;
                    }
                    $sql_check_student =  "SELECT COUNT(eg.idEtudiant) as count  
                                          FROM Etudiant eg
                                          JOIN Groupe g ON eg.idGroupe = g.idGroupe
                                          WHERE eg.idEtudiant = ? AND g.anneeUniversaire = ?";
                    $stmt_check = mysqli_prepare($conn, $sql_check_student);
                    mysqli_stmt_bind_param($stmt_check, "is", $idEtudiant, $anneeUniversitaire);
                    mysqli_stmt_execute($stmt_check);
                    $res_check = mysqli_stmt_get_result($stmt_check);
                    $row_check = mysqli_fetch_assoc($res_check);
                    mysqli_stmt_close($stmt_check);
                    if ($row_check['count'] > 0) {
                        $_SESSION['message'] = "Erreur : L'étudiant ID " . $idEtudiant . " est déjà affecté à un groupe pour l'année " . htmlspecialchars($anneeUniversitaire) . ".";
                        $can_assign = false;
                        break;
                    }
                }

                if ($can_assign) {
                    mysqli_autocommit($conn, FALSE); // Start transaction
                    $sql_insert_groupe = "INSERT INTO Groupe (numGroupe, idEncadrant, anneeUniversaire) VALUES (?, ?, ?)";
                    $stmt_groupe = mysqli_prepare($conn, $sql_insert_groupe);
                    if ($stmt_groupe) {
                        mysqli_stmt_bind_param($stmt_groupe, "sis", $numGroupe, $idEncadrant, $anneeUniversitaire);
                        if (mysqli_stmt_execute($stmt_groupe)) {
                            $idGroupe = mysqli_insert_id($conn);
                            $all_students_assigned = true;
                            foreach ($idEtudiants as $idEtudiant) {
                                $idEtudiant_filtered = filter_var($idEtudiant, FILTER_VALIDATE_INT);
                                $sql_update_etudiant = "UPDATE Etudiant SET idGroupe = ? WHERE idEtudiant = ?";
                                $stmt_etudiant_groupe = mysqli_prepare($conn, $sql_update_etudiant);
                                 if ($stmt_etudiant_groupe) {
                                    mysqli_stmt_bind_param($stmt_etudiant_groupe, "ii", $idGroupe, $idEtudiant_filtered); // Ordre des paramètres : idGroupe, puis idEtudiant
                                    if (!mysqli_stmt_execute($stmt_etudiant_groupe)) { // Ligne 93
                                        $all_students_assigned = false;
                                        $_SESSION['message'] = "Erreur lors de l'assignation de l'étudiant ID " . $idEtudiant_filtered . " au groupe : " . mysqli_stmt_error($stmt_etudiant_groupe);
                                        mysqli_stmt_close($stmt_etudiant_groupe);
                                        break;
                                    }
                                    mysqli_stmt_close($stmt_etudiant_groupe);
                                } else {
                                     $all_students_assigned = false;
                                    $_SESSION['message'] = "Erreur de préparation (étudiant-groupe): " . mysqli_error($conn);
                                    break;
                                }
                            }

                            if ($all_students_assigned) {
                                mysqli_commit($conn);
                                $_SESSION['message'] = "Groupe '" . htmlspecialchars($numGroupe) . "' affecté avec succès.";
                            } else {
                                mysqli_rollback($conn);
                                // Message d'erreur déjà défini dans la boucle
                            }
                        } else {
                            mysqli_rollback($conn);
                            $_SESSION['message'] = "Erreur lors de la création du groupe : " . mysqli_stmt_error($stmt_groupe);
                        }
                        mysqli_stmt_close($stmt_groupe);
                    } else {
                        mysqli_rollback($conn);
                        $_SESSION['message'] = "Erreur de préparation de la requête (groupe) : " . mysqli_error($conn);
                    }
                    mysqli_autocommit($conn, TRUE); // End transaction
                }
            } else {
                 $_SESSION['message'] = "Un groupe doit contenir entre 1 et 3 étudiants.";
            }
        } else {
            $_SESSION['message'] = "Veuillez sélectionner un encadrant, au moins un étudiant et spécifier l'année universitaire.";
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // SUPPRIMER UNE AFFECTATION (SUPPRIMER LE GROUPE)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_group') {
        $idGroupe_to_delete = filter_var($_POST['idGroupe'] ?? 0, FILTER_VALIDATE_INT);
        if ($idGroupe_to_delete > 0) {
            // La suppression en cascade devrait gérer Etudiant_Groupe
            $sql_delete_groupe = "DELETE FROM Groupe WHERE idGroupe = ?";
            $stmt_delete = mysqli_prepare($conn, $sql_delete_groupe);
            if ($stmt_delete) {
                mysqli_stmt_bind_param($stmt_delete, "i", $idGroupe_to_delete);
                if (mysqli_stmt_execute($stmt_delete)) {
                    if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                        $_SESSION['message'] = "Groupe (ID: " . $idGroupe_to_delete . ") et ses affectations supprimés avec succès.";
                    } else {
                        $_SESSION['message'] = "Aucun groupe trouvé avec l'ID: " . $idGroupe_to_delete . ".";
                    }
                } else {
                    $_SESSION['message'] = "Erreur lors de la suppression du groupe : " . mysqli_stmt_error($stmt_delete);
                }
                mysqli_stmt_close($stmt_delete);
            } else {
                $_SESSION['message'] = "Erreur de préparation (suppression groupe): " . mysqli_error($conn);
            }
        } else {
            $_SESSION['message'] = "ID de groupe invalide pour la suppression.";
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // --- RÉCUPÉRATION DES DONNÉES POUR AFFICHAGE ---
    $etudiants = [];
    $sql_etudiants = "SELECT idEtudiant, nomEtudiant, prenomEtudiant, cinEtudiant FROM Etudiant ORDER BY nomEtudiant, prenomEtudiant";
    $result_etudiants = mysqli_query($conn, $sql_etudiants);
    if ($result_etudiants) {
        while($row = mysqli_fetch_assoc($result_etudiants)) $etudiants[] = $row;
    } else {
        $message .= (empty($message) ? "" : "<br>") . "Erreur récupération étudiants: " . mysqli_error($conn);
    }

    $encadrants = [];
    $sql_encadrants = "SELECT idEncadrant, nomEncadrant, prenomEncadrant FROM Encadrant ORDER BY nomEncadrant, prenomEncadrant";
    $result_encadrants = mysqli_query($conn, $sql_encadrants);
    if ($result_encadrants) {
        while($row = mysqli_fetch_assoc($result_encadrants)) $encadrants[] = $row;
    } else {
        $message .= (empty($message) ? "" : "<br>") . "Erreur récupération encadrants: " . mysqli_error($conn);
    }

    $affectations = [];
    $sql_affectations = "SELECT
                            G.idGroupe, G.numGroupe, G.anneeUniversaire,
                            Enc.idEncadrant, Enc.nomEncadrant, Enc.prenomEncadrant,
                            GROUP_CONCAT(DISTINCT CONCAT(Et.prenomEtudiant, ' ', Et.nomEtudiant) ORDER BY Et.nomEtudiant SEPARATOR '<br>') AS membresGroupe
                        FROM Groupe G
                        JOIN Encadrant Enc ON G.idEncadrant = Enc.idEncadrant
                        LEFT JOIN Etudiant Et ON G.idGroupe = Et.idGroupe
                        GROUP BY G.idGroupe
                        ORDER BY G.anneeUniversaire DESC, G.numGroupe ASC"; // Tri plus logique
    $result_affectations = mysqli_query($conn, $sql_affectations);
    if ($result_affectations) {
        while($row = mysqli_fetch_assoc($result_affectations)) $affectations[] = $row;
    } else {
        $message .= (empty($message) ? "" : "<br>") . "Erreur récupération affectations: " . mysqli_error($conn) . " (Query: " . $sql_affectations . ")";
    }

    
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <title>Gestion des PFE</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   
    <style>
        body{
             background: linear-gradient(#EEE8AA,#F0E68C);
             background-repeat: no-repeat;
             background-attachment: fixed;
        }

 /*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/

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
              /* flex: 1 1 0; Removed as it's now fixed */
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

        .sidebar  > h3 {
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

        .content > h1{
             color: rgb(0, 0, 0);
             font-size: 2.5rem;
             margin-bottom: 1rem;
        }

        .content > p {
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
        
     
            /* Styles for user management content, similar to GererExempleA and GererRessA */
        .user-management-content { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top: 20px; }
        .user-management-content h2, .user-management-content h3 { color: #333; border-bottom: 2px solid  #2e916e; padding-bottom: 10px; margin-top: 20px;}
        .user-management-content table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .user-management-content th, .user-management-content td { border: 1px solid #ddd; padding: 10px; text-align: left; word-break: break-word; }
        .user-management-content th { background-color:  #2e916e; color: white; }
        .user-management-content tr:nth-child(even) { background-color: #f9f9f9; }
        .user-management-content tr:hover { background-color: #f1f1f1; }

        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .form-section { margin-bottom: 30px; padding: 15px; border: 1px solid #eee; border-radius: 5px; background-color: #fdfdfd;} /* Standard background */
        .form-section div { margin-bottom: 10px; }
        .form-section label { display: block; margin-bottom: 5px; font-weight: bold;}
        .form-section input[type="text"], .form-section input[type="password"], .form-section select { width: calc(100% - 22px); padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .form-section select[multiple] { height: 100px; } /* Specific for this page's multi-select */
        .form-section button, .form-section input[type="submit"] { background-color:  #2e916e; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; }
        .form-section button:hover, .form-section input[type="submit"]:hover { background-color:  #257758; } /* Standard hover */

        .delete-button { background-color: #dc3545; color:white; padding: 5px 10px; border:none; border-radius:4px; cursor:pointer; } /* Standard delete button */
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
             <h3>Menu Administrateur</h3>
         
            <nav class="menu">
                <a href="Administrateur.php" class="menu-item">Home</a>
                <a href="GererExempleA.php" class="menu-item">Gérer les exemples PFE</a>
                <a href="GererRessA.php" class="menu-item">Gérer les ressources pédagogiques</a>
                <a href="GererUtilA.php" class="menu-item">Gérer les utilisateurs de l'application</a>
                <a href="AffectationA.php" class="menu-item is-active">Affectation</a>
                <a href="SuiviA.php" class="menu-item" >Suivi</a>
                <a href="deconnexion.php" class="menu-item">Se déconnecter</a> 
            </nav>
        </aside>

        <main class="content">
              <h1>Gestion des Affectations PFE</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($nomAdministrateur); ?>. Vous pouvez affecter des encadrants à des groupes d'étudiants ici.</p>

            <div class="user-management-content"> <!-- Changed class from management-content to user-management-content -->
   

                <?php if (!empty($message)): ?>
                    <p class="message <?php echo (strpos(strtolower($message), 'erreur') === false && strpos(strtolower($message), 'invalide') === false) ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                <?php endif; ?>

                <div class="form-section">
                    <h2>Nouvelle Affectation</h2>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <input type="hidden" name="action" value="assign_group">
                        <div>
                            <label for="idEncadrant">Encadrant :</label>
                            <select id="idEncadrant" name="idEncadrant" required>
                                <option value="">-- Choisir un encadrant --</option>
                                <?php foreach ($encadrants as $enc): ?>
                                    <option value="<?php echo htmlspecialchars($enc['idEncadrant']); ?>">
                                        <?php echo htmlspecialchars($enc['prenomEncadrant'] . ' ' . $enc['nomEncadrant']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="idEtudiants">Étudiants (maintenez CTRL/CMD pour sélectionner plusieurs, max 3) :</label>
                            <select id="idEtudiants" name="idEtudiants[]" multiple required size="5">
                                <?php foreach ($etudiants as $etu): ?>
                                    <option value="<?php echo htmlspecialchars($etu['idEtudiant']); ?>">
                                        <?php echo htmlspecialchars($etu['prenomEtudiant'] . ' ' . $etu['nomEtudiant'] . ' (CIN: ' . $etu['cinEtudiant'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="numGroupe">Nom du Groupe (optionnel) :</label>
                            <input type="text" id="nomGroupe" name="numGroupe" placeholder="Ex: Groupe Alpha PFE">
                        </div>
                        <div>
                            <label for="anneeUniversaire">Année Universitaire :</label>
                            <input type="text" id="anneeUniversaire" name="anneeUniversaire" placeholder="Ex: 2023-2024" value="<?php echo date('Y') . '-' . (date('Y')+1); ?>" required>
                        </div>
                        <button type="submit">Affecter le Groupe</button>
                    </form>
                </div>

                <h2>Affectations Existantes</h2>
                <?php if (empty($affectations)): ?>
                    <p>Aucune affectation trouvée.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID Groupe</th>
                                <th>Nom Groupe</th>
                                <th>Année Univ.</th> <!-- Ajout de l'en-tête manquant -->
                                <th>Encadrant</th>
                                <th>Membres du Groupe</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($affectations as $aff): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($aff['idGroupe']); ?></td>
                                    <td><?php echo htmlspecialchars($aff['numGroupe']); ?></td>
                                    <td><?php echo htmlspecialchars($aff['anneeUniversaire']); ?></td>
                                    <td><?php echo htmlspecialchars($aff['prenomEncadrant'] . ' ' . $aff['nomEncadrant']); ?></td>
                                    <td><?php echo $aff['membresGroupe'] ? $aff['membresGroupe'] : 'Aucun étudiant'; ?></td>
                                    <td>
                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce groupe et ses affectations ?');">
                                            <input type="hidden" name="action" value="delete_group">
                                            <input type="hidden" name="idGroupe" value="<?php echo htmlspecialchars($aff['idGroupe']); ?>">
                                            <button type="submit" class="delete-button">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div> <!-- .management-content -->
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
         mysqli_close($conn); // Fermer la connexion à la base de données
      ?>
</body>
</html>