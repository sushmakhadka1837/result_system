<?php
require 'db_config.php';

// Delete feedback if requested
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM student_feedback WHERE id='$id'");
    header("Location: manage_feedback.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Feedback</title>
<style>
body {font-family: Arial, sans-serif; background: #f4f6f8; padding: 20px;}
.container {max-width: 1000px; margin:auto; background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1);}
h2 {text-align:center; margin-bottom:20px; color:#1a1a1a;}
#searchInput {width:100%; padding:10px; margin-bottom:15px; border:1px solid #ccc; border-radius:6px;}
table {width:100%; border-collapse:collapse;}
th, td {padding:12px 10px; border-bottom:1px solid #ddd; text-align:left;}
th {background:#007BFF; color:#fff; font-weight:600;}
tr:hover {background:#f1f1f1;}
.delete-btn {padding:5px 10px; background:#dc3545; color:#fff; border:none; border-radius:5px; cursor:pointer; transition:0.3s;}
.delete-btn:hover{background:#a71d2a;}
</style>
</head>
<body>

<div class="container">
<h2>💬 Manage Student Feedback</h2>

<input type="text" id="searchInput" placeholder="Search feedback by name, email, or keyword...">

<table id="feedbackTable">
<tr>
    <th>ID</th><th>Name</th><th>Email</th><th>Feedback</th><th>Date</th><th>Action</th>
</tr>

<?php
$q = $conn->query("SELECT * FROM student_feedback ORDER BY created_at DESC");
while($row = $q->fetch_assoc()){
?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['student_name']) ?></td>
    <td><?= htmlspecialchars($row['student_email']) ?></td>
    <td><?= htmlspecialchars($row['feedback']) ?></td>
    <td><?= $row['created_at'] ?></td>
    <td><a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure?');"><button class="delete-btn">Delete</button></a></td>
</tr>
<?php } ?>
</table>
</div>

<script>
// Real-time search
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('keyup', function(){
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#feedbackTable tr:not(:first-child)');
    rows.forEach(row=>{
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>
