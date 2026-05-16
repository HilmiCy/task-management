<?php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';

// Cek apakah sudah login, jika ya redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'] ?: $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            
            // Update last login
            $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            // Response untuk animasi loading modern
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
                exit;
            } else {
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $error = 'Email/Username atau password salah. Coba lagi ya!';
        }
    } else {
        $error = 'Yuk, isi dulu email/username dan passwordnya!';
    }
}

$page_title = 'Login - Task Management';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= APP_NAME ?> - Kelola Tugas Lebih Mudah | Masuk</title>
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

        /* Main Container - Glassmorphism */
        .login-container {
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

        /* Left Side - Branding with Teal theme */
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

        .btn-register {
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

        .btn-register:hover {
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

        /* Form Styles */
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

        .form-control {
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

        .form-control:focus {
            border-color: #2c7a6e;
            background: white;
            box-shadow: 0 0 0 3px rgba(44, 122, 110, 0.1);
        }

        .form-control:focus + .input-icon {
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

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 13px;
            color: #6b7280;
        }

        .checkbox-label input {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #2c7a6e;
        }

        .forgot-link {
            color: #f97316;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: #ea580c;
            text-decoration: underline;
        }

        .btn-login {
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

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(44, 122, 110, 0.4);
            background: linear-gradient(135deg, #1a5f5a 0%, #0f4a45 100%);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .divider {
            text-align: center;
            margin-bottom: 24px;
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

        .register-mobile {
            display: none;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }

        .register-mobile a {
            color: #f97316;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-mobile a:hover {
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
            background: #fef2f2;
            border-left: 3px solid #ef4444;
            color: #dc2626;
        }

        /* Modern Loading Animation */
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

        /* Task Spinner */
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

        /* Success Animation */
        .success-circle {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            animation: scaleUp 0.5s ease;
        }

        @keyframes scaleUp {
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
            animation: dotPulse 1.4s ease-in-out infinite;
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

        /* Glass effect for inputs */
        .glass-input {
            background: rgba(249, 250, 251, 0.9);
            backdrop-filter: blur(5px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
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

            .btn-register {
                display: none;
            }

            .register-mobile {
                display: block;
            }

            .form-side {
                padding: 32px 24px;
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

    <div class="login-container">
        <!-- Left Side - Branding -->
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
            <a href="register.php" class="btn-register">Buat Akun Gratis →</a>
        </div>

        <!-- Right Side - Login Form -->
        <div class="form-side">
            <div class="form-header">
                <h2>Selamat Datang Kembali! 👋</h2>
                <p>Masuk untuk lanjut kelola tugasmu</p>
            </div>

            <?php if($error): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="input-group">
                    <label class="input-label">Email atau Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="text" 
                               name="email" 
                               id="emailInput"
                               class="form-control" 
                               placeholder="contoh@email.com atau username" 
                               required 
                               autocomplete="email"
                               autofocus>
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
                               placeholder="Masukkan kata sandi" 
                               required 
                               autocomplete="current-password">
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember_me" id="rememberMe">
                        <span>Ingat saya</span>
                    </label>
                    <a href="forgot-password.php" class="forgot-link">Lupa sandi?</a>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </button>

                <div class="divider">
                    <span>atau</span>
                </div>

                <div class="register-mobile">
                    Belum punya akun? <a href="register.php">Daftar sekarang</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Modern Loading Animation -->
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
            <h3 id="loadingText">Memproses masuk...</h3>
            <p id="loadingSubtext">Tunggu sebentar ya, lagi siapin dashboardmu</p>
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }

        // Remember me functionality
        const rememberCheckbox = document.getElementById('rememberMe');
        const emailInput = document.getElementById('emailInput');
        
        if (localStorage.getItem('saved_email')) {
            emailInput.value = localStorage.getItem('saved_email');
            if (rememberCheckbox) rememberCheckbox.checked = true;
        }
        
        if (rememberCheckbox) {
            rememberCheckbox.addEventListener('change', function() {
                if (this.checked && emailInput.value) {
                    localStorage.setItem('saved_email', emailInput.value);
                } else {
                    localStorage.removeItem('saved_email');
                }
            });
        }
        
        if (emailInput) {
            emailInput.addEventListener('change', function() {
                if (rememberCheckbox && rememberCheckbox.checked) {
                    localStorage.setItem('saved_email', this.value);
                }
            });
        }

        // Form submission with modern loading animation
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const spinnerArea = document.getElementById('spinnerArea');
        const successArea = document.getElementById('successArea');
        const loadingText = document.getElementById('loadingText');
        const loadingSubtext = document.getElementById('loadingSubtext');
        
        const loadingMessages = [
            { text: 'Memeriksa akunmu...', sub: 'Pastikan data sudah benar ya' },
            { text: 'Login berhasil!', sub: 'Kami arahkan ke dashboard...' }
        ];

        let formSubmitted = false;

        if (loginForm) {
            loginForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                if (formSubmitted) return;
                formSubmitted = true;
                
                const formData = new FormData(loginForm);
                
                // Show loading animation
                loadingOverlay.classList.add('active');
                
                // Change loading message after delay
                setTimeout(() => {
                    loadingText.textContent = loadingMessages[0].text;
                    loadingSubtext.textContent = loadingMessages[0].sub;
                }, 1000);
                
                loginBtn.classList.add('loading');
                const originalText = loginBtn.innerHTML;
                loginBtn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Memproses...';
                loginBtn.disabled = true;
                
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Show success animation
                        setTimeout(() => {
                            spinnerArea.style.display = 'none';
                            successArea.style.display = 'block';
                            loadingText.textContent = 'Berhasil Masuk! 🎉';
                            loadingSubtext.textContent = 'Selamat datang kembali!';
                        }, 500);
                        
                        setTimeout(() => {
                            window.location.href = result.redirect;
                        }, 2000);
                    } else {
                        window.location.reload();
                    }
                } catch (error) {
                    loginForm.submitted = true;
                    loginForm.submit();
                }
            });
        }
    </script>
</body>
</html>