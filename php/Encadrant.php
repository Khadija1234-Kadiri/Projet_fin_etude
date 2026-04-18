<?php
session_start();
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" || !isset($_SESSION["idEncadrant"])) {
    header("location:AuthentificationEncadrant.php");
    exit();
}

$nomEncadrant = $_SESSION["prenomNom"] ?? 'Encadrant';
$idEncadrant = $_SESSION["idEncadrant"];

require_once 'Connexion.php'; // Database connection

$groupes_ayant_choisi_sujet = [];
$groupes_ayant_depose_rapport = [];

// Fetch groups that have chosen a subject
$sql_choisi_sujet = "SELECT
                        g.idGroupe,
                        g.numGroupe AS nomGroupe,
                        s.titreSujetPFE,
                        s.descriptions AS descriptionSujet,
                        GROUP_CONCAT(DISTINCT CONCAT(e.prenomEtudiant, ' ', e.nomEtudiant) ORDER BY e.nomEtudiant SEPARATOR ', ') AS membresGroupe
                    FROM Groupe g
                    JOIN Choisir c ON g.idGroupe = c.idGroupe
                    JOIN SujetPFE s ON c.idSujetPFE = s.idSujetPFE
                    LEFT JOIN Etudiant e ON g.idGroupe = e.idGroupe
                    WHERE g.idEncadrant = ?
                    GROUP BY g.idGroupe, s.idSujetPFE
                    ORDER BY g.numGroupe";
$stmt_choisi = mysqli_prepare($conn, $sql_choisi_sujet);
if ($stmt_choisi) {
    mysqli_stmt_bind_param($stmt_choisi, "i", $idEncadrant);
    mysqli_stmt_execute($stmt_choisi);
    $result_choisi = mysqli_stmt_get_result($stmt_choisi);
    while ($row = mysqli_fetch_assoc($result_choisi)) {
        $groupes_ayant_choisi_sujet[] = $row;
    }
    mysqli_stmt_close($stmt_choisi);
}

// Fetch groups that have deposited a report
$sql_depose_rapport = "SELECT
                            g.idGroupe,
                            g.numGroupe AS nomGroupe,
                            r.titreRapport,
                            r.nom_fichier AS nomFichierRapport,
                            r.dateDepot,
                            r.statutValidation,
                            r.fichier_pdf AS cheminFichierRapport,
                            GROUP_CONCAT(DISTINCT CONCAT(e.prenomEtudiant, ' ', e.nomEtudiant) ORDER BY e.nomEtudiant SEPARATOR ', ') AS membresGroupe
                        FROM Groupe g
                        JOIN Deposer d ON g.idGroupe = d.idGroupe
                        JOIN Rapport r ON d.idRapport = r.idRapport
                        LEFT JOIN Etudiant e ON g.idGroupe = e.idGroupe
                        WHERE g.idEncadrant = ?
                        GROUP BY g.idGroupe, r.idRapport
                        ORDER BY r.dateDepot DESC, g.numGroupe";
$stmt_depose = mysqli_prepare($conn, $sql_depose_rapport);
if ($stmt_depose) {
    mysqli_stmt_bind_param($stmt_depose, "i", $idEncadrant);
    mysqli_stmt_execute($stmt_depose);
    $result_depose = mysqli_stmt_get_result($stmt_depose);
    while ($row = mysqli_fetch_assoc($result_depose)) {
        $groupes_ayant_depose_rapport[] = $row;
    }
    mysqli_stmt_close($stmt_depose);
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
             width: 300px;
             height: 100vh; /* Full viewport height */
             z-index: 1000; /* Ensure sidebar is on top */
             overflow-y: auto;

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

        /* Styles for information display */
        .info-section { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top: 20px; margin-bottom: 30px; }
        .info-section h2 { color: #333; border-bottom: 2px solid #2e916e; padding-bottom: 10px; margin-top: 0; margin-bottom: 15px; font-size: 1.8em; }
        .info-section table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .info-section th, .info-section td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: top; word-break: break-word; }
        .info-section th { background-color: #2e916e; color: white; }
        .info-section tr:nth-child(even) { background-color: #f9f9f9; }
        .info-section tr:hover { background-color: #f1f1f1; }
        .info-section p.no-data { color: #777; font-style: italic; }

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
                <a href="Encadrant.php" class="menu-item is-active">Home</a>
                <a href="ProposerSujetEnc.php" class="menu-item">Proposer les sujets PFE</a>
                <a href="valid&FeedEnc.php" class="menu-item">validation et Feesback</a>
                <a href="deconnexion.php" class="menu-item">Se déconnecter</a>
            </nav>
        </aside>

        <main class="content">
            
            <p>Bienvenue, <?php echo htmlspecialchars($nomEncadrant); ?>. Voici un aperçu de l'activité de vos groupes.</p>

            

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
                <h2>Groupes Ayant Choisi un Sujet PFE</h2>
                <?php if (empty($groupes_ayant_choisi_sujet)): ?>
                    <p class="no-data">Aucun de vos groupes n'a encore choisi de sujet PFE.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom du Groupe</th>
                                <th>Membres du Groupe</th>
                                <th>Titre du Sujet</th>
                                <th>Description du Sujet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groupes_ayant_choisi_sujet as $groupe_sujet): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($groupe_sujet['nomGroupe']); ?></td>
                                    <td><?php echo htmlspecialchars($groupe_sujet['membresGroupe'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($groupe_sujet['titreSujetPFE']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars(mb_strimwidth($groupe_sujet['descriptionSujet'], 0, 150, "..."))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="info-section">
                <h2>Groupes Ayant Déposé un Rapport PFE</h2>
                <?php if (empty($groupes_ayant_depose_rapport)): ?>
                    <p class="no-data">Aucun de vos groupes n'a encore déposé de rapport PFE.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom du Groupe</th>
                                <th>Membres du Groupe</th>
                                <th>Titre du Rapport</th>
                                <th>Fichier</th>
                                <th>Date Dépôt</th>
                                <th>Statut Validation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groupes_ayant_depose_rapport as $groupe_rapport): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($groupe_rapport['nomGroupe']); ?></td>
                                    <td><?php echo htmlspecialchars($groupe_rapport['membresGroupe'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($groupe_rapport['titreRapport']); ?></td>
                                    <td><a href="<?php echo htmlspecialchars($groupe_rapport['cheminFichierRapport']); ?>" target="_blank" title="Voir <?php echo htmlspecialchars($groupe_rapport['nomFichierRapport']); ?>"><?php echo htmlspecialchars(mb_strimwidth($groupe_rapport['nomFichierRapport'], 0, 25, "...")); ?></a></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($groupe_rapport['dateDepot']))); ?></td>
                                    <td><?php echo htmlspecialchars($groupe_rapport['statutValidation']); ?></td>
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