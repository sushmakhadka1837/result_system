<?php
session_start();
require_once 'db_config.php';

$success_msg = "";

// --- UT MARKS IMPORT LOGIC ---
if (isset($_POST['import_ut_marks'])) {
    $sem_id = intval($_POST['semester_confirm']);
    $batch_confirm = intval($_POST['batch_confirm']); 

    if ($_FILES['csv_file']['size'] > 0) {
        $file = fopen($_FILES['csv_file']['tmp_name'], "r");
        $header = fgetcsv($file); 

        while (($row = fgetcsv($file)) !== FALSE) {
            $student_id = intval($row[0]);
            
            // Student verify garne (Batch check garera)
            $st_check = $conn->query("SELECT id FROM students WHERE id = $student_id AND batch_year = $batch_confirm LIMIT 1");
            
            if($st_check->num_rows > 0) {
                for ($i = 3; $i < count($header); $i++) {
                    $header_text = $header[$i];
                    
                    // Code nikalne logic (CODE)
                    preg_match('/\(([^)]+)\)/', $header_text, $code_match);
                    $subject_code = isset($code_match[1]) ? trim($code_match[1]) : '';
                    
                    $marks_raw = trim($row[$i]);

                    // FM/PM Logic from Header
                    $fm = 50; $pm = 22;
                    if (preg_match('/FM\s*:\s*(\d+)/i', $header_text, $matches_fm)) { $fm = intval($matches_fm[1]); }
                    if (preg_match('/PM\s*:\s*(\d+)/i', $header_text, $matches_pm)) { $pm = intval($matches_pm[1]); }

                    if ($marks_raw !== "" && $marks_raw !== "-" && !empty($subject_code)) {
                        $m_val = floatval($marks_raw);
                        
                        // SUBJECT ID LOCK: Semester id ra Project filter thapiyo
                        $sub_q = $conn->query("SELECT id, subject_code, is_elective, credit_hours 
                                              FROM subjects_master 
                                              WHERE subject_code = '$subject_code' 
                                              AND semester_id = $sem_id 
                                              AND subject_type != 'Project' 
                                              LIMIT 1");
                        
                        if($sub_data = $sub_q->fetch_assoc()) {
                            $sid = $sub_data['id'];
                            $scode = $sub_data['subject_code'];
                            $is_elective = $sub_data['is_elective'];
                            $chrs = $sub_data['credit_hours'];

                            $can_save = true;
                            // Elective validation
                            if($is_elective == 1) {
                                $el_check = $conn->query("SELECT id FROM student_electives WHERE student_id = $student_id AND elective_option_id = $sid");
                                if($el_check->num_rows == 0) { $can_save = false; }
                            }

                            if($can_save) {
                                $percent = ($m_val / $fm) * 100;
                                // Grading Logic
                                if ($m_val < $pm) { $grade = 'F'; } 
                                else {
                                    if($percent >= 90)      $grade = 'A';
                                    elseif($percent >= 85) $grade = 'A-';
                                    elseif($percent >= 80) $grade = 'B+';
                                    elseif($percent >= 75) $grade = 'B';
                                    elseif($percent >= 70) $grade = 'B-';
                                    elseif($percent >= 65) $grade = 'C+';
                                    elseif($percent >= 60) $grade = 'C';
                                    elseif($percent >= 55) $grade = 'C-';
                                    elseif($percent >= 50) $grade = 'D+';
                                    else                   $grade = 'F';
                                }

                                // DB INSERT/UPDATE
                                $conn->query("INSERT INTO results 
                                    (student_id, subject_id, semester_id, subject_code, credit_hours, ut_full_marks, ut_pass_marks, ut_obtain, ut_grade, published) 
                                    VALUES 
                                    ($student_id, $sid, $sem_id, '$scode', '$chrs', $fm, $pm, $m_val, '$grade', 0) 
                                    ON DUPLICATE KEY UPDATE 
                                    ut_obtain = $m_val, ut_grade = '$grade', ut_full_marks = $fm, ut_pass_marks = $pm, credit_hours = '$chrs'");
                            }
                        }
                    }
                }
            }
        }
        fclose($file);
        echo "<script>alert('Marks Imported with Correct IDs!'); window.location.href='admin_ut_manager.php?status=success&b=$batch_confirm&s=$sem_id';</script>";
        exit();
    }
}

if(isset($_GET['status']) && $_GET['status'] == 'success') {
    $success_msg = "UT Marks for Batch ".$_GET['b']." (Sem ".$_GET['s'].") uploaded successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UT Management - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-6 md:p-10">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-black text-slate-800 mb-8 uppercase tracking-tight">Unit Test (UT) Manager</h1>

        <?php if($success_msg): ?>
            <div class="bg-emerald-600 text-white p-5 rounded-2xl mb-8 shadow-xl flex flex-col md:flex-row justify-between items-center gap-4 border-l-8 border-white/30">
                <div>
                    <p class="font-bold text-lg">✅ <?= $success_msg ?></p>
                    <p class="text-xs text-emerald-100 italic">Verify or update these marks before final publishing.</p>
                </div>
                <div class="flex gap-2">
                    <a href="admin_edit_recent_upload.php?batch=<?= $_GET['b'] ?>&sem=<?= $_GET['s'] ?>&type=ut" 
                       class="bg-white text-emerald-700 px-6 py-2 rounded-xl font-bold text-sm hover:bg-emerald-50 shadow-md transition-all">
                       Edit Recent Upload ✍️
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
            <div class="bg-white p-8 rounded-2xl shadow-sm border-t-4 border-blue-600 h-fit">
                <h2 class="text-lg font-bold mb-6 text-blue-600 uppercase">1. Generate Matrix</h2>
                <form action="export_template.php" method="GET" class="space-y-4">
                    <input type="hidden" name="type" value="ut">
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Department</label>
                        <select name="department_id" id="dept_select" class="w-full border p-3 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500" required onchange="updateSemesters()">
                            <option value="">-- Select --</option>
                            <?php 
                            $d_res = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");
                            while($d = $d_res->fetch_assoc()) {
                                echo "<option value='{$d['id']}' data-name='{$d['department_name']}'>{$d['department_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-600 mb-1">Batch</label>
                            <input type="number" name="batch" placeholder="2022" class="w-full border p-3 rounded-lg bg-gray-50" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-600 mb-1">Section</label>
                            <select name="section" class="w-full border p-3 rounded-lg bg-gray-50">
                                <option value="">-- All --</option>
                                <option value="A">Section A</option>
                                <option value="B">Section B</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Semester</label>
                        <select name="semester" id="sem_select" class="w-full border p-3 rounded-lg bg-gray-50" required>
                            <option value="">-- Select Dept First --</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-lg font-bold hover:bg-blue-700 shadow-lg transition-all">Download Template</button>
                </form>
            </div>

            <div class="space-y-8">
                <div class="bg-white p-8 rounded-2xl shadow-sm border-t-4 border-emerald-600">
                    <h2 class="text-lg font-bold mb-6 text-emerald-600 uppercase">2. Upload Marks</h2>
                    <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-600 mb-1">Batch Year</label>
                                <input type="number" name="batch_confirm" class="w-full border p-3 rounded-lg bg-gray-50" placeholder="2022" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-600 mb-1">Semester ID</label>
                                <input type="number" name="semester_confirm" class="w-full border p-3 rounded-lg bg-gray-50" placeholder="7" required>
                            </div>
                        </div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Select CSV File</label>
                        <input type="file" name="csv_file" accept=".csv" class="w-full border p-3 rounded-lg bg-emerald-50" required>
                        <button type="submit" name="import_ut_marks" class="w-full bg-emerald-600 text-white py-4 rounded-lg font-bold hover:bg-emerald-700 shadow-lg">Upload & Save</button>
                    </form>
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-sm border-t-4 border-slate-800">
                    <h2 class="text-lg font-bold mb-4 text-slate-800 uppercase text-center">3. Results Dashboard</h2>
                    <p class="text-gray-500 text-sm text-center mb-6">View, filter and monitor Unit Test & Assessment results.</p>
                    <a href="admin_view_results.php" class="block w-full text-center bg-slate-800 text-white py-4 rounded-lg font-bold hover:bg-black shadow-lg uppercase tracking-wider transition-all">
                        🔍 Open Admin View Results
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    function updateSemesters() {
        const d = document.getElementById('dept_select');
        const s = document.getElementById('sem_select');
        const name = d.options[d.selectedIndex].getAttribute('data-name') || "";
        s.innerHTML = '<option value="">-- Select --</option>';
        let max = name.toLowerCase().includes('architecture') ? 10 : 8;
        if (d.value !== "") {
            for (let i = 1; i <= max; i++) {
                let opt = document.createElement('option');
                opt.value = i; opt.text = "Semester " + i; s.add(opt);
            }
        }
    }
    </script>
</body>
</html>