<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PEC Result Hub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    footer {
      background-color: #0d6efd;
      color: #fff;
      padding: 20px 0;
    }
    footer a {
      color: #fff !important;
    }
    footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<!-- Header -->
<div class="top-header d-flex flex-wrap justify-content-between align-items-center p-3 bg-light">
  <div class="d-flex align-items-center">
    <img src="images.png" alt="PEC Logo" style="max-width: 250px; height: auto;">
    <div class="ms-3">
      <h5 class="mb-0 fw-bold text-primary">PEC RESULT-HUB</h5>
    </div>
  </div>

  <div class="info text-end">
    <div>Phirke 08 Pokhara Nepal 🇳🇵</div>
    <div>📞 +977 061-581209 / 575926</div>
    <div><a href="#" class="text-primary text-decoration-none">📧 Contact Us</a></div>
  </div>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='dashboard.php') echo 'active'; ?>" href="dashboard.php">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='about.php') echo 'active'; ?>" href="aboutus.php">About</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Results</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="view_results.php">View Results</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='notice.php') echo 'active'; ?>" href="notice.php">Notices</a>
        </li>

        <!-- Session Based Login / Profile -->
        <?php if(isset($_SESSION['student_id'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
              <img src="images/profile_icon.png" alt="Account" width="30" height="30" class="rounded-circle me-2">
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="student_dashboard.php">My Account</a></li>
              <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            </ul>
          </li>
        <?php elseif(isset($_SESSION['teacher_id'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
              <img src="images/profile_icon.png" alt="Account" width="30" height="30" class="rounded-circle me-2">
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="teacher_dashboard.php">My Account</a></li>
              <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Login</a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="student_login.php">Student Login</a></li>
              <li><a class="dropdown-item" href="teacher_login.php">Teacher Login</a></li>
            </ul>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>

<!-- Content -->
<div class="container my-5">
  <h2 class="text-center text-primary mb-4">Welcome to PEC Result Management System</h2>
  <p class="text-center">Manage student records, view results, and access academic resources all in one place.</p>
</div>

<!-- Footer -->
<footer>
  <div class="container d-flex flex-wrap justify-content-between">
    <div class="mb-3">
      <h5>Quick Links</h5>
      <a href="index.php" class="d-block text-light text-decoration-none">Home</a>
      <a href="#" class="d-block text-light text-decoration-none">Our Programs</a>
      <a href="about.php" class="d-block text-light text-decoration-none">About Us</a>
      <a href="notice.php" class="d-block text-light text-decoration-none">Notice Board</a>
    </div>
    <div class="mb-3">
      <h5>Follow Us</h5>
      <div class="social-icons d-flex gap-2">
        <a href="https://www.facebook.com/PECPoU" aria-label="Facebook">
          <img src="https://img.icons8.com/ios-filled/24/ffffff/facebook-new.png" alt="Facebook"/>
        </a>
        <a href="https://www.instagram.com/pec.pkr/" aria-label="Instagram">
          <img src="https://img.icons8.com/ios-filled/24/ffffff/instagram-new.png" alt="Instagram"/>
        </a>
      </div>
    </div>
    <div class="mb-3">
      <h5>Contact Us</h5>
      <p>Phirke Pokhara-8, Nepal</p>
      <p>Phone: 061 581209</p>
      <p>Email: info@pec.edu.np</p>
    </div>
    <div class="mb-3">
      <h5>Useful Links</h5>
      <a href="https://pu.edu.np/" class="d-block text-light text-decoration-none">Pokhara University</a>
      <a href="https://ctevt.org.np/" class="d-block text-light text-decoration-none">CTEVT</a>
      <a href="https://nec.gov.np/" class="d-block text-light text-decoration-none">Nepal Engineering Council</a>
      <a href="https://neanepal.org.np/" class="d-block text-light text-decoration-none">Nepal Engineer's Association</a>
      <a href="https://pu.edu.np/research/purc-seminar-series/" class="d-block text-light text-decoration-none">PU Research</a>
    </div>
  </div>
  <div class="text-center mt-3">
    <small>&copy; 2025 PEC Result Hub. All rights reserved.</small>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
