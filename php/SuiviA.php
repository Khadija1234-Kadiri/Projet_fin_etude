

<?php 
session_start(); 
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" ||!isset($_SESSION["idAdministrateur"])) { 
    // Rediriger vers la page d'accueil si l'utilisateur n'est pas autorisé
    header("location:index.php"); 
    exit(); 
} 

       $nomAdministrateur = $_SESSION["nomAdministrateur"] ?? 'Administrateur'; // Corrected session variable

    require_once 'Connexion.php'; // Database connection

    $sujets_recemment_proposes = [];
    $sujets_recemment_choisis = [];
    $rapports_recemment_deposes = [];
    $feedbacks_recents = [];

    // 1. Sujets récemment proposés
    $sql_sujets_proposes = "SELECT s.titreSujetPFE, s.descriptions, s.dateProposition, d.nomDomaine, 
                                  CONCAT(e.prenomEncadrant, ' ', e.nomEncadrant) AS nomEncadrantProposant
                           FROM SujetPFE s
                           JOIN Domaine d ON s.idDomaine = d.idDomaine
                           JOIN Encadrant e ON s.idEncadrant = e.idEncadrant
                           ORDER BY s.dateProposition DESC
                           LIMIT 5"; // Show recent 5
    $result_sp = mysqli_query($conn, $sql_sujets_proposes);
    if ($result_sp) {
        while ($row = mysqli_fetch_assoc($result_sp)) {
            $sujets_recemment_proposes[] = $row;
        }
    }

    // 2. Sujets récemment choisis
    // Assuming Choisir table has a dateChoix column. If not, this query might need adjustment.
    // For this example, let's assume dateChoix exists or we use SujetPFE.dateProposition as a proxy if estChoisi = 1
    $sql_sujets_choisis = "SELECT s.titreSujetPFE, g.numGroupe AS nomGroupe, 
                                 CONCAT(e.prenomEncadrant, ' ', e.nomEncadrant) AS nomEncadrantDuGroupe
                          FROM SujetPFE s
                          JOIN Choisir ch ON s.idSujetPFE = ch.idSujetPFE
                          JOIN Groupe g ON ch.idGroupe = g.idGroupe
                          JOIN Encadrant e ON s.idEncadrant = e.idEncadrant
                          WHERE s.estChoisi = 1
                          ORDER BY  s.dateProposition DESC
                          LIMIT 5";
    $result_sc = mysqli_query($conn, $sql_sujets_choisis);
    if ($result_sc) {
        while ($row = mysqli_fetch_assoc($result_sc)) {
            $sujets_recemment_choisis[] = $row;
        }
    }

    // 3. Rapports récemment déposés
    $sql_rapports_deposes = "SELECT r.titreRapport, r.dateDepot, r.nom_fichier, r.fichier_pdf, g.numGroupe AS nomGroupe, 
                                   CONCAT(e.prenomEncadrant, ' ', e.nomEncadrant) AS nomEncadrant
                            FROM Rapport r
                            JOIN Deposer d ON r.idRapport = d.idRapport
                            JOIN Groupe g ON d.idGroupe = g.idGroupe
                            JOIN Encadrant e ON g.idEncadrant = e.idEncadrant
                            ORDER BY r.dateDepot DESC
                            LIMIT 5";
    $result_rd = mysqli_query($conn, $sql_rapports_deposes);
    if ($result_rd) {
        while ($row = mysqli_fetch_assoc($result_rd)) {
            $rapports_recemment_deposes[] = $row;
        }
    }

    // 4. Feedbacks récents sur les rapports
    $sql_feedbacks = "SELECT r.titreRapport, g.numGroupe AS nomGroupe, r.statutValidation, 
                            f.contenu AS feedbackContenu, f.dateFeedback, 
                            CONCAT(enc_feed.prenomEncadrant, ' ', enc_feed.nomEncadrant) AS nomEncadrantFeedback
                     FROM Feedback f
                     JOIN Deposer d ON f.idFeedback = d.idFeedback
                     JOIN Rapport r ON d.idRapport = r.idRapport
                     JOIN Groupe g ON d.idGroupe = g.idGroupe
                     JOIN Encadrant enc_feed ON f.idEncadrant = enc_feed.idEncadrant
                     ORDER BY f.dateFeedback DESC
                     LIMIT 5";
    $result_f = mysqli_query($conn, $sql_feedbacks);
    if ($result_f) {
        while ($row = mysqli_fetch_assoc($result_f)) {
            $feedbacks_recents[] = $row;
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
             padding: 2rem;
             margin-left: 300px ;
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


         

        /* Styles for information display (similar to Encadrant.php) */
        .info-section { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top: 20px; margin-bottom: 30px; }
        .info-section h2 { color: #333; border-bottom: 2px solid #2e916e; padding-bottom: 10px; margin-top: 0; margin-bottom: 15px; font-size: 1.6em; }
        .info-section table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9em; }
        .info-section th, .info-section td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; word-break: break-word; }
        .info-section th { background-color: #2e916e; color: white; }
        .info-section tr:nth-child(even) { background-color: #f9f9f9; }
        .info-section tr:hover { background-color: #f1f1f1; }
        .info-section p.no-data { color: #777; font-style: italic; }
    
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
                <a href="Administrateur.php" class="menu-item">Home</a>
                <a href="GererExempleA.php" class="menu-item">Gérer les exemples PFE</a>
                <a href="GererRessA.php" class="menu-item">Gérer les ressources pédagogiques</a>
                <a href="GererUtilA.php" class="menu-item">Gérer les utilisateurs de l'application</a>
                <a href="AffectationA.php" class="menu-item" >Affectation</a>
                <a href="SuiviA.php" class="menu-item is-active">Suivi</a>
                <a href="deconnexion.php" class="menu-item">Se déconnecter</a> 
            </nav>
        </aside>

        <main class="content">
                       <h1>Suivi des Activités PFE</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($nomAdministrateur); ?>. Voici les dernières activités concernant les PFE.</p>

            <div class="info-section">
                <h2>Sujets PFE Récemment Proposés</h2>
                <?php if (empty($sujets_recemment_proposes)): ?>
                    <p class="no-data">Aucun sujet n'a été proposé récemment.</p>
                <?php else: ?>
                    <table>
                        <thead><tr><th>Titre</th><th>Domaine</th><th>Proposé par</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($sujets_recemment_proposes as $sujet): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(mb_strimwidth($sujet['titreSujetPFE'], 0, 100, "...")); ?></td>
                                    <td><?php echo htmlspecialchars($sujet['nomDomaine']); ?></td>
                                    <td><?php echo htmlspecialchars($sujet['nomEncadrantProposant']); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($sujet['dateProposition']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="info-section">
                <h2>Sujets PFE Récemment Choisis</h2>
                <?php if (empty($sujets_recemment_choisis)): ?>
                    <p class="no-data">Aucun sujet n'a été choisi récemment.</p>
                <?php else: ?>
                    <table>
                        <thead><tr><th>Titre du Sujet</th><th>Groupe</th><th>Encadrant</th></tr></thead>
                        <tbody>
                            <?php foreach ($sujets_recemment_choisis as $sujet): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(mb_strimwidth($sujet['titreSujetPFE'], 0, 100, "...")); ?></td>
                                    <td><?php echo htmlspecialchars($sujet['nomGroupe']); ?></td>
                                    <td><?php echo htmlspecialchars($sujet['nomEncadrantDuGroupe']); ?></td>
                                      </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="info-section">
                <h2>Rapports PFE Récemment Déposés</h2>
                <?php if (empty($rapports_recemment_deposes)): ?>
                    <p class="no-data">Aucun rapport n'a été déposé récemment.</p>
                <?php else: ?>
                    <table>
                        <thead><tr><th>Titre Rapport</th><th>Groupe</th><th>Encadrant</th><th>Fichier</th><th>Date Dépôt</th></tr></thead>
                        <tbody>
                            <?php foreach ($rapports_recemment_deposes as $rapport): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(mb_strimwidth($rapport['titreRapport'],0,100,"...")); ?></td>
                                    <td><?php echo htmlspecialchars($rapport['nomGroupe']); ?></td>
                                    <td><?php echo htmlspecialchars($rapport['nomEncadrant']); ?></td>
                                    <td><a href="<?php echo htmlspecialchars($rapport['fichier_pdf']); ?>" target="_blank" title="Voir <?php echo htmlspecialchars($rapport['nom_fichier']); ?>"><?php echo htmlspecialchars(mb_strimwidth($rapport['nom_fichier'], 0, 20, "...")); ?></a></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($rapport['dateDepot']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="info-section">
                <h2>Feedbacks Récents sur les Rapports</h2>
                <?php if (empty($feedbacks_recents)): ?>
                    <p class="no-data">Aucun feedback n'a été donné récemment.</p>
                <?php else: ?>
                    <table>
                        <thead><tr><th>Titre Rapport</th><th>Groupe</th><th>Statut</th><th>Feedback par</th><th>Date Feedback</th></tr></thead>
                        <tbody>
                            <?php foreach ($feedbacks_recents as $fb): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(mb_strimwidth($fb['titreRapport'], 0, 100, "...")); ?></td>
                                    <td><?php echo htmlspecialchars($fb['nomGroupe']); ?></td>
                                    <td><?php echo htmlspecialchars($fb['statutValidation']); ?></td>
                                    <td><?php echo htmlspecialchars($fb['nomEncadrantFeedback']); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($fb['dateFeedback']))); ?></td>
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

<?php
mysqli_close($conn);
?>