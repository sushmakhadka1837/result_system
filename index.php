<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'db_config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PEC Result Hub | Pokhara Engineering College</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root {
      --navy-dark: #001f4d;
      --navy-medium: #003380;
      --accent-gold: #f4c430;
      --soft-bg: #f8fafc;
      --text-dark: #1e293b;
      --text-muted: #64748b;
      --white: #ffffff;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: var(--soft-bg);
      color: var(--text-dark);
      overflow-x: hidden;
    }

    /* --- Content Section Styling --- */
    .section-title {
      font-weight: 700;
      color: var(--navy-dark);
      position: relative;
      display: inline-block;
      margin-bottom: 1rem;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 4px;
      background: var(--accent-gold);
      border-radius: 2px;
    }

    .subtitle {
      color: var(--text-muted);
      max-width: 650px;
      margin: 0 auto;
    }

    /* --- Info Boxes (Features) --- */
    .info-box {
      background: var(--white);
      padding: 2rem;
      border-radius: 20px;
      box-shadow: 0 4px 20px rgba(0, 31, 77, 0.05);
      border: 1px solid rgba(0, 31, 77, 0.05);
      transition: var(--transition);
      height: 100%;
      text-align: center;
    }

    .info-box:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 35px rgba(0, 31, 77, 0.12);
      border-color: var(--accent-gold);
    }

    .icon-wrapper {
      width: 70px;
      height: 70px;
      background: var(--soft-bg);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      margin: 0 auto 1.5rem;
      transition: var(--transition);
    }

    .info-box:hover .icon-wrapper {
      background: var(--navy-dark);
      color: var(--white) !important;
    }

    /* --- AI Card Specific --- */
    .ai-card {
      background: linear-gradient(135deg, #ffffff 0%, #f3f0ff 100%);
      border: 1px solid #dcd0ff;
    }
    .ai-icon {
      background: linear-gradient(135deg, #6f42c1, #001f4d);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    /* --- Hero Section --- */
    .hero {
      position: relative;
      height: 65vh;
      margin: 2rem;
      border-radius: 30px;
      background: url('images/hero.webp') center/cover no-repeat;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }

    .hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(45deg, rgba(0,31,77,0.9), rgba(0,31,77,0.4));
      z-index: 1;
    }

    .hero-content {
      position: relative;
      z-index: 2;
      color: white;
      text-align: center;
      width: 100%;
      padding: 0 20px;
    }

    .departments-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 15px;
      margin-top: 2.5rem;
    }

    .dept-link { text-decoration: none; }

    .department-box {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 20px;
      border-radius: 15px;
      color: white;
      font-weight: 600;
      transition: var(--transition);
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .department-box:hover {
      background: var(--accent-gold);
      color: var(--navy-dark);
      transform: scale(1.05);
      border-color: var(--accent-gold);
    }

    /* --- Announcements, Calendar, Feedback --- */
    .college-main-section { padding-bottom: 5rem; }

    .custom-card {
      background: var(--white);
      border-radius: 20px;
      padding: 1.8rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.05);
      height: 100%;
      border: none;
      transition: var(--transition);
    }

    .custom-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }

    .card-title {
      color: var(--navy-dark);
      font-weight: 700;
      font-size: 1.25rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .announcement-item {
      padding: 12px;
      border-radius: 12px;
      background: #f1f5f9;
      margin-bottom: 12px;
      cursor: pointer;
      transition: var(--transition);
      border-left: 4px solid transparent;
    }

    .announcement-item:hover {
      background: #e2e8f0;
      border-left-color: var(--accent-gold);
      padding-left: 18px;
    }

    .calendar-list { list-style: none; padding: 0; }
    .calendar-list li {
      padding: 10px 0;
      border-bottom: 1px dashed #e2e8f0;
      font-size: 0.95rem;
    }

    /* --- Feedback Form Styling --- */
    .feedback-form input, .feedback-form textarea {
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 12px;
      background: #f8fafc;
      width: 100%;
      margin-bottom: 15px;
      outline: none;
      transition: var(--transition);
    }

    .feedback-form input:focus, .feedback-form textarea:focus {
      border-color: var(--navy-dark);
      background: var(--white);
      box-shadow: 0 0 0 3px rgba(0, 31, 77, 0.1);
    }

    .btn-submit {
      background: var(--navy-dark);
      color: var(--white);
      border: none;
      padding: 12px;
      border-radius: 12px;
      font-weight: 600;
      width: 100%;
      transition: var(--transition);
    }

    .btn-submit:hover {
      background: var(--accent-gold);
      color: var(--navy-dark);
    }

    @media (max-width: 768px) {
      .hero { height: auto; padding: 3rem 1rem; margin: 10px; border-radius: 20px; }
      .hero-content h1 { font-size: 1.5rem; }
    }
  </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container py-5">
  <div class="text-center mb-5">
    <h2 class="section-title">Hamro <span style="color: var(--navy-medium);">Result</span> Hub</h2>
    <p class="subtitle">Access your academic journey in one place—results, study materials, and college updates.</p>
  </div>

  <div class="row g-4">
    <div class="col-lg-4 col-md-6">
      <div class="info-box">
        <div class="icon-wrapper" style="color: #3b82f6;"><i class="fas fa-university"></i></div>
        <h5>4 Departments</h5>
        <p class="text-muted mb-0">Specialized Engineering Programs</p>
      </div>
    </div>
    <div class="col-lg-4 col-md-6">
      <div class="info-box">
        <div class="icon-wrapper" style="color: #10b981;"><i class="fas fa-chart-line"></i></div>
        <h5>Result Management</h5>
        <p class="text-muted mb-0">Secure Semester-wise Digital Records</p>
      </div>
    </div>
    <div class="col-lg-4 col-md-6">
      <div class="info-box">
        <div class="icon-wrapper" style="color: #f59e0b;"><i class="fas fa-bullhorn"></i></div>
        <h5>Instant Notices</h5>
        <p class="text-muted mb-0">Stay Updated with Faculty News</p>
      </div>
    </div>
    <div class="col-lg-4 col-md-6">
      <div class="info-box">
        <div class="icon-wrapper" style="color: #6366f1;"><i class="fas fa-book-open"></i></div>
        <h5>Study Resources</h5>
        <p class="text-muted mb-0">Handwritten Notes & PDF Materials</p>
      </div>
    </div>
    <div class="col-lg-8 col-md-12">
      <div class="info-box ai-card">
        <div class="d-flex align-items-center justify-content-center h-100">
           <div class="me-4 d-none d-sm-block">
             <span class="fs-1 ai-icon"><i class="fas fa-robot"></i></span>
           </div>
           <div class="text-start">
             <h5 class="fw-bold">AI Assistance Features</h5>
             <p class="text-muted mb-0">Get smart performance analysis, grade predictions, and automated feedback powered by AI.</p>
           </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="hero" id="hero">
  <div class="hero-content container">
    <h1 class="display-5 fw-bold">Explore Our Departments</h1>
    <p class="opacity-75">Click a department to view specific results and academic structures.</p>
    
    <div class="departments-grid">
      <a href="department_results.php?dept=computer" class="dept-link">
        <div class="department-box" data-bg="images/diploma.jpeg">Computer Engineering</div>
      </a>
      <a href="department_results.php?dept=civil" class="dept-link">
        <div class="department-box" data-bg="images/civil.jpg">Civil Engineering</div>
      </a>
      <a href="department_results.php?dept=architecture" class="dept-link">
        <div class="department-box" data-bg="images/architecture.jpg">Architecture</div>
      </a>
      <a href="department_results.php?dept=it" class="dept-link">
        <div class="department-box" data-bg="images/computer1.jpg">Information Technology</div>
      </a>
    </div>
  </div>
</div>

<section class="college-main-section container mt-5">
  <div class="row g-4">
    
    <div class="col-lg-5 col-md-12">
      <div class="custom-card">
        <h4 class="card-title"><i class="fas fa-bell text-warning"></i> Recent Announcements</h4>
        <div class="announcement-list">
          <?php
          $notices = $conn->query("SELECT n.*, t.full_name as teacher FROM notices n LEFT JOIN teachers t ON n.teacher_id=t.id ORDER BY created_at DESC LIMIT 3");
          if($notices->num_rows > 0):
            while($n = $notices->fetch_assoc()):
          ?>
            <div class="announcement-item" onclick="location.href='notice_detail.php?id=<?= $n['id'] ?>'">
              <h6 class="mb-1 fw-bold"><?= $n['title'] ?></h6>
              <small class="text-muted"><i class="far fa-clock"></i> <?= date('M j, Y', strtotime($n['created_at'])) ?> | By <?= $n['teacher'] ?></small>
            </div>
          <?php endwhile; else: echo "<p>No notices found.</p>"; endif; ?>
        </div>
        <a href="college_updates.php" class="btn btn-link text-decoration-none mt-2 p-0">View All Updates →</a>
      </div>
    </div>

    <div class="col-lg-4 col-md-6">
      <div class="custom-card">
        <h4 class="card-title"><i class="fas fa-calendar-alt text-primary"></i> Academic Events</h4>
        <ul class="calendar-list">
          <?php
          $events = $conn->query("SELECT * FROM academic_events ORDER BY start_date ASC LIMIT 5");
          if($events->num_rows > 0):
            while($e = $events->fetch_assoc()):
          ?>
            <li><span class="fw-bold text-navy"><?= date('M d', strtotime($e['start_date'])) ?>:</span> <?= $e['title'] ?></li>
          <?php endwhile; else: echo "<li>No events listed.</li>"; endif; ?>
        </ul>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="custom-card">
        <h4 class="card-title"><i class="fas fa-comment-dots text-success"></i> Feedback</h4>
        <form action="submit_feedback.php" method="POST" class="feedback-form">
          <input type="text" name="student_name" placeholder="Your Name" required>
          <textarea name="feedback" rows="3" placeholder="How can we improve?" required></textarea>
          <button type="submit" class="btn-submit">Send 🚀</button>
        </form>
      </div>
    </div>

  </div>
</section>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Hero Background Hover Effect
  const hero = document.getElementById('hero');
  const deptBoxes = document.querySelectorAll('.department-box');
  const defaultBg = "linear-gradient(45deg, rgba(0,31,77,0.9), rgba(0,31,77,0.4)), url('images/hero.webp')";

  deptBoxes.forEach(box => {
    box.addEventListener('mouseenter', () => {
      const img = box.dataset.bg;
      hero.style.backgroundImage = `linear-gradient(45deg, rgba(0,31,77,0.8), rgba(0,31,77,0.3)), url('${img}')`;
    });
    box.addEventListener('mouseleave', () => {
      hero.style.backgroundImage = defaultBg;
    });
  });
</script>

</body>
</html>