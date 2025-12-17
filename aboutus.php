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
  <link href="#" rel="stylesheet" />
<style> 
.container h2 {
    color: #001f4d;
    font-weight: 700;
    margin-bottom: 20px;
}
.container p {
    color: #333;
    font-size: 1rem;
    line-height: 1.7;
}
.container h4 {
    color: #0d6efd;
    margin-top: 25px;
    margin-bottom: 10px;
}

/* ---------- About Photos ---------- */
.about-photos img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease-in-out;
}
.about-photos img:hover {
    transform: scale(1.03);
}

/* ---------- Sidebar ---------- */
.sidebar {
    background-color: #ffffff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.sidebar h5 {
    font-weight: 600;
    color: #0d6efd;
    margin-bottom: 20px;
}
.bottom-section img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    border-radius: 12px;
    transition: transform 0.3s ease-in-out;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}
.bottom-section img:hover {
    transform: scale(1.05);
}
.bottom-section p {
    font-weight: 600;
    margin-top: 10px;
    text-align: center;
}
</style>

</head>
<body>
<?php include 'header.php';?>

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

<?php include 'footer.php'; ?>

</body>
</html>
