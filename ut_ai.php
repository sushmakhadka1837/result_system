<?php

// Student login check
if(!isset($_SESSION['student_id'])){
    header("Location: index.php");
    exit;
}

// Fetch UT results for logged-in student
$student_id = $_SESSION['student_id'];
$results_q = $conn->query("
    SELECT sm.subject_name, r.ut_obtain AS ut_marks
    FROM results r
    JOIN subjects_master sm ON r.subject_code = sm.subject_code
    WHERE r.student_id = $student_id
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UT AI Performance Insight</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.card:hover{
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.15);
    transition: 0.3s;
}
</style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <h3 class="text-center mb-4"> AI Performance Insight</h3>

    <div class="card shadow-sm p-3">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-chart-line"></i> Analyze Your UT Performance</h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                Select a subject to get AI feedback on your performance.
            </p>

            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <select id="aiSubject" class="form-select">
                        <option value="">-- Select Subject --</option>
                        <?php
                        $results_q->data_seek(0);
                        while($row = $results_q->fetch_assoc()):
                        ?>
                            <option value="<?= htmlspecialchars($row['subject_name']); ?>"
                                    data-marks="<?= intval($row['ut_marks']); ?>">
                                <?= htmlspecialchars($row['subject_name']); ?>
                                (<?= intval($row['ut_marks']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100" onclick="analyzeAIInsight()">
                        Analyze
                    </button>
                </div>
            </div>

            <div id="aiInsightResult" class="alert alert-info mt-3">
                Select a subject and click Analyze to get AI feedback.
            </div>
        </div>
    </div>
</div>

<script>
function analyzeAIInsight() {
    const select = document.getElementById('aiSubject');
    const subject = select.value;
    if(subject === "") { alert("Please select a subject"); return; }

    const marks = select.options[select.selectedIndex].dataset.marks;
    const formData = new FormData();
    formData.append('subject', subject);
    formData.append('marks', marks);

    fetch('ai_performance_insight.php', { method:'POST', body:formData })
    .then(res => res.text())
    .then(data => {
        document.getElementById('aiInsightResult').innerHTML = data;
    });
}
</script>

</body>
</html>
