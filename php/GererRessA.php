

<?php 
session_start(); 
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" ||!isset($_SESSION["idAdministrateur"])) { 
    // Rediriger vers la page d'accueil si l'utilisateur n'est pas autorisé
    header("location:index.php"); 
    exit(); 
} 

   $nomAdministrateur = $_SESSION["nomAdministrateur"] ?? 'Administrateur';
    $idAdministrateur = $_SESSION["idAdministrateur"]; // Nécessaire pour l'ajout

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

    // AJOUTER UNE RESSOURCE
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_ressource') {
        $titre = trim($_POST['titreRessources'] ?? '');
        $description = trim($_POST['descriptionRessource'] ?? '');
        
        
         $url = trim($_POST['url'] ?? '');
        $mots_cles = trim($_POST['motsCles'] ?? '');
        $id_domaine_post = $_POST['idDomaine'] ?? '';
        $id_domaine = null;

        if ($id_domaine_post !== '') {
            $id_domaine = filter_var($id_domaine_post, FILTER_VALIDATE_INT);
            if ($id_domaine === false) {
                 $_SESSION['message'] = "Erreur : Le domaine sélectionné est invalide.";
                 header("Location: " . $_SERVER['PHP_SELF']);
                 exit();
            }
        } elseif ($id_domaine_post === '') { // Domaine is required
            $_SESSION['message'] = "Erreur : Le domaine est requis.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }


        if (!empty($titre) && !empty($url)) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $contenu_final_url = $url;

                $sql_insert = "INSERT INTO Ressources (titreRessouces, descriptionRessource, url , motsCles, idDomaine, idAdministrateur) VALUES (?, ?, ?, ?, ?, ?)";
        
        
        
        $stmt_insert = mysqli_prepare($conn, $sql_insert);
                if ($stmt_insert) {
                       mysqli_stmt_bind_param($stmt_insert, "ssssii", $titre, $description, $contenu_final_url, $mots_cles, $id_domaine, $idAdministrateur);
                    if (mysqli_stmt_execute($stmt_insert)) {
                        $_SESSION['message'] = "Ressource ajoutée avec succès.";
                    } else {
                        $_SESSION['message'] = "Erreur lors de l'ajout de la ressource : " . mysqli_stmt_error($stmt_insert);
                    }
                    mysqli_stmt_close($stmt_insert);
                } else {
                    $_SESSION['message'] = "Erreur de préparation de la requête d'insertion : " . mysqli_error($conn);
                }
            } else {
                $_SESSION['message'] = "Erreur : L'URL fournie n'est pas valide.";}
 
        } else {
               $_SESSION['message'] = "Le titre et l'URL sont requis.";
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // SUPPRIMER UNE RESSOURCE
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_ressource') {
        $id_to_delete = filter_var($_POST['idRessource'] ?? 0, FILTER_VALIDATE_INT);

        if ($id_to_delete > 0) {
           
            $sql_delete = "DELETE FROM Ressources WHERE idRessources = ?";
            $stmt_delete = mysqli_prepare($conn, $sql_delete);
            if ($stmt_delete) {
                mysqli_stmt_bind_param($stmt_delete, "i", $id_to_delete);
                if (mysqli_stmt_execute($stmt_delete)) {
                    if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                        
                        $_SESSION['message'] = "Ressource (ID: " . $id_to_delete . ") supprimée avec succès.";
                    } else {
                        $_SESSION['message'] = "Aucune ressource trouvée avec l'ID: " . $id_to_delete . ".";
                    }
                } else {
                    $_SESSION['message'] = "Erreur lors de la suppression : " . mysqli_stmt_error($stmt_delete);
                }
                mysqli_stmt_close($stmt_delete);
            } else {
                $_SESSION['message'] = "Erreur de préparation de la requête de suppression : " . mysqli_error($conn);
            }
        } else {
            $_SESSION['message'] = "ID de ressource invalide pour la suppression.";
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

         // RÉCUPÉRER LES DOMAINES POUR LE FORMULAIRE
    $domaines = [];
    $sql_select_domaines = "SELECT idDomaine, nomDomaine FROM Domaine ORDER BY nomDomaine ASC";
    $result_domaines = mysqli_query($conn, $sql_select_domaines);
    if ($result_domaines) {
        while ($row_domaine = mysqli_fetch_assoc($result_domaines)) {
            $domaines[] = $row_domaine;
        }
    } else {
        $message .= (empty($message) ? "" : "<br>") . "Erreur lors de la récupération des domaines : " . mysqli_error($conn);
    }

    // RÉCUPÉRER LES RESSOURCES POUR AFFICHAGE
    $ressources = [];
    $sql_select_all = "SELECT rp.idRessources, rp.titreRessouces, rp.descriptionRessource, rp.url, rp.motsCles, rp.dateAjout, d.nomDomaine 
                       FROM Ressources rp
                       LEFT JOIN Domaine d ON rp.idDomaine = d.idDomaine
                       ORDER BY rp.dateAjout DESC";
    $result_select_all = mysqli_query($conn, $sql_select_all);
    if ($result_select_all) {
        while ($row = mysqli_fetch_assoc($result_select_all)) {
            $ressources[] = $row;
        }
    } else {
        $message .= (empty($message) ? "" : "<br>") . "Erreur lors de la récupération des ressources : " . mysqli_error($conn);
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

        .sidebar > h3 {
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
             margin-left :300px ;
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


        /* Styles pour le formulaire et le tableau */
        .resource-management-content { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top: 20px; }
        .resource-management-content h2, .resource-management-content h3 { color: #333; border-bottom: 2px solid  #2e916e; padding-bottom: 10px; margin-top: 20px;}
        .resource-management-content table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .resource-management-content th, .resource-management-content td { border: 1px solid #ddd; padding: 10px; text-align: left; word-break: break-word; }
        .resource-management-content th { background-color:  #2e916e; color: white; }
        .resource-management-content tr:nth-child(even) { background-color: #f9f9f9; }
        .resource-management-content tr:hover { background-color: #f1f1f1; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-section div { margin-bottom: 10px; }
        .form-section label { display: block; margin-bottom: 5px; font-weight: bold;}
        .form-section input[type="text"], .form-section input[type="url"], .form-section textarea, .form-section select, .form-section input[type="file"] { width: calc(100% - 22px); padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .form-section textarea { min-height: 80px; }
        .form-section button, .form-section input[type="submit"] {
            background-color:  #2e916e; color: white; padding: 10px 15px; border: none;
            border-radius: 4px; cursor: pointer; font-size: 1em;
        }
        .form-section button:hover, .form-section input[type="submit"]:hover { background-color:  #257758; }
        .delete-button { background-color: #dc3545; color:white; padding: 5px 10px; border:none; border-radius:4px; cursor:pointer; }
        .delete-button:hover { background-color: #c82333; }
        .form-section { margin-bottom: 30px; padding: 15px; border: 1px solid #eee; border-radius: 5px; background-color: #fdfdfd;}
        .file-input-section, .link-input-section { display: none; }
  

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
                <a href="GererRessA.php" class="menu-item is-active">Gérer les ressources pédagogiques</a>
                <a href="GererUtilA.php" class="menu-item">Gérer les utilisateurs de l'application</a>
                <a href="AffectationA.php" class="menu-item" >Affectation</a>
                <a href="SuiviA.php" class="menu-item" >Suivi</a>
                <a href="deconnexion.php" class="menu-item">Se déconnecter</a> 
            </nav>
        </aside>

        <main class="content">
             <h1>Gestion des Ressources Pédagogiques</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($nomAdministrateur); ?>. Vous pouvez ajouter, voir et supprimer des ressources pédagogiques.</p>

            <div class="resource-management-content">
                <?php if (!empty($message)): ?>
                    <p class="message <?php echo (strpos(strtolower($message), 'erreur') === false && strpos(strtolower($message), 'invalide') === false) ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                <?php endif; ?>

                <div class="form-section">
                    <h2>Ajouter une nouvelle ressource</h2>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">

                        <input type="hidden" name="action" value="add_ressource">
                        <div>
                            <label for="titreRessources">Titre :</label>
                            <input type="text" id="titreRessources" name="titreRessources" required>
                        </div>
                        <div>
                            <label for="descriptionRessource">Description (optionnel) :</label>
                            <textarea id="descriptionRessource" name="descriptionRessource"></textarea>
                        </div>
                        <div>
                            <label for="url">URL de la ressource :</label>
                            <input type="url" id="url" name="url" placeholder="https://example.com/ressource" required>
                        </div>
                        <div>
                            <label for="motsCles">Mots Clés (séparés par une virgule) :</label>
                            <textarea id="motsCles" name="motsCles" placeholder="Ex: php, base de données, IA"></textarea>
                        </div>
                        <div>
                            <label for="idDomaine">Domaine :</label>
                            <select id="idDomaine" name="idDomaine" required>
                                <option value="">-- Choisir un domaine --</option>
                                <?php foreach ($domaines as $domaine): ?>
                                    <option value="<?php echo htmlspecialchars($domaine['idDomaine']); ?>">
                                        <?php echo htmlspecialchars($domaine['nomDomaine']); ?>
                                    </option>
                                <?php endforeach; ?>
 
                            </select>
                        </div>
                       
                        <button type="submit">Ajouter la ressource</button>
                    </form>
                </div>

                <h2>Liste des ressources pédagogiques</h2>
                <?php if (empty($ressources)): ?>
                    <p>Aucune ressource pédagogique trouvée.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Titre/Description</th>
                                <th>URL</th>
                                <th>Mots Clés</th>
                                <th>Date Ajout</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ressources as $res): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($res['idRessources']); ?></td>
                                    <td><?php echo htmlspecialchars($res['titreRessouces']); ?><br><small><?php echo nl2br(htmlspecialchars($res['descriptionRessource'])); ?></small></td>
                                    <td><a href="<?php echo htmlspecialchars($res['url']); ?>" target="_blank" title="<?php echo htmlspecialchars($res['url']); ?>"><?php echo htmlspecialchars(substr($res['url'], 0, 50) . (strlen($res['url']) > 50 ? '...' : '')); ?></a></td>
                                    <td><?php echo nl2br(htmlspecialchars($res['motsCles'])); ?></td>
                                    
                                  
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($res['dateAjout']))); ?></td>
                                    <td>
                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette ressource ?');">
                                            <input type="hidden" name="action" value="delete_ressource">
                                            <input type="hidden" name="idRessource" value="<?php echo htmlspecialchars($res['idRessources']); ?>">
                                            <button type="submit" class="delete-button">Supprimer</button>
                                        </form>
                                    </td>
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
        }) ;
               
    </script>

</body>
</html>