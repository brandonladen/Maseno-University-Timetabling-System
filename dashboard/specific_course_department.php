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

// Get school and department IDs from URL parameters
$school_id = isset($_GET['school_id']) ? $_GET['school_id'] : '';
$department_id = isset($_GET['department_id']) ? $_GET['department_id'] : '';

if (empty($school_id) || empty($department_id)) {
    header("Location: schools.php");
    exit;
}

// Handle course addition
if (isset($_POST['add-course-btn'])) {
    $course_code = mysqli_real_escape_string($db, $_POST['course_code']);
    $course_name = mysqli_real_escape_string($db, $_POST['course_name']);
    $credits = mysqli_real_escape_string($db, $_POST['credits']);

    if (empty($course_code)) {
        array_push($errors, "Course code is required");
    }
    if (empty($course_name)) {
        array_push($errors, "Course name is required");
    }
    
    if (count($errors) == 0) {
        $add_course_query = "INSERT INTO courses (school_id, department_id, course_code, course_name, credits) 
                            VALUES ('$school_id', '$department_id', '$course_code', '$course_name', '$credits')";
        mysqli_query($db, $add_course_query);
        header("Location: courses.php?school_id=$school_id&department_id=$department_id");
    }
}

// Handle course deletion
if (isset($_POST['delete-course-btn'])) {
    $course_id = mysqli_real_escape_string($db, $_POST['course_id']);
    if (!empty($course_id)) {
        $delete_query = "DELETE FROM courses WHERE id = '$course_id'";
        mysqli_query($db, $delete_query);
    }
    header("Location: courses.php?school_id=$school_id&department_id=$department_id");
}
?>

<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    <title>Courses | EDUTIME</title>
    <?php include '../assets/components/header.php'; ?>
</head>

<body>
    <?php include '../assets/components/topbar.php'; ?>
    <?php include '../assets/components/sidebar.php'; ?>

    <div class="preloader">
        <div class="lds-ripple">
            <div class="lds-pos"></div>
            <div class="lds-pos"></div>
        </div>
    </div>

    <div id="main-wrapper" data-layout="vertical" data-navbarbg="skin5" data-sidebartype="full"
        data-sidebar-position="absolute" data-header-position="absolute" data-boxed-layout="full">

        <div class="page-wrapper">
            <div class="page-breadcrumb pt-5">
                <div class="row">
                    <div class="col-12 d-flex no-block align-items-center">
                        <h4 class="page-title">Courses - <?php echo htmlspecialchars($department_id); ?></h4>
                        <div class="ms-auto text-end">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                                    <li class="breadcrumb-item"><a href="schools.php">Schools</a></li>
                                    <li class="breadcrumb-item"><a href="departments.php?id=<?php echo htmlspecialchars($school_id); ?>">Departments</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Courses</li>
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
                                <h5 class="card-title">Courses List</h5>
                                <button type="button" class="btn btn-primary float-end m-2" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                                    Add Course
                                </button>
                                <table id="dtBasicExample" class="table table-striped table-bordered table-sm" cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>Course Code</th>
                                            <th>Course Name</th>
                                            <th>Credits</th>
                                            <th>Date Updated</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $courses_query = "SELECT * FROM courses WHERE school_id = '$school_id' AND department_id = '$department_id'";
                                        $courses_result = mysqli_query($db, $courses_query);
                                        
                                        if ($courses_result && mysqli_num_rows($courses_result) > 0) {
                                            while ($row = mysqli_fetch_assoc($courses_result)) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['course_code']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['course_name']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['credits']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['date_updated']) . "</td>";
                                                echo "<td>
                                                    <form method='POST' action='' class='d-inline'>
                                                        <input type='hidden' name='course_id' value='" . htmlspecialchars($row['id']) . "'>
                                                        <button type='submit' name='delete-course-btn' class='btn btn-danger'
                                                            onclick='return confirm(\"Are you sure you want to delete this course?\");'>
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

            <!-- Add Course Modal -->
            <div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addCourseModalLabel">Add New Course</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="course_code" class="form-label">Course Code</label>
                                    <input type="text" class="form-control" id="course_code" name="course_code" 
                                           placeholder="e.g. CS101" required>
                                </div>
                                <div class="mb-3">
                                    <label for="course_name" class="form-label">Course Name</label>
                                    <input type="text" class="form-control" id="course_name" name="course_name" 
                                           placeholder="e.g. Introduction to Programming" required>
                                </div>
                                <div class="mb-3">
                                    <label for="credits" class="form-label">Credits</label>
                                    <input type="number" class="form-control" id="credits" name="credits" 
                                           placeholder="e.g. 3" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" name="add-course-btn">Add Course</button>
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
        $(window).on('load', function() {
            $(".preloader").fadeOut();
        });

        $(document).ready(function() {
            $('#dtBasicExample').DataTable();
            $('.dataTables_length').addClass('bs-select');
        });
    </script>
</body>
</html>