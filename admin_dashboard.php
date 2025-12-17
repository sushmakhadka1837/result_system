<?php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

// Admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header("Location: admin_login.php");
    exit;
}

$admin_data = getAdminById($admin_id, $conn);
$username   = $admin_data['email'] ?? 'Admin User';
$last_login = $admin_data['last_login_at'] ?? 'N/A';
$page_title = "Admin Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans">

<!-- Header -->
<header class="bg-indigo-600 text-white p-4 flex justify-between items-center">
  <h1 class="text-xl font-bold">Result Management System</h1>
  <div class="flex items-center gap-4">
    <span>Welcome, <?= htmlspecialchars($username); ?></span>
    <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded">Logout</a>
  </div>
</header>

<div class="flex min-h-screen">
  <!-- Sidebar -->
  <aside class="w-64 bg-gray-900 text-gray-200">
    <nav class="p-4 space-y-2">
      <a href="admin_dashboard.php" class="block px-3 py-2 rounded hover:bg-gray-700">Dashboard</a>
      <a href="manage_users.php" class="block px-3 py-2 rounded hover:bg-gray-700">Manage Users</a>
      <a href="manage_departments.php" class="block px-3 py-2 rounded hover:bg-gray-700">Manage Departments</a>
      <a href="manage_subjects.php" class="block px-3 py-2 rounded hover:bg-gray-700">Manage Subjects</a>
      <a href="manage_students.php" class="block px-3 py-2 rounded hover:bg-gray-700">Manage Students</a>
      <a href="manage_teachers.php" class="block px-3 py-2 rounded hover:bg-gray-700">Manage Teachers</a>
      <a href="admin_publish_results.php" class="block px-3 py-2 rounded hover:bg-gray-700">Publish Results</a>
      <a href="admin_settings.php" class="block px-3 py-2 rounded hover:bg-gray-700">Settings</a>
      <a href="activity_log.php" class="block px-3 py-2 rounded hover:bg-gray-700">Activity Log</a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-6">
    <h2 class="text-2xl font-semibold mb-4">Dashboard Overview</h2>
    <p class="text-gray-600 mb-6">Central control panel for the Result Management System.</p>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <div class="bg-white rounded-lg p-5 shadow">
        <h3 class="text-gray-500">Total Students</h3>
        <p class="text-2xl font-bold"><?= getTotalCount('students', $conn); ?></p>
      </div>
      <div class="bg-white rounded-lg p-5 shadow">
        <h3 class="text-gray-500">Total Teachers</h3>
        <p class="text-2xl font-bold"><?= getTotalCount('teachers', $conn); ?></p>
      </div>
      <div class="bg-white rounded-lg p-5 shadow">
        <h3 class="text-gray-500">Departments</h3>
        <p class="text-2xl font-bold"><?= getTotalCount('departments', $conn); ?></p>
      </div>
      <div class="bg-white rounded-lg p-5 shadow">
        <h3 class="text-gray-500">Subjects</h3>
        <p class="text-2xl font-bold"><?= getTotalCount('subjects_master', $conn); ?></p>
      </div>
      <div class="bg-white rounded-lg p-5 shadow">
        <h3 class="text-gray-500">Pending Results</h3>
        <p class="text-2xl font-bold"><?= getPendingResultsCount($conn); ?></p>
      </div>
      <div class="bg-white rounded-lg p-5 shadow">
        <h3 class="text-gray-500">Last Login</h3>
        <p class="text-xl"><?= htmlspecialchars($last_login); ?></p>
      </div>
    </div>

    <!-- =========================
         Upload Notes Section
    ========================= -->


  </main>
</div>

<footer class="bg-gray-200 text-center py-4 mt-6">
  <p>&copy; <?= date('Y'); ?> Your College Name. All rights reserved.</p>
</footer>

</body>
</html>
