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

// Ambil data user
$userData = $user->getUserById($user_id);

if (!$userData) {
    header('Location: logout.php');
    exit;
}

$success = '';
$error = '';

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_profile') {
            // Update profil
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            if (empty($full_name)) {
                $error = 'Nama lengkap tidak boleh kosong!';
            } elseif (empty($email)) {
                $error = 'Email tidak boleh kosong!';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Format email tidak valid!';
            } else {
                $data = [
                    'full_name' => $full_name,
                    'email' => $email
                ];
                
                $result = $user->updateProfile($user_id, $data);
                
                if ($result['success']) {
                    $success = $result['message'];
                    // Refresh data
                    $userData = $user->getUserById($user_id);
                    $_SESSION['user_name'] = $full_name;
                    $_SESSION['user_email'] = $email;
                } else {
                    $error = $result['error'];
                }
            }
        } elseif ($_POST['action'] == 'change_password') {
            // Ganti password
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
        } elseif ($_POST['action'] == 'update_avatar') {
            // Upload avatar
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['avatar']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $file_size = $_FILES['avatar']['size'];
                
                if ($file_size > 2 * 1024 * 1024) {
                    $error = 'Ukuran file maksimal 2MB!';
                } elseif (in_array($ext, $allowed)) {
                    $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                    $upload_path = __DIR__ . '/assets/images/avatars/';
                    
                    // Buat folder jika belum ada
                    if (!file_exists($upload_path)) {
                        mkdir($upload_path, 0777, true);
                    }
                    
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path . $new_filename)) {
                        // Hapus avatar lama jika ada
                        if (!empty($userData['avatar']) && file_exists($upload_path . $userData['avatar'])) {
                            unlink($upload_path . $userData['avatar']);
                        }
                        
                        $result = $user->updateProfile($user_id, ['avatar' => $new_filename]);
                        
                        if ($result['success']) {
                            $success = 'Avatar berhasil diubah!';
                            $userData['avatar'] = $new_filename;
                        } else {
                            $error = $result['error'];
                        }
                    } else {
                        $error = 'Gagal mengupload file!';
                    }
                } else {
                    $error = 'Format file tidak didukung! Gunakan JPG, PNG, atau GIF.';
                }
            } else {
                $error = 'Pilih file avatar terlebih dahulu!';
            }
        }
    }
}

$page_title = 'Profil Saya - TaskFlow';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="profile-container">
    <div class="profile-header">
        <h1><i class="fas fa-user-circle"></i> Profil Saya</h1>
        <p>Kelola informasi akun dan pengaturan profil Anda</p>
    </div>

    <div class="profile-grid">
        <!-- Avatar Section -->
        <div class="profile-card avatar-card">
            <div class="avatar-section">
                <div class="avatar-preview">
                    <?php if (!empty($userData['avatar']) && file_exists(__DIR__ . '/assets/images/avatars/' . $userData['avatar'])): ?>
                        <img src="assets/images/avatars/<?= htmlspecialchars($userData['avatar']) ?>" alt="Avatar" id="avatarImg">
                    <?php else: ?>
                        <div class="avatar-placeholder" id="avatarPlaceholder">
                            <i class="fas fa-user-circle"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <form method="POST" enctype="multipart/form-data" id="avatarForm">
                    <input type="hidden" name="action" value="update_avatar">
                    <div class="avatar-upload">
                        <label for="avatarInput" class="btn-secondary">
                            <i class="fas fa-camera"></i> Ganti Avatar
                        </label>
                        <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display: none;">
                        <button type="submit" class="btn-primary" style="display: none;" id="uploadAvatarBtn">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                    </div>
                </form>
                <p class="avatar-hint">Format: JPG, PNG, GIF. Maks 2MB</p>
            </div>
        </div>

        <!-- Profile Info Section -->
        <div class="profile-card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle"></i> Informasi Profil</h3>
            </div>
            
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
            
            <form method="POST" class="profile-form">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label for="full_name">Nama Lengkap</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?= htmlspecialchars($userData['full_name'] ?? $userData['username']) ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-icon">
                        <i class="fas fa-at"></i>
                        <input type="text" id="username" class="form-control" 
                               value="<?= htmlspecialchars($userData['username']) ?>" disabled>
                    </div>
                    <small class="form-hint">Username tidak dapat diubah</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($userData['email']) ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Member Sejak</label>
                    <div class="input-icon">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="text" class="form-control" 
                               value="<?= date('d F Y', strtotime($userData['created_at'])) ?>" disabled>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password Section -->
        <div class="profile-card">
            <div class="card-header">
                <h3><i class="fas fa-lock"></i> Ganti Password</h3>
            </div>
            
            <form method="POST" class="profile-form">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password">Password Saat Ini</label>
                    <div class="input-icon">
                        <i class="fas fa-key"></i>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="new_password">Password Baru</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                    </div>
                    <small class="form-hint">Minimal 6 karakter</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <div class="input-icon">
                        <i class="fas fa-check-circle"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-sync-alt"></i> Ganti Password
                    </button>
                </div>
            </form>
        </div>

        <!-- Account Stats Section -->
        <div class="profile-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-line"></i> Statistik Akun</h3>
            </div>
            
            <div class="stats-list" id="accountStats">
                <div class="stat-item">
                    <span class="stat-label">Total Tugas</span>
                    <span class="stat-value" id="totalTasks">-</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Tugas Selesai</span>
                    <span class="stat-value" id="completedTasks">-</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Tingkat Penyelesaian</span>
                    <span class="stat-value" id="completionRate">-</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total Catatan</span>
                    <span class="stat-value" id="totalNotes">-</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total Kategori</span>
                    <span class="stat-value" id="totalCategories">-</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.profile-header {
    margin-bottom: 32px;
}

.profile-header h1 {
    font-size: 28px;
    color: #1f2937;
    margin-bottom: 8px;
}

.profile-header h1 i {
    color: #2c7a6e;
    margin-right: 12px;
}

.profile-header p {
    color: #6b7280;
    font-size: 14px;
}

.profile-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

.profile-card {
    background: white;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.avatar-card {
    grid-column: span 2;
    text-align: center;
}

.avatar-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}

.avatar-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 80px;
    color: #9ca3af;
}

.avatar-upload {
    display: flex;
    gap: 12px;
}

.avatar-hint {
    font-size: 12px;
    color: #9ca3af;
    margin: 0;
}

.card-header {
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e5e7eb;
}

.card-header h3 {
    font-size: 18px;
    color: #1f2937;
    margin: 0;
}

.card-header h3 i {
    color: #2c7a6e;
    margin-right: 8px;
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

.input-icon {
    position: relative;
}

.input-icon i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 16px;
}

.input-icon .form-control {
    padding-left: 42px;
}

.form-control {
    width: 100%;
    padding: 12px 14px;
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

.form-control:disabled {
    background: #f9fafb;
    cursor: not-allowed;
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
    transform: translateY(-1px);
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

.stats-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
}

.stat-value {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

@media (max-width: 768px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
    
    .avatar-card {
        grid-column: span 1;
    }
    
    .profile-container {
        padding: 16px;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Avatar upload preview
const avatarInput = document.getElementById('avatarInput');
const uploadBtn = document.getElementById('uploadAvatarBtn');
const avatarForm = document.getElementById('avatarForm');
const avatarImg = document.getElementById('avatarImg');
const avatarPlaceholder = document.getElementById('avatarPlaceholder');

if (avatarInput) {
    avatarInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (avatarImg) {
                    avatarImg.src = e.target.result;
                } else if (avatarPlaceholder) {
                    avatarPlaceholder.style.display = 'none';
                    const newImg = document.createElement('img');
                    newImg.id = 'avatarImg';
                    newImg.src = e.target.result;
                    newImg.style.width = '100%';
                    newImg.style.height = '100%';
                    newImg.style.objectFit = 'cover';
                    document.querySelector('.avatar-preview').appendChild(newImg);
                }
                uploadBtn.style.display = 'inline-flex';
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// Load account statistics
function loadAccountStats() {
    fetch('pages/reports/get_report_data.php?start_date=2020-01-01&end_date=<?= date('Y-m-d') ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalTasks').textContent = data.stats.total || 0;
                document.getElementById('completedTasks').textContent = data.stats.completed || 0;
                const total = data.stats.total || 1;
                const rate = Math.round((data.stats.completed / total) * 100);
                document.getElementById('completionRate').textContent = rate + '%';
            }
        })
        .catch(error => console.error('Error:', error));
    
    // Load categories count
    fetch('pages/categories/get_categories_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalCategories').textContent = data.count || 0;
            }
        })
        .catch(error => console.error('Error:', error));
    
    // Load notes count
    fetch('pages/notes/get_notes_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalNotes').textContent = data.count || 0;
            }
        })
        .catch(error => console.error('Error:', error));
}

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

// Load stats on page load
loadAccountStats();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>