<?php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'classes/Database.php';

// Cek apakah sudah login, jika ya redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$step = $_GET['step'] ?? 'request'; // step: request, verify, reset
$error = '';
$success = '';
$email = '';
$security_question = '';
$user_id = '';
$username = '';

// Step 1: Request reset (masukkan email)
if ($step == 'request' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Yuk, isi dulu emailnya!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format emailnya kurang tepat, coba cek lagi';
    } else {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, username, full_name, security_question FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Cek apakah user punya security question
            if (empty($user['security_question'])) {
                $error = 'Akun ini belum memiliki pertanyaan keamanan. Silakan hubungi admin.';
            } else {
                // Simpan data di session untuk sementara
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_username'] = $user['username'];
                
                // Redirect ke step verifikasi
                header('Location: forgot-password.php?step=verify');
                exit;
            }
        } else {
            $error = 'Email nggak ditemukan nih. Coba cek lagi atau <a href="register.php">daftar dulu</a> ya!';
        }
    }
}

// Step 2: Verifikasi pertanyaan keamanan
if ($step == 'verify') {
    // Ambil security question dari database
    if (isset($_SESSION['reset_email'])) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, username, security_question, security_answer FROM users WHERE email = ?");
        $stmt->execute([$_SESSION['reset_email']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $security_question = $user['security_question'];
            $user_id = $user['id'];
            $username = $user['username'];
            $stored_answer = $user['security_answer'];
        } else {
            $error = 'Sesi habis. Yuk mulai lagi dari awal!';
            $step = 'request';
        }
    } else {
        $error = 'Sesi habis. Yuk mulai lagi dari awal!';
        $step = 'request';
    }
    
    // Proses verifikasi jawaban
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 'verify') {
        $security_answer = trim($_POST['security_answer'] ?? '');
        
        if (empty($security_answer)) {
            $error = 'Jawab dulu ya pertanyaan keamanannya!';
        } else {
            // Normalisasi jawaban
            $normalized_answer = strtolower(trim($security_answer));
            $is_valid = false;
            $used_method = '';
            
            // Method 1: Coba dengan password_verify (untuk format hash baru)
            if (password_verify($normalized_answer, $stored_answer)) {
                $is_valid = true;
                $used_method = 'password_hash';
            }
            // Method 2: Coba dengan MD5 (untuk format lama)
            elseif (md5($normalized_answer) === $stored_answer) {
                $is_valid = true;
                $used_method = 'md5';
                
                // Upgrade ke format password_hash yang lebih aman
                $new_hash = password_hash($normalized_answer, PASSWORD_DEFAULT);
                $updateStmt = $db->prepare("UPDATE users SET security_answer = ? WHERE id = ?");
                $updateStmt->execute([$new_hash, $user_id]);
            }
            
            if ($is_valid) {
                // Jawaban benar, langsung ke step reset password
                $_SESSION['reset_verified'] = true;
                header('Location: forgot-password.php?step=reset');
                exit;
            } else {
                $error = 'Waduh, jawabannya kurang tepat. Coba ingat-ingat lagi ya!';
                // Untuk debugging (hapus jika sudah production)
                if (isset($_GET['debug'])) {
                    $error .= "<br><small>Debug: MD5=" . md5($normalized_answer) . " | Stored=" . $stored_answer . "</small>";
                }
            }
        }
    }
}

// Step 3: Reset password
if ($step == 'reset') {
    // Cek apakah sudah terverifikasi
    if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
        $error = 'Akses tidak valid. Silakan mulai dari awal!';
        $step = 'request';
    } elseif (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_user_id'])) {
        $error = 'Sesi habis. Silakan mulai dari awal!';
        $step = 'request';
    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($new_password) || empty($confirm_password)) {
            $error = 'Isi dulu password barunya!';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password minimal 6 karakter biar lebih aman';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Waduh, password dan konfirmasinya nggak sama nih';
        } else {
            // Update password
            $db = Database::getInstance()->getConnection();
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($stmt->execute([$hashed_password, $_SESSION['reset_user_id']])) {
                $success = 'Yeay! Password berhasil diubah. Yuk langsung login! 🎉';
                
                // Hapus session reset
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_username']);
                unset($_SESSION['reset_verified']);
                
                // Redirect setelah 3 detik
                echo '<meta http-equiv="refresh" content="3;url=login.php">';
            } else {
                $error = 'Maaf, ada kendala teknis. Coba lagi ya!';
            }
        }
    }
}

$page_title = 'Lupa Password - Task Management';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= APP_NAME ?> - Lupa Password | Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(145deg, #0f2b3d 0%, #1a4a5f 50%, #2c7a6e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        /* Decorative glass elements */
        .bg-circle {
            position: fixed;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(60px);
            pointer-events: none;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .circle-1 {
            width: 400px;
            height: 400px;
            top: -200px;
            left: -200px;
            background: rgba(44, 122, 110, 0.3);
        }

        .circle-2 {
            width: 500px;
            height: 500px;
            bottom: -250px;
            right: -250px;
            background: rgba(249, 115, 22, 0.15);
        }

        .circle-3 {
            width: 300px;
            height: 300px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(56, 189, 248, 0.1);
        }

        /* Main Container */
        .forgot-container {
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 1;
        }

        .forgot-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 48px;
            padding: 48px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .forgot-card:hover {
            transform: translateY(-4px);
        }

        /* Header */
        .header-section {
            text-align: center;
            margin-bottom: 32px;
        }

        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .step {
            width: 40px;
            height: 40px;
            background: #f3f4f6;
            border-radius: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #9ca3af;
            transition: all 0.3s ease;
        }

        .step.active {
            background: linear-gradient(135deg, #2c7a6e 0%, #1a5f5a 100%);
            color: white;
            box-shadow: 0 4px 10px rgba(44, 122, 110, 0.3);
        }

        .step.completed {
            background: #10b981;
            color: white;
        }

        .step-line {
            width: 40px;
            height: 2px;
            background: #e5e7eb;
        }

        .step-line.completed {
            background: #10b981;
        }

        .icon-badge {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #2c7a6e 0%, #1a5f5a 100%);
            border-radius: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px -5px rgba(44, 122, 110, 0.3);
        }

        .icon-badge i {
            font-size: 32px;
            color: white;
        }

        .forgot-card h2 {
            font-size: 28px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 14px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 24px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            color: #9ca3af;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            font-size: 14px;
            border: 1.5px solid #e5e7eb;
            border-radius: 20px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background: #f9fafb;
            outline: none;
        }

        .form-input:focus {
            border-color: #2c7a6e;
            background: white;
            box-shadow: 0 0 0 3px rgba(44, 122, 110, 0.1);
        }

        .form-input:focus + .input-icon {
            color: #2c7a6e;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            font-size: 16px;
            padding: 0;
        }

        .password-toggle:hover {
            color: #2c7a6e;
        }

        /* Password Strength */
        .strength-container {
            margin-top: 8px;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .strength-bar {
            flex: 1;
            height: 4px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 4px;
        }

        .strength-text {
            font-size: 11px;
            font-weight: 500;
            min-width: 100px;
        }

        /* Match Indicator */
        .match-indicator {
            font-size: 12px;
            margin-top: 6px;
            margin-left: 48px;
        }

        /* Security Question Box */
        .security-box {
            background: #f0fdf4;
            border: 1px solid #d1fae5;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 24px;
            text-align: center;
        }

        .security-box .question-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .security-box .question-text {
            font-weight: 700;
            font-size: 16px;
            color: #065f46;
            margin-bottom: 8px;
        }

        .security-box .question-hint {
            font-size: 12px;
            color: #6b7280;
        }

        /* Button */
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2c7a6e 0%, #1a5f5a 100%);
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(44, 122, 110, 0.4);
        }

        /* Back Link */
        .back-link {
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }

        .back-link a {
            color: #2c7a6e;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #1a5f5a;
            text-decoration: underline;
        }

        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background: #d1fae5;
            border-left: 3px solid #10b981;
            color: #059669;
        }

        .alert-error {
            background: #fef2f2;
            border-left: 3px solid #ef4444;
            color: #dc2626;
        }

        .alert-error a {
            color: #dc2626;
            text-decoration: underline;
        }

        /* Info Box */
        .info-box {
            background: #eff6ff;
            border-radius: 16px;
            padding: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 12px;
            color: #1e40af;
        }

        .info-box i {
            font-size: 16px;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0f2b3d 0%, #2c7a6e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s ease;
        }

        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .loading-card {
            text-align: center;
            animation: bounceIn 0.5s ease;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 48px;
            border-radius: 48px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }
            70% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .task-spinner {
            width: 100px;
            height: 100px;
            margin: 0 auto 24px;
            position: relative;
        }

        .task-spinner i {
            position: absolute;
            font-size: 40px;
            color: white;
            animation: floatTask 1.2s ease-in-out infinite;
        }

        .task-spinner i:nth-child(1) {
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            animation-delay: 0s;
        }

        .task-spinner i:nth-child(2) {
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            animation-delay: 0.3s;
        }

        .task-spinner i:nth-child(3) {
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            animation-delay: 0.6s;
        }

        .task-spinner i:nth-child(4) {
            top: 50%;
            right: 0;
            transform: translateY(-50%);
            animation-delay: 0.9s;
        }

        @keyframes floatTask {
            0%, 100% {
                transform: translateY(-50%) scale(1);
                opacity: 1;
            }
            50% {
                transform: translateY(-50%) scale(1.2);
                opacity: 0.7;
            }
        }

        .success-circle {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            animation: scaleSuccess 0.5s ease;
        }

        @keyframes scaleSuccess {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-circle i {
            font-size: 50px;
            color: #2c7a6e;
        }

        .loading-card h3 {
            color: white;
            font-size: 24px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .loading-card p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }

        .loading-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
        }

        .loading-dots span {
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            animation: dotPulse 1.2s ease-in-out infinite;
        }

        .loading-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .loading-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes dotPulse {
            0%, 100% {
                transform: scale(0.5);
                opacity: 0.5;
            }
            50% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Shake animation */
        .shake {
            animation: shakeAnim 0.3s ease-in-out;
        }

        @keyframes shakeAnim {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .forgot-card {
                padding: 32px 24px;
            }
            
            .forgot-card h2 {
                font-size: 24px;
            }
            
            .step {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }
            
            .step-line {
                width: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-circle circle-1"></div>
    <div class="bg-circle circle-2"></div>
    <div class="bg-circle circle-3"></div>

    <div class="forgot-container">
        <div class="forgot-card">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?= $step == 'request' ? 'active' : ($step == 'verify' || $step == 'reset' ? 'completed' : '') ?>">
                    <?= ($step == 'verify' || $step == 'reset') ? '<i class="fas fa-check"></i>' : '1' ?>
                </div>
                <div class="step-line <?= ($step == 'verify' || $step == 'reset') ? 'completed' : '' ?>"></div>
                <div class="step <?= $step == 'verify' ? 'active' : ($step == 'reset' ? 'completed' : '') ?>">
                    <?= $step == 'reset' ? '<i class="fas fa-check"></i>' : '2' ?>
                </div>
                <div class="step-line <?= $step == 'reset' ? 'completed' : '' ?>"></div>
                <div class="step <?= $step == 'reset' ? 'active' : '' ?>">
                    3
                </div>
            </div>

            <div class="header-section">
                <div class="icon-badge">
                    <?php if ($step == 'request'): ?>
                        <i class="fas fa-key"></i>
                    <?php elseif ($step == 'verify'): ?>
                        <i class="fas fa-shield-alt"></i>
                    <?php else: ?>
                        <i class="fas fa-lock"></i>
                    <?php endif; ?>
                </div>
                
                <?php if ($step == 'request'): ?>
                    <h2>Lupa Password? 🔐</h2>
                    <p class="subtitle">Tenang, kami bantu reset passwordmu</p>
                <?php elseif ($step == 'verify'): ?>
                    <h2>Verifikasi Keamanan 🛡️</h2>
                    <p class="subtitle">Jawab pertanyaan ini untuk melanjutkan</p>
                <?php else: ?>
                    <h2>Buat Password Baru ✨</h2>
                    <p class="subtitle">Buat password yang kuat dan mudah diingat</p>
                <?php endif; ?>
            </div>

            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= $success ?></span>
                </div>
            <?php endif; ?>

            <!-- STEP 1: Request Reset -->
            <?php if ($step == 'request'): ?>
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <span>Masukkan email yang terdaftar, nanti kami akan verifikasi melalui pertanyaan keamanan</span>
                </div>
                
                <form method="POST" id="requestForm">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" 
                                   name="email" 
                                   class="form-input" 
                                   placeholder="Email yang terdaftar"
                                   value="<?= htmlspecialchars($email) ?>"
                                   required 
                                   autofocus>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Kirim
                    </button>
                </form>

                <div class="back-link">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Kembali ke Login
                    </a>
                </div>
            <?php endif; ?>

            <!-- STEP 2: Verify Security Question -->
            <?php if ($step == 'verify'): ?>
                <div class="security-box">
                    <div class="question-icon">❓</div>
                    <div class="question-text"><?= htmlspecialchars($security_question) ?></div>
                    <div class="question-hint">Ingat-ingat ya jawaban yang kamu daftarkan dulu</div>
                </div>

                <form method="POST" id="verifyForm">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-key input-icon"></i>
                            <input type="text" 
                                   name="security_answer" 
                                   id="securityAnswer"
                                   class="form-input" 
                                   placeholder="Jawabanmu"
                                   required 
                                   autofocus>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-check-circle"></i> Verifikasi
                    </button>
                </form>

                <div class="back-link">
                    <a href="forgot-password.php?step=request">
                        <i class="fas fa-arrow-left"></i> Gunakan email lain
                    </a>
                </div>
            <?php endif; ?>

            <!-- STEP 3: Reset Password -->
            <?php if ($step == 'reset' && !$success): ?>
                <form method="POST" id="resetForm">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" 
                                   name="new_password" 
                                   id="newPassword"
                                   class="form-input" 
                                   placeholder="Password baru"
                                   required>
                            <button type="button" class="password-toggle" id="toggleNewPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="strength-container">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <span class="strength-text" id="strengthText">Kekuatan</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-check-circle input-icon"></i>
                            <input type="password" 
                                   name="confirm_password" 
                                   id="confirmPassword"
                                   class="form-input" 
                                   placeholder="Konfirmasi password baru"
                                   required>
                            <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="match-indicator" id="matchIndicator"></div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-save"></i> Simpan Password Baru
                    </button>
                </form>

                <div class="back-link">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Kembali ke Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-card">
            <div id="spinnerArea">
                <div class="task-spinner">
                    <i class="fas fa-tasks"></i>
                    <i class="fas fa-check-circle"></i>
                    <i class="fas fa-clock"></i>
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div id="successArea" style="display: none;">
                <div class="success-circle">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <h3 id="loadingTitle">Memproses...</h3>
            <p id="loadingMessage">Tunggu sebentar ya ✨</p>
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>

    <script>
        // Toggle Password untuk reset step
        const toggleNewPassword = document.getElementById('toggleNewPassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const newPasswordInput = document.getElementById('newPassword');
        const confirmInput = document.getElementById('confirmPassword');
        
        if (toggleNewPassword && newPasswordInput) {
            toggleNewPassword.addEventListener('click', function() {
                const type = newPasswordInput.type === 'password' ? 'text' : 'password';
                newPasswordInput.type = type;
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }
        
        if (toggleConfirmPassword && confirmInput) {
            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmInput.type === 'password' ? 'text' : 'password';
                confirmInput.type = type;
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }

        // Password Strength Checker (untuk reset step)
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        
        function checkStrength(password) {
            let score = 0;
            if (password.length >= 6) score++;
            if (password.length >= 10) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[$@#&!]/.test(password)) score++;
            
            const levels = {
                0: { width: '0%', text: '🤔 Minimal 6 karakter', color: '#9ca3af' },
                1: { width: '20%', text: '😅 Lemah', color: '#ef4444' },
                2: { width: '40%', text: '🙂 Lumayan', color: '#f59e0b' },
                3: { width: '60%', text: '😊 Baik', color: '#10b981' },
                4: { width: '80%', text: '💪 Kuat', color: '#10b981' },
                5: { width: '100%', text: '🔥 Sangat Kuat!', color: '#10b981' }
            };
            
            let index = Math.min(Math.floor(score / 1.2), 5);
            const level = levels[index];
            if (strengthFill) {
                strengthFill.style.width = level.width;
                strengthFill.style.backgroundColor = level.color;
            }
            if (strengthText) {
                strengthText.textContent = level.text;
                strengthText.style.color = level.color;
            }
            
            return index >= 3;
        }
        
        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', function() {
                checkStrength(this.value);
                checkMatch();
            });
        }

        // Password Match Checker (untuk reset step)
        const matchIndicator = document.getElementById('matchIndicator');
        
        function checkMatch() {
            if (!newPasswordInput || !confirmInput) return;
            
            const password = newPasswordInput.value;
            const confirm = confirmInput.value;
            
            if (confirm.length === 0) {
                if (matchIndicator) matchIndicator.innerHTML = '';
                return;
            }
            
            if (matchIndicator) {
                if (password === confirm) {
                    matchIndicator.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> <span style="color: #10b981;">Yess, sandinya cocok!</span>';
                } else {
                    matchIndicator.innerHTML = '<i class="fas fa-times-circle" style="color: #ef4444;"></i> <span style="color: #ef4444;">Waduh, sandinya nggak sama</span>';
                }
            }
        }
        
        if (confirmInput) {
            confirmInput.addEventListener('input', checkMatch);
        }

        // Form Submit with Loading Animation
        const form = document.querySelector('form');
        const submitBtn = document.getElementById('submitBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const spinnerArea = document.getElementById('spinnerArea');
        const successArea = document.getElementById('successArea');
        const loadingTitle = document.getElementById('loadingTitle');
        const loadingMessage = document.getElementById('loadingMessage');
        
        let formSubmitted = false;
        
        if (form) {
            form.addEventListener('submit', function(e) {
                <?php if ($step == 'reset'): ?>
                // Validasi khusus untuk reset password
                const newPassword = newPasswordInput ? newPasswordInput.value : '';
                const confirm = confirmInput ? confirmInput.value : '';
                
                if (newPassword !== confirm) {
                    e.preventDefault();
                    if (confirmInput) {
                        confirmInput.classList.add('shake');
                        setTimeout(() => confirmInput.classList.remove('shake'), 300);
                    }
                    return;
                }
                
                if (newPassword.length < 6) {
                    e.preventDefault();
                    if (newPasswordInput) {
                        newPasswordInput.classList.add('shake');
                        setTimeout(() => newPasswordInput.classList.remove('shake'), 300);
                    }
                    return;
                }
                <?php endif; ?>
                
                if (formSubmitted) {
                    e.preventDefault();
                    return;
                }
                
                formSubmitted = true;
                
                // Show loading animation
                if (loadingOverlay) loadingOverlay.classList.add('active');
                
                // Update loading message berdasarkan step
                <?php if ($step == 'request'): ?>
                setTimeout(() => {
                    if (loadingTitle) loadingTitle.textContent = 'Memeriksa email...';
                    if (loadingMessage) loadingMessage.textContent = 'Tunggu sebentar ya';
                }, 500);
                <?php elseif ($step == 'verify'): ?>
                setTimeout(() => {
                    if (loadingTitle) loadingTitle.textContent = 'Memverifikasi jawaban...';
                    if (loadingMessage) loadingMessage.textContent = 'Kami cek dulu ya';
                }, 500);
                <?php else: ?>
                setTimeout(() => {
                    if (loadingTitle) loadingTitle.textContent = 'Mengubah password...';
                    if (loadingMessage) loadingMessage.textContent = 'Sedikit lagi ya';
                }, 500);
                <?php endif; ?>
                
                setTimeout(() => {
                    if (spinnerArea) spinnerArea.style.display = 'none';
                    if (successArea) successArea.style.display = 'block';
                    if (loadingTitle) loadingTitle.textContent = 'Berhasil! 🎉';
                    if (loadingMessage) loadingMessage.textContent = 'Terima kasih sudah sabar menunggu';
                }, 1500);
                
                // Submit form normally
                setTimeout(() => {
                    form.submit();
                }, 2000);
            });
        }
    </script>
</body>
</html>