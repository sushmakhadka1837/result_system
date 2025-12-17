<?php

$conn = new mysqli("localhost", "root", "", "result_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$departments = $conn->query("SELECT * FROM departments");


$selected_dept = '';
$semesters_query = [];


if(isset($_POST['department'])) {
    $selected_dept = $_POST['department'];

   
    if($selected_dept == 'YOUR_ARCHITECTURE_DEPARTMENT_ID'){ 
        $semester_limit = 10;
    } else {
        $semester_limit = 8;
    }

    $semesters_query = $conn->query("SELECT * FROM semesters WHERE id <= $semester_limit");
} else {
    $semesters_query = $conn->query("SELECT * FROM semesters WHERE id <= 8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Search Students</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen flex flex-col">

<div class="flex-grow container mx-auto p-6">

<h2 class="text-3xl font-semibold text-indigo-700 text-center mb-8">Search Students</h2>

<form method="POST" action="student_list.php" class="bg-white p-6 rounded-lg shadow-md flex flex-wrap gap-4 justify-center">
    <div class="flex flex-col">
        <label class="font-semibold mb-1">Department:</label>
        <select name="department" required onchange="this.form.submit()" class="border p-2 rounded">
            <option value="">-- Select Department --</option>
            <?php
            $departments->data_seek(0);
            while($row = $departments->fetch_assoc()):
            ?>
                <option value="<?= $row['id'] ?>" <?= ($selected_dept == $row['id'])?'selected':'' ?>>
                    <?= $row['department_name'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="flex flex-col">
        <label class="font-semibold mb-1">Semester:</label>
        <select name="semester" required class="border p-2 rounded">
            <option value="">-- Select Semester --</option>
            <?php
            if(isset($semesters_query)){
                while($row = $semesters_query->fetch_assoc()):
            ?>
                <option value="<?= $row['id'] ?>"><?= $row['semester_name'] ?></option>
            <?php endwhile; } ?>
        </select>
    </div>

    <div class="flex flex-col">
        <label class="font-semibold mb-1">Section:</label>
        <input type="text" name="section" placeholder="Leave empty for all" class="border p-2 rounded">
    </div>

    <div class="flex flex-col">
        <label class="font-semibold mb-1">Batch Year:</label>
        <input type="number" name="batch_year" placeholder="Leave empty for all" class="border p-2 rounded">
    </div>

    <div class="flex items-end">
        <button type="submit" name="search" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded font-semibold">Search</button>
    </div>
</form>

</div>

<!-- Tailwind Footer -->
<footer class="bg-indigo-600 text-white mt-8 p-6">
  <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-6">
    <div>
      <h5 class="font-semibold mb-2">Quick Links</h5>
      <ul>
        <li><a href="index.php" class="hover:underline">Home</a></li>
        <li><a href="#" class="hover:underline">Our Programs</a></li>
        <li><a href="about.php" class="hover:underline">About Us</a></li>
        <li><a href="notice.php" class="hover:underline">Notice Board</a></li>
      </ul>
    </div>
    <div>
      <h5 class="font-semibold mb-2">Follow Us</h5>
      <div class="flex gap-2">
        <a href="https://www.facebook.com/PECPoU" aria-label="Facebook">
          <img src="https://img.icons8.com/ios-filled/24/ffffff/facebook-new.png" alt="Facebook"/>
        </a>
        <a href="https://www.instagram.com/pec.pkr/" aria-label="Instagram">
          <img src="https://img.icons8.com/ios-filled/24/ffffff/instagram-new.png" alt="Instagram"/>
        </a>
      </div>
    </div>
    <div>
      <h5 class="font-semibold mb-2">Contact Us</h5>
      <p>Phirke Pokhara-8, Nepal</p>
      <p>Phone: 061 581209</p>
      <p>Email: info@pec.edu.np</p>
    </div>
    <div>
      <h5 class="font-semibold mb-2">Useful Links</h5>
      <ul>
        <li><a href="https://pu.edu.np/" class="hover:underline">Pokhara University</a></li>
        <li><a href="https://ctevt.org.np/" class="hover:underline">CTEVT</a></li>
        <li><a href="https://nec.gov.np/" class="hover:underline">Nepal Engineering Council</a></li>
        <li><a href="https://neanepal.org.np/" class="hover:underline">Nepal Engineer's Association</a></li>
        <li><a href="https://pu.edu.np/research/purc-seminar-series/" class="hover:underline">PU Research</a></li>
      </ul>
    </div>
  </div>
  <div class="text-center mt-6 text-sm">
    &copy; 2025 PEC Result Hub. All rights reserved.
  </div>
</footer>

</body>
</html>
