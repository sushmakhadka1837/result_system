<?php
session_start();
require 'db_config.php';

$teacher_id = $_SESSION['teacher_id'] ?? 0;
if(!$teacher_id){
    header("Location: teacher_login.php");
    exit;
}

// Fetch teacher info
$teacher = $conn->query("SELECT full_name FROM teachers WHERE id=$teacher_id")->fetch_assoc();

// Fetch assigned subjects
$query = "SELECT ts.id AS assign_id, sm.subject_name, sm.subject_code, ts.semester_id, ts.batch_year, d.department_name
          FROM teacher_subjects ts
          JOIN subjects_master sm ON ts.subject_map_id = sm.id
          JOIN departments d ON ts.department_id = d.id
          WHERE ts.teacher_id = ?
          ORDER BY ts.batch_year ASC, ts.semester_id ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Subjects - <?= htmlspecialchars($teacher['full_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --navy-blue: #0a192f;
            --accent-cyan: #64ffda;
            --soft-bg: #f4f7f9;
        }

        body {
            background-color: var(--soft-bg);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        /* Card Design */
        .subject-card {
            background: #fff;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
            border-left: 5px solid var(--navy-blue);
        }

        .subject-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.1);
        }

        .card-category {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-muted);
        }

        .batch-badge {
            font-size: 0.7rem;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        .old-batch { background: #fff3cd; color: #856404; }
        .new-batch { background: #d1e7dd; color: #0f5132; }

        .btn-view {
            background-color: var(--navy-blue);
            color: #fff;
            border-radius: 8px;
            font-weight: 500;
            width: 100%;
            transition: 0.3s;
        }

        .btn-view:hover {
            background-color: #112240;
            color: var(--accent-cyan);
        }

        .page-title {
            font-weight: 800;
            color: var(--navy-blue);
            margin-bottom: 30px;
        }
    </style>
</head>
<body>

<?php include 'teacher_header.php'; ?>

<div class="container my-5">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-book-reader me-2"></i> Assigned Subjects
                <small class="d-block text-muted fs-6 fw-normal">Welcome back, <?= htmlspecialchars($teacher['full_name']) ?></small>
            </h2>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="teacher_dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="row g-4">
        <?php if($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $is_new = ($row['batch_year'] > 2022);
                $batch_label = $is_new ? "New Batch" : "Old Batch";
                $batch_class = $is_new ? "new-batch" : "old-batch";
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card subject-card p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="card-category"><?= htmlspecialchars($row['department_name']) ?></span>
                        <span class="batch-badge <?= $batch_class ?>"><?= $batch_label ?></span>
                    </div>
                    
                    <h4 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($row['subject_name']) ?></h4>
                    <p class="text-muted small mb-3">Code: <?= !empty($row['subject_code']) ? $row['subject_code'] : "N/A" ?></p>
                    
                    <div class="d-flex align-items-center mb-4 text-secondary">
                        <div class="me-4">
                            <i class="fas fa-layer-group me-1"></i> 
                            <strong><?= $row['semester_id'] ?></strong> Sem
                        </div>
                        <div>
                            <i class="fas fa-calendar-alt me-1"></i> 
                            <?= $row['batch_year'] ?? 'All' ?>
                        </div>
                    </div>

                    <a href="subject_students.php?assign_id=<?= $row['assign_id'] ?>" class="btn btn-view">
                        <i class="fas fa-users me-2"></i> Show Students
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-white rounded-4 shadow-sm">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h4>No subjects assigned yet.</h4>
                    <p class="text-muted">Please contact the administrator to assign subjects to your profile.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>