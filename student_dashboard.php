<?php
session_start();
require 'db_config.php';
require_once 'common.php'; 

if (!isset($_SESSION['student_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch student details
$stmt = $conn->prepare("SELECT s.*, d.department_name FROM students s LEFT JOIN departments d ON s.department_id=d.id WHERE s.id=?");
$stmt->bind_param("i",$student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Auto-select current semester logic
$filter_batch = $student['batch_year'];
$filter_department = $student['department_id'];
$current_semester_order = getCurrentSemester($filter_batch);

$stmt_sem = $conn->prepare("SELECT id FROM semesters WHERE department_id=? AND semester_order=?");
$stmt_sem->bind_param("ii", $filter_department, $current_semester_order);
$stmt_sem->execute();
$result_sem = $stmt_sem->get_result();
$filter_semester = ($row_sem = $result_sem->fetch_assoc()) ? $row_sem['id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PEC Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --navy: #001f4d;
            --navy-light: #003366;
            --gold: #f4c430;
            --bg-gray: #f8fafc; /* Clean background */
            --border-color: #e2e8f0;
        }

        body { 
            background-color: var(--bg-gray); 
            font-family: 'Inter', 'Poppins', sans-serif; 
            color: #1e293b; 
        }
        
        .dashboard-container { padding: 30px 0 60px; }

        /* Profile Card - White & Clean */
        .profile-sidebar {
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            position: sticky;
            top: 100px;
        }
        .profile-header-accent {
            background: #adb5bd;
            height: 90px;
        }
        .profile-img-container {
            margin-top: -55px;
            text-align: center;
        }
        .profile-img-container img {
            width: 115px;
            height: 115px;
            border-radius: 50%;
            border: 6px solid white;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .profile-body { padding: 20px 25px 30px; text-align: center; }
        .profile-body h4 { font-weight: 700; color: var(--navy); margin-bottom: 4px; }
        .dept-tag {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .info-list { text-align: left; margin-top: 25px; }
        .sem-highlight {
            background: #fff9e6;
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #475569;
        }
        .info-item i { color: var(--navy-light); width: 18px; text-align: center; }

        /* Welcome Card - Dynamic Gradient */
        .welcome-card {
            background: linear-gradient(135deg, var(--navy) 0%, #004080 100%);
            border-radius: 24px;
            padding: 40px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        .welcome-card h2 { font-size: 1.8rem; margin-bottom: 10px; }

        /* Content Sections */
        .section-card {
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }
        .card-title-custom {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .edit-btn {
            background: #f1f5f9;
            color: var(--navy);
            border-radius: 12px;
            width: 100%;
            margin-top: 20px;
            font-weight: 600;
            border: none;
            padding: 12px;
            transition: 0.3s;
        }
        .edit-btn:hover { background: var(--navy); color: white; }

        /* Mobile Responsive */
        @media (max-width: 992px) {
            .profile-sidebar {
                position: relative;
                top: 0;
                margin-bottom: 20px;
            }
            .dashboard-container { padding: 15px 0 30px; }
            .welcome-card { padding: 25px; }
            .welcome-card h2 { font-size: 1.4rem; }
            .section-card { padding: 20px; }
        }

        @media (max-width: 768px) {
            .profile-header-accent { height: 70px; }
            .profile-img-container { margin-top: -40px; }
            .profile-img-container img { width: 85px; height: 85px; border-width: 4px; }
            .profile-body { padding: 15px 20px 25px; }
            .profile-body h4 { font-size: 1.1rem; }
            .dept-tag { font-size: 0.8rem; }
            .info-item { font-size: 0.85rem; padding: 10px 0; }
            .sem-highlight { padding: 10px; gap: 10px; }
            .welcome-card { padding: 20px; margin-bottom: 20px; }
            .welcome-card h2 { font-size: 1.2rem; }
            .section-card { padding: 15px; }
            .card-title-custom { font-size: 1rem; margin-bottom: 15px; }
        }

        @media (max-width: 576px) {
            .dashboard-container { padding: 10px 0 20px; }
            .profile-img-container img { width: 75px; height: 75px; }
            .profile-body h4 { font-size: 1rem; }
            .info-item { flex-direction: column; align-items: flex-start; gap: 5px; font-size: 0.8rem; }
            .info-item i { margin-bottom: 5px; }
            .welcome-card { padding: 15px; }
            .welcome-card h2 { font-size: 1rem; }
        }
    </style>
</head>
<body>

<?php include 'student_header.php'; ?>

<div class="container dashboard-container">
    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="profile-sidebar">
                <div class="profile-header-accent"></div>
                <div class="profile-img-container">
                    <form action="upload_profile.php" method="post" enctype="multipart/form-data" id="profileForm">
                        <label for="profile_photo" style="cursor: pointer;">
                            <img src="<?php echo !empty($student['profile_photo']) ? $student['profile_photo'] : 'images/default.png'; ?>" alt="Student Photo">
                        </label>
                        <input type="file" name="profile_photo" id="profile_photo" style="display:none;" onchange="document.getElementById('profileForm').submit()">
                    </form>
                </div>
                <div class="profile-body">
                    <h4><?php echo htmlspecialchars($student['full_name']); ?></h4>
                    <span class="dept-tag"><i class="fas fa-university me-1"></i> <?php echo htmlspecialchars($student['department_name']); ?></span>
                    
                    <div class="info-list">
                        <div class="sem-highlight">
                            <div class="icon-circle bg-white shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 10px;">
                                <i class="fas fa-graduation-cap text-warning"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.7rem; text-transform: uppercase;">Active Status</small>
                                <span class="fw-bold text-dark"><?php echo ($current_semester_order > 0) ? $current_semester_order . "th Semester" : "N/A"; ?></span>
                            </div>
                        </div>

                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($student['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($student['phone']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-calendar-check"></i>
                            <span>Batch Year: <?php echo htmlspecialchars($student['batch_year']); ?></span>
                        </div>
                    </div>
                    
                    <button class="edit-btn" onclick="window.location.href='student_edit_profile.php'">
                        <i class="fas fa-fingerprint me-2"></i> Update Profile
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            

            <div class="mb-4">
               
            </div>

            <div class="section-card">
                <h4 class="card-title-custom">
                    <i class="fas fa-bullhorn text-warning"></i> Recent Announcements
                </h4>
                <hr class="text-muted opacity-25">
                <?php include 'view_student_notice.php'; ?>
            </div>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>