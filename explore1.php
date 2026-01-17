<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Event Highlights | Explore PEC</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" rel="stylesheet" />

  <style>
    :root {
        --pec-blue: #001f4d;
        --pec-accent: #0d6efd;
        --text-muted: #64748b;
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: #f8fafc;
        color: #1e293b;
    }

    /* Page Header */
    .page-header {
        background: white;
        padding: 40px 0;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 40px;
    }

    /* Main Article Card */
    .event-main-card {
        background: white;
        border: none;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }
    .event-main-card img {
        width: 100%;
        height: auto;
        max-height: 500px;
        object-fit: cover;
    }
    .event-content {
        padding: 40px;
    }
    .event-tag {
        background: rgba(13,110,253,0.1);
        color: var(--pec-accent);
        padding: 6px 15px;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 20px;
    }

    /* Sidebar Styles */
    .sidebar-title {
        font-weight: 700;
        color: var(--pec-blue);
        position: relative;
        padding-bottom: 10px;
        margin-bottom: 25px;
    }
    .sidebar-title::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 40px;
        height: 3px;
        background: var(--pec-accent);
        border-radius: 2px;
    }
    .sidebar-card {
        background: white;
        padding: 25px;
        border-radius: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.03);
    }
    .mini-post {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        text-decoration: none;
        color: inherit;
        transition: 0.3s;
    }
    .mini-post img {
        width: 80px;
        height: 80px;
        border-radius: 12px;
        object-fit: cover;
    }
    .mini-post h6 {
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 5px;
        line-height: 1.4;
    }
    .mini-post:hover {
        color: var(--pec-accent);
    }

    /* Sticky Sidebar */
    .sticky-sidebar {
        position: sticky;
        top: 20px;
    }
  </style>
</head>
<body>

<?php include 'header.php';?>

<div class="page-header">
    <div class="container text-center">
        <h1 class="fw-bold">Explore PEC Events</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tech EXPO Highlights</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container pb-5">
  <div class="row g-4">
    
    <div class="col-lg-8">
      <div class="event-main-card">
        <a href="images/bottom1.jpg" class="glightbox">
            <img src="images/bottom1.jpg" class="card-img-top" alt="8th PEC Tech EXPO 2022">
        </a>
        <div class="event-content">
          <span class="event-tag"><i class="fas fa-calendar-alt me-2"></i>Tech EXPO 2022</span>
          <h2 class="fw-bold mb-4" style="color: var(--pec-blue);">8th PEC Tech EXPO 2022 को भव्य उदघाटन</h2>
          
          <p class="fs-5 lh-lg" style="color: #475569;">
            Gandaki University का कुलपति <strong>Prof. Dr. Ganesh Man Gurung</strong> ज्यूबाट 
            <strong>8th PEC Tech EXPO 2022</strong> को विधिवत उदघाटन सु-सम्पन्न भयो। उक्त कार्यक्रममा 
            विद्यार्थीहरूले आफ्ना नवीनतम् इन्जिनियरिङ प्रोजेक्टहरू प्रदर्शन गरेका थिए।
          </p>
          
          <div class="alert alert-light border-0 shadow-sm mt-4 p-4" style="border-left: 4px solid var(--pec-accent) !important;">
             <i class="fas fa-quote-left fa-2x opacity-25 mb-3"></i>
             <p class="fst-italic mb-0">"यस्ता प्रदर्शनीहरूले विद्यार्थीहरूको रचनात्मकतालाई बाहिर ल्याउन र प्रविधिमा देशलाई आत्मनिर्भर बनाउन मद्दत पुर्‍याउँछ।"</p>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="sticky-sidebar">
        <div class="sidebar-card">
          <h5 class="sidebar-title">Explore More</h5>
          
          <a href="explore1.php" class="mini-post">
            <img src="images/expo1.jpg" alt="Expo">
            <div>
              <h6>Tech EXPO को उदघाटन र मुख्य आकर्षणहरू</h6>
              <small class="text-muted">Event Gallery</small>
            </div>
          </a>
          
          <a href="explore2.php" class="mini-post">
            <img src="images/expo.jpg" alt="Expo highlights">
            <div>
              <h6>8th PEC EXPO का विशेष झलकहरू र क्लब गतिविधि</h6>
              <small class="text-muted">Club News</small>
            </div>
          </a>

          <hr>
          
          <div class="p-3 bg-light rounded-4 text-center">
             <h6 class="fw-bold">Interested in PEC?</h6>
             <p class="small text-muted">Join our community of future engineers.</p>
             <a href="admission.php" class="btn btn-primary btn-sm rounded-pill w-100">Apply Now</a>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include 'footer.php';?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<script>
    const lightbox = GLightbox({ selector: '.glightbox' });
</script>

</body>
</html>