<?php
// get_count.php
require 'config.php'; // Include your database connection file

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM aadhaar_entries");
    $count = $stmt->fetchColumn();
    echo $count;
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
