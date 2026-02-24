<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['student_id'])){
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch existing student data
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if(!$student){
    header("Location: student_dashboard.php");
    exit();
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';

    // Optional profile photo upload
    $profile_photo = $student['profile_photo']; // default existing
    if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0){
        $target_dir = "uploads/";
        
        // Create uploads directory if it doesn't exist
        if(!is_dir($target_dir)){
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = time().'_'.basename($_FILES["profile_photo"]["name"]);
        $target_file = $target_dir . $file_name;
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_photo']['type'];
        
        if(in_array($file_type, $allowed_types)){
            if(move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)){
                $profile_photo = $target_file;
            } else {
                $error = "Failed to upload photo. Please check folder permissions.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, and GIF allowed.";
        }
    }

    // Update database only if no upload errors
    if(!isset($error)){
        $stmt = $conn->prepare("UPDATE students SET full_name=?, email=?, phone=?, profile_photo=? WHERE id=?");
        $stmt->bind_param("ssssi", $full_name, $email, $phone, $profile_photo, $student_id);
        if($stmt->execute()){
            header("Location: student_edit_profile.php?success=1");
            exit();
        } else {
            $error = "Failed to update profile. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile - Student Portal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 30px 15px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.profile-container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    overflow: hidden;
}

.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 30px;
    text-align: center;
    position: relative;
}

.profile-header h2 {
    margin: 0;
    font-weight: 800;
    letter-spacing: 1px;
}

.profile-photo-section {
    text-align: center;
    padding: 30px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-bottom: 3px solid #e9ecef;
}

.profile-photo-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}

.profile-photo-preview {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    object-fit: cover;
    border: 6px solid white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
    cursor: pointer;
}

.profile-photo-preview:hover {
    transform: scale(1.05);
    box-shadow: 0 15px 40px rgba(0,0,0,0.25);
    filter: brightness(0.9);
}

.upload-overlay {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
}

.upload-overlay:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.upload-overlay i {
    color: white;
    font-size: 1.2rem;
}

.file-input-hidden {
    display: none;
}

.form-section {
    padding: 40px 30px;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-control, .form-select {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 12px 15px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
}

.form-control:read-only {
    background-color: #f8f9fa;
    cursor: not-allowed;
    border-color: #dee2e6;
}

.input-icon {
    position: relative;
}

.input-icon i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.input-icon .form-control {
    padding-left: 45px;
}

.btn-update {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    font-weight: 700;
    padding: 14px 40px;
    border-radius: 10px;
    font-size: 1rem;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.btn-update:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: white;
}

.btn-cancel {
    background: #6c757d;
    border: none;
    color: white;
    font-weight: 600;
    padding: 14px 40px;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.btn-cancel:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.alert-custom {
    border-radius: 10px;
    border: none;
    padding: 15px 20px;
    margin-bottom: 20px;
}

.info-card {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-left: 4px solid #2196f3;
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 25px;
}

.info-card i {
    color: #2196f3;
    font-size: 1.2rem;
    margin-right: 10px;
}

@media (max-width: 768px) {
    body { padding: 15px 10px; }
    .profile-container { border-radius: 15px; }
    .profile-header { padding: 30px 20px; }
    .profile-header h2 { font-size: 1.5rem; }
    .profile-photo-section { padding: 25px 15px; }
    .profile-photo-preview { width: 150px; height: 150px; }
    .form-section { padding: 30px 20px; }
    .btn-update, .btn-cancel { padding: 12px 30px; font-size: 0.95rem; }
}

@media (max-width: 576px) {
    body { padding: 10px 5px; }
    .profile-header { padding: 25px 15px; }
    .profile-header h2 { font-size: 1.3rem; }
    .profile-photo-preview { width: 130px; height: 130px; border: 4px solid white; }
    .upload-overlay { width: 45px; height: 45px; }
    .upload-overlay i { font-size: 1rem; }
    .form-section { padding: 25px 15px; }
    .btn-update, .btn-cancel { 
        padding: 12px 20px;
        font-size: 0.9rem;
        width: 100%;
        margin-bottom: 10px;
    }
}
</style>
</head>
<body>
<?php include 'student_header.php'; ?>

<div class="profile-container">
    <div class="profile-header">
        <h2><i class="fas fa-user-edit me-2"></i>Edit Your Profile</h2>
        <p class="mb-0 mt-2 opacity-75">Update your personal information</p>
    </div>

    <form action="student_edit_profile.php" method="post" enctype="multipart/form-data">
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-custom mx-4 mt-4">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-custom mx-4 mt-4">
                <i class="fas fa-check-circle me-2"></i>Profile updated successfully!
            </div>
        <?php endif; ?>

        <!-- Profile Photo Section -->
        <div class="profile-photo-section">
            <div class="profile-photo-wrapper">
                <img 
                    id="profilePreview" 
                    src="<?php echo !empty($student['profile_photo']) ? htmlspecialchars($student['profile_photo']) : 'uploads/default-avatar.png'; ?>" 
                    alt="Profile Photo" 
                    class="profile-photo-preview"
                    onclick="document.getElementById('profilePhotoInput').click()"
                    title="Click to change photo"
                    onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($student['full_name']); ?>&size=180&background=667eea&color=fff&bold=true'"
                >
                <label for="profilePhotoInput" class="upload-overlay" title="Change Photo">
                    <i class="fas fa-camera"></i>
                </label>
                <input 
                    type="file" 
                    name="profile_photo" 
                    id="profilePhotoInput" 
                    class="file-input-hidden" 
                    accept="image/*"
                    onchange="previewImage(event)"
                >
            </div>
            <p class="text-muted mb-0">
                <i class="fas fa-info-circle me-2"></i>Click the photo or camera icon to upload a new picture
            </p>
            <small class="text-muted">Recommended: Square image, max 2MB</small>
        </div>

        <!-- Form Section -->
        <div class="form-section">
            <div class="info-card">
                <i class="fas fa-shield-alt"></i>
                <strong>Note:</strong> Department and Batch Year cannot be changed. Contact admin for modifications.
            </div>

            <div class="row g-4">
                <!-- Full Name -->
                <div class="col-md-12">
                    <label class="form-label">
                        <i class="fas fa-user me-2"></i>Full Name
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input 
                            type="text" 
                            name="full_name" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($student['full_name'] ?? ''); ?>" 
                            required
                            placeholder="Enter your full name"
                        >
                    </div>
                </div>

                <!-- Email -->
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email Address
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input 
                            type="email" 
                            name="email" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" 
                            required
                            placeholder="your.email@example.com"
                        >
                    </div>
                </div>

                <!-- Phone -->
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-phone me-2"></i>Phone Number
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-phone"></i>
                        <input 
                            type="text" 
                            name="phone" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>"
                            placeholder="9800000000"
                        >
                    </div>
                </div>

                <!-- Department (Read-only) -->
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-building me-2"></i>Department
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-building"></i>
                        <input 
                            type="text" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($student['department'] ?? 'N/A'); ?>" 
                            readonly
                        >
                    </div>
                </div>

                <!-- Batch Year (Read-only) -->
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-calendar-alt me-2"></i>Batch Year
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-calendar-alt"></i>
                        <input 
                            type="text" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($student['batch_year'] ?? 'N/A'); ?>" 
                            readonly
                        >
                    </div>
                </div>

                <!-- Buttons -->
                <div class="col-12">
                    <div class="d-flex gap-3 justify-content-center mt-4">
                        <button type="submit" class="btn btn-update">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                        <a href="student_dashboard.php" class="btn btn-cancel">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        // Validate file size (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB');
            event.target.value = '';
            return;
        }

        // Validate file type
        if (!file.type.startsWith('image/')) {
            alert('Please select a valid image file');
            event.target.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>
