<?php
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_GET['ts_id'])) {
    echo json_encode(['error' => 'Missing teacher_subject ID']);
    exit;
}

$ts_id = (int)$_GET['ts_id'];

// Get subject_id and section_id first
$ts_data = $conn->query("
    SELECT subject_id, section_id 
    FROM teacher_subject 
    WHERE id = $ts_id
")->fetch_assoc();

if (!$ts_data) {
    echo json_encode(['error' => 'Invalid teacher_subject ID']);
    exit;
}

$subject_id = $ts_data['subject_id'];
$section_id = $ts_data['section_id'];

// Get students in this section
$students = $conn->query("
    SELECT s.student_id, s.first_name, s.last_name
    FROM students s
    JOIN student_section ss ON s.student_id = ss.student_id
    WHERE ss.section_id = $section_id
    ORDER BY s.last_name, s.first_name
")->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'students' => $students,
    'subject_id' => $subject_id,
    'section_id' => $section_id
]);
?>