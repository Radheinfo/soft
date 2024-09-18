<?php
// Start PHP session at the very top
session_start();

// Include PhpSpreadsheet library (make sure it is installed via Composer)
require 'vendor/autoload.php';

// Use the necessary classes from PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Database connection
$host = 'localhost'; // Database host
$db = 'radhe_infotech'; // Database name
$user = 'root'; // Database username
$pass = ''; // Database passwor

// Create a new PDO instance
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


// Include database connection
require 'db_connection.php';

// Initialize status filter
$status_filter = isset($_POST['status_filter']) ? $_POST['status_filter'] : '';

// Build the SQL query with the status filter
$sql = "SELECT * FROM aadhaar_entries";
if ($status_filter && $status_filter !== 'All') {
    $sql .= " WHERE status = :status";
}

// Prepare and execute the statement
$stmt = $pdo->prepare($sql);
if ($status_filter && $status_filter !== 'All') {
    $stmt->bindParam(':status', $status_filter);
}
$stmt->execute();
$aadhaar_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $date = $_POST['date'];
    $name = $_POST['name'];
    $srn_number = $_POST['srn_number'];
    $mobile_number = $_POST['mobile_number'];
    $status = $_POST['status']; // Added status field

    // Insert the submitted data into the database
    $stmt = $pdo->prepare("INSERT INTO aadhaar_entries (date, name, srn_number, mobile_number, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$date, $name, $srn_number, $mobile_number, $status]);
}


// Handle Excel download
if (isset($_GET['download_excel'])) {
    downloadExcel($pdo);
}

// Function to download data as Excel
function downloadExcel($pdo)
{
    // Fetch Aadhaar entries from the database
    $stmt = $pdo->query("SELECT date, name, srn_number, mobile_number, status FROM aadhaar_entries");
    $aadhaar_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create a new spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Add header row
    $sheet->setCellValue('A1', 'Date');
    $sheet->setCellValue('B1', 'Name');
    $sheet->setCellValue('C1', 'SRN Number');
    $sheet->setCellValue('D1', 'Mobile Number');
    $sheet->setCellValue('E1', 'Status');

    // Add data rows
    $rowNumber = 2;
    foreach ($aadhaar_entries as $entry) {
        $sheet->setCellValue('A' . $rowNumber, $entry['date']);
        $sheet->setCellValue('B' . $rowNumber, $entry['name']);
        $sheet->setCellValue('C' . $rowNumber, $entry['srn_number']);
        $sheet->setCellValue('D' . $rowNumber, $entry['mobile_number']);
        $sheet->setCellValue('E' . $rowNumber, $entry['status']);

        // Apply color based on status
        switch ($entry['status']) {
            case 'Approved':
                $color = Fill::FILL_SOLID;
                $sheet->getStyle('A' . $rowNumber . ':E' . $rowNumber)
                      ->applyFromArray([
                          'fill' => [
                              'fillType' => $color,
                              'startColor' => ['argb' => '00FF00'] // Green
                          ]
                      ]);
                break;
            case 'Rejected':
                $color = Fill::FILL_SOLID;
                $sheet->getStyle('A' . $rowNumber . ':E' . $rowNumber)
                      ->applyFromArray([
                          'fill' => [
                              'fillType' => $color,
                              'startColor' => ['argb' => 'FF0000'] // Red
                          ]
                      ]);
                break;
            case 'Pending':
                $color = Fill::FILL_SOLID;
                $sheet->getStyle('A' . $rowNumber . ':E' . $rowNumber)
                      ->applyFromArray([
                          'fill' => [
                              'fillType' => $color,
                              'startColor' => ['argb' => 'FFFF00'] // Yellow
                          ]
                      ]);
                break;
        }

        $rowNumber++;
    }

    // Output the spreadsheet to browser
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="aadhaar_entries.xlsx"');
    $writer->save('php://output');
    exit;
}

?>










<?php
// Start session and include necessary files
session_start();
require 'vendor/autoload.php'; // Ensure the autoload file is included for Excel export

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=radhe_infotech', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE aadhaar_entries SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
}

// Handle form submission for adding new entries
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $date = $_POST['date'];
    $name = $_POST['name'];
    $srn_number = $_POST['srn_number'];
    $mobile_number = $_POST['mobile_number'];

    $stmt = $pdo->prepare("INSERT INTO aadhaar_entries (date, name, srn_number, mobile_number) VALUES (?, ?, ?, ?)");
    $stmt->execute([$date, $name, $srn_number, $mobile_number]);
}

// Download Excel functionality
if (isset($_GET['download_excel'])) {
    downloadExcel($pdo);
}

function downloadExcel($pdo)
{
    // Fetch Aadhaar entries from the database
    $stmt = $pdo->query("SELECT date, name, srn_number, mobile_number, status FROM aadhaar_entries");
    $aadhaar_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create a new spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Add header row
    $sheet->setCellValue('A1', 'Date');
    $sheet->setCellValue('B1', 'Name');
    $sheet->setCellValue('C1', 'SRN Number');
    $sheet->setCellValue('D1', 'Mobile Number');
    $sheet->setCellValue('E1', 'Status');

    // Add data rows
    $rowNumber = 2;
    foreach ($aadhaar_entries as $entry) {
        $sheet->setCellValue('A' . $rowNumber, $entry['date']);
        $sheet->setCellValue('B' . $rowNumber, $entry['name']);
        $sheet->setCellValue('C' . $rowNumber, $entry['srn_number']);
        $sheet->setCellValue('D' . $rowNumber, $entry['mobile_number']);
        $sheet->setCellValue('E' . $rowNumber, $entry['status']);

        // Apply color based on status
        $color = match ($entry['status']) {
            'Approved' => '00FF00', // Green
            'Rejected' => 'FF0000', // Red
            'Pending' => 'FFFF00', // Yellow
        };

        $sheet->getStyle('A' . $rowNumber . ':E' . $rowNumber)
              ->applyFromArray([
                  'fill' => [
                      'fillType' => Fill::FILL_SOLID,
                      'startColor' => ['argb' => $color]
                  ]
              ]);

        $rowNumber++;
    }

    // Output the spreadsheet to the browser
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="aadhaar_entries.xlsx"');
    $writer->save('php://output');
    exit;
}
?>