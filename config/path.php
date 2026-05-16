<?php
// Definisikan path constants
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . DS . 'includes');
define('CLASSES_PATH', ROOT_PATH . DS . 'classes');
define('CONFIG_PATH', ROOT_PATH . DS . 'config');
define('PAGES_PATH', ROOT_PATH . DS . 'pages');
define('ASSETS_PATH', '/task_management/assets');

// Fungsi untuk mendapatkan base URL
function base_url($path = '') {
    return '/task_management/' . ltrim($path, '/');
}

// Fungsi untuk include file dengan path absolut
function include_file($file) {
    include_once ROOT_PATH . DS . str_replace('/', DS, $file);
}
?>