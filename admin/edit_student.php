<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: manage_students.php");
    exit;
}

$student_id = intval($_GET['id']);

// Fetch current student info
$stmt = $conn->prepare("
    SELECT s.student_id, s.first_name, s.last_name, s.email, s.phone, 
           u.username, u.id as user_id
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.student_id = ?"
);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "Student not found.";
    exit;
}

$student = $result->fetch_assoc();

// Update student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $conn->begin_transaction();
    try {
        // Update student table
        $stmt = $conn->prepare("UPDATE students SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE student_id = ?");
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $student_id);
        $stmt->execute();

        // Update username and password if needed
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $hashed_password, $student['user_id']);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $username, $student['user_id']);
        }
        $stmt->execute();

        $conn->commit();
        // Set the success flag in the session
        $_SESSION['update_success'] = true;

        // Redirect to the same page with updated data
        header("Location: manage_students.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error updating student: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Student - Grade Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

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
        <h2>Edit Student</h2>
        <hr>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="col-md-6 offset-md-3">
            <!-- This centers the form in a column that is 6 units wide on medium screens -->
            <div class="card">
                <div class="card-header">
                    <h5>Edit Student Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name"
                                value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name"
                                value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo htmlspecialchars($student['email']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                value="<?php echo htmlspecialchars($student['phone']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username"
                                value="<?php echo htmlspecialchars($student['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password <small>(leave blank to keep
                                    current)</small></label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="submit" name="update_student" class="btn btn-success">Update Student</button>
                            <a href="manage_students.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>