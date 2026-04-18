<?php
session_start();



$cinResponsable = $_POST["cinResponsable"] ?? null;
$motpasseResponsable_input = $_POST["motpasseResponsable"] ?? null; 
$valider = $_POST["valider"] ?? null;
$erreur = "";

if (isset($valider)) {
    if (empty($cinResponsable) || empty($motpasseResponsable_input)) {
        $erreur = "Le CIN et le mot de passe sont requis.";
    } else {
      
        // Assurez-vous que le chemin est correct si Connexion.php n'est pas dans le même dossier.
        include("Connexion.php"); 

        // Vérifier si la connexion a réussi (si $conn est défini et n'est pas false)
        if (!$conn || mysqli_connect_errno()) {
            $erreur = "Erreur de connexion à la base de données. Veuillez contacter l'administrateur.";
            // Log l'erreur réelle pour le débogage côté serveur, ne pas l'afficher à l'utilisateur.
            // error_log("Erreur de connexion MySQL: " . mysqli_connect_error());
        } else {
            // Préparer la requête pour éviter les injections SQL
            // Table: etudiant
            // Colonnes: idEtudiant, nomEtudiant, prenomEtudiant, motpasseEtudiant (haché), cinEtudiant
            $stmt = mysqli_prepare($conn, "SELECT idResponsable, nomResponsable, prenomResponsable, motpasseResponsable FROM ResponsablePFE WHERE cinResponsable = ?");

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $cinResponsable);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if ($user = mysqli_fetch_assoc($result)) {
                    // Vérifier le mot de passe entré contre le hash stocké dans la base de données
                    if (password_verify($motpasseResponsable_input, $user['motpasseResponsable'])) {
                        // Le mot de passe est correct
                        $_SESSION["prenomNom"] = ucfirst(strtolower($user["prenomResponsable"])) . " " . strtoupper($user["nomResponsable"]);
                        $_SESSION["idResponsable"] = $user["idResponsable"]; // Optionnel: stocker l'ID de l'étudiant
                        $_SESSION["autoriser"] = "oui"; // Marqueur d'autorisation
                        
                        // Redirection vers la page de session de l'étudiant
                        header("location:RespoPFE.php"); // Doit être c:\xampp\htdocs\GestionPFE\Session.php
                        exit(); // Important après une redirection header
                    } else {
                        // Mot de passe incorrect
                        $erreur = "CIN ou mot de passe incorrect.";
                    }
                } else {
                    // Aucun utilisateur trouvé avec ce CIN
                    $erreur = "CIN ou mot de passe incorrect.";
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
    <title>Authentification Responsable - GestionPFE</title>
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
            margin-bottom: 20px; /* Espace entre la navbar et le formulaire */
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
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border-color:#2e916e;
            box-shadow: 0 0 0 0.2rem #2e916e;
            outline: none;
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
            background-color:#2e916e; 
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
    <div class="container">
        <h1>Authentification Responsable</h1>
        <?php if (!empty($erreur)): ?>
            <div class="erreur"><?php echo htmlspecialchars($erreur); ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="cinResponsable">CIN Responsable :</label>
                <input type="text" id="cinResponsable" name="cinResponsable" placeholder="Entrez votre CIN" value="<?php echo htmlspecialchars($cinResponsable ?? ''); ?>" required />
            </div>
            <div class="form-group">
                <label for="motpasseResponsable">Mot de passe :</label>
                <input type="password" id="motpasseResponsable" name="motpasseResponsable" placeholder="Entrez votre mot de passe" required />
            </div>
            <input type="submit" name="valider" value="S'authentifier" />
        </form>
    </div>
</body>
</html>