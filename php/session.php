<?php 
session_start(); 
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui") { 
    // Rediriger vers la page d'accueil si l'utilisateur n'est pas autorisé
    header("location:index.php"); 
    exit(); 
} 

$nomUtilisateur = "Utilisateur"; // Valeur par défaut
$role = "Inconnu"; // Rôle par défaut

// Déterminer le nom et le rôle de l'utilisateur en fonction des variables de session
if (isset($_SESSION["idAdministrateur"])) {
    $nomUtilisateur = $_SESSION["nomAdministrateur"] ?? 'Administrateur';
    $role = "Administrateur";

} elseif (isset($_SESSION["idEncadrant"])) {
    $nomUtilisateur = $_SESSION["prenomNom"] ?? 'Encadrant';
    $role = "Encadrant";
} elseif (isset($_SESSION["idResponsable"])) {
    $nomUtilisateur = $_SESSION["prenomNom"] ?? 'Responsable PFE';
    $role = "Responsable PFE";
} 
  

?>
<!DOCTYPE html> 
<html> 
<head> 
    <meta charset="utf-8" /> 
     <title>Espace <?php echo htmlspecialchars($role); ?></title>
  
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
        <h2>Bienvenue <?php echo htmlspecialchars($nomUtilisateur); ?> </h2> 

        <div class="role-specific-content">
           
            <?php if ($role === "Encadrant"): ?>
                <h3>Votre Espace Encadrant</h3>
                <p>Gérez les PFE de vos étudiants, proposez des sujets, donnez des feedbacks.</p>
                <ul>
                    <li><a href="#">Liste de mes étudiants</a></li>
                    <li><a href="#">Proposer des sujets de PFE</a></li>
                    <li><a href="#">Évaluer les rapports</a></li>
                </ul>
            <?php elseif ($role === "Administrateur"): ?>
                <h3>Votre Espace Administrateur</h3>
                <p>Gérez les utilisateurs et les paramètres du système.</p>
                 <ul>
                    <li><a href="#">Gestion des comptes</a></li>
                    <li><a href="#">Paramètres généraux</a></li>
                </ul>
            <?php elseif ($role === "Responsable PFE"): ?>
                <h3>Votre Espace Responsable PFE</h3>
                <p>Gérez l'attribution des PFE et les listes.</p>
                 <ul>
                    <li><a href="#">Attribuer les PFE</a></li>
                    <li><a href="#">Consulter les listes Encadrant/Groupe</a></li>
                </ul>
            <?php else: ?>
                <p>Bienvenue dans votre espace personnel.</p>
            <?php endif; ?>
        </div>

        <a href="deconnexion.php" class="logout-link">Se déconnecter</a> 
    </div>
</body> 
</html>