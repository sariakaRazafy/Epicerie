<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "epicerie_db";

$cnx = new mysqli($host, $user, $pass, $db);

if ($cnx->connect_error) {
    die("Erreur de connexion : " . $cnx->connect_error);
}
