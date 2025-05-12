<?php
session_start();
require_once '../config/db.php';

// Redirect if not logged in or not a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

// Get teacher ID
$teacher_id = $conn->query("SELECT teacher_id FROM teachers WHERE user_id = {$_SESSION['user_id']}")->fetch_assoc()['teacher_id'];

// Get students assigned to this teacher's sections
$students = $conn->query("
    SELECT s.student_id, s.first_name, s.last_name, s.email, 
           sec.section_name, sub.subject_name
    FROM students s
    JOIN student_section ss ON s.student_id = ss.student_id
    JOIN teacher_subject ts ON ss.section_id = ts.section_id
    JOIN sections sec ON ss.section_id = sec.section_id
    JOIN subjects sub ON ts.subject_id = sub.subject_id
    WHERE ts.teacher_id = $teacher_id
    ORDER BY sec.section_name, s.last_name, s.first_name
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students - Grade Management System</title>
    <?php include '../includes/header.php'; ?>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="bi bi-people-fill"></i> My Students</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($students->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Section</th>
                                            <th>Subject</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($student = $students->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($student['student_id']) ?></td>
                                                <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                                </td>
                                                <td><?= htmlspecialchars($student['email']) ?></td>
                                                <td><?= htmlspecialchars($student['section_name']) ?></td>
                                                <td><?= htmlspecialchars($student['subject_name']) ?></td>
                                                <td>
                                                    <a href="view_grades.php?student_id=<?= $student['student_id'] ?>"
                                                        class="btn btn-sm btn-info" title="View Grades">
                                                        <i class="bi bi-journal-bookmark"></i>
                                                    </a>
                                                    <a href="enter_grades.php?student_id=<?= $student['student_id'] ?>"
                                                        class="btn btn-sm btn-success" title="Enter Grades">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No students assigned to your sections.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Initialize tooltips
        $(document).ready(function () {
            $('[title]').tooltip();
        });
    </script>
</body>

</html>