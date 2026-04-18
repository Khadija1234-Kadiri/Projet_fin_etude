<?php
session_start(); 
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" ||!isset($_SESSION["idEtudiant"])) { 
    // Rediriger vers la page d'accueil si l'utilisateur n'est pas autorisé
    header("location:index.php"); 
    exit(); 
} 
 
    $nomAdministrateur = $_SESSION["Nom"] ?? 'Etudiant';
    


  
// Logique de recherche (à implémenter avec la base de données plus tard)
$termeRecherche = $_GET['q'] ?? '';
$filiereRecherche = $_GET['filiere'] ?? '';
$exemplesPFE = [];
$messageRecherche = null;

if (!empty($termeRecherche) || !empty($filiereRecherche)) {
    // Simuler une recherche - Remplacez ceci par votre logique de BDD
    if (stripos($termeRecherche, "informatique") !== false || $filiereRecherche === "informatique") {
        $exemplesPFE[] = ["titre" => "Développement d'une application web pour la gestion de PFE", "auteur" => "John Doe", "annee" => 2023, "lien" => "#pfe1"];
       
    }
    if (stripos($termeRecherche, "gestion") !== false || $filiereRecherche === "gestion") {
        $exemplesPFE[] = ["titre" => "Analyse de marché pour un nouveau produit", "auteur" => "Jane Roe", "annee" => 2022, "lien" => "#pfe2"];
    }

    if (empty($exemplesPFE) ) {
        $messageRecherche = "Aucun résultat trouvé pour \"".htmlspecialchars($termeRecherche)."\" dans la filière \"".htmlspecialchars($filiereRecherche)."\".";
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <title>Recherche PFE  <?php echo htmlspecialchars($role); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #f4f4f4; color: #333; padding: 20px; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 900px; margin: auto; }
        h1, h2, h3 { color: #2e916e; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .logout-link, .back-link { display: inline-block; margin-top: 20px; margin-right:10px; padding: 8px 15px; color: white; border-radius: 4px; text-decoration: none; }
        .logout-link { background-color: #dc3545; }
        .logout-link:hover { background-color: #c82333; }
        .back-link { background-color: #007bff; }
        .back-link:hover { background-color: #0056b3; }
        .search-form { margin-bottom: 30px; padding: 20px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; }
        .search-form label { display: block; margin-bottom: 8px; font-weight: 600; }
        .search-form input[type="text"], .search-form select { width: calc(100% - 22px); padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .search-form input[type="submit"] { padding: 10px 20px; background-color: #2e916e; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .search-form input[type="submit"]:hover { background-color: #247e5b; }
        .results-section { margin-top: 20px; }
        .result-item { background-color: #f9f9f9; border: 1px solid #eee; padding: 15px; margin-bottom: 10px; border-radius: 4px; }
        .result-item h4 { margin-top: 0; color: #333; }
        .result-item p { margin-bottom: 5px; font-size: 0.9em; }
        .no-results { color: #777; font-style: italic; padding: 10px; background-color: #fff8e1; border: 1px solid #ffecb3; border-radius: 4px;}
    </style>
</head>
<body>
    <div class="container">
        <h1>Recherche d'Exemples de PFE </h1>
        <p>Bienvenue, <?php echo htmlspecialchars($nomEtudiant); ?> </p>

        <div class="search-form">
            <form method="get" action="RecherchePFE.php">
                <div>
                    <label for="q">Mots-clés :</label>
                    <input type="text" id="q" name="q" placeholder="Ex: intelligence artificielle, marketing digital..." value="<?php echo htmlspecialchars($termeRecherche); ?>">
                </div>
                <div>
                    <label for="filiere">Filière :</label>
                    <select id="filiere" name="filiere">
                        <option value="">Toutes les filières</option>
                        <option value="informatique" <?php echo ($filiereRecherche === 'informatique') ? 'selected' : ''; ?>>Informatique</option>
                        <option value="gestion" <?php echo ($filiereRecherche === 'gestion') ? 'selected' : ''; ?>>Gestion</option>
                        <option value="genie_civil" <?php echo ($filiereRecherche === 'genie_civil') ? 'selected' : ''; ?>>Génie Civil</option>
                        <!-- Ajoutez d'autres filières ici -->
                    </select>
                </div>
                <input type="submit" value="Rechercher">
            </form>
        </div>

        <div class="results-section">
            <h2>Résultats</h2>
            <?php if ($messageRecherche): ?>
                <p class="no-results"><?php echo $messageRecherche; ?></p>
            <?php endif; ?>

            <h3>Exemples de PFE</h3>
            <?php if (!empty($exemplesPFE)): foreach ($exemplesPFE as $pfe): ?>
                <div class="result-item"><h4><a href="<?php echo htmlspecialchars($pfe['lien']); ?>"><?php echo htmlspecialchars($pfe['titre']); ?></a></h4><p>Auteur: <?php echo htmlspecialchars($pfe['auteur']); ?> | Année: <?php echo htmlspecialchars($pfe['annee']); ?></p></div>
            <?php endforeach; elseif (empty($termeRecherche) && empty($filiereRecherche)): ?>
                <p class="no-results">Effectuez une recherche pour voir des exemples de PFE.</p>
            <?php elseif (!$messageRecherche): ?><p class="no-results">Aucun exemple de PFE trouvé.</p><?php endif; ?>

            <h3>Ressources Pédagogiques</h3>
            <?php if (!empty($ressourcesPedagogiques)): foreach ($ressourcesPedagogiques as $res): ?>
                <div class="result-item"><h4><a href="<?php echo htmlspecialchars($res['lien']); ?>"><?php echo htmlspecialchars($res['titre']); ?></a></h4><p>Type: <?php echo htmlspecialchars($res['type']); ?> | Source: <?php echo htmlspecialchars($res['source']); ?></p></div>
            <?php endforeach; elseif (empty($termeRecherche) && empty($filiereRecherche)): ?>
                <p class="no-results">Effectuez une recherche pour voir des ressources.</p>
            <?php elseif (!$messageRecherche): ?><p class="no-results">Aucune ressource trouvée.</p><?php endif; ?>
        </div>

        <a href="Session.php" class="back-link">Retour à mon espace</a>
        <a href="deconnexion.php" class="logout-link">Se déconnecter</a>
    </div>
</body>
</html>