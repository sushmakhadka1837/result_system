<?php
session_start();
require 'db_config.php';

// PHPMailer (Keep this for the announcement logic)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; 

if (!isset($_SESSION['teacher_id']) || $_SESSION['user_type'] != 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

/* ---------- FETCH TEACHER INFO ---------- */
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id=?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

/* ---------- HOD CHECK ---------- */
$hod_department = null;
$hod_stmt = $conn->prepare("SELECT department_name FROM departments WHERE hod_id = ? LIMIT 1");
$hod_stmt->bind_param("i", $teacher_id);
$hod_stmt->execute();
$hod_res = $hod_stmt->get_result();
if($hod_res->num_rows > 0){
    $hod_department = $hod_res->fetch_assoc()['department_name'];
}

/* ---------- UNREAD MESSAGES ---------- */
$stmt2 = $conn->prepare("SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_id=? AND is_read=0");
$stmt2->bind_param("i", $teacher_id);
$stmt2->execute();
$unread_count = $stmt2->get_result()->fetch_assoc()['unread_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard | Academic Portal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

    <style>
        :root {
            --primary-blue: #4318FF;
            --soft-bg: #F4F7FE;
            --navy: #1B2559;
            --grey: #A3AED0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--soft-bg);
            color: var(--navy);
        }

        /* Profile Sidebar */
        .profile-sidebar {
            background: white;
            border-radius: 24px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: none;
        }
        .profile-sidebar img {
            width: 100px; height: 100px;
            border-radius: 50%;
            border: 4px solid #F4F7FE;
            transition: 0.3s;
            object-fit: cover;
        }

        /* Announcement Card Styling */
        .content-card {
            background: white;
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: none;
            margin-bottom: 25px;
        }

        /* Responsive Calendar Fixes */
        #calendar {
            background: #fff;
            padding: 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        /* Mobile Responsive */
        @media (max-width: 992px) {
            .profile-sidebar { margin-bottom: 20px; }
            .content-card { padding: 20px; margin-bottom: 20px; }
        }

        @media (max-width: 768px) {
            .profile-sidebar { padding: 20px; }
            .profile-sidebar img { width: 80px; height: 80px; }
            .content-card { padding: 15px; border-radius: 16px; }
            #calendar { font-size: 0.7rem; padding: 8px; }
            .fc-toolbar { flex-direction: column; gap: 10px; }
            .fc-toolbar-chunk { width: 100%; justify-content: center; }
        }

        @media (max-width: 576px) {
            .profile-sidebar { padding: 15px; }
            .profile-sidebar img { width: 70px; height: 70px; }
            .content-card { padding: 12px; }
            #calendar { font-size: 0.65rem; }
        }
        .fc .fc-toolbar-title { font-size: 1.1rem; font-weight: 700; color: var(--navy); }
        .fc .fc-button-primary { background: var(--primary-blue); border: none; }
        
        /* Notice Form Inputs */
        .custom-input {
            background: var(--soft-bg);
            border: 1px solid transparent;
            border-radius: 12px;
            padding: 10px 15px;
            font-weight: 500;
        }
        .custom-input:focus {
            background: #fff;
            border-color: var(--primary-blue);
            box-shadow: none;
        }
        
        .btn-publish {
            background: var(--primary-blue);
            color: white;
            border-radius: 12px;
            padding: 12px;
            font-weight: 700;
            border: none;
            transition: 0.3s;
        }
        .btn-publish:hover { background: #3311CC; transform: translateY(-2px); }

        @media (max-width: 768px) {
            .profile-sidebar { margin-bottom: 20px; }
        }
    </style>
</head>
<body>

<?php include 'teacher_header.php'; ?>

<div class="container-fluid px-4 mt-4">
    <div class="row">
        
        <div class="col-lg-3">
            <div class="profile-sidebar shadow-sm">
                <form action="upload_teacher_profile.php" method="post" enctype="multipart/form-data">
                    <label for="profile_photo" style="position: relative; cursor: pointer;">
                        <img src="<?= !empty($teacher['profile_pic']) ? $teacher['profile_pic'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png'; ?>">
                        <div style="position: absolute; bottom: 5px; right: 5px; background: var(--primary-blue); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid white;">
                            <i class="bi bi-camera-fill" style="font-size: 12px;"></i>
                        </div>
                    </label>
                    <input type="file" name="profile_pic" id="profile_photo" class="d-none" onchange="this.form.submit()">
                </form>

                <h5 class="fw-bold mt-3 mb-1"><?= htmlspecialchars($teacher['full_name']); ?></h5>
                
                <?php if($hod_department): ?>
                    <span class="badge rounded-pill mb-3" style="background: rgba(67, 24, 255, 0.1); color: var(--primary-blue);">HOD: <?= htmlspecialchars($hod_department); ?></span>
                <?php else: ?>
                    <span class="badge bg-light text-secondary rounded-pill mb-3 border">Faculty Member</span>
                <?php endif; ?>

                <div class="text-start mt-4 px-2">
                    <div class="small text-muted mb-1">Email Address</div>
                    <div class="small fw-bold mb-3"><?= htmlspecialchars($teacher['email']); ?></div>
                    
                    <div class="small text-muted mb-1">Employee ID</div>
                    <div class="small fw-bold mb-3"><?= htmlspecialchars($teacher['employee_id']); ?></div>
                </div>

                    
                </button>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="row g-4">
                
                <div class="col-md-7">
                    <div class="content-card h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold m-0"><i class="bi bi- megaphone-fill text-primary me-2"></i> Create Announcement</h5>
                            <a href="manage_announcements.php" class="text-decoration-none small fw-bold">View History</a>
                        </div>
                        
                        <?php include 'announcement.php'; ?>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="content-card h-100">
                        <h5 class="fw-bold mb-4 border-bottom pb-2">
                            <i class="bi bi-calendar-week text-primary me-2"></i> Academic Calendar
                        </h5>
                        
                        <div id="calendar"></div>
                        
                        <div class="mt-3 small d-flex gap-3">
                            <span><i class="bi bi-circle-fill text-primary me-1"></i> Events</span>
                            <span><i class="bi bi-circle-fill text-success me-1"></i> Notices</span>
                        </div>
                    </div>
                </div>

            </div> <div class="row mt-4">
                <div class="col-12">
                    <div class="content-card">
                        <h5 class="fw-bold mb-4 border-bottom pb-2">
                            <i class="bi bi-bar-chart-fill text-primary me-2"></i> Result Analysis
                        </h5>
                        
                    </div>
                </div>
            </div>
        </div>

    </div> </div>

<?php include 'footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: window.innerWidth < 768 ? 'listMonth' : 'dayGridMonth',
            height: 'auto',
            headerToolbar: {
                left: 'prev,next',
                center: 'title',
                right: ''
            },
            events: [
                <?php
                // 1. Fetch Academic Events
                $ev_res = $conn->query("SELECT title, start_date FROM academic_events");
                while($e = $ev_res->fetch_assoc()){
                    echo "{ title: '".addslashes($e['title'])."', start: '".$e['start_date']."', color: '#4318FF' },";
                }
                // 2. Fetch Teacher's Notices
                $nt_res = $conn->query("SELECT title, created_at FROM notices WHERE teacher_id = $teacher_id");
                while($n = $nt_res->fetch_assoc()){
                    echo "{ title: 'Notice: ".addslashes($n['title'])."', start: '".date('Y-m-d', strtotime($n['created_at']))."', color: '#05cd99' },";
                }
                ?>
            ],
            windowResize: function(view) {
                if (window.innerWidth < 768) {
                    calendar.changeView('listMonth');
                } else {
                    calendar.changeView('dayGridMonth');
                }
            }
        });
        calendar.render();
    });
</script>

</body>
</html>