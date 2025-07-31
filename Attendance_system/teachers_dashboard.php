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

// Get students count in teacher's class
$stmt = $conn->prepare("SELECT COUNT(*) as student_count FROM students WHERE class_id = ?");
$stmt->execute([$teacher_class_id]);
$student_count = $stmt->fetch(PDO::FETCH_ASSOC)['student_count'];

// Get today's attendance summary
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total_marked,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
    FROM attendance 
    WHERE teacher_id = ? AND class_id = ? AND attendance_date = ?");
$stmt->execute([$teacher_id, $teacher_class_id, $today]);
$today_attendance = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent attendance records
$stmt = $conn->prepare("SELECT 
    a.attendance_date,
    a.status,
    s.student_name,
    s.roll_number
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE a.teacher_id = ? AND a.class_id = ?
    ORDER BY a.attendance_date DESC, s.student_name
    LIMIT 10");
$stmt->execute([$teacher_id, $teacher_class_id]);
$recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Attendance System</title>
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
            transform: translateY(-1px);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stats-card .card-body {
            padding: 25px;
        }
        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .welcome-card {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white;
        }
        .attendance-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .present-badge {
            background-color: #d4edda;
            color: #155724;
        }
        .absent-badge {
            background-color: #f8d7da;
            color: #721c24;
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
                        <a class="nav-link active" href="teachers_dashboard.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="teacher_profile.php">
                            <i class="fas fa-user me-1"></i>Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="teacher_attendance.php">
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
                            <li><a class="dropdown-item" href="teacher_profile.php">
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
        <!-- Welcome Card -->
        <div class="card welcome-card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2><i class="fas fa-hand-wave me-2"></i>Welcome, <?php echo $teacher_name; ?>!</h2>
                        <p class="mb-0">Managing: <strong><?php echo $class_name; ?></strong></p>
                        <small>Today is <?php echo date('l, F j, Y'); ?></small>
                    </div>
                    <div class="col-md-4 text-end">
                        <i class="fas fa-chalkboard-teacher" style="font-size: 4rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users stats-icon"></i>
                        <h3 class="mt-2"><?php echo $student_count; ?></h3>
                        <p class="mb-0">Total Students</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check stats-icon"></i>
                        <h3 class="mt-2"><?php echo $today_attendance['total_marked'] ?? 0; ?></h3>
                        <p class="mb-0">Today's Marked</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-check stats-icon"></i>
                        <h3 class="mt-2"><?php echo $today_attendance['present_count'] ?? 0; ?></h3>
                        <p class="mb-0">Present Today</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-times stats-icon"></i>
                        <h3 class="mt-2"><?php echo $today_attendance['absent_count'] ?? 0; ?></h3>
                        <p class="mb-0">Absent Today</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Attendance -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Attendance</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_attendance)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Student</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_attendance as $record): ?>
                                            <tr>
                                                <td><?php echo date('M j', strtotime($record['attendance_date'])); ?></td>
                                                <td>
                                                    <small><?php echo $record['student_name']; ?><br>
                                                    <span class="text-muted"><?php echo $record['roll_number']; ?></span></small>
                                                </td>
                                                <td>
                                                    <span class="attendance-badge <?php echo $record['status'] == 'present' ? 'present-badge' : 'absent-badge'; ?>">
                                                        <?php echo ucfirst($record['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                <p>No attendance records found</p>
                                <a href="mark_attendance.php" class="btn btn-primary btn-sm">Mark First Attendance</a>
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