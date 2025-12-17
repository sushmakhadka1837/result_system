<?php
session_start();
require 'db_config.php';

$teacher_id = $_SESSION['teacher_id'] ?? 0;
if (!$teacher_id) {
    echo "Teacher not logged in.";
    exit;
}

// Fetch students for chat
$students_res = $conn->query("SELECT * FROM students ORDER BY full_name ASC");
$students = [];
while($stu = $students_res->fetch_assoc()){
    $students[] = $stu;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chat with Students</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
body { background: #f0f2f5; }
.chat-box { height:500px; overflow-y:auto; background:#fff; padding:10px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1);}
.message { padding:8px 12px; margin:5px 0; border-radius:15px; max-width:75%; }
.message.teacher { background-color:#d1ecf1; text-align:right; margin-left:auto;}
.message.student { background-color:#f8d7da; text-align:left; margin-right:auto;}
.student-item.active { background-color:#007bff; color:white; }
.list-group-item { cursor:pointer; }
</style>
</head>
<body>

<div class="container mt-5">
    <h4>Chat with Students</h4>
    <div class="row">
        <div class="col-md-4">
            <ul class="list-group" id="studentList">
                <?php foreach($students as $stu): ?>
                <li class="list-group-item student-item" data-id="<?= $stu['id'] ?>"><?= $stu['full_name'] ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="col-md-8">
            <div class="chat-box" id="chatBox"></div>
            <form id="messageForm" class="mt-2 d-flex">
                <input type="text" class="form-control me-2" id="messageInput" placeholder="Type a message..." required>
                <button class="btn btn-primary">Send</button>
            </form>
        </div>
    </div>
</div>

<script>
let selectedStudent = 0;

// Select student to chat
$('.student-item').click(function(){
    $('.student-item').removeClass('active');
    $(this).addClass('active');
    selectedStudent = $(this).data('id');
    $('#chatBox').html('');
    fetchMessages();
});

// Send message
$('#messageForm').submit(function(e){
    e.preventDefault();
    if(selectedStudent==0) { alert('Select a student first'); return; }
    let msg = $('#messageInput').val();
    $.post('send_message.php', {
        sender_type:'teacher', sender_id:<?= $teacher_id ?>, 
        receiver_id:selectedStudent, receiver_type:'student', message: msg
    }, function(){
        $('#messageInput').val('');
        fetchMessages();
    });
});

// Fetch messages every 2 sec
function fetchMessages(){
    if(selectedStudent==0) return;
    $.get('fetch_messages.php', {teacher_id:<?= $teacher_id ?>, student_id:selectedStudent}, function(data){
        $('#chatBox').html(data);
        $('#chatBox').scrollTop($('#chatBox')[0].scrollHeight);
    });
}
setInterval(fetchMessages, 2000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
