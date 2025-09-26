<?php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

// --- Admin-only access ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<h2>❌ Access Denied. Admin only!</h2>";
    exit;
}

$page_title = "Manage Users";
$message = "";

// Fetch all users with department info
$users = [];
$sql = "SELECT u.user_id, u.name, u.email, u.role, d.dept_name, u.last_login_at
        FROM users u
        LEFT JOIN departments d ON u.dept_id = d.dept_id
        ORDER BY u.role, u.name";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($page_title); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans">

<div class="flex min-h-screen">
  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Main Content -->
  <main class="flex-1 p-6">
    <h2 class="text-2xl font-semibold mb-4"><?= htmlspecialchars($page_title); ?></h2>

    <div class="mb-4">
      <a href="add_user.php" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">+ Add New User</a>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full bg-white shadow rounded">
        <thead class="bg-gray-200">
          <tr>
            <th class="px-4 py-2">#</th>
            <th class="px-4 py-2">Name</th>
            <th class="px-4 py-2">Email</th>
            <th class="px-4 py-2">Role</th>
            <th class="px-4 py-2">Department</th>
            <th class="px-4 py-2">Last Login</th>
            <th class="px-4 py-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $index => $user): ?>
            <tr class="<?= $index % 2 == 0 ? 'bg-gray-50' : ''; ?>">
              <td class="px-4 py-2"><?= $index + 1; ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($user['name']); ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($user['email']); ?></td>
              <td class="px-4 py-2"><?= ucfirst(htmlspecialchars($user['role'])); ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($user['dept_name'] ?? '-'); ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($user['last_login_at'] ?? '-'); ?></td>
              <td class="px-4 py-2 space-x-2">
                <a href="edit_user.php?id=<?= $user['user_id']; ?>" class="bg-yellow-400 px-2 py-1 rounded hover:bg-yellow-500">Edit</a>
                <a href="delete_user.php?id=<?= $user['user_id']; ?>" onclick="return confirm('Are you sure?');" class="bg-red-500 px-2 py-1 rounded hover:bg-red-600">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

</body>
</html>
