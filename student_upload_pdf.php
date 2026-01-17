<?php
session_start();
require 'db_config.php';
require 'notification_helper.php';

/* ---------------- AUTH CHECK ---------------- */
if(!isset($_SESSION['student_id'])){
    header("Location: login.php");
    exit();
}
$student_id = $_SESSION['student_id'];

/* ---------------- GET PARAMETERS ---------------- */
$subject_id = intval($_GET['subject_id'] ?? 0);
$category   = $_GET['category'] ?? 'notes'; 

/* ---------------- CATEGORY CONFIG ---------------- */
$catLabels = [
    'notes'         => ['📚','Notes', 'primary'],
    'syllabus'      => ['📘','Syllabus', 'success'],
    'past_question' => ['📝','Past Question', 'warning']
];
$catIcon  = $catLabels[$category][0] ?? '📁';
$catTitle = $catLabels[$category][1] ?? ucfirst($category);
$catColor = $catLabels[$category][2] ?? 'secondary';

/* ---------------- SUBJECT INFO ---------------- */
$subject_stmt = $conn->prepare("SELECT * FROM subjects_master WHERE id=?");
$subject_stmt->bind_param("i", $subject_id);
$subject_stmt->execute();
$subject = $subject_stmt->get_result()->fetch_assoc();

if(!$subject) die("Invalid subject!");

/* ---------------- FILE UPLOAD LOGIC ---------------- */
$uploadMsg = "";
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_file'])){
    $title = htmlspecialchars($_POST['title']);
    $file  = $_FILES['file_upload'];
    $declaration_agreed = isset($_POST['declaration_agreed']) && $_POST['declaration_agreed'] === 'on';
    
    // Check if student has agreed to declaration
    if(!$declaration_agreed){
        $uploadMsg = "<div class='alert alert-danger py-2 small'><i class='fa fa-exclamation-circle'></i> You must agree to the declaration to upload!</div>";
    } else {
        $allowed = ['pdf','doc','docx','pptx','zip'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if(!in_array($ext, $allowed)){
            $uploadMsg = "<div class='alert alert-danger py-2 small'><i class='fa fa-times-circle me-1'></i><strong>Invalid File Type!</strong> केवल PDF, DOC, DOCX, PPTX, ZIP files मात्र upload गर्न सकिन्छ।</div>";
        } else {
            $dir = "uploads/notes/"; 
            if(!is_dir($dir)) mkdir($dir, 0777, true);

            $newName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $file['name']);
            $path = $dir . $newName;

            if(move_uploaded_file($file['tmp_name'], $path)){
                // Insert notes with pending approval status
                $approval_status = 'pending';
                $stmt = $conn->prepare("INSERT INTO notes (department_id, semester_id, subject_id, note_type, title, file_path, uploader_id, uploader_role, approval_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'student', ?, NOW())");
                
                $stmt->bind_param("iiisssss", 
                    $subject['department_id'], 
                    $subject['semester_id'], 
                    $subject_id, 
                    $category, 
                    $title, 
                    $path, 
                    $student_id,
                    $approval_status
                );

                if($stmt->execute()){
                    $upload_id = $stmt->insert_id;
                    
                    // Save declaration record
                    $declaration_text = "I declare that this is my original and authentic study material. I understand that providing false or plagiarized content may result in penalties.";
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    
                    $decl_stmt = $conn->prepare("INSERT INTO student_upload_declarations (upload_id, student_id, declaration_text, ip_address) VALUES (?, ?, ?, ?)");
                    $decl_stmt->bind_param("iiss", $upload_id, $student_id, $declaration_text, $ip_address);
                    $decl_stmt->execute();
                    
                    // Get teachers assigned to this subject and notify them
                    $teachers_query = "SELECT DISTINCT t.id FROM teachers t 
                                      JOIN teacher_subjects ts ON t.id = ts.teacher_id 
                                      WHERE ts.subject_map_id = ?";
                    $teachers_stmt = $conn->prepare($teachers_query);
                    $teachers_stmt->bind_param("i", $subject_id);
                    $teachers_stmt->execute();
                    $teachers_result = $teachers_stmt->get_result();
                    
                    // Notify each teacher
                    while($teacher = $teachers_result->fetch_assoc()){
                        // Count pending uploads for this teacher's subject
                        $count_query = "SELECT COUNT(*) as cnt FROM notes n 
                                       WHERE n.subject_id = ? AND n.approval_status = 'pending'";
                        $count_stmt = $conn->prepare($count_query);
                        $count_stmt->bind_param("i", $subject_id);
                        $count_stmt->execute();
                        $count_data = $count_stmt->get_result()->fetch_assoc();
                        
                        notifyTeacherPendingUploads($teacher['id'], $count_data['cnt'], $subject['subject_name'], $conn);
                    }
                    
                    header("Location: student_upload_pdf.php?subject_id=$subject_id&category=$category&success=1");
                    exit();
                }
            }
        }
    }
}

/* ---------------- DELETE LOGIC ---------------- */
if(isset($_GET['delete'])){
    $del_id = intval($_GET['delete']);
    $check = $conn->prepare("SELECT file_path FROM notes WHERE id=? AND uploader_id=? AND uploader_role='student'");
    $check->bind_param("ii", $del_id, $student_id);
    $check->execute();
    $res = $check->get_result();
    if($f = $res->fetch_assoc()){
        if(file_exists($f['file_path'])) unlink($f['file_path']);
        $conn->query("DELETE FROM notes WHERE id=$del_id");
        header("Location: student_upload_pdf.php?subject_id=$subject_id&category=$category&deleted=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= $catTitle ?> - <?= htmlspecialchars($subject['subject_name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --navy: #001f4d; }
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .category-tabs { background: #fff; padding: 10px; border-radius: 50px; display: inline-flex; gap: 5px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .cat-tab { text-decoration: none; padding: 10px 25px; border-radius: 40px; color: #555; font-weight: 600; transition: 0.3s; }
        .cat-tab.active { background: var(--navy); color: #fff; }
        .upload-card { border: none; border-radius: 15px; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .file-card { border: none; border-radius: 15px; background: #fff; padding: 18px; margin-bottom: 12px; border-left: 6px solid var(--navy); transition: 0.2s; }
        .file-card:hover { transform: scale(1.01); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .file-icon { width: 40px; height: 40px; object-fit: contain; }
    </style>
</head>
<body>

<?php include 'student_header.php'; ?>

<div class="container py-4">
    <a href="student_uploadnotes.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mb-3">
        <i class="fa fa-arrow-left me-1"></i> Back to Subjects
    </a>
</div>
    <div class="text-center mb-5">
        <h2 class="fw-bold mb-1"><?= htmlspecialchars($subject['subject_name']) ?></h2>
        <p class="text-muted small mb-4">Upload and Download Study Materials</p>
        
        <div class="category-tabs">
            <a href="?subject_id=<?= $subject_id ?>&category=notes" class="cat-tab <?= $category=='notes'?'active':'' ?>">📚 Notes</a>
            <a href="?subject_id=<?= $subject_id ?>&category=syllabus" class="cat-tab <?= $category=='syllabus'?'active':'' ?>">📘 Syllabus</a>
            <a href="?subject_id=<?= $subject_id ?>&category=past_question" class="cat-tab <?= $category=='past_question'?'active':'' ?>">📝 Past Questions</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="upload-card p-4 sticky-top" style="top: 100px;">
                <h5 class="fw-bold mb-3">Add <?= $catTitle ?></h5>
                <?= $uploadMsg ?>
                <?php if(isset($_GET['success'])) echo "<div class='alert alert-success py-2 small'><i class='fa fa-check-circle me-1'></i>Upload Successful! अब teacher verification को लागि pending छ।</div>"; ?>
                <?php if(isset($_GET['deleted'])) echo "<div class='alert alert-info py-2 small'><i class='fa fa-trash me-1'></i>Upload deleted successfully.</div>"; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Title / शीर्षक</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Chapter 1-3 Notes, Unit Test Syllabus" required>
                        <small class="text-muted">Brief description of your upload</small>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">File / फाइल</label>
                        <input type="file" name="file_upload" class="form-control" accept=".pdf,.doc,.docx,.pptx,.zip" required>
                        <small class="text-muted"><i class="fa fa-info-circle me-1"></i>Accepted: PDF, DOC, DOCX, PPTX, ZIP (Max 10MB)</small>
                    </div>
                    
                    <!-- Self Declaration Section -->
                    <div class="mb-3 p-3 bg-light rounded-3 border-start border-4 border-warning">
                        <h6 class="mb-2 fw-bold text-dark">
                            <i class="fa fa-exclamation-triangle text-warning me-2"></i>Self Declaration
                        </h6>
                        <p class="small text-muted mb-2" style="line-height: 1.5;">
                            I confirm that this is my original and authentic study material. I understand that providing false or plagiarized content may result in academic penalties, marks deduction, or other disciplinary action.
                        </p>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="declaration_agreed" name="declaration_agreed" required>
                            <label class="form-check-label small" for="declaration_agreed">
                                I agree to the above declaration
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" name="upload_file" class="btn w-100 fw-bold text-white" style="background: var(--navy);">
                        Upload to <?= $catTitle ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <h5 class="fw-bold mb-4"><?= $catIcon ?> Recent <?= $catTitle ?></h5>

            <?php
            // Simple query without JOIN to avoid column name errors
            $stmt = $conn->prepare("SELECT * FROM notes WHERE subject_id=? AND note_type=? ORDER BY id DESC");
            $stmt->bind_param("is", $subject_id, $category);
            $stmt->execute();
            $files = $stmt->get_result();

            if($files->num_rows > 0): 
                while($f = $files->fetch_assoc()):
                    $fext = pathinfo($f['file_path'], PATHINFO_EXTENSION);
                    
                    // Determine approval badge
                    $approval_badge = '';
                    $approval_color = '';
                    if($f['approval_status'] == 'approved'){
                        $approval_badge = '<span class="badge bg-success ms-1"><i class="fa fa-check-circle me-1"></i>Approved</span>';
                        $approval_color = 'border-success';
                    } else if($f['approval_status'] == 'rejected'){
                        $approval_badge = '<span class="badge bg-danger ms-1"><i class="fa fa-times-circle me-1"></i>Rejected</span>';
                        $approval_color = 'border-danger';
                    } else if($f['approval_status'] == 'plagiarized'){
                        $approval_badge = '<span class="badge bg-danger ms-1"><i class="fa fa-ban me-1"></i>Flagged</span>';
                        $approval_color = 'border-danger';
                    } else {
                        $approval_badge = '<span class="badge bg-warning text-dark ms-1"><i class="fa fa-hourglass-half me-1"></i>Pending Approval</span>';
                        $approval_color = 'border-warning';
                    }
            ?>
                <div class="file-card d-flex align-items-center justify-content-between <?= $approval_color ?>">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="me-3 fs-3 text-secondary">
                            <i class="fa fa-file-<?= ($fext=='pdf')?'pdf':'alt' ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-bold">
                                <?= htmlspecialchars($f['title']) ?>
                                <?= $approval_badge ?>
                            </h6>
                            <small class="text-muted">
                                <span class="badge bg-light text-dark border me-1"><?= strtoupper($fext) ?></span>
                                <?= date('M d, Y', strtotime($f['created_at'])) ?>
                                <?php if($f['approval_status'] == 'approved'): ?>
                                    <i class="fa fa-check text-success ms-1"></i> Approved <?= date('M d', strtotime($f['approved_at'])) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= $f['file_path'] ?>" target="_blank" class="btn btn-sm btn-light border"><i class="fa fa-eye"></i></a>
                        <a href="<?= $f['file_path'] ?>" download class="btn btn-sm btn-outline-primary"><i class="fa fa-download"></i></a>
                        
                        <?php if($f['uploader_id'] == $student_id && $f['approval_status'] == 'pending'): ?>
                            <a href="?subject_id=<?= $subject_id ?>&category=<?= $category ?>&delete=<?= $f['id'] ?>" 
                               onclick="return confirm('Delete this pending upload?')" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <div class="text-center py-5 bg-white rounded-4 border">
                    <p class="text-muted">No files in <?= $catTitle ?>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>