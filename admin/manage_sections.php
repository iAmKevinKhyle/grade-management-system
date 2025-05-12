<?php
session_start();
require_once '../config/db.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Add new section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_section'])) {
    $section_name = trim($_POST['section_name']);
    $academic_year = trim($_POST['academic_year']);

    $stmt = $conn->prepare("INSERT INTO sections (section_name, academic_year) VALUES (?, ?)");
    $stmt->bind_param("ss", $section_name, $academic_year);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Section added successfully!";
    } else {
        $_SESSION['error'] = "Error adding section: " . $conn->error;
    }
    header("Location: manage_sections.php");
    exit;
}

// Delete section
if (isset($_GET['delete'])) {
    $section_id = (int) $_GET['delete'];

    $conn->begin_transaction();
    try {
        // Delete from related tables first
        $conn->query("DELETE FROM student_section WHERE section_id = $section_id");
        $conn->query("DELETE FROM teacher_subject WHERE section_id = $section_id");
        $conn->query("DELETE FROM grades WHERE section_id = $section_id");

        // Then delete the section
        $conn->query("DELETE FROM sections WHERE section_id = $section_id");

        $conn->commit();
        $_SESSION['success'] = "Section deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error deleting section: " . $e->getMessage();
    }
    header("Location: manage_sections.php");
    exit;
}

// Get all sections with student count
$query = "
    SELECT s.section_id, s.section_name, s.academic_year, COUNT(ss.student_id) AS student_count
    FROM sections s
    LEFT JOIN student_section ss ON s.section_id = ss.section_id
    GROUP BY s.section_id
    ORDER BY s.academic_year DESC, s.section_name
";
$sections = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sections - Grade Management System</title>
    <?php include '../includes/header.php'; ?>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2><i class="bi bi-collection"></i> Manage Sections</h2>
                <hr>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['success'];
                    unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'];
                    unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5><i class="bi bi-plus-circle"></i> Add New Section</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="section_name" class="form-label">Section Name</label>
                                        <input type="text" class="form-control" id="section_name" name="section_name"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="academic_year" class="form-label">Academic Year</label>
                                        <input type="text" class="form-control" id="academic_year" name="academic_year"
                                            placeholder="e.g., 2023-2024" required>
                                    </div>
                                    <button type="submit" name="add_section" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Save Section
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5><i class="bi bi-list-ul"></i> Section List</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($sections->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Academic Year</th>
                                                    <th>Student Count</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($section = $sections->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($section['section_id']) ?></td>
                                                        <td><?= htmlspecialchars($section['section_name']) ?></td>
                                                        <td><?= htmlspecialchars($section['academic_year']) ?></td>
                                                        <td><?= htmlspecialchars($section['student_count']) ?></td>
                                                        <td>
                                                            <a href="edit_section.php?id=<?= $section['section_id'] ?>"
                                                                class="btn btn-sm btn-warning" title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <a href="?delete=<?= $section['section_id'] ?>"
                                                                class="btn btn-sm btn-danger" title="Delete"
                                                                onclick="return confirm('Are you sure you want to delete this section?')">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                            <a href="manage_section_students.php?id=<?= $section['section_id'] ?>"
                                                                class="btn btn-sm btn-info" title="Manage Students">
                                                                <i class="bi bi-people"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">No sections found.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <!-- SweetAlert success message -->
    <?php if (isset($_SESSION['updated'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Section Updated',
                text: 'The section was updated successfully.',
                confirmButtonColor: '#3085d6',
            });
        </script>
        <?php unset($_SESSION['updated']); ?>
    <?php endif; ?>

    <script>
        $(document).ready(function () {
            $('[title]').tooltip();
        });
    </script>
</body>

</html>