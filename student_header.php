<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';

// 1. Initialize variables to avoid errors
$student_id = $_SESSION['student_id'] ?? 0;
$unread_count = 0; 
$published_results = []; 

if ($student_id) {
    /* ---------- 2. Unread Messages Count ---------- */
    $stmt3 = $conn->prepare("SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_id=? AND is_read=0");
    $stmt3->bind_param("i", $student_id);
    $stmt3->execute();
    $unread_count = $stmt3->get_result()->fetch_assoc()['unread_count'];

    /* ---------- 3. Check Department & Published Results ---------- */
    $stu_query = $conn->prepare("SELECT department_id FROM students WHERE id = ?");
    $stu_query->bind_param("i", $student_id);
    $stu_query->execute();
    $stu_res = $stu_query->get_result()->fetch_assoc();

    if ($stu_res) {
        $dept_id = $stu_res['department_id'];

        $res_status = $conn->prepare("
            SELECT DISTINCT LOWER(TRIM(result_type)) as r_type 
            FROM results_publish_status 
            WHERE department_id = ? AND published = 1
        ");
        $res_status->bind_param("i", $dept_id);
        $res_status->execute();
        $res_data = $res_status->get_result();

        while ($r = $res_data->fetch_assoc()) {
            $published_results[] = $r['r_type'];
        }
    }
}

// Current Page check for Active Class
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="header-section no-print">
    <div class="header-container">
        <div class="logo-area">
            <a href="student_dashboard.php" class="logo-link">
                <div class="logo-circle">
                    <img src="images/logoheader.png" alt="PEC Logo" onerror="this.src='https://via.placeholder.com/50'">
                </div>
                <div class="logo-text">
                    <span class="brand-name">PEC</span>
                    <span class="portal-name">Student Portal</span>
                </div>
            </a>
        </div>

        <nav class="nav-links">
             <a href="index.php" class="nav-item <?= ($current_page == 'index.php') ? 'active' : '' ?>">
                <i class="fas fa-home"></i> <span>Home</span>
            </a>

            <a href="student_dashboard.php" class="nav-item <?= ($current_page == 'student_dashboard.php') ? 'active' : '' ?>">
                <i class="fas fa-user-circle"></i> <span>Profile</span>
            </a>

            <?php if (!empty($published_results)): ?>
                <div class="result-dropdown">
                    <button class="result-btn" id="resultBtn">
                        <i class="fas fa-chart-line"></i> <span>Result</span> <i class="fas fa-chevron-down caret-icon"></i>
                    </button>
                    <div class="result-menu shadow" id="resultMenu">
                        <?php if (in_array('ut', $published_results)): ?>
                            <a href="view_student_ut_result.php"><i class="fas fa-file-invoice me-2"></i> UT Exam Result</a>
                        <?php endif; ?>
                        
                        <?php if (in_array('assessment', $published_results)): ?>
                            <a href="view_student_assessment_result.php"><i class="fas fa-graduation-cap me-2"></i> Final Assessment</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <a href="student_announcement.php" class="nav-item <?= ($current_page == 'student_announcement.php') ? 'active' : '' ?>">
                <i class="fas fa-bullhorn"></i> <span>Notices</span>
            </a>

            <a href="student_notes.php" class="nav-item <?= ($current_page == 'student_notes.php') ? 'active' : '' ?>">
                <i class="fas fa-book-open"></i> <span>Notes</span>
            </a>
            
            <a href="student_chat.php" class="nav-item <?= ($current_page == 'student_chat.php') ? 'active' : '' ?>" id="nav-messages" style="position: relative;">
                <i class="fas fa-comment-dots"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="unread-badge"><?= ($unread_count > 9) ? '9+' : $unread_count; ?></span>
                <?php endif; ?>
            </a>

            <div class="nav-divider"></div>

            <a href="logout.php" class="logout-pill">
                <i class="fas fa-power-off"></i> <span>Logout</span>
            </a>
        </nav>
    </div>
</div>

<style>
:root {
    --navy: #001f4d;
    --navy-light: #002d6b;
    --gold: #f4c430;
    --white: #ffffff;
    --smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.header-section {
    background: var(--navy);
    position: sticky;
    top: 0;
    z-index: 1000;
    padding: 12px 0;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.header-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 30px;
}

.logo-link { display: flex; align-items: center; text-decoration: none; }

.logo-circle {
    width: 44px; height: 44px;
    background: var(--white);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    padding: 2px;
    transition: var(--smooth);
}
.logo-circle img { width: 100%; height: 100%; object-fit: contain; border-radius: 50%; }

.logo-text { display: flex; flex-direction: column; margin-left: 12px; }
.brand-name { color: var(--gold); font-size: 1.6rem; font-weight: 850; line-height: 1; letter-spacing: -0.5px; }
.portal-name { color: rgba(255,255,255,0.6); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }

.nav-links { display: flex; align-items: center; gap: 8px; }

.nav-item, .result-btn {
    background: transparent; border: none;
    color: rgba(255,255,255,0.85);
    padding: 10px 18px; border-radius: 12px;
    font-weight: 500; font-size: 0.92rem;
    transition: var(--smooth);
    display: flex; align-items: center; gap: 10px;
    text-decoration: none; cursor: pointer;
}

.nav-item:hover, .result-btn:hover {
    background: rgba(255, 255, 255, 0.08);
    color: var(--white);
    transform: translateY(-1px);
}

.nav-item.active { 
    background: rgba(244, 196, 48, 0.12); 
    color: var(--gold); 
    font-weight: 600;
}

/* Dropdown */
.result-dropdown { position: relative; }
.result-menu {
    display: none;
    position: absolute;
    top: calc(100% + 15px);
    right: 0;
    background: white;
    min-width: 220px;
    border-radius: 16px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.3);
    padding: 8px;
    z-index: 1001;
    animation: dropdownAnim 0.3s cubic-bezier(0.18, 0.89, 0.32, 1.28) forwards;
}

@keyframes dropdownAnim {
    from { opacity: 0; transform: translateY(15px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.result-menu a {
    display: flex; align-items: center;
    padding: 12px 15px;
    color: var(--navy) !important;
    font-size: 0.88rem; font-weight: 600;
    border-radius: 10px;
    transition: var(--smooth); text-decoration: none;
}
.result-menu a:hover { background: #f0f4f8; color: var(--navy-light) !important; transform: translateX(5px); }

.caret-icon { font-size: 0.7rem; opacity: 0.7; transition: 0.3s; }

.unread-badge {
    position: absolute; top: -2px; right: 2px;
    background: #ff3b3b; color: white;
    font-size: 0.65rem; min-width: 18px; height: 18px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 50px; font-weight: 800;
    border: 2px solid var(--navy);
}

.logout-pill {
    background: rgba(255, 59, 59, 0.1); color: #ff8080;
    padding: 10px 20px; border-radius: 50px;
    font-weight: 700; text-decoration: none;
    display: flex; align-items: center; gap: 8px; transition: var(--smooth);
    border: 1px solid rgba(255, 59, 59, 0.05);
}
.logout-pill:hover { background: #ff3b3b; color: white; box-shadow: 0 4px 15px rgba(255, 59, 59, 0.3); }

.nav-divider { width: 1px; height: 30px; background: rgba(255,255,255,0.1); margin: 0 12px; }

@media (max-width: 1100px) {
    .nav-item span, .result-btn span, .logout-pill span { display: none; }
    .nav-item i, .result-btn i, .logout-pill i { font-size: 1.2rem; margin: 0; }
}

@media (max-width: 768px) {
    .header-container { flex-direction: column; padding: 15px; }
    .nav-links { margin-top: 15px; gap: 4px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resBtn = document.getElementById('resultBtn');
    const resMenu = document.getElementById('resultMenu');

    if(resBtn && resMenu) {
        resBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isVisible = resMenu.style.display === 'block';
            resMenu.style.display = isVisible ? 'none' : 'block';
            resBtn.querySelector('.caret-icon').style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
        });

        // Close when clicking outside
        document.addEventListener('click', () => {
            resMenu.style.display = 'none';
            resBtn.querySelector('.caret-icon').style.transform = 'rotate(0deg)';
        });
    }
});
</script>