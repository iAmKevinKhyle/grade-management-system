<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Add new teacher
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_teacher'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

    // Create user first
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'teacher')");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $user_id = $conn->insert_id;

        $stmt = $conn->prepare("INSERT INTO teachers (user_id, first_name, last_name, email, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $first_name, $last_name, $email, $phone);
        $stmt->execute();

        $conn->commit();
        $success = "Teacher added successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error adding teacher: " . $e->getMessage();
    }
}

// Delete teacher
if (isset($_GET['delete'])) {
    $teacher_id = $_GET['delete'];

    $conn->begin_transaction();

    try {
        // Get user_id first
        $user_id = $conn->query("SELECT user_id FROM teachers WHERE teacher_id = $teacher_id")->fetch_assoc()['user_id'];

        // Delete from teacher_subject first
        $conn->query("DELETE FROM teacher_subject WHERE teacher_id = $teacher_id");

        // Delete teacher
        $conn->query("DELETE FROM teachers WHERE teacher_id = $teacher_id");

        // Delete user
        $conn->query("DELETE FROM users WHERE id = $user_id");

        $conn->commit();
        $success = "Teacher deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error deleting teacher: " . $e->getMessage();
    }
}

// Handle teacher search
$search_keyword = '';
if (isset($_GET['search'])) {
    $search_keyword = trim($_GET['search']);
}

// Get all teachers or filtered teachers
$sql = "SELECT t.teacher_id, t.first_name, t.last_name, t.email, t.phone, u.username
        FROM teachers t
        JOIN users u ON t.user_id = u.id";
if (!empty($search_keyword)) {
    $sql .= " WHERE t.first_name LIKE ? OR t.last_name LIKE ? OR u.username LIKE ?";
}
$sql .= " ORDER BY t.last_name, t.first_name";

$stmt = $conn->prepare($sql);
if (!empty($search_keyword)) {
    $search_param = "%" . $search_keyword . "%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}
$stmt->execute();
$teachers = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - Grade Management System</title>
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
        <h2>Manage Teachers</h2>
        <hr>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Add Teacher Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Add New Teacher</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" name="add_teacher" class="btn btn-primary">Add Teacher</button>
                        </form>
                    </div>
                </div>
            </div>



            <!-- Teacher List -->
            <div class="col-md-8">

                <!-- Search Form -->
                <form method="GET" class="d-flex mb-3">
                    <input type="text" class="form-control" name="search" placeholder="Search by name or username"
                        value="<?php echo htmlspecialchars($search_keyword); ?>">
                    <button type="submit" class="btn btn-success ms-2">Search</button>
                </form>

                <div class="card">
                    <div class="card-header">
                        <h5>Teacher List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Contact</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($teacher = $teachers->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $teacher['teacher_id']; ?></td>
                                            <td><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></td>
                                            <td><?php echo $teacher['username']; ?></td>
                                            <td>
                                                <?php if ($teacher['email']): ?>
                                                    <?php echo $teacher['email']; ?><br>
                                                <?php endif; ?>
                                                <?php echo $teacher['phone'] ?? ''; ?>
                                            </td>
                                            <td>
                                                <a href="edit_teacher.php?id=<?php echo $teacher['teacher_id']; ?>"
                                                    class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?delete=<?php echo $teacher['teacher_id']; ?>"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Are you sure you want to delete this teacher?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <a href="assign_subjects.php?id=<?php echo $teacher['teacher_id']; ?>"
                                                    class="btn btn-sm btn-info">
                                                    <i class="bi bi-book"></i> Subjects
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Message -->
    <?php if (isset($_SESSION['toast_message'])): ?>
        <div class="toast align-items-center text-bg-success border-0 show" role="alert" aria-live="assertive"
            aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <?php echo $_SESSION['toast_message'];
                    unset($_SESSION['toast_message']); ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Optional: Auto-hide Toast Message after 3 seconds -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toastElement = document.querySelector('.toast');
            if (toastElement) {
                setTimeout(function () {
                    var toast = new bootstrap.Toast(toastElement);
                    toast.hide();
                }, 3000); // 3 seconds delay
            }
        });
    </script>
</body>

</html>