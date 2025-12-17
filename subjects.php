<?php
require 'db_config.php';
$dept_id = intval($_GET['dept_id'] ?? 0);
$sem_id = intval($_GET['sem_id'] ?? 0);
$subjects = $conn->query("SELECT * FROM subjects_master WHERE department_id=$dept_id AND semester_id=$sem_id ORDER BY subject_name ASC");
?>
<h3>Subjects</h3>
<ul>
<?php while($sub = $subjects->fetch_assoc()): ?>
    <li><a href="notes_list.php?subject_id=<?php echo $sub['id']; ?>">
        <?php echo $sub['subject_name']; ?>
    </a></li>
<?php endwhile; ?>
</ul>
