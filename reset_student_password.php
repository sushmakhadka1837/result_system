<?php
require 'db_config.php';

$message = '';

// Reset password if form submitted
if(isset($_POST['reset_password'])) {
    $student_id = intval($_POST['student_id']);
    $new_password = $_POST['new_password'];
    
    if($student_id && $new_password) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE students SET password = ?, is_verified = 1 WHERE id = ?");
        $stmt->bind_param("si", $hashed, $student_id);
        
        if($stmt->execute()) {
            $message = "<div class='alert alert-success'>Password updated successfully for student ID: $student_id</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error updating password</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Password Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 1200px; }
        table { font-size: 0.9rem; }
        .pwd-hash { 
            max-width: 200px; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            white-space: nowrap; 
            font-family: monospace;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔐 Student Password Manager</h2>
        <p class="text-muted">View student details and reset passwords</p>
        <hr>

        <?= $message ?>

        <h4>All Students</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Symbol No</th>
                        <th>Batch</th>
                        <th>Verified</th>
                        <th>Password Hash</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $students = $conn->query("SELECT id, full_name, email, symbol_no, batch_year, password, is_verified FROM students ORDER BY id ASC LIMIT 50");
                    while($s = $students->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $s['id'] ?></td>
                        <td><?= htmlspecialchars($s['full_name']) ?></td>
                        <td><?= htmlspecialchars($s['email']) ?></td>
                        <td><?= htmlspecialchars($s['symbol_no']) ?></td>
                        <td><?= htmlspecialchars($s['batch_year']) ?></td>
                        <td>
                            <?= $s['is_verified'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning">No</span>' ?>
                        </td>
                        <td class="pwd-hash"><?= htmlspecialchars(substr($s['password'], 0, 40)) ?>...</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="showResetForm(<?= $s['id'] ?>, '<?= htmlspecialchars($s['full_name']) ?>')">Reset</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <hr>
        <div id="resetForm" style="display:none;" class="mt-4 p-4 bg-light rounded">
            <h4>Reset Password</h4>
            <form method="POST">
                <input type="hidden" name="student_id" id="reset_student_id">
                <div class="mb-3">
                    <label class="form-label"><strong>Student:</strong> <span id="student_name_display"></span></label>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="text" name="new_password" class="form-control" placeholder="Enter new password" required>
                    <small class="text-muted">Common passwords: password, 123456, test123</small>
                </div>
                <button type="submit" name="reset_password" class="btn btn-danger">Reset Password</button>
                <button type="button" class="btn btn-secondary" onclick="hideResetForm()">Cancel</button>
            </form>
        </div>

        <hr>
        <div class="alert alert-info">
            <strong>💡 Quick Login Test Credentials:</strong><br>
            After resetting password to "password", use:<br>
            - Email: (from table above)<br>
            - Symbol No: (from table above)<br>
            - Password: password
        </div>
    </div>

    <script>
        function showResetForm(id, name) {
            document.getElementById('reset_student_id').value = id;
            document.getElementById('student_name_display').textContent = name + ' (ID: ' + id + ')';
            document.getElementById('resetForm').style.display = 'block';
            document.getElementById('resetForm').scrollIntoView({behavior: 'smooth'});
        }

        function hideResetForm() {
            document.getElementById('resetForm').style.display = 'none';
        }
    </script>
</body>
</html>
