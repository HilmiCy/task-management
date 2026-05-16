<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/User.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user = new User();
$db = DB::getInstance();

// Ambil data user
$userData = $user->getUserById($user_id);

// Ambil pengaturan user
$sql = "SELECT * FROM user_settings WHERE user_id = ?";
$settings = $db->fetch($sql, [$user_id]);

if (!$settings) {
    // Buat default settings jika belum ada
    $sql = "INSERT INTO user_settings (user_id) VALUES (?)";
    $db->insert($sql, [$user_id]);
    $sql = "SELECT * FROM user_settings WHERE user_id = ?";
    $settings = $db->fetch($sql, [$user_id]);
}

$success = '';
$error = '';

// Proses update settings
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_general') {
            // General settings
            $language = $_POST['language'] ?? 'id';
            $timezone = $_POST['timezone'] ?? 'Asia/Jakarta';
            $date_format = $_POST['date_format'] ?? 'd M Y';
            $time_format = $_POST['time_format'] ?? 'H:i';
            $items_per_page = (int)($_POST['items_per_page'] ?? 10);
            
            $sql = "UPDATE user_settings SET 
                    language = ?, 
                    timezone = ?, 
                    date_format = ?, 
                    time_format = ?, 
                    items_per_page = ? 
                    WHERE user_id = ?";
            $result = $db->update($sql, [$language, $timezone, $date_format, $time_format, $items_per_page, $user_id]);
            
            if ($result !== false) {
                $success = 'Pengaturan umum berhasil disimpan!';
                // Refresh settings
                $sql = "SELECT * FROM user_settings WHERE user_id = ?";
                $settings = $db->fetch($sql, [$user_id]);
            } else {
                $error = 'Gagal menyimpan pengaturan!';
            }
            
        } elseif ($_POST['action'] == 'update_notification') {
            // Notification settings
            $notification_enabled = isset($_POST['notification_enabled']) ? 1 : 0;
            $email_reminder = isset($_POST['email_reminder']) ? 1 : 0;
            $task_reminder = isset($_POST['task_reminder']) ? 1 : 0;
            $reminder_days = (int)($_POST['reminder_days'] ?? 1);
            $daily_summary = isset($_POST['daily_summary']) ? 1 : 0;
            $weekly_report = isset($_POST['weekly_report']) ? 1 : 0;
            
            $sql = "UPDATE user_settings SET 
                    notification_enabled = ?, 
                    email_reminder = ?, 
                    task_reminder = ?, 
                    reminder_days = ?, 
                    daily_summary = ?, 
                    weekly_report = ? 
                    WHERE user_id = ?";
            $result = $db->update($sql, [
                $notification_enabled, 
                $email_reminder, 
                $task_reminder, 
                $reminder_days, 
                $daily_summary, 
                $weekly_report, 
                $user_id
            ]);
            
            if ($result !== false) {
                $success = 'Pengaturan notifikasi berhasil disimpan!';
                // Refresh settings
                $sql = "SELECT * FROM user_settings WHERE user_id = ?";
                $settings = $db->fetch($sql, [$user_id]);
            } else {
                $error = 'Gagal menyimpan pengaturan notifikasi!';
            }
            
        } elseif ($_POST['action'] == 'update_display') {
            // Display settings
            $theme = $_POST['theme'] ?? 'light';
            $compact_view = isset($_POST['compact_view']) ? 1 : 0;
            $show_completed = isset($_POST['show_completed']) ? 1 : 0;
            $default_view = $_POST['default_view'] ?? 'list';
            
            $sql = "UPDATE user_settings SET 
                    theme = ?, 
                    compact_view = ?, 
                    show_completed = ?, 
                    default_view = ? 
                    WHERE user_id = ?";
            $result = $db->update($sql, [$theme, $compact_view, $show_completed, $default_view, $user_id]);
            
            if ($result !== false) {
                $success = 'Pengaturan tampilan berhasil disimpan!';
                // Refresh settings
                $sql = "SELECT * FROM user_settings WHERE user_id = ?";
                $settings = $db->fetch($sql, [$user_id]);
                
                // Set cookie untuk theme
                setcookie('theme', $theme, time() + (86400 * 30), "/");
            } else {
                $error = 'Gagal menyimpan pengaturan tampilan!';
            }
            
        } elseif ($_POST['action'] == 'change_password') {
            // Change password via User class
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($current_password)) {
                $error = 'Password saat ini harus diisi!';
            } elseif (empty($new_password)) {
                $error = 'Password baru tidak boleh kosong!';
            } elseif (strlen($new_password) < 6) {
                $error = 'Password baru minimal 6 karakter!';
            } elseif ($new_password !== $confirm_password) {
                $error = 'Konfirmasi password tidak cocok!';
            } else {
                $result = $user->changePassword($user_id, $current_password, $new_password);
                
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['error'];
                }
            }
            
        } elseif ($_POST['action'] == 'clear_data') {
            // Clear all data
            $confirm = $_POST['confirm_clear'] ?? '';
            
            if ($confirm === 'DELETE MY DATA') {
                // Hapus semua data user
                try {
                    // Hapus tasks
                    $db->delete("DELETE FROM tasks WHERE user_id = ?", [$user_id]);
                    // Hapus categories
                    $db->delete("DELETE FROM categories WHERE user_id = ?", [$user_id]);
                    // Hapus notes
                    $db->delete("DELETE FROM notes WHERE user_id = ?", [$user_id]);
                    
                    $success = 'Semua data berhasil dihapus!';
                } catch (Exception $e) {
                    $error = 'Gagal menghapus data: ' . $e->getMessage();
                }
            } else {
                $error = 'Konfirmasi tidak sesuai! Ketik "DELETE MY DATA" untuk mengkonfirmasi.';
            }
        }
    }
}

$page_title = 'Pengaturan - TaskFlow';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="settings-container">
    <div class="settings-header">
        <h1><i class="fas fa-cog"></i> Pengaturan</h1>
        <p>Atur preferensi dan konfigurasi aplikasi Anda</p>
    </div>

    <div class="settings-wrapper">
        <!-- Sidebar Navigation -->
        <div class="settings-sidebar">
            <div class="settings-nav">
                <button class="settings-nav-btn active" data-tab="general">
                    <i class="fas fa-sliders-h"></i> Umum
                </button>
                <button class="settings-nav-btn" data-tab="notifications">
                    <i class="fas fa-bell"></i> Notifikasi
                </button>
                <button class="settings-nav-btn" data-tab="display">
                    <i class="fas fa-palette"></i> Tampilan
                </button>
                <button class="settings-nav-btn" data-tab="security">
                    <i class="fas fa-shield-alt"></i> Keamanan
                </button>
                <button class="settings-nav-btn" data-tab="data">
                    <i class="fas fa-database"></i> Data
                </button>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="settings-content">
            <?php if($success): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- General Settings -->
            <div class="settings-panel active" id="panel-general">
                <div class="panel-header">
                    <h3><i class="fas fa-sliders-h"></i> Pengaturan Umum</h3>
                    <p>Atur bahasa, zona waktu, format tanggal, dan jumlah item per halaman</p>
                </div>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_general">
                    
                    <div class="form-group">
                        <label for="language">Bahasa</label>
                        <select id="language" name="language" class="form-control">
                            <option value="id" <?= ($settings['language'] ?? 'id') == 'id' ? 'selected' : '' ?>>Indonesia</option>
                            <option value="en" <?= ($settings['language'] ?? 'id') == 'en' ? 'selected' : '' ?>>English</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="timezone">Zona Waktu</label>
                        <select id="timezone" name="timezone" class="form-control">
                            <option value="Asia/Jakarta" <?= ($settings['timezone'] ?? 'Asia/Jakarta') == 'Asia/Jakarta' ? 'selected' : '' ?>>WIB (Jakarta)</option>
                            <option value="Asia/Makassar" <?= ($settings['timezone'] ?? 'Asia/Jakarta') == 'Asia/Makassar' ? 'selected' : '' ?>>WITA (Makassar)</option>
                            <option value="Asia/Jayapura" <?= ($settings['timezone'] ?? 'Asia/Jakarta') == 'Asia/Jayapura' ? 'selected' : '' ?>>WIT (Jayapura)</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_format">Format Tanggal</label>
                            <select id="date_format" name="date_format" class="form-control">
                                <option value="d M Y" <?= ($settings['date_format'] ?? 'd M Y') == 'd M Y' ? 'selected' : '' ?>>25 Jan 2024</option>
                                <option value="d/m/Y" <?= ($settings['date_format'] ?? 'd M Y') == 'd/m/Y' ? 'selected' : '' ?>>25/01/2024</option>
                                <option value="Y-m-d" <?= ($settings['date_format'] ?? 'd M Y') == 'Y-m-d' ? 'selected' : '' ?>>2024-01-25</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="time_format">Format Waktu</label>
                            <select id="time_format" name="time_format" class="form-control">
                                <option value="H:i" <?= ($settings['time_format'] ?? 'H:i') == 'H:i' ? 'selected' : '' ?>>24 Jam (14:30)</option>
                                <option value="h:i A" <?= ($settings['time_format'] ?? 'H:i') == 'h:i A' ? 'selected' : '' ?>>12 Jam (02:30 PM)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="items_per_page">Item per Halaman</label>
                        <select id="items_per_page" name="items_per_page" class="form-control">
                            <option value="5" <?= ($settings['items_per_page'] ?? 10) == 5 ? 'selected' : '' ?>>5 item</option>
                            <option value="10" <?= ($settings['items_per_page'] ?? 10) == 10 ? 'selected' : '' ?>>10 item</option>
                            <option value="25" <?= ($settings['items_per_page'] ?? 10) == 25 ? 'selected' : '' ?>>25 item</option>
                            <option value="50" <?= ($settings['items_per_page'] ?? 10) == 50 ? 'selected' : '' ?>>50 item</option>
                            <option value="100" <?= ($settings['items_per_page'] ?? 10) == 100 ? 'selected' : '' ?>>100 item</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Notification Settings -->
            <div class="settings-panel" id="panel-notifications">
                <div class="panel-header">
                    <h3><i class="fas fa-bell"></i> Pengaturan Notifikasi</h3>
                    <p>Atur bagaimana Anda ingin menerima notifikasi</p>
                </div>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_notification">
                    
                    <div class="toggle-group">
                        <div class="toggle-item">
                            <div class="toggle-info">
                                <label>Notifikasi Aplikasi</label>
                                <p>Terima notifikasi di dalam aplikasi</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="notification_enabled" value="1" <?= ($settings['notification_enabled'] ?? 1) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="toggle-item">
                            <div class="toggle-info">
                                <label>Pengingat Email</label>
                                <p>Terima pengingat tugas melalui email</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="email_reminder" value="1" <?= ($settings['email_reminder'] ?? 1) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="toggle-item">
                            <div class="toggle-info">
                                <label>Pengingat Tugas</label>
                                <p>Terima pengingat untuk tugas yang mendekati deadline</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="task_reminder" value="1" id="taskReminderCheckbox" <?= ($settings['task_reminder'] ?? 1) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="form-group" id="reminderDaysGroup" style="<?= ($settings['task_reminder'] ?? 1) ? '' : 'display: none;' ?>">
                            <label for="reminder_days">Pengingat (hari sebelum deadline)</label>
                            <select id="reminder_days" name="reminder_days" class="form-control">
                                <option value="1" <?= ($settings['reminder_days'] ?? 1) == 1 ? 'selected' : '' ?>>1 hari sebelumnya</option>
                                <option value="2" <?= ($settings['reminder_days'] ?? 1) == 2 ? 'selected' : '' ?>>2 hari sebelumnya</option>
                                <option value="3" <?= ($settings['reminder_days'] ?? 1) == 3 ? 'selected' : '' ?>>3 hari sebelumnya</option>
                                <option value="7" <?= ($settings['reminder_days'] ?? 1) == 7 ? 'selected' : '' ?>>1 minggu sebelumnya</option>
                            </select>
                        </div>
                        
                        <div class="toggle-item">
                            <div class="toggle-info">
                                <label>Ringkasan Harian</label>
                                <p>Terima ringkasan tugas setiap pagi</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="daily_summary" value="1" <?= ($settings['daily_summary'] ?? 0) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="toggle-item">
                            <div class="toggle-info">
                                <label>Laporan Mingguan</label>
                                <p>Terima laporan progres setiap minggu</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="weekly_report" value="1" <?= ($settings['weekly_report'] ?? 0) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Display Settings -->
            <div class="settings-panel" id="panel-display">
                <div class="panel-header">
                    <h3><i class="fas fa-palette"></i> Pengaturan Tampilan</h3>
                    <p>Sesuaikan tampilan aplikasi sesuai preferensi Anda</p>
                </div>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_display">
                    
                    <div class="form-group">
                        <label>Tema</label>
                        <div class="theme-options">
                            <label class="theme-option <?= ($settings['theme'] ?? 'light') == 'light' ? 'active' : '' ?>">
                                <input type="radio" name="theme" value="light" <?= ($settings['theme'] ?? 'light') == 'light' ? 'checked' : '' ?>>
                                <div class="theme-preview light">
                                    <i class="fas fa-sun"></i>
                                    <span>Terang</span>
                                </div>
                            </label>
                            <label class="theme-option <?= ($settings['theme'] ?? 'light') == 'dark' ? 'active' : '' ?>">
                                <input type="radio" name="theme" value="dark" <?= ($settings['theme'] ?? 'light') == 'dark' ? 'checked' : '' ?>>
                                <div class="theme-preview dark">
                                    <i class="fas fa-moon"></i>
                                    <span>Gelap</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="toggle-item">
                        <div class="toggle-info">
                            <label>Tampilan Ringkas</label>
                            <p>Tampilkan lebih banyak item dalam satu halaman</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="compact_view" value="1" <?= ($settings['compact_view'] ?? 0) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="toggle-item">
                        <div class="toggle-info">
                            <label>Tampilkan Tugas Selesai</label>
                            <p>Tampilkan tugas yang sudah selesai di daftar tugas</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="show_completed" value="1" <?= ($settings['show_completed'] ?? 1) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="default_view">Tampilan Default</label>
                        <select id="default_view" name="default_view" class="form-control">
                            <option value="list" <?= ($settings['default_view'] ?? 'list') == 'list' ? 'selected' : '' ?>>List / Tabel</option>
                            <option value="grid" <?= ($settings['default_view'] ?? 'list') == 'grid' ? 'selected' : '' ?>>Grid</option>
                            <option value="calendar" <?= ($settings['default_view'] ?? 'list') == 'calendar' ? 'selected' : '' ?>>Kalender</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security Settings -->
            <div class="settings-panel" id="panel-security">
                <div class="panel-header">
                    <h3><i class="fas fa-shield-alt"></i> Pengaturan Keamanan</h3>
                    <p>Atur keamanan akun Anda</p>
                </div>
                
                <div class="security-section">
                    <h4>Ganti Password</h4>
                    <form method="POST" class="settings-form">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Password Saat Ini</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Password Baru</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                            <small class="form-hint">Minimal 6 karakter</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password Baru</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-key"></i> Ganti Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Data Settings -->
            <div class="settings-panel" id="panel-data">
                <div class="panel-header">
                    <h3><i class="fas fa-database"></i> Pengaturan Data</h3>
                    <p>Kelola data aplikasi Anda</p>
                </div>
                
                <div class="data-section">
                    <h4>Ekspor Data</h4>
                    <p>Download semua data Anda dalam format JSON</p>
                    <button type="button" class="btn-secondary" onclick="exportData()">
                        <i class="fas fa-download"></i> Ekspor Data
                    </button>
                </div>
                
                <div class="data-section warning">
                    <h4>Hapus Semua Data</h4>
                    <p class="warning-text">Tindakan ini akan menghapus semua tugas, kategori, dan catatan Anda. Data yang dihapus tidak dapat dikembalikan!</p>
                    <form method="POST" id="clearDataForm" onsubmit="return confirmClearData(event)">
                        <input type="hidden" name="action" value="clear_data">
                        <div class="form-group">
                            <label>Ketik "DELETE MY DATA" untuk konfirmasi</label>
                            <input type="text" name="confirm_clear" class="form-control" placeholder="DELETE MY DATA">
                        </div>
                        <button type="submit" class="btn-danger">
                            <i class="fas fa-trash"></i> Hapus Semua Data
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.settings-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.settings-header {
    margin-bottom: 32px;
}

.settings-header h1 {
    font-size: 28px;
    color: #1f2937;
    margin-bottom: 8px;
}

.settings-header h1 i {
    color: #2c7a6e;
    margin-right: 12px;
}

.settings-header p {
    color: #6b7280;
    font-size: 14px;
}

.settings-wrapper {
    display: flex;
    gap: 32px;
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.settings-sidebar {
    width: 260px;
    background: #f9fafb;
    padding: 24px 0;
    border-right: 1px solid #e5e7eb;
}

.settings-nav {
    display: flex;
    flex-direction: column;
}

.settings-nav-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 24px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    color: #6b7280;
    transition: all 0.3s;
    text-align: left;
    width: 100%;
}

.settings-nav-btn i {
    width: 20px;
}

.settings-nav-btn:hover {
    background: #f3f4f6;
    color: #1f2937;
}

.settings-nav-btn.active {
    background: #e6f7f5;
    color: #2c7a6e;
    border-right: 3px solid #2c7a6e;
}

.settings-content {
    flex: 1;
    padding: 32px;
}

.settings-panel {
    display: none;
}

.settings-panel.active {
    display: block;
}

.panel-header {
    margin-bottom: 28px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.panel-header h3 {
    font-size: 18px;
    color: #1f2937;
    margin-bottom: 8px;
}

.panel-header h3 i {
    color: #2c7a6e;
    margin-right: 8px;
}

.panel-header p {
    font-size: 13px;
    color: #6b7280;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #374151;
    font-size: 14px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #2c7a6e;
    box-shadow: 0 0 0 3px rgba(44, 122, 110, 0.1);
}

.form-hint {
    display: block;
    margin-top: 6px;
    font-size: 11px;
    color: #9ca3af;
}

.form-actions {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

/* Toggle Switch */
.toggle-group {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.toggle-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
}

.toggle-info label {
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 4px;
}

.toggle-info p {
    font-size: 12px;
    color: #6b7280;
    margin: 0;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.3s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #2c7a6e;
}

input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

/* Theme Options */
.theme-options {
    display: flex;
    gap: 16px;
}

.theme-option {
    cursor: pointer;
}

.theme-option input {
    display: none;
}

.theme-preview {
    width: 100px;
    height: 80px;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s;
}

.theme-preview i {
    font-size: 24px;
}

.theme-preview span {
    font-size: 12px;
}

.theme-preview.light {
    background: #ffffff;
    color: #1f2937;
}

.theme-preview.dark {
    background: #1f2937;
    color: #ffffff;
}

.theme-option.active .theme-preview {
    border-color: #2c7a6e;
    box-shadow: 0 0 0 2px rgba(44, 122, 110, 0.2);
}

/* Security & Data Sections */
.security-section, .data-section {
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e5e7eb;
}

.security-section:last-child, .data-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.security-section h4, .data-section h4 {
    font-size: 16px;
    color: #1f2937;
    margin-bottom: 12px;
}

.security-section p, .data-section p {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 16px;
}

.data-section.warning {
    background: #fef2f2;
    padding: 20px;
    border-radius: 16px;
    border-bottom: none;
}

.warning-text {
    color: #dc2626 !important;
}

.btn-danger {
    padding: 10px 20px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 40px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-danger:hover {
    background: #dc2626;
}

.btn-primary {
    padding: 10px 20px;
    background: #2c7a6e;
    color: white;
    border: none;
    border-radius: 40px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary:hover {
    background: #1f5e54;
}

.btn-secondary {
    padding: 10px 20px;
    background: #f3f4f6;
    border: none;
    border-radius: 40px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-secondary:hover {
    background: #e5e7eb;
    color: #374151;
}

.alert {
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

.alert.error {
    background: #fef2f2;
    border-left: 3px solid #ef4444;
    color: #dc2626;
}

.alert.success {
    background: #f0fdf4;
    border-left: 3px solid #22c55e;
    color: #16a34a;
}

@media (max-width: 768px) {
    .settings-wrapper {
        flex-direction: column;
    }
    
    .settings-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
        padding: 12px;
    }
    
    .settings-nav {
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .settings-nav-btn.active {
        border-right: none;
        border-bottom: 3px solid #2c7a6e;
    }
    
    .settings-content {
        padding: 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .theme-options {
        flex-direction: column;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Tab navigation
document.querySelectorAll('.settings-nav-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.settings-nav-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));
        
        btn.classList.add('active');
        const tab = btn.dataset.tab;
        document.getElementById(`panel-${tab}`).classList.add('active');
    });
});

// Show/hide reminder days based on task reminder toggle
const taskReminderCheckbox = document.getElementById('taskReminderCheckbox');
const reminderDaysGroup = document.getElementById('reminderDaysGroup');

if (taskReminderCheckbox) {
    taskReminderCheckbox.addEventListener('change', function() {
        if (reminderDaysGroup) {
            reminderDaysGroup.style.display = this.checked ? 'block' : 'none';
        }
    });
}

// Theme preview on radio change
document.querySelectorAll('input[name="theme"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.theme-option').forEach(opt => {
            opt.classList.remove('active');
        });
        this.closest('.theme-option').classList.add('active');
        
        // Apply theme immediately
        applyTheme(this.value);
    });
});

// Password confirmation validation
const newPassword = document.getElementById('new_password');
const confirmPassword = document.getElementById('confirm_password');

function validatePassword() {
    if (newPassword && confirmPassword) {
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Password tidak cocok');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
}

if (newPassword) {
    newPassword.addEventListener('change', validatePassword);
    confirmPassword.addEventListener('keyup', validatePassword);
}

// Export data function
function exportData() {
    Swal.fire({
        title: 'Ekspor Data',
        text: 'Mengumpulkan data Anda...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch('pages/reports/export_data.php')
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.success) {
                const blob = new Blob([JSON.stringify(data.data, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `taskflow_export_${new Date().toISOString().slice(0,19)}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Data Anda berhasil diekspor',
                    customClass: { popup: 'swal-rounded' }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: data.message,
                    customClass: { popup: 'swal-rounded' }
                });
            }
        })
        .catch(error => {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Terjadi kesalahan saat mengekspor data',
                customClass: { popup: 'swal-rounded' }
            });
        });
}

// Confirm clear data
function confirmClearData(event) {
    event.preventDefault();
    const confirmInput = document.querySelector('input[name="confirm_clear"]');
    
    if (confirmInput.value !== 'DELETE MY DATA') {
        Swal.fire({
            icon: 'error',
            title: 'Konfirmasi Salah!',
            text: 'Ketik "DELETE MY DATA" untuk mengkonfirmasi',
            customClass: { popup: 'swal-rounded' }
        });
        return false;
    }
    
    Swal.fire({
        title: 'Hapus Semua Data?',
        text: 'Tindakan ini tidak dapat dibatalkan! Semua tugas, kategori, dan catatan akan hilang.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus Semua!',
        cancelButtonText: 'Batal',
        customClass: { popup: 'swal-rounded' }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('clearDataForm').submit();
        }
    });
    
    return false;
}

// Dark mode implementation
function applyTheme(theme) {
    if (theme === 'dark') {
        document.body.classList.add('dark-mode');
        // Apply dark mode styles
        let style = document.getElementById('dark-mode-styles');
        if (!style) {
            style = document.createElement('style');
            style.id = 'dark-mode-styles';
            style.textContent = `
                body.dark-mode {
                    background: #1a1a2e;
                }
                body.dark-mode .settings-container .settings-wrapper,
                body.dark-mode .settings-container .settings-sidebar,
                body.dark-mode .settings-container .settings-content,
                body.dark-mode .settings-container .settings-panel,
                body.dark-mode .settings-container .panel-header,
                body.dark-mode .settings-container .form-control,
                body.dark-mode .settings-container .theme-preview.light {
                    background: #16213e;
                    color: #e2e8f0;
                    border-color: #2d3748;
                }
                body.dark-mode .settings-container .settings-nav-btn {
                    color: #94a3b8;
                }
                body.dark-mode .settings-container .settings-nav-btn.active {
                    background: #1e293b;
                    color: #2c7a6e;
                }
                body.dark-mode .settings-container .form-control {
                    background: #0f172a;
                    border-color: #334155;
                    color: #e2e8f0;
                }
                body.dark-mode .settings-container .toggle-item {
                    border-color: #334155;
                }
                body.dark-mode .settings-container .alert.success {
                    background: #064e3b;
                    color: #34d399;
                }
                body.dark-mode .settings-container .alert.error {
                    background: #7f1d1d;
                    color: #fca5a5;
                }
                body.dark-mode .settings-container .theme-preview.dark {
                    background: #0f172a;
                    border-color: #2c7a6e;
                }
            `;
            document.head.appendChild(style);
        }
    } else {
        document.body.classList.remove('dark-mode');
        const darkStyles = document.getElementById('dark-mode-styles');
        if (darkStyles) darkStyles.remove();
    }
}

// Check for saved theme on page load
const savedTheme = '<?= $settings['theme'] ?? 'light' ?>';
applyTheme(savedTheme);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>