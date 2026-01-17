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
$page_title = "Admin Dashboard | RMS";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link:hover { background-color: rgba(255, 255, 255, 0.1); }
        .sidebar-link.active { background-color: #4f46e5; color: white; border-right: 4px solid #818cf8; }
        /* Custom scrollbar for better UX */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <div class="flex min-h-screen">
        <aside class="w-72 bg-slate-900 text-slate-300 fixed inset-y-0 left-0 z-50 flex flex-col transition-all duration-300">
            <div class="p-6 flex items-center gap-3 border-b border-slate-800">
                <div class="h-10 w-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                    <i class="fa-solid fa-graduation-cap text-lg"></i>
                </div>
                <div class="leading-tight">
                    <span class="text-xl font-bold text-white block tracking-tight">RMS</span>
                    <span class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">Administrator</span>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto p-4 space-y-1 mt-4">
                <p class="text-[11px] uppercase font-bold text-slate-500 px-3 py-2 tracking-widest">Dashboard Area</p>
                
                <a href="admin_dashboard.php" class="sidebar-link active flex items-center gap-3 px-3 py-3 rounded-xl transition-all group">
                    <i class="fa-solid fa-house-chimney w-5 text-center"></i>
                    <span class="font-medium">Dashboard Overview</span>
                </a>

                <div class="my-4 border-t border-slate-800/50 pt-4">
                    <p class="text-[11px] uppercase font-bold text-slate-500 px-3 py-2 tracking-widest">Management</p>
                </div>

                <a href="manage_users.php" class="sidebar-link flex items-center gap-3 px-3 py-3 rounded-xl transition-all group hover:text-white">
                    <i class="fa-solid fa-user-shield w-5 text-slate-500 group-hover:text-indigo-400"></i>
                    <span class="font-medium">User Accounts</span>
                </a>

                <a href="manage_departments.php" class="sidebar-link flex items-center gap-3 px-3 py-3 rounded-xl transition-all group hover:text-white">
                    <i class="fa-solid fa-sitemap w-5 text-slate-500 group-hover:text-indigo-400"></i>
                    <span class="font-medium">Departments</span>
                </a>

                <a href="manage_subjects.php" class="sidebar-link flex items-center gap-3 px-3 py-3 rounded-xl transition-all group hover:text-white">
                    <i class="fa-solid fa-book-bookmark w-5 text-slate-500 group-hover:text-indigo-400"></i>
                    <span class="font-medium">Subject Catalog</span>
                </a>

                <a href="manage_students.php" class="sidebar-link flex items-center gap-3 px-3 py-3 rounded-xl transition-all group hover:text-white">
                    <i class="fa-solid fa-user-graduate w-5 text-slate-500 group-hover:text-indigo-400"></i>
                    <span class="font-medium">Students List</span>
                </a>

                <a href="manage_teachers.php" class="sidebar-link flex items-center gap-3 px-3 py-3 rounded-xl transition-all group hover:text-white">
                    <i class="fa-solid fa-chalkboard-user w-5 text-slate-500 group-hover:text-indigo-400"></i>
                    <span class="font-medium">Faculty/Teachers</span>
                </a>

                <div class="my-4 border-t border-slate-800/50 pt-4">
                    <p class="text-[11px] uppercase font-bold text-slate-500 px-3 py-2 tracking-widest">Exam & Results</p>
                </div>

                <a href="admin_publish_results.php" class="sidebar-link flex items-center gap-3 px-3 py-3 rounded-xl transition-all group hover:text-white">
                    <i class="fa-solid fa-paper-plane w-5 text-orange-400"></i>
                    <span class="font-medium">Publish Results</span>
                </a>

                <a href="manage_feedback.php" class="sidebar-link flex items-center gap-3 px-3 py-3 rounded-xl transition-all group hover:text-white">
                    <i class="fa-solid fa-comments w-5 text-slate-500 group-hover:text-indigo-400"></i>
                    <span class="font-medium">Student Feedback</span>
                </a>

                <a href="manage_testimonials.php" class="sidebar-link flex items-center gap-3 px-3 py-3 rounded-xl transition-all group hover:text-white">
                    <i class="fa-solid fa-star w-5 text-slate-500 group-hover:text-indigo-400"></i>
                    <span class="font-medium">Testimonials</span>
                </a>

                <a href="activity_log.php" class="sidebar-link flex items-center gap-3 px-3 py-3 rounded-xl transition-all group hover:text-white">
                    <i class="fa-solid fa-clock-rotate-left w-5 text-slate-500 group-hover:text-indigo-400"></i>
                    <span class="font-medium">Activity Log</span>
                </a>
            </nav>

            <div class="p-4 bg-slate-950/40">
                <div class="flex items-center gap-3 p-2 bg-slate-800/40 rounded-2xl border border-slate-700/50">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-blue-500 flex items-center justify-center text-white shadow-inner">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-bold text-white truncate"><?= explode('@', $username)[0]; ?></p>
                        <p class="text-[10px] text-slate-500 italic">Last login: <?= date('H:i', strtotime($last_login)); ?></p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="flex-1 ml-72 flex flex-col min-h-screen">
            
            <header class="bg-white/80 backdrop-blur-md sticky top-0 z-40 border-b border-slate-200 px-10 h-20 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest">Current Page</h2>
                    <h3 class="text-xl font-bold text-slate-800">Analytics Dashboard</h3>
                </div>
                
                <div class="flex items-center gap-6">
                    <button class="bg-indigo-50 text-indigo-600 p-2.5 rounded-xl hover:bg-indigo-600 hover:text-white transition-all">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                    
                    <div class="h-8 w-[1px] bg-slate-200"></div>

                    <a href="logout.php" class="bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all border border-rose-100 flex items-center gap-2">
                        <i class="fa-solid fa-power-off"></i> Logout
                    </a>
                </div>
            </header>

            <main class="p-10 flex-1">
                
                <div class="flex items-center justify-between mb-10">
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Swagat Cha, Admin! 👋</h1>
                        <p class="text-slate-500 mt-1">Here's what's happening in your system today.</p>
                    </div>
                    <div class="bg-white shadow-sm border border-slate-200 px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2 text-slate-600">
                        <i class="fa-regular fa-calendar-check text-indigo-500"></i>
                        <?= date('l, F j, Y'); ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-8 mb-10">
                    
                    <div class="relative bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden group hover:shadow-xl transition-all duration-300">
                        <div class="relative z-10 flex flex-col gap-4">
                            <div class="h-12 w-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-user-graduate"></i>
                            </div>
                            <div>
                                <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Total Students</h3>
                                <p class="text-4xl font-black text-slate-900 mt-1"><?= getTotalCount('students', $conn); ?></p>
                            </div>
                        </div>
                        <i class="fa-solid fa-user-graduate absolute -right-4 -bottom-4 text-8xl text-slate-50 opacity-[0.03]"></i>
                    </div>

                    <div class="relative bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden group hover:shadow-xl transition-all duration-300">
                        <div class="relative z-10 flex flex-col gap-4">
                            <div class="h-12 w-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-chalkboard-user"></i>
                            </div>
                            <div>
                                <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Total Teachers</h3>
                                <p class="text-4xl font-black text-slate-900 mt-1"><?= getTotalCount('teachers', $conn); ?></p>
                            </div>
                        </div>
                        <i class="fa-solid fa-chalkboard-user absolute -right-4 -bottom-4 text-8xl text-slate-50 opacity-[0.03]"></i>
                    </div>

                    <div class="relative bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden group hover:shadow-xl transition-all duration-300">
                        <div class="relative z-10 flex flex-col gap-4">
                            <div class="h-12 w-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-building-columns"></i>
                            </div>
                            <div>
                                <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Departments</h3>
                                <p class="text-4xl font-black text-slate-900 mt-1"><?= getTotalCount('departments', $conn); ?></p>
                            </div>
                        </div>
                        <i class="fa-solid fa-building-columns absolute -right-4 -bottom-4 text-8xl text-slate-50 opacity-[0.03]"></i>
                    </div>

                    <div class="relative bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden group hover:shadow-xl transition-all duration-300">
                        <div class="relative z-10 flex flex-col gap-4">
                            <div class="h-12 w-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-book-open"></i>
                            </div>
                            <div>
                                <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Active Subjects</h3>
                                <p class="text-4xl font-black text-slate-900 mt-1"><?= getTotalCount('subjects_master', $conn); ?></p>
                            </div>
                        </div>
                        <i class="fa-solid fa-book-open absolute -right-4 -bottom-4 text-8xl text-slate-50 opacity-[0.03]"></i>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-xl font-bold text-slate-800">System Logs</h3>
                            <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full uppercase tracking-tighter">Live Updates</span>
                        </div>
                        
                        <div class="space-y-6">
                            <div class="flex items-start gap-4 p-4 rounded-2xl hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-100">
                                <div class="h-10 w-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center shrink-0 mt-1">
                                    <i class="fa-solid fa-info-circle"></i>
                                </div>
                                <div>
                                    <p class="text-slate-700 font-semibold leading-none">Database optimized successfully.</p>
                                    <p class="text-slate-400 text-xs mt-2">The system automatic cleanup job finished.</p>
                                    <span class="text-[10px] text-slate-400 inline-block mt-2 font-bold uppercase tracking-widest">2 Minutes Ago</span>
                                </div>
                            </div>

                            
                        </div>
                    </div>

                    <div class="flex flex-col gap-6">
                        

                        <div class="bg-white rounded-[2rem] border border-slate-100 p-8 shadow-sm">
                            <h3 class="text-lg font-bold text-slate-800 mb-4">Security Overview</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500 text-sm italic">Admin Role</span>
                                    <span class="text-xs bg-emerald-100 text-emerald-700 font-black px-2 py-0.5 rounded-md uppercase">Super</span>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="bg-white border-t border-slate-200 py-6 px-10 text-center">
                <p class="text-slate-400 text-sm">
                    &copy; <?= date('Y'); ?> <span class="font-black text-slate-600 tracking-tighter uppercase ml-1">Result Management System</span>. 
                    <span class="hidden sm:inline">Made with ❤️ for Academic Excellence.</span>
                </p>
            </footer>
        </div>
    </div>

</body>
</html>