<?php
session_start();
require_once '../config/db.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Add new subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $subject_name = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);
    $description = trim($_POST['description']);

    $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, description) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $subject_name, $subject_code, $description);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Subject added successfully!";
    } else {
        $_SESSION['error'] = "Error adding subject: " . $conn->error;
    }
    header("Location: manage_subjects.php");
    exit;
}

// Delete subject
if (isset($_GET['delete'])) {
    $subject_id = (int) $_GET['delete'];

    $conn->begin_transaction();
    try {
        // Delete from related tables first
        $conn->query("DELETE FROM teacher_subject WHERE subject_id = $subject_id");
        $conn->query("DELETE FROM grades WHERE subject_id = $subject_id");

        // Then delete the subject
        $conn->query("DELETE FROM subjects WHERE subject_id = $subject_id");

        $conn->commit();
        $_SESSION['success'] = "Subject deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error deleting subject: " . $e->getMessage();
    }
    header("Location: manage_subjects.php");
    exit;
}

// Get all subjects
$subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_name");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects - Grade Management System</title>
    <?php include '../includes/header.php'; ?>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2><i class="bi bi-book"></i> Manage Subjects</h2>
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
                                <h5><i class="bi bi-plus-circle"></i> Add New Subject</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="subject_name" class="form-label">Subject Name</label>
                                        <input type="text" class="form-control" id="subject_name" name="subject_name"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="subject_code" class="form-label">Subject Code</label>
                                        <input type="text" class="form-control" id="subject_code" name="subject_code"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description"
                                            rows="3"></textarea>
                                    </div>
                                    <button type="submit" name="add_subject" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Save Subject
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5><i class="bi bi-list-ul"></i> Subject List</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($subjects->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Code</th>
                                                    <th>Description</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($subject = $subjects->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($subject['subject_id']) ?></td>
                                                        <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                                                        <td><?= htmlspecialchars($subject['subject_code']) ?></td>
                                                        <td><?= htmlspecialchars($subject['description'] ?? 'N/A') ?></td>
                                                        <td>
                                                            <div class="d-flex gap-2 align-items-center">
                                                                <a href="edit_subject.php?id=<?= $subject['subject_id'] ?>"
                                                                    class="btn btn-sm btn-warning" title="Edit">
                                                                    <i class="bi bi-pencil"></i>
                                                                </a>
                                                                <a href="?delete=<?= $subject['subject_id'] ?>"
                                                                    class="btn btn-sm btn-danger" title="Delete"
                                                                    onclick="return confirm('Are you sure you want to delete this subject?')">
                                                                    <i class="bi bi-trash"></i>
                                                                </a>
                                                                <a href="manage_subject_student.php?id=<?= $subject['subject_id'] ?>"
                                                                    class="btn btn-sm btn-info" title="Manage subject">
                                                                    <i class="bi bi-people"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">No subjects found.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <?php if (isset($_SESSION['updated'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Subject Updated',
                text: 'The subject was updated successfully.',
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