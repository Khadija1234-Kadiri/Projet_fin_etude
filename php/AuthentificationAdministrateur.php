<?php
session_start();

// Le fichier Connexion.php doit se trouver dans c:\xampp\htdocs\GestionPFE\
// et doit définir la variable $conn (connexion mysqli à la base de données 'gestionpfe')

$cinAdministrateur = $_POST["cinAdministrateur"] ?? null;
$motpasseAdministrateur_input = $_POST["motpasseAdministrateur"] ?? null; // Renommé pour clarté
$valider = $_POST["valider"] ?? null;
$erreur = "";


if (isset($valider)) {
    if (empty($cinAdministrateur) || empty($motpasseAdministrateur_input)) {
        $erreur = "Le CIN et le mot de passe sont requis.";
    } else {
       
    
        // Inclure le fichier de connexion
        // Assurez-vous que le chemin est correct si Connexion.php n'est pas dans le même dossier.
        include("Connexion.php"); 

        // Vérifier si la connexion a réussi (si $conn est défini et n'est pas false)
        if (!$conn || mysqli_connect_errno()) {
            $erreur = "Erreur de connexion à la base de données.";
            // Log l'erreur réelle pour le débogage côté serveur, ne pas l'afficher à l'utilisateur.
            // error_log("Erreur de connexion MySQL: " . mysqli_connect_error());
        } else {
            // Préparer la requête pour éviter les injections SQL
            // Table: etudiant
            // Colonnes: idEtudiant, nomEtudiant, prenomEtudiant, motpasseEtudiant (haché), cinEtudiant
            $stmt = mysqli_prepare($conn, "SELECT idAdministrateur, nomAdministrateur, motpasseAdministrateur FROM Administrateur WHERE cinAdministrateur = ?");

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $cinAdministrateur);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if ($user = mysqli_fetch_assoc($result)) {
                    // Vérifier le mot de passe entré contre le hash stocké dans la base de données
                    if (password_verify($motpasseAdministrateur_input, $user['motpasseAdministrateur'])) {
                        // Le mot de passe est correct
                        $_SESSION["nomAdministrateur"] = strtoupper($user["nomAdministrateur"]);
                        $_SESSION["idAdministrateur"] = $user["idAdministrateur"]; // Optionnel: stocker l'ID de l'étudiant
                        $_SESSION["autoriser"] = "oui"; // Marqueur d'autorisation
                        
                        // Redirection vers la page de session 
                        header("location:Administrateur.php"); // Session.php est dans le même répertoire (c:\xampp\htdocs\backend\)
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
                $erreur = "Erreur lors de la préparation de la requête.";
                // error_log("Erreur de préparation MySQL: " . mysqli_error($conn));
            }
            mysqli_close($conn);
        }
     }
}
?>


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

            #verification{
                text-align : center ;

            }

    </style>


         <!DOCTYPE html>
           <html lang="fr">
             <head>
             <meta charset="utf-8" />
             <title>Authentification Administrateur - GestionPFE</title>

  


    <div class="container">
        <h1>Authentification Administrateur</h1>
        <?php if (!empty($erreur)): ?>
            <div class="erreur"><?php echo htmlspecialchars($erreur); ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="cinAdministrateur">CIN Administrateur :</label>
                <input type="text" id="cinAdministrateur" name="cinAdministrateur" placeholder="Entrez votre CIN" value="<?php echo htmlspecialchars($cinAdministrateur ?? ''); ?>" required />
            
           <br/>
                <label for="motpasseAdministrateur">Mot de passe :</label>
                <input type="password" id="motpasseAdministrateur" name="motpasseAdministrateur" placeholder="Entrez votre mot de passe" required />
            </div>
            <input type="submit" name="valider" value="S'authentifier" />
          
        </form>
    </div>
</body>
</html>