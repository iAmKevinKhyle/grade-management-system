<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get counts for dashboard
$students_count = $conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
$teachers_count = $conn->query("SELECT COUNT(*) FROM teachers")->fetch_row()[0];
$subjects_count = $conn->query("SELECT COUNT(*) FROM subjects")->fetch_row()[0];
$sections_count = $conn->query("SELECT COUNT(*) FROM sections")->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Grade Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5 pt-4">
        <h2>Admin Dashboard</h2>
        <hr>

        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Students</h5>
                                <h2><?php echo $students_count; ?></h2>
                            </div>
                            <i class="bi bi-people-fill" style="font-size: 2rem;"></i>
                        </div>
                        <a href="manage_students.php" class="text-white">View Students</a>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Teachers</h5>
                                <h2><?php echo $teachers_count; ?></h2>
                            </div>
                            <i class="bi bi-person-badge-fill" style="font-size: 2rem;"></i>
                        </div>
                        <a href="manage_teachers.php" class="text-white">View Teachers</a>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Subjects</h5>
                                <h2><?php echo $subjects_count; ?></h2>
                            </div>
                            <i class="bi bi-book-fill" style="font-size: 2rem;"></i>
                        </div>
                        <a href="manage_subjects.php" class="text-white">View Subjects</a>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Sections</h5>
                                <h2><?php echo $sections_count; ?></h2>
                            </div>
                            <i class="bi bi-collection-fill" style="font-size: 2rem;"></i>
                        </div>
                        <a href="manage_sections.php" class="text-white">View Sections</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Grades Added</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $grades_query = $conn->query("
                                    SELECT s.first_name, s.last_name, sub.subject_name, g.grade 
                                    FROM grades g
                                    JOIN students s ON g.student_id = s.student_id
                                    JOIN subjects sub ON g.subject_id = sub.subject_id
                                    ORDER BY g.grade_date DESC LIMIT 5
                                ");

                                while ($grade = $grades_query->fetch_assoc()) {
                                    echo "<tr>
                                        <td>{$grade['first_name']} {$grade['last_name']}</td>
                                        <td>{$grade['subject_name']}</td>
                                        <td>{$grade['grade']}</td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>System Statistics</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statsChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('statsChart').getContext('2d');
        const statsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Students', 'Teachers', 'Subjects', 'Sections'],
                datasets: [{
                    label: 'Count',
                    data: [<?php echo $students_count; ?>, <?php echo $teachers_count; ?>, <?php echo $subjects_count; ?>, <?php echo $sections_count; ?>],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>

</html>