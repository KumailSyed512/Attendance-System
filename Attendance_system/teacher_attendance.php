<?php
include 'config.php';

// Check if teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teachers_login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];
$teacher_class_id = $_SESSION['teacher_class_id'];

// Get teacher's class information
$stmt = $conn->prepare("SELECT class_name FROM classes WHERE id = ?");
$stmt->execute([$teacher_class_id]);
$class_info = $stmt->fetch(PDO::FETCH_ASSOC);
$class_name = $class_info ? $class_info['class_name'] : 'No Class Assigned';

// Get attendance records for teacher's class
$search_date = isset($_GET['date']) ? $_GET['date'] : '';
$search_student = isset($_GET['student']) ? $_GET['student'] : '';

$query = "SELECT a.*, s.student_name, s.roll_number 
          FROM attendance a 
          JOIN students s ON a.student_id = s.id 
          WHERE a.teacher_id = ? AND a.class_id = ?";
$params = [$teacher_id, $teacher_class_id];

if ($search_date) {
    $query .= " AND a.attendance_date = ?";
    $params[] = $search_date;
}

if ($search_student) {
    $query .= " AND (s.student_name LIKE ? OR s.roll_number LIKE ?)";
    $params[] = "%$search_student%";
    $params[] = "%$search_student%";
}

$query .= " ORDER BY a.attendance_date DESC, s.student_name";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get students in teacher's class for attendance summary
$stmt = $conn->prepare("SELECT s.*, 
    COUNT(a.id) as total_days,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days
    FROM students s 
    LEFT JOIN attendance a ON s.id = a.student_id 
    WHERE s.class_id = ? 
    GROUP BY s.id 
    ORDER BY s.student_name");
$stmt->execute([$teacher_class_id]);
$students_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Records - Teacher Dashboard</title>

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
        .present-badge {
            background-color: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .absent-badge {
            background-color: #f8d7da;
            color: #721c24;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .attendance-percentage {
            font-weight: bold;
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
                        <a class="nav-link" href="teacher_profile.php">
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
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Attendance Management - <?php echo $class_name; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <a href="mark_attendance.php" class="btn btn-success">
                                    <i class="fas fa-plus me-2"></i>Mark Today's Attendance
                                </a>
                            </div>
                            <div class="col-md-6">
                                <form method="GET" class="d-flex">
                                    <input type="date" name="date" class="form-control me-2" value="<?php echo $search_date; ?>">
                                    <input type="text" name="student" class="form-control me-2" placeholder="Search student..." value="<?php echo $search_student; ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if ($search_date || $search_student): ?>
                                        <a href="teacher_attendance.php" class="btn btn-secondary ms-2">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Summary -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Students Attendance Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Roll Number</th>
                                        <th>Total Days</th>
                                        <th>Present Days</th>
                                        <th>Attendance %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students_summary as $student): ?>
                                        <?php 
                                        $percentage = $student['total_days'] > 0 ? 
                                            round(($student['present_days'] / $student['total_days']) * 100, 2) : 0;
                                        ?>
                                        <tr>
                                            <td><?php echo $student['student_name']; ?></td>
                                            <td><?php echo $student['roll_number']; ?></td>
                                            <td><?php echo $student['total_days']; ?></td>
                                            <td><?php echo $student['present_days']; ?></td>
                                            <td>
                                                <span class="attendance-percentage 
                                                    <?php echo $percentage >= 75 ? 'text-success' : ($percentage >= 50 ? 'text-warning' : 'text-danger'); ?>">
                                                    <?php echo $percentage; ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Records -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Attendance Records</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($attendance_records)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Student Name</th>
                                            <th>Roll Number</th>
                                            <th>Status</th>
                                            <th>Marked At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendance_records as $record): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($record['attendance_date'])); ?></td>
                                                <td><?php echo $record['student_name']; ?></td>
                                                <td><?php echo $record['roll_number']; ?></td>
                                                <td>
                                                    <span class="<?php echo $record['status'] == 'present' ? 'present-badge' : 'absent-badge'; ?>">
                                                        <?php echo ucfirst($record['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('g:i A', strtotime($record['marked_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                <h5>No attendance records found</h5>
                                <p>Start by marking attendance for your class.</p>
                                <a href="mark_attendance.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Mark Attendance
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>