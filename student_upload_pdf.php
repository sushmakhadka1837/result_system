<?php
session_start();
require 'db_config.php';

/* ---------------- AUTH CHECK ---------------- */
if(!isset($_SESSION['student_id'])){
    header("Location: login.php");
    exit();
}
$student_id = $_SESSION['student_id'];

/* ---------------- GET PARAMS ---------------- */
$subject_id = intval($_GET['subject_id'] ?? 0);
$category   = $_GET['category'] ?? 'notes';

/* ---------------- SUBJECT INFO ---------------- */
$subject_q = $conn->query("SELECT * FROM subjects_master WHERE id='$subject_id'");
if($subject_q->num_rows == 0){
    die("Invalid subject!");
}
$subject = $subject_q->fetch_assoc();

/* ---------------- DELETE FUNCTION ---------------- */
if(isset($_GET['delete']) && intval($_GET['delete'])>0){
    $del_id = intval($_GET['delete']);
    $note_q = $conn->query("SELECT * FROM notes WHERE id='$del_id' AND uploader_id='$student_id' AND uploader_role='student'");
    if($note_q->num_rows>0){
        $note = $note_q->fetch_assoc();
        if(file_exists($note['file_path'])) unlink($note['file_path']);
        $conn->query("DELETE FROM notes WHERE id='$del_id'");
        header("Location: student_upload_pdf.php?subject_id=$subject_id&category=$category&deleted=1");
        exit();
    }
}

$uploadMsg = "";

/* ---------------- FILE UPLOAD ---------------- */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['upload_file'])){
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $file  = $_FILES['file_upload'];

    $allowedExt = ['pdf','doc','docx','zip'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $check = $conn->query("SELECT id FROM notes 
                           WHERE subject_id='$subject_id' 
                           AND note_type='$category' 
                           AND title='$title'");
    if($check->num_rows > 0){
        $uploadMsg = "This title already exists!";
    }
    elseif(!in_array($ext, $allowedExt)){
        $uploadMsg = "Only PDF, DOC, DOCX, ZIP allowed!";
    }
    elseif($file['size'] > 50 * 1024 * 1024){
        $uploadMsg = "File too large (max 50MB)";
    }
    elseif($file['error'] !== 0){
        $uploadMsg = "Upload error code: ".$file['error'];
    }
    else{
        $dir = __DIR__."/uploads/$category/";
        if(!is_dir($dir)) mkdir($dir,0777,true);

        $newName = time()."_".$file['name'];
        $path = "uploads/$category/".$newName;

        if(move_uploaded_file($file['tmp_name'], $path)){
            $conn->query("INSERT INTO notes
                (department_id, semester_id, subject_id, note_type, title, file_path, uploader_id, uploader_role, created_at)
                VALUES
                ('{$subject['department_id']}','{$subject['semester_id']}','$subject_id','$category','$title','$path','$student_id','student',NOW())
            ");
            header("Location: student_upload_pdf.php?subject_id=$subject_id&category=$category&success=1");
            exit();
        } else {
            $uploadMsg = "File move failed!";
        }
    }
}

if(isset($_GET['success'])) $uploadMsg="Notes uploaded successfully!";
if(isset($_GET['deleted'])) $uploadMsg="Note deleted successfully!";

/* ---------------- FETCH FILES ---------------- */
$files = $conn->query("SELECT * FROM notes WHERE subject_id='$subject_id' AND note_type='$category' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
<title>Upload Notes - Student</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body{display:flex;padding:20px;font-family:Arial;background:#f9f9f9;}
.sidebar{width:300px;background:#fff;padding:20px;border-right:1px solid #ddd;box-shadow:0 0 10px rgba(0,0,0,0.05);}
.content{flex:1;padding:20px;overflow-y:auto;}
.file-card{
    border:1px solid #ddd;
    padding:10px;
    border-radius:5px;
    margin-bottom:15px;
    background:#fff;
    display:flex;
    align-items:center;
    gap:15px;
    box-shadow:0 2px 5px rgba(0,0,0,0.05);
    transition: transform 0.2s, box-shadow 0.2s;
}
.file-card:hover{
    transform: translateY(-3px);
    box-shadow:0 5px 15px rgba(0,0,0,0.2);
}
.file-card img{
    width:80px;
    height:100px;
    object-fit:cover;
    border:1px solid #ccc;
    border-radius:3px;
    cursor:pointer;
}
.file-card img:hover{
    opacity:0.8;
}
.file-info{flex:1;cursor:pointer;}
.file-info h6{margin:0 0 5px;}
.file-info p{margin:0;font-size:0.9em;color:#555;}
.file-actions a{margin-right:10px;color:#007bff;text-decoration:none;}
.file-actions a:hover{color:#0056b3;}
.file-actions .delete:hover{color:red;}
</style>
<script>
function copyLink(link){
    navigator.clipboard.writeText(window.location.origin + "/" + link)
        .then(()=>{alert('Link copied to clipboard!');});
}
function openFile(link){
    window.open(link,'_blank');
}
</script>
</head>

<body>

<div class="sidebar">
    <h4>Upload Notes</h4>
    <?php if($uploadMsg!="") echo "<p style='color:green'>$uploadMsg</p>"; ?>
    <form method="POST" enctype="multipart/form-data">
        <label>Title</label>
        <input class="form-control mb-2" type="text" name="title" required>
        <label>File (PDF/DOC/DOCX/ZIP – max 50MB)</label>
        <input class="form-control mb-3" type="file" name="file_upload" required>
        <button class="btn btn-primary w-100" name="upload_file">Upload</button>
    </form>
</div>

<div class="content">
    <h3>Notes Files</h3>
    <?php if($files->num_rows>0): ?>
        <?php while($f=$files->fetch_assoc()): ?>
        <?php
            $ext = pathinfo($f['file_path'], PATHINFO_EXTENSION);
            if($ext=='pdf') $icon="https://cdn-icons-png.flaticon.com/512/337/337946.png";
            elseif(in_array($ext, ['doc','docx'])) $icon="https://cdn-icons-png.flaticon.com/512/337/337932.png";
            elseif($ext=='zip') $icon="https://cdn-icons-png.flaticon.com/512/716/716784.png";
            else $icon="https://cdn-icons-png.flaticon.com/512/109/109612.png";
        ?>
        <div class="file-card" onclick="openFile('<?= $f['file_path'] ?>')">
            <img src="<?= $icon ?>" alt="<?= strtoupper($ext) ?>">
            <div class="file-info">
                <h6><?= htmlspecialchars($f['title']) ?></h6>
                <p><b>Uploader:</b> <?= ucfirst($f['uploader_role']) ?></p>
                <div class="file-actions mt-2">
                    <a href="<?= $f['file_path'] ?>" target="_blank" title="View"><i class="fa fa-eye"></i></a>
                    <a href="<?= $f['file_path'] ?>" download title="Download"><i class="fa fa-download"></i></a>
                    <a href="javascript:void(0)" onclick="copyLink('<?= $f['file_path'] ?>')" title="Copy Link"><i class="fa fa-share-alt"></i></a>
                    <?php if($f['uploader_id']==$student_id && $f['uploader_role']=='student'): ?>
                        <a href="?subject_id=<?= $subject_id ?>&category=<?= $category ?>&delete=<?= $f['id'] ?>" onclick="return confirm('Delete this note?')" title="Delete"><i class="fa fa-trash"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No notes uploaded yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
