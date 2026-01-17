<?php include 'db_config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>About Us | Pokhara Engineering College</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" rel="stylesheet" />

  <style>
    :root {
        --primary-dark: #001f4d;
        --accent-blue: #0d6efd;
        --bg-soft: #f8fafc;
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-soft);
        color: #334155;
    }

    /* Modern Hero Section */
    .about-hero {
        background: linear-gradient(135deg, rgba(0,31,77,0.95), rgba(13,110,253,0.8)), url('images/building1.png');
        background-size: cover;
        background-position: center;
        color: white;
        padding: 80px 0;
        margin-bottom: 50px;
        border-radius: 0 0 40px 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    /* Photo Gallery */
    .about-photos img {
        width: 100%;
        height: 240px;
        object-fit: cover;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }
    .about-photos img:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    /* Content Cards */
    .info-card {
        background: white;
        padding: 35px;
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: 0 5px 20px rgba(0,0,0,0.03);
        margin-bottom: 30px;
        transition: 0.3s;
    }
    .info-card:hover {
        border-color: var(--accent-blue);
    }
    .icon-box {
        width: 50px;
        height: 50px;
        background: rgba(13,110,253,0.1);
        color: var(--accent-blue);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 20px;
    }

    /* Sidebar Styling */
    .sticky-sidebar {
        position: sticky;
        top: 30px;
    }
    .sidebar-card {
        background: white;
        border-radius: 18px;
        padding: 20px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    }
    .explore-item {
        display: block;
        text-decoration: none;
        margin-bottom: 25px;
        group;
    }
    .explore-item img {
        width: 100%;
        height: 160px;
        border-radius: 12px;
        object-fit: cover;
        margin-bottom: 12px;
        transition: 0.3s;
    }
    .explore-item:hover img {
        filter: brightness(0.8);
    }
    .explore-item p {
        font-weight: 600;
        color: var(--primary-dark);
        margin: 0;
        font-size: 0.95rem;
    }

    ul.custom-list {
        list-style: none;
        padding: 0;
    }
    ul.custom-list li {
        padding-left: 30px;
        position: relative;
        margin-bottom: 12px;
    }
    ul.custom-list li::before {
        content: "\f058";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        position: absolute;
        left: 0;
        color: #10b981;
    }
  </style>
</head>
<body>

<?php include 'header.php'; ?>

<section class="about-hero text-center">
    <div class="container">
        <span class="badge bg-light text-primary px-3 py-2 mb-3 rounded-pill">ESTD 1999</span>
        <h1 class="display-4 fw-bold">Pokhara Engineering College</h1>
        <p class="lead opacity-75">Nurturing Innovation and Engineering Excellence</p>
    </div>
</section>

<div class="container pb-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="about-photos row g-4">
                <div class="col-md-6"><a href="images/building1.png" class="glightbox"><img src="images/building1.png" alt="PEC Building"></a></div>
                <div class="col-md-6"><a href="images/expo1.jpg" class="glightbox"><img src="images/expo1.jpg" alt="Tech Expo"></a></div>
                <div class="col-md-6"><a href="images/13th conv.jpg" class="glightbox"><img src="images/13th conv.jpg" alt="Convocation"></a></div>
                <div class="col-md-6"><a href="images/admission.jpg" class="glightbox"><img src="images/admission.jpg" alt="Admission"></a></div>
            </div>

            <div class="mt-5 mb-5">
                <h2 class="fw-bold" style="color: var(--primary-dark);">Who We Are</h2>
                <p class="lead text-muted mt-3">
                    Pokhara Engineering College (PEC) is the pioneer institution of the Western region, 
                    dedicated to shaping the future of engineering since 1999. Under the affiliation of 
                    Pokhara University and CTEVT, we provide a dynamic environment for Master, Bachelor, 
                    and Diploma level students.
                </p>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="info-card">
                        <div class="icon-box"><i class="fas fa-eye"></i></div>
                        <h4>Our Vision</h4>
                        <p>To excel professional education and achieve the best on par with global context, creating a benchmark in technical learning.</p>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="info-card">
                        <div class="icon-box" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="fas fa-bullseye"></i></div>
                        <h4>Our Mission</h4>
                        <ul class="custom-list mt-3">
                            <li>Build creative engineers imbued with original and practical ideas.</li>
                            <li>Develop findings that serve as true assets for the nation's growth.</li>
                            <li>Indoctrinate ethical values and leadership in personal and professional life.</li>
                            <li>Pursue excellence in every academic and research endeavor.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="sticky-sidebar">
                <div class="sidebar-card">
                    <h5 class="fw-bold mb-4" style="color: var(--primary-dark);">Explore More</h5>
                    
                    <a href="explore1.php" class="explore-item">
                        <img src="images/bottom1.jpg" alt="PEC Expo">
                        <p>8th PEC Tech EXPO 2022 Inauguration</p>
                        <small class="text-primary">Read More <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>

                    <hr class="my-4 opacity-25">

                    <a href="explore2.php" class="explore-item">
                        <img src="images/expo.jpg" alt="PEC Clubs">
                        <p>8th PEC EXPO Highlights & Activities</p>
                        <small class="text-primary">Read More <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<script>
    const lightbox = GLightbox({ selector: '.glightbox' });
</script>

</body>
</html>