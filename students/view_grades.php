<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

// Get student ID safely
$student_id = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_data = $result->fetch_assoc();
    $student_id = $student_data['student_id'] ?? null;
}

// Check if we have a valid student ID
if (!$student_id) {
    die("Student record not found");
}

// Get student grades along with their subjects and teachers
$grades = $conn->query("
    SELECT s.subject_name, g.grade, g.grade_date, g.remarks, t.first_name AS teacher_first, t.last_name AS teacher_last
    FROM grades g
    JOIN subjects s ON g.subject_id = s.subject_id
    JOIN teachers t ON g.teacher_id = t.teacher_id
    WHERE g.student_id = $student_id
    ORDER BY g.grade_date DESC
");

// Get student info
$student_info = $conn->query("
    SELECT s.first_name, s.last_name, sec.section_name
    FROM students s
    LEFT JOIN student_section ss ON s.student_id = ss.student_id
    LEFT JOIN sections sec ON ss.section_id = sec.section_id
    WHERE s.student_id = $student_id
")->fetch_assoc();

// Get the subjects the student is enrolled in
$subjects = $conn->query("
    SELECT sub.subject_name
    FROM subjects sub
    JOIN student_section ss ON sub.subject_id = ss.subject_id
    WHERE ss.student_id = $student_id
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades - Grade Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-5 pt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Student Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <p><strong>Name:</strong> <?php echo $student_info['first_name'] . ' ' . $student_info['last_name']; ?></p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Section:</strong> <?php echo $student_info['section_name'] ?? 'Not assigned'; ?></p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Student ID:</strong> <?php echo $student_id; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Card for My Subjects -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>My Subjects</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($subjects->num_rows > 0): ?>
                            <ul>
                                <?php while ($subject = $subjects->fetch_assoc()): ?>
                                    <li><?php echo $subject['subject_name']; ?></li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <div class="alert alert-info">No subjects assigned yet.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4>My Grades</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($grades->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Grade</th>
                                            <th>Teacher</th>
                                            <th>Date</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($grade = $grades->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $grade['subject_name']; ?></td>
                                                <td><?php echo $grade['grade']; ?></td>
                                                <td><?php echo $grade['teacher_first'] . ' ' . $grade['teacher_last']; ?></td>
                                                <td><?php echo $grade['grade_date']; ?></td>
                                                <td><?php echo $grade['remarks']; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No grades recorded yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
