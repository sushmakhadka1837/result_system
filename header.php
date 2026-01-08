


<!-- Navbar / Main Header -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background:#001f4d;">
  <div class="container d-flex align-items-center justify-content-between">
    
    <!-- Logo -->
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="images/logoheader.png" alt="PEC Logo" class="logo-round">
    </a>

    <!-- Contact Info -->
    <div class="header-info d-flex flex-column text-white text-end">
      <div>Phirke 08, Pokhara, Nepal 🇳🇵</div>
      <div>📞 +977 061-581209 / 575926</div>
      <div><a href="#" class="text-decoration-none text-white">📧 Contact Us</a></div>
    </div>

    <!-- Navbar Links -->
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
            <li><a class="dropdown-item" href="results.php">View Results</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="noticeDropdown" data-bs-toggle="dropdown">Notice</a>
          <ul class="dropdown-menu" aria-labelledby="noticeDropdown">
            <li><a class="dropdown-item" href="college_updates.php">College Updates</a></li>
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
 body {
      font-family: 'Poppins', sans-serif;
      background: #f5f6fa;
  }
.navbar{
    background:#001f4d !important;
    padding:5px 20px;
}
.navbar-nav {
    display: flex;
    align-items: center;
    gap: 20px; /* links बीचको space, default 15-20px भन्दा बढी */
}
/* Round Logo */
.logo-round{
    width:100px;
    height:100px;
    border-radius:50%;
    object-fit:cover;
}

/* Header info text */
.header-info div,
.header-info a{
    font-size:14px;
    font-weight:500;
    color:#fff;
}

/* Navbar links */
.navbar .nav-link{
    font-size:16px;
    font-weight:600;
    padding:12px 18px;  /* normal padding */
    color:#fff !important;
    transition: all 0.25s ease; /* smooth transition */
}

/* Subtle hover */
.navbar .nav-link:hover{
    background:#f4c430;   /* golden yellow highlight */
    color:#001f4d !important;
    padding:12px 18px;    /* keep padding same, no jump */
}

/* Dropdown hover */
.dropdown-item:hover{
    background:#001f4d;
    color:#f4c430;
    padding:10px 18px;   /* slightly smaller vertical padding */
}

/* Dropdown smooth */
.dropdown-menu{
    display:block;
    opacity:0;
    visibility:hidden;
    transform:translateY(12px); /* slightly below */
    transition:all 0.3s ease;   /* smooth */
    pointer-events:none;
    border:none;
    border-radius:0;
    min-width:220px;
    box-shadow:0 12px 30px rgba(0,0,0,0.12);
    background:#fff;
    padding:0;
}

/* Hover open */
.nav-item.dropdown:hover > .dropdown-menu{
    opacity:1;
    visibility:visible;
    transform:translateY(0);
    pointer-events:auto;
}

/* Dropdown items */
.dropdown-item{
    font-size:15px;       /* slightly bigger */
    font-weight:500;
    padding:12px 18px;
    color:#001f4d;
    transition:all 0.25s ease;
}

/* Dropdown hover */

/* Responsive */
@media(max-width:768px){
    .container{
        flex-direction:column;
        align-items:center;
        gap:10px;
    }

    .header-info{
        text-align:center;
        flex-direction:column;
    }

    .logo-round{
        width:80px;
        height:80px;
    }
}
</style>