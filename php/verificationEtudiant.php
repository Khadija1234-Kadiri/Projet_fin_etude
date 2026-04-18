<?php
session_start();

// Le fichier Connexion.php doit se trouver dans c:\xampp\htdocs\GestionPFE\
// et doit définir la variable $conn (connexion mysqli à la base de données 'gestionpfe')

$cneEtudiant = $_POST["cneEtudiant"] ?? null;
$dateNaissance = $_POST["dateNaissance"] ?? null; // Renommé pour clarté
$valider = $_POST["valider"] ?? null;
$erreur = "";

if (isset($valider)) {
    if (empty($cneEtudiant) || empty($dateNaissance)) {
        $erreur = "CNE ou la date ne sont pas renseignes.";
    } else {
        // Inclure le fichier de connexion
        // Assurez-vous que le chemin est correct si Connexion.php n'est pas dans le même dossier.
        include("Connexion.php"); 

        // Vérifier si la connexion a réussi (si $conn est défini et n'est pas false)
        if (!$conn || mysqli_connect_errno()) {
            $erreur = "Erreur de connexion à la base de données. Veuillez contacter l'administrateur.";
           
        } else {
            // Préparer la requête pour éviter les injections SQL
            // Table: etudiant
            // Colonnes: idEtudiant, nomEtudiant, prenomEtudiant, motpasseEtudiant (haché), cinEtudiant
            $stmt = mysqli_prepare($conn, "SELECT idEtudiant, nomEtudiant, prenomEtudiant, dateNaissance AS date_naissance_db FROM Etudiant WHERE cneEtudiant = ?");


            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $cneEtudiant);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if ($user = mysqli_fetch_assoc($result)) {
                    // Vérifier le mot de passe entré contre le hash stocké dans la base de données
                    // Vérifier si la date de naissance entrée correspond à celle dans la base de données
                    // L'input de type="date" fournit 'YYYY-MM-DD'. Assurez-vous que $user['date_naissance_db'] est dans ce format.
                    if ($dateNaissance === $user['date_naissance_db']) {
                      
                        // Le mot de passe est correct
                        $_SESSION["prenomNom"] = ucfirst(strtolower($user["prenomEtudiant"])) . " " . strtoupper($user["nomEtudiant"]);
                        $_SESSION["idEtudiant"] = $user["idEtudiant"]; // Optionnel: stocker l'ID de l'étudiant
                        $_SESSION["autoriser"] = "oui"; // Marqueur d'autorisation
                        
                        // Redirection vers la page de session de l'étudiant
                        header("location:PriveeEtudiant.php"); // Doit être c:\xampp\htdocs\GestionPFE\Session.php
                        exit(); // Important après une redirection header
                    } else {
                        // Mot de passe incorrect
                        $erreur = "CNE ou la date de naissance sont incorrect.";
                    }
                } else {
                    // Aucun utilisateur trouvé avec ce CIN
                    $erreur = "CNE ou la date de naissance sont incorrect.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $erreur = "Erreur lors de la préparation de la requête. Veuillez contacter l'administrateur.";
                // error_log("Erreur de préparation MySQL: " . mysqli_error($conn));
            }
            mysqli_close($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <title>Authentification Étudiant - GestionPFE</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            
            background: linear-gradient(#EEE8AA,#F0E68C);
            display: flex; 
             flex-direction: column; /* Pour empiler les enfants verticalement */
            align-items: center; /* Pour centrer les enfants (navbar, container) horizontalement */
            min-height: 100vh; 
            padding: 20px;
            box-sizing: border-box;

        }

             .main { /* Conteneur de la barre de navigation */
            margin-bottom: 20px;
            margin-top :50px ; 
            width: 500px; 
            height: 500px ;
             /* Espace entre la navbar et le formulaire */
        }             
         


        .container { 
            width: 100%;
            max-width: 400px; 
            padding: 35px 30px; 
            background-color: #ffffff; 
            border-radius: 10px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
        }
        h1 { 
            text-align: center; 
            color:#2e916e; 
            margin-bottom: 30px; 
            font-size: 24px;
        }
        label { 
            display: block; 
            margin-bottom: 8px; 
            color:#2e916e; 
            font-weight: 600; 
            font-size: 14px;
        }
        input[type="text"], input[type="date"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
         input[type="text"]:focus, input[type="date"]:focus {
            border-color:#2e916e;
            box-shadow: 0 0 0 0.2rem #2e916e;
            outline: none;
             border: 1px solid #ced4da;
        }
        input[type="submit"] {
            width: 100%;
            padding: 12px;
           background-color: #2e916e;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.2s ease-in-out;
        }
        input[type="submit"]:hover { 
            background-color:rgb(46, 106, 85); 
        }
        .erreur { 
            color: #D8000C; 
            background-color: #FFD2D2; 
            border: 1px solid #D8000C; 
            padding: 12px 15px; 
            margin-bottom: 20px; 
            border-radius: 6px; 
            text-align: center; 
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
            margin-top : 20px ; 
        }

         .contact{
    
                border:#2e916e;
                width: 330px;
                float: left;
                margin-left: 270px;
                }

               a{
    color: #2e916e;
         }       
         

    </style>
</head>
<body>

 
           
         <div class="main">        
        
    <div class="container">
       
        <?php if (!empty($erreur)): ?>
            <div class="erreur"><?php echo htmlspecialchars($erreur); ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="cneEtudiant">CNE Étudiant :</label>
                <input type="text" id="cneEtudiant" name="cneEtudiant" placeholder="Entrez votre CNE" value="<?php echo htmlspecialchars($cneEtudiant ?? ''); ?>" required />
            </div>
            <div class="form-group">
                <label for="dateNaissance">Date de naissance :</label>
                <input type="date" id="dateNaissance" name="dateNaissance" placeholder="entrer la date de naissance" required />
            </div>
            <input type="submit" name="valider" value="Verification" />
        </form>
    </div>
</body>
</html>