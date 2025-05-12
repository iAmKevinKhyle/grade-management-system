<?php
session_start();
require_once '../config/db.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No subject selected.";
    header("Location: manage_subjects.php");
    exit;
}

$subject_id = (int)$_GET['id'];

// Get subject details
$subject_stmt = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
$subject_stmt->bind_param("i", $subject_id);
$subject_stmt->execute();
$subject_result = $subject_stmt->get_result();
$subject = $subject_result->fetch_assoc();

if (!$subject) {
    $_SESSION['error'] = "Subject not found.";
    header("Location: manage_subjects.php");
    exit;
}

// Assign teacher to subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_teacher'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $section_id = (int)$_POST['section_id'];

    if ($teacher_id > 0 && $section_id > 0) {
        $stmt = $conn->prepare("INSERT INTO teacher_subject (teacher_id, subject_id, section_id)
                                VALUES (?, ?, ?)
                                ON DUPLICATE KEY UPDATE teacher_id = VALUES(teacher_id)");
        $stmt->bind_param("iii", $teacher_id, $subject_id, $section_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Teacher assigned successfully!";
        } else {
            $_SESSION['error'] = "Failed to assign teacher: " . $stmt->error;
        }
    } else {
        $_SESSION['error'] = "Please select both a teacher and a section.";
    }
    $_SESSION['trigger_modal'] = true;
}

// Assign student to subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_student'])) {
    $student_id = (int)$_POST['student_id'];

    if ($student_id > 0) {
        $stmt = $conn->prepare("INSERT INTO student_subject (student_id, subject_id)
                                VALUES (?, ?)
                                ON DUPLICATE KEY UPDATE subject_id = VALUES(subject_id)");
        $stmt->bind_param("ii", $student_id, $subject_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Student assigned to subject successfully!";
        } else {
            $_SESSION['error'] = "Failed to assign student: " . $stmt->error;
        }
    } else {
        $_SESSION['error'] = "Please select a student.";
    }
    $_SESSION['trigger_modal'] = true;
}

$teachers = $conn->query("SELECT teacher_id, CONCAT(first_name, ' ', last_name) AS full_name FROM teachers ORDER BY first_name");
$students = $conn->query("SELECT student_id, CONCAT(first_name, ' ', last_name) AS full_name FROM students ORDER BY first_name");
$sections = $conn->query("SELECT section_id, section_name FROM sections ORDER BY section_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subject Assignments</title>
    <?php include '../includes/header.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container mt-4">
    <h3>Manage Assignments for: <strong><?= htmlspecialchars($subject['subject_name']) ?></strong></h3>
    <hr>

    <!-- Modal Message -->
    <?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
        <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header <?= isset($_SESSION['success']) ? 'bg-success' : 'bg-danger' ?> text-white">
                        <h5 class="modal-title" id="messageModalLabel">
                            <?= isset($_SESSION['success']) ? 'Success' : 'Error'; ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?= $_SESSION['success'] ?? $_SESSION['error']; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['success'], $_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Assignment Forms -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-success text-white">Assign Teacher</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Select Teacher</label>
                            <select name="teacher_id" class="form-select" required>
                                <option value="">-- Choose Teacher --</option>
                                <?php while ($t = $teachers->fetch_assoc()): ?>
                                    <option value="<?= $t['teacher_id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Section</label>
                            <select name="section_id" class="form-select" required>
                                <option value="">-- Choose Section --</option>
                                <?php foreach ($sections as $s): ?>
                                    <option value="<?= $s['section_id'] ?>"><?= htmlspecialchars($s['section_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="assign_teacher" class="btn btn-success">Assign Teacher</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-info text-white">Assign Student</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Select Student</label>
                            <select name="student_id" class="form-select" required>
                                <option value="">-- Choose Student --</option>
                                <?php while ($s = $students->fetch_assoc()): ?>
                                    <option value="<?= $s['student_id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" name="assign_student" class="btn btn-info">Assign Student</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Modal Trigger -->
<?php if (isset($_SESSION['trigger_modal'])): ?>
<script>
    var messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
    messageModal.show();
</script>
<?php unset($_SESSION['trigger_modal']); endif; ?>

<?php include '../includes/footer.php'; ?>
</body>
</html>
