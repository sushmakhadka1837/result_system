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
$stmt2 = $conn->prepare("SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_id=? AND sender_type='student' AND is_read=0");
$stmt2->bind_param("i", $teacher_id);
$stmt2->execute();
$unread_count = (int)($stmt2->get_result()->fetch_assoc()['unread_count'] ?? 0);

$absence_table_exists = false;
$pending_absence_count = 0;
$absence_table_check = $conn->query("SHOW TABLES LIKE 'student_absence_requests'");
if ($absence_table_check && $absence_table_check->num_rows > 0) {
    $absence_table_exists = true;
    $pending_absence_count = (int)($conn->query("SELECT COUNT(*) AS cnt FROM student_absence_requests WHERE status='pending'")->fetch_assoc()['cnt'] ?? 0);
}

$recheck_table_exists = false;
$pending_recheck_count = 0;
$recheck_table_check = $conn->query("SHOW TABLES LIKE 'assessment_recheck_requests'");
if ($recheck_table_check && $recheck_table_check->num_rows > 0) {
    $recheck_table_exists = true;
    $rc_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM assessment_recheck_requests WHERE assigned_teacher_id = ? AND status = 'pending'");
    $rc_stmt->bind_param("i", $teacher_id);
    $rc_stmt->execute();
    $pending_recheck_count = (int)($rc_stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
}
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
            --card-radius: 20px;
            --card-border: #e8ecfb;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--soft-bg);
            color: var(--navy);
            min-height: 100vh;
        }

        .dashboard-shell {
            padding-bottom: 28px;
        }

        .profile-sidebar,
        .content-card,
        .stat-card {
            background: #fff;
            border-radius: var(--card-radius);
            border: 1px solid var(--card-border);
            box-shadow: 0 10px 30px rgba(17, 34, 68, 0.04);
        }

        .profile-sidebar {
            padding: 28px;
            text-align: center;
            position: sticky;
            top: 96px;
        }

        .profile-photo-wrap {
            position: relative;
            cursor: pointer;
            display: inline-flex;
        }

        .profile-sidebar img {
            width: 104px;
            height: 104px;
            border-radius: 50%;
            border: 4px solid #eef2ff;
            object-fit: cover;
        }

        .camera-badge {
            position: absolute;
            bottom: 4px;
            right: 4px;
            background: var(--primary-blue);
            color: #fff;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
        }

        .content-card {
            padding: 22px;
        }

        .section-title {
            font-weight: 700;
            margin: 0;
            color: #18224f;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        .stat-card {
            padding: 14px 16px;
        }

        .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7aa7;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 1.35rem;
            font-weight: 700;
            line-height: 1;
            color: #1b2559;
        }

        .request-list {
            display: grid;
            gap: 12px;
        }

        .request-item {
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            background: #fff;
        }

        .count-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            height: 30px;
            padding: 0 10px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
            background: #eef2ff;
            color: var(--primary-blue);
            margin-left: 6px;
        }

        .count-pill.alert {
            background: #ffecef;
            color: #d11a2a;
        }

        #calendar {
            background: #fff;
            border: 1px solid var(--card-border);
            border-radius: 15px;
            padding: 10px;
            font-size: 0.82rem;
        }

        .fc .fc-toolbar-title { font-size: 1.05rem; font-weight: 700; color: var(--navy); }
        .fc .fc-button-primary { background: var(--primary-blue); border: none; }

        @media (max-width: 1199px) {
            .stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 992px) {
            .profile-sidebar {
                margin-bottom: 18px;
                position: static;
            }
            .content-card { padding: 18px; }
        }

        @media (max-width: 768px) {
            .stat-grid {
                grid-template-columns: 1fr;
            }
            .request-item {
                flex-direction: column;
                align-items: flex-start;
            }
            #calendar { font-size: 0.72rem; padding: 8px; }
            .fc-toolbar { flex-direction: column; gap: 8px; }
            .fc-toolbar-chunk { width: 100%; display: flex; justify-content: center; }
        }
    </style>
</head>
<body>

<?php include 'teacher_header.php'; ?>

<div class="container-fluid px-4 mt-4 dashboard-shell">
    <div class="row">
        
        <div class="col-lg-3">
            <div class="profile-sidebar shadow-sm">
                <form action="upload_teacher_profile.php" method="post" enctype="multipart/form-data">
                    <label for="profile_photo" class="profile-photo-wrap">
                        <img src="<?= !empty($teacher['profile_pic']) ? $teacher['profile_pic'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png'; ?>">
                        <div class="camera-badge">
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
            </div>
        </div>

        <div class="col-lg-9">
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-label">Unread Messages</div>
                    <div class="stat-value"><?= $unread_count ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending Absence Requests</div>
                    <div class="stat-value"><?= $absence_table_exists ? $pending_absence_count : 0 ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending Recheck Requests</div>
                    <div class="stat-value"><?= $recheck_table_exists ? $pending_recheck_count : 0 ?></div>
                </div>
            </div>

            <div class="row g-4">
                
                <div class="col-md-7">
                    <div class="content-card h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="section-title"><i class="bi bi-megaphone-fill text-primary"></i> Create Announcement</h5>
                            <a href="manage_announcements.php" class="text-decoration-none small fw-bold">View History</a>
                        </div>
                        
                        <?php include 'announcement.php'; ?>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="content-card h-100">
                        <h5 class="section-title mb-4 border-bottom pb-2">
                            <i class="bi bi-calendar-week text-primary me-2"></i> Academic Calendar
                        </h5>
                        
                        <div id="calendar"></div>
                        
                        <div class="mt-3 small d-flex gap-3">
                            <span><i class="bi bi-circle-fill text-primary me-1"></i> Events</span>
                            <span><i class="bi bi-circle-fill text-success me-1"></i> Notices</span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row mt-2">
                <div class="col-12">
                    <div class="content-card">
                        <h5 class="section-title mb-4 border-bottom pb-2">
                            <i class="bi bi-list-check text-primary"></i> Request Management
                        </h5>

                        <div class="request-list">
                            <div class="request-item">
                                <div>
                                    <div class="fw-semibold">Student Absence Requests
                                        <?php if ($absence_table_exists): ?>
                                            <span class="count-pill <?= $pending_absence_count > 0 ? 'alert' : '' ?>"><?= $pending_absence_count ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($absence_table_exists): ?>
                                        <small class="text-muted">Review submitted absence documents and exam/class leave requests.</small>
                                    <?php else: ?>
                                        <small class="text-warning">Module not initialized. Run create_absence_requests_table.sql</small>
                                    <?php endif; ?>
                                </div>
                                <a href="teacher_absence_requests.php" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-person-lines-fill me-1"></i> Open Panel
                                </a>
                            </div>

                            <div class="request-item">
                                <div>
                                    <div class="fw-semibold">Assessment Re-total / Recheck
                                        <?php if ($recheck_table_exists): ?>
                                            <span class="count-pill <?= $pending_recheck_count > 0 ? 'alert' : '' ?>"><?= $pending_recheck_count ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($recheck_table_exists): ?>
                                        <small class="text-muted">Check and review pending re-total/recheck submissions assigned to you.</small>
                                    <?php else: ?>
                                        <small class="text-warning">Run create_assessment_recheck_requests_table.sql</small>
                                    <?php endif; ?>
                                </div>
                                <a href="teacher_assessment_recheck_requests.php" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-arrow-repeat me-1"></i> Open Panel
                                </a>
                            </div>
                        </div>
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