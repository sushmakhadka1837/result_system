<?php
require 'db_config.php';

$subject_id = intval($_GET['subject_id'] ?? 0);

$subject_q = $conn->query("SELECT * FROM subjects_master WHERE id='$subject_id'");
if($subject_q->num_rows==0){
    die("Invalid Subject");
}
$subject = $subject_q->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($subject['subject_name']) ?> - Categories</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .category-card{
            border-radius:12px;
            padding:25px;
            background:#fff;
            box-shadow:0 5px 20px rgba(0,0,0,0.08);
            transition:0.3s;
            text-align:center;
        }
        .category-card:hover{
            transform:translateY(-5px);
            box-shadow:0 12px 30px rgba(0,0,0,0.15);
        }
        .category-card a{
            text-decoration:none;
            font-size:1.2rem;
            font-weight:600;
            display:block;
            color:#333;
        }
    </style>
</head>

<body>
<?php include 'student_header.php'; ?>

<div class="container my-5">
    <h3 class="mb-2">
        <?= htmlspecialchars($subject['subject_name']) ?>
        <small class="text-muted">(<?= htmlspecialchars($subject['subject_code']) ?>)</small>
    </h3>
    <p class="text-muted">Select category to upload or view files</p>

    <div class="row g-4 mt-4">

        <div class="col-md-4">
            <div class="category-card">
                <a href="student_upload_pdf.php?subject_id=<?= $subject_id ?>&category=notes">
                    📚 Notes
                </a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="category-card">
                <a href="student_upload_pdf.php?subject_id=<?= $subject_id ?>&category=syllabus">
                    📘 Syllabus
                </a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="category-card">
                <a href="student_upload_pdf.php?subject_id=<?= $subject_id ?>&category=past_question">
                    📝 Past Question
                </a>
            </div>
        </div>

    </div>
</div>
 <button
        onclick="history.back()"
        class="w-10 h-10 flex items-center justify-center 
               rounded-full bg-gray-200 hover:bg-gray-300 
               text-gray-700 hover:text-gray-900 
               shadow transition"
        title="Go Back">
        ←
    </button>
<?php include 'footer.php'; ?>
</body>
</html>
