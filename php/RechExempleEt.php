
<?php
session_start();
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" || !isset($_SESSION["idEtudiant"])) {
    header("location:index.php");
    exit();
}
$nomEtudiant = $_SESSION["prenomNom"] ?? 'Étudiant';

require_once 'Connexion.php'; // Assurez-vous que Connexion.php est accessible et gère $conn

$exemples_results = [];
$search_keywords_form = '';
$search_annee_form = '';
$search_filiere_form = '';
$search_domain_form = '';
$message_search = '';
$is_search_action = false;

$domaines = [];
$filieres = [];

// Fetch domaines for filter
$sql_domaines = "SELECT idDomaine, nomDomaine FROM Domaine ORDER BY nomDomaine ASC";
$result_domaines_query = mysqli_query($conn, $sql_domaines);
if ($result_domaines_query) {
    while ($row = mysqli_fetch_assoc($result_domaines_query)) {
        $domaines[] = $row;
    }
}

// Fetch filieres for filter
$sql_filieres = "SELECT idFiliere, nomFiliere FROM Filiere ORDER BY nomFiliere ASC";
$result_filieres_query = mysqli_query($conn, $sql_filieres);
if ($result_filieres_query) {
    while ($row = mysqli_fetch_assoc($result_filieres_query)) {
        $filieres[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search_submit'])) {
    $is_search_action = true;
    $search_keywords = trim($_GET['keywords'] ?? '');
    $search_annee = trim($_GET['annee'] ?? '');
    $search_filiere_id = filter_var($_GET['filiere'] ?? '', FILTER_VALIDATE_INT);
    $search_domain_id = filter_var($_GET['domain'] ?? '', FILTER_VALIDATE_INT);

    $search_keywords_form = $search_keywords;
    $search_annee_form = $search_annee;
    $search_filiere_form = $search_filiere_id;
    $search_domain_form = $search_domain_id;

    if (!empty($search_keywords) || !empty($search_annee) || $search_filiere_id || $search_domain_id) {
        $sql_query_exemples = "SELECT ex.idExemplePFE, ex.titreExemplePFE, ex.annee, ex.nom_fichier, ex.taille, ex.dateAjout, d.nomDomaine, f.nomFiliere
                FROM ExemplePFE ex
                LEFT JOIN Domaine d ON ex.idDomaine = d.idDomaine
                LEFT JOIN Filiere f ON ex.idFiliere = f.idFiliere
                WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($search_keywords)) {
            $sql_query_exemples .= " AND ex.titreExemplePFE LIKE ?";
            $params[] = "%" . $search_keywords . "%";
            $types .= "s";
        }
        if (!empty($search_annee)) {
            $sql_query_exemples .= " AND ex.annee = ?";
            $params[] = $search_annee;
            $types .= "s"; // Année peut être traitée comme une chaîne ici si pas strictement numérique
        }
        if ($search_filiere_id) {
            $sql_query_exemples .= " AND ex.idFiliere = ?";
            $params[] = $search_filiere_id;
            $types .= "i";
        }
        if ($search_domain_id) {
            $sql_query_exemples .= " AND ex.idDomaine = ?";
            $params[] = $search_domain_id;
            $types .= "i";
        }
        $sql_query_exemples .= " ORDER BY ex.dateAjout DESC";

        $stmt_exemples = mysqli_prepare($conn, $sql_query_exemples);
        if ($stmt_exemples) {
            if (!empty($types)) mysqli_stmt_bind_param($stmt_exemples, $types, ...$params);
            mysqli_stmt_execute($stmt_exemples);
            $result_exemples = mysqli_stmt_get_result($stmt_exemples);
            while ($row = mysqli_fetch_assoc($result_exemples)) $exemples_results[] = $row;
            mysqli_stmt_close($stmt_exemples);
            if (empty($exemples_results)) $message_search = "Aucun exemple de PFE trouvé pour les critères spécifiés.";
        } else {
            $message_search = "Erreur lors de la préparation de la recherche : " . mysqli_error($conn);
        }
    } else {
        $message_search = "Veuillez entrer au moins un critère de recherche.";
    }
} else { // Initial page load
    $sql_all_exemples = "SELECT ex.idExemplePFE, ex.titreExemplePFE, ex.annee, ex.nom_fichier, ex.taille, ex.dateAjout, d.nomDomaine, f.nomFiliere
                       FROM ExemplePFE ex
                       LEFT JOIN Domaine d ON ex.idDomaine = d.idDomaine
                       LEFT JOIN Filiere f ON ex.idFiliere = f.idFiliere
                       ORDER BY ex.dateAjout DESC";
    $result_all_exemples_query = mysqli_query($conn, $sql_all_exemples);
    if ($result_all_exemples_query) {
        while ($row = mysqli_fetch_assoc($result_all_exemples_query)) $exemples_results[] = $row;
        if (empty($exemples_results) && !$is_search_action) $message_search = "Aucun exemple de PFE n'est disponible pour le moment.";
    } else {
        $message_search = "Erreur lors de la récupération des exemples PFE : " . mysqli_error($conn);
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
             margin-left : 300px  ;
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


             /* Styles for search form and results (similar to RechRessEt.php) */
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
        .form-section input[type="text"], .form-section input[type="number"], .form-section select {
            width: 100%; /* Adjusted for form-row */
            padding: 10px; border: 1px solid #ccc; border-radius: 4px;
            box-sizing: border-box;
        }
        .form-section button { background-color: #2e916e; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; margin-top: 10px; }
        .form-section button:hover { background-color: #257758; }
        .form-row { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .form-row > div { flex: 1; min-width: 200px; /* Adjust as needed */ }

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
        .form-row input[type="text"], .form-row select { width: 100%; }/* Take full width of their flex container */ 
  
            .centre-bloc {
                margin-left: auto;
                margin-right: auto;
                width: 50%; /* ou une largeur spécifique */
              }
          
             .bouton {
                 display: block;
                 margin-left: auto;
                 margin-right: auto;
                 width: 200px; /* largeur définie */
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
                <a href="RechExempleEt.php" class="menu-item is-active">Rechercher des exemples PFE</a>
                <a href="RechRessEt.php" class="menu-item">Rechercher des ressources pédagogiques</a>
                <a href="ChoisirSujetEt.php" class="menu-item">Choisir un sujet parmi ceux proposés</a>
                <a href="DeposerRapEt.php" class="menu-item" >Déposer votre PFE</a>
                <a href="deconnexion.php" class="menu-item">Se déconnecter</a>
            </nav>
        </aside>

        <main class="content">
             <h1>Rechercher des Exemples de PFE</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($nomEtudiant); ?>. Utilisez le formulaire ci-dessous pour trouver des exemples de PFE.</p>

            <div class="search-content">
                <div class="form-section">
                    <div class = "centre-bloc ">
                    <h2> Recherche</h2></div>
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="form-row">
                            <div>
                                <label for="keywords">Mots-clés (Titre) :</label>
                                <input type="text" id="keywords" name="keywords" placeholder="Ex: Application web, IA..." value="<?php echo htmlspecialchars($search_keywords_form); ?>">
                            </div>
                            <div>
                                <label for="annee">Année :</label>
                                <input type="number" id="annee" name="annee" placeholder="Ex: <?php echo date('Y'); ?>" value="<?php echo htmlspecialchars($search_annee_form); ?>" min="1900" max="<?php echo date('Y')+5; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div>
                                <label for="filiere">Filière :</label>
                                <select id="filiere" name="filiere">
                                    <option value="">-- Toutes les filières --</option>
                                    <?php foreach ($filieres as $fil): ?>
                                        <option value="<?php echo htmlspecialchars($fil['idFiliere']); ?>" <?php echo ($search_filiere_form == $fil['idFiliere']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($fil['nomFiliere']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="domain">Domaine :</label>
                                <select id="domain" name="domain">
                                    <option value="">-- Tous les domaines --</option>
                                    <?php foreach ($domaines as $dom): ?>
                                        <option value="<?php echo htmlspecialchars($dom['idDomaine']); ?>" <?php echo ($search_domain_form == $dom['idDomaine']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dom['nomDomaine']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div> 
                        </div>
                        <button class ="bouton"  type="submit" name="search_submit">Rechercher</button>
                    </form>
                </div>
            
                <?php if (!empty($message_search)): ?>
                    <p class="message info"><?php echo htmlspecialchars($message_search); ?></p>
                <?php endif; ?>
            </div>

            <div class="search-content">
                <?php if (!empty($exemples_results)): ?>
                    <h2><?php echo ($is_search_action && (!empty($search_keywords_form) || !empty($search_annee_form) || !empty($search_filiere_form) || !empty($search_domain_form))) ? "Résultats de la recherche" : "Exemples de PFE disponibles"; ?></h2>
                    <table>
                        <thead><tr><th>Titre</th><th>Année</th><th>Filière</th><th>Domaine</th><th>Fichier</th></tr></thead>
                        <tbody>
                            <?php foreach ($exemples_results as $ex): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ex['titreExemplePFE']); ?></td>
                                    <td><?php echo htmlspecialchars($ex['annee']); ?></td>
                                    <td><?php echo htmlspecialchars($ex['nomFiliere'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($ex['nomDomaine'] ?? 'N/A'); ?></td>
                                    <td><a href="view_Exemple_pdf.php?id=<?php echo htmlspecialchars($ex['idExemplePFE']); ?>" target="_blank" title="Voir <?php echo htmlspecialchars($ex['nom_fichier']); ?>"><?php echo htmlspecialchars(mb_strimwidth($ex['nom_fichier'], 0, 30, "...")); ?> </a></td>
                                   
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

    
    <?php
mysqli_close($conn);
?>
    
    
   
    

</body>
</html>