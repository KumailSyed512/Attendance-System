<?php
require_once 'config.php';

if (!isset($_SESSION['student_logged_in'])) {
    header("Location: students_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$page = isset($_GET['page']) ? $_GET['page'] : 'attendance';

// Get student information
$stmt = $conn->prepare("SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password === $confirm_password) {
        $stmt = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
        $stmt->execute([$new_password, $student_id]);
        $success = "Password changed successfully!";
        
        // Update session
        $_SESSION['student_password'] = $new_password;
    } else {
        $error = "Passwords do not match!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
     <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Student Dashboard</a>
            <span class="navbar-text me-3">Welcome, <?php echo $_SESSION['student_name']; ?></span>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'attendance' ? 'active' : ''; ?>" href="?page=attendance">My Attendance</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'profile' ? 'active' : ''; ?>" href="?page=profile">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="students_logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($page == 'attendance'): ?>
            <!-- Attendance Page -->
            <div class="row">
                <div class="col-md-8">
                    <h2>My Attendance Record</h2>
                    <div class="card">
                        <div class="card-header">
                            <h5>Attendance Summary</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $attendance_percentage = calculateAttendancePercentage($student_id, $conn);
                            $stmt = $conn->prepare("SELECT COUNT(*) as total_days FROM attendance WHERE student_id = ?");
                            $stmt->execute([$student_id]);
                            $total_days = $stmt->fetch(PDO::FETCH_ASSOC)['total_days'];
                            
                            $stmt = $conn->prepare("SELECT COUNT(*) as present_days FROM attendance WHERE student_id = ? AND status = 'present'");
                            $stmt->execute([$student_id]);
                            $present_days = $stmt->fetch(PDO::FETCH_ASSOC)['present_days'];
                            
                            $absent_days = $total_days - $present_days;
                            ?>
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h3><?php echo $total_days; ?></h3>
                                            <p>Total Days</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h3><?php echo $present_days; ?></h3>
                                            <p>Present Days</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-danger text-white">
                                        <div class="card-body">
                                            <h3><?php echo $absent_days; ?></h3>
                                            <p>Absent Days</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h3><?php echo $attendance_percentage; ?>%</h3>
                                            <p>Attendance %</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Attendance</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $stmt = $conn->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY attendance_date DESC LIMIT 10");
                            $stmt->execute([$student_id]);
                            $recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <div class="list-group">
                                <?php foreach ($recent_attendance as $record): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></span>
                                    <?php if ($record['status'] == 'present'): ?>
                                        <span class="badge bg-success">Present</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Absent</span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                                
                                <?php if (empty($recent_attendance)): ?>
                                <div class="list-group-item text-center text-muted">
                                    No attendance records found
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h3>Detailed Attendance Record</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Status</th>
                                <th>Marked At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY attendance_date DESC");
                            $stmt->execute([$student_id]);
                            while ($record = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                                <td><?php echo date('l', strtotime($record['attendance_date'])); ?></td>
                                <td>
                                    <?php if ($record['status'] == 'present'): ?>
                                        <span class="badge bg-success">Present</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Absent</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($record['marked_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'profile'): ?>
            <!-- Profile Page -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <h2>My Profile</h2>
                    <div class="card">
                        <div class="card-header">
                            <h5>Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Name:</strong></div>
                                <div class="col-md-9"><?php echo $student['student_name']; ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Roll Number:</strong></div>
                                <div class="col-md-9"><?php echo $student['roll_number']; ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Email:</strong></div>
                                <div class="col-md-9"><?php echo $student['email']; ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Class:</strong></div>
                                <div class="col-md-9"><?php echo $student['class_name'] ?? 'Not Assigned'; ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Current Password:</strong></div>
                                <div class="col-md-9">
                                    <span class="text-muted">••••••••</span>
                                    <button class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                        Change Password
                                    </button>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Total Attendance:</strong></div>
                                <div class="col-md-9">
                                    <span class="badge bg-primary"><?php echo calculateAttendancePercentage($student_id, $conn); ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>