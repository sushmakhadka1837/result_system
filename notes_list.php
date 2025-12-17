<?php
require 'db_config.php';
$subject_id = intval($_GET['subject_id'] ?? 0);
$notes = $conn->query("SELECT * FROM notes WHERE subject_id=$subject_id ORDER BY created_at DESC");
?>
<h3>Notes</h3>
<?php
if($notes->num_rows > 0){
    while($note = $notes->fetch_assoc()){
        echo "<div class='card mb-2 p-2'>";
        echo "<h5>{$note['title']}</h5>";
        echo "<p>{$note['description']}</p>";
        if(!empty($note['file_path'])){
            echo "<a href='{$note['file_path']}' class='btn btn-primary' target='_blank'>Download</a>";
        } else {
            echo "<span>No file uploaded</span>";
        }
        echo "</div>";
    }
} else {
    echo "<p>No notes available.</p>";
}
?>
