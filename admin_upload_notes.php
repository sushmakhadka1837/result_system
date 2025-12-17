<!-- ===========================
     ADMIN UPLOAD NOTES SECTION
=========================== -->

<style>
    .upload-container {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        margin-top: 25px;
        width: 100%;
        max-width: 650px;
        box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    }

    .upload-title {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        margin-bottom: 15px;
    }

    .form-group label {
        font-weight: 600;
        margin-bottom: 6px;
    }

    .form-input {
        padding: 10px 13px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #f8f8f8;
    }

    .form-input:focus {
        border-color: #4F46E5;
        background: #fff;
        outline: none;
        box-shadow: 0 0 5px rgba(79,70,229,0.3);
    }

    .upload-btn {
        margin-top: 20px;
        background-color: #4F46E5;
        color: white;
        padding: 12px;
        border: none;
        width: 100%;
        border-radius: 8px;
        font-size: 17px;
        cursor: pointer;
        transition: 0.2s;
    }

    .upload-btn:hover {
        background-color: #3b36c8;
    }
</style>


<div class="upload-container">
    <h2 class="upload-title">📚 Upload Notes / PDF</h2>
    <form action="upload_notes.php" method="POST" enctype="multipart/form-data">
        <!-- Batch -->
        <div class="form-group">
            <label>Batch:</label>
            <select name="batch" class="form-input" required>
                <option value="">-- Select Batch --</option>
                <option value="new">New Batch</option>
                <option value="old">Old Batch</option>
            </select>
        </div>

        <!-- Department -->
        <div class="form-group">
            <label>Department:</label>
            <select name="department" id="department" class="form-input" required>
                <option value="">-- Select Department --</option>
                <?php
                $departments = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");
                while($d = $departments->fetch_assoc()):
                ?>
                    <option value="<?= $d['id']; ?>"><?= $d['department_name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Semester -->
        <div class="form-group">
            <label>Semester:</label>
            <select name="semester" id="semester" class="form-input" required>
                <option value="">-- Select Semester --</option>
            </select>
        </div>

        <!-- Subject -->
        <div class="form-group">
            <label>Subject:</label>
            <select name="subject" id="subject" class="form-input" required>
                <option value="">-- Select Subject --</option>
            </select>
        </div>

        <!-- Title -->
        <div class="form-group">
            <label>Notes Title:</label>
            <input type="text" name="title" class="form-input" placeholder="Enter notes title" required>
        </div>

        <!-- PDF Upload -->
        <div class="form-group">
            <label>Select PDF File:</label>
            <input type="file" class="form-input" name="pdf_file" accept="application/pdf" required>
        </div>

        <button class="upload-btn">Upload Notes</button>
    </form>
</div>

<!-- AJAX for Semester & Subject -->
<script>
document.getElementById("department").addEventListener("change", function(){
    let dept_id = this.value;
    fetch("fetch_semesters.php?dept_id=" + dept_id)
    .then(res => res.text())
    .then(data => {
        document.getElementById("semester").innerHTML = data;
        document.getElementById("subject").innerHTML = "<option value=''>-- Select Subject --</option>";
    });
});

document.getElementById("semester").addEventListener("change", function(){
    let sem_id = this.value;
    fetch("fetch_subjects.php?sem_id=" + sem_id)
    .then(res => res.text())
    .then(data => {
        document.getElementById("subject").innerHTML = data;
    });
});
</script>
3️⃣ fetch_semesters.php
php
Copy code
<?php
require 'db_config.php';
$dept = $_GET['dept_id'];
$sem = $conn->query("SELECT id, semester_name FROM semesters WHERE department_id='$dept' ORDER BY id ASC");
echo "<option value=''>-- Select Semester --</option>";
while($s = $sem->fetch_assoc()){
    echo "<option value='".$s['id']."'>".$s['semester_name']."</option>";
}
?>