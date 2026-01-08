<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session Check
if(!isset($_SESSION['student_id'])){
    header("Location:login.php");
    exit();
}

require 'db_config.php';

// Parameters tanna (Department, Semester, Batch)
$dept_id = intval($_GET['dept_id'] ?? 0);
$sem_id = intval($_GET['sem_id'] ?? 0);
$batch = $_GET['batch'] ?? 'old';

// Batch Condition: Old syllabus (NULL/Empty) vs New (batch_year=1)
$batch_condition = ($batch=='old') ? "(sds.batch_year IS NULL OR sds.batch_year='')" : "sds.batch_year=1";

// Department details tanna
$dept_q = $conn->prepare("SELECT department_name FROM departments WHERE id = ?");
$dept_q->bind_param("i", $dept_id);
$dept_q->execute();
$dept = $dept_q->get_result()->fetch_assoc() ?? ['department_name' => 'Engineering'];

// Include the standard header
include 'student_header.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($dept['department_name']); ?> Resources - PEC</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        :root {
            --navy: #001f4d;
            --gold: #f4c430;
        }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
        
        /* Filter Card Styling */
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-top: 30px;
            margin-bottom: 30px;
        }
        
        /* Subject Card Styling */
        .subject-card {
            background: white;
            border: none;
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border: 1px solid #e2e8f0;
        }
        
        .subject-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 31, 77, 0.1);
            border-color: var(--gold);
        }

        .icon-circle {
            width: 65px;
            height: 65px;
            background: #f8fafc;
            color: var(--navy);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 15px;
            transition: 0.3s;
        }

        .subject-card:hover .icon-circle {
            background: var(--gold);
            color: var(--navy);
        }

        .subject-name {
            font-weight: 700;
            color: var(--navy);
            font-size: 1.15rem;
            margin-bottom: 5px;
        }

        .subject-code {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .breadcrumb-custom {
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .breadcrumb-custom a { text-decoration: none; color: #64748b; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="breadcrumb-custom">
        <a href="student_notes.php">Academic Resources</a> / <span class="text-dark fw-bold"><?= $dept['department_name'] ?></span>
    </div>

    <h2 class="fw-bold text-dark mt-2 mb-4">
        <i class="fas fa-book-reader text-warning me-2"></i> 
        <?= htmlspecialchars($dept['department_name']); ?> Subjects
    </h2>

    <div class="filter-section">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label fw-bold small text-muted">SYLLABUS BATCH</label>
                <select id="batch_id" class="form-select select2">
                    <option value="old" <?= ($batch=='old') ? 'selected' : ''; ?>>Old Batch </option>
                    <option value="new" <?= ($batch=='new') ? 'selected' : ''; ?>>New Batch </option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold small text-muted">SEMESTER</label>
                <select id="semester_id" class="form-select select2">
                    <option value="">-- Choose Semester --</option>
                    <?php for($s=1; $s<=8; $s++): ?>
                        <option value="<?= $s; ?>" <?= ($sem_id==$s) ? 'selected' : ''; ?>>
                            <?= $s; ?><?= ($s==1?'st':($s==2?'nd':($s==3?'rd':'th'))) ?> Semester
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 d-grid align-items-end">
                <button class="btn btn-dark fw-bold py-2" style="background: var(--navy); height: 40px;">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <?php
        if($sem_id){
            // Prepare Query for Subjects
            $sql = "SELECT sm.* FROM subjects_master sm
                    JOIN subjects_department_semester sds ON sm.id=sds.subject_id
                    WHERE sm.department_id=? AND sm.semester_id=? AND $batch_condition
                    ORDER BY sm.subject_name ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $dept_id, $sem_id);
            $stmt->execute();
            $subjects = $stmt->get_result();

            if($subjects->num_rows > 0){
                while($sub = $subjects->fetch_assoc()):
        ?>
                    <div class="col-md-4 col-sm-6">
                        <a href="student_upload_pdf.php?subject_id=<?= $sub['id']; ?>" class="text-decoration-none">
                            <div class="subject-card shadow-sm">
                                <div class="icon-circle">
                                    <i class="fas fa-scroll"></i>
                                </div>
                                <div class="subject-name"><?= htmlspecialchars($sub['subject_name']); ?></div>
                                <div class="subject-code"><?= htmlspecialchars($sub['subject_code']); ?></div>
                                <div class="mt-3 text-primary small fw-bold">
                                    View Notes <i class="fas fa-chevron-right ms-1"></i>
                                </div>
                            </div>
                        </a>
                    </div>
        <?php
                endwhile;
            } else {
                echo '
                <div class="text-center py-5 w-100">
                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No subjects found for '. $sem_id .'th Semester ('. ucfirst($batch) .' Batch).</h5>
                </div>';
            }
        } else {
            echo '
            <div class="text-center py-5 w-100">
                <img src="https://cdn-icons-png.flaticon.com/512/2997/2997608.png" style="width: 120px; opacity: 0.6;" class="mb-3">
                <h5 class="text-muted">Please select a semester to view available subjects.</h5>
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
    $('.select2').select2({ width: '100%' });

    // Auto-reload on change
    $('#batch_id, #semester_id').change(function(){
        let batch = $('#batch_id').val();
        let sem = $('#semester_id').val();
        if(sem){
            window.location.href = `student_uploadnotes.php?dept_id=<?= $dept_id; ?>&batch=${batch}&sem_id=${sem}`;
        }
    });
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>