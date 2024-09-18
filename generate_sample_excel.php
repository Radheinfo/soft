<?php
require 'vendor/autoload.php'; // Ensure this points to your Composer autoload

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

function generateSampleExcelWithColors()
{
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set the header of the sample file
    $sheet->setCellValue('A1', 'Date');
    $sheet->setCellValue('B1', 'Name');
    $sheet->setCellValue('C1', 'SRN Number');
    $sheet->setCellValue('D1', 'Mobile Number');
    $sheet->setCellValue('E1', 'Status');

    // Sample data with status
    $sampleData = [
        ['01-01-2024', 'John Doe', '1234567890', '9876543210', 'Pending'],
        ['02-01-2024', 'Jane Smith', '0987654321', '9123456789', 'Approved'],
        ['03-01-2024', 'Mike Johnson', '1112131415', '9182736455', 'Rejected'],
    ];

    // Define status colors (Fill background colors)
    $statusColors = [
        'Pending' => 'FFFF00', // Yellow for Pending
        'Approved' => '00FF00', // Green for Approved
        'Rejected' => 'FF0000', // Red for Rejected
    ];

    // Add sample data to the sheet
    $rowNumber = 2; // Start from the second row
    foreach ($sampleData as $row) {
        // Insert data into cells
        $sheet->setCellValue('A' . $rowNumber, $row[0]);
        $sheet->setCellValue('B' . $rowNumber, $row[1]);
        $sheet->setCellValue('C' . $rowNumber, $row[2]);
        $sheet->setCellValue('D' . $rowNumber, $row[3]);
        $sheet->setCellValue('E' . $rowNumber, $row[4]);

        // Get the status and apply corresponding color
        $status = $row[4];
        if (isset($statusColors[$status])) {
            $sheet->getStyle('A' . $rowNumber . ':E' . $rowNumber)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($statusColors[$status]);
        }

        $rowNumber++;
    }

    // Set the content type and force download of the Excel file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="sample_file_with_status_colors.xlsx"');
    header('Cache-Control: max-age=0');

    // Save the spreadsheet to output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Call the function to generate and download the sample Excel
generateSampleExcelWithColors();
