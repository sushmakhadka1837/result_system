<?php
require 'db_config.php';

if(!isset($_GET['id'])){
    die("Notice not found.");
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$notice = $result->fetch_assoc();

if(!$notice){
    die("Notice not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($notice['title']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 40px;
        }
        .notice-container {
            max-width: 800px;
            background: #fff;
            padding: 30px;
            margin: auto;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #1a73e8;
            margin-bottom: 10px;
        }
        .date {
            color: #777;
            margin-bottom: 20px;
        }
        p {
            line-height: 1.6;
            color: #333;
        }
        a.back {
            display: inline-block;
            margin-top: 20px;
            color: #1a73e8;
            text-decoration: none;
            font-weight: bold;
        }
        a.back:hover {
            text-decoration: underline;
        }
        
    </style>
</head>
<body>
    <div class="notice-container">
        <h2><?php echo htmlspecialchars($notice['title']); ?></h2>
        <div class="date">📅 <?php echo date("F d, Y", strtotime($notice['created_at'])); ?></div>
        <p><?php echo nl2br($notice['message']); ?></p>
        <a href="student_dashboard.php" class="back">← Back to Dashboard</a>
    </div>
</body>
</html>
