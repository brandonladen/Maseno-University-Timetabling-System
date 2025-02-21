<?php
include '../server.php';

if (!isset($_SESSION['role_id']) || empty($_SESSION['role_id'])) {
  session_destroy();
  header("location: ../index.php");
  exit;
}

if ($_SESSION['role_name'] !== 'Admin') {
  header("Location: index.php");
  exit;
}

// Get school ID from URL parameter
$school_id = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($school_id)) {
  header("Location: schools.php");
  exit;
}

// Fetch school details
$school_query = "SELECT * FROM school_details WHERE school_id = '$school_id'";
$school_result = mysqli_query($db, $school_query);
$school = mysqli_fetch_assoc($school_result);

if (!$school) {
  header("Location: schools.php");
  exit;
}

// Handle department addition
if (isset($_POST['add-department-btn'])) {
  $department_id = $_POST['department_id'];

  if (empty($department_id)) {
    array_push($errors, "Department ID is required");
  }
  
  if (count($errors) == 0) {
    $add_dept_query = "INSERT INTO school_department_details (school_id, department_id) 
                      VALUES ('$school_id', '$department_id')";
    $results = mysqli_query($db, $add_dept_query);
    header("Location: departments.php?id=$school_id");
  }
}

// Handle department deletion
if (isset($_POST['delete-department-btn'])) {
  $id = $_POST['record_id'];
  
  if (!empty($id)) {
    $delete_query = "DELETE FROM school_department_details WHERE id = '$id'";
    mysqli_query($db, $delete_query);
  }
  header("Location: departments.php?id=$school_id");
}
?>

<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    <title>Departments | EDUTIME</title>
    <?php include '../assets/components/header.php'; ?>
</head>

<body>
    <?php include '../assets/components/topbar.php'; ?>
    <?php include '../assets/components/sidebar.php'; ?>


    <body>
    <!-- Preloader -->
    <div class="preloader">
        <div class="lds-ripple">
            <div class="lds-pos"></div>
            <div class="lds-pos"></div>
        </div>
    </div>

    <!-- Main wrapper -->
    <div id="main-wrapper" data-layout="vertical" data-navbarbg="skin5" data-sidebartype="full"
        data-sidebar-position="absolute" data-header-position="absolute" data-boxed-layout="full">
        <!-- Rest of your content -->

    <div class="page-wrapper">
        <div class="page-breadcrumb pt-5">
            <div class="row">
                <div class="col-12 d-flex no-block align-items-center">
                    <h4 class="page-title">Departments - <?php echo htmlspecialchars($school['school_id']); ?></h4>
                    <div class="ms-auto text-end">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item"><a href="schools.php">Schools</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Departments</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Departments List</h5>
                            <button type="button" class="btn btn-primary float-end m-2" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                                Add Department
                            </button>
                            <table id="dtBasicExample" class="table table-striped table-bordered table-sm" cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>Department ID</th>
                                        <th>Date Updated</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
$dept_query = "SELECT * FROM school_department_details WHERE school_id = '$school_id'";
$dept_result = mysqli_query($db, $dept_query);

if ($dept_result && mysqli_num_rows($dept_result) > 0) {
    while ($row = mysqli_fetch_assoc($dept_result)) {
        echo "<tr>";
        // Make department ID a clickable link
        echo "<td><a href='courses.php?school_id=" . htmlspecialchars($school_id) . "&department_id=" . htmlspecialchars($row['department_id']) . "'>" 
             . htmlspecialchars($row['department_id']) . "</a></td>";
        echo "<td>" . htmlspecialchars($row['date_updated']) . "</td>";
        echo "<td>
            <form method='POST' action='' class='d-inline'>
                <input type='hidden' name='record_id' value='" . htmlspecialchars($row['id']) . "'>
                <button type='submit' name='delete-department-btn' class='btn btn-danger'
                    onclick='return confirm(\"Are you sure you want to delete this department?\");'>
                    Delete
                </button>
            </form>
        </td>";
        echo "</tr>";
    }
}
?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Department Modal -->
        <div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addDepartmentModalLabel">Add New Department</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="department_id" class="form-label">Department ID</label>
                                <input type="text" class="form-control" id="department_id" name="department_id" 
                                       placeholder="e.g. DPT_COMPUTERSCIENCE" required>
                                <small class="form-text text-muted">Format: DPT_DEPARTMENTNAME (uppercase, no spaces)</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" name="add-department-btn">Add Department</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php include '../assets/components/footer.php'; ?>
    </div>
</div>

<!-- Scripts -->
<script src="../assets/libs/jquery/dist/jquery.min.js"></script>
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
<script src="../assets/extra-libs/sparkline/sparkline.js"></script>
<script src="../dist/js/waves.js"></script>
<script src="../dist/js/sidebarmenu.js"></script>
<script src="../dist/js/custom.min.js"></script>
<script src="../assets/extra-libs/DataTables/datatables.min.js"></script>

<script>
    // Hide preloader once page is fully loaded
    $(window).on('load', function() {
        $(".preloader").fadeOut();
    });

    $(document).ready(function() {
        $('#dtBasicExample').DataTable();
        $('.dataTables_length').addClass('bs-select');
    });
</script>
<script>
    $(document).ready(function() {
        $('#dtBasicExample').DataTable();
        $('.dataTables_length').addClass('bs-select');
    });
</script>

</body>
</html>