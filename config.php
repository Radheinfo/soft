<?php
// config.php

$host = 'localhost'; // Database host
$db   = 'radhe_infotech'; // Database name
$user = 'root'; // Database username
$pass = ''; // Database password
$charset = 'utf8mb4'; // Charset

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Handle the error and stop the script
    echo 'Connection failed: ' . $e->getMessage();
    exit;
}
?>
