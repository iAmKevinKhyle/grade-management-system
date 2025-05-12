<?php
session_start();

// Redirect logged-in users to their respective dashboards
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            exit;
        case 'teacher':
            header("Location: teachers/view_students.php");
            exit;
        case 'student':
            header("Location: students/view_grades.php");
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Grade Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1588072432836-e10032774350?ixlib=rb-4.0.3');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 50px;
        }
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #0d6efd;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Grade Management System</h1>
            <p class="lead mb-5">Streamline your educational institution's grading process with our comprehensive solution</p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <a href="login.php" class="btn btn-primary btn-lg px-4 gap-3">Login</a>
                <a href="register.php" class="btn btn-outline-light btn-lg px-4">Register</a>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row text-center mb-5">
            <div class="col-lg-12">
                <h2 class="fw-bold mb-4">Key Features</h2>
                <p class="lead text-muted">Our system provides all the tools you need for efficient grade management</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 card-hover">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h4 class="card-title">Student Management</h4>
                        <p class="card-text">Easily manage student records, track progress, and view complete academic histories.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 card-hover">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                        <h4 class="card-title">Grade Tracking</h4>
                        <p class="card-text">Record and manage grades efficiently with our intuitive interface designed for educators.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 card-hover">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="bi bi-bar-chart-line-fill"></i>
                        </div>
                        <h4 class="card-title">Reporting</h4>
                        <p class="card-text">Generate comprehensive reports and analytics to monitor academic performance.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="fw-bold mb-4">About Our System</h2>
                    <p class="lead">The Grade Management System is designed to simplify the process of recording, tracking, and analyzing student grades for educational institutions of all sizes.</p>
                    <p>Our platform provides administrators, teachers, and students with the tools they need to manage academic information efficiently and securely.</p>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3" alt="Education" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Grade Management System</h5>
                    <p class="text-muted">© <?php echo date('Y'); ?> All Rights Reserved</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white me-3"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white me-3"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="text-white"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>