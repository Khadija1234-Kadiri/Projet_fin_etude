
<?php 
session_start(); 
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" ||!isset($_SESSION["idAdministrateur"])) { 
    // Rediriger vers la page d'accueil si l'utilisateur n'est pas autorisé
    header("location:index.php"); 
    exit(); 
} 

   $nomAdministrateur = $_SESSION["nomAdministrateur"] ?? 'Administrateur';

    require_once 'Connexion.php'; // Database connection

    // Initialize statistics variables
    $stats = [
        'total_etudiants' => 0,
        'total_encadrants' => 0,
        'total_responsables' => 0,
        'sujets_proposes' => 0,
        'sujets_choisis' => 0,
        'rapports_deposes' => 0,
        'rapports_valides' => 0,
    ];

    // Fetch statistics
    $queries = [
        'total_etudiants' => "SELECT COUNT(*) as count FROM Etudiant",
        'total_encadrants' => "SELECT COUNT(*) as count FROM Encadrant",
        'total_responsables' => "SELECT COUNT(*) as count FROM ResponsablePFE",
        'sujets_proposes' => "SELECT COUNT(*) as count FROM SujetPFE",
        'sujets_choisis' => "SELECT COUNT(*) as count FROM SujetPFE WHERE estChoisi = 1",
        'rapports_deposes' => "SELECT COUNT(*) as count FROM Rapport",
        'rapports_valides' => "SELECT COUNT(*) as count FROM Rapport WHERE statutValidation = 'Validé'",
    ];

    foreach ($queries as $key => $sql) {
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $stats[$key] = $row['count'];
            mysqli_free_result($result);
        }
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
             padding: 2rem;
             margin-left : 300px;
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

                      /* Styles for statistics display */
        .stats-container { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 30px; }
        .stat-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            flex: 1 1 200px; /* Flex-grow, flex-shrink, flex-basis */
            text-align: center;
        }


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
       



        .stat-card h3 { color: #2e916e; margin-top: 0; font-size: 1.2em; }
        .stat-card p { font-size: 2em; font-weight: bold; color: #333; margin: 10px 0 0 0; }
    



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
                <a href="Administrateur.php" class="menu-item is-active">Home</a>
                <a href="GererExempleA.php" class="menu-item">Gérer les exemples PFE</a>
                <a href="GererRessA.php" class="menu-item">Gérer les ressources pédagogiques</a>
                <a href="GererUtilA.php" class="menu-item">Gérer les utilisateurs de l'application</a>
                <a href="AffectationA.php" class="menu-item" >Affectation</a>
                <a href="SuiviA.php" class="menu-item" >Suivi</a>
                <a href="deconnexion.php" class="menu-item">Se déconnecter</a> 
            </nav>
        </aside>

        <main class="content">
            <h1>Tableau de Bord Administrateur</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($nomAdministrateur); ?>. Voici un aperçu global du système.</p>

            <div class="stats-container">
                <div class="stat-card">
                    <h3>Étudiants </h3>
                    <p><?php echo $stats['total_etudiants']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Encadrants</h3>
                    <p><?php echo $stats['total_encadrants']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Responsables PFE</h3>
                    <p><?php echo $stats['total_responsables']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Sujets PFE Proposés</h3>
                    <p><?php echo $stats['sujets_proposes']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Sujets PFE Choisis</h3>
                    <p><?php echo $stats['sujets_choisis']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Rapports Déposés</h3>
                    <p><?php echo $stats['rapports_deposes']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Rapports Validés</h3>
                    <p><?php echo $stats['rapports_valides']; ?></p>
                </div>
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


<?php
mysqli_close($conn);
?>
