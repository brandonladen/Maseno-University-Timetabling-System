<?php
include '../server.php';

if (!isset($_SESSION['role_id']) || empty($_SESSION['role_id'])) {
  // if the session variable 'role_id' is not set or is empty, destroy the session and redirect to the login page
  session_destroy();
  header("location: ../index.php"); // replace 'login.php' with the URL of your login page
  exit;
}


// Query to count schools
$schools_count_query= "SELECT COUNT(*) as total_schools FROM `school_details`";
$stmt = $db->prepare($schools_count_query);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_schools = $row['total_schools'];

//Query to count departments
$departments_count_query= "SELECT COUNT(*) as total_departments FROM `department_details`";
$stmt = $db->prepare($departments_count_query);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_departments= $row['total_departments'];



//Query to count courses
$courses_count_query= "SELECT COUNT(*) as total_courses FROM `course_details`";
$stmt = $db->prepare($courses_count_query);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_courses = $row['total_courses'];


//Query to count units
$units_count_query= "SELECT COUNT(*) as total_units FROM `unit_details`";
$stmt = $db->prepare($units_count_query);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_units = $row['total_units'];


//Query to count academic year
$academic_year_count_query= "SELECT COUNT(*) as total_academic_year FROM `academic_year`";
$stmt = $db->prepare($academic_year_count_query);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_academic_year = $row['total_academic_year'];

//Query to count semesters
$semester_count_query= "SELECT COUNT(*) as total_semesters FROM `semester_details`";
$stmt = $db->prepare($semester_count_query);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_semesters = $row['total_semesters'];


//Query to count rooms
$rooms_count_query= "SELECT COUNT(*) as total_rooms FROM `room_details`";
$stmt = $db->prepare($rooms_count_query);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_rooms = $row['total_rooms'];


//Query to count course groups
$course_groups_count_query= "SELECT COUNT(*) as total_course_groups FROM `course_group_details`";
$stmt = $db->prepare($course_groups_count_query);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_course_groups = $row['total_course_groups'];


//Query to count lecturers
$lecturers_count_query= "SELECT COUNT(*) AS total_lecturers 
FROM user_role_details 
WHERE role_id = 'role002';";
$stmt = $db->prepare($lecturers_count_query);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_lecturers = $row['total_lecturers'];


// Query to count admins
$admins_count_query= "SELECT COUNT(*) AS total_admins
FROM user_role_details 
WHERE role_id = 'role001';";
$stmt = $db->prepare($admins_count_query);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_admins = $row['total_admins'];

//Query to count reports
$reports_count_query= "SELECT COUNT(*) as total_reports FROM `report_details`";



$name = $_SESSION['salutation'] . " ".$_SESSION['lname'];
$role_name = $_SESSION['role_name'];
$mail = $_SESSION['email'];
?>

<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <title>Dashboard | EDUTIME </title>
    <?php
include '../assets/components/header.php';
?>

</head>

<body>
    <!-- ============================================================== -->
    <!-- Topbar header - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <?php
     include '../assets/components/topbar.php';
     ?>
    <!-- ============================================================== -->
    <!-- End Topbar header -->
    <!-- ============================================================== -->


    <!-- ============================================================== -->
    <!-- Left Sidebar - style you can find in sidebar.scss  -->
    <!-- ============================================================== -->
    <?php
     include '../assets/components/sidebar.php';
     ?>
    <!-- ============================================================== -->
    <!-- End Left Sidebar - style you can find in sidebar.scss  -->
    <!-- ============================================================== -->



    <!-- ============================================================== -->
    <!-- Page wrapper  -->
    <!-- ============================================================== -->
    <div class="page-wrapper">
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="page-breadcrumb">
            <div class="row">
                <div class="col-12 d-flex no-block align-items-center">
                    <h4 class="page-title">Dashboard</h4>
                    <div class="ms-auto text-end">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item p-4"><a href="#">Home</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Container fluid  -->
        <!-- ============================================================== -->
        <div class="container-fluid">
            <!-- ============================================================== -->
            <!-- Sales Cards  -->
            <!-- ============================================================== -->
            <div class="row p-3">
                <?php
    if ($_SESSION['role_name'] === 'Chairperson' || $_SESSION['role_name'] === 'Lecturer' || $_SESSION['role_name'] === 'Dean'){
    // display the HTML code if the session variable 'role_name' is set to 'Admin'
    ?>
                <div class="col-md-4">
                    <a href="./department-courses.php">
                        <div class="card card-hover">
                            <div class="box bg-info text-center">
                                <h1 class="font-light text-white">
                                    <i class="mdi mdi-school"></i>
                                </h1>
                                <h6 class="text-white">Courses</h6>
                            </div>
                        </div>
                    </a>
                </div>
                <?php
    }
    ?>
                <?php
    if ($_SESSION['role_name'] === 'Chairperson'){
    // display the HTML code if the session variable 'role_name' is set to 'Admin'
    ?>
                <div class="col-md-4">
                    <a href="./department-units.php">
                        <div class="card card-hover">
                            <div class="box bg-primary text-center">
                                <h1 class="font-light text-white">
                                    <i class="mdi mdi-vector-square"></i>
                                </h1>
                                <h6 class="text-white">Units</h6>
                            </div>
                        </div>
                    </a>
                </div>

                <?php
    }
    ?>

                <?php
    if ($_SESSION['role_name'] === 'Chairperson' || $_SESSION['role_name'] === 'Lecturer' || $_SESSION['role_name'] === 'Dean'){
    // display the HTML code if the session variable 'role_name' is set to 'Admin'
    ?>
                <div class="col-md-4">
                    <a href="./select-units.php">
                        <div class="card card-hover">
                            <div class="box bg-success text-center">
                                <h1 class="font-light text-white">
                                    <i class="mdi mdi-checkbox-marked"></i>
                                </h1>
                                <h6 class="text-white">Select Units</h6>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="./selected-units.php">
                        <div class="card card-hover">
                            <div class="box bg-primary text-center">
                                <h1 class="font-light text-white">
                                    <i class="mdi mdi-book"></i>
                                </h1>
                                <h6 class="text-white">Selected Units</h6>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="./schedules.php">
                        <div class="card card-hover">
                            <div class="box bg-secondary text-center">
                                <h1 class="font-light text-white">
                                    <i class="fa fa-file-alt"></i>
                                </h1>
                                <h6 class="text-white">Reports</h6>
                            </div>
                        </div>
                    </a>
                </div>

                <?php
    }
    ?>

                <!-- Column -->
                <?php
    if ($_SESSION['role_name'] === 'Admin'){
    // display the HTML code if the session variable 'role_name' is set to 'Admin'
    ?>
                <div class="col-md-4">
                    <a href="./schools.php">
                        <div class="card card-hover">
                            <div class="box bg-cyan text-center">
                                <h1 class="font-light text-white">
                                    <i class="mdi mdi-book-open-page-variant"></i>
                                </h1>
                                <h6 class="text-white">Schools</h6>
                                <span class="text-white" style="font-size: 24px;"><?php echo $total_schools; ?></span>
                            </div>
                            
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="./departments.php">
                        <div class="card card-hover">
                            <div class="box bg-primary text-center">
                                <h1 class="font-light text-white">
                                    <i class="mdi mdi-folder-multiple"></i>
                                </h1>
                                <h6 class="text-white">Departments</h6>
                                <span class="text-white" style="font-size: 24px;"><?php echo $total_departments; ?></span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="./courses.php">
                        <div class="card card-hover">
                            <div class="box bg-info text-center">
                                <h1 class="font-light text-white">
                                    <i class="mdi mdi-school"></i>
                                </h1>
                                <h6 class="text-white">Courses</h6>
                                <span class="text-white" style="font-size: 24px;"><?php echo $total_courses; ?></span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="./units.php">
                        <div class="card card-hover">
                            <div class="box bg-secondary text-center">
                                <h1 class="font-light text-white">
                                    <i class="mdi mdi-vector-square"></i>
                                </h1>
                                <h6 class="text-white">Units</h6>
                                <span class="text-white" style="font-size: 24px;"><?php echo $total_units ; ?></span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="./academic-year.php">
                        <div class="card card-hover">
                            <div class="box bg-cyan text-center">
                                <h1 class="font-light text-white">
                                    <i class="mdi mdi-calendar-range"></i>
                                </h1>
                                <h6 class="text-white">Academic Year</h6>
                                <span class="text-white" style="font-size: 24px;"><?php echo $total_academic_year; ?></span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="./semesters.php">
                        <div class="card card-hover">
                            <div class="box bg-orange text-center">
                                <h1 class="font-light text-white">
                                    <i class="mdi mdi-timetable"></i>
                                </h1>
                                <h6 class="text-white">Semesters</h6>
                                <span class="text-white" style="font-size: 24px;"><?php echo $total_semesters ; ?></span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="./rooms.php">
                        <div class="card card-hover">
                            <div class="box bg-primary text-center">
                                <h1 class="font-light text-white">
                                    <i class="mdi mdi-window-open"></i>
                                </h1>
                                <h6 class="text-white">Rooms</h6>
                                <span class="text-white" style="font-size: 24px;"><?php echo $total_rooms; ?></span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="./course-groups.php">
                        <div class="card card-hover">
                            <div class="box bg-info text-center">
                                <h1 class="font-light text-white">
                                    <i class="mdi mdi-book-multiple"></i>
                                </h1>
                                <h6 class="text-white">Course Groups</h6>
                                <span class="text-white" style="font-size: 24px;"><?php echo $total_course_groups; ?></span>
                            </div>
                        </div>
                    </a>
                </div>


                <div class="col-md-4">
                    <a href="./users.php">
                        <div class="card card-hover">
                            <div class="box bg-success text-center">
                                <h1 class="font-light text-white">
                                    <i class="mdi mdi-account"></i>
                                </h1>
                                <h6 class="text-light">Lecturers</h6>
                                <span class="text-white" style="font-size: 24px;"><?php echo $total_lecturers; ?></span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="./admins.php">
                        <div class="card card-hover">
                            <div class="box bg-cyan text-center">
                                <h1 class="font-light text-white">
                                    <i class="mdi mdi-account-key"></i>
                                </h1>
                                <h6 class="text-light">Admins</h6>
                                <span class="text-white" style="font-size: 24px;"><?php echo $total_admins; ?></span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="./timetable.php">
                        <div class="card card-hover">
                            <div class="box bg-success text-center">
                                <h1 class="font-light text-white">
                                    <i class="mdi mdi-calendar-clock"></i>
                                </h1>
                                <h6 class="text-light">Generate Timetable</h6>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="./reports.php">
                        <div class="card card-hover">
                            <div class="box bg-secondary text-center">
                                <h1 class="font-light text-white">
                                    <i class="fa fa-file-alt"></i>
                                </h1>
                                <h6 class="text-white">Reports</h6>
                                <span class="text-white" style="font-size: 24px;"></span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Column -->


                <?php
}
?>

            </div>

            <!-- ============================================================== -->
            <!-- footer -->
            <!-- ============================================================== -->
            <?php
    include '../assets/components/footer.php';
    ?>
            <!-- ============================================================== -->
            <!-- End footer -->
            <!-- ============================================================== -->
            <!-- </div> -->
            <!-- ============================================================== -->
            <!-- End Page wrapper  -->
            <!-- ============================================================== -->
        </div>
        <!-- ============================================================== -->
        <!-- End Wrapper -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- All Jquery -->
        <!-- ============================================================== -->
        <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
        <!-- Bootstrap tether Core JavaScript -->
        <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
        <script src="../assets/extra-libs/sparkline/sparkline.js"></script>
        <!--Wave Effects -->
        <script src="../dist/js/waves.js"></script>
        <!--Menu sidebar -->
        <script src="../dist/js/sidebarmenu.js"></script>
        <!--Custom JavaScript -->
        <script src="../dist/js/custom.min.js"></script>
        <!--This page JavaScript -->
        <!-- <script src="../dist/js/pages/dashboards/dashboard1.js"></script> -->
        <!-- Charts js Files -->
        <script src="../assets/libs/flot/excanvas.js"></script>
        <script src="../assets/libs/flot/jquery.flot.js"></script>
        <script src="../assets/libs/flot/jquery.flot.pie.js"></script>
        <script src="../assets/libs/flot/jquery.flot.time.js"></script>
        <script src="../assets/libs/flot/jquery.flot.stack.js"></script>
        <script src="../assets/libs/flot/jquery.flot.crosshair.js"></script>
        <script src="../assets/libs/flot.tooltip/js/jquery.flot.tooltip.min.js"></script>
        <script src="../dist/js/pages/chart/chart-page-init.js"></script>
</body>

</html>