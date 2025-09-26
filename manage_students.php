<?php
require 'db_config.php'; // expects $conn (mysqli)

$departments = [];
$dept_stmt = $conn->prepare("SELECT id, department_name, total_semesters FROM departments ORDER BY department_name ASC");
if ($dept_stmt) {
    $dept_stmt->execute();
    $dept_result = $dept_stmt->get_result();
    while ($d = $dept_result->fetch_assoc()) {
        $departments[] = $d;
    }
    $dept_stmt->close();
}

$students = [];
$highlight_ids = [];
$selected_dept = $_POST['department'] ?? '';
$selected_sem  = $_POST['semester'] ?? '';
$search_name   = trim($_POST['name'] ?? '');
$total_semesters = 8; // default

// department ko total_semesters निकाल्ने
if ($selected_dept) {
    foreach ($departments as $d) {
        if ($d['id'] == $selected_dept) {
            $total_semesters = $d['total_semesters'];
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($selected_dept !== '' && $selected_sem !== '') {
        // department अब students मा string छ (id होइन) → JOIN हटाइयो
        $sql = "SELECT id, full_name, symbol_no, semester, batch_year, department, email, phone 
                FROM students 
                WHERE department = ? AND semester = ?
                ORDER BY full_name ASC";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ss", $selected_dept, $selected_sem);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $students[] = $row;
                if ($search_name !== '' && stripos($row['full_name'], $search_name) !== false) {
                    $highlight_ids[] = $row['id'];
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ne">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Students</title>
<style>
    :root{
        --excel-header: #dfefff;
        --excel-highlight: #fff59d;
        --zebra1: #ffffff;
        --zebra2: #f7f9fc;
        --border: #c7d0da;
        --text: #222;
    }
    body { font-family: "Segoe UI", Roboto, Arial, sans-serif; margin: 24px; background: #f4f6f8; color: var(--text); }
    .card { background: white; border-radius: 8px; box-shadow: 0 6px 18px rgba(20,30,40,0.06); padding: 18px; max-width: 1150px; margin: 0 auto; }
    h2 { margin-bottom: 12px; font-size: 20px; }
    form.search-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    form.search-row input, form.search-row select { padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; min-width: 160px; }
    form.search-row button { padding: 9px 14px; border-radius: 6px; border: none; background: linear-gradient(#1976d2,#0f63b8); color: white; font-weight: 600; cursor: pointer; }
    form.search-row button:active { transform: translateY(1px); }
    .table-wrap { overflow-x: auto; margin-top: 16px; }
    table.excel { border-collapse: collapse; width: 100%; min-width: 950px; font-size: 14px; }
    table.excel th, table.excel td { border: 1px solid var(--border); padding: 8px 10px; text-align: center; }
    table.excel thead th { background: var(--excel-header); font-weight: 700; }
    table.excel tbody tr:nth-child(odd) { background: var(--zebra1); }
    table.excel tbody tr:nth-child(even) { background: var(--zebra2); }
    table.excel tbody tr:hover { background: #eef5ff; }
    .highlight { background: var(--excel-highlight) !important; font-weight: bold; }
    .no-data { margin-top: 16px; color: #666; }
</style>
</head>
<body>
<div class="card">
    <h2>Manage Students</h2>
    <form class="search-row" method="POST">
        <input type="text" name="name" placeholder="Enter Student Name" value="<?= htmlspecialchars($search_name) ?>">
        
        <select name="department" required onchange="this.form.submit()">
            <option value="">-- Select Department --</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?= htmlspecialchars($d['department_name']) ?>" <?= ($d['department_name']==$selected_dept)?'selected':'' ?>>
                    <?= htmlspecialchars($d['department_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select name="semester" required>
            <option value="">-- Select Semester --</option>
            <?php for ($i=1; $i<=$total_semesters; $i++): ?>
                <option value="<?= $i ?>" <?= ($selected_sem==$i)?'selected':'' ?>>
                    <?= $i ?><?= ($i==1?'st':($i==2?'nd':($i==3?'rd':'th'))) ?> Semester
                </option>
            <?php endfor; ?>
        </select>
        
        <button type="submit">Search</button>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD']==='POST'): ?>
        <?php if (!empty($students)): ?>
            <div class="table-wrap">
                <table class="excel">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Symbol No</th>
                            <th>Semester</th>
                            <th>Batch Year</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i=1; foreach ($students as $stu): ?>
                            <tr class="<?= in_array($stu['id'],$highlight_ids)?'highlight':'' ?>">
                                <td><?= $i ?></td>
                                <td><?= htmlspecialchars($stu['full_name']) ?></td>
                                <td><?= htmlspecialchars($stu['symbol_no']) ?></td>
                                <td><?= htmlspecialchars($stu['semester']) ?></td>
                                <td><?= htmlspecialchars($stu['batch_year']) ?></td>
                                <td><?= htmlspecialchars($stu['department']) ?></td>
                                <td><?= htmlspecialchars($stu['email']) ?></td>
                                <td><?= htmlspecialchars($stu['phone'] ?? '-') ?></td>
                            </tr>
                        <?php $i++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="no-data">No students found for selected Department & Semester.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
