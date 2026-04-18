<?php 
session_start(); 
if (!isset($_SESSION["autoriser"]) || $_SESSION["autoriser"] != "oui" ||!isset($_SESSION["idEtudiant"])) { 
    // Rediriger vers la page d'accueil si l'utilisateur n'est pas autorisé
    header("location:index.php"); 
    exit(); 
} 

 
    $nomEtudiant = $_SESSION["prenomNom"] ?? 'Étudiant';
    
    