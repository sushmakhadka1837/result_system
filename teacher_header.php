<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php
    // Fetch unread messages count for the logged-in teacher
    if(isset($_SESSION['teacher_id'])) {
        $teacher_id = $_SESSION['teacher_id'];
        $unread_query = "SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND sender_type = 'student' AND is_read = 0";
        $stmt = $conn->prepare($unread_query);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $unread_result = $stmt->get_result();
        $unread_data = $unread_result->fetch_assoc();
        $unread_count = $unread_data['unread_count'] ?? 0;

        $pending_recheck_count = 0;
        $recheck_table_check = $conn->query("SHOW TABLES LIKE 'assessment_recheck_requests'");
        if ($recheck_table_check && $recheck_table_check->num_rows > 0) {
            $recheck_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM assessment_recheck_requests WHERE assigned_teacher_id = ? AND status = 'pending'");
            $recheck_stmt->bind_param("i", $teacher_id);
            $recheck_stmt->execute();
            $recheck_row = $recheck_stmt->get_result()->fetch_assoc();
            $pending_recheck_count = (int)($recheck_row['cnt'] ?? 0);
        }
    } else {
        $unread_count = 0;
        $pending_recheck_count = 0;
    }
    ?>
    
    <style>
        :root {
            --navy-blue: #0a192f;        /* Deep Navy Blue */
            --navy-light: #112240;       /* Lighter Navy for hover */
            --accent-cyan: #64ffda;      /* Teal/Cyan accent for icons/badges */
            --text-silver: #ccd6f6;      /* Soft silver text */
            --pure-white: #ffffff;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #f4f7f9;
            padding-top: 80px;
        }

        /* ===== Navy Blue Navbar ===== */
        .teacher-navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 80px;
            background-color: var(--navy-blue);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 50px;
            box-shadow: 0 10px 30px -10px rgba(2, 12, 27, 0.7);
            z-index: 1000;
            box-sizing: border-box;
        }

        /* Logo & Brand */
        .nav-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-left img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            /* Navy blue background ma logo set garna white padding */
            background: #fff; 
            padding: 5px;
            border-radius: 10px;
        }

        .brand-text h1 {
            margin: 0;
            color: var(--pure-white);
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .brand-text p {
            margin: 0;
            color: var(--accent-cyan);
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
        }

        /* Navigation Links */
        .nav-center {
            display: flex;
            gap: 5px;
        }

        .nav-link {
            color: var(--text-silver);
            text-decoration: none;
            padding: 10px 18px;
            font-size: 0.9rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link i {
            color: var(--accent-cyan); /* Cyan icons on Navy */
            font-size: 1.1rem;
        }

        .nav-link:hover {
            background: var(--navy-light);
            color: var(--accent-cyan);
            transform: translateY(-2px);
        }

        /* Message Badge */
        .badge-container {
            position: relative;
        }

        .nav-badge {
            position: absolute;
            top: -6px;
            right: -10px;
            background: #ff3b30;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            line-height: 1;
            min-width: 20px;
            height: 20px;
            padding: 0 5px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--navy-blue);
            box-shadow: 0 4px 10px rgba(255, 59, 48, 0.35);
        }

        /* Logout Button */
        .nav-right .logout-btn {
            background: transparent;
            border: 1px solid var(--accent-cyan);
            color: var(--accent-cyan);
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .nav-right .logout-btn:hover {
            background: rgba(100, 255, 218, 0.1);
            box-shadow: 0 0 15px rgba(100, 255, 218, 0.2);
        }

        /* Scannable divider */
        .divider {
            width: 1px;
            height: 30px;
            background: rgba(255,255,255,0.1);
            margin: 0 15px;
        }

        /* Dropdown Menu */
        .nav-dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: var(--navy-light);
            border-radius: 8px;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            z-index: 1001;
            margin-top: 5px;
        }

        .nav-dropdown:hover .dropdown-menu {
            display: block;
        }

        .dropdown-menu a {
            color: var(--text-silver);
            padding: 12px 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .dropdown-menu a:first-child {
            border-radius: 8px 8px 0 0;
        }

        .dropdown-menu a:last-child {
            border-radius: 0 0 8px 8px;
        }

        .dropdown-menu a:hover {
            background: var(--navy-blue);
            color: var(--accent-cyan);
            border-left-color: var(--accent-cyan);
            padding-left: 24px;
        }

        .dropdown-menu i {
            color: var(--accent-cyan);
            font-size: 1rem;
        }
    </style>
</head>
<body>

<header class="teacher-navbar">
    <div class="nav-left">
        <img src="images/logoheader.png" alt="Logo">
        <div class="brand-text">
            <h1>EduPortal</h1>
            <p>Teacher Administration</p>
        </div>
    </div>

    <nav class="nav-center">
        <a href="index.php" class="nav-link">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="teacher_subjects.php" class="nav-link">
            <i class="fas fa-book-open"></i> Subjects
        </a>
        <div class="nav-dropdown">
            <a href="teacher_notes.php" class="nav-link">
                <i class="fas fa-file-signature"></i> Notes
            </a>
            <div class="dropdown-menu">
                <a href="teacher_notes.php">
                    <i class="fas fa-upload"></i> Upload Notes
                </a>
                <a href="teacher_verify_student_uploads.php">
                    <i class="fas fa-check-circle"></i> Verify Student Uploads
                </a>
            </div>
        </div>
        <a href="publish_result.php" class="nav-link">
            <i class="fas fa-poll"></i> Results
        </a>
        <a href="teacher_assessment_recheck_requests.php" class="nav-link badge-container">
            <i class="fas fa-rotate-left"></i> Recheck
            <?php if($pending_recheck_count > 0): ?>
                <span class="nav-badge"><?= $pending_recheck_count ?></span>
            <?php endif; ?>
        </a>
         <a href="teacher_class_analysis.php" class="nav-link">
            <i class="fas fa-chart-line"></i> Analytics
        </a>
        <div class="divider"></div>

        <a href="teacher_chat.php" class="nav-link badge-container">
            <i class="fas fa-envelope"></i> Messages
            <?php if($unread_count > 0): ?>
                <span class="nav-badge"><?= $unread_count ?></span>
            <?php endif; ?>
        </a>
    </nav>

    <div class="nav-right">
        <a href="logout.php" style="text-decoration: none;">
            <button class="logout-btn">
                <i class="fas fa-power-off me-2"></i> LOGOUT
            </button>
        </a>
    </div>
</header>

</body>
</html>