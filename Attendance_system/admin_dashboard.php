<?php
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 'students';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') 
    if (isset($_POST['add_class'])) {
        $class_name = $_POST['class_name'];
        $stmt = $conn->prepare("INSERT INTO classes (class_name) VALUES (?)");
        $stmt->execute([$class_name]);
        $success = "Class added successfully!";
    }
    
    if (isset($_POST['add_student'])) {
        $student_name = $_POST['student_name'];
        $roll_number = $_POST['roll_number'];
        $class_id = $_POST['class_id'];
        
        $credentials = generateStudentCredentials($student_name, $roll_number);
        
        $stmt = $conn->prepare("INSERT INTO students (student_name, roll_number, email, password, class_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_name, $roll_number, $credentials['email'], $credentials['password'], $class_id]);
        $success = "Student added successfully!";
    }
    
    if (isset($_POST['edit_student'])) {
        $student_id = $_POST['student_id'];
        $student_name = $_POST['student_name'];
        $roll_number = $_POST['roll_number'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $class_id = $_POST['class_id'];
        
        $stmt = $conn->prepare("UPDATE students SET student_name = ?, roll_number = ?, email = ?, password = ?, class_id = ? WHERE id = ?");
        $stmt->execute([$student_name, $roll_number, $email, $password, $class_id, $student_id]);
        $success = "Student updated successfully!";
    }
    
// Replace the add_teacher block (around line 35-53)
if (isset($_POST['add_teacher'])) {
    $teacher_name = $_POST['teacher_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $class_id = $_POST['class_id']; // Now single value, not array
    $subject = $_POST['subject'];
    
    $stmt = $conn->prepare("INSERT INTO teachers (teacher_name, email, password, subject, class_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$teacher_name, $email, $password, $subject, $class_id]);
    
    $success = "Teacher added successfully!";
}

// Replace the edit_teacher block (around line 54-75)
if (isset($_POST['edit_teacher'])) {
    $teacher_id = $_POST['teacher_id'];
    $teacher_name = $_POST['teacher_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $class_id = $_POST['class_id']; // Now single value, not array
    $subject = $_POST['subject'];
    
    $stmt = $conn->prepare("UPDATE teachers SET teacher_name = ?, email = ?, password = ?, subject = ?, class_id = ? WHERE id = ?");
    $stmt->execute([$teacher_name, $email, $password, $subject, $class_id, $teacher_id]);
    
    $success = "Teacher updated successfully!";
}
// Delete operations
if (isset($_GET['delete_student'])) {
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$_GET['delete_student']]);
    $success = "Student deleted successfully!";
}

if (isset($_GET['delete_teacher'])) {
    $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
    $stmt->execute([$_GET['delete_teacher']]);
    $success = "Teacher deleted successfully!";
}

if (isset($_GET['delete_class'])) {
    $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->execute([$_GET['delete_class']]);
    $success = "Class deleted successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Admin Dashboard</a>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'students' ? 'active' : ''; ?>" href="?page=students">Students</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'classes' ? 'active' : ''; ?>" href="?page=classes">Classes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'teachers' ? 'active' : ''; ?>" href="?page=teachers">Teachers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'all_students' ? 'active' : ''; ?>" href="?page=all_students">All Students</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'attendance' ? 'active' : ''; ?>" href="?page=attendance">Attendance</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($page == 'students'): ?>
            <!-- Students Page -->
            <h2>Students Overview</h2>
            <div class="row mb-3">
                <div class="col-md-6">
                    <input type="text" id="rollSearch" class="form-control" placeholder="Search by Roll Number">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Roll Number</th>
                            <th>Class</th>
                            <th>Teacher</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTable">
                        <?php
                        $stmt = $conn->query("SELECT s.*, c.class_name, t.teacher_name 
                                            FROM students s 
                                            LEFT JOIN classes c ON s.class_id = c.id 
                                            LEFT JOIN teachers t ON c.id = t.class_id");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            $attendance_percentage = calculateAttendancePercentage($row['id'], $conn);
                        ?>
                        <tr data-roll="<?php echo $row['roll_number']; ?>">
                            <td><?php echo $row['student_name']; ?></td>
                            <td><?php echo $row['roll_number']; ?></td>
                            <td><?php echo $row['class_name'] ?? 'Not Assigned'; ?></td>
                            <td><?php echo $row['teacher_name'] ?? 'Not Assigned'; ?></td>
                            <td><?php echo $attendance_percentage; ?>%</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($page == 'classes'): ?>
            <!-- Classes Page -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Classes Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">Add Class</button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Students Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("SELECT c.*, COUNT(s.id) as student_count 
                                            FROM classes c 
                                            LEFT JOIN students s ON c.id = s.class_id 
                                            GROUP BY c.id");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                        <tr>
                            <td><?php echo $row['class_name']; ?></td>
                            <td><?php echo $row['student_count']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal" onclick="setClassId(<?php echo $row['id']; ?>)">Add Student</button>
                                <a href="?page=classes&delete_class=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($page == 'teachers'): ?>
            <!-- Teachers Page -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Teachers Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">Add Teacher</button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Password</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                      <?php
                    $stmt = $conn->query("SELECT t.*, c.class_name FROM teachers t LEFT JOIN classes c ON t.class_id = c.id");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                    <tr>
                        <td><?php echo $row['teacher_name']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['password']; ?></td>
                        <td><?php echo $row['class_name'] ?? 'Not Assigned'; ?></td>
                        <td><?php echo $row['subject']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editTeacherModal" onclick="editTeacher(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                            <a href="?page=teachers&delete_teacher=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>

                    </tbody>
                </table>
            </div>

        <?php elseif ($page == 'all_students'): ?>
            <!-- All Students Page -->
            <h2>All Students Data</h2>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Roll Number</th>
                            <th>Email</th>
                            <th>Password</th>
                            <th>Class</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                        <tr>
                            <td><?php echo $row['student_name']; ?></td>
                            <td><?php echo $row['roll_number']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['password']; ?></td>
                            <td><?php echo $row['class_name'] ?? 'Not Assigned'; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editStudentModal" onclick="editStudent(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                                <a href="?page=all_students&delete_student=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($page == 'attendance'): ?>
            <!-- Attendance Page -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Attendance Reports</h2>
                
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Teacher</th>
                            <th>Total Students</th>
                            <th>Average Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("SELECT c.class_name, t.teacher_name, COUNT(s.id) as total_students,
                                            AVG(CASE WHEN a.status = 'present' THEN 100 ELSE 0 END) as avg_attendance
                                            FROM classes c
                                            LEFT JOIN teachers t ON c.id = t.class_id
                                            LEFT JOIN students s ON c.id = s.class_id
                                            LEFT JOIN attendance a ON s.id = a.student_id
                                            GROUP BY c.id");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                        <tr>
                            <td><?php echo $row['class_name']; ?></td>
                            <td><?php echo $row['teacher_name'] ?? 'Not Assigned'; ?></td>
                            <td><?php echo $row['total_students']; ?></td>
                            <td><?php echo round($row['avg_attendance'] ?? 0, 2); ?>%</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modals -->
    <!-- Add Class Modal -->
    <div class="modal fade" id="addClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="class_name" class="form-label">Class Name</label>
                            <input type="text" class="form-control" name="class_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_class" class="btn btn-primary">Add Class</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="student_name" class="form-label">Student Name</label>
                            <input type="text" class="form-control" name="student_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="roll_number" class="form-label">Roll Number</label>
                            <input type="text" class="form-control" name="roll_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="class_id" class="form-label">Class</label>
                            <select class="form-control" name="class_id" id="studentClassSelect" required>
                                <option value="">Select Class</option>
                                <?php
                                $stmt = $conn->query("SELECT * FROM classes");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo $row['class_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <small class="text-muted">Email and password will be auto-generated</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="student_id" id="editStudentId">
                        <div class="mb-3">
                            <label for="edit_student_name" class="form-label">Student Name</label>
                            <input type="text" class="form-control" name="student_name" id="editStudentName" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_roll_number" class="form-label">Roll Number</label>
                            <input type="text" class="form-control" name="roll_number" id="editRollNumber" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editStudentEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Password</label>
                            <input type="text" class="form-control" name="password" id="editStudentPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_class_id" class="form-label">Class</label>
                            <select class="form-control" name="class_id" id="editStudentClassId" required>
                                <option value="">Select Class</option>
                                <?php
                                $stmt = $conn->query("SELECT * FROM classes");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo $row['class_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="edit_student" class="btn btn-primary">Update Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="teacher_name" class="form-label">Teacher Name</label>
                            <input type="text" class="form-control" name="teacher_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                     <div class="mb-3">
                    <label for="class_id" class="form-label">Assign Class</label>
                    <select class="form-control" name="class_id" required>
                        <option value="">Select Class</option>
                        <?php
                        $stmt = $conn->query("SELECT * FROM classes");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['class_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" name="subject" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_teacher" class="btn btn-primary">Add Teacher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="teacher_id" id="editTeacherId">
                        <div class="mb-3">
                            <label for="edit_teacher_name" class="form-label">Teacher Name</label>
                            <input type="text" class="form-control" name="teacher_name" id="editTeacherName" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_teacher_email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editTeacherEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_teacher_password" class="form-label">Password</label>
                            <input type="text" class="form-control" name="password" id="editTeacherPassword" required>
                        </div>
                            <div class="mb-3">
                            <label for="edit_teacher_class_id" class="form-label">Assign Class</label>
                            <select class="form-control" name="class_id" id="editTeacherClassId" required>
                                <option value="">Select Class</option>
                                <?php
                                $stmt = $conn->query("SELECT * FROM classes");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo $row['class_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <small class="text-muted">Hold Ctrl/Cmd to select multiple classes</small>
                         </div>
                        <div class="mb-3">
                            <label for="edit_teacher_subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" name="subject" id="editTeacherSubject" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="edit_teacher" class="btn btn-primary">Update Teacher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

   

    <script>
        // Search functionality
        document.getElementById('rollSearch').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#studentsTable tr');
            
            rows.forEach(row => {
                const rollNumber = row.getAttribute('data-roll');
                if (rollNumber && rollNumber.toLowerCase().includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        function setClassId(classId) {
            document.getElementById('studentClassSelect').value = classId;
        }

        // Edit Student Function
        function editStudent(student) {
            document.getElementById('editStudentId').value = student.id;
            document.getElementById('editStudentName').value = student.student_name;
            document.getElementById('editRollNumber').value = student.roll_number;
            document.getElementById('editStudentEmail').value = student.email;
            document.getElementById('editStudentPassword').value = student.password;
            document.getElementById('editStudentClassId').value = student.class_id || '';
        }

                // Edit Teacher Function
                function editTeacher(teacher) {
            document.getElementById('editTeacherId').value = teacher.id;
            document.getElementById('editTeacherName').value = teacher.teacher_name;
            document.getElementById('editTeacherEmail').value = teacher.email;
            document.getElementById('editTeacherPassword').value = teacher.password;
            document.getElementById('editTeacherSubject').value = teacher.subject;
            document.getElementById('editTeacherClassId').value = teacher.class_id || '';
        }
    </script>
</body>
</html>