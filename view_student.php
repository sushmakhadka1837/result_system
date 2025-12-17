<?php
include('db_config.php');
session_start();

// Helper to safely escape
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Check for valid student id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid student ID.");
}

$student_id = intval($_GET['id']);

// Fetch student
$query = "SELECT * FROM students WHERE id = $student_id";
$result = $conn->query($query);

if (!$result || $result->num_rows == 0) {
    die("Student not found.");
}

$student = $result->fetch_assoc();

// Get department name (if 'department' stores id)
$department_name = "";
if (!empty($student['department'])) {
    $dept_id = $student['department'];
    $dept_query = "SELECT department_name FROM departments WHERE id = '$dept_id' LIMIT 1";
    $dept_result = $conn->query($dept_query);
    if ($dept_result && $dept_result->num_rows > 0) {
        $dept_row = $dept_result->fetch_assoc();
        $department_name = $dept_row['department_name'];
    } else {
        // fallback if department field already stores name
        $department_name = $student['department'];
    }
}

// Photo path fix
$photo = (!empty($student['profile_photo']) && file_exists($student['profile_photo']))
    ? $student['profile_photo']
    : 'uploads/default_user.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($student['full_name']) ?> - Profile</title>
    <style>
        * {
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
        }

        body {
            background: #f4f6f8;
            margin: 0;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            max-width: 700px;
            width: 100%;
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-card img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #3498db;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .info {
            width: 100%;
            margin-top: 20px;
        }

        .info table {
            width: 100%;
            border-collapse: collapse;
        }

        .info td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .info td.label {
            font-weight: bold;
            color: #2c3e50;
            width: 40%;
        }

        .buttons {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            color: #fff;
            font-weight: bold;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .btn-back { background: #7f8c8d; }
        .btn-back:hover { background: #636e72; }

        .btn-marksheet { background: #3498db; }
        .btn-marksheet:hover { background: #2d89c8; }

        @media (max-width: 600px) {
            .profile-card { padding: 20px; }
            .info td { padding: 8px; font-size: 13px; }
        }
    </style>
</head>
<body>

<div class="profile-card">
    <img src="<?= e($photo) ?>" alt="Student Photo">

    <h2><?= e($student['full_name']) ?></h2>

    <div class="info">
        <table>
            <tr><td class="label">Department:</td><td><?= e($department_name) ?></td></tr>
            <tr><td class="label">Semester:</td><td><?= e($student['semester']) ?></td></tr>
            <tr><td class="label">Section:</td><td><?= e($student['section']) ?: 'N/A' ?></td></tr>
            <tr><td class="label">Batch Year:</td><td><?= e($student['batch_year']) ?></td></tr>
            <tr><td class="label">Email:</td><td><?= e($student['email']) ?></td></tr>
            <tr><td class="label">Symbol No.:</td><td><?= e($student['symbol_no']) ?></td></tr>
            <tr><td class="label">Faculty:</td><td><?= e($student['faculty']) ?></td></tr>
        </table>
    </div>

    <div class="buttons">
        <a href="student_list.php" class="btn btn-back">← Back</a>
        <a href="view_marksheet.php?id=<?= e($student['id']) ?>" class="btn btn-marksheet">View Marksheet</a>
    </div>
</div>

</body>
</html>
