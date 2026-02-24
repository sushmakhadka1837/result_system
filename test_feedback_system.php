<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Feedback System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f0f2f5;
        }
        .test-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #003380;
            margin-bottom: 30px;
        }
        .status {
            padding: 10px 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #003380;
            color: white;
        }
        .btn {
            background: #003380;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #002255;
        }
    </style>
</head>
<body>
    <h1>🧪 Feedback System Test Page</h1>

    <div class="test-card">
        <h2>📊 Database Status</h2>
        <?php
        require 'db_config.php';
        
        // Check if tables exist
        $tables_to_check = ['student_feedback', 'student_feedback_pending'];
        
        foreach($tables_to_check as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if($result->num_rows > 0) {
                echo "<div class='status success'>✅ Table '$table' exists</div>";
                
                // Show count
                $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
                $count = $count_result->fetch_assoc()['count'];
                echo "<div class='status info'>📝 Total records: $count</div>";
            } else {
                echo "<div class='status error'>❌ Table '$table' NOT found</div>";
            }
        }
        ?>
    </div>

    <div class="test-card">
        <h2>📧 Email Functions Test</h2>
        <?php
        require 'mail_config.php';
        
        if(function_exists('sendFeedbackVerification')) {
            echo "<div class='status success'>✅ sendFeedbackVerification() function exists</div>";
        } else {
            echo "<div class='status error'>❌ sendFeedbackVerification() function NOT found</div>";
        }
        
        if(function_exists('sendFeedbackThankYou')) {
            echo "<div class='status success'>✅ sendFeedbackThankYou() function exists</div>";
        } else {
            echo "<div class='status error'>❌ sendFeedbackThankYou() function NOT found</div>";
        }
        ?>
    </div>

    <div class="test-card">
        <h2>📋 Recent Pending Feedbacks</h2>
        <?php
        $pending = $conn->query("SELECT * FROM student_feedback_pending ORDER BY created_at DESC LIMIT 5");
        if($pending->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Created</th></tr>";
            while($row = $pending->fetch_assoc()) {
                $status = $row['is_verified'] ? '<span style="color: green;">✅ Verified</span>' : '<span style="color: orange;">⏳ Pending</span>';
                echo "<tr>";
                echo "<td>#{$row['id']}</td>";
                echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['student_email']) . "</td>";
                echo "<td>$status</td>";
                echo "<td>" . date('M d, Y H:i', strtotime($row['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='status info'>No pending feedbacks found</div>";
        }
        ?>
    </div>

    <div class="test-card">
        <h2>✅ Verified Feedbacks</h2>
        <?php
        $verified = $conn->query("SELECT * FROM student_feedback ORDER BY created_at DESC LIMIT 5");
        if($verified->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Feedback</th><th>Verified At</th></tr>";
            while($row = $verified->fetch_assoc()) {
                echo "<tr>";
                echo "<td>#{$row['id']}</td>";
                echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['student_email']) . "</td>";
                echo "<td>" . substr(htmlspecialchars($row['feedback']), 0, 50) . "...</td>";
                echo "<td>" . ($row['verified_at'] ? date('M d, Y H:i', strtotime($row['verified_at'])) : 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='status info'>No verified feedbacks found yet</div>";
        }
        ?>
    </div>

    <div class="test-card">
        <h2>🚀 Quick Actions</h2>
        <a href="index.php" class="btn">Go to Homepage</a>
        <a href="manage_feedback.php" class="btn">Admin Panel</a>
        <a href="submit_feedback.php" class="btn" onclick="alert('Use the form on homepage'); return false;">Test Feedback Form</a>
    </div>

    <div class="test-card">
        <h2>📝 System Info</h2>
        <div class='status info'>
            <strong>PHP Version:</strong> <?= phpversion() ?><br>
            <strong>MySQL Version:</strong> <?= $conn->server_info ?><br>
            <strong>Server Time:</strong> <?= date('Y-m-d H:i:s') ?>
        </div>
    </div>

</body>
</html>
