<?php
include 'config.php';

// Check if teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teachers_login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$success = '';
$error = '';

// Get teacher information
$stmt = $conn->prepare("SELECT t.*, c.class_name FROM teachers t 
                       LEFT JOIN classes c ON t.class_id = c.id 
                       WHERE t.id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_POST && isset($_POST['update_profile'])) {
    $teacher_name = $_POST['teacher_name'];
    $email = $_POST['email'];
    // Remove subject from update - only admin can change it
    
    try {
        $stmt = $conn->prepare("UPDATE teachers SET teacher_name = ?, email = ? WHERE id = ?");
        $stmt->execute([$teacher_name, $email, $teacher_id]);
        
        // Update session variables
        $_SESSION['teacher_name'] = $teacher_name;
        $_SESSION['teacher_email'] = $email;
        
        $success = "Profile updated successfully!";
        
        // Refresh teacher data
        $stmt = $conn->prepare("SELECT t.*, c.class_name FROM teachers t 
                               LEFT JOIN classes c ON t.class_id = c.id 
                               WHERE t.id = ?");
        $stmt->execute([$teacher_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Failed to update profile: " . $e->getMessage();
    }
}

// Handle password change
if ($_POST && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($current_password !== $teacher['password']) {
        $error = "Current password is incorrect!";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long!";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE teachers SET password = ? WHERE id = ?");
            $stmt->execute([$new_password, $teacher_id]);
            $success = "Password changed successfully!";
            
            // Refresh teacher data
            $stmt = $conn->prepare("SELECT t.*, c.class_name FROM teachers t 
                                   LEFT JOIN classes c ON t.class_id = c.id 
                                   WHERE t.id = ?");
            $stmt->execute([$teacher_id]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = "Failed to change password: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Profile - Attendance System</title>

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
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 20px;
        }
        .form-control {
            border-radius: 10px;
        }
        .btn-update {
            border-radius: 10px;
            font-weight: 600;
        }
        .readonly-field {
            background-color: #f8f9fa;
            border-color: #dee2e6;
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
                        <a class="nav-link active" href="teachers_profile.php">
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
                            <i class="fas fa-user-circle me-1"></i><?php echo $teacher['teacher_name']; ?>
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
        <!-- Breadcrumb -->
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="teachers_dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Profile</li>
                    </ol>
                </nav>
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

        <div class="row">
            <!-- Profile Information -->
            <div class="col-md-8">
                <div class="card">
                    <div class="profile-header">
                        <div class="card-body text-center">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <h4><?php echo $teacher['teacher_name']; ?></h4>
                            <p class="mb-0"><?php echo $teacher['subject']; ?> Teacher</p>
                            <small><?php echo $teacher['class_name'] ?? 'No Class Assigned'; ?></small>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-edit me-2"></i>Update Profile Information
                        </h5>
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="teacher_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="teacher_name" name="teacher_name" 
                                           value="<?php echo $teacher['teacher_name']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo $teacher['email']; ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control readonly-field" id="subject" 
                                           value="<?php echo $teacher['subject']; ?>" readonly>
                                    <small class="text-muted">
                                        <i class="fas fa-lock me-1"></i>Subject can only be changed by admin
                                    </small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="class_name" class="form-label">Assigned Class</label>
                                    <input type="text" class="form-control readonly-field" id="class_name" 
                                           value="<?php echo $teacher['class_name'] ?? 'No Class Assigned'; ?>" readonly>
                                    <small class="text-muted">
                                        <i class="fas fa-lock me-1"></i>Class assignment can only be changed by admin
                                    </small>
                                </div>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary btn-update">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-lock me-2"></i>Change Password
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" 
                                       name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" 
                                       name="new_password" minlength="6" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" minlength="6" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-warning btn-update w-100">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Quick Stats
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get teacher's class statistics
                        $stats_query = "SELECT 
                            COUNT(DISTINCT s.id) as total_students,
                            COUNT(DISTINCT a.attendance_date) as total_days,
                            COUNT(a.id) as total_records
                            FROM students s
                            LEFT JOIN attendance a ON s.id = a.student_id AND a.teacher_id = ?
                            WHERE s.class_id = ?";
                        $stmt = $conn->prepare($stats_query);
                        $stmt->execute([$teacher_id, $teacher['class_id']]);
                        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                        ?>
                        <div class="text-center">
                            <div class="mb-3">
                                <h4 class="text-primary"><?php echo $stats['total_students']; ?></h4>
                                <small class="text-muted">Total Students</small>
                            </div>
                            <div class="mb-3">
                                <h4 class="text-success"><?php echo $stats['total_days']; ?></h4>
                                <small class="text-muted">Days Recorded</small>
                            </div>
                            <div class="mb-0">
                                <h4 class="text-info"><?php echo $stats['total_records']; ?></h4>
                                <small class="text-muted">Total Records</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirm = this.value;
            
            if (password !== confirm) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>