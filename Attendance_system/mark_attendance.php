<?php
include 'config.php';

// Check if teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teachers_login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];

$success = '';
$error = '';

// Get teacher's assigned class (simplified approach)
$stmt = $conn->prepare("SELECT t.class_id, c.class_name 
                       FROM teachers t 
                       LEFT JOIN classes c ON t.class_id = c.id 
                       WHERE t.id = ?");
$stmt->execute([$teacher_id]);
$teacher_class = $stmt->fetch(PDO::FETCH_ASSOC);

$selected_class_id = $teacher_class['class_id'] ?? null;
$selected_class_name = $teacher_class['class_name'] ?? 'No Class Assigned';

// Process attendance submission
if ($_POST && isset($_POST['submit_attendance'])) {
    $attendance_date = $_POST['attendance_date'];
    $attendance_data = $_POST['attendance'] ?? [];
    
    if (!$selected_class_id) {
        $error = "You are not assigned to any class!";
    } else {
        // Get all students in the class to ensure we process everyone
        $stmt = $conn->prepare("SELECT id FROM students WHERE class_id = ?");
        $stmt->execute([$selected_class_id]);
        $all_students = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($all_students)) {
            $error = "No students found in your class!";
        } else {
            try {
                $conn->beginTransaction();
                
                // Delete existing attendance for the same date (if any)
                $stmt = $conn->prepare("DELETE FROM attendance WHERE teacher_id = ? AND class_id = ? AND attendance_date = ?");
                $stmt->execute([$teacher_id, $selected_class_id, $attendance_date]);
                
                // Insert new attendance records
                $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, teacher_id, attendance_date, status) VALUES (?, ?, ?, ?, ?)");
                
                foreach ($all_students as $student_id) {
                    // Check if this student was marked present (checkbox checked)
                    $status = isset($attendance_data[$student_id]) && $attendance_data[$student_id] === 'present' ? 'present' : 'absent';
                    $stmt->execute([$student_id, $selected_class_id, $teacher_id, $attendance_date, $status]);
                }
                
                $conn->commit();
                $success = "Attendance marked successfully for " . date('F j, Y', strtotime($attendance_date)) . "!";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Failed to mark attendance: " . $e->getMessage();
            }
        }
    }
}

// Get students in selected class
$students = [];
if ($selected_class_id) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE class_id = ? ORDER BY student_name");
    $stmt->execute([$selected_class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get today's date for default
$today = date('Y-m-d');

// Check if attendance already exists for today
$selected_date = $_POST['attendance_date'] ?? $today;
$existing_attendance = [];
if ($selected_class_id) {
    $stmt = $conn->prepare("SELECT student_id, status FROM attendance WHERE teacher_id = ? AND class_id = ? AND attendance_date = ?");
    $stmt->execute([$teacher_id, $selected_class_id, $selected_date]);
    $existing_attendance = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Teacher Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

        <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand, .nav-link {
            color: white !important;
            font-weight: 500;
        }
        .nav-link:hover {
            color: #f8f9fa !important;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .student-card {
            transition: transform 0.2s;
            border: 2px solid #e9ecef;
        }
        .student-card:hover {
            transform: translateY(-2px);
        }
        .attendance-toggle {
            width: 100px;
            height: 30px;
        }
        .form-check-input:checked {
            background-color: #28a745;
            border-color: #28a745;
        }
        .present-card {
            border-color: #28a745 !important;
            background-color: #f8fff9;
        }
        .absent-card {
            border-color: #dc3545 !important;
            background-color: #fff8f8;
        }
        .class-selector {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chalkboard-teacher me-2"></i>Teacher Dashboard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="teachers_dashboard.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="teachers_profile.php">
                            <i class="fas fa-user me-1"></i>Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="teacher_attendance.php">
                            <i class="fas fa-clipboard-check me-1"></i>Attendance
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo $teacher_name; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="teachers_profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="teachers_logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>Mark Attendance
                            <?php if ($selected_class_name && $selected_class_name !== 'No Class Assigned'): ?>
                                - <?php echo $selected_class_name; ?>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="teachers_dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="teacher_attendance.php">Attendance</a></li>
                                <li class="breadcrumb-item active">Mark Attendance</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Check if teacher has a class assigned -->
        <?php if (!$selected_class_id): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5>No Class Assigned</h5>
                    <p class="text-muted">You are not assigned to any class yet. Please contact the administrator.</p>
                    <a href="teachers_dashboard.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        <?php elseif (empty($students)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-user-times fa-3x text-muted mb-3"></i>
                    <h5>No Students Found</h5>
                    <p class="text-muted">There are no students in your assigned class yet.</p>
                    <a href="teachers_dashboard.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Class Information -->
            <div class="card mb-4">
                <div class="card-header class-selector">
                    <h6 class="mb-0">
                        <i class="fas fa-school me-2"></i>Your Assigned Class: <?php echo $selected_class_name; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-muted">
                        <i class="fas fa-users me-2"></i>
                        <?php echo count($students); ?> students in this class
                    </div>
                </div>
            </div>

            <form method="POST" id="attendanceForm">
                <!-- Date Selection -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <label for="attendance_date" class="form-label">
                                    <i class="fas fa-calendar me-2"></i>Select Date
                                </label>
                                <input type="date" class="form-control" id="attendance_date" name="attendance_date" 
                                       value="<?php echo $selected_date; ?>" max="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="button" class="btn btn-success me-2" onclick="markAllPresent()">
                                    <i class="fas fa-check-double me-2"></i>Mark All Present
                                </button>
                                <button type="button" class="btn btn-danger" onclick="markAllAbsent()">
                                    <i class="fas fa-times me-2"></i>Mark All Absent
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students List -->
                <div class="row">
                    <?php foreach ($students as $student): ?>
                        <?php 
                        $current_status = $existing_attendance[$student['id']] ?? 'absent';
                        ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card student-card h-100 <?php echo $current_status == 'present' ? 'present-card' : 'absent-card'; ?>" 
                                 id="student-card-<?php echo $student['id']; ?>">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-user-circle fa-3x text-muted"></i>
                                    </div>
                                    <h6 class="card-title"><?php echo $student['student_name']; ?></h6>
                                    <p class="card-text text-muted">
                                        <small>Roll: <?php echo $student['roll_number']; ?></small>
                                    </p>
                                    
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input attendance-toggle" type="checkbox" 
                                               name="attendance[<?php echo $student['id']; ?>]" 
                                               value="present"
                                               id="attendance-<?php echo $student['id']; ?>"
                                               onchange="updateStudentCard(<?php echo $student['id']; ?>)"
                                               <?php echo $current_status == 'present' ? 'checked' : ''; ?>>
                                        <label class="form-check-label ms-2" for="attendance-<?php echo $student['id']; ?>">
                                            <span class="status-text"><?php echo ucfirst($current_status); ?></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Submit Button -->
                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <button type="submit" name="submit_attendance" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-save me-2"></i>Save Attendance
                        </button>
                        <a href="teacher_attendance.php" class="btn btn-secondary btn-lg px-5 ms-3">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStudentCard(studentId) {
            const checkbox = document.getElementById('attendance-' + studentId);
            const card = document.getElementById('student-card-' + studentId);
            const statusText = card.querySelector('.status-text');
            
            if (checkbox.checked) {
                card.classList.remove('absent-card');
                card.classList.add('present-card');
                statusText.textContent = 'Present';
            } else {
                card.classList.remove('present-card');
                card.classList.add('absent-card');
                statusText.textContent = 'Absent';
            }
        }

        function markAllPresent() {
            const checkboxes = document.querySelectorAll('.attendance-toggle');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
                const studentId = checkbox.id.split('-')[1];
                updateStudentCard(studentId);
            });
        }

        function markAllAbsent() {
            const checkboxes = document.querySelectorAll('.attendance-toggle');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
                const studentId = checkbox.id.split('-')[1];
                updateStudentCard(studentId);
            });
        }

        // Reload page when date changes to show existing attendance
        document.getElementById('attendance_date')?.addEventListener('change', function() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="attendance_date" value="' + this.value + '">';
            document.body.appendChild(form);
            form.submit();
        });
    </script>
</body>
</html>