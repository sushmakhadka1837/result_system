<?php
session_start();
require 'db_config.php';

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
    
    $allowed = ['pdf','doc','docx','zip'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if(!in_array($ext, $allowed)){
        $uploadMsg = "<div class='alert alert-danger py-2 small'>PDF, DOC, ZIP matra support hunchha!</div>";
    } else {
        $dir = "uploads/notes/"; 
        if(!is_dir($dir)) mkdir($dir, 0777, true);

        $newName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $file['name']);
        $path = $dir . $newName;

        if(move_uploaded_file($file['tmp_name'], $path)){
            // $category lai note_type ma save garne logic
            $stmt = $conn->prepare("INSERT INTO notes (department_id, semester_id, subject_id, note_type, title, file_path, uploader_id, uploader_role, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'student', NOW())");
            
            $stmt->bind_param("iiisssi", 
                $subject['department_id'], 
                $subject['semester_id'], 
                $subject_id, 
                $category, 
                $title, 
                $path, 
                $student_id
            );

            if($stmt->execute()){
                header("Location: student_upload_pdf.php?subject_id=$subject_id&category=$category&success=1");
                exit();
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
                <?php if(isset($_GET['success'])) echo "<div class='alert alert-success py-2 small'>Saved to $catTitle!</div>"; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">File</label>
                        <input type="file" name="file_upload" class="form-control" required>
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
            ?>
                <div class="file-card d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="me-3 fs-3 text-secondary">
                            <i class="fa fa-file-<?= ($fext=='pdf')?'pdf':'alt' ?>"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($f['title']) ?></h6>
                            <small class="text-muted">
                                <span class="badge bg-light text-dark border me-1"><?= strtoupper($fext) ?></span>
                                <?= date('M d, Y', strtotime($f['created_at'])) ?>
                            </small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= $f['file_path'] ?>" target="_blank" class="btn btn-sm btn-light border"><i class="fa fa-eye"></i></a>
                        <a href="<?= $f['file_path'] ?>" download class="btn btn-sm btn-outline-primary"><i class="fa fa-download"></i></a>
                        
                        <?php if($f['uploader_id'] == $student_id): ?>
                            <a href="?subject_id=<?= $subject_id ?>&category=<?= $category ?>&delete=<?= $f['id'] ?>" 
                               onclick="return confirm('Delete?')" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></a>
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