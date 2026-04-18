
<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "gestionpfe";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Échec de la connexion : " . mysqli_connect_error());
}
?>