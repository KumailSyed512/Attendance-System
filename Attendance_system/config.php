<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "attendance_system";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions
function calculateAttendancePercentage($student_id, $conn) {
    $stmt = $conn->prepare("SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days
        FROM attendance WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total_days'] == 0) return 0;
    return round(($result['present_days'] / $result['total_days']) * 100, 2);
}

function generateStudentCredentials($student_name, $roll_number) {
    $email = strtolower(str_replace(' ', '', $student_name)) . $roll_number . '@student.com';
    $password = 'pass' . $roll_number;
    return ['email' => $email, 'password' => $password];
}
if (isset($_GET['teacher_id'])) {
    $teacher_id = $_GET['teacher_id'];
    $stmt = $conn->prepare("SELECT class_id FROM teacher_classes WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $class_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    header('Content-Type: application/json');
    echo json_encode(array_map('intval', $class_ids));
} 
?>
