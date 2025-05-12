<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">Grade Management</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role'])): ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/manage_students.php">Students</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/manage_teachers.php">Teachers</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/manage_subjects.php">Subjects</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/manage_sections.php">Sections</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/manage_assingments.php">Manage Assignments</a>
                        </li>
                    <?php elseif ($_SESSION['role'] === 'teacher'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../teachers/view_students.php">Students</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../teachers/enter_grades.php">Enter Grades</a>
                        </li>
                    <?php elseif ($_SESSION['role'] === 'student'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../students/view_grades.php">My Grades</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
