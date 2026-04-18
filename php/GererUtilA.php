

<?php 
session_start(); 
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" ||!isset($_SESSION["idAdministrateur"])) { 
    // Rediriger vers la page d'accueil si l'utilisateur n'est pas autorisé
    header("location:index.php"); 
    exit(); 
} 

    $nomAdministrateur = $_SESSION["nomAdministrateur"] ?? 'Administrateur'; // Corrected session variable key
    




// Configuration de la base de données (pour la base 'mabase' comme dans le fichier d'origine)
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "gestionPFE"; // Utilisation de 'mabase' comme dans le fichier d'origine

// Connexion à la base de données
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Vérifier la connexion
if (!$conn) {
    die("Erreur de connexion : " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");

// Configuration des types d'utilisateurs et de leurs tables/colonnes correspondantes
$user_type_config = [
    'etudiant' => [
        'table' => 'Etudiant',
        'pk_col' => 'idEtudiant',
        'display_name' => 'Étudiant',
        'form_to_db_cols' => [ // Maps conceptual form fields to DB columns for INSERT
            'nom'    => 'nomEtudiant',
            'prenom' => 'prenomEtudiant',
            'login'  => 'emailEtudiant',     // Form 'login' field maps to 'emailEtudiant' DB column
            'pass'   => 'motpasseEtudiant',  // Hashed password
            'cin'    => 'cinEtudiant'
            
        ],
        'display_cols_map' => [ // Maps generic display column names to actual DB column names for SELECT
            'id'     => 'idEtudiant',
            'nom'    => 'nomEtudiant',
            'prenom' => 'prenomEtudiant',
            'login'  => 'emailEtudiant',     // 'emailEtudiant' DB column will be aliased AS 'login'
            'cin'    => 'cinEtudiant',
        ]
    ],
    'encadrant' => [
        'table' => 'Encadrant', // Singular, as per your schema
        'pk_col' => 'idEncadrant',
        'display_name' => 'Encadrant',
        'form_to_db_cols' => [
            'nom'    => 'nomEncadrant',
            'prenom' => 'prenomEncadrant',
            'login'  => 'emailEncadrant',    // Form 'login' field maps to 'emailEncadrant' DB column
            'pass'   => 'motpasseEncadrant',
            'cin'    => 'cinEncadrant'
        ],
        'display_cols_map' => [
            'id'     => 'idEncadrant',
            'nom'    => 'nomEncadrant',
            'prenom' => 'prenomEncadrant',
            'login'  => 'emailEncadrant',    // 'emailEncadrant' DB column will be aliased AS 'login'
            'cin'    => 'cinEncadrant'
        ]
    ],
    'responsable' => [ // Using 'responsable' as the key for consistency
        'table' => 'ResponsablePFE',
        'pk_col' => 'idResponsable', // As per your schema 'idResponsable'
        'display_name' => 'Responsable PFE',
        'form_to_db_cols' => [
            'nom'    => 'nomResponsable', // Corrected typo from nomResposable
            'prenom' => 'prenomResponsable',
            'login'  => 'email',
             'cin'    => 'cinResponsable',     // Form 'login' field maps to 'cinResponsable' as no specific login/email
            'pass'   => 'motpasseResponsable'   // Form 'cin' field also maps to 'cinResponsable'
        ],
        'display_cols_map' => [ // Maps generic display column names to actual DB column names for SELECT
            'id'     => 'idResponsable',
            'nom'    => 'nomResponsable',
            'prenom' => 'prenomResponsable',
            'login'  => 'email',    // 'cinResponsable' DB column will be aliased AS 'login'
            'cin'    => 'cinResponsable'     // 'cinResponsable' DB column will be aliased AS 'cin'
        ]
    ]
];
$allowed_user_types = array_keys($user_type_config);
$message = ""; // Pour les messages de succès ou d'erreur

// Récupérer le message de la session s'il existe (après une redirection)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Effacer le message pour qu'il ne s'affiche qu'une fois
}
// --- GESTION DES ACTIONS ---

// AJOUTER UN UTILISATEUR
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $pass_plain = $_POST['pass'] ?? '';
    $type = $_POST['type'] ?? ''; // Récupérer le type
    $cin = trim($_POST['cin'] ?? ''); // Ajouter le champ CIN

    // Valider le type
    if (!in_array($type, $allowed_user_types)) {
        $_SESSION['message'] = "Type d'utilisateur invalide.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

     $config = $user_type_config[$type];
    $table_name = $config['table'];

    // Build column list and placeholders for INSERT
    $db_column_names = array_values($config['form_to_db_cols']);
    $db_columns_string = implode(", ", $db_column_names);
    $placeholders_string = implode(", ", array_fill(0, count($db_column_names), '?'));

    $sql_insert = "INSERT INTO {$table_name} ({$db_columns_string}) VALUES ({$placeholders_string})";

 
    if (!empty($nom) && !empty($prenom) && !empty($login) && !empty($pass_plain) && !empty($cin)) {
        // Hacher le mot de passe
        $pass_hashed = password_hash($pass_plain, PASSWORD_DEFAULT);
       
         // Préparer les valeurs sources à partir du formulaire
        $source_values_from_form = [
            'nom' => $nom,     
            'prenom' => $prenom,
            'login' => $login,       // Valeur du champ 'login' (Email) du formulaire
            'pass' => $pass_hashed, // Mot de passe haché
            'cin' => $cin          // Valeur du champ 'cin' du formulaire
        ];

        // Construire le tableau $bind_values_ordered en fonction des clés de form_to_db_cols
        $bind_values_ordered = [];
        foreach (array_keys($config['form_to_db_cols']) as $form_field_key) {
            if (array_key_exists($form_field_key, $source_values_from_form)) {
                $bind_values_ordered[] = $source_values_from_form[$form_field_key];
            } else {
                // Gérer une erreur de configuration si une clé attendue n'est pas dans $source_values_from_form
                // Normalement, cela ne devrait pas arriver avec la configuration actuelle
                $_SESSION['message'] = "Erreur de configuration interne pour le type: " . htmlspecialchars($type);
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        }


        $stmt_insert = mysqli_prepare($conn, $sql_insert);

        if ($stmt_insert) {
                       mysqli_stmt_bind_param($stmt_insert, str_repeat('s', count($db_column_names)), ...$bind_values_ordered);
          
            if (mysqli_stmt_execute($stmt_insert)) {
                            $_SESSION['message'] = "Nouvel utilisateur (" . htmlspecialchars($config['display_name']) . ") ajouté avec succès.";
            } else {
                // Vérifier si c'est une erreur de login ou CIN dupliqué
                if (mysqli_errno($conn) == 1062) { // 1062 est le code d'erreur pour entrée dupliquée
                        $_SESSION['message'] = "Erreur : Le login (ou email/CIN utilisé comme login) ou le CIN existe déjà pour " . htmlspecialchars($config['display_name']) . ".";
                } else {
                    $_SESSION['message'] = "Erreur lors de l'ajout de l'utilisateur : " . mysqli_stmt_error($stmt_insert);
                }
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            $_SESSION['message'] = "Erreur de préparation de la requête d'insertion : " . mysqli_error($conn);
        }
    } else {
        $_SESSION['message'] = "Tous les champs (Nom, Prénom, Login, Mot de passe, CIN, Type) sont requis pour ajouter un utilisateur.";
    }
    header("Location: " . $_SERVER['PHP_SELF']); // Rediriger pour éviter la resoumission du formulaire (PRG pattern)
    exit();
}

// SUPPRIMER UN UTILISATEUR
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_user') {
    $id_to_delete = filter_var($_POST['id_user'] ?? 0, FILTER_VALIDATE_INT);
    $type_to_delete = $_POST['type_user'] ?? ''; // Récupérer le type pour savoir de quelle table supprimer

    // Valider le type
    if (!in_array($type_to_delete, $allowed_user_types)) {
         $_SESSION['message'] = "Type d'utilisateur invalide pour la suppression.";
         header("Location: " . $_SERVER['PHP_SELF']);
         exit();
    }

    // Déterminer la table en fonction du type
     $config = $user_type_config[$type_to_delete];
    $table_name = $config['table'];
    $pk_col_name = $config['pk_col'];


    if ($id_to_delete > 0) {
         $sql_delete = "DELETE FROM {$table_name} WHERE {$pk_col_name} = ?";
        $stmt_delete = mysqli_prepare($conn, $sql_delete);
        if ($stmt_delete) {
            mysqli_stmt_bind_param($stmt_delete, "i", $id_to_delete);
            if (mysqli_stmt_execute($stmt_delete)) {
                if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                     $_SESSION['message'] = htmlspecialchars($config['display_name']) . " (ID: " . $id_to_delete . ") supprimé avec succès.";
                } else {
                      $_SESSION['message'] = "Aucun " . strtolower(htmlspecialchars($config['display_name'])) . " trouvé avec l'ID: " . $id_to_delete . " pour la suppression.";
                }
            } else {
                $_SESSION['message'] = "Erreur lors de la suppression de l'utilisateur : " . mysqli_stmt_error($stmt_delete);
            }
            mysqli_stmt_close($stmt_delete);
        } else {
            $_SESSION['message'] = "Erreur de préparation de la requête de suppression : " . mysqli_error($conn);
        }
    } else {
        $_SESSION['message'] = "ID utilisateur invalide pour la suppression.";
    }
    header("Location: " . $_SERVER['PHP_SELF']); // PRG pattern
    exit();
}

// --- RÉCUPÉRATION DES DONNÉES POUR AFFICHAGE ---
$etudiants = [];
$encadrants = [];
$responsables = [];

foreach ($user_type_config as $type_key => $config) {
    $table = $config['table'];
    $select_parts = [];
    foreach ($config['display_cols_map'] as $alias => $db_col) {
        $select_parts[] = "{$db_col} AS {$alias}";
    }
    $select_string = implode(", ", $select_parts);
    // Use the DB column name for ordering, not the alias, for wider compatibility
    $order_by_nom = $config['display_cols_map']['nom'];
    $order_by_prenom = $config['display_cols_map']['prenom'];

    $sql = "SELECT {$select_string} FROM {$table} ORDER BY {$order_by_nom}, {$order_by_prenom}";

    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['type'] = $type_key; // Use the lowercase config key
            if ($type_key === 'etudiant') $etudiants[] = $row;
            elseif ($type_key === 'encadrant') $encadrants[] = $row;
            elseif ($type_key === 'responsable') $responsables[] = $row;
        }
    } else {
        $message .= (!empty($message) ? "<br>" : "") . "Erreur lors de la récupération des " . htmlspecialchars($config['display_name']) . "s : " . mysqli_error($conn) . " (Query: " . htmlspecialchars($sql) . ")";
    } }

// Fusionner toutes les listes pour l'affichage (facultatif, on peut aussi afficher les tableaux séparément)
// $all_users = array_merge($etudiants, $encadrants, $responsables);
// Optionnel: trier la liste fusionnée si vous voulez un ordre global
// usort($all_users, function($a, $b) { return strcmp($a['nom'], $b['nom']); });

?>


<!DOCTYPE html>
<html lang="fr"> <!-- Changed lang to fr -->
<head>
    <title>Gestion des PFE</title>
     <meta charset="UTF-8"> <!-- Added charset -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Added viewport -->
    <style>
         

          body{
             background: linear-gradient(#EEE8AA,#F0E68C);
             background-repeat: no-repeat;
             background-attachment: fixed;
        }

       * { margin: 0; padding: 0; box-sizing: border-box; font-family: "fire sans", "sans-serif"; }


        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #333; border-bottom: 2px solid  #2e916e; padding-bottom: 10px; margin-top: 20px;}
        h3 { border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 15px;}
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color:  #2e916e; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        form div { margin-bottom: 10px; }
        label { display: block; margin-bottom: 5px; font-weight: bold;}
        input[type="text"], input[type="password"], select { width: calc(100% - 22px); padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        button, input[type="submit"] {
            background-color:  #2e916e; color: white; padding: 10px 15px; border: none;
            border-radius: 4px; cursor: pointer; font-size: 1em;
        }
        button:hover, input[type="submit"]:hover { background-color:  #2e916e; }
        .delete-button { background-color: #dc3545; }
        .delete-button:hover { background-color: #c82333; }
        .form-section { margin-bottom: 30px; padding: 15px; border: 1px solid #eee; border-radius: 5px; background-color:  #EEE8AA;}
   




        
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

           .sidebar > h3 { /* More specific selector for sidebar's h3 */
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

        .content > h1{ /* More specific selector for content's h1 */
             color: rgb(0, 0, 0);
             font-size: 2.5rem;
             margin-bottom: 1rem;
        }

        .content > p { /* More specific selector for content's p */
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
             <h3>Menu Administrateur</h3> <!-- Corrected title -->
            <nav class="menu">
                <a href="Administrateur.php" class="menu-item">Home</a>
                <a href="GererExempleA.php" class="menu-item">Gérer les exemples PFE</a>
                <a href="GererRessA.php" class="menu-item">Gérer les ressources pédagogiques</a>
                <a href="GererUtilA.php" class="menu-item is-active">Gérer les utilisateurs de l'application</a>
                <a href="AffectationA.php" class="menu-item" >Affectation</a>
                <a href="SuiviA.php" class="menu-item" >Suivi</a>
                <a href="deconnexion.php" class="menu-item">Se déconnecter</a> 
            </nav>
        </aside>

        <main class="content">
            <h1>Gestion des Utilisateurs</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($nomAdministrateur); ?>. Vous pouvez gérer les utilisateurs de l'application ici.</p>

            <div class="user-management-content"> 

                <?php if (!empty($message)): ?>
                    <p class="message <?php echo (strpos(strtolower($message), 'erreur') === false && strpos(strtolower($message), 'existe déjà') === false && strpos(strtolower($message), 'invalide') === false && strpos(strtolower($message), 'aucun utilisateur trouvé') === false) ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                <?php endif; ?>

                <div class="form-section">
                    <h2>Ajouter un nouvel utilisateur</h2>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <input type="hidden" name="action" value="add_user">
                        <div>
                            <label for="type">Type :</label>
                            <select id="type" name="type" required>
                                <option value="etudiant" selected>Étudiant</option>
                                <option value="encadrant">Encadrant</option>
                                <option value="responsable">Responsable PFE</option>
                            </select>
                        </div>
                        <div>
                            <label for="nom">Nom :</label>
                            <input type="text" id="nom" name="nom" required>
                        </div>
                        <div>
                            <label for="prenom">Prénom :</label>
                            <input type="text" id="prenom" name="prenom" required>
                        </div>
                         <div>
                            <label for="cin">CIN :</label>
                            <input type="text" id="cin" name="cin" required>
                        </div>
                        <div>
                           <label for="login">Email :</label>
                            <input type="text" id="login" name="login" required>
                        </div>
                        <div>
                            <label for="pass">Mot de passe :</label>
                            <input type="password" id="pass" name="pass" required>
                        </div>
                        <button type="submit">Ajouter l'utilisateur</button>
                    </form>
                </div>

                <h2>Liste des utilisateurs par type</h2>

                <?php
                // Fonction pour afficher un tableau d'utilisateurs
                function display_user_table($users_list, $title_display_name) { // Changed $title to $title_display_name for clarity
                    if (empty($users_list)) {
                        echo "<h3>" . htmlspecialchars($title_display_name) . "</h3>"; // Use display name
                        echo "<p>Aucun " . strtolower(htmlspecialchars(rtrim($title_display_name, 's'))) . " trouvé.</p>"; // Make singular for "aucun"
                        return;
                    }
                ?>
                    <h3><?php echo htmlspecialchars($title_display_name); ?></h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th> <!-- Clarified header -->
                                <th>CIN</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users_list as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($user['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($user['login']); ?></td>
                                    <td><?php echo htmlspecialchars($user['cin']); ?></td>
                                    <td>
                                        <?php
                                            $confirmMessage = sprintf(
                                                'Êtes-vous sûr de vouloir supprimer cet utilisateur ?\nType: %s\nNom: %s (ID: %s)',
                                                htmlspecialchars(ucfirst($user['type'])), // $user['type'] is 'etudiant', 'encadrant', etc.
                                                htmlspecialchars($user['prenom'] . ' ' . $user['nom']),
                                                $user['id']
                                            );
                                        ?>
                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="display:inline;" onsubmit="return confirm(<?php echo json_encode($confirmMessage); ?>);">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="id_user" value="<?php echo htmlspecialchars($user['id']); ?>">
                                            <input type="hidden" name="type_user" value="<?php echo htmlspecialchars($user['type']); ?>">
                                             <button type="submit" class="delete-button">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php
                } // Fin de la fonction display_user_table
                ?>

                <?php
                // Afficher les tableaux séparés
                 display_user_table($etudiants, $user_type_config['etudiant']['display_name'] . "s");
                 display_user_table($encadrants, $user_type_config['encadrant']['display_name'] . "s");
                 display_user_table($responsables, $user_type_config['responsable']['display_name'] . "s"); // Corrected to use plural 's'
                ?>

                <?php if (empty($etudiants) && empty($encadrants) && empty($responsables) && empty($message)): ?>
                    <p>Aucun utilisateur trouvé dans la base de données.</p>
                <?php endif; ?>

            </div> <!-- end of .user-management-content -->
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