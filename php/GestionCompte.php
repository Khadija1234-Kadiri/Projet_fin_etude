<?php 
session_start(); 
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" ||!isset($_SESSION["idAdministrateur"])) { 
    // Rediriger vers la page d'accueil si l'utilisateur n'est pas autorisé
    header("location:index.php"); 
    exit(); 
} 

 
    $nomAdministrateur = $_SESSION["nomAdministrateur"] ?? 'Administrateur';
    
    
?>

<!DOCTYPE html> 
<html> 
<head> 
    <meta charset="utf-8" /> 
    <title>Gestion des Comptes - Espace Administrateur</title>
    <style> 
        * { font-family: Arial, sans-serif; } 
        body { margin: 20px; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #2e916e; } /* Couleur PFE Gestion */
        a { color: #007bff; text-decoration: none; } 
        a:hover { text-decoration: underline; } 
        .content-section { margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background-color: #f9f9f9;}
        .content-section h3 { color: #333; }
        .action-links a { display: inline-block; margin-top: 20px; margin-right: 10px; padding: 8px 15px; color: white; border-radius: 4px; text-decoration: none; }
        .back-link { background-color: #007bff; }
        .back-link:hover { background-color: #0056b3; }
        .logout-link { background-color: #dc3545; }
        .logout-link:hover { background-color: #c82333; }
    </style> 
</head> 
<body> 
   <div class="container">
        <h1>Gestion des Comptes</h1>
        <p>Bienvenue, <?php echo htmlspecialchars($nomAdministrateur); ?>.</p>

        <div class="content-section">
            <h3>Fonctionnalités de gestion des comptes</h3>
            <p>Ici, vous pourrez ajouter, modifier, ou supprimer des comptes utilisateurs (Étudiants, Encadrants, Responsables PFE).</p>
            <ul>
                <li><a href="#">Ajouter un nouvel utilisateur</a></li>
                <li><a href="#">Modifier un utilisateur existant</a></li>
                <li><a href="#">Supprimer un utilisateur</a></li>
                <li><a href="#">Voir la liste des utilisateurs</a></li>
            </ul>
            <!-- Plus de contenu et de fonctionnalités peuvent être ajoutés ici -->
        </div>

        <div class="action-links">
            <a href="PriveeAdministrateur.php" class="back-link">Retour à l'espace Administrateur</a>
            <a href="deconnexion.php" class="logout-link">Se déconnecter</a> 
        </div>
    </div>
</body> 
</html>
