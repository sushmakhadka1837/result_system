<?php
session_start();
require 'db_config.php';
require 'common.php'; // Make sure getCurrentSemester() is defined here

/* ---------- 1. AJAX HANDLER (Must be at the TOP) ---------- */
if(isset($_POST['ajax']) && $_POST['ajax'] == 1 && isset($_POST['department_id'])){
    $dept_id = intval($_POST['department_id']);
    $sem_res = $conn->query("SELECT * FROM semesters WHERE department_id = $dept_id ORDER BY semester_order ASC");
    echo '<option value="">-- Select Semester --</option>';
    while($row = $sem_res->fetch_assoc()){
        echo '<option value="'.$row['id'].'">'.htmlspecialchars($row['semester_name']).'</option>';
    }
    exit; // Stop further execution for AJAX
}

/* ---------- 2. INITIAL FETCH & FILTERS ---------- */
$departments = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");
$selected_dept = $_POST['department'] ?? '';
$selected_sem = $_POST['semester'] ?? '';
$section = $_POST['section'] ?? '';
$batch_year = $_POST['batch_year'] ?? '';

$students = [];
if(isset($_POST['search'])){
    $query = "SELECT s.*, d.department_name as dept_label 
              FROM students s 
              LEFT JOIN departments d ON s.department_id = d.id 
              WHERE 1=1";

    if($selected_dept) $query .= " AND s.department_id = ".intval($selected_dept);
    if($section) $query .= " AND s.section LIKE '%".$conn->real_escape_string($section)."%' ";
    if($batch_year) $query .= " AND s.batch_year = ".intval($batch_year);

    $result = $conn->query($query);
    if($result){
        while($row = $result->fetch_assoc()){
            // Dynamic semester calc
            $row['current_semester'] = function_exists('getCurrentSemester') ? getCurrentSemester($row['batch_year']) : 'N/A';
            
            // Login Activity
            $sid = $row['id'];
            $activity_res = $conn->query("SELECT activity_time FROM student_activity WHERE student_id=$sid AND activity_type='login' ORDER BY activity_time ASC");
            $row['activity_times'] = [];
            while($a = $activity_res->fetch_assoc()){
                $row['activity_times'][] = $a['activity_time'];
            }
            $students[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students | PEC RMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .glass { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(8px); }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            aside { display: none; }
            main { margin-left: 0 !important; }
        }
        
        @media (max-width: 768px) {
            main { padding: 1rem !important; }
            h2 { font-size: 1.5rem !important; }
            form { padding: 1.5rem !important; border-radius: 1rem !important; }
            .flex-wrap { flex-direction: column; }
            .flex-1 { width: 100%; }
            table { font-size: 0.85rem; }
            th, td { padding: 0.75rem !important; }
        }
        
        @media (max-width: 640px) {
            main { padding: 0.5rem !important; }
            h2 { font-size: 1.25rem !important; }
            form { padding: 1rem !important; }
            button { width: 100%; }
            .overflow-x-auto { overflow-x: auto; }
            table { font-size: 0.75rem; min-width: 600px; }
            th, td { padding: 0.5rem !important; }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">

<div class="flex">
    <aside class="w-64 bg-slate-900 text-slate-300 min-h-screen hidden lg:block fixed">
        <div class="p-6 border-b border-slate-800 text-white font-bold text-xl flex items-center gap-2">
            <i class="fa-solid fa-user-graduate text-indigo-500"></i> RMS Admin
        </div>
        <nav class="p-4 space-y-2 mt-4">
            <a href="admin_dashboard.php" class="block p-3 rounded-xl hover:bg-slate-800 transition">Dashboard</a>
            <a href="manage_students.php" class="block p-3 rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/30 font-bold">Manage Students</a>
            <a href="manage_subjects.php" class="block p-3 rounded-xl hover:bg-slate-800 transition">Subjects</a>
            <a href="manage_departments.php" class="block p-3 rounded-xl hover:bg-slate-800 transition">Departments</a>
        </nav>
    </aside>

    <main class="flex-1 lg:ml-64 p-8">
        <div class="max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-extrabold text-slate-800 tracking-tight">Search Students</h2>
                <button onclick="history.back()" class="h-10 w-10 flex items-center justify-center rounded-full bg-white shadow-sm border border-slate-200 hover:bg-slate-50 transition">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
            </div>

            <form method="POST" class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 flex flex-wrap gap-6 items-end mb-10">
                <div class="flex-1 min-w-[200px]">
                    <label class="text-xs font-bold text-slate-400 uppercase ml-1 mb-2 block">Department</label>
                    <select name="department" id="department" required class="w-full border-slate-200 bg-slate-50 p-3 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                        <option value="">-- All Departments --</option>
                        <?php while($row = $departments->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" <?= ($selected_dept == $row['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['department_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="flex-1 min-w-[150px]">
                    <label class="text-xs font-bold text-slate-400 uppercase ml-1 mb-2 block">Section</label>
                    <input type="text" name="section" value="<?= htmlspecialchars($section) ?>" placeholder="A / B / C" class="w-full border-slate-200 bg-slate-50 p-3 rounded-xl outline-none">
                </div>

                <div class="flex-1 min-w-[150px]">
                    <label class="text-xs font-bold text-slate-400 uppercase ml-1 mb-2 block">Batch Year</label>
                    <input type="number" name="batch_year" value="<?= htmlspecialchars($batch_year) ?>" placeholder="e.g. 2024" class="w-full border-slate-200 bg-slate-50 p-3 rounded-xl outline-none">
                </div>

                <button type="submit" name="search" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3.5 rounded-xl font-bold shadow-lg shadow-indigo-100 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass"></i> Search
                </button>
            </form>

            <?php if(count($students) > 0): ?>
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden mb-12 overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 text-slate-400 text-xs font-bold uppercase tracking-wider">
                                <th class="py-5 px-6">Student Info</th>
                                <th class="py-5 px-6">Department</th>
                                <th class="py-5 px-6 text-center">Semester</th>
                                <th class="py-5 px-6">Symbol No.</th>
                                <th class="py-5 px-6">Batch</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach($students as $s): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-5 px-6 font-bold text-slate-800"><?= htmlspecialchars($s['full_name'] ?? '') ?></td>
                                    <td class="py-5 px-6 text-slate-600 font-medium"><?= htmlspecialchars($s['dept_label'] ?? 'N/A') ?></td>
                                    <td class="py-5 px-6 text-center">
                                        <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-lg text-xs font-black">SEM <?= htmlspecialchars($s['current_semester']) ?></span>
                                    </td>
                                    <td class="py-5 px-6 font-mono text-sm"><?= htmlspecialchars($s['symbol_no'] ?? '-') ?></td>
                                    <td class="py-5 px-6 text-slate-500 font-bold"><?= htmlspecialchars($s['batch_year'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100">
                    <h3 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-chart-line text-indigo-500"></i> Login Activity Insights
                    </h3>
                    <div class="h-[400px]">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>

            <?php elseif(isset($_POST['search'])): ?>
                <div class="text-center py-20 bg-rose-50 rounded-[2rem] border-2 border-dashed border-rose-100">
                    <i class="fa-solid fa-user-slash text-4xl text-rose-300 mb-4"></i>
                    <p class="text-rose-600 font-bold">No students matching your criteria were found.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
// Chart.js Visualization
<?php if(count($students) > 0): ?>
const ctx = document.getElementById('activityChart').getContext('2d');
const datasets = [
<?php foreach($students as $s): if(empty($s['activity_times'])) continue; ?>
{
    label: '<?= addslashes($s['full_name']) ?>',
    data: [
        <?php foreach($s['activity_times'] as $t): ?>
        { x: '<?= $t ?>', y: 1 },
        <?php endforeach; ?>
    ],
    borderColor: '#'+Math.floor(Math.random()*16777215).toString(16),
    tension: 0.3,
    pointRadius: 4,
    showLine: true
},
<?php endforeach; ?>
];

new Chart(ctx, {
    type: 'line',
    data: { datasets },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                type: 'time',
                time: { unit: 'day' },
                title: { display: true, text: 'Time Period' }
            },
            y: {
                display: false,
                min: 0,
                max: 2
            }
        },
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, padding: 20 } }
        }
    }
});
<?php endif; ?>

// Department-Semester Ajax (Simplified)
$('#department').change(function(){
    var dept_id = $(this).val();
    if(dept_id){
        $.post('', { department_id: dept_id, ajax: 1 }, function(res){
            $('#semester').html(res);
        });
    }
});
</script>

</body>
</html>