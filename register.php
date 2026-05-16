<?php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'classes/Database.php';
require_once 'classes/User.php'; // Menambahkan User class jika diperlukan

// Cek apakah sudah login, jika ya redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']) ? true : false;
    $security_question = $_POST['security_question'] ?? '';
    $security_answer = trim($_POST['security_answer'] ?? '');
    
    // Validasi
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Yuk lengkapi dulu semua datanya!';
    } elseif (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter ya';
    } elseif (strlen($full_name) < 3) {
        $error = 'Nama lengkap minimal 3 karakter';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format emailnya kurang tepat, coba cek lagi';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal 6 karakter biar lebih aman';
    } elseif ($password !== $confirm_password) {
        $error = 'Waduh, kata sandi dan konfirmasinya nggak sama nih';
    } elseif (!$terms) {
        $error = 'Setuju dulu ya sama syarat & ketentuan kami';
    } elseif (empty($security_question)) {
        $error = 'Pilih pertanyaan keamanan dulu ya';
    } elseif (empty($security_answer)) {
        $error = 'Isi jawaban untuk pertanyaan keamanan';
    } else {
        $db = Database::getInstance()->getConnection();
        
        // Cek apakah email atau username sudah terdaftar
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->fetch()) {
            $error = 'Email atau username sudah terdaftar. Coba pakai yang lain!';
        } else {
            // Hash password dan security answer
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $hashed_security_answer = password_hash(strtolower($security_answer), PASSWORD_DEFAULT);
            
            // Insert user baru
            $stmt = $db->prepare("INSERT INTO users (username, full_name, email, password, security_question, security_answer, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            
            if ($stmt->execute([$username, $full_name, $email, $hashed_password, $security_question, $hashed_security_answer])) {
                $user_id = $db->lastInsertId();
                
                // Buat default categories untuk user baru
                $default_categories = [
                    ['Pekerjaan', '#2c7a6e', 'fa-briefcase'],
                    ['Pribadi', '#f97316', 'fa-user'],
                    ['Belajar', '#3b82f6', 'fa-graduation-cap'],
                    ['Olahraga', '#ef4444', 'fa-heartbeat']
                ];
                
                $catStmt = $db->prepare("INSERT INTO categories (user_id, name, color, icon) VALUES (?, ?, ?, ?)");
                foreach ($default_categories as $cat) {
                    $catStmt->execute([$user_id, $cat[0], $cat[1], $cat[2]]);
                }
                
                // Buat default settings
                $settingStmt = $db->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
                $settingStmt->execute([$user_id]);
                
                $success = 'Yeay! Akunmu berhasil dibuat. Yuk langsung login! 🎉';
                // Clear form
                $username = $full_name = $email = '';
            } else {
                $error = 'Maaf, ada kendala teknis. Coba lagi ya!';
            }
        }
    }
}

$page_title = 'Daftar - Task Management';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= APP_NAME ?> - Mulai Kelola Tugasmu | Daftar</title>
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

        /* Decorative glass elements - Sama dengan login */
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

        /* Main Container - Glassmorphism (sama dengan login) */
        .register-container {
            width: 100%;
            max-width: 1000px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.2);
            display: flex;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        /* Left Side - Branding dengan tema Teal (sama dengan login) */
        .brand-side {
            flex: 1;
            min-width: 280px;
            background: linear-gradient(135deg, #1a5f5a 0%, #2c7a6e 50%, #0f2b3d 100%);
            padding: 48px 32px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .brand-side::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .logo-area {
            margin-bottom: 32px;
            position: relative;
        }

        .logo-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo-icon i {
            font-size: 36px;
            color: #fbbf24;
        }

        .brand-side h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .tagline {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 32px;
            line-height: 1.5;
        }

        .features {
            list-style: none;
            margin-bottom: 40px;
        }

        .features li {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .features li i {
            width: 20px;
            font-size: 14px;
            opacity: 0.9;
            color: #fbbf24;
        }

        .btn-login-side {
            display: inline-block;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1.5px solid rgba(255, 255, 255, 0.3);
            border-radius: 40px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-login-side:hover {
            background: white;
            color: #1a5f5a;
            transform: translateY(-2px);
            border-color: transparent;
        }

        /* Right Side - Form */
        .form-side {
            flex: 1;
            min-width: 280px;
            padding: 48px 40px;
            background: white;
            max-height: 80vh;
            overflow-y: auto;
        }

        /* Custom scrollbar untuk form side */
        .form-side::-webkit-scrollbar {
            width: 6px;
        }

        .form-side::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .form-side::-webkit-scrollbar-thumb {
            background: #2c7a6e;
            border-radius: 10px;
        }

        .form-header {
            margin-bottom: 32px;
        }

        .form-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: #0f2b3d;
            margin-bottom: 8px;
        }

        .form-header p {
            color: #6b7280;
            font-size: 14px;
        }

        /* Form Styles (sama dengan login) */
        .input-group {
            margin-bottom: 24px;
        }

        .input-label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
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
            transition: color 0.3s ease;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 14px 16px 14px 48px;
            font-size: 14px;
            border: 1.5px solid #e5e7eb;
            border-radius: 16px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            outline: none;
            background: #f9fafb;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2c7a6e;
            background: white;
            box-shadow: 0 0 0 3px rgba(44, 122, 110, 0.1);
        }

        .form-control:focus + .input-icon,
        .form-select:focus + .input-icon {
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
            transition: color 0.3s ease;
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

        .info-hint {
            font-size: 11px;
            color: #6b7280;
            margin-top: 6px;
            margin-left: 48px;
        }

        .info-hint i {
            margin-right: 4px;
            font-size: 10px;
        }

        /* Terms */
        .terms-group {
            margin-bottom: 24px;
        }

        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            font-size: 13px;
            color: #6b7280;
            line-height: 1.4;
        }

        .checkbox-label input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #2c7a6e;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .checkbox-label a {
            color: #f97316;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .checkbox-label a:hover {
            color: #ea580c;
            text-decoration: underline;
        }

        .btn-register {
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
            margin-bottom: 24px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(44, 122, 110, 0.4);
            background: linear-gradient(135deg, #1a5f5a 0%, #0f4a45 100%);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .divider {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: calc(50% - 60px);
            height: 1px;
            background: #e5e7eb;
        }

        .divider::before {
            left: 0;
        }

        .divider::after {
            right: 0;
        }

        .divider span {
            background: white;
            padding: 0 16px;
            color: #9ca3af;
            font-size: 12px;
        }

        .login-mobile {
            display: none;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }

        .login-mobile a {
            color: #f97316;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-mobile a:hover {
            color: #ea580c;
        }

        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 16px;
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

        /* Loading Overlay (sama dengan login) */
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
        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
                border-radius: 24px;
            }

            .brand-side {
                padding: 32px 24px;
                text-align: center;
            }

            .logo-icon {
                margin: 0 auto 20px;
            }

            .features {
                text-align: left;
                max-width: 260px;
                margin: 0 auto 28px;
            }

            .btn-login-side {
                display: none;
            }

            .login-mobile {
                display: block;
            }

            .form-side {
                padding: 32px 24px;
                max-height: none;
                overflow-y: visible;
            }

            .form-header h2 {
                font-size: 24px;
            }
            
            .loading-card {
                padding: 32px 24px;
                margin: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-circle circle-1"></div>
    <div class="bg-circle circle-2"></div>
    <div class="bg-circle circle-3"></div>

    <div class="register-container">
        <!-- Left Side - Branding (sama dengan login) -->
        <div class="brand-side">
            <div class="logo-area">
                <div class="logo-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <h1>TaskFlow</h1>
                <p class="tagline">Kelola tugasmu lebih mudah,<br>produktivitas meningkat!</p>
            </div>
            <ul class="features">
                <li><i class="fas fa-check-circle"></i> <span>Buat & kelola tugas dengan mudah</span></li>
                <li><i class="fas fa-tag"></i> <span>Kategorikan tugas sesuai prioritas</span></li>
                <li><i class="fas fa-chart-line"></i> <span>Pantau progres tugasmu</span></li>
                <li><i class="fas fa-bell"></i> <span>Pengingat deadline otomatis</span></li>
                <li><i class="fas fa-mobile-alt"></i> <span>Akses di mana saja, kapan saja</span></li>
            </ul>
            <a href="login.php" class="btn-login-side">Masuk ke Akun →</a>
        </div>

        <!-- Right Side - Register Form -->
        <div class="form-side">
            <div class="form-header">
                <h2>Mulai Kelola Tugasmu! 🚀</h2>
                <p>Daftar gratis, langsung bisa pakai ✨</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <div class="input-group">
                    <label class="input-label">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-at input-icon"></i>
                        <input type="text" 
                               name="username" 
                               class="form-control" 
                               placeholder="contoh: johndoe123"
                               value="<?= htmlspecialchars($username ?? '') ?>"
                               required 
                               autofocus>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label">Nama Lengkap</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" 
                               name="full_name" 
                               class="form-control" 
                               placeholder="Contoh: John Doe"
                               value="<?= htmlspecialchars($full_name ?? '') ?>"
                               required>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label">Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" 
                               name="email" 
                               id="emailInput"
                               class="form-control" 
                               placeholder="contoh@email.com"
                               value="<?= htmlspecialchars($email ?? '') ?>"
                               required>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label">Kata Sandi</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               name="password" 
                               id="password"
                               class="form-control" 
                               placeholder="Minimal 6 karakter"
                               required>
                        <button type="button" class="password-toggle" id="togglePassword">
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

                <div class="input-group">
                    <label class="input-label">Konfirmasi Kata Sandi</label>
                    <div class="input-wrapper">
                        <i class="fas fa-check-circle input-icon"></i>
                        <input type="password" 
                               name="confirm_password" 
                               id="confirmPassword"
                               class="form-control" 
                               placeholder="Ketik ulang kata sandi"
                               required>
                        <button type="button" class="password-toggle" id="toggleConfirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="match-indicator" id="matchIndicator"></div>
                </div>

                <!-- Pertanyaan Keamanan -->
                <div class="input-group">
                    <label class="input-label">Pertanyaan Keamanan</label>
                    <div class="input-wrapper">
                        <i class="fas fa-question-circle input-icon"></i>
                        <select name="security_question" class="form-control" required>
                            <option value="">Pilih pertanyaan keamanan</option>
                            <option value="Apa nama hewan peliharaan pertama Anda?" <?= (isset($_POST['security_question']) && $_POST['security_question'] == 'Apa nama hewan peliharaan pertama Anda?') ? 'selected' : '' ?>>🐾 Nama hewan peliharaan pertamaku?</option>
                            <option value="Apa nama sekolah dasar Anda?" <?= (isset($_POST['security_question']) && $_POST['security_question'] == 'Apa nama sekolah dasar Anda?') ? 'selected' : '' ?>>🏫 Nama SD-ku dulu?</option>
                            <option value="Siapa nama pahlawan favorit Anda?" <?= (isset($_POST['security_question']) && $_POST['security_question'] == 'Siapa nama pahlawan favorit Anda?') ? 'selected' : '' ?>>🦸 Pahlawan favoritku?</option>
                            <option value="Apa makanan favorit Anda?" <?= (isset($_POST['security_question']) && $_POST['security_question'] == 'Apa makanan favorit Anda?') ? 'selected' : '' ?>>🍕 Makanan favoritku?</option>
                            <option value="Apa kota kelahiran Anda?" <?= (isset($_POST['security_question']) && $_POST['security_question'] == 'Apa kota kelahiran Anda?') ? 'selected' : '' ?>>🏙️ Kota kelahiranku?</option>
                            <option value="Apa nama ibu kandung Anda?" <?= (isset($_POST['security_question']) && $_POST['security_question'] == 'Apa nama ibu kandung Anda?') ? 'selected' : '' ?>>👩 Nama ibuku?</option>
                        </select>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label">Jawaban</label>
                    <div class="input-wrapper">
                        <i class="fas fa-key input-icon"></i>
                        <input type="text" 
                               name="security_answer" 
                               id="securityAnswer"
                               class="form-control" 
                               placeholder="Jawaban pertanyaan keamanan"
                               value="<?= htmlspecialchars($_POST['security_answer'] ?? '') ?>"
                               required>
                    </div>
                    <div class="info-hint">
                        <i class="fas fa-info-circle"></i> Jawaban dipakai kalau lupa password
                    </div>
                </div>

                <div class="terms-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" <?= isset($_POST['terms']) ? 'checked' : '' ?> required>
                        <span>Aku setuju dengan <a href="terms.php">Syarat & Ketentuan</a> dan <a href="privacy.php">Kebijakan Privasi</a></span>
                    </label>
                </div>

                <button type="submit" class="btn-register" id="registerBtn">
                    <i class="fas fa-user-plus"></i> Daftar Sekarang
                </button>

                <div class="divider">
                    <span>atau</span>
                </div>

                <div class="login-mobile">
                    Sudah punya akun? <a href="login.php">Masuk sini yuk</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Modern Loading Animation (sama dengan login) -->
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
            <h3 id="loadingTitle">Mendaftarkan Akun...</h3>
            <p id="loadingMessage">Tunggu sebentar ya, lagi kami proses ✨</p>
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>

    <script>
        // Toggle Password
        const togglePassword = document.getElementById('togglePassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirmPassword');
        
        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }
        
        if (toggleConfirmPassword) {
            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmInput.type === 'password' ? 'text' : 'password';
                confirmInput.type = type;
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }

        // Password Strength Checker
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
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                checkStrength(this.value);
                checkMatch();
            });
        }

        // Password Match Checker
        const matchIndicator = document.getElementById('matchIndicator');
        
        function checkMatch() {
            if (!passwordInput || !confirmInput) return;
            
            const password = passwordInput.value;
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

        // Email validation realtime
        const emailInput = document.getElementById('emailInput');
        if (emailInput) {
            emailInput.addEventListener('input', function() {
                const email = this.value;
                const regex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
                if (email.length > 0 && !regex.test(email)) {
                    this.style.borderColor = '#ef4444';
                } else {
                    this.style.borderColor = '#e5e7eb';
                }
            });
        }

        // Form Submit with Loading Animation
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const spinnerArea = document.getElementById('spinnerArea');
        const successArea = document.getElementById('successArea');
        const loadingTitle = document.getElementById('loadingTitle');
        const loadingMessage = document.getElementById('loadingMessage');
        
        let formSubmitted = false;
        
        if (registerForm) {
            registerForm.addEventListener('submit', function(e) {
                // Validasi tambahan sebelum submit
                const password = passwordInput ? passwordInput.value : '';
                const confirm = confirmInput ? confirmInput.value : '';
                const terms = document.querySelector('input[name="terms"]');
                const termsChecked = terms ? terms.checked : false;
                
                if (password !== confirm) {
                    e.preventDefault();
                    if (confirmInput) {
                        confirmInput.classList.add('shake');
                        setTimeout(() => confirmInput.classList.remove('shake'), 300);
                    }
                    return;
                }
                
                if (password.length < 6) {
                    e.preventDefault();
                    if (passwordInput) {
                        passwordInput.classList.add('shake');
                        setTimeout(() => passwordInput.classList.remove('shake'), 300);
                    }
                    return;
                }
                
                if (!termsChecked) {
                    e.preventDefault();
                    return;
                }
                
                if (formSubmitted) {
                    e.preventDefault();
                    return;
                }
                
                formSubmitted = true;
                
                // Show loading animation
                if (loadingOverlay) loadingOverlay.classList.add('active');
                
                // Update loading message
                setTimeout(() => {
                    if (loadingTitle) loadingTitle.textContent = 'Sedikit lagi...';
                    if (loadingMessage) loadingMessage.textContent = 'Kami siapin akun kamu';
                }, 800);
                
                setTimeout(() => {
                    if (spinnerArea) spinnerArea.style.display = 'none';
                    if (successArea) successArea.style.display = 'block';
                    if (loadingTitle) loadingTitle.textContent = 'Yeay! 🎉';
                    if (loadingMessage) loadingMessage.textContent = 'Akunmu berhasil dibuat!';
                }, 1500);
                
                // Submit form normally
                setTimeout(() => {
                    registerForm.submit();
                }, 2000);
            });
        }
        
        <?php if($success): ?>
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>