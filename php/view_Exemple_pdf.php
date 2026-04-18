<?php
session_start(); // Peut être utile si vous voulez ajouter des contrôles d'accès plus tard

require_once 'Connexion.php'; // Assurez-vous que Connexion.php est accessible

$id_exemple = 0;
if (isset($_GET['id'])) {
    $id_exemple = filter_var($_GET['id'], FILTER_VALIDATE_INT);
}

if (!$id_exemple || $id_exemple <= 0) {
    header("HTTP/1.0 400 Bad Request");
    echo "ID d'exemple PFE invalide ou manquant.";
    exit();
}

if (!$conn) {
    // $conn est défini dans Connexion.php
    // Cette vérification est redondante si Connexion.php fait die() en cas d'échec,
    // mais c'est une bonne pratique de vérifier.
    header("HTTP/1.0 500 Internal Server Error");
    echo "Erreur de connexion à la base de données.";
    exit();
}

mysqli_set_charset($conn, "utf8");

$sql = "SELECT Fichier_pdf, nom_fichier, type_mime, taille FROM ExemplePFE WHERE idExemplePFE = ?";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id_exemple);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt); // Important pour mysqli_stmt_num_rows

    if (mysqli_stmt_num_rows($stmt) == 1) {
        mysqli_stmt_bind_result($stmt, $pdf_content_base64, $nom_fichier, $type_mime, $taille); // Content from DB is Base64
        mysqli_stmt_fetch($stmt);

     $pdf_content_decoded = base64_decode($pdf_content_base64); // Decode Base64

        header("Content-Type: " . $type_mime);
        header("Content-Disposition: inline; filename=\"" . basename($nom_fichier) . "\""); // basename pour la sécurité
         header("Content-Length: " . $taille); // $taille is the original binary file size, which is correct
         header("Cache-Control: private"); // Pour certains navigateurs/proxies
        header("Pragma: private");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé

           echo $pdf_content_decoded; // Echo the decoded binary content
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "Exemple PFE non trouvé.";
    }
    mysqli_stmt_close($stmt);
} else {
    header("HTTP/1.0 500 Internal Server Error");
    echo "Erreur lors de la préparation de la requête : " . mysqli_error($conn);
}

mysqli_close($conn);
exit(); // Assurez-vous qu'aucun autre contenu n'est envoyé
?>