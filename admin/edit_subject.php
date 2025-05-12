<?php
session_start();
require_once '../config/db.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid subject ID.";
    header("Location: manage_subjects.php");
    exit;
}

$subject_id = (int) $_GET['id'];

// Fetch subject
$stmt = $conn->prepare("SELECT * FROM subjects WHERE subject_id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
$subject = $result->fetch_assoc();

if (!$subject) {
    $_SESSION['error'] = "Subject not found.";
    header("Location: manage_subjects.php");
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_subject'])) {
    $subject_name = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);
    $description = trim($_POST['description']);

    $update_stmt = $conn->prepare("UPDATE subjects SET subject_name = ?, subject_code = ?, description = ? WHERE subject_id = ?");
    $update_stmt->bind_param("sssi", $subject_name, $subject_code, $description, $subject_id);

    if ($update_stmt->execute()) {
        $_SESSION['updated'] = true;
        header("Location: manage_subjects.php");
        exit;
    } else {
        $_SESSION['error'] = "Failed to update subject.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Subject</title>
    <?php include '../includes/header.php'; ?>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="container" style="max-width: 600px;">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <h3 class="mb-4 text-center"><i class="bi bi-pencil"></i> Edit Subject</h3>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error'];
                        unset($_SESSION['error']); ?></div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header bg-primary text-white text-center">
                            <h5 class="mb-0">Edit Subject Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="subject_name" class="form-label">Subject Name</label>
                                    <input type="text" class="form-control" id="subject_name" name="subject_name"
                                        required value="<?= htmlspecialchars($subject['subject_name']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="subject_code" class="form-label">Subject Code</label>
                                    <input type="text" class="form-control" id="subject_code" name="subject_code"
                                        required value="<?= htmlspecialchars($subject['subject_code']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description"
                                        rows="3"><?= htmlspecialchars($subject['description']) ?></textarea>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <a href="manage_subjects.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Back
                                    </a>
                                    <button type="submit" name="update_subject" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Update Subject
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

</body>

</html>