<?php
include 'db_config.php'; // database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>About Us - Pokhara Engineering College</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- GLightbox CSS -->
  <link href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" rel="stylesheet" />
  <link href="style.css" rel="stylesheet" />

  <style>
    body {
      background-color: #f5f7fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .about-photos img {
      width: 100%;
      height: 220px;
      object-fit: cover;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease-in-out;
    }
    .about-photos img:hover { transform: scale(1.03); }
    .sidebar {
      background-color: #ffffff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    .bottom-section img {
      width: 100%;
      height: 160px;
      object-fit: cover;
      border-radius: 12px;
      transition: transform 0.3s ease-in-out;
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
    }
    .bottom-section img:hover { transform: scale(1.05); }
    .bottom-section p { font-weight: 600; margin-top: 10px; }
    h2, h4 { color: #0d6efd; }
    ul li { margin-bottom: 8px; }
    .top-header { background-color: #ffffff; padding: 15px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); }
    nav.navbar { background-color: #0d6efd; }
    nav.navbar a.nav-link, nav.navbar a.dropdown-item { color: #ffffff; }
    nav.navbar a.nav-link:hover, nav.navbar a.dropdown-item:hover { background-color: #0056b3; color: #ffffff; }
  </style>
</head>
<body>
  <div class="top-header d-flex flex-wrap justify-content-between align-items-center">
    <div class="d-flex align-items-center">
      <img src="images.png" alt="PEC Logo" height="50">
      <div class="ms-3">
        <h5 class="mb-0 fw-bold text-primary">PEC RESULT-HUB</h5>
      </div>
    </div>
    <div class="info text-end">
      <div>Phirke 08 Pokhara Nepal 🇳🇵</div>
      <div>📞 +977 061-581209/575926</div>
      <div><a href="#" class="text-primary text-decoration-none">Contact Us</a></div>
    </div>
  </div>

  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="aboutus.php">About us</a></li>

          <!-- Programs Dropdown Fetched from Database -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Programs</a>
            <ul class="dropdown-menu">
              <?php
              $sql = "SELECT * FROM departments ORDER BY department_name ASC";
              $result = $conn->query($sql);

              if ($result->num_rows > 0) {
                  while($row = $result->fetch_assoc()) {
                      echo '<li><a class="dropdown-item" href="#">' . htmlspecialchars($row['department_name']) . '</a></li>';
                  }
              } else {
                  echo '<li><a class="dropdown-item" href="#">No Departments Found</a></li>';
              }
              ?>
            </ul>
          </li>

          <li class="nav-item"><a class="nav-link" href="#">Notice</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Publications</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Achievements</a></li>
       
          
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Conference</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#">Conference Info</a></li>
            </ul>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Others</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#">FAQ</a></li>
            </ul>
          </li>
        </ul>
        <a class="btn btn-light" href="#">Registration</a>
      </div>
    </div>
  </nav>

  <div class="container mt-5">
    <div class="row">
      <div class="col-lg-8">
        <h2 class="mb-4">About Us</h2>
        <div class="about-photos row g-3">
          <div class="col-md-6">
            <a href="images/building1.png" class="glightbox" data-gallery="about-gallery">
              <img src="images/building1.png" alt="PEC Photo 1" />
            </a>
          </div>
          <div class="col-md-6">
            <a href="images/expo1.jpg" class="glightbox" data-gallery="about-gallery">
              <img src="images/expo1.jpg" alt="PEC Photo 2" />
            </a>
          </div>
          <div class="col-md-6">
            <a href="images/13th conv.jpg" class="glightbox" data-gallery="about-gallery">
              <img src="images/13th conv.jpg" alt="PEC Photo 3" />
            </a>
          </div>
          <div class="col-md-6">
            <a href="images/admission.jpg" class="glightbox" data-gallery="about-gallery">
              <img src="images/admission.jpg" alt="PEC Photo 4" />
            </a>
          </div>
        </div>

        <p class="mt-4">
          Pokhara Engineering College (PEC), established in 1999 AD, is the pioneer
          college of Western region for providing engineering education. It runs
          various engineering programs at Master, Bachelor and Diploma level under
          affiliation of Pokhara University and CTEVT.
        </p>

        <h4 class="mt-4">Vision:</h4>
        <p>To excel professional education and achieve the best on par with global context.</p>

        <h4>Mission:</h4>
        <ul>
          <li>To build up creative engineers imbibed with original ideas.</li>
          <li>To recognize as innovative institution and make findings truly assets of the nation.</li>
          <li>To indoctrinate the values of ethical standards in personal, social and public life and create leadership parallel to brilliant education as per the objective and need of the society and nation.</li>
          <li>To pursuit for excellence.</li>
        </ul>
      </div>

      <div class="col-lg-4">
        <div class="sidebar">
          <h5 class="text-primary fw-bold mb-4">Explore More from PEC</h5>
          <div class="bottom-section">
            <div class="mb-4 text-center">
              <a href="explore1.php">
                <img src="images/bottom1.jpg" class="img-fluid" alt="8th PEC Tech EXPO 2022 को उदघाटन।" />
              </a>
              <p><a href="explore1.php" class="text-decoration-none text-dark">8th PEC Tech EXPO 2022 को उदघाटन।</a></p>
            </div>
           
            <div class="mb-4 text-center">
              <a href="explore2.php">
                <img src="images/expo.jpg" class="img-fluid" alt="Clubs" />
              </a>
              <p><a href="explore2.php" class="text-decoration-none text-dark">8th PEC EXPO</a></p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
  <script>
    const lightbox = GLightbox({ selector: '.glightbox' });
  </script>

  <footer>
    <div>
      <h4>Quick Links</h4>
      <a href="index.php">Home</a>
      <a href="#">Our Programs</a>
      <a href="about.php">About Us</a>
      <a href="#">Notice Board</a>
    </div>

    <div>
      <h4>Follow Us</h4>
      <div class="social-icons">
        <a href="https://www.facebook.com/PECPoU" aria-label="Facebook"><img src="https://img.icons8.com/ios-filled/24/ffffff/facebook-new.png" alt="Facebook"/></a>
        <a href="https://www.instagram.com/pec.pkr/" aria-label="Instagram"><img src="https://img.icons8.com/ios-filled/24/ffffff/instagram-new.png" alt="Instagram"/></a>
      </div>
    </div>

    <div>
      <h4>Contact Us</h4>
      <p>Address: Phirke Pokhara-8, Nepal</p>
      <p>Phone No: 061 581209</p>
      <p>Email: info@pec.edu.np</p>
    </div>

    <div>
      <h4>Useful Links</h4>
      <a href="https://pu.edu.np/">Pokhara University</a>
      <a href="https://ctevt.org.np/">CTEVT</a>
      <a href="https://nec.gov.np/">Nepal Engineering Council</a>
      <a href="https://neanepal.org.np/">Nepal Engineer's Association</a>
      <a href="https://pu.edu.np/research/purc-seminar-series/">PU Research</a>
    </div>
  </footer>
</body>
</html>
