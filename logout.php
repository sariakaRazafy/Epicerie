<?php
session_start();

// Détruit la session
$_SESSION = [];
session_destroy();

// Redirection vers la page d'accueil
header('Location: index.php');
exit;
