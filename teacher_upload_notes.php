<?php
session_start();
if(!isset($_SESSION['teacher_id'])){
    header("Location: teacher_login.php");
    exit();
}

require 'db_config.php';

// Data Sanitization
$dept_id = intval($_GET['dept_id'] ?? 0);
$dept_query = $conn->query("SELECT * FROM departments WHERE id=$dept_id");
$dept = ($dept_query->num_rows > 0) ? $dept_query->fetch_assoc() : ['department_name'=>'Unknown'];

$sem_id = intval($_GET['sem_id'] ?? 0);
$batch = $_GET['batch'] ?? 'old';
$batch_condition = ($batch=='old') ? "(sds.batch_year IS NULL OR sds.batch_year='')" : "sds.batch_year=1";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($dept['department_name']); ?> - Subjects</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --navy-blue: #0a192f;
            --accent-cyan: #64ffda;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.05);
            --text-muted: #64748b;
        }

        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
            padding-top: 100px; /* Navbar space */
        }

        /* Subject Card Styling */
        .subject-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .subject-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 4px; height: 100%;
            background: var(--navy-blue);
            opacity: 0;
            transition: 0.3s;
        }

        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }

        .subject-card:hover::before {
            opacity: 1;
        }

        .subject-name {
            font-weight: 700;
            color: var(--navy-blue);
            margin-bottom: 5px;
            font-size: 1.15rem;
        }

        .subject-code {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Filter Section Styling */
        .filter-section {
            background: #fff;
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 35px;
            border: 1px solid #eef2f6;
        }

        .form-label {
            font-weight: 600;
            color: #475569;
            font-size: 0.85rem;
            margin-bottom: 8px;
        }

        /* UI Headers */
        .section-header-box {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .icon-box {
            background: var(--navy-blue);
            color: var(--accent-cyan);
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.5rem;
        }

        .select2-container--default .select2-selection--single {
            height: 45px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding-top: 8px;
        }
    </style>
</head>
<body>

<?php 
// Badge hatauna ko lagi count lai zero set gareko
$unread_count = 0; 
include 'teacher_header.php'; 
?>

<div class="container mb-5">
    <div class="section-header-box">
        <div class="icon-box">
            <i class="fas fa-layer-group"></i>
        </div>
        <div>
            <h2 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($dept['department_name']); ?> Department</h2>
            <p class="text-muted mb-0">Manage and upload notes for your assigned subjects</p>
        </div>
    </div>

    <div class="filter-section">
        <div class="row g-4">
            <div class="col-md-4">
                <label class="form-label"><i class="fas fa-calendar-alt me-1 text-primary"></i> Academic Batch</label>
                <select id="batch_id" class="form-select select2">
                    <option value="old" <?php if($batch=='old') echo 'selected'; ?>>Old Batch (Before 2081)</option>
                    <option value="new" <?php if($batch=='new') echo 'selected'; ?>>New Batch (2081 Onwards)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label"><i class="fas fa-clock me-1 text-primary"></i> Current Semester</label>
                <select id="semester_id" class="form-select select2">
                    <option value="">Choose Semester...</option>
                    <?php for($s=1;$s<=8;$s++): ?>
                        <option value="<?= $s; ?>" <?php if($sem_id==$s) echo 'selected'; ?>>
                            <?= $s; ?><?= ($s==1)?'st':(($s==2)?'nd':(($s==3)?'rd':'th')); ?> Semester
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="row g-4">
    <?php
    if($sem_id){
        $sql = "SELECT sm.* FROM subjects_master sm
                JOIN subjects_department_semester sds ON sm.id=sds.subject_id
                WHERE sm.department_id=$dept_id AND sm.semester_id=$sem_id AND $batch_condition
                ORDER BY sm.subject_name ASC";
        $subjects = $conn->query($sql);
        
        if($subjects && $subjects->num_rows > 0){
            while($sub = $subjects->fetch_assoc()):
    ?>
        <div class="col-md-4">
            <a href="teacher_upload_pdf.php?subject_id=<?= $sub['id']; ?>" class="text-decoration-none">
                <div class="subject-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="subject-name"><?= htmlspecialchars($sub['subject_name']); ?></div>
                        <i class="fas fa-arrow-right-long text-muted opacity-25"></i>
                    </div>
                    <div class="subject-code"><i class="fas fa-code me-2"></i><?= htmlspecialchars($sub['subject_code']); ?></div>
                </div>
            </a>
        </div>
    <?php
            endwhile;
        } else {
            echo '<div class="col-12 text-center py-5">
                    <div class="p-5 border-2 border-dashed rounded-4 bg-light text-muted">
                        <i class="fas fa-folder-open fa-3x mb-3 opacity-25"></i>
                        <h5>No subjects found</h5>
                        <p>Try changing the batch or semester filter.</p>
                    </div>
                  </div>';
        }
    } else {
        echo '<div class="col-12 text-center py-5">
                <div class="alert alert-warning border-0 shadow-sm d-inline-block px-5 py-3 rounded-pill">
                    <i class="fas fa-hand-pointer me-2"></i> Please select a <b>Semester</b> to view subject list.
                </div>
              </div>';
    }
    ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function(){
    // Initialize Select2
    $('.select2').select2({
        width: '100%',
        minimumResultsForSearch: Infinity
    });

    // Handle Selection Change
    $('#batch_id, #semester_id').change(function(){
        let batch = $('#batch_id').val();
        let sem = $('#semester_id').val();
        if(sem){
            window.location.href = `teacher_upload_notes.php?dept_id=<?= $dept_id; ?>&batch=${batch}&sem_id=${sem}`;
        }
    });
});
</script>

<?php include 'footer.php'; ?>

</body>
</html>