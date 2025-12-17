<?php
session_start();
require 'db_config.php';

// Department
$dept_id = intval($_GET['dept_id'] ?? 0);
$dept = $conn->query("SELECT * FROM departments WHERE id=$dept_id")->fetch_assoc() ?? ['department_name'=>'Unknown'];

// Selected semester
$sem_id = intval($_GET['sem_id'] ?? 0);

// Selected batch
$batch = $_GET['batch'] ?? 'old';

// Batch condition
$batch_condition = ($batch == 'old')
    ? "(sds.batch_year IS NULL OR sds.batch_year = '')"
    : "sds.batch_year = 1";
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($dept['department_name']); ?> - Notes</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        .subject-card { 
            padding:15px; 
            border-radius:8px; 
            box-shadow:0 2px 6px rgba(0,0,0,0.1); 
            margin-bottom:10px; 
            transition:0.3s; 
        }
        .subject-card:hover { 
            transform:translateY(-3px); 
            box-shadow:0 5px 15px rgba(0,0,0,0.2); 
        }
    </style>
</head>
<body>

<div class="container my-5">
    <h3><?php echo $dept['department_name']; ?> Department</h3>

    <div class="row mb-3">
        <div class="col-md-3">
            <label>Batch</label>
            <select id="batch_id" class="form-control">
                <option value="old" <?php if($batch=='old') echo 'selected'; ?>>Old Batch</option>
                <option value="new" <?php if($batch=='new') echo 'selected'; ?>>New Batch</option>
            </select>
        </div>

        <div class="col-md-3">
            <label>Semester</label>
            <select id="semester_id" class="form-control">
                <option value="">Select Semester</option>
                <?php for($s=1; $s<=8; $s++): ?>
                    <option value="<?php echo $s; ?>" <?php if($sem_id==$s) echo 'selected'; ?>>
                        <?php echo $s; ?> Semester
                    </option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <script>
    $(document).ready(function(){
        $('#batch_id, #semester_id').select2();

        $('#batch_id, #semester_id').change(function(){
            let batch = $('#batch_id').val();
            let sem = $('#semester_id').val();

            if(sem){
                window.location.href =
                `student_uploadnotes.php?dept_id=<?php echo $dept_id; ?>&batch=${batch}&sem_id=${sem}`;
            }
        });
    });
    </script>

    <div class="row mt-4">
    <?php
    if($sem_id){
        $sql = "
            SELECT sm.*
            FROM subjects_master sm
            JOIN subjects_department_semester sds ON sm.id = sds.subject_id
            WHERE sm.department_id = $dept_id
              AND sm.semester_id = $sem_id
              AND $batch_condition
            ORDER BY sm.subject_name ASC
        ";

        $subjects = $conn->query($sql);

        if($subjects->num_rows > 0){
            while($sub = $subjects->fetch_assoc()):
    ?>
        <div class="col-md-4">
        <a href="student_notes_category.php?subject_id=<?php echo $sub['id']; ?>" 
   style="text-decoration:none; color:inherit;">
    <div class="subject-card">
        <h5><?php echo $sub['subject_name']; ?></h5>
        <p class="text-muted"><?php echo $sub['subject_code']; ?></p>
    </div>
</a>

        </div>
    <?php
            endwhile;
        } else {
            echo "<p class='text-muted'>No subjects available for this batch & semester.</p>";
        }
    } else {
        echo "<p class='text-muted'>Please select batch and semester.</p>";
    }
    ?>
    </div>

</div>

</body>
</html>
