<?php
session_start();
require_once '../config/db.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch all sections
$sections_result = $conn->query("SELECT section_id, section_name FROM sections");

// Filter section (optional)
$filter_section_id = isset($_GET['filter_section_id']) ? (int)$_GET['filter_section_id'] : 0;

// Fetch students
if ($filter_section_id > 0) {
    $stmt = $conn->prepare("
        SELECT s.student_id, s.first_name, s.last_name, u.username, sec.section_name
        FROM students s
        LEFT JOIN student_section ss ON s.student_id = ss.student_id
        LEFT JOIN sections sec ON ss.section_id = sec.section_id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE ss.section_id = ?
        ORDER BY s.last_name, s.first_name
    ");
    $stmt->bind_param("i", $filter_section_id);
    $stmt->execute();
    $students_result = $stmt->get_result();
} else {
    $students_result = $conn->query("
        SELECT s.student_id, s.first_name, s.last_name, u.username, sec.section_name
        FROM students s
        LEFT JOIN student_section ss ON s.student_id = ss.student_id
        LEFT JOIN sections sec ON ss.section_id = sec.section_id
        LEFT JOIN users u ON s.user_id = u.id
        ORDER BY sec.section_name, s.last_name, s.first_name
    ");
}

// Handle assign/reassign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reassign_student'])) {
    $student_id = $_POST['student_id'];
    $new_section_id = $_POST['new_section_id'];

    if (is_numeric($student_id) && is_numeric($new_section_id)) {
        // Check if student already has an assignment
        $check = $conn->prepare("SELECT * FROM student_section WHERE student_id = ?");
        $check->bind_param("i", $student_id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            // Update existing assignment
            $stmt = $conn->prepare("UPDATE student_section SET section_id = ? WHERE student_id = ?");
        } else {
            // Insert new assignment
            $stmt = $conn->prepare("INSERT INTO student_section (student_id, section_id) VALUES (?, ?)");
            // Note the reversed param order
            $stmt->bind_param("ii", $student_id, $new_section_id);
            $stmt->execute();

            $_SESSION['success'] = "Student assigned successfully!";
            header("Location: manage_section_students.php");
            exit;
        }

        $stmt->bind_param("ii", $new_section_id, $student_id);
        $stmt->execute();

        $_SESSION['success'] = "Student reassigned successfully!";
        header("Location: manage_section_students.php");
        exit;
    } else {
        $_SESSION['error'] = "Invalid student or section.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Section Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container mt-5 pt-4">
    <h2>Student Section List</h2>

    <!-- Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white"><h5>Filter Students by Section</h5></div>
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-6">
                    <select name="filter_section_id" class="form-select" onchange="this.form.submit()">
                        <option value="0">-- Show All Sections --</option>
                        <?php
                        $sections_result->data_seek(0);
                        while ($section = $sections_result->fetch_assoc()): ?>
                            <option value="<?= $section['section_id'] ?>" <?= $filter_section_id == $section['section_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($section['section_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Students Table -->
    <div class="card">
        <div class="card-header bg-secondary text-white"><h5>Students</h5></div>
        <div class="card-body">
            <?php if ($students_result->num_rows > 0): ?>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Username</th>
                            <th>Section</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = $students_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                                <td><?= htmlspecialchars($student['username']) ?></td>
                                <td><?= htmlspecialchars($student['section_name'] ?? 'Unassigned') ?></td>
                                <td>
                                    <?php if (empty($student['section_name'])): ?>
                                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#reassignModal"
                                            data-student-id="<?= $student['student_id'] ?>" data-current-section="Unassigned">
                                            Assign
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#reassignModal"
                                            data-student-id="<?= $student['student_id'] ?>" data-current-section="<?= htmlspecialchars($student['section_name']) ?>">
                                            Reassign
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No students found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="reassignModal" tabindex="-1" aria-labelledby="reassignModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reassignModalLabel">Reassign Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="student_id" id="student_id">
                <div class="mb-3">
                    <label for="current_section" class="form-label">Current Section</label>
                    <input type="text" class="form-control" id="current_section" disabled>
                </div>
                <div class="mb-3">
                    <label for="new_section_id" class="form-label">New Section</label>
                    <select name="new_section_id" class="form-select" required>
                        <?php
                        $sections_result->data_seek(0);
                        while ($section = $sections_result->fetch_assoc()): ?>
                            <option value="<?= $section['section_id'] ?>"><?= htmlspecialchars($section['section_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="reassign_student" class="btn btn-primary" id="submitBtn">Submit</button>
            </div>
        </form>
    </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const reassignModal = document.getElementById('reassignModal');
    reassignModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const studentId = button.getAttribute('data-student-id');
        const currentSection = button.getAttribute('data-current-section');

        document.getElementById('student_id').value = studentId;
        document.getElementById('current_section').value = currentSection;

        const modalTitle = reassignModal.querySelector('.modal-title');
        const submitBtn = document.getElementById('submitBtn');

        if (currentSection === 'Unassigned') {
            modalTitle.textContent = 'Assign Student';
            submitBtn.textContent = 'Assign';
        } else {
            modalTitle.textContent = 'Reassign Student';
            submitBtn.textContent = 'Reassign';
        }
    });
</script>
</body>
</html>
