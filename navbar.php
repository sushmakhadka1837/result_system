<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <!-- Brand -->
    <a class="navbar-brand" href="index.php">HAMRO RESULT</a>

    <!-- Toggler for mobile -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Navbar links -->
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='index.php') echo 'active'; ?>" href="dashboard.php">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='about.php') echo 'active'; ?>" href="about.php">About</a>
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

        <?php if(isset($_SESSION['student_id'])): ?>
          <!-- Student logged in -->
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
          <!-- Teacher logged in -->
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
          <!-- Not logged in -->
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
