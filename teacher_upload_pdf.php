<?php
session_start();
if(!isset($_SESSION['teacher_id'])){
    header("Location: teacher_login.php");
    exit();
}

require 'db_config.php';
$teacher_id = $_SESSION['teacher_id'];

$subject_id = intval($_GET['subject_id'] ?? 0);
$category   = $_GET['category'] ?? 'notes';

/* ---------- DELETE NOTE ---------- */
if(isset($_GET['delete'])){
    $delete_id = intval($_GET['delete']);
    $check = $conn->query("
        SELECT file_path FROM notes 
        WHERE id='$delete_id' 
        AND uploader_id='$teacher_id'
        AND uploader_role='teacher'
    ");
    if($check->num_rows == 1){
        $row = $check->fetch_assoc();
        if(file_exists($row['file_path'])){
            unlink($row['file_path']);
        }
        $conn->query("DELETE FROM notes WHERE id='$delete_id'");
        header("Location: teacher_upload_pdf.php?subject_id=$subject_id&category=$category");
        exit();
    }
}

/* ---------- SUBJECT CHECK ---------- */
$subject_q = $conn->query("SELECT * FROM subjects_master WHERE id='$subject_id'");
if($subject_q->num_rows == 0){
    die("Invalid subject!");
}
$subject = $subject_q->fetch_assoc();

$uploadMsg = "";

/* ---------- FILE UPLOAD ---------- */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_file'])){
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $file  = $_FILES['file_upload'];

    $allowedExt = ['pdf','doc','docx','zip'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // duplicate title check
    $check = $conn->query("
        SELECT id FROM notes 
        WHERE subject_id='$subject_id' 
        AND note_type='$category' 
        AND title='$title'
        AND uploader_role='teacher'
    ");

    if($check->num_rows > 0){
        $uploadMsg = "This title already exists!";
    }
    elseif(!in_array($ext, $allowedExt)){
        $uploadMsg = "Only PDF, DOC, DOCX, ZIP allowed!";
    }
    elseif($file['size'] > 50 * 1024 * 1024){
        $uploadMsg = "File too large (Max 50MB)";
    }
    elseif($file['error'] !== 0){
        $uploadMsg = "Upload error!";
    }
    else{
        $dir = __DIR__ . "/uploads/teacher_notes/$category/";
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }

        $newName = time() . "_" . basename($file['name']);
        $path = "uploads/teacher_notes/$category/" . $newName;

        if(move_uploaded_file($file['tmp_name'], $path)){
            $conn->query("
                INSERT INTO notes 
                (department_id, semester_id, subject_id, title, note_type, file_path, uploader_id, uploader_role, created_at)
                VALUES
                ('{$subject['department_id']}','{$subject['semester_id']}','$subject_id',
                 '$title','$category','$path','$teacher_id','teacher',NOW())
            ");

            header("Location: teacher_upload_pdf.php?subject_id=$subject_id&category=$category&success=1");
            exit();
        } else {
            $uploadMsg = "File upload failed!";
        }
    }
}

if(isset($_GET['success'])){
    $uploadMsg = "Notes uploaded successfully!";
}

/* ---------- FETCH ALL NOTES ---------- */
$files = $conn->query("
    SELECT 
        n.*,
        CASE 
            WHEN n.uploader_role='teacher' THEN t.full_name
            ELSE s.full_name
        END AS uploader_name
    FROM notes n
    LEFT JOIN teachers t 
        ON t.id = n.uploader_id AND n.uploader_role='teacher'
    LEFT JOIN students s 
        ON s.id = n.uploader_id AND n.uploader_role='student'
    WHERE n.subject_id='$subject_id'
    AND n.note_type='$category'
    ORDER BY n.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Upload Notes - Teacher</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body{
    display:flex;
    padding:20px;
    background:#f4f6f8;
    font-family: Arial;
}
.sidebar{
    width:320px;
    background:#fff;
    padding:20px;
    border-radius:8px;
    box-shadow:0 0 10px rgba(0,0,0,0.08);
}
.content{
    flex:1;
    margin-left:20px;
}
.file-card{
    background:#fff;
    border-radius:8px;
    padding:15px;
    display:flex;
    gap:15px;
    margin-bottom:15px;
    box-shadow:0 3px 8px rgba(0,0,0,0.08);
    align-items:center;
    transition: transform 0.2s, box-shadow 0.2s;
}
.file-card:hover{
    transform: translateY(-3px);
    box-shadow:0 5px 15px rgba(0,0,0,0.2);
}
.file-card img{
    width:70px;
    height:90px;
    object-fit:contain;
    cursor:pointer;
}
.file-card img:hover{
    opacity:0.8;
}
.file-info h6{
    margin:0;
    cursor:pointer;
}
.file-info small{
    color:#666;
}
.action-icons a{
    margin-left:10px;
    color:#555;
    font-size:16px;
}
.action-icons a:hover{
    color:#0d6efd;
}
.action-icons .delete:hover{
    color:red;
}
.back-btn{
    margin-bottom:15px;
}
</style>
<script>
function copyLink(link){
    navigator.clipboard.writeText(window.location.origin + "/" + link);
    alert("Link copied!");
}

function openFile(link){
    window.open(link,'_blank');
}

function goBack(){
    window.history.back();
}
</script>
</head>
<body>

<!-- LEFT UPLOAD -->
<div class="sidebar">
    <!-- BACK BUTTON -->
    <button class="btn btn-secondary back-btn" onclick="goBack()">&larr; Back</button>

    <h5>Upload Notes (Teacher)</h5>
    <?php if($uploadMsg!=""): ?>
        <div class="alert alert-success"><?= $uploadMsg ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label class="mt-2">Title</label>
        <input type="text" name="title" class="form-control mb-2" required>

        <label>File (PDF/DOC/DOCX/ZIP – max 50MB)</label>
        <input type="file" name="file_upload" class="form-control mb-3" required>

        <button name="upload_file" class="btn btn-primary w-100">
            Upload Notes
        </button>
    </form>
</div>

<!-- RIGHT CONTENT -->
<div class="content">
    <h4>Uploaded Notes</h4>

    <?php if($files->num_rows > 0): ?>
        <?php while($f = $files->fetch_assoc()): ?>
            <?php
            $ext = pathinfo($f['file_path'], PATHINFO_EXTENSION);
            if($ext=='pdf')      $icon="https://cdn-icons-png.flaticon.com/512/337/337946.png";
            elseif(in_array($ext,['doc','docx'])) $icon="https://cdn-icons-png.flaticon.com/512/337/337932.png";
            elseif($ext=='zip')  $icon="https://cdn-icons-png.flaticon.com/512/716/716784.png";
            else                 $icon="https://cdn-icons-png.flaticon.com/512/109/109612.png";
            ?>

            <div class="file-card">
                <img src="<?= $icon ?>" onclick="openFile('<?= $f['file_path'] ?>')" title="Click to view">

                <div class="file-info flex-grow-1" onclick="openFile('<?= $f['file_path'] ?>')">
                    <h6><?= htmlspecialchars($f['title']) ?></h6>
                    <small>
                        Uploaded by: <b><?= htmlspecialchars($f['uploader_name']) ?></b>
                        (<?= ucfirst($f['uploader_role']) ?>)
                    </small>
                </div>

                <div class="action-icons text-end">
                    <a href="<?= $f['file_path'] ?>" download title="Download">
                        <i class="fas fa-download"></i>
                    </a>

                    <a href="<?= $f['file_path'] ?>" target="_blank" title="Open">
                        <i class="fas fa-eye"></i>
                    </a>

                    <a href="javascript:void(0)"
                       onclick="copyLink('<?= $f['file_path'] ?>')"
                       title="Copy Link">
                        <i class="fas fa-share-alt"></i>
                    </a>

                    <?php if($f['uploader_role']=='teacher' && $f['uploader_id']==$teacher_id): ?>
                        <a href="?subject_id=<?= $subject_id ?>&category=<?= $category ?>&delete=<?= $f['id'] ?>"
                           onclick="return confirm('Delete this note?')"
                           class="delete" title="Delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-muted">No notes uploaded yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
