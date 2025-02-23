<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include '../server.php';

if (!isset($_SESSION['role_id']) || empty($_SESSION['role_id'])) {
  // if the session variable 'role_id' is not set or is empty, destroy the session and redirect to the login page
  session_destroy();
  header("location: ../index.php"); // replace 'login.php' with the URL of your login page
  exit;
}

//deny access to courses.php if user is not an admin
if ($_SESSION['role_name'] !== 'Admin') {
  // if the session variable 'role_name' is not set or does not equal 'Admin', deny access and redirect to a non-privileged page
  header("Location: index.php"); // replace 'index.php' with the URL of a non-privileged page
  exit;
}


//generate timetable class
// First, try to increase PHP execution time limit at runtime
ini_set('max_execution_time', 300); // Set to 5 minutes
ini_set('memory_limit', '256M');    // Increase memory limit if needed

class TimeTableChromosome {
    private $schedule;
    private $fitness;
    private $db;
    private $clashCache; // Add cache to store clash information
    
    
    public function __construct($db) {
        $this->db = $db;
        $this->schedule = array();
        $this->fitness = 0;
        $this->clashCache = null;
    }
    
    // Initialize random schedule
    public function initializeRandom($units, $rooms, $timeslots) {
        foreach ($units as $unit) {
            $day = array_rand($timeslots);
            $time = array_rand($timeslots[$day]);
            $room = $rooms[array_rand($rooms)];
            
            $this->schedule[] = [
                'unit_id' => $unit['unit_id'],
                'unit_type' => $unit['unit_type'],
                'lecturer_id' => $unit['lecturer_id'],
                'group_number' => $unit['group_number'],
                'day' => $day,
                'timeslot' => $timeslots[$day][$time],
                'room_id' => $room['room_id'],
                'room_type' => $room['room_type'],
                'room_capacity' => $room['capacity']
            ];
        }
    }
    
    // Calculate fitness score
    public function calculateFitness() {
        $this->fitness = 100; // Start with perfect score
        $clashes = $this->detectClashes();
        
        // Deduct points for each type of clash
        foreach ($clashes as $clash) {
            switch ($clash['type']) {
                case 'room_double_booking':
                    $this->fitness -= 10;
                    break;
                case 'lecturer_double_booking':
                    $this->fitness -= 10;
                    break;
                case 'room_capacity':
                    $this->fitness -= 5;
                    break;
                case 'room_type_mismatch':
                    $this->fitness -= 5;
                    break;
            }
        }
        
        return $this->fitness;
    }
    
    // Detect different types of clashes
    public function detectClashes() {
        // Return cached clashes if available
        if ($this->clashCache !== null) {
            return $this->clashCache;
        }

        $clashes = array();
        
        // Create indexes for faster lookup
        $roomIndex = [];
        $lecturerIndex = [];
        
        // Build indexes
        foreach ($this->schedule as $i => $slot) {
            $key = $slot['day'] . '_' . $slot['timeslot'] . '_' . $slot['room_id'];
            $lecturerKey = $slot['day'] . '_' . $slot['timeslot'] . '_' . $slot['lecturer_id'];
            
            // Check room double booking using index
            if (isset($roomIndex[$key])) {
                $clashes[] = [
                    'type' => 'room_double_booking',
                    'units' => [$slot['unit_id'], $this->schedule[$roomIndex[$key]]['unit_id']],
                    'room' => $slot['room_id']
                ];
            }
            $roomIndex[$key] = $i;
            
            // Check lecturer double booking using index
            if (isset($lecturerIndex[$lecturerKey])) {
                $clashes[] = [
                    'type' => 'lecturer_double_booking',
                    'lecturer' => $slot['lecturer_id'],
                    'units' => [$slot['unit_id'], $this->schedule[$lecturerIndex[$lecturerKey]]['unit_id']]
                ];
            }
            $lecturerIndex[$lecturerKey] = $i;
            
            // Check room capacity and type (these don't need indexing)
            if ($slot['group_number'] > $slot['room_capacity']) {
                $clashes[] = [
                    'type' => 'room_capacity',
                    'unit' => $slot['unit_id'],
                    'room' => $slot['room_id']
                ];
            }
            
            if (!$this->isRoomTypeCompatible($slot['unit_type'], $slot['room_type'])) {
                $clashes[] = [
                    'type' => 'room_type_mismatch',
                    'unit' => $slot['unit_id'],
                    'room' => $slot['room_id']
                ];
            }
        }
        
        // Cache the results
        $this->clashCache = $clashes;
        return $clashes;
    }
    
    // Invalidate cache when schedule changes
    public function setSchedule($schedule) {
        $this->schedule = $schedule;
        $this->clashCache = null; // Invalidate cache
    }
    
    // Helper function to check room type compatibility
    private function isRoomTypeCompatible($unitType, $roomType) {
        $compatibility = [
            'Theory' => ['Standard'],
            'ICT-Practical' => ['ICT Labaratory'],
            'ELECT-Practical' => ['Electronics LAB']
        ];
        
        return in_array($roomType, $compatibility[$unitType] ?? []);
    }
    
    // Crossover operation
    public function crossover($partner) {
        $child = new TimeTableChromosome($this->db);
        $crossoverPoint = rand(0, count($this->schedule) - 1);
        
        $childSchedule = array_merge(
            array_slice($this->schedule, 0, $crossoverPoint),
            array_slice($partner->getSchedule(), $crossoverPoint)
        );
        
        $child->setSchedule($childSchedule);
        return $child;
    }
    
    // Mutation operation
    public function mutate($rooms, $timeslots, $mutationRate = 0.5) {
        foreach ($this->schedule as &$slot) {
            if (rand(0, 100) / 100 < $mutationRate) {
                // Randomly change either room, day, or timeslot
                $mutation = rand(0, 2);
                switch ($mutation) {
                    case 0: // Change room
                        $newRoom = $rooms[array_rand($rooms)];
                        $slot['room_id'] = $newRoom['room_id'];
                        $slot['room_type'] = $newRoom['room_type'];
                        $slot['room_capacity'] = $newRoom['capacity'];
                        break;
                    case 1: // Change day
                        $newDay = array_rand($timeslots);
                        $slot['day'] = $newDay;
                        break;
                    case 2: // Change timeslot
                        $day = $slot['day'];
                        $newTime = array_rand($timeslots[$day]);
                        $slot['timeslot'] = $timeslots[$day][$newTime];
                        break;
                }
            }
        }
    }
    
    // Getters and setters
    public function getSchedule() {
        return $this->schedule;
    }
    
   
    
    public function getFitness() {
        return $this->fitness;
    }
}

class TimetableGenerator {
    private $db;
    private $populationSize;
    private $generations;
    private $units;
    private $rooms;
    private $timeslots;
    private $maxExecutionTime;
    
    private const ITEMS_PER_PAGE = 10;
    private $currentPage = 1;
    
    public function __construct($db, $populationSize = 700, $generations = 760, $maxExecutionTime = 480) {
        $this->db = $db;
        $this->populationSize = $populationSize;
        $this->generations = $generations;
        $this->maxExecutionTime = $maxExecutionTime;
        $this->initializeData();
    }
    
    private function initializeData() {
        // Initialize timeslots (using existing structure)
        $this->timeslots = [
            'Monday' => ['07:00-09:00', '09:00-11:00', '11:00-13:00', '13:00-15:00', '15:00-17:00', '17:00-19:00'],
            'Tuesday' => ['07:00-09:00', '09:00-11:00', '11:00-13:00', '13:00-15:00', '15:00-17:00', '17:00-19:00'],
            'Wednesday' => ['07:00-09:00', '09:00-11:00', '11:00-13:00', '13:00-15:00', '15:00-17:00', '17:00-19:00'],
            'Thursday' => ['07:00-09:00', '09:00-11:00', '11:00-13:00', '13:00-15:00', '15:00-17:00', '17:00-19:00'],
            'Friday' => ['07:00-09:00', '09:00-11:00', '11:00-13:00', '13:00-15:00', '15:00-17:00', '17:00-19:00']
        ];
        
        // Fetch rooms and units from database (using existing queries)
        $this->fetchRooms();
        $this->fetchUnits();
    }
    
    private function fetchRooms() {
        $query = "SELECT * FROM room_details
                 INNER JOIN room_type_details ON room_type_details.room_type_id = room_details.room_type_id";
        $result = mysqli_query($this->db, $query);
        $this->rooms = [];
        while ($room = mysqli_fetch_assoc($result)) {
            $this->rooms[] = [
                'room_id' => $room['room_id'],
                'room_name' => $room['room_name'],
                'capacity' => $room['room_capacity'],
                'room_type' => $room['room_type']
            ];
        }
    }
    
    private function fetchUnits() {
        // Using existing complex query
        $query = "SELECT DISTINCT unit_details.*, lecturer_unit_details.*, user_details.*, 
                 unit_semester_details.*, semester_details.*, unit_course_details.*, 
                 course_details.*, course_group_details.*
                 FROM unit_details 
                 INNER JOIN lecturer_unit_details ON lecturer_unit_details.unit_id = unit_details.unit_code 
                 INNER JOIN user_details ON user_details.pf_number = lecturer_unit_details.lecturer_id
                 INNER JOIN unit_semester_details ON unit_semester_details.unit_id = lecturer_unit_details.unit_id
                 INNER JOIN semester_details ON semester_details.semester_id = unit_semester_details.semester_id
                 INNER JOIN unit_course_details ON unit_course_details.unit_id = lecturer_unit_details.unit_id
                 INNER JOIN course_details ON course_details.course_id = unit_course_details.course_id
                 INNER JOIN course_group_details ON course_group_details.course_id = course_details.course_id
                 ORDER BY unit_details.unit_code ASC";
        $result = mysqli_query($this->db, $query);
        $this->units = [];
        while ($unit = mysqli_fetch_assoc($result)) {
            $this->units[] = $unit;
        }
    }
    
    public function generateTimetable() {
        $startTime = time();
        
        // Initialize population with smaller size initially
        $population = [];
        $initialPopulationSize = min($this->populationSize, 20); // Start with smaller population
        
        for ($i = 0; $i < $initialPopulationSize; $i++) {
            if (time() - $startTime > $this->maxExecutionTime) {
                throw new RuntimeException("Execution time limit exceeded during initialization");
            }
            
            $chromosome = new TimeTableChromosome($this->db);
            $chromosome->initializeRandom($this->units, $this->rooms, $this->timeslots);
            $chromosome->calculateFitness();
            $population[] = $chromosome;
        }
        
        $bestFitness = 0;
        $noImprovementCount = 0;
        
        // Evolution process with early stopping
        for ($generation = 0; $generation < $this->generations; $generation++) {
            if (time() - $startTime > $this->maxExecutionTime) {
                // Return best solution found so far if time limit is reached
                usort($population, function($a, $b) {
                    return $b->getFitness() - $a->getFitness();
                });
                return $this->saveTimetable($population[0]);
            }
            
            // Sort population by fitness
            usort($population, function($a, $b) {
                return $b->getFitness() - $a->getFitness();
            });
            
            // Check for perfect solution
            if ($population[0]->getFitness() == 100) {
                return $this->saveTimetable($population[0]);
            }
            
            // Early stopping if no improvement
            if ($population[0]->getFitness() <= $bestFitness) {
                $noImprovementCount++;
                if ($noImprovementCount > 10) { // Stop if no improvement for 10 generations
                    return $this->saveTimetable($population[0]);
                }
            } else {
                $bestFitness = $population[0]->getFitness();
                $noImprovementCount = 0;
            }
            
            // Create new population with adaptive size
            $newPopulation = [];
            $currentPopSize = min($this->populationSize, 
                                count($population) + 5); // Gradually increase population
            
            // Keep top 10% of population (elitism)
            $eliteCount = max(1, floor($currentPopSize * 0.1));
            for ($i = 0; $i < $eliteCount; $i++) {
                $newPopulation[] = $population[$i];
            }
            
            // Create rest of new population
            while (count($newPopulation) < $currentPopSize) {
                $parent1 = $this->tournamentSelect($population);
                $parent2 = $this->tournamentSelect($population);
                
                $child = $parent1->crossover($parent2);
                $child->mutate($this->rooms, $this->timeslots);
                $child->calculateFitness();
                
                $newPopulation[] = $child;
            }
            
            $population = $newPopulation;
        }
        
        // Return best solution found
        usort($population, function($a, $b) {
            return $b->getFitness() - $a->getFitness();
        });
        
        return $this->saveTimetable($population[0]);
    }
    
    private function tournamentSelect($population) {
        $tournamentSize = 5;
        $tournament = array_rand($population, $tournamentSize);
        $best = $population[$tournament[0]];
        
        for ($i = 1; $i < $tournamentSize; $i++) {
            if ($population[$tournament[$i]]->getFitness() > $best->getFitness()) {
                $best = $population[$tournament[$i]];
            }
        }
        
        return $best;
    }
    
    private function saveTimetable($chromosome) {
        // Clear existing timetable
        mysqli_query($this->db, "DELETE FROM unit_room_time_day_allocation_details");
        mysqli_query($this->db, "ALTER TABLE unit_room_time_day_allocation_details DROP COLUMN id");
        mysqli_query($this->db, "ALTER TABLE unit_room_time_day_allocation_details ADD id INT AUTO_INCREMENT PRIMARY KEY FIRST");
        
        // Save new timetable
        $schedule = $chromosome->getSchedule();
        $clashes = $chromosome->detectClashes();
        
        // Save to database
        foreach ($schedule as $slot) {
            $query = "INSERT INTO unit_room_time_day_allocation_details 
                     (unit_id, lecturer_id, room_id, time_slot_id, weekday)
                     VALUES (
                         '{$slot['unit_id']}',
                         '{$slot['lecturer_id']}',
                         '{$slot['room_id']}',
                         '{$slot['timeslot']}',
                         '{$slot['day']}'
                     )";
            mysqli_query($this->db, $query);
        }
        
        // Save to CSV
        $fp = fopen('timetable.csv', 'w');
        fputcsv($fp, ['Unit Code', 'Unit Name', 'Lecturer', 'Day', 'Time Slot', 'Room', 'Clashes']);
        
        foreach ($schedule as $slot) {
            $clashInfo = $this->getClashInfo($slot, $clashes);
            fputcsv($fp, [
                $slot['unit_id'],
                $this->getUnitName($slot['unit_id']),
                $this->getLecturerName($slot['lecturer_id']),
                $slot['day'],
                $slot['timeslot'],
                $this->getRoomName($slot['room_id']),
                $clashInfo
            ]);
        }
        fclose($fp);
        
        return [
            'fitness' => $chromosome->getFitness(),
            'schedule' => $schedule,
            'clashes' => $clashes
        ];
    }
    
    private function getClashInfo($slot, $clashes) {
        $clashInfo = [];
        foreach ($clashes as $clash) {
            switch ($clash['type']) {
                case 'room_double_booking':
                    if (in_array($slot['unit_id'], $clash['units'])) {
                        $clashInfo[] = "Room double booked";
                    }
                    break;
                case 'lecturer_double_booking':
                    if (in_array($slot['unit_id'], $clash['units'])) {
                        $clashInfo[] = "Lecturer double booked";
                    }
                    break;
                case 'room_capacity':
                    if ($slot['unit_id'] === $clash['unit']) {
                        $clashInfo[] = "Room capacity exceeded";
                    }
                    break;
                case 'room_type_mismatch':
                    if ($slot['unit_id'] === $clash['unit']) {
                        $clashInfo[] = "Room type mismatch";
                    }
                    break;
            }
        }
        return implode(", ", $clashInfo);
    }
    
    private function getUnitName($unitId) {
        foreach ($this->units as $unit) {
            if ($unit['unit_id'] === $unitId) {
                return $unit['unit_name'];
            }
        }
        return '';
    }
    
    private function getLecturerName($lecturerId) {
        foreach ($this->units as $unit) {
            if ($unit['lecturer_id'] === $lecturerId) {
                return $unit['user_title'] . " " . $unit['user_firstname'] . " " . $unit['user_lastname'];
            }
        }
        return '';
    }
    
    private function getRoomName($roomId) {
        foreach ($this->rooms as $room) {
            if ($room['room_id'] === $roomId) {
                return $room['room_name'];
            }
        }
        return '';
    }


    
    public function displayTimetable() {
        $csvFile = 'timetable.csv';
        
        if (!file_exists($csvFile)) {
            return '<div class="alert alert-danger">No timetable has been generated yet. Click "Generate Timetable" to create one.</div>';
        }
        
        // Read the CSV file and group rows by day
        $timetableData = [];
        if (($handle = fopen($csvFile, "r")) !== false) {
            // Read and discard the header row
            $header = fgetcsv($handle);
            
            while (($data = fgetcsv($handle)) !== false) {
                $day = $data[3];
                $timetableData[$day][] = $data;
            }
            fclose($handle);
        }
        
        // Get the current page and day from URL parameters
        $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $currentDay = isset($_GET['day']) ? $_GET['day'] : 'Monday';
        $itemsPerPage = self::ITEMS_PER_PAGE;
        
        // Start building the HTML output
        $html = '<div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Generated Timetable</h4>
                        <div class="table-responsive">';
        
        // Create Bootstrap tabs for each day
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $html .= '<ul class="nav nav-tabs" role="tablist">';
        foreach ($days as $index => $day) {
            $activeClass = ($day === $currentDay) ? 'active' : '';
            $html .= "<li class='nav-item'>
                        <a class='nav-link $activeClass' data-bs-toggle='tab' href='#$day' role='tab' 
                           onclick=\"window.location.href='?day=$day&page=1'\">$day</a>
                      </li>";
        }
        $html .= '</ul>';
        
        // Create the content for each tab (day)
        $html .= '<div class="tab-content">';
        foreach ($days as $index => $day) {
            $activeClass = ($day === $currentDay) ? 'active' : '';
            $html .= "<div class='tab-pane p-3 $activeClass' id='$day' role='tabpanel'>";
            
            // Get all rows for this day
            $rows = isset($timetableData[$day]) ? $timetableData[$day] : [];
            $totalRows = count($rows);
            $totalPages = max(1, ceil($totalRows / $itemsPerPage));
            
            // Ensure current page is within valid range
            $currentPage = min(max(1, $currentPage), $totalPages);
            
            $offset = ($currentPage - 1) * $itemsPerPage;
            $pagedRows = array_slice($rows, $offset, $itemsPerPage);
            
            // Build the table for the day
            $html .= '<table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Time Slot</th>
                                <th>Unit Code</th>
                                <th>Unit Name</th>
                                <th>Lecturer</th>
                                <th>Room</th>
                                <th>Clashes</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            if (!empty($pagedRows)) {
                usort($pagedRows, function($a, $b) {
                    return strcmp($a[4], $b[4]);
                });
                
                foreach ($pagedRows as $slot) {
                    $clashClass = !empty($slot[6]) ? 'class="table-info"' : '';
                    $html .= "<tr $clashClass>
                                <td>{$slot[4]}</td>
                                <td>{$slot[0]}</td>
                                <td>{$slot[1]}</td>
                                <td>{$slot[2]}</td>
                                <td>{$slot[5]}</td>
                                <td>{$slot[6]}</td>
                              </tr>";
                }
            } else {
                $html .= "<tr><td colspan='6' class='text-center'>No classes scheduled</td></tr>";
            }
            $html .= '</tbody></table>';
            
            // Add pagination if there is more than one page
            if ($totalPages > 1) {
                $html .= '<nav aria-label="Timetable pagination">
                            <ul class="pagination justify-content-center">';
                
                // Previous button
                $prevDisabled = ($currentPage <= 1) ? 'disabled' : '';
                $prevPage = $currentPage - 1;
                $html .= "<li class='page-item $prevDisabled'>
                            <a class='page-link' href='?day=$day&page=$prevPage' aria-label='Previous'>
                                <span aria-hidden='true'>Previous</span>
                                <span class='visually-hidden'>Previous</span>
                            </a>
                         </li>";
                
                // Page numbers
                $startPage = max(1, $currentPage - 2);
                $endPage = min($startPage + 4, $totalPages);
                
                // Adjust start page if we're near the end
                if ($endPage - $startPage < 4) {
                    $startPage = max(1, $endPage - 4);
                }
                
                // First page and ellipsis if needed
                if ($startPage > 1) {
                    $html .= "<li class='page-item'><a class='page-link' href='?day=$day&page=1'>1</a></li>";
                    if ($startPage > 2) {
                        $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
                    }
                }
                
                // Page numbers
                for ($i = $startPage; $i <= $endPage; $i++) {
                    $activeClass = ($i == $currentPage) ? 'active' : '';
                    $html .= "<li class='page-item $activeClass'>
                                <a class='page-link' href='?day=$day&page=$i'>$i</a>
                             </li>";
                }
                
                // Last page and ellipsis if needed
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
                    }
                    $html .= "<li class='page-item'>
                                <a class='page-link' href='?day=$day&page=$totalPages'>$totalPages</a>
                             </li>";
                }
                
                // Next button
                $nextDisabled = ($currentPage >= $totalPages) ? 'disabled' : '';
                $nextPage = $currentPage + 1;
                $html .= "<li class='page-item $nextDisabled'>
                            <a class='page-link' href='?day=$day&page=$nextPage' aria-label='Next'>
                                <span aria-hidden='true'>Next</span>
                                <span class='visually-hidden'>Next</span>
                            </a>
                         </li>";
                
                $html .= '</ul></nav>';
                
                // Add page info
                $html .= "<div class='text-center mt-2'>
                            <small class='text-muted'>
                                Page $currentPage of $totalPages 
                                (showing " . count($pagedRows) . " of $totalRows entries)
                            </small>
                         </div>";
            }
            
            $html .= '</div>'; // End of tab-pane
        }
        $html .= '</div>'; // End of tab-content
        $html .= '</div></div>'; // End of card-body and card
        
        return $html;
    }
    

}


function displayAlert($type, $message) {
    return "<div class='alert alert-$type alert-dismissible fade show'>
                $message
                <button type='button' class='btn-close btn-close-danger' data-bs-dismiss='alert' style='background-color: #dc3545; opacity: 1;'></button>
            </div>";
}



if (isset($_POST['generate-timetable-btn'])) {
    // Start session to store generation status
    session_start();
    $_SESSION['generation_status'] = 'running';
    $_SESSION['generation_start_time'] = time();
    
    try {
        // Set script timeout
        set_time_limit(300); // 5 minutes
        
        // Create generator with custom parameters
        $generator = new TimetableGenerator(
            $db,
            populationSize: 30,
            generations: 50,
            maxExecutionTime: 240
        );
        
        $result = $generator->generateTimetable();
        
        $_SESSION['timetable_generation'] = [
            'success' => true,
            'fitness' => $result['fitness'],
            'clash_count' => count($result['clashes']),
            'generation_time' => time() - $_SESSION['generation_start_time']
        ];
        
        if (!empty($result['clashes'])) {
            $_SESSION['timetable_clashes'] = $result['clashes'];
        }
        
    } catch (Exception $e) {
        $_SESSION['timetable_generation'] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    
    $_SESSION['generation_status'] = 'completed';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <title>Timetables | EDUTIME</title>
    <?php
include '../assets/components/header.php';
$generator = new TimetableGenerator($db);

?>
</head>

<body>
    



<div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="modal-title mb-3">Generating Timetable</h5>
                <p class="mb-0">This may take a few minutes. Please don't close this window.</p>
                <div class="progress mt-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
                <p class="mt-2 mb-0" id="timeElapsed">Time elapsed: 0 seconds</p>
            </div>
        </div>
    </div>
</div>


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
        <div class="page-breadcrumb pt-5">
            <div class="row">
                <div class="col-12 d-flex no-block align-items-center">
                    <h4 class="page-title">Timetable Details</h4>
                    <div class="ms-auto text-end">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    Timetables
                                </li>


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
            <!-- Start Page Content -->
            <!-- ============================================================== -->
            <div class="row">
                <div class="col-md-12">
                    <!-- Action Buttons Card -->
                    <div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <form method="POST" action="" class="me-3" id="generateForm">
                <button type="submit" class="btn btn-primary btn-lg" name="generate-timetable-btn">
                    <i class="fa fa-refresh me-2"></i>Generate Timetable
                </button>
            </form>
            
            <a href="timetable.csv" download class="btn btn-success btn-lg">
                <i class="fa fa-download me-2"></i>Download Timetable
            </a>
        </div>


        

                    <!-- Status Messages Container -->
        <div id="statusMessages" class="mt-3">
            <?php if (isset($_SESSION['timetable_generation'])): ?>
                <?php
                $status = $_SESSION['timetable_generation'];
                if ($status['success']) {
                    $message = "<strong>Success!</strong> Timetable generated successfully.<br>";
                    $message .= "Fitness Score: {$status['fitness']}<br>";
                    $message .= "Number of Clashes: {$status['clash_count']}<br>";
                    $message .= "Generation Time: {$status['generation_time']} seconds";
                    echo displayAlert('success', $message);
                } else {
                    echo displayAlert('danger', "<strong>Error!</strong> {$status['error']}");
                }
                unset($_SESSION['timetable_generation']);
                ?>
            <?php endif; ?>
        </div>
                    <!-- Timetable Display -->
                    <div class="mt-4 text-center">
                        <?php echo $generator->displayTimetable(); ?>
                    </div>
                </div>
            </div>
                
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Container fluid  -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- footer -->
        <!-- ============================================================== -->
        <?php
    include '../assets/components/footer.php';
    ?>
        <!-- ============================================================== -->
        <!-- End footer -->
        <!-- ============================================================== -->
    </div>
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
    <!-- slimscrollbar scrollbar JavaScript -->
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../assets/extra-libs/sparkline/sparkline.js"></script>
    <!--Wave Effects -->
    <script src="../dist/js/waves.js"></script>
    <!--Menu sidebar -->
    <script src="../dist/js/sidebarmenu.js"></script>
    <!--Custom JavaScript -->
    <script src="../dist/js/custom.min.js"></script>
    <!-- this page js -->
    <script src="../assets/extra-libs/multicheck/datatable-checkbox-init.js"></script>
    <script src="../assets/extra-libs/multicheck/jquery.multicheck.js"></script>
    <script src="../assets/extra-libs/DataTables/datatables.min.js"></script>


    <script>
let loadingModal;
let startTime;
let progressInterval;
let timeInterval;

document.addEventListener('DOMContentLoaded', function() {
    loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    
    // Handle form submission
    document.getElementById('generateForm').addEventListener('submit', function(e) {
        startLoading();
    });
    
    // Check if generation is running on page load
    <?php if (isset($_SESSION['generation_status']) && $_SESSION['generation_status'] === 'running'): ?>
    startLoading();
    <?php endif; ?>
});

function startLoading() {
    loadingModal.show();
    startTime = Date.now();
    
    // Update progress bar
    let progress = 0;
    progressInterval = setInterval(() => {
        progress += 0.5;
        if (progress <= 100) {
            document.querySelector('.progress-bar').style.width = progress + '%';
        }
    }, 1200); // Adjusted to complete in about 4 minutes
    
    // Update elapsed time
    timeInterval = setInterval(updateTimeElapsed, 1000);
}

function updateTimeElapsed() {
    const elapsed = Math.floor((Date.now() - startTime) / 1000);
    document.getElementById('timeElapsed').textContent = `Time elapsed: ${elapsed} seconds`;
}

// Clean up intervals when the modal is hidden
document.getElementById('loadingModal').addEventListener('hidden.bs.modal', function () {
    clearInterval(progressInterval);
    clearInterval(timeInterval);
});
</script>

</body>

</html>