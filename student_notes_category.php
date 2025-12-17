<?php
require 'db_config.php';

$subject_id = intval($_GET['subject_id']);

$subject = $conn->query("SELECT * FROM subjects_master WHERE id=$subject_id")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $subject['subject_name']; ?> - Categories</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
<div class="container my-5">

    <h3><?php echo $subject['subject_name']; ?> (<?php echo $subject['subject_code']; ?>)</h3>
    <p>Select category:</p>

    <div class="list-group">

        <a href="student_upload_pdf.php?subject_id=<?php echo $subject_id; ?>&type=notes"
           class="list-group-item list-group-item-action">📚 Notes</a>

        <a href="student_upload_pdf.php?subject_id=<?php echo $subject_id; ?>&type=syllabus"
           class="list-group-item list-group-item-action">📘 Syllabus</a>

        <a href="student_upload_pdf.php?subject_id=<?php echo $subject_id; ?>&type=past_question"
           class="list-group-item list-group-item-action">📝 Past Question</a>

    </div>

</div>
</body>
</html>
