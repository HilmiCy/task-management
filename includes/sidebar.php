<?php
// Mendapatkan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-tasks"></i>
            <span class="logo-text">TaskFlow</span>
        </div>
        <button class="sidebar-close" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="sidebar-user">
        <div class="user-avatar-large">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="user-details">
            <h4><?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User') ?></h4>
            <p><?= htmlspecialchars($_SESSION['user_email'] ?? 'user@example.com') ?></p>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <a href="/task_management/dashboard.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                    <?php if($current_page == 'dashboard.php'): ?>
                        <i class="fas fa-chevron-right"></i>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-divider">MAIN MENU</li>
            
            <li class="nav-item <?= $current_dir == 'tasks' ? 'active' : '' ?>">
                <a href="/task_management/pages/tasks/index.php" class="nav-link">
                    <i class="fas fa-check-circle"></i>
                    <span>Semua Tugas</span>
                    <?php if($current_dir == 'tasks'): ?>
                        <i class="fas fa-chevron-right"></i>
                    <?php endif; ?>
                </a>
            </li>
            
            <!-- GANTI: Tambah Tugas menjadi Kalender -->
            <li class="nav-item <?= $current_dir == 'calendar' ? 'active' : '' ?>">
                <a href="/task_management/pages/calendar/index.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Kalender</span>
                    <?php if($current_dir == 'calendar'): ?>
                        <i class="fas fa-chevron-right"></i>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item <?= $current_dir == 'categories' ? 'active' : '' ?>">
                <a href="/task_management/pages/categories/index.php" class="nav-link">
                    <i class="fas fa-tags"></i>
                    <span>Kategori</span>
                    <?php if($current_dir == 'categories'): ?>
                        <i class="fas fa-chevron-right"></i>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item <?= $current_dir == 'notes' ? 'active' : '' ?>">
                <a href="/task_management/pages/notes/index.php" class="nav-link">
                    <i class="fas fa-sticky-note"></i>
                    <span>Catatan</span>
                    <?php if($current_dir == 'notes'): ?>
                        <i class="fas fa-chevron-right"></i>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-divider">LAINNYA</li>
            
            <li class="nav-item <?= $current_dir == 'reports' ? 'active' : '' ?>">
                <a href="/task_management/pages/reports/index.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Laporan</span>
                    <?php if($current_dir == 'reports'): ?>
                        <i class="fas fa-chevron-right"></i>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/task_management/profile.php" class="nav-link">
                    <i class="fas fa-user-circle"></i>
                    <span>Profil</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/task_management/settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Pengaturan</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="progress-stats">
            <div class="progress-label">
                <span>Produktivitas Hari Ini</span>
                <span id="todayProgressPercent">0%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="todayProgressFill" style="width: 0%"></div>
            </div>
        </div>
        <button class="logout-btn-mobile" onclick="location.href='/task_management/logout.php'">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </button>
    </div>
</aside>