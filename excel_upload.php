<?php
require 'vendor/autoload.php'; // Include PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

// Database connection
$host = 'localhost'; // Database host
$db = 'radhe_infotech'; // Database name
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

if (isset($_POST['upload'])) {
    // Check if the file was uploaded without errors
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
        $fileTmpPath = $_FILES['excel_file']['tmp_name'];
        $fileName = $_FILES['excel_file']['name'];
        $fileSize = $_FILES['excel_file']['size'];
        $fileType = $_FILES['excel_file']['type'];

        // Allowed file types
        $allowedExtensions = ['xls', 'xlsx'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileExtension, $allowedExtensions)) {
            // Load the uploaded Excel file
            $spreadsheet = IOFactory::load($fileTmpPath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Loop through the rows
            foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false); // Loop through all cells

                $data = [];
                foreach ($cellIterator as $cell) {
                    $data[] = $cell->getValue();
                }

                // Assuming the Excel columns are: Date, Name, SRN Number, Mobile Number, Status
                $date = isset($data[0]) ? date('Y-m-d', strtotime($data[0])) : null; // Convert date format
                $name = isset($data[1]) ? $data[1] : null;
                $srn_number = isset($data[2]) ? $data[2] : null;
                $mobile_number = isset($data[3]) ? $data[3] : null;
                $status = isset($data[4]) ? $data[4] : 'Pending'; // Default to Pending if not provided

                // Insert data into the database
                $stmt = $pdo->prepare("INSERT INTO aadhaar_entries (date, name, srn_number, mobile_number, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$date, $name, $srn_number, $mobile_number, $status]);
            }

            // Redirect to the listing page with a success message
            header("Location: excelentry.php?message=success");
            exit; // Always exit after a redirect to stop further code execution
        } else {
            echo "Invalid file extension. Please upload an Excel file.";
        }
    } else {
        echo "There was an error uploading the file.";
    }
}
?>
