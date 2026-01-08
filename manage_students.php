<?php
session_start();
require 'db_config.php';
require 'common.php'; // Dynamic semester calculation

// Fetch all departments
$departments = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");

// Initialize selected values
$selected_dept = $_POST['department'] ?? '';
$selected_sem = $_POST['semester'] ?? '';
$section = $_POST['section'] ?? '';
$batch_year = $_POST['batch_year'] ?? '';

// Fetch semesters based on selected department
$semesters = [];
if($selected_dept){
    $semesters_result = $conn->query("SELECT * FROM semesters WHERE department_id = $selected_dept ORDER BY semester_order ASC");
    while($row = $semesters_result->fetch_assoc()){
        $semesters[] = $row;
    }
}

// Handle search
$students = [];
if(isset($_POST['search'])){
    $query = "SELECT * FROM students WHERE 1";

    if($selected_dept) $query .= " AND department_id = $selected_dept";
    // Semester filter removed to allow dynamic current semester calculation
    if($section) $query .= " AND section LIKE '%".$conn->real_escape_string($section)."%' ";
    if($batch_year) $query .= " AND batch_year = ".intval($batch_year);

    $result = $conn->query($query);
    while($row = $result->fetch_assoc()){
        // Calculate current semester dynamically
        $row['current_semester'] = getCurrentSemester($row['batch_year']);

        // Fetch login activity for graph
        $sid = $row['id'];
        $activity_result = $conn->query("SELECT activity_time FROM student_activity WHERE student_id=$sid AND activity_type='login' ORDER BY activity_time ASC");
        $row['activity_times'] = [];
        while($a = $activity_result->fetch_assoc()){
            $row['activity_times'][] = $a['activity_time'];
        }
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Students</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen flex flex-col">

<div class="flex-grow container mx-auto p-6">

<h2 class="text-3xl font-semibold text-indigo-700 text-center mb-8">Search Students</h2>

<form method="POST" action="" class="bg-white p-6 rounded-lg shadow-md flex flex-wrap gap-4 justify-center">
    <div class="flex flex-col">
        <label class="font-semibold mb-1">Department:</label>
        <select name="department" id="department" required class="border p-2 rounded">
            <option value="">-- Select Department --</option>
            <?php foreach($departments as $row): ?>
                <option value="<?= $row['id'] ?>" <?= ($selected_dept == $row['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['department_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="flex flex-col">
        <label class="font-semibold mb-1">Semester:</label>
        <select name="semester" id="semester" class="border p-2 rounded">
            <option value="">-- Select Semester --</option>
            <?php foreach($semesters as $row): ?>
                <option value="<?= $row['id'] ?>" <?= ($selected_sem == $row['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['semester_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="flex flex-col">
        <label class="font-semibold mb-1">Section:</label>
        <input type="text" name="section" value="<?= htmlspecialchars($section) ?>" placeholder="Leave empty for all" class="border p-2 rounded">
    </div>

    <div class="flex flex-col">
        <label class="font-semibold mb-1">Batch Year:</label>
        <input type="number" name="batch_year" value="<?= htmlspecialchars($batch_year) ?>" placeholder="Leave empty for all" class="border p-2 rounded">
    </div>

    <div class="flex items-end">
        <button type="submit" name="search" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded font-semibold">Search</button>
    </div>
</form>

<?php if(count($students) > 0): ?>
    <div class="mt-8 overflow-x-auto">
      <table class="min-w-full bg-white border">
    <thead>
        <tr class="bg-indigo-600 text-white">
            <th class="py-2 px-4 border">Full Name</th>
            <th class="py-2 px-4 border">Department</th>
            <th class="py-2 px-4 border">Semester</th>
            <th class="py-2 px-4 border">Symbol Number</th>
            <th class="py-2 px-4 border">Batch Year</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($students as $s): ?>
            <tr class="text-center">
                <td class="py-2 px-4 border"><?= htmlspecialchars($s['full_name'] ?? '') ?></td>
                <td class="py-2 px-4 border"><?= htmlspecialchars($s['department'] ?? '') ?></td>
                <td class="py-2 px-4 border"><?= htmlspecialchars($s['current_semester']) ?></td>
                <td class="py-2 px-4 border"><?= htmlspecialchars($s['symbol_no'] ?? '-') ?></td>
                <td class="py-2 px-4 border"><?= htmlspecialchars($s['batch_year'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
    </div>

    <!-- Activity Graph -->
    <h3 class="text-xl font-semibold mt-8 text-center">Student Login Activity</h3>
    <canvas id="activityChart" class="mx-auto mt-4" width="900" height="400"></canvas>

<?php elseif(isset($_POST['search'])): ?>
    <p class="mt-6 text-center text-red-600 font-semibold">No students found.</p>
<?php endif; ?>

</div>

<script>
$(document).ready(function(){
    $('#department').change(function(){
        var dept_id = $(this).val();
        $('#semester').html('<option value="">Loading...</option>');

        if(dept_id != ''){
            $.ajax({
                url: '',
                type: 'POST',
                data: { department_id: dept_id, ajax: 1 },
                success: function(response){
                    $('#semester').html(response);
                }
            });
        } else {
            $('#semester').html('<option value="">-- Select Semester --</option>');
        }
    });
});

// Chart.js dataset
const ctx = document.getElementById('activityChart').getContext('2d');
const datasets = [
<?php foreach($students as $s):
    if(empty($s['activity_times'])) continue;
?>
{
    label: '<?= addslashes($s['full_name']) ?>',
    data: [
        <?php foreach($s['activity_times'] as $t): ?>
        {x: '<?= $t ?>', y: 1},
        <?php endforeach; ?>
    ],
    borderColor: 'rgba(<?= rand(0,255) ?>, <?= rand(0,255) ?>, <?= rand(0,255) ?>, 1)',
    backgroundColor: 'rgba(0,0,0,0)',
    fill: false,
    tension: 0.2
},
<?php endforeach; ?>
];

new Chart(ctx, {
    type: 'line',
    data: { datasets },
    options: {
        responsive: true,
        parsing: false,
        scales: {
            x: {
                type: 'time',
                time: { unit: 'day', tooltipFormat: 'YYYY-MM-DD HH:mm' },
                title: { display: true, text: 'Date & Time' }
            },
            y: {
                ticks: { stepSize: 1, display: false },
                title: { display: true, text: 'Activity' }
            }
        },
        plugins: { legend: { display: true, position: 'bottom' } }
    }
});
</script>

<div class="flex justify-center mb-4">
    <button
        onclick="history.back()"
        class="w-10 h-10 flex items-center justify-center 
               rounded-full bg-gray-200 hover:bg-gray-300 
               text-gray-700 hover:text-gray-900 
               shadow transition"
        title="Go Back">
        ←
    </button>
</div>

<?php
// AJAX response for semesters
if(isset($_POST['ajax']) && $_POST['ajax'] == 1 && isset($_POST['department_id'])){
    $dept_id = intval($_POST['department_id']);
    $semesters_result = $conn->query("SELECT * FROM semesters WHERE department_id = $dept_id ORDER BY semester_order ASC");
    echo '<option value="">-- Select Semester --</option>';
    while($row = $semesters_result->fetch_assoc()){
        echo '<option value="'.$row['id'].'">'.htmlspecialchars($row['semester_name']).'</option>';
    }
    exit;
}
?>
</body>
</html>
