<?php
session_start();
require 'db_config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; 

if (!isset($_SESSION['teacher_id']) || $_SESSION['user_type'] != 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Fetch teacher info
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id=?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

// Fetch unread messages count
$stmt2 = $conn->prepare("SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_id=? AND is_read=0");
$stmt2->bind_param("i", $teacher_id);
$stmt2->execute();
$unread_count = $stmt2->get_result()->fetch_assoc()['unread_count'];

// Fetch recent messages (last 5)
$recent_messages = [];
$stmt3 = $conn->prepare("
    SELECT m.message, m.created_at, 
           t.full_name AS sender_name
    FROM messages m
    JOIN teachers t ON t.id = m.sender_id
    WHERE m.receiver_id=?
    ORDER BY m.created_at DESC
    LIMIT 5
");
$stmt3->bind_param("i", $teacher_id);
$stmt3->execute();
$res3 = $stmt3->get_result();
while($row = $res3->fetch_assoc()){
    $recent_messages[] = $row;
}

// Fetch upcoming notices for calendar (last 20)
$calendar_events = [];
$stmt4 = $conn->prepare("SELECT title, created_at FROM notices WHERE teacher_id=? ORDER BY created_at DESC LIMIT 20");
$stmt4->bind_param("i", $teacher_id);
$stmt4->execute();
$res4 = $stmt4->get_result();
while($row = $res4->fetch_assoc()){
    $calendar_events[] = [
        'title' => $row['title'],
        'start' => date('Y-m-d', strtotime($row['created_at']))
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teacher Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css' rel='stylesheet' />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js'></script>
<style>
body{margin:0;font-family:'Roboto',sans-serif;background:#f4f6f8;}
.navbar{display:flex;justify-content:space-between;background:#1a73e8;color:white;padding:15px 20px;}
.navbar a{color:white;text-decoration:none;margin-left:15px;font-weight:bold;position:relative;}
.navbar a span{background:red;color:white;font-size:12px;padding:2px 6px;border-radius:50%;position:absolute;top:-8px;right:-10px;}
.container{display:flex;flex-wrap:wrap;padding:20px;gap:25px;}
.profile-card{flex:1 1 280px;background:white;border-radius:10px;padding:20px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.08);}
.profile-card img{width:120px;height:120px;border-radius:50%;object-fit:cover;cursor:pointer;}
.profile-card h3{margin:15px 0 5px 0;}
.profile-card p{margin:5px 0;color:#555;}
.profile-card button{margin-top:15px;padding:10px 15px;border:none;background:#1a73e8;color:white;border-radius:5px;cursor:pointer;}
.profile-card button:hover{background-color:#155ab6;}
.announcement-card{flex:2 1 600px;background:#e8f0fe;padding:20px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.08);transition:all 0.3s;}
.announcement-card:hover{box-shadow:0 8px 20px rgba(0,0,0,0.12);}
.announcement-card h2{margin-top:0;color:#1a73e8;}
.right-panel{flex:1 1 300px;background:white;border-radius:10px;padding:20px;box-shadow:0 4px 12px rgba(0,0,0,0.08);}
.card-section{margin-bottom:20px;}
#teacherCalendar{max-width:100%;margin:0 auto;background:white;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.08);}
#recentMessages{max-height:220px;overflow-y:auto;}
#recentMessages div{padding:5px 0;border-bottom:1px solid #eee;}
@media(max-width:768px){.container{flex-direction:column;}.profile-card,.announcement-card,.right-panel{flex:1 1 100%;}}
</style>
</head>
<body>

<div class="navbar">
    <div>Teacher Dashboard</div>
    <div>
        <a href="index.php">Home</a>
        <a href="teacher_subjects.php">Subjects</a>
        <a href="teacher_notes.php">Notes</a>
        <a href="publish_result.php">Publish Result</a>
        <a href="teacher_chat.php" id="nav-messages">Messages
            <?php if($unread_count>0): ?>
                <span id="unread-count"><?= ($unread_count>9)?'9+':$unread_count ?></span>
            <?php endif; ?>
        </a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <!-- Profile -->
    <div class="profile-card">
        <form action="upload_teacher_profile.php" method="post" enctype="multipart/form-data">
            <label for="profile_photo">
                <img src="<?= !empty($teacher['profile_pic'])?$teacher['profile_pic']:'https://cdn-icons-png.flaticon.com/512/149/149071.png'; ?>" alt="Profile Photo">
            </label>
            <input type="file" name="profile_pic" id="profile_photo" style="display:none;" onchange="this.form.submit()">
        </form>
        <h3><?= htmlspecialchars($teacher['full_name']); ?></h3>
        <p><strong>Email:</strong> <?= htmlspecialchars($teacher['email']); ?></p>
        <p><strong>Employee ID:</strong> <?= htmlspecialchars($teacher['employee_id']); ?></p>
        <p><strong>Contact:</strong> <?= htmlspecialchars($teacher['contact']); ?></p>
        <button onclick="window.location.href='edit_profile.php'">Edit Profile</button>
    </div>

    <!-- Announcement -->
    <div class="main-events-section d-flex flex-wrap" style="gap:20px; margin:40px auto; max-width:1200px;">

  <!-- Left Column: Announcements (include announcement.php) -->
  <div class="announcement-section flex-fill" style="min-width:300px; flex:1.5;">
      <?php include 'announcement.php'; ?>
  </div>

  <!-- Right Column: Calendar / Upcoming Events -->
  <div class="calendar-box flex-fill" style="min-width:250px; flex:1;">
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

<style>
.main-events-section {
    display: flex;
    gap: 20px;
    flex-wrap: wrap; /* mobile friendly */
}

.announcement-section, .calendar-box {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
}

.announcement-section:hover, .calendar-box:hover {
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

/* Responsive: stack on smaller screens */
@media(max-width:992px){
    .main-events-section {
        flex-direction: column;
    }
}
</style>

</div>
<?php include 'footer.php'; ?>
<script>
// Dynamic semester options
const deptLimit = {};
<?php
$dept_result = $conn->query("SELECT * FROM departments");
while($d=$dept_result->fetch_assoc()){
    echo "deptLimit[{$d['id']}] = {$d['total_semesters']};";
}
?>
const departmentSelect = document.getElementById('departmentSelect');
const semesterSelect = document.getElementById('semesterSelect');
departmentSelect.addEventListener('change', ()=>{
    semesterSelect.innerHTML = '<option value="">All Semesters</option>';
    const deptId = departmentSelect.value;
    if(deptId && deptId !== 'all'){
        const semCount = deptLimit[deptId];
        for(let i=1;i<=semCount;i++){
            const opt = document.createElement('option');
            opt.value=i;
            opt.text='Semester '+i;
            semesterSelect.appendChild(opt);
        }
    }
});

// FullCalendar
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('teacherCalendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 400,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: <?= json_encode($calendar_events); ?>
    });
    calendar.render();
});

// AJAX for unread messages
function updateUnreadCount(){
    $.get('fetch_teacher_unread_count.php', function(data){
        $('#unread-count').remove();
        if(data>0){
            $('#nav-messages').append('<span id="unread-count">'+(data>9?'9+':data)+'</span>');
        }
    });
}
setInterval(updateUnreadCount,3000);
</script>
</body>
</html>
