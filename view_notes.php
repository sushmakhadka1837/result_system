<?php
session_start();
require 'db_config.php';

$subject_id = intval($_GET['subject_id'] ?? 0);
$category = $_GET['category'] ?? 'notes';

$subject_q = $conn->query("SELECT * FROM subjects_master WHERE id='$subject_id'");
if($subject_q->num_rows==0){
    die("Invalid subject!");
}
$subject = $subject_q->fetch_assoc();

$notes_q = $conn->query("
    SELECT 
        n.*,
        CASE 
            WHEN n.uploader_role='teacher' THEN t.full_name
            ELSE s.full_name
        END AS uploader_name
    FROM notes n
    LEFT JOIN teachers t ON t.id=n.uploader_id AND n.uploader_role='teacher'
    LEFT JOIN students s ON s.id=n.uploader_id AND n.uploader_role='student'
    WHERE n.subject_id='$subject_id'
      AND n.note_type='$category'
    ORDER BY n.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($subject['subject_name']) ?> - <?= ucfirst($category) ?></title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body{
    background:#f4f6f8;
    font-family: Arial, sans-serif;
}

/* MAIN CONTENT */
.notes-section{
    min-height: calc(100vh - 160px);
    padding: 60px 0 80px;
}

/* HEADER CARD */
.page-header{
    background: linear-gradient(135deg,#0d6efd,#4f8dfd);
    color:#fff;
    padding:25px 30px;
    border-radius:12px;
    margin-bottom:30px;
}

.page-header h4{
    margin:0;
    font-weight:600;
}

.badge-category{
    background:#fff;
    color:#0d6efd;
    font-size:13px;
}

/* SEARCH */
.search-box{
    max-width:300px;
}

/* FILE CARD */
.file-card{
    background:#fff;
    border-radius:12px;
    padding:18px;
    display:flex;
    gap:18px;
    align-items:center;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    transition:0.3s;
    height:100%;
}

.file-card:hover{
    transform:translateY(-6px);
    box-shadow:0 12px 30px rgba(0,0,0,0.15);
}

.file-card img{
    width:70px;
    height:90px;
    object-fit:contain;
    border-radius:6px;
}

.file-info h6{
    margin:0;
    font-size:1.05rem;
    font-weight:600;
}

.file-info small{
    color:#666;
}

/* ACTIONS */
.action-icons a{
    margin-left:10px;
    font-size:17px;
    color:#555;
}
.action-icons a:hover{
    color:#0d6efd;
}

/* EMPTY STATE */
.empty-box{
    background:#fff;
    padding:60px;
    text-align:center;
    border-radius:12px;
    color:#777;
}

/* RESPONSIVE */
@media(max-width:768px){
    .file-card{
        flex-direction:column;
        align-items:flex-start;
    }
}
</style>

<script>
function changeCategory(cat){
    window.location.href = `view_notes.php?subject_id=<?= $subject_id ?>&category=${cat}`;
}

function copyLink(link){
    navigator.clipboard.writeText(window.location.origin + "/" + link);
    alert("Link copied!");
}

function openFile(link){
    window.open(link,'_blank');
}

/* SEARCH FILTER */
function filterNotes(){
    const input = document.getElementById("searchInput").value.toLowerCase();
    document.querySelectorAll(".note-col").forEach(card=>{
        card.style.display = card.innerText.toLowerCase().includes(input) ? "" : "none";
    });
}
</script>
</head>

<body>

<?php include 'header.php'; ?>

<section class="notes-section">
<div class="container">

    <!-- HEADER CARD -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h4><?= htmlspecialchars($subject['subject_name']) ?></h4>
            <span class="badge badge-category mt-2"><?= ucfirst($category) ?></span>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0 align-items-center">
            <input type="text" id="searchInput" onkeyup="filterNotes()" class="form-control search-box" placeholder="Search notes...">
            <select onchange="changeCategory(this.value)" class="form-select">
                <option value="notes" <?= $category=='notes'?'selected':'' ?>>Notes</option>
                <option value="syllabus" <?= $category=='syllabus'?'selected':'' ?>>Syllabus</option>
                <option value="past_questions" <?= $category=='past_questions'?'selected':'' ?>>Past Questions</option>
            </select>
        </div>
    </div>

    <!-- FILES -->
    <div class="row g-4">
        <?php if($notes_q->num_rows>0): ?>
            <?php while($f=$notes_q->fetch_assoc()): ?>
                <?php
                $ext = pathinfo($f['file_path'], PATHINFO_EXTENSION);
                if($ext=='pdf') $icon="https://cdn-icons-png.flaticon.com/512/337/337946.png";
                elseif(in_array($ext,['doc','docx'])) $icon="https://cdn-icons-png.flaticon.com/512/337/337932.png";
                elseif($ext=='zip') $icon="https://cdn-icons-png.flaticon.com/512/716/716784.png";
                else $icon="https://cdn-icons-png.flaticon.com/512/109/109612.png";
                ?>
                <div class="col-md-4 note-col">
                    <div class="file-card">
                        <img src="<?= $icon ?>" onclick="openFile('<?= $f['file_path'] ?>')">
                        <div class="file-info flex-grow-1" onclick="openFile('<?= $f['file_path'] ?>')">
                            <h6><?= htmlspecialchars($f['title']) ?></h6>
                            <small>
                                Uploaded by <?= htmlspecialchars($f['uploader_name']) ?>
                                (<?= ucfirst($f['uploader_role']) ?>)
                            </small>
                        </div>
                        <div class="action-icons text-end">
                            <a href="<?= $f['file_path'] ?>" download title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                            <a href="javascript:void(0)" onclick="copyLink('<?= $f['file_path'] ?>')" title="Share">
                                <i class="fas fa-share-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-box">
                    <i class="fas fa-folder-open fa-3x mb-3"></i>
                    <h5>No <?= ucfirst($category) ?> Available</h5>
                    <p>Notes will appear here once uploaded.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>
</section>
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
