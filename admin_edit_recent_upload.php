<?php
session_start();
require_once 'db_config.php';

// URL bata Batch ra Semester lyaune
$batch = $_GET['batch'] ?? '';
$sem   = $_GET['sem'] ?? '';

if (!$batch || !$sem) {
    die("<div class='p-10 text-red-600 font-bold'>Error: Batch and Semester are required.</div>");
}

// --- 1. DELETE LOGIC ---
if (isset($_POST['delete_all'])) {
    $del_sql = "DELETE FROM results 
                WHERE semester_id = '$sem' 
                AND student_id IN (SELECT id FROM students WHERE batch_year = '$batch')";
    if ($conn->query($del_sql)) {
        echo "<script>alert('Sabaai marks delete bhayo!'); window.location.href=window.location.href;</script>";
    }
}

// --- 2. UPDATE/SAVE LOGIC ---
if (isset($_POST['save_matrix'])) {
    foreach ($_POST['marks'] as $res_id => $m_val) {
        $fm = floatval($_POST['fm'][$res_id]);
        $pm = floatval($_POST['pm'][$res_id]);
        $m_val = ($m_val === "") ? 0 : floatval($m_val);
        $percent = ($m_val / $fm) * 100;

        // PU Grading Logic
        if ($m_val < $pm) { $grade = 'F'; }
        else {
            if($percent >= 90) $grade = 'A';
            elseif($percent >= 85) $grade = 'A-';
            elseif($percent >= 80) $grade = 'B+';
            elseif($percent >= 75) $grade = 'B';
            elseif($percent >= 70) $grade = 'B-';
            elseif($percent >= 65) $grade = 'C+';
            elseif($percent >= 60) $grade = 'C';
            elseif($percent >= 55) $grade = 'C-';
            elseif($percent >= 50) $grade = 'D+';
            else $grade = 'F';
        }

        $stmt = $conn->prepare("UPDATE results SET ut_obtain = ?, ut_grade = ? WHERE id = ?");
        $stmt->bind_param("dsi", $m_val, $grade, $res_id);
        $stmt->execute();
    }
    echo "<script>alert('Marks Updated Successfully!'); window.location.href=window.location.href;</script>";
}

// --- 3. FETCH HEADERS (Unique Subjects in Results for this Batch) ---
// Yesle timile rename gareko (ELE31) code anusar subject header lyaunchha
$sub_sql = "SELECT DISTINCT sm.id, sm.subject_name, sm.subject_code 
            FROM results r 
            JOIN subjects_master sm ON r.subject_id = sm.id 
            JOIN students s ON r.student_id = s.id
            WHERE s.batch_year = '$batch' AND r.semester_id = '$sem'
            ORDER BY sm.subject_code ASC";

$sub_res = $conn->query($sub_sql);
$subjects = [];
while($s = $sub_res->fetch_assoc()) { 
    $subjects[$s['id']] = $s; 
}

// --- 4. FETCH STUDENTS & MAPPING ---
$students = [];
$matrix = [];
$stu_res = $conn->query("SELECT id, full_name, symbol_no FROM students WHERE batch_year = '$batch' ORDER BY symbol_no ASC");

while($st = $stu_res->fetch_assoc()) {
    $sid = $st['id'];
    $students[$sid] = $st;
    
    // Tyo student ko sabaai result fetch garne
    $res_data = $conn->query("SELECT * FROM results WHERE student_id = $sid AND semester_id = '$sem'");
    while($r = $res_data->fetch_assoc()) {
        $matrix[$sid][$r['subject_id']] = $r;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Recent Upload - Matrix View</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .sticky-col { position: sticky; left: 0; background: white; z-index: 10; border-right: 2px solid #e2e8f0; }
        .sticky-header { position: sticky; top: 0; z-index: 20; }
        input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
</head>
<body class="bg-slate-50 p-6">
    <div class="max-w-full mx-auto">
        <form method="POST">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 bg-white p-6 rounded-2xl shadow-sm border border-slate-200 gap-4">
                <div>
                    <h1 class="font-black text-2xl text-slate-800 uppercase tracking-tight">Edit Recent Upload</h1>
                    <p class="text-sm text-slate-500 font-bold italic">Batch: <?= htmlspecialchars($batch) ?> | Semester: <?= htmlspecialchars($sem) ?></p>
                </div>
                <div class="flex gap-4">
                    <button type="submit" name="delete_all" 
                            onclick="return confirm('Sabaai marks delete garne ho? Yo pachi data firta aauna sakdaina.');"
                            class="text-red-600 font-bold px-4 hover:bg-red-50 rounded-xl transition-all">
                        Delete All
                    </button>
                    <button type="submit" name="save_matrix" 
                            class="bg-blue-600 text-white px-10 py-3 rounded-xl font-bold shadow-lg hover:bg-blue-700 transition-all active:scale-95">
                        Save Changes
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-800 text-white sticky-header">
                            <tr>
                                <th class="p-4 sticky-col bg-slate-800 min-w-[120px] text-xs uppercase tracking-widest">Symbol</th>
                                <th class="p-4 sticky-col bg-slate-800 min-w-[220px] text-xs uppercase tracking-widest" style="left: 120px;">Student Name</th>
                                <?php foreach($subjects as $sub): ?>
                                    <th class="p-4 text-center border-l border-slate-700 min-w-[140px]">
                                        <div class="text-[10px] font-black text-blue-300 uppercase"><?= $sub['subject_code'] ?></div>
                                        <div class="text-[9px] font-normal opacity-70 leading-tight"><?= $sub['subject_name'] ?></div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if(empty($subjects)): ?>
                                <tr><td colspan="100%" class="p-20 text-center text-slate-400 font-bold italic">No marks found. Please upload the Excel file first.</td></tr>
                            <?php endif; ?>

                            <?php foreach($students as $sid => $info): ?>
                            <tr class="hover:bg-blue-50/50 transition-colors">
                                <td class="p-4 font-bold text-slate-600 sticky-col bg-inherit text-xs"><?= $info['symbol_no'] ?></td>
                                <td class="p-4 font-bold text-slate-800 sticky-col bg-inherit uppercase text-[11px]" style="left: 120px;"><?= $info['full_name'] ?></td>
                                
                                <?php foreach($subjects as $sub_id => $sub): 
                                    $m = $matrix[$sid][$sub_id] ?? null;
                                ?>
                                <td class="p-4 border-l border-slate-50 text-center">
                                    <?php if($m): ?>
                                        <input type="hidden" name="fm[<?= $m['id'] ?>]" value="<?= $m['ut_full_marks'] ?>">
                                        <input type="hidden" name="pm[<?= $m['id'] ?>]" value="<?= $m['ut_pass_marks'] ?>">
                                        
                                        <input type="number" step="0.1" name="marks[<?= $m['id'] ?>]" 
                                               value="<?= $m['ut_obtain'] ?>" 
                                               class="w-16 border-2 border-slate-100 rounded-lg p-1.5 text-center font-black text-blue-700 focus:border-blue-500 outline-none transition-all">
                                        
                                        <div class="text-[9px] mt-1 font-bold <?= ($m['ut_grade'] == 'F') ? 'text-red-500' : 'text-slate-400' ?>">
                                            Grade: <?= $m['ut_grade'] ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-200 font-bold">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</body>
</html>