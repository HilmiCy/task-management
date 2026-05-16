<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tentukan root path
$root_path = dirname(__DIR__);

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil avatar dari database
$user_avatar = 'default.png';
if (isset($_SESSION['user_id'])) {
    require_once $root_path . '/classes/Database.php';
    $db = DB::getInstance();
    $sql = "SELECT avatar, full_name, username FROM users WHERE id = ?";
    
    // Gunakan method fetch dari class DB
    $user = $db->fetch($sql, [$_SESSION['user_id']]);
    
    if ($user) {
        $user_avatar = !empty($user['avatar']) ? $user['avatar'] : 'default.png';
        // Simpan ke session
        $_SESSION['user_avatar'] = $user_avatar;
        $_SESSION['user_fullname'] = $user['full_name'] ?? $user['username'];
    }
}

$page_title = $page_title ?? APP_NAME . ' - Task Management';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="description" content="Aplikasi manajemen tugas modern untuk meningkatkan produktivitas">
    <title><?= htmlspecialchars($page_title) ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js untuk grafik -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom CSS - gunakan path absolut -->
    <link rel="stylesheet" href="/task_management/assets/css/style.css">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/task_management/assets/images/favicon.ico">
    
    <style>
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
        }
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .user-avatar i {
            font-size: 40px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar akan dimuat di sini -->
        <?php include_once $root_path . '/includes/sidebar.php'; ?>
        
        <!-- Main Content Wrapper -->
        <div class="main-wrapper">
            <!-- Top Navigation Bar -->
            <header class="top-header">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title">
                        <h2><?= $page_heading ?? 'Dashboard' ?></h2>
                    </div>
                </div>
                
                <div class="header-right">
                    <!-- Search Bar -->
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Cari tugas, catatan..." id="globalSearch">
                    </div>
                    
                    <!-- Notifications -->
                    <div class="notification-dropdown">
                        <button class="notification-btn" id="notificationBtn">
                            <i class="fas fa-bell"></i>
                            <span class="badge" id="notificationBadge">0</span>
                        </button>
                        <div class="dropdown-menu" id="notificationMenu">
                            <div class="dropdown-header">
                                <h4>Notifikasi</h4>
                                <button class="mark-all-read">Tandai semua</button>
                            </div>
                            <div class="dropdown-body" id="notificationList">
                                <div class="loading-notif">Memuat...</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Profile -->
                    <div class="user-dropdown">
                        <button class="user-btn" id="userBtn">
                            <div class="user-avatar">
                                <?php if ($user_avatar == 'default.png' || empty($user_avatar)): ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php else: ?>
                                    <img src="/task_management/assets/images/avatars/<?= htmlspecialchars($user_avatar) ?>" alt="Avatar">
                                <?php endif; ?>
                            </div>
                            <div class="user-info">
                                <span class="user-name"><?= htmlspecialchars($_SESSION['user_fullname'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User') ?></span>
                                <span class="user-role">Member</span>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu" id="userMenu">
                            <a href="/task_management/profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>Profil Saya</span>
                            </a>
                            <a href="/task_management/settings.php" class="dropdown-item">
                                <i class="fas fa-cog"></i>
                                <span>Pengaturan</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="/task_management/logout.php" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="main-content">