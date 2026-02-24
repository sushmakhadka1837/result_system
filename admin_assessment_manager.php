<?php
session_start();
require_once 'db_config.php';

$success_msg = "";

if (isset($_POST['import_assessment'])) {
    $sem_id = intval($_POST['semester_confirm']);
    $batch_confirm = intval($_POST['batch_confirm']);
    
    // Convert student batch_year to syllabus flag (1=New, NULL/2=Old)
    $syllabus_flag = ($batch_confirm >= 2023) ? 1 : 'NULL';

    if ($_FILES['csv_file']['size'] > 0) {
        $file = fopen($_FILES['csv_file']['tmp_name'], "r");
        
        // CSV Headers read garne
        $header1 = fgetcsv($file); // Subject Names with (Code)
        $header2 = fgetcsv($file); // Components like Ass(100), Tut(5)...

        while (($row = fgetcsv($file)) !== FALSE) {
            $student_id = intval($row[0]);
            
            // Student verify garne
            $st_check = $conn->query("SELECT id FROM students WHERE id = $student_id AND batch_year = $batch_confirm LIMIT 1");
            
            if($st_check->num_rows > 0) {
                // Subject column index 3 bata suru hunchha, pratyek subject ko 5 components chhan
                for ($i = 3; $i < count($row); $i += 5) {
                    
                    // Skip if value is '-' (Elective not taken) or empty
                    if(!isset($row[$i]) || $row[$i] === '-' || $row[$i] === '' || $row[$i] === 'N/A') continue;

                    // Header bata Subject Code nikalne
                    preg_match('/\(([^)]+)\)/', $header1[$i], $code_match);
                    $subject_code = isset($code_match[1]) ? trim($code_match[1]) : '';
                    if(empty($subject_code)) continue;

                    // --- DECIMAL RAW DATA READING ---
                    $ass_raw    = floatval($row[$i]);      // Assessment out of 100
                    $tutorial   = floatval($row[$i+1]);    // Tutorial out of 5
                    $practical  = floatval($row[$i+2]);    // Practical out of 20
                    $total_days = intval($row[$i+3]);      // Total Working Days
                    $att_days   = intval($row[$i+4]);      // Attended Days

                    // Database bata Subject ID khojne - SYLLABUS FLAG CHECK
                    if($syllabus_flag === 'NULL') {
                        $sub_q = $conn->query("
                            SELECT DISTINCT sm.id 
                            FROM subjects_master sm
                            LEFT JOIN subjects_department_semester sds ON sm.id = sds.subject_id
                            WHERE sm.subject_code = '$subject_code' 
                            AND sm.semester_id = $sem_id
                            AND (sds.syllabus_flag IS NULL OR sds.syllabus_flag = 2)
                            LIMIT 1
                        ");
                    } else {
                        $sub_q = $conn->query("
                            SELECT DISTINCT sm.id 
                            FROM subjects_master sm
                            LEFT JOIN subjects_department_semester sds ON sm.id = sds.subject_id
                            WHERE sm.subject_code = '$subject_code' 
                            AND sm.semester_id = $sem_id
                            AND sds.syllabus_flag = $syllabus_flag
                            LIMIT 1
                        ");
                    }
                    
                    if($sub_data = $sub_q->fetch_assoc()) {
                        $sid = $sub_data['id'];

                        // 1. UT Marks fetch garne (Database ma paila dekhi bhayeko UT marks 50 bata 5 ma scale hunchha)
                        $ut_q = $conn->query("SELECT ut_obtain FROM results WHERE student_id = $student_id AND subject_id = $sid");
                        $ut_raw = ($ut_q->num_rows > 0) ? floatval($ut_q->fetch_assoc()['ut_obtain']) : 0;

                        // --- TIMILE DIYEKO CALCULATION LOGIC ---
                        
                        // Scaling to AI marks
                        $ut_ai   = round(($ut_raw / 50) * 5, 2);      // UT (5)
                        $ass_ai  = round(($ass_raw / 100) * 15, 2);   // Assessment (15)
                        $att_ai  = ($total_days > 0) ? round(($att_days / $total_days) * 5, 2) : 0; // Attendance (5)

                        // Final Theory (30) = Assessment(15) + Attendance(5) + UT(5) + Tutorial(5)
                        $final_theory = $ut_ai + $ass_ai + $att_ai + $tutorial;
                        
                        // Final Total (50) = Final Theory(30) + Practical(20)
                        $final_total = $final_theory + $practical;

                        // --- PU GRADING SYSTEM (Based on 50 marks) ---
                        $percent = ($final_total / 50) * 100;
                        if($percent >= 90) { $lg = 'A'; $gp = 4.0; }
                        elseif($percent >= 85) { $lg = 'A-'; $gp = 3.7; }
                        elseif($percent >= 80) { $lg = 'B+'; $gp = 3.3; }
                        elseif($percent >= 75) { $lg = 'B'; $gp = 3.0; }
                        elseif($percent >= 70) { $lg = 'B-'; $gp = 2.7; }
                        elseif($percent >= 65) { $lg = 'C+'; $gp = 2.3; }
                        elseif($percent >= 60) { $lg = 'C'; $gp = 2.0; }
                        elseif($percent >= 55) { $lg = 'C-'; $gp = 1.7; }
                        elseif($percent >= 50) { $lg = 'D+'; $gp = 1.3; }
                        else { $lg = 'F'; $gp = 0.0; }

                        // --- DATABASE SYNC ---
                        // 'final_total' ra 'total_obtained' dubai ma Theory + Practical ko sum janchha
                        $conn->query("INSERT INTO results 
                            (student_id, subject_id, semester_id, assessment_raw, assessment_ai_marks, tutorial_marks, attendance_marks, total_attendance_days, practical_marks, ut_ai_marks, final_theory, total_obtained, final_total, letter_grade, grade_point, published)
                            VALUES 
                            ($student_id, $sid, $sem_id, $ass_raw, $ass_ai, $tutorial, $att_days, $total_days, $practical, $ut_ai, $final_theory, $final_total, $final_total, '$lg', $gp, 1)
                            ON DUPLICATE KEY UPDATE 
                            assessment_raw=$ass_raw, 
                            assessment_ai_marks=$ass_ai, 
                            tutorial_marks=$tutorial, 
                            attendance_marks=$att_days, 
                            total_attendance_days=$total_days, 
                            practical_marks=$practical, 
                            ut_ai_marks=$ut_ai, 
                            final_theory=$final_theory, 
                            total_obtained=$final_total, 
                            final_total=$final_total, 
                            letter_grade='$lg', 
                            grade_point=$gp, 
                            published=1");
                    }
                }
            }
        }
        fclose($file);
        $success_msg = "Assessment synced! Final Total = Theory(30) + Practical(20)";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assessment Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-6 font-sans">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-black text-slate-800 uppercase italic">Assessment Manager</h1>
                <p class="text-slate-500 text-sm">Theory (30) + Practical (20) Calculation</p>
            </div>
            <div class="flex gap-3">
                <a href="batch_assignment_tool.php" class="bg-amber-600 text-white px-6 py-2 rounded-xl font-bold shadow-lg hover:bg-amber-700 transition">
                    <i class="fas fa-cog"></i> Batch Settings
                </a>
                <a href="view_recent_assessment.php" class="bg-slate-800 text-white px-6 py-2 rounded-xl font-bold shadow-lg hover:bg-slate-700 transition">Recent Uploads</a>
            </div>
        </div>

        <?php if($success_msg): ?>
            <div class="bg-emerald-500 text-white p-5 rounded-2xl mb-8 shadow-lg flex justify-between items-center border-b-4 border-emerald-700 animate-bounce">
                <p class="font-bold">✅ <?= $success_msg ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition">
                <h2 class="text-indigo-600 font-black uppercase text-sm mb-6 tracking-widest border-l-4 border-indigo-600 pl-3">1. Generate Matrix</h2>
                <form action="export_assessment_template.php" method="GET" class="space-y-4">
                    <select name="department_id" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none" required>
                        <option value="">Select Department</option>
                        <?php 
                        $res = $conn->query("SELECT id, department_name FROM departments");
                        while($d = $res->fetch_assoc()) echo "<option value='{$d['id']}'>{$d['department_name']}</option>";
                        ?>
                    </select>
                    <div class="grid grid-cols-2 gap-4">
                        <input type="number" name="batch" placeholder="Batch (2079)" class="p-4 bg-slate-50 border border-slate-200 rounded-xl w-full" required>
                        <input type="number" name="semester" placeholder="Sem ID" class="p-4 bg-slate-50 border border-slate-200 rounded-xl w-full" required>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-black hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">Download CSV Template</button>
                </form>
            </div>

            <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition">
                <h2 class="text-emerald-600 font-black uppercase text-sm mb-6 tracking-widest border-l-4 border-emerald-600 pl-3">2. Upload Assessment</h2>
                <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="number" name="batch_confirm" placeholder="Confirm Batch" class="p-4 bg-slate-50 border border-slate-200 rounded-xl w-full" required>
                        <input type="number" name="semester_confirm" placeholder="Confirm Sem ID" class="p-4 bg-slate-50 border border-slate-200 rounded-xl w-full" required>
                    </div>
                    <div class="relative border-2 border-dashed border-emerald-200 rounded-xl p-4 bg-emerald-50">
                        <input type="file" name="csv_file" accept=".csv" class="w-full cursor-pointer opacity-100" required>
                    </div>
                    <button type="submit" name="import_assessment" class="w-full bg-emerald-600 text-white py-4 rounded-xl font-black hover:bg-emerald-700 transition shadow-lg shadow-emerald-200">Process & Save Data</button>
                </form>
            </div>
        </div>
    </div>
    <div class="bg-white p-8 rounded-2xl shadow-sm border-t-4 border-slate-800">
                    <h2 class="text-lg font-bold mb-4 text-slate-800 uppercase text-center">3. Results Dashboard</h2>
                    <p class="text-gray-500 text-sm text-center mb-6">View, filter and monitor Unit Test & Assessment results.</p>
                    <a href="admin_view_assessment_results.php" class="block w-full text-center bg-slate-800 text-white py-4 rounded-lg font-bold hover:bg-black shadow-lg uppercase tracking-wider transition-all">
                        🔍 Open Admin View Results
                    </a>
                </div>
</body>
</html>