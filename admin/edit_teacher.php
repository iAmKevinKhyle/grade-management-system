<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch teacher data
if (!isset($_GET['id'])) {
    header("Location: manage_teachers.php");
    exit;
}

$teacher_id = $_GET['id'];
$teacher = $conn->query("
    SELECT t.*, u.username, u.id AS user_id
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE t.teacher_id = $teacher_id
")->fetch_assoc();

if (!$teacher) {
    $error = "Teacher not found.";
}

// Update teacher info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_teacher'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $new_password = trim($_POST['password']);

    try {
        // Update username
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $username, $teacher['user_id']);
        $stmt->execute();

        // If password was provided, update it
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $teacher['user_id']);
            $stmt->execute();
        }

        // Update teacher info
        $stmt = $conn->prepare("UPDATE teachers SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE teacher_id = ?");
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $teacher_id);
        $stmt->execute();

        $_SESSION['toast_message'] = "Teacher information updated successfully!";

        header("Location: manage_teachers.php");
        exit;
    } catch (Exception $e) {
        $error = "Error updating teacher: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Teacher - Grade Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom styles to ensure consistent size for toast messages */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
            width: 300px;
            /* Adjust the width */
            max-width: 90%;
            /* Make it responsive */
            font-size: 16px;
            /* Increase font size */
            padding: 10px;
            /* Adjust padding */
        }

        .toast-body {
            font-size: 16px;
            /* Ensure the text is the same size */
            padding: 10px;
            /* Add padding to the body for spacing */
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5 pt-4">
        <h2>Edit Teacher</h2>
        <hr>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($teacher): ?>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Edit Teacher Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                        value="<?php echo htmlspecialchars($teacher['first_name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                        value="<?php echo htmlspecialchars($teacher['last_name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?php echo htmlspecialchars($teacher['email']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        value="<?php echo htmlspecialchars($teacher['phone']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username"
                                        value="<?php echo htmlspecialchars($teacher['username']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password (optional)</label>
                                    <input type="password" class="form-control" id="password" name="password"
                                        placeholder="Leave blank to keep current password">
                                </div>
                                <div class="d-flex justify-content-between">
                                    <button type="submit" name="update_teacher" class="btn btn-success">Update</button>
                                    <a href="manage_teachers.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>