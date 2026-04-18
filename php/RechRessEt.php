


<?php
session_start();
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" || !isset($_SESSION["idEtudiant"])) {
    header("location:index.php");
    exit();
}
$nomEtudiant = $_SESSION["prenomNom"] ?? 'Étudiant';

require_once 'Connexion.php'; // Assurez-vous que Connexion.php est accessible et gère $conn

$ressources_results = [];
$search_term_form = '';
$search_domain_form = '';
$message_search = '';
$is_search_action = false; // Flag to indicate if a search was attempted
$domaines = [];

// Fetch domains for the filter dropdown
$sql_domaines = "SELECT idDomaine, nomDomaine FROM Domaine ORDER BY nomDomaine ASC";
$result_domaines_query = mysqli_query($conn, $sql_domaines);
if ($result_domaines_query) {
    while ($row = mysqli_fetch_assoc($result_domaines_query)) {
        $domaines[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search_submit'])) {
      $is_search_action = true;
    $search_term = trim($_GET['term'] ?? '');
    $search_domain_id = filter_var($_GET['domain'] ?? '', FILTER_VALIDATE_INT);

    $search_term_form = $search_term; // For pre-filling the form
    $search_domain_form = $search_domain_id;

   

    if (!empty($search_term) || $search_domain_id) {

 $sql_query_ressources = "SELECT r.titreRessouces, r.descriptionRessource, r.url, r.motsCles, r.dateAjout, d.nomDomaine
                FROM Ressources r
                LEFT JOIN Domaine d ON r.idDomaine = d.idDomaine
                WHERE 1=1";

        $params = [];
        $types = "";

        if (!empty($search_term)) {
            $sql_query_ressources .= " AND (r.titreRessouces LIKE ? OR r.descriptionRessource LIKE ? OR r.motsCles LIKE ?)";
            $like_term = "%" . $search_term . "%";
            array_push($params, $like_term, $like_term, $like_term);
            $types .= "sss";
        }

        if ($search_domain_id) {
            $sql_query_ressources .= " AND r.idDomaine = ?";
            array_push($params, $search_domain_id);
            $types .= "i";
        }

        $sql_query_ressources .= " ORDER BY r.dateAjout DESC";




        $stmt_ressources = mysqli_prepare($conn, $sql_query_ressources);
        if ($stmt_ressources) {
            if (!empty($types)) { // Bind parameters only if there are any
                mysqli_stmt_bind_param($stmt_ressources, $types, ...$params);
            }
            mysqli_stmt_execute($stmt_ressources);
            $result_ressources = mysqli_stmt_get_result($stmt_ressources);
            while ($row = mysqli_fetch_assoc($result_ressources)) {
                $ressources_results[] = $row;
            }
            mysqli_stmt_close($stmt_ressources);
            if (empty($ressources_results)) {
                $message_search = "Aucune ressource pédagogique trouvée pour les critères spécifiés.";
            }
        } else {
            $message_search = "Erreur lors de la préparation de la recherche : " . mysqli_error($conn);
        }
    } else {
        $message_search = "Veuillez entrer un terme de recherche ou sélectionner un domaine pour lancer la recherche.";
 
        // $ressources_results reste vide car aucun critère de recherche n'a été fourni
    }
} else { // Chargement initial de la page (pas de soumission de recherche)
    $sql_all_ressources = "SELECT r.titreRessouces, r.descriptionRessource, r.url, r.motsCles, r.dateAjout, d.nomDomaine
                           FROM Ressources r
                           LEFT JOIN Domaine d ON r.idDomaine = d.idDomaine
                           ORDER BY r.dateAjout DESC";
    $result_all_ressources_query = mysqli_query($conn, $sql_all_ressources);
    if ($result_all_ressources_query) {
        while ($row = mysqli_fetch_assoc($result_all_ressources_query)) {
            $ressources_results[] = $row;
        }
        if (empty($ressources_results)) {
            $message_search = "Aucune ressource pédagogique n'est disponible pour le moment.";
        }
           } else {
        $message_search = "Erreur lors de la récupération des ressources : " . mysqli_error($conn);
 
 
     }
}
?>



<!DOCTYPE html>
<html lang="fr">
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
             margin-left : 300px 
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


 /* Styles for search form and results */
        .search-content { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top: 20px; }
        .search-content h2 { color: #333; border-bottom: 2px solid #2e916e; padding-bottom: 10px; margin-top: 20px; text-align : center ;}
        .search-content table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .search-content th, .search-content td { border: 1px solid #ddd; padding: 10px; text-align: left; word-break: break-word; }
        .search-content th { background-color: #2e916e; color: white; }
        .search-content tr:nth-child(even) { background-color: #f9f9f9; }
        .search-content tr:hover { background-color: #f1f1f1; }
        .search-content .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .search-content .message.info { background-color: #e7f3fe; color: #31708f; border: 1px solid #bce8f1; }
        .search-content .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .form-section { margin-bottom: 30px; padding: 15px; border: 1px solid #eee; border-radius: 5px; background-color: #fdfdfd;}
        .form-section div { margin-bottom: 10px; }
        .form-section label { display: block; margin-bottom: 5px; font-weight: bold;}
        .form-section input[type="text"], .form-section select {
            width: calc(100% - 22px); padding: 10px; border: 1px solid #ccc; border-radius: 4px;
            box-sizing: border-box; /* Correction pour que padding n'augmente pas la largeur totale */
        }
        .form-section button {
            background-color: #2e916e; color: white; padding: 10px 15px; border: none;
            border-radius: 4px; cursor: pointer; font-size: 1em; 
             display: block;
                 margin-left: auto;
                 margin-right: auto;
                 width: 200px;
        }
        .form-section button:hover { background-color: #257758; }

        
        
  
        /* Styles for inline form fields */
        .form-row {
            display: flex;
            flex-wrap: wrap; /* Allows items to wrap on smaller screens if necessary */
            gap: 20px; /* Space between items */
            align-items: flex-end; /* Aligns items to the bottom, useful if labels make heights different */
        }
        .form-row > div {
            flex: 1; /* Allows fields to grow and share space */
            min-width: 250px; /* Minimum width before wrapping */
            
        }
        .form-row label { margin-bottom: 5px; margin-top: 20px;}
        .form-row input[type="text"], .form-row select { width: 100%; }/* Take full width of their flex container */ }
         
         


        .centre-bloc {
                margin-left: auto;
                margin-right: auto;
                width: 50%; /* ou une largeur spécifique */
              }

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
                <a href="RechRessEt.php" class="menu-item is-active">Rechercher des ressources pédagogiques</a>
                <a href="ChoisirSujetEt.php" class="menu-item">Choisir un sujet parmi ceux proposés</a>
                <a href="DeposerRapEt.php" class="menu-item" >Déposer votre PFE</a>
                <a href="deconnexion.php" class="menu-item">Se déconnecter</a>
            </nav>
        </aside>

        <main class="content">
            <h1>Rechercher des Ressources Pédagogiques</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($nomEtudiant); ?>. Utilisez le formulaire ci-dessous pour trouver des ressources.</p>

            <div class="search-content">
                <div class="form-section">
                    <div class ="centre-bloc ">
                    <h2> Recherche</h2></div>
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="form-row">
                            <div>
                                <label for="term">Mots-clés :</label>
                                <input type="text" id="term" name="term" placeholder="Titre, description, mots-clés..." value="<?php echo htmlspecialchars($search_term_form); ?>">
                            </div>
                            <div>
                                <label for="domain">Domaine :</label>
                                <select id="domain" name="domain">
                                    <option value="">-- Tous les domaines --</option>
                                    <?php foreach ($domaines as $domaine): ?>
                                        <option value="<?php echo htmlspecialchars($domaine['idDomaine']); ?>" <?php echo ($search_domain_form == $domaine['idDomaine']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($domaine['nomDomaine']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button  class ="bouton" type="submit" name="search_submit">Rechercher</button>
                    </form>
                </div>
            </div>
            <div class="search-content">
                <?php if (!empty($message_search)): ?>
                    <p class="message info"><?php echo htmlspecialchars($message_search); ?></p>
                <?php endif; ?>

                <?php if (!empty($ressources_results)): ?>
                    <?php
                    $table_title = "";
                    if ($is_search_action && (!empty($search_term_form) || !empty($search_domain_form))) {
                        $table_title = "Résultats de la recherche";
                    } elseif (!$is_search_action) {
                        $table_title = "Ressources disponibles";
                    }
                    ?>
                    <?php if (!empty($table_title)): ?>
                        <h2><?php echo htmlspecialchars($table_title); ?></h2>
                    <?php endif; ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Description</th>
                                <th>URL</th>
                                
                                <th>Domaine</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ressources_results as $res): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($res['titreRessouces']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($res['descriptionRessource'])); ?></td>
                                     <td>
                                        <a href="<?php echo htmlspecialchars($res['url']); ?>" target="_blank" title="<?php echo htmlspecialchars($res['url']); ?>">
                                            <?php echo htmlspecialchars(mb_strimwidth($res['url'], 0, 50, "...")); ?>
                                        </a>
                                    </td>
                                    
                                    <td><?php echo htmlspecialchars($res['nomDomaine'] ?? 'N/A'); ?></td>
                                    
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
            </div>   </main>
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