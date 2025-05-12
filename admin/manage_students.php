<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Add new student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

    // Create user first
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $user_id = $conn->insert_id;

        $stmt = $conn->prepare("INSERT INTO students (user_id, first_name, last_name, email, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $first_name, $last_name, $email, $phone);
        $stmt->execute();

        $conn->commit();
        $success = "Student added successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error adding student: " . $e->getMessage();
    }
}

// Delete student
if (isset($_GET['delete'])) {
    $student_id = (int) $_GET['delete'];
    $conn->autocommit(false);
    $conn->begin_transaction();

    try {
        $result = $conn->query("SELECT user_id FROM students WHERE student_id = $student_id");

        if ($result && $result->num_rows > 0) {
            $user_id = $result->fetch_assoc()['user_id'];

            if (!$conn->query("DELETE FROM student_subject WHERE student_id = $student_id")) {
                throw new Exception("Failed to delete from student_subject: " . $conn->error);
            }

            if (!$conn->query("DELETE FROM student_section WHERE student_id = $student_id")) {
                throw new Exception("Failed to delete from student_section: " . $conn->error);
            }

            if (!$conn->query("DELETE FROM students WHERE student_id = $student_id")) {
                throw new Exception("Failed to delete student: " . $conn->error);
            }

            if (!$conn->query("DELETE FROM users WHERE id = $user_id")) {
                throw new Exception("Failed to delete user: " . $conn->error);
            }

            $conn->commit();
            $success = "Student deleted successfully!";
        } else {
            throw new Exception("Student ID not found.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error deleting student: " . $e->getMessage();
    }
}

// Get all students
$search_keyword = '';
if (isset($_GET['search'])) {
    $search_keyword = trim($_GET['search']);
}

$sql = "
    SELECT s.student_id, s.first_name, s.last_name, s.email, s.phone, 
           sec.section_name, u.username
    FROM students s
    LEFT JOIN student_section ss ON s.student_id = ss.student_id
    LEFT JOIN sections sec ON ss.section_id = sec.section_id
    LEFT JOIN users u ON s.user_id = u.id
";

if (!empty($search_keyword)) {
    $sql .= " WHERE s.first_name LIKE ? OR s.last_name LIKE ? OR u.username LIKE ?";
}
$sql .= " ORDER BY s.last_name, s.first_name";

$stmt = $conn->prepare($sql);
if (!empty($search_keyword)) {
    $search_param = "%" . $search_keyword . "%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}
$stmt->execute();
$students = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Grade Management System</title>
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
        <h2>Manage Students</h2>
        <hr>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Add New Student</h5>
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
                            <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">

                <form method="GET" class="d-flex mb-3">
                    <input type="text" class="form-control" name="search" placeholder="Search by name or username"
                        value="<?php echo htmlspecialchars($search_keyword); ?>">
                    <button type="submit" class="btn btn-success ms-2">Search</button>
                </form>

                <div class="card">
                    <div class="card-header">
                        <h5>Student List</h5>
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
                                        <th>Section</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($student = $students->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $student['student_id']; ?></td>
                                            <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                            <td><?php echo $student['username']; ?></td>
                                            <td>
                                                <?php if ($student['email']): ?>
                                                    <?php echo $student['email']; ?><br>
                                                <?php endif; ?>
                                                <?php echo $student['phone'] ?? ''; ?>
                                            </td>
                                            <td><?php echo $student['section_name'] ?? 'Not assigned'; ?></td>
                                            <td>
                                                <a href="edit_student.php?id=<?php echo $student['student_id']; ?>"
                                                    class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?delete=<?php echo $student['student_id']; ?>"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Are you sure you want to delete this student?')">
                                                    <i class="bi bi-trash"></i>
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

    <!-- Success Message Popup (JavaScript) -->
    <?php if (isset($_SESSION['update_success']) && $_SESSION['update_success']): ?>
        <script type="text/javascript">
            // Show the success message (Bootstrap Toast)
            document.addEventListener("DOMContentLoaded", function () {
                var toastHTML = `
                    <div class="toast align-items-center text-bg-success border-0 show position-fixed bottom-0 end-0 p-3" style="z-index: 1050" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                Student updated successfully!
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', toastHTML);

                // Automatically hide the toast after 3 seconds
                setTimeout(function () {
                    var toast = document.querySelector('.toast');
                    toast.classList.remove('show');
                }, 3000);
            });
        </script>
        <?php unset($_SESSION['update_success']); ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>