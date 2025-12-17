<?php
session_start();
include "db.php";

$current_user = $_SESSION['user_id'];
$other_user = $_GET['user_id'];

// Fetch receiver info
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM users WHERE id=$other_user"));
?>
<!DOCTYPE html>
<html>
<head>
<title>Chat with <?php echo $user['name']; ?></title>
<style>
.chat-box { width: 60%; margin: auto; border: 1px solid #ccc; padding: 10px; height: 400px; overflow-y: scroll; }
.message-input { width: 60%; margin: auto; display: flex; }
.message-input textarea { width: 90%; }
.message.sent { color: blue; text-align: right; }
.message.received { color: black; text-align: left; }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<h3>Chat with <?php echo $user['name']; ?></h3>

<div class="chat-box" id="chat_box">
<!-- Messages load here -->
</div>

<div class="message-input">
    <textarea id="message"></textarea>
    <button onclick="sendMessage()">Send</button>
</div>

<script>
function fetchMessages() {
    $.post("fetch_messages.php", {
        current_user: <?php echo $current_user; ?>,
        other_user: <?php echo $other_user; ?>
    }, function(data) {
        $("#chat_box").html(data);
        $("#chat_box").scrollTop($("#chat_box")[0].scrollHeight);
    });
}

function sendMessage() {
    var msg = $("#message").val();
    if(msg.trim() == "") return;
    $.post("send_message.php", {
        sender: <?php echo $current_user; ?>,
        receiver: <?php echo $other_user; ?>,
        message: msg
    }, function() {
        $("#message").val("");
        fetchMessages();
    });
}

setInterval(fetchMessages, 1000); // auto refresh
fetchMessages();
</script>

</body>
</html>