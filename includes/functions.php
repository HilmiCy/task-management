<?php
// Format date
function formatDate($date, $format = 'd M Y H:i') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Convert timestamp to time ago string
 * @param string $datetime Datetime string
 * @return string Time ago (e.g., "2 jam yang lalu")
 */
function time_ago($datetime) {
    $time = strtotime($datetime);
    $current = time();
    $diff = $current - $time;
    
    if ($diff < 60) {
        return 'baru saja';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' menit yang lalu';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' jam yang lalu';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' hari yang lalu';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' minggu yang lalu';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' bulan yang lalu';
    } else {
        $years = floor($diff / 31536000);
        return $years . ' tahun yang lalu';
    }
}

// Generate random string
function randomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Display flash message
function displayFlash() {
    global $session;
    $flash = $session->getFlash();
    if ($flash) {
        echo '<div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show" role="alert">
                ' . $flash['message'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    }
}

// Get priority badge
function getPriorityBadge($priority) {
    $colors = [
        'high' => 'danger',
        'medium' => 'warning',
        'low' => 'info'
    ];
    $color = $colors[$priority] ?? 'secondary';
    return "<span class='badge bg-$color'>" . ucfirst($priority) . "</span>";
}

// Get status badge
function getStatusBadge($status) {
    $colors = [
        'pending' => 'secondary',
        'in_progress' => 'primary',
        'completed' => 'success',
        'cancelled' => 'danger'
    ];
    $color = $colors[$status] ?? 'secondary';
    $icons = [
        'pending' => 'fa-clock',
        'in_progress' => 'fa-spinner',
        'completed' => 'fa-check',
        'cancelled' => 'fa-times'
    ];
    $icon = $icons[$status] ?? 'fa-tasks';
    
    return "<span class='badge bg-$color'><i class='fas $icon'></i> " . ucfirst(str_replace('_', ' ', $status)) . "</span>";
}

function base_url($path = '') {
    // Deteksi apakah di localhost atau server
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $base = '/task_management/';
    
    return $protocol . $host . $base . ltrim($path, '/');
}

// Fungsi untuk URL tanpa domain
function site_url($path = '') {
    return '/task_management/' . ltrim($path, '/');
}

// Debug function
function debug($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
}
?>