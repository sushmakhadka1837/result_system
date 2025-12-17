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
  <title>PEC Result Hub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
  body {
      font-family: 'Poppins', sans-serif;
      background: #f5f6fa;
  }

  /* Content Section */
  .content-section {
      padding: 60px 20px;
      text-align: center;
      background: linear-gradient(to bottom, #ffffff, #f3f6ff);
      border-radius: 14px;
      margin-bottom: 40px;
  }
  .content-section h2 {
      font-weight: 700;
      color: #001f4d;
  }
  .content-section .subtitle {
      font-size: 1rem;
      color: #444;
      max-width: 700px;
      margin: 10px auto 0;
  }
  .info-box {
      background: #ffffff;
      padding: 20px 15px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      transition: transform 0.3s, box-shadow 0.3s;
      text-align: center;
  }
  .info-box h5 {
      margin-top: 10px;
      font-size: 1rem;
      font-weight: 600;
      color: #001f4d;
  }
  .info-box p {
      font-size: 0.85rem;
      color: #555;
      margin-bottom: 0;
  }
  .info-box:hover {
      transform: translateY(-6px);
      box-shadow: 0 10px 22px rgba(0,0,0,0.15);
  }

  /* Hero Section */
  .hero {
    position: relative;
    height: 50vh;
    background: url('images/hero.webp') center/cover no-repeat;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-image 0.5s ease-in-out;
    margin-bottom: 40px;
  }
  .hero::after {
      content: '';
      position: absolute;
      inset: 0;
      background: rgba(0,0,0,0.55);
  }
  .hero-content {
      position: relative;
      z-index: 2;
      text-align: center;
      max-width: 800px;
      padding: 10px 20px;
  }
  .hero-content h1 { font-size: 2rem; }
  .hero-content p { font-size: 1rem; margin-bottom: 15px; }

  /* Departments Grid */
  .departments-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit,minmax(200px,1fr));
      gap: 15px;
  }
  .department-box {
      border: 2px solid #ffdd57;
      border-radius: 10px;
      background: rgba(255,255,255,0.1);
      padding: 20px;
      color: white;
      cursor: pointer;
      transition: all 0.3s ease-in-out;
      text-align: center;
  }
  .department-box:hover {
      background: rgba(255,255,255,0.25);
      transform: scale(1.05);
      box-shadow: 0 6px 15px rgba(0,0,0,0.15);
  }

  /* Announcements & Events Section */
  .announcement-section, .upcoming-events-section {
    background: #fff;
    padding: 15px; /* reduce padding */
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); /* lighter shadow */
}
.announcement-cards-container {
    display: flex;
    flex-direction: column;
    gap: 10px; /* smaller gap between cards */
}

.announcement-card {
    padding: 10px 15px; /* smaller padding */
    border-radius: 6px;
    font-size: 0.9rem; /* smaller font */
}
  .announcement-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  }
  .announcement-card h5 {
      margin: 0 0 5px 0;
      font-weight: 600;
      display: flex;
      justify-content: space-between;
      align-items: center;
  }
  .announcement-card h5 span {
      font-size: 0.75rem;
      font-weight: 500;
      color: #555;
      background-color: rgba(0,0,0,0.05);
      padding: 2px 6px;
      border-radius: 4px;
  }
  .announcement-card p { font-size: 0.95rem; color: #333; margin: 0 0 8px 0; }
  .announcement-card small { font-size: 0.8rem; color: #777; }

  .upcoming-events-section h4 {
    font-size: 1.1rem; /* smaller title */
}


  /* Side by side layout */
  .main-events-section {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    max-width: 1200px;
    margin: 20px auto; /* reduce top/bottom margin */
}
  .main-events-section > div {
      flex: 1 1 0;
  }

  /* Responsive */
  @media(max-width:992px){
      .main-events-section { flex-direction: column; }
  }
  </style>
</head>
<body>

<?php include 'header.php'; ?>

<!-- Main Content -->
<div class="container content-section">
  <h2>Hamro Result</h2>
  <p class="subtitle">
    A centralized academic platform to access results, notices, notes, and departmental updates.
  </p>

  <div class="row mt-4 g-3 justify-content-center">
    <div class="col-md-3 col-sm-6">
      <div class="info-box">🎓<h5>4 Departments</h5><p>Engineering Programs</p></div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="info-box">📊<h5>Result Management</h5><p>Semester-wise Records</p></div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="info-box">📢<h5>Latest Notices</h5><p>Department Updates</p></div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="info-box">📚<h5>Study Resources</h5><p>Notes & Materials</p></div>
    </div>
  </div>
</div>

<!-- Hero Section -->
<div class="hero" id="hero">
  <div class="hero-content">
    <h1>Departments at Pokhara Engineering College</h1>
    <p>Explore our diverse departments dedicated to academic excellence and innovation.</p>
    <div class="departments-grid mt-3">
      <a href="computer_department.php"><div class="department-box" data-bg="images/diploma.jpeg">Bachelor of Computer Engineering</div></a>
      <a href="civil_department.php"><div class="department-box" data-bg="images/civil.jpg">Bachelor of Civil Engineering</div></a>
      <a href="architecture_department.php"><div class="department-box" data-bg="images/architecture.jpg">Bachelor of Architecture</div></a>
      <a href="beit_department.php"><div class="department-box" data-bg="images/computer1.jpg">Bachelor of Information Technology</div></a>
    </div>
  </div>
</div>

<script>
const hero = document.getElementById('hero');
const deptBoxes = document.querySelectorAll('.department-box');
const defaultBg = "url('images/hero.webp')";
deptBoxes.forEach(box => {
  box.addEventListener('mouseenter', () => { hero.style.backgroundImage = `url('${box.dataset.bg}')`; });
  box.addEventListener('mouseleave', () => { hero.style.backgroundImage = defaultBg; });
  box.addEventListener('click', () => {
      const deptName = box.textContent.trim().toLowerCase().replace(/ /g,'_');
      window.location.href = `department_results.php?dept=${deptName}`;
  });
});
</script>

<!-- Announcements + Upcoming Events -->
<!-- Events & Announcements Section -->
<div class="row main-events-section" style="gap:20px; margin:40px auto; max-width:1200px;">

  <!-- Left Column: Announcements -->
  <div class="col-lg-7 col-md-12">
    <div class="announcement-section">
        <h2>📢 Recent Announcements</h2>
        <div class="announcement-cards-container">
        <?php
        $dept_notices = $conn->query("
            SELECT n.id, n.title, n.message, n.notice_type, n.created_at, 
                   t.full_name AS teacher_name, 
                   CASE WHEN n.department_id = 0 THEN 'All Departments' ELSE d.department_name END AS department_name
            FROM notices n
            LEFT JOIN teachers t ON n.teacher_id = t.id
            LEFT JOIN departments d ON n.department_id = d.id
            ORDER BY n.created_at DESC
            LIMIT 2
        ");

        if($dept_notices && $dept_notices->num_rows > 0){
            while($n = $dept_notices->fetch_assoc()){
                switch($n['notice_type']){
                    case 'general': $color = '#1a73e8'; break;
                    case 'exam': $color = 'orange'; break;
                    case 'internal': $color = 'red'; break;
                    default: $color = '#1a73e8';
                }

                $cat_label = ucfirst($n['notice_type']);

                echo "
                <div class='announcement-card' style='--card-color: {$color};' 
                    onclick=\"location.href='notice_detail.php?id={$n['id']}'\">
                    <h5>{$n['title']} <span>($cat_label)</span></h5>
                    <p>".substr($n['message'],0,200)."...</p>
                    <small>Department: {$n['department_name']} | By: {$n['teacher_name']}</small>
                </div>";
            }
        } else {
            echo "<p class='no-announcements'>No recent announcements yet.</p>";
        }
        ?>
        </div>
        <div class='text-end'>
            <a href='college_updates.php'>View All 📢</a>
        </div>
    </div>
  </div>

  <!-- Right Column: Calendar-style Upcoming Events -->
  <div class="col-lg-5 col-md-12">
    <div class="calendar-box">
      <div class="calendar-header">
        <span class="calendar-icon">📅</span>
        <h4>Upcoming Events</h4>
      </div>
      <ul class="calendar-events-list" style="list-style:none; padding:0;">
        <?php
        $events_query = $conn->query("SELECT * FROM academic_events ORDER BY start_date ASC LIMIT 10");
        if($events_query && $events_query->num_rows > 0){
            while($event = $events_query->fetch_assoc()){
                $start = date('M j', strtotime($event['start_date']));
                $end = $event['end_date'] ? date('M j', strtotime($event['end_date'])) : '';
                $display_date = $end && $start != $end ? "$start - $end" : $start;
                echo "<li><strong>$display_date:</strong> " . htmlspecialchars($event['title']) . "</li>";
            }
        } else {
            echo "<li>No upcoming events</li>";
        }
        ?>
      </ul>
    </div>
  </div>

</div>

<style>
/* Calendar Box */
.calendar-box {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.calendar-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 22px rgba(0,0,0,0.15);
}

.calendar-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.calendar-icon {
    font-size: 1.8rem;
}

.calendar-events-list li {
    font-size: 0.95rem;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.calendar-events-list li:last-child {
    border-bottom: none;
}

/* Responsive */
@media(max-width:992px){
    .main-events-section {
        flex-direction: column;
    }
}
</style>


<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
