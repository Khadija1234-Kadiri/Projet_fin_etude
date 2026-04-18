
<?php
session_start(); // Optional, but good practice for potential future use (e.g., flash messages)

$message_display = ""; // For displaying success/error messages
$message_type = "";    // "success" or "error"
$form_submitted_successfully = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['envoyer'])) {
    $problem_description = trim($_POST['problem'] ?? '');
    $user_email = filter_var(trim($_POST['user_email'] ?? ''), FILTER_VALIDATE_EMAIL);

    if (empty($user_email)) {
        $message_display = "Veuillez fournir une adresse e-mail valide.";
        $message_type = "error";
    } elseif (empty($problem_description)) {
        $message_display = "Veuillez décrire votre problème.";
        $message_type = "error";
    } else {
        $to = "kadirkadij50@gmail.com"; // Your email address
        $subject = "Nouveau Problème Signalé via PFE Gestion - ContactezNous.php";
        
        $email_body = "Un utilisateur a signalé un problème :\n\n";
        $email_body .= "Email de l'utilisateur : " . htmlspecialchars($user_email) . "\n";
        $email_body .= "Description du problème :\n" . htmlspecialchars($problem_description) . "\n";
        
        $headers = "From: noreply@votredomaine.com\r\n"; // Replace with a generic sender or your domain
        $headers .= "Reply-To: " . htmlspecialchars($user_email) . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        if (mail($to, $subject, $email_body, $headers)) {
            $message_display = "Votre message a été envoyé avec succès. Nous vous contacterons bientôt.";
            $message_type = "success";
            $form_submitted_successfully = true; 
        } else {
            $message_display = "Désolé, une erreur s'est produite lors de l'envoi de votre message. Veuillez réessayer plus tard ou contacter directement kadirkadij50@gmail.com.";
            $message_type = "error";
            // For debugging, you might want to log the error: error_log("Mail sending failed from ContactezNous.php");
        }
    }
}
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Gestion des PFE</title>
    


    <style>
        body { 

             background: linear-gradient(#EEE8AA,#F0E68C);
             background-repeat: no-repeat;
             background-attachment: fixed;

             font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
             margin: 0; 
    
             display: flex; 
             flex-direction: column; /* Pour empiler les enfants verticalement */
             align-items: center; /* Pour centrer les enfants (navbar, container) horizontalement */
             min-height: 100vh; 
             padding: 20px;
             box-sizing: border-box;
        }


               /* Navbar Styles from index.php - Start */
        .navbar{
             width: 1200px;
             height: 75px;
             margin: auto;
        } 

        .logo{
             color:#2e916e ;
             font-size: 35px;
             font-family:Georgia ;
             padding-left: 0px;
             float: left;
             padding-top: 20px;
             margin-top: 5px
        }

        .menu{
             width: 400px; /* This might need adjustment if navbar items are too many */
             float: left;
             height: 70px;
             margin-left: 150px; /* Adjust as needed to space out from logo */
        }

        ul{
             float: left;
             display: flex;
             justify-content: center;
             align-items: center;
        }




        .main { /* Conteneur de la barre de navigation */
             width: 100%; /* Ensure navbar container takes full width */
             margin-bottom: 20px; 
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

       input[type="text"], input[type="email"], textarea {        
               width: 100%;
             padding: 12px 15px;
             margin-bottom: 20px;
             border: 1px solid #ced4da;
             border-radius: 6px;
             box-sizing: border-box;
             font-size: 15px;
             transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }
              input[type="text"]:focus, input[type="email"]:focus, textarea:focus {
             border-color:#2e916e;
             box-shadow: 0 0 0 0.2rem #2e916e;
             outline: none;
        }

        input[type="submit"] {
              width: auto; /* Let padding define width or set specific width */
             min-width: 150px;
             padding: 12px;
             background-color: #2e916e;
             color: white;
             border: none;
             border-radius: 6px;
             cursor: pointer;
             font-size: 15px;
             font-weight: 600;
             transition: background-color 0.2s ease-in-out;
             display: block; /* To center it if needed */
             margin: 0 auto; 
         }

        input[type="submit"]:hover { 
                    background-color:#257758; 
        }

        .message {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            text-align: center;
            font-size: 14px;
        }
        .message.success {
            color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;
        }
        .message.error {
            color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;
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
             text-decoration: none;
        }
        /* Navbar specific styles from index.php - End */
        ul li{ /* Copied from index.php for navbar */
             list-style: none;
             margin-left: 30px; /* Adjusted for potentially more items */
             margin-top: 27px;
             font-size: 14px;
        }

        ul li a{ /* Copied from index.php for navbar */
             text-decoration:none;
             color: #000; /* Changed to black for better visibility on light background */
             font-family: Arial;
             font-weight:bold;
             transition: 0.4s ease-in-out;
        }

        ul li a:hover{ /* Copied from index.php for navbar */
             color: #2e916e;
        }
        .btn{ /* Copied from index.php for navbar button */
             width: 150px; /* Adjusted width */
             height: 40px;
             background: #2e916e;
             border: 2px solid #2e916e;
             margin-top: 0; /* Adjusted margin */
             color: #fff;
             font-size: 15px;
             border-radius: 10px;
             transition: 0.2s ease;
             cursor:pointer;
     
        }
        
    </style>
</head>
<body>
    <div class="main">
        <div class="navbar">
            <div class="icon">
                <h2 class="logo">PFE Gestion</h2>
            </div>
            <div class="menu">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="Apropos.php">À Propos</a></li>
                    <li><a href="VousEtes.php">Vous Êtes</a></li>
                    <li><a href="ContactezNous.php"><button class="btn">Contactez-nous</button></a></li>
                </ul>
            </div>
        </div> 
    </div>
    <div class="container">
        <?php if (!empty($message_display)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo $message_display; ?></div>
        <?php endif; ?>
        <?php if (!$form_submitted_successfully): ?>
   

            <h1>Contactez-Nous</h1>
             <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
           

                
                        <label for="user_email">Votre Email :</label>
                    <input type="email" id="user_email" name="user_email" placeholder="Entrez votre adresse e-mail" value="<?php echo htmlspecialchars($_POST['user_email'] ?? ''); ?>" required />
                </div>
                <div class="form-group">
                    <label for="problem">Problème rencontré :</label>
                    <textarea id="problem" name="problem" placeholder="Décrivez votre problème ici..." rows="5" required><?php echo htmlspecialchars($_POST['problem'] ?? ''); ?></textarea>
                </div>
                <input type="submit" name="envoyer" value="Envoyer" />
            </form>
        <?php endif; ?>
    </div>
    <script src="https://unpkg.com/ionicons@5.4.0/dist/ionicons.js"></script>

       


   
</body>
</html>