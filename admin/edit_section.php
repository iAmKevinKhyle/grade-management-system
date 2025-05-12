<?php
session_start();
require_once '../config/db.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get section ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid section ID.";
    header("Location: manage_sections.php");
    exit;
}

$section_id = (int) $_GET['id'];

// Fetch existing section data
$stmt = $conn->prepare("SELECT * FROM sections WHERE section_id = ?");
$stmt->bind_param("i", $section_id);
$stmt->execute();
$section = $stmt->get_result()->fetch_assoc();

if (!$section) {
    $_SESSION['error'] = "Section not found.";
    header("Location: manage_sections.php");
    exit;
}

// Update section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_section'])) {
    $section_name = trim($_POST['section_name']);

    $stmt = $conn->prepare("UPDATE sections SET section_name = ? WHERE section_id = ?");
    $stmt->bind_param("si", $section_name, $section_id);

    if ($stmt->execute()) {
        $_SESSION['updated'] = true; // use this for SweetAlert popup
        header("Location: manage_sections.php");
        exit;
    } else {
        $_SESSION['error'] = "Error updating section: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Section - Grade Management System</title>
    <?php include '../includes/header.php'; ?>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h3 class="mb-4"><i class="bi bi-pencil-square"></i> Edit Section</h3>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'];
                    unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Edit Section Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="section_name" class="form-label">Section Name</label>
                                <input type="text" class="form-control" id="section_name" name="section_name" required
                                    value="<?= htmlspecialchars($section['section_name']) ?>">
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="manage_sections.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" name="update_section" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Section
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

</body>

</html>