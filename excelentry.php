<?php
session_start();
require 'vendor/autoload.php'; // Ensure this path is correct for your project
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill; // Import the Fill class

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


try {
    // Prepare and execute the SQL query
    $stmt = $pdo->query("SELECT * FROM aadhaar_entries");
    // Fetch all records into an associative array
    $aadhaar_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    $aadhaar_entries = []; // Initialize as empty array to prevent warnings
}

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['status']) && isset($_POST['id'])) {
    // Capture the new status and the SRN number
    $newStatus = $_POST['status'];
    $srnNumber = $_POST['id'];

    // Update the status in the database
    $stmt = $pdo->prepare("UPDATE aadhaar_entries SET status = :status WHERE srn_number = :srn_number");
    $stmt->execute(['status' => $newStatus, 'srn_number' => $srnNumber]);

    // Redirect to the same page to avoid resubmission on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all entries from the database to display in the listing
$stmt = $pdo->query("SELECT * FROM aadhaar_entries");
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's date in the format 'dd-mm-yyyy'
$today = date('d-m-Y');

// $userDate = $_POST['date']; // Example: 2024-09-16
//     $formattedDate = date('d-m-Y', strtotime($userDate));

// Set number of entries per page
$entriesPerPage = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $entriesPerPage;

// SQL query for fetching limited entries with pagination
$sql = "SELECT * FROM aadhaar_entries LIMIT :offset, :entriesPerPage";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':entriesPerPage', $entriesPerPage, PDO::PARAM_INT);
$stmt->execute();
$entries = $stmt->fetchAll();

// Calculate total pages
$totalEntriesSql = "SELECT COUNT(*) FROM aadhaar_entries";
$totalEntries = $pdo->query($totalEntriesSql)->fetchColumn();
$totalPages = ceil($totalEntries / $entriesPerPage);

// Query to get total count of entries
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM aadhaar_entries");
$totalEntries = $stmt->fetchColumn();

?>



<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Radhe Infotech</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .status-btn {
            width: 100%;
        }

        .status-approved {
            background-color: #28a745;
            color: white;
        }

        .status-rejected {
            background-color: #dc3545;
            color: white;
        }

        .status-pending {
            background-color: #ffc107;
            color: black;
        }
    </style>

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-laugh-wink"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Radhe Infotech</div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item ">
                <a class="nav-link" href="index.html">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Entry Form
            </div>

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item active">
                <a class="nav-link collapsed" href="excelentry.php">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Excel Entry</span>
                </a>
            </li>



            <!-- Divider -->
            <hr class="sidebar-divider">

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Search -->
                    <form
                        class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..."
                                aria-label="Search" aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                        <li class="nav-item dropdown no-arrow d-sm-none">
                            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-search fa-fw"></i>
                            </a>
                            <!-- Dropdown - Messages -->
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
                                aria-labelledby="searchDropdown">
                                <form class="form-inline mr-auto w-100 navbar-search">
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light border-0 small"
                                            placeholder="Search for..." aria-label="Search"
                                            aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </li>

                        <!-- Nav Item - Alerts -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                <!-- Counter - Alerts -->
                                <span class="badge badge-danger badge-counter">3+</span>
                            </a>
                            <!-- Dropdown - Alerts -->
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="alertsDropdown">
                                <h6 class="dropdown-header">
                                    Alerts Center
                                </h6>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-primary">
                                            <i class="fas fa-file-alt text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">December 12, 2019</div>
                                        <span class="font-weight-bold">A new monthly report is ready to download!</span>
                                    </div>
                                </a>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-success">
                                            <i class="fas fa-donate text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">December 7, 2019</div>
                                        $290.29 has been deposited into your account!
                                    </div>
                                </a>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-warning">
                                            <i class="fas fa-exclamation-triangle text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">December 2, 2019</div>
                                        Spending Alert: We've noticed unusually high spending for your account.
                                    </div>
                                </a>
                                <a class="dropdown-item text-center small text-gray-500" href="#">Show All Alerts</a>
                            </div>
                        </li>

                        <!-- Nav Item - Messages -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-envelope fa-fw"></i>
                                <!-- Counter - Messages -->
                                <span class="badge badge-danger badge-counter">7</span>
                            </a>
                            <!-- Dropdown - Messages -->
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="messagesDropdown">
                                <h6 class="dropdown-header">
                                    Message Center
                                </h6>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="dropdown-list-image mr-3">
                                        <img class="rounded-circle" src="img/undraw_profile_1.svg" alt="...">
                                        <div class="status-indicator bg-success"></div>
                                    </div>
                                    <div class="font-weight-bold">
                                        <div class="text-truncate">Hi there! I am wondering if you can help me with a
                                            problem I've been having.</div>
                                        <div class="small text-gray-500">Emily Fowler · 58m</div>
                                    </div>
                                </a>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="dropdown-list-image mr-3">
                                        <img class="rounded-circle" src="img/undraw_profile_2.svg" alt="...">
                                        <div class="status-indicator"></div>
                                    </div>
                                    <div>
                                        <div class="text-truncate">I have the photos that you ordered last month, how
                                            would you like them sent to you?</div>
                                        <div class="small text-gray-500">Jae Chun · 1d</div>
                                    </div>
                                </a>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="dropdown-list-image mr-3">
                                        <img class="rounded-circle" src="img/undraw_profile_3.svg" alt="...">
                                        <div class="status-indicator bg-warning"></div>
                                    </div>
                                    <div>
                                        <div class="text-truncate">Last month's report looks great, I am very happy with
                                            the progress so far, keep up the good work!</div>
                                        <div class="small text-gray-500">Morgan Alvarez · 2d</div>
                                    </div>
                                </a>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="dropdown-list-image mr-3">
                                        <img class="rounded-circle" src="https://source.unsplash.com/Mv9hjnEUHR4/60x60"
                                            alt="...">
                                        <div class="status-indicator bg-success"></div>
                                    </div>
                                    <div>
                                        <div class="text-truncate">Am I a good boy? The reason I ask is because someone
                                            told me that people say this to all dogs, even if they aren't good...</div>
                                        <div class="small text-gray-500">Chicken the Dog · 2w</div>
                                    </div>
                                </a>
                                <a class="dropdown-item text-center small text-gray-500" href="#">Read More Messages</a>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">Radhe Infotech<br> Admin</span>
                                <img class="img-profile rounded-circle" src="img/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Settings
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Activity Log
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="login.php" data-toggle="modal"
                                    data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Aadhaar Update</h1>


                        <!-- Add New Entry Button -->
                        <button class="btn btn-primary ml-auto mr-2" id="openModalBtn">Add New Entry</button>


                        <!-- Add New Upload Excel -->
                        <button type="button" class="btn btn-secondary  mr-2" data-toggle="modal"
                            data-target="#uploadModal">Upload Excel</button>


                        <!-- Download Excel Button -->
                        <a href="?download_excel=1" class="btn btn-success">Download Excel</a>
                    </div>

                    <div class="form-group">
    <label for="entriesPerPage">Show entries:</label>
    <select id="entriesPerPage" class="form-control" style="width: auto; display: inline-block;" onchange="changeEntriesPerPage()">
        <option value="10" <?= isset($_GET['entries']) && $_GET['entries'] == 10 ? 'selected' : ''; ?>>10</option>
        <option value="20" <?= isset($_GET['entries']) && $_GET['entries'] == 20 ? 'selected' : ''; ?>>20</option>
        <option value="50" <?= isset($_GET['entries']) && $_GET['entries'] == 50 ? 'selected' : ''; ?>>50</option>
    </select>
</div>

                    <!-- Listing Table -->
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>SR No.</th> <!-- Added SR No. column -->
                                <th>Date</th>
                                <th>Name</th>
                                <th>SRN Number</th>
                                <th>Mobile Number</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $srNo = 1; // Initialize SR No. ?>
                            <?php foreach ($entries as $entry): ?>
                                <?php
                                // Assign color class based on status
                                $statusClass = '';
                                if ($entry['status'] === 'Approved') {
                                    $statusClass = 'status-approved';
                                } elseif ($entry['status'] === 'Rejected') {
                                    $statusClass = 'status-rejected';
                                } elseif ($entry['status'] === 'Pending') {
                                    $statusClass = 'status-pending';
                                }
                                ?>
                                <tr>
                                    <td><?= $srNo++; ?></td> <!-- Corrected the SR No. line -->
                                    <td><?= date('d-m-Y', strtotime($entry['date'])); ?></td>
                                    <!-- Date formatted as dd-mm-yyyy -->
                                    <td><?= htmlspecialchars($entry['name']); ?></td>
                                    <td><?= htmlspecialchars($entry['srn_number']); ?></td>
                                    <!-- Corrected htmlspecialchars usage -->
                                    <td><?= htmlspecialchars($entry['mobile_number']); ?></td>
                                    <td>
                                        <form method="POST" action="">
                                            <select name="status" class="status-btn <?= $statusClass; ?>"
                                                onchange="this.form.submit()">
                                                <option value="Pending" <?= $entry['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Approved" <?= $entry['status'] === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                                <option value="Rejected" <?= $entry['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                            <input type="hidden" name="id"
                                                value="<?= htmlspecialchars($entry['srn_number']); ?>" />
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

<!-- Pagination Links -->
<nav aria-label="Page navigation">
    <ul class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?entries=<?= $entriesPerPage; ?>&page=<?= $i; ?>"><?= $i; ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>


                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Radhe Infotech 2024</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="login.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload Excel File</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Form for Excel Upload -->
                    <form id="uploadForm" action="excel_upload.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="excel_file">Choose Excel File</label>
                            <input type="file" class="form-control-file" name="excel_file" id="excel_file"
                                accept=".xlsx, .xls" required>
                        </div>
                        <!-- Sample File Download Option -->
                        <div class="form-group">
                            <label>Need a sample file?</label>
                            <a href="generate_sample_excel.php" class="btn btn-info">Download Sample File</a>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" form="uploadForm" name="upload">Choose</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Aadhaar Entry Form -->
    <div id="aadhaarModal" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Aadhaar Entry</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="date">Date</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="srn_number">SRN Number</label>
                            <input type="text" class="form-control" id="srn_number" name="srn_number" required>
                        </div>
                        <div class="form-group">
                            <label for="mobile_number">Mobile Number</label>
                            <input type="text" class="form-control" id="mobile_number" name="mobile_number" required>
                        </div>
                        <button type="submit" name="save" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>



    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="vendor/chart.js/Chart.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="js/demo/chart-area-demo.js"></script>
    <script src="js/demo/chart-pie-demo.js"></script>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Custom scripts for modal functionality-->
    <script>
        var modal = document.getElementById('aadhaarModal');
        var openModalBtn = document.getElementById('openModalBtn');

        // Show modal when "Add New Entry" button is clicked
        openModalBtn.onclick = function () {
            $(modal).modal('show');
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Function to format the date as dd-mm-yyyy
        function formatDate(date) {
            let day = String(date.getDate()).padStart(2, '0');
            let month = String(date.getMonth() + 1).padStart(2, '0'); // January is 0
            let year = date.getFullYear();

            return `${day}-${month}-${year}`;
        }

        // Get today's date
        const today = new Date();
        document.getElementById('date').value = formatDate(today); // Auto-fill the date field
    </script>
    <script>
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                $('#aadhaarModal').modal('show');
            }
        });
    </script>
    <script>
function changeEntriesPerPage() {
    var entries = document.getElementById('entriesPerPage').value;
    window.location.href = "?entries=" + entries + "&page=1"; // Reset to page 1
}
</script>


<script>
document.addEventListener('keydown', function(event) {
    // Check if Shift + A is pressed
    if (event.shiftKey && event.key === 'A') {
        event.preventDefault();
        $('#uploadModal').modal('show'); // Open uploadModal
    }
    
    // Check if Shift + Enter is pressed
    if (event.shiftKey && event.key === 'Enter') {
        event.preventDefault();
        $('#uploadModal').modal('show'); // Open uploadModal
    }
});

// Function to change entries per page and reset pagination
function changeEntriesPerPage() {
    var entries = document.getElementById('entriesPerPage').value;
    window.location.href = "?entries=" + encodeURIComponent(entries) + "&page=1"; // Reset to page 1
}
</script>



</body>

</body>

</html>