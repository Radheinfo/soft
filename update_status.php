<?php
// Assuming you have already established a PDO connection as $pdo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the ID and status from the form
    $id = $_POST['id'];
    $status = $_POST['status'];

    // Validate input
    if (in_array($status, ['Approved', 'Rejected', 'Pending'])) {
        try {
            // Prepare and execute the SQL update query
            $stmt = $pdo->prepare("UPDATE aadhaar_entries SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $id]);

            // Redirect to the listing page with a success message
            header("Location: excelentry.php?status=success");
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "Invalid status.";
    }
} else {
    echo "Invalid request method.";
}
