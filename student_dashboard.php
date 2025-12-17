<?php
session_start();
require 'db_config.php';

// Redirect if not logged in
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

// Fetch internal notices
$stmt2 = $conn->prepare("SELECT n.*, t.full_name AS teacher_name FROM notices n JOIN teachers t ON n.teacher_id=t.id WHERE n.notice_type='internal' AND n.department_id=? AND (n.semester=? OR n.semester='all') ORDER BY n.created_at DESC LIMIT 5");
$stmt2->bind_param("is",$student['department_id'],$student['semester']);
$stmt2->execute();
$internal_notices = $stmt2->get_result();

// Fetch unread messages count
$stmt3 = $conn->prepare("SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_id=? AND is_read=0");
$stmt3->bind_param("i",$student_id);
$stmt3->execute();
$unread_count = $stmt3->get_result()->fetch_assoc()['unread_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    margin:0;
    font-family:'Poppins', sans-serif;
    background:#f4f6f8;
}
.navbar-custom {
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#1a73e8;
    color:white;
    padding:12px 20px;
    flex-wrap:wrap;
}
.navbar-custom a{
    color:white;
    text-decoration:none;
    margin-left:15px;
    font-weight:500;
    position:relative;
}
.navbar-custom a span{
    background:red;
    color:white;
    font-size:12px;
    padding:2px 6px;
    border-radius:50%;
    position:absolute;
    top:-8px;
    right:-10px;
}
.container-dashboard{
    display:flex;
    flex-wrap:wrap;
    gap:20px;
    padding:20px;
}
.profile-card{
    flex:1 1 280px;
    background:white;
    border-radius:15px;
    padding:25px 20px;
    text-align:center;
    box-shadow:0 8px 20px rgba(0,0,0,0.08);
    transition:transform 0.3s ease, box-shadow 0.3s ease;
}
.profile-card:hover{
    transform:translateY(-5px);
    box-shadow:0 12px 25px rgba(0,0,0,0.12);
}
.profile-card img{
    width:120px;
    height:120px;
    border-radius:50%;
    object-fit:cover;
    cursor:pointer;
    border:3px solid #1a73e8;
}
.profile-card h3{
    margin:15px 0 5px 0;
    color:#1a73e8;
}
.profile-card p{
    margin:5px 0;
    color:#555;
    font-size:0.95rem;
}
.profile-card button{
    margin-top:15px;
    padding:10px 15px;
    border:none;
    background:#1a73e8;
    color:white;
    border-radius:8px;
    cursor:pointer;
    font-weight:500;
}
.profile-card button:hover{
    background:#155ab6;
}
.card-section{
    flex:2 1 600px;
    background:white;
    padding:25px 20px;
    border-radius:15px;
    box-shadow:0 8px 20px rgba(0,0,0,0.08);
    margin-bottom:20px;
}
.card-section h2{
    margin-top:0;
    color:#1a73e8;
    margin-bottom:20px;
}
.notice-card{
    background:#f1f3f4;
    padding:15px 20px;
    border-radius:10px;
    margin-bottom:12px;
    transition:all 0.3s ease;
}
.notice-card:hover{
    background:#e8f0fe;
    transform:translateY(-3px);
}
.notice-card h4{
    margin:0 0 5px 0;
    color:#1a73e8;
    font-weight:600;
}
.notice-card small{
    color:#555;
    font-size:0.85rem;
}
.view-all{
    display:block;
    text-align:right;
    margin-top:10px;
    font-weight:bold;
    color:#1a73e8;
    text-decoration:none;
}
@media(max-width:768px){
    .container-dashboard{
        flex-direction:column;
    }
}
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="navbar-custom">
    <div><strong>Student Dashboard</strong></div>
    <div>
    <a href="index.php">Home</a>
        
        <a href="student_view_result.php">View Result</a>
        <a href="student_announcement.php">Announcements</a>
        <a href="student_notes.php">Notes</a>
        <a href="student_chat.php" id="nav-messages">Messages
            <?php if($unread_count>0): ?>
                <span id="unread-count"><?php echo ($unread_count>9)?'9+':$unread_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container-dashboard">
    <!-- Profile Card -->
    <div class="profile-card">
        <form action="upload_profile.php" method="post" enctype="multipart/form-data">
            <label for="profile_photo">
                <img src="<?php echo !empty($student['profile_photo'])?$student['profile_photo']:'default.png'; ?>" alt="Profile Photo">
            </label>
            <input type="file" name="profile_photo" id="profile_photo" style="display:none;" onchange="this.form.submit()">
        </form>
        <h3><?php echo htmlspecialchars($student['full_name']); ?></h3>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone']); ?></p>
        <p><strong>Department:</strong> <?php echo htmlspecialchars($student['department_name']); ?></p>
        <p><strong>Batch:</strong> <?php echo htmlspecialchars($student['batch_year']); ?></p>
        <button onclick="window.location.href='student_edit_profile.php'">Edit Profile</button>
    </div>

    <!-- Internal Announcements -->
    <?php include 'view_student_notice.php'; ?>
</div>
<?php include 'footer.php'; ?>
<script>
function updateUnreadCount(){
    $.get('fetch_unread_count.php', function(data){
        if(data>0){
            $('#unread-count').remove();
            $('#nav-messages').append('<span id="unread-count">'+(data>9?'9+':data)+'</span>');
        } else {
            $('#unread-count').remove();
        }
    });
}
setInterval(updateUnreadCount,3000);
</script>

</body>
</html>
