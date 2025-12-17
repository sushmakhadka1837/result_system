<?php

?>

<!-- Top Header -->
<div class="top-header">
  <div></div>
  <div class="info text-end">
    <div>Phirke 08, Pokhara, Nepal 🇳🇵</div>
    <div>📞 +977 061-581209 / 575926</div>
    <div><a href="#" class="text-decoration-none">📧 Contact Us</a></div>
  </div>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="images.png" alt="PEC Logo" width="400" height="50" class="me-2">
      <span class="fw-bold text-dark">PEC Result Hub</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">

        <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="aboutus.php">About</a></li>
        <li class="nav-item"><a class="nav-link" href="engineering_notes.php">Notes</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Results</a>
          <ul class="dropdown-menu dropdown-menu-dark">
            <li><a class="dropdown-item" href="view_results.php">View Results</a></li>
          </ul>
        </li>

      <li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle" href="#" id="noticeDropdown" data-bs-toggle="dropdown">Notice</a>
  <ul class="dropdown-menu" aria-labelledby="noticeDropdown">
   
    <li><a class="dropdown-item" href="notice.php?category=college_updates">College Updates</a></li>

    <li><a class="dropdown-item" href="https://pu.edu.np/notices/">PU Related</a></li>
          </ul>
      </li>

        <li class="nav-item dropdown">
          <?php
          $user_type = $_SESSION['user_type'] ?? null;
          if ($user_type === 'student' || $user_type === 'teacher'):
              $dashboard = ($user_type === 'student') ? 'student_dashboard.php' : 'teacher_dashboard.php';
          ?>
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">My Account</a>
              <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                  <li><a class="dropdown-item" href="<?= $dashboard ?>">Profile</a></li>
                  <li><a class="dropdown-item" href="logout.php">Logout</a></li>
              </ul>
          <?php else: ?>
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Login</a>
              <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                  <li><a class="dropdown-item" href="student_login.php">Student Login</a></li>
                  <li><a class="dropdown-item" href="teacher_login.php">Teacher Login</a></li>
              </ul>
          <?php endif; ?>
        </li>

      </ul>
    </div>
  </div>
</nav>

<style>
/* Top Header */
.top-header {
    background-color: #ffffff;
    padding: 10px 20px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #001f4d;
}
.top-header .info div, .top-header .info a {
    font-size: 0.85rem;
    color: #001f4d;
}

/* Navbar */
.navbar {
    background-color: #ffffff !important;
    position: sticky;
    top: 0;
    z-index: 999;
}
.navbar .nav-link {
    color: #001f4d;
    font-weight: 500;
    position: relative;
    transition: color 0.3s;
}
.navbar .nav-link:hover,
.navbar .nav-link.active {
    color: #ffdd57 !important;
}
.navbar .nav-link::after {
    content: '';
    position: absolute;
    width: 0%;
    height: 2px;
    bottom: 0;
    left: 0;
    background: #ffdd57;
    transition: width 0.3s;
}
.navbar .nav-link:hover::after,
.navbar .nav-link.active::after {
    width: 100%;
}

/* Responsive */
@media(max-width:768px){
    .top-header { flex-direction: column; text-align: center; }
    .top-header .info { margin-top: 10px; }
}
</style>
