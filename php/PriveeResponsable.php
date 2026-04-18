<?php 
session_start(); 
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" ||!isset($_SESSION["idResponsable"])) { 
    // Rediriger vers la page d'accueil si l'utilisateur n'est pas autorisé
    header("location:index.php"); 
    exit(); 
} 

 
    $nomResponsable = $_SESSION["prenomNom"] ?? 'ResponsablePFE';
    


  

?>
<!DOCTYPE html> 
<html> 
<head> 
    <meta charset="utf-8" /> 
     <title>Espace de Responsable </title>
  
    <style> 
        * { font-family: Arial; } 
          body { margin: 20px; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { color: #2e916e; } /* Couleur PFE Gestion */
        a { color: #007bff; text-decoration: none; } 
        a:hover { text-decoration: underline; } 
        .role-specific-content { margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background-color: #f9f9f9;}
        .role-specific-content h3 { color: #333; }
        .logout-link { display: inline-block; margin-top: 20px; padding: 8px 15px; background-color: #dc3545; color: white; border-radius: 4px; text-decoration: none; }
        .logout-link:hover { background-color: #c82333; text-decoration: none; }
    </style> 
</head> 
<body> 
   <div class="container">
        <h2>Bienvenue <?php echo htmlspecialchars($nomResponsable); ?> </h2> 

        <div class="role-specific-content">
           
                <h3>Votre Espace Responsable PFE</h3>
                <p>Gérez l'attribution des PFE et les listes.</p>
                 <ul>
                    <li><a href="#">Attribuer les PFE</a></li>
                    <li><a href="#">Consulter les listes Encadrant/Groupe</a></li>
                </ul>
           

        <a href="deconnexion.php" class="logout-link">Se déconnecter</a> 
    </div>
</body> 
</html>