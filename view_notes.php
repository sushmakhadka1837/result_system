<?php
session_start();
require 'db_config.php';

/* ---------------- GET PARAMS ---------------- */
$subject_id = intval($_GET['subject_id'] ?? 0);
$category = $_GET['category'] ?? 'notes'; // default category

/* ---------------- FETCH SUBJECT INFO ---------------- */
$subject_q = $conn->query("SELECT * FROM subjects_master WHERE id='$subject_id'");
if($subject_q->num_rows==0){
    die("Invalid subject!");
}
$subject = $subject_q->fetch_assoc();

/* ---------------- FETCH NOTES ---------------- */
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
<html>
<head>
    <title><?= htmlspecialchars($subject['subject_name']); ?> - <?= ucfirst($category); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    body {
    font-family: 'Arial', sans-serif;
    background: #f4f6f8;
    padding: 30px 15px;
}

h3 {
    margin-bottom: 25px;
    color: #333;
}

/* Category dropdown */
.category-select label {
    font-weight: 600;
    margin-right: 10px;
}
.category-select select {
    display: inline-block;
    width: auto;
}

/* File cards */
.file-card {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    cursor: pointer;
}

.file-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

/* File icon */
.file-card img {
    width: 80px;
    height: 100px;
    object-fit: contain;
    flex-shrink: 0;
}

/* File info */
.file-info {
    flex: 1;
}
.file-info h6 {
    margin: 0;
    font-size: 1.1rem;
    color: #1a1a1a;
}
.file-info small {
    color: #777;
}

/* Action icons */
.action-icons a {
    margin-left: 12px;
    color: #555;
    font-size: 18px;
    transition: 0.2s;
}
.action-icons a:hover {
    color: #0d6efd;
}
.action-icons .delete:hover {
    color: red;
}

/* Responsive spacing */
@media (max-width: 767px) {
    .file-card {
        flex-direction: column;
        align-items: flex-start;
    }
    .file-card img {
        margin-bottom: 10px;
    }
    .action-icons {
        margin-top: 10px;
    }
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
    </script>
</head>
<body>

<div class="container">
    <h3><?= htmlspecialchars($subject['subject_name']); ?></h3>

    <!-- Category Dropdown -->
    <div class="mb-3">
        <label>Select Category:</label>
        <select onchange="changeCategory(this.value)" class="form-select w-auto d-inline-block">
            <option value="notes" <?= $category=='notes'?'selected':''; ?>>Notes</option>
            <option value="syllabus" <?= $category=='syllabus'?'selected':''; ?>>Syllabus</option>
            <option value="past_questions" <?= $category=='past_questions'?'selected':''; ?>>Past Questions</option>
        </select>
    </div>

    <script>
        function changeCategory(cat){
            const subject = <?= $subject_id ?>;
            window.location.href = `view_notes.php?subject_id=${subject}&category=${cat}`;
        }
    </script>

    <!-- Notes Display -->
    <div class="row g-3">
        <?php if($notes_q->num_rows>0): ?>
            <?php while($f=$notes_q->fetch_assoc()): ?>
                <?php
                $ext = pathinfo($f['file_path'], PATHINFO_EXTENSION);
                if($ext=='pdf') $icon="https://cdn-icons-png.flaticon.com/512/337/337946.png";
                elseif(in_array($ext,['doc','docx'])) $icon="https://cdn-icons-png.flaticon.com/512/337/337932.png";
                elseif($ext=='zip') $icon="https://cdn-icons-png.flaticon.com/512/716/716784.png";
                else $icon="https://cdn-icons-png.flaticon.com/512/109/109612.png";
                ?>
                <div class="col-md-4">
                    <div class="file-card">
                        <img src="<?= $icon ?>" onclick="openFile('<?= $f['file_path'] ?>')" title="Click to view">
                        <div class="file-info" onclick="openFile('<?= $f['file_path'] ?>')">
                            <h6><?= htmlspecialchars($f['title']) ?></h6>
                            <small>Uploaded by: <?= htmlspecialchars($f['uploader_name']) ?> (<?= ucfirst($f['uploader_role']) ?>)</small>
                        </div>
                        <div class="action-icons text-end">
                            <a href="<?= $f['file_path'] ?>" download title="Download"><i class="fas fa-download"></i></a>
                            <a href="javascript:void(0)" onclick="copyLink('<?= $f['file_path'] ?>')" title="Copy Link"><i class="fas fa-share-alt"></i></a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted">No <?= $category ?> available for this subject.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
