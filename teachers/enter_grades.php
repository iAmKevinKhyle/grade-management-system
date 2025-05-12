<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

// Get teacher ID
$teacher_id = $conn->query("SELECT teacher_id FROM teachers WHERE user_id = {$_SESSION['user_id']}")->fetch_assoc()['teacher_id'];

// Get subjects and sections taught by this teacher
$subjects_sections = $conn->query("
    SELECT ts.id, s.subject_name, sec.section_name 
    FROM teacher_subject ts
    JOIN subjects s ON ts.subject_id = s.subject_id
    JOIN sections sec ON ts.section_id = sec.section_id
    WHERE ts.teacher_id = $teacher_id
");

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $section_id = $_POST['section_id'];
    $grade = $_POST['grade'];
    $remarks = $_POST['remarks'];
    
    $stmt = $conn->prepare("INSERT INTO grades (student_id, subject_id, teacher_id, section_id, grade, grade_date, remarks) VALUES (?, ?, ?, ?, ?, CURDATE(), ?)");
    $stmt->bind_param("iiidss", $student_id, $subject_id, $teacher_id, $section_id, $grade, $remarks);
    
    if ($stmt->execute()) {
        $success = "Grade added successfully!";
    } else {
        $error = "Error adding grade: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Grades - Grade Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <h2>Enter Grades</h2>
        <hr>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Add New Grade</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="subject_section" class="form-label">Subject & Section</label>
                                <select class="form-select" id="subject_section" name="subject_section" required>
                                    <option value="">Select Subject & Section</option>
                                    <?php while ($row = $subjects_sections->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo $row['subject_name'] . ' - ' . $row['section_name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="student_id" class="form-label">Student</label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">Select Student</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="grade" class="form-label">Grade</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="grade" name="grade" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                            </div>
                            
                            <input type="hidden" id="subject_id" name="subject_id">
                            <input type="hidden" id="section_id" name="section_id">
                            
                            <button type="submit" class="btn btn-primary">Submit Grade</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Grades Entered</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>Grade</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent_grades = $conn->query("
                                    SELECT s.first_name, s.last_name, sub.subject_name, g.grade, g.grade_date 
                                    FROM grades g
                                    JOIN students s ON g.student_id = s.student_id
                                    JOIN subjects sub ON g.subject_id = sub.subject_id
                                    WHERE g.teacher_id = $teacher_id
                                    ORDER BY g.grade_date DESC LIMIT 5
                                ");
                                
                                while ($grade = $recent_grades->fetch_assoc()) {
                                    echo "<tr>
                                        <td>{$grade['first_name']} {$grade['last_name']}</td>
                                        <td>{$grade['subject_name']}</td>
                                        <td>{$grade['grade']}</td>
                                        <td>{$grade['grade_date']}</td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('subject_section').addEventListener('change', function() {
            const tsId = this.value;
            if (!tsId) return;
            
            fetch(`get_students.php?ts_id=${tsId}`)
                .then(response => response.json())
                .then(data => {
                    const studentSelect = document.getElementById('student_id');
                    studentSelect.innerHTML = '<option value="">Select Student</option>';
                    
                    data.students.forEach(student => {
                        const option = document.createElement('option');
                        option.value = student.student_id;
                        option.textContent = `${student.first_name} ${student.last_name}`;
                        studentSelect.appendChild(option);
                    });
                    
                    document.getElementById('subject_id').value = data.subject_id;
                    document.getElementById('section_id').value = data.section_id;
                });
        });
    </script>
</body>
</html>