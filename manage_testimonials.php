<?php
session_start();
require 'db_config.php';

// Check if user is admin
if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// Handle Delete
if(isset($_POST['delete_id'])){
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("SELECT photo_path FROM testimonials WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if($result && $result['photo_path'] && file_exists($result['photo_path'])){
        unlink($result['photo_path']);
    }
    
    $del_stmt = $conn->prepare("DELETE FROM testimonials WHERE id = ?");
    $del_stmt->bind_param("i", $id);
    if($del_stmt->execute()){
        $success_msg = "Testimonial deleted successfully.";
    } else {
        $error_msg = "Error deleting testimonial.";
    }
}

// Handle Add/Edit
if(isset($_POST['submit'])){
    $id = isset($_POST['id']) && $_POST['id'] ? intval($_POST['id']) : null;
    $name = trim($_POST['name']);
    $role = $_POST['role'];
    $quote = trim($_POST['quote']);
    $rating = intval($_POST['rating']);
    $status = $_POST['status'];
    $photo_path = '';

    // Validate
    if(!$name || !$role || !$quote){
        $error_msg = "Name, role, and quote are required.";
    } else {
        // Handle photo upload
        if(isset($_FILES['photo']) && $_FILES['photo']['size'] > 0){
            $upload_dir = 'images/testimonials/';
            if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if(!in_array($file_ext, $allowed)){
                $error_msg = "Only JPG, PNG, GIF allowed.";
            } else {
                $file_name = uniqid() . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;
                
                if(move_uploaded_file($_FILES['photo']['tmp_name'], $file_path)){
                    $photo_path = $file_path;
                } else {
                    $error_msg = "File upload failed.";
                }
            }
        }

        // Insert or Update
        if(!$error_msg){
            if($id){
                // Get existing photo
                $existing = $conn->prepare("SELECT photo_path FROM testimonials WHERE id = ?");
                $existing->bind_param("i", $id);
                $existing->execute();
                $existing_result = $existing->get_result()->fetch_assoc();
                
                if(!$photo_path && $existing_result){
                    $photo_path = $existing_result['photo_path'];
                }
                
                $update = $conn->prepare("UPDATE testimonials SET name=?, role=?, quote=?, rating=?, status=?, photo_path=? WHERE id=?");
                $update->bind_param("sssiisi", $name, $role, $quote, $rating, $status, $photo_path, $id);
                if($update->execute()){
                    $success_msg = "Testimonial updated successfully.";
                } else {
                    $error_msg = "Error updating testimonial.";
                }
            } else {
                // Insert new
                $insert = $conn->prepare("INSERT INTO testimonials (name, role, quote, rating, status, photo_path) VALUES (?, ?, ?, ?, ?, ?)");
                $insert->bind_param("sssiis", $name, $role, $quote, $rating, $status, $photo_path);
                if($insert->execute()){
                    $success_msg = "Testimonial added successfully.";
                } else {
                    $error_msg = "Error adding testimonial.";
                }
            }
        }
    }
}

// Fetch all testimonials
$testimonials = $conn->query("SELECT * FROM testimonials ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Testimonials - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Poppins', sans-serif; }
        .sidebar { background: #001f4d; color: white; min-height: 100vh; padding-top: 20px; }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; transition: 0.3s; }
        .sidebar a:hover { background: #003380; }
        .main-content { padding: 30px; }
        .card { border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-radius: 12px; }
        .btn-primary { background: #001f4d; border: none; }
        .btn-primary:hover { background: #003380; }
        .table-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #f4c430; }
        .badge-active { background: #10b981; }
        .badge-inactive { background: #ef4444; }
        .form-label { font-weight: 600; color: #001f4d; }
        .testimonial-preview { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-top: 10px; background: #f9fafb; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar">
            <h5 class="mb-4 ps-3"><i class="fas fa-cog"></i> Admin</h5>
            <a href="admin_dashboard.php"><i class="fas fa-chart-bar"></i> Dashboard</a>
            <a href="manage_testimonials.php" class="active" style="background: #003380;"><i class="fas fa-comments"></i> Testimonials</a>
            <a href="manage_users.php"><i class="fas fa-users"></i> Users</a>
            <a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="col-md-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark"><i class="fas fa-comments text-primary"></i> Manage Testimonials</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New</button>
            </div>

            <?php if($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?= $success_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?= $error_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Photo</th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Quote</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($testimonials->num_rows > 0): while($t = $testimonials->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if($t['photo_path']): ?>
                                            <img src="<?= $t['photo_path'] ?>" alt="<?= $t['name'] ?>" class="table-avatar">
                                        <?php else: ?>
                                            <span class="text-muted"><i class="fas fa-image"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($t['name']) ?></td>
                                    <td><span class="badge bg-info"><?= ucfirst($t['role']) ?></span></td>
                                    <td><small><?= htmlspecialchars(substr($t['quote'], 0, 50)) ?>...</small></td>
                                    <td>
                                        <i class="fas fa-star text-warning"></i>
                                        <?= $t['rating'] ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $t['status'] == 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                            <?= ucfirst($t['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editTestimonial(<?= $t['id'] ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteTestimonial(<?= $t['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="7" class="text-center text-muted">No testimonials yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Add Testimonial</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="principal">Principal</option>
                            <option value="management">Management</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quote <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="quote" name="quote" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Photo</label>
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                        <small class="text-muted">JPG, PNG, or GIF (optional)</small>
                        <div id="photoPreview" style="margin-top: 10px;"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <select class="form-select" id="rating" name="rating">
                            <option value="5">⭐⭐⭐⭐⭐ (5 stars)</option>
                            <option value="4">⭐⭐⭐⭐ (4 stars)</option>
                            <option value="3">⭐⭐⭐ (3 stars)</option>
                            <option value="2">⭐⭐ (2 stars)</option>
                            <option value="1">⭐ (1 star)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editTestimonial(id) {
    fetch(`get_testimonial.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('id').value = data.id;
            document.getElementById('name').value = data.name;
            document.getElementById('role').value = data.role;
            document.getElementById('quote').value = data.quote;
            document.getElementById('rating').value = data.rating;
            document.getElementById('status').value = data.status;
            document.getElementById('modalTitle').textContent = 'Edit Testimonial';
            new bootstrap.Modal(document.getElementById('addModal')).show();
        });
}

function deleteTestimonial(id) {
    if(confirm('Delete this testimonial?')){
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="delete_id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

// Photo preview
document.getElementById('photo')?.addEventListener('change', function(e) {
    const preview = document.getElementById('photoPreview');
    if(e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100px; border-radius: 8px;">`;
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});
</script>

</body>
</html>
