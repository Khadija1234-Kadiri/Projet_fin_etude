
<?php 
session_start(); 
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" ||!isset($_SESSION["idResponsable"])) { 
    // Rediriger vers la page d'accueil si l'utilisateur n'est pas autorisé
    header("location:index.php"); 
    exit(); 
} 

    $nomResponsable = $_SESSION["prenomNom"] ?? 'Responsable PFE'; // Consistent with RespoPFE.php
    $idResponsable = $_SESSION["idResponsable"]; // Nécessaire pour l'ajout


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

    // AJOUTER UN EXEMPLE PFE
   if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_liste') {
        $titre = trim($_POST['titreListe'] ?? ''); // This was already correct
         $annee = filter_var($_POST['annee'] ?? null, FILTER_VALIDATE_INT);
        $id_filiere_post = $_POST['idFiliere'] ?? '';
        $id_domaine_post = $_POST['idDomaine'] ?? '';
        $id_filiere = $id_domaine = null;

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

         if ($id_filiere_post !== '') {
            $id_filiere = filter_var($id_filiere_post, FILTER_VALIDATE_INT);
            if ($id_filiere === false) {
                 $_SESSION['message'] = "Erreur : La filière sélectionnée est invalide.";
                 header("Location: " . $_SERVER['PHP_SELF']);
                 exit();
            }
        } elseif ($id_filiere_post === '') { // Filiere is required
            $_SESSION['message'] = "Erreur : La filière est requise.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        if (empty($annee) || $annee < 1900 || $annee > date("Y") + 5) {
            $_SESSION['message'] = "Erreur : L'année est invalide.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }


        if (!empty($titre) && $annee && $id_filiere && $id_domaine && isset($_FILES['fichierListe']) && $_FILES['fichierListe']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['fichierListe']['tmp_name'];
            $file_name = $_FILES['fichierListe']['name'];
            $file_size = $_FILES['fichierListe']['size'];
            $file_type = $_FILES['fichierListe']['type'];
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            
             if ($file_extension == 'pdf' && $file_type == 'application/pdf') {
                $pdf_content = file_get_contents($file_tmp_path);
                if ($pdf_content === false) {
                    $_SESSION['message'] = "Erreur lors de la lecture du contenu du fichier PDF.";
                } else {

                           // Vérifier si la connexion est toujours active avant une opération potentiellement lourde
                    if (!mysqli_ping($conn)) {
                        $_SESSION['message'] = "La connexion au serveur MySQL a été perdue. Tentative de reconnexion...";
                        mysqli_close($conn); // Fermer l'ancienne connexion
                        $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name); // Tenter de se reconnecter
                        if (!$conn) {
                            $_SESSION['message'] = "Échec de la reconnexion à la base de données : " . mysqli_connect_error();
                            header("Location: " . $_SERVER['PHP_SELF']);
                            exit();
                        }
                        mysqli_set_charset($conn, "utf8");
                    }

                    $pdf_content_base64 = base64_encode($pdf_content); // Encode to Base64
                    $sql_insert = "INSERT INTO Liste (titreListe, annee, idFiliere, idDomaine, Fichier_pdf, nom_fichier, type_mime, taille, idResponsable) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt_insert = mysqli_prepare($conn, $sql_insert);
                    if ($stmt_insert) {
                          // Lier la chaîne Base64. Le type 's' pour Fichier_pdf (5ème paramètre) est correct.
                        mysqli_stmt_bind_param(
                            $stmt_insert, 
                            "siiisssii", 
                            $titre, $annee, $id_filiere, $id_domaine, 
                            $pdf_content_base64, // Lier directement la chaîne Base64
                            $file_name, $file_type, $file_size, 
                            $idResponsable 
                        );

                       if (mysqli_stmt_execute($stmt_insert)) {
                            $_SESSION['message'] = "Liste PFE ajouté avec succès.";
                        } else {
                            $_SESSION['message'] = "Erreur lors de l'ajout de la Liste PFE : " . mysqli_stmt_error($stmt_insert) . " (Code: " . mysqli_errno($conn) . ")";
                        }
                        mysqli_stmt_close($stmt_insert);
                   

                    } else {
                        $_SESSION['message'] = "Erreur de préparation de la requête d'insertion : " . mysqli_error($conn);
                    }
                
                }
            } else {
                $_SESSION['message'] = "Erreur : Seuls les fichiers PDF sont autorisés.";
            }
        } else {
             $_SESSION['message'] = "Tous les champs (Titre, Année, Filière, Domaine) et le fichier PDF sont requis.";
            if (isset($_FILES['fichierListe']) && $_FILES['fichierListe']['error'] != UPLOAD_ERR_OK) {
                 $_SESSION['message'] .= " Erreur d'upload: " . $_FILES['fichierListe']['error'];
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    
     // SUPPRIMER UNE LISTE
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_liste') {
        $id_to_delete = filter_var($_POST['idListe'] ?? 0, FILTER_VALIDATE_INT);

        if ($id_to_delete > 0) {
           

            $sql_delete = "DELETE FROM Liste WHERE idListe = ?";
            $stmt_delete = mysqli_prepare($conn, $sql_delete);
            if ($stmt_delete) {
                mysqli_stmt_bind_param($stmt_delete, "i", $id_to_delete);
                if (mysqli_stmt_execute($stmt_delete)) {
                    if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                        
                        $_SESSION['message'] = "Liste PFE (ID: " . $id_to_delete . ") supprimé avec succès.";
                    } else {
                        $_SESSION['message'] = "Aucun liste PFE trouvé avec l'ID: " . $id_to_delete . ".";
                    }
                } else {
                    $_SESSION['message'] = "Erreur lors de la suppression : " . mysqli_stmt_error($stmt_delete);
                }
                mysqli_stmt_close($stmt_delete);
            } else {
                $_SESSION['message'] = "Erreur de préparation de la requête de suppression : " . mysqli_error($conn);
            }
        } else {
            $_SESSION['message'] = "ID de liste PFE invalide pour la suppression.";
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


     // RÉCUPÉRER LES FILIÈRES POUR LE FORMULAIRE
    $filieres = [];
    $sql_select_filieres = "SELECT idFiliere, nomFiliere FROM Filiere ORDER BY nomFiliere ASC";
    $result_filieres = mysqli_query($conn, $sql_select_filieres);
    if ($result_filieres) {
        while ($row_filiere = mysqli_fetch_assoc($result_filieres)) {
            $filieres[] = $row_filiere;
        }
    } else {
        $message .= (empty($message) ? "" : "<br>") . "Erreur lors de la récupération des filières : " . mysqli_error($conn);
    }


    // RÉCUPÉRER LES EXEMPLES PFE POUR AFFICHAGE
       $listes_partagees = [];
        $sql_select_all = "SELECT ex.idListe, ex.titreListe, ex.annee, ex.nom_fichier, ex.taille, ex.dateAjout, d.nomDomaine, f.nomFiliere 
                        FROM Liste ex
                       LEFT JOIN Domaine d ON ex.idDomaine = d.idDomaine
                       LEFT JOIN Filiere f ON ex.idFiliere = f.idFiliere
                       ORDER BY ex.dateAjout DESC";
    $result_select_all = mysqli_query($conn, $sql_select_all);
    if ($result_select_all) {
        while ($row = mysqli_fetch_assoc($result_select_all)) {
               $listes_partagees[] = $row;
        }
    } else {
        $message .= (empty($message) ? "" : "<br>") . "Erreur lors de la récupération de liste : " . mysqli_error($conn);
    }
    
?>



<!DOCTYPE html>
<html lang="fr">
<head>
      <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   
     <title>Partager Listes - Gestion PFE</title>
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
             margin-left : 300px ;
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

           /* Styles pour le formulaire et le tableau (adaptés de GererRessA) */
        .management-content { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top: 20px; }
        .management-content h2, .management-content h3 { color: #333; border-bottom: 2px solid  #2e916e; padding-bottom: 10px; margin-top: 20px;}
        .management-content table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .management-content th, .management-content td { border: 1px solid #ddd; padding: 10px; text-align: left; word-break: break-word; }
        .management-content th { background-color:  #2e916e; color: white; }
        .management-content tr:nth-child(even) { background-color: #f9f9f9; }
        .management-content tr:hover { background-color: #f1f1f1; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-section div { margin-bottom: 10px; }
        .form-section label { display: block; margin-bottom: 5px; font-weight: bold;}
        .form-section input[type="text"], .form-section textarea, .form-section select, .form-section input[type="file"] { width: calc(100% - 22px); padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .form-section textarea { min-height: 80px; }
        .form-section button, .form-section input[type="submit"] { background-color:  #2e916e; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; }
        .form-section button:hover, .form-section input[type="submit"]:hover { background-color:  #257758; }
        .delete-button { background-color: #dc3545; color:white; padding: 5px 10px; border:none; border-radius:4px; cursor:pointer; }
        .delete-button:hover { background-color: #c82333; }
        .form-section { margin-bottom: 30px; padding: 15px; border: 1px solid #eee; border-radius: 5px; background-color: #fdfdfd;}
 

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
               <h3>Menu Responsable PFE</h3>
            <nav class="menu">
                  <a href="RespoPFE.php" class="menu-item">Home</a>
                <a href="PartagerListRes.php" class="menu-item is-active">Partager la liste Encadrant / Groupe d'étudiant</a>
                 <a href="deconnexion.php" class="menu-item">Se déconnecter</a>
            </nav>
        </aside>

        <main class="content">
             <h1>Partager les Listes Encadrant / Groupe</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($nomResponsable); ?>. Vous pouvez partager, voir et supprimer des listes (fichiers PDF).</p>

            <div class="management-content">
                <?php if (!empty($message)): ?>
                    <p class="message <?php echo (strpos(strtolower($message), 'erreur') === false && strpos(strtolower($message), 'invalide') === false) ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                <?php endif; ?>

                <div class="form-section">
                   <h2>Partager une Nouvelle Liste</h2>
                  
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                         <input type="hidden" name="action" value="add_liste">
                     
                        <div>
                            <label for="titreListe">Titre :</label>
                            <input type="text" id="titreListe" name="titreListe" required>
                        </div>
                        <div>
                           
                             <label for="annee">Année :</label>
                            <input type="number" id="annee" name="annee" min="1900" max="<?php echo date("Y") + 5; ?>" placeholder="Ex: <?php echo date("Y"); ?>" required>
                        </div>
                        <div>
                            <label for="idFiliere">Filière :</label>
                            <select id="idFiliere" name="idFiliere" required>
                                <option value="">-- Choisir une filière --</option>
                                <?php foreach ($filieres as $filiere): ?>
                                    <option value="<?php echo htmlspecialchars($filiere['idFiliere']); ?>">
                                        <?php echo htmlspecialchars($filiere['nomFiliere']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                         <label for="fichierListe">Fichier PDF :</label>
                            <input type="file" id="fichierListe" name="fichierListe" accept=".pdf" required>
                    
                        
                        <button type="submit">Ajouter la liste </button>
                    </form>
                </div>

               <h2>Listes Partagées</h2>
                <?php if (empty($listes_partagees)): ?>
                    <p>Aucune liste trouvée.</p>
       
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Titre</th>
                                <th>Année</th>
                                <th>Filière</th>
                                <th>Domaine</th>
                                <th>Fichier</th>
                                <th>Date Ajout</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                             <?php foreach ($listes_partagees as $liste_item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($liste_item['idListe']); ?></td>
                                    <td><?php echo htmlspecialchars($liste_item['titreListe']); ?></td>
                                    <td><?php echo htmlspecialchars($liste_item['annee']); ?></td>
                                    <td><?php echo htmlspecialchars($liste_item['nomFiliere'] ?? ''); ?></td>
                                   <td><?php echo htmlspecialchars($liste_item['nomDomaine'] ?? ''); ?></td>
                               
                                    <td>
                                         <a href="view_liste_pdf.php?id=<?php echo htmlspecialchars($liste_item['idListe']); ?>" target="_blank" title="Voir le PDF: <?php echo htmlspecialchars($liste_item['nom_fichier']); ?>">
                                             <?php echo htmlspecialchars(mb_strimwidth($liste_item['nom_fichier'], 0, 30, "...")); ?> 
                                          </a></td>
                                   
                                     <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($liste_item['dateAjout']))); ?></td>
                                    <td>
                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette liste ?');">
                                            <input type="hidden" name="action" value="delete_liste">
                                            <input type="hidden" name="idListe" value="<?php echo htmlspecialchars($liste_item['idListe']); ?>">
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
        })
    </script>

<?php
mysqli_close($conn); // Fermer la connexion à la base de données
?>

</body>
</html>