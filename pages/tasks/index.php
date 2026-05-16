<?php
$root_path = dirname(dirname(__DIR__));
require_once $root_path . '/config/config.php';
require_once $root_path . '/config/session.php';
require_once $root_path . '/includes/functions.php';
require_once $root_path . '/classes/Database.php';
require_once $root_path . '/classes/Task.php';
require_once $root_path . '/classes/Category.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$task = new Task();
$category = new Category();

// Ambil filter dari URL
$filter = $_GET['filter'] ?? 'all';
$category_id = $_GET['category_id'] ?? null;
$status = $_GET['status'] ?? null;
$search = $_GET['search'] ?? '';

// Ambil data tugas berdasarkan filter
if ($search) {
    $tasks = $task->searchTasks($user_id, $search);
} elseif ($category_id) {
    $tasks = $task->getTasksByCategory($user_id, $category_id);
} elseif ($status) {
    $tasks = $task->getTasksByStatus($user_id, $status);
} else {
    // Default: ambil semua tugas
    $sql = "SELECT t.*, c.name as category_name, c.color as category_color 
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.is_archived = 0
            ORDER BY FIELD(t.status, 'pending', 'in_progress', 'completed'), t.due_date ASC";
    $db = DB::getInstance();
    $tasks = $db->fetchAll($sql, [$user_id]);
}

// Ambil semua kategori untuk filter
$categories = $category->getAllCategories($user_id);

$page_title = 'Semua Tugas - TaskFlow';
$page_heading = 'Manajemen Tugas';
?>
<?php include '../../includes/header.php'; ?>

<div class="tasks-page-container">
    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <a href="?filter=all" class="filter-btn <?= $filter == 'all' && !$category_id && !$status ? 'active' : '' ?>">
                <i class="fas fa-list"></i> Semua
            </a>
            <a href="?status=pending" class="filter-btn <?= $status == 'pending' ? 'active' : '' ?>">
                <i class="fas fa-clock"></i> Pending
            </a>
            <a href="?status=in_progress" class="filter-btn <?= $status == 'in_progress' ? 'active' : '' ?>">
                <i class="fas fa-spinner"></i> In Progress
            </a>
            <a href="?status=completed" class="filter-btn <?= $status == 'completed' ? 'active' : '' ?>">
                <i class="fas fa-check-circle"></i> Selesai
            </a>
        </div>
        
        <div class="filter-group">
            <select id="categoryFilter" class="filter-select">
                <option value="">Semua Kategori</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Cari tugas..." value="<?= htmlspecialchars($search) ?>">
        </div>
        
        <button type="button" class="btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Tugas Baru
        </button>
    </div>
    
    <!-- Tasks Table -->
    <div class="tasks-table-container">
        <?php if(empty($tasks)): ?>
            <div class="empty-state-large">
                <i class="fas fa-tasks"></i>
                <h3>Belum ada tugas</h3>
                <p>Mulai buat tugas pertama Anda sekarang!</p>
                <button type="button" class="btn-primary" onclick="openAddModal()">+ Buat Tugas</button>
            </div>
        <?php else: ?>
            <table class="tasks-table">
                <thead>
                    <tr>
                        <th width="50"></th>
                        <th>Nama Tugas</th>
                        <th>Kategori</th>
                        <th>Prioritas</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tasks as $task_item): ?>
                        <tr class="task-row <?= $task_item['status'] == 'completed' ? 'completed-row' : '' ?>" data-task-id="<?= $task_item['id'] ?>">
                            <td class="text-center">
                                <input type="checkbox" class="task-checkbox" 
                                       data-id="<?= $task_item['id'] ?>"
                                       <?= $task_item['status'] == 'completed' ? 'checked' : '' ?>>
                            </td>
                            <td>
                                <div class="task-title-cell">
                                    <span class="task-title <?= $task_item['status'] == 'completed' ? 'completed' : '' ?>">
                                        <?= htmlspecialchars($task_item['title']) ?>
                                    </span>
                                    <?php if($task_item['description']): ?>
                                        <small class="task-desc-preview">
                                            <?= htmlspecialchars(substr($task_item['description'], 0, 50)) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if($task_item['category_name']): ?>
                                    <span class="category-badge" style="background: <?= $task_item['category_color'] ?>20; color: <?= $task_item['category_color'] ?>">
                                        <i class="fas fa-tag"></i> <?= htmlspecialchars($task_item['category_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="priority-badge priority-<?= $task_item['priority'] ?>">
                                    <i class="fas fa-flag"></i> <?= ucfirst($task_item['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $due_date = strtotime($task_item['due_date']);
                                $today = strtotime(date('Y-m-d'));
                                $is_overdue = $due_date < $today && $task_item['status'] != 'completed';
                                ?>
                                <span class="deadline-date <?= $is_overdue ? 'overdue' : '' ?>">
                                    <i class="fas fa-calendar"></i>
                                    <?= date('d M Y', $due_date) ?>
                                    <?php if($task_item['due_time']): ?>
                                        <br><small><?= date('H:i', strtotime($task_item['due_time'])) ?></small>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $task_item['status'] ?>">
                                    <?php
                                    $status_icons = [
                                        'pending' => 'fa-clock',
                                        'in_progress' => 'fa-spinner',
                                        'completed' => 'fa-check-circle',
                                        'cancelled' => 'fa-ban'
                                    ];
                                    ?>
                                    <i class="fas <?= $status_icons[$task_item['status']] ?>"></i>
                                    <?= ucfirst(str_replace('_', ' ', $task_item['status'])) ?>
                                </span>
                            </td>
                            <td class="actions">
                                <button onclick="openEditModal(<?= $task_item['id'] ?>)" class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteTask(<?= $task_item['id'] ?>)" class="btn-icon danger" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal untuk Add/Edit Tugas -->
<div id="taskModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Tugas Baru</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i> Memuat...
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background-color: white;
    margin: 3% auto;
    width: 90%;
    max-width: 800px;
    border-radius: 20px;
    animation: slideDown 0.3s;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    max-height: 90vh;
    overflow-y: auto;
}

.modal-lg {
    max-width: 800px;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    position: sticky;
    top: 0;
    background: white;
    z-index: 10;
    border-radius: 20px 20px 0 0;
}

.modal-header h3 {
    margin: 0;
    font-size: 20px;
    color: #1f2937;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #9ca3af;
    transition: color 0.3s;
}

.close:hover {
    color: #ef4444;
}

.modal-body {
    padding: 24px;
}

.loading-spinner {
    text-align: center;
    padding: 40px;
    color: #2c7a6e;
}

/* Form Styles untuk modal */
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

.required {
    color: #ef4444;
}

.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #2c7a6e;
    box-shadow: 0 0 0 3px rgba(44, 122, 110, 0.1);
}

textarea.form-control {
    resize: vertical;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-actions {
    display: flex;
    gap: 16px;
    margin-top: 32px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.btn-primary {
    padding: 10px 20px;
    background: #2c7a6e;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
    text-decoration: none;
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
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
    transition: all 0.3s;
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

.tasks-page-container {
    max-width: 1400px;
    margin: 0 auto;
}

.filter-bar {
    background: white;
    border-radius: 16px;
    padding: 16px 24px;
    margin-bottom: 24px;
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: center;
    justify-content: space-between;
}

.filter-group {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 8px 16px;
    border-radius: 40px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    color: #6b7280;
    background: #f3f4f6;
    transition: all 0.3s ease;
}

.filter-btn i {
    margin-right: 6px;
}

.filter-btn:hover,
.filter-btn.active {
    background: #2c7a6e;
    color: white;
}

.filter-select {
    padding: 8px 16px;
    border-radius: 40px;
    border: 1px solid #e5e7eb;
    background: white;
    font-size: 13px;
    cursor: pointer;
}

.search-box {
    flex: 1;
    max-width: 300px;
    position: relative;
}

.search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
}

.search-box input {
    width: 100%;
    padding: 8px 12px 8px 36px;
    border: 1px solid #e5e7eb;
    border-radius: 40px;
    font-size: 13px;
}

.tasks-table-container {
    background: white;
    border-radius: 16px;
    overflow-x: auto;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.tasks-table {
    width: 100%;
    border-collapse: collapse;
}

.tasks-table thead tr {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.tasks-table th {
    padding: 16px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tasks-table td {
    padding: 16px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

.task-row.completed-row {
    background: #f9fafb;
}

.task-title-cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.task-title {
    font-weight: 500;
    color: #1f2937;
}

.task-title.completed {
    text-decoration: line-through;
    color: #9ca3af;
}

.task-desc-preview {
    font-size: 11px;
    color: #9ca3af;
}

.category-badge,
.priority-badge,
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
}

.priority-high {
    background: #fee2e2;
    color: #ef4444;
}

.priority-medium {
    background: #fef3c7;
    color: #f59e0b;
}

.priority-low {
    background: #d1fae5;
    color: #10b981;
}

.status-pending {
    background: #fef3c7;
    color: #d97706;
}

.status-in_progress {
    background: #dbeafe;
    color: #2563eb;
}

.status-completed {
    background: #d1fae5;
    color: #059669;
}

.deadline-date {
    font-size: 12px;
    color: #6b7280;
}

.deadline-date.overdue {
    color: #ef4444;
    font-weight: 600;
}

.text-center {
    text-align: center;
}

.text-muted {
    color: #9ca3af;
}

.actions {
    display: flex;
    gap: 8px;
}

.btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px;
    border-radius: 6px;
    color: #6b7280;
    transition: all 0.3s ease;
}

.btn-icon:hover {
    background: #f3f4f6;
    color: #2c7a6e;
}

.btn-icon.danger:hover {
    background: #fee2e2;
    color: #ef4444;
}

.empty-state-large {
    text-align: center;
    padding: 80px 20px;
}

.empty-state-large i {
    font-size: 64px;
    color: #d1d5db;
    margin-bottom: 20px;
}

.empty-state-large h3 {
    font-size: 20px;
    margin-bottom: 8px;
}

.empty-state-large p {
    color: #6b7280;
    margin-bottom: 24px;
}

/* Style untuk input time 24 jam */
input[type="time"] {
    font-family: 'Inter', monospace;
}

/* Hilangkan spinner pada input time */
input[type="time"]::-webkit-inner-spin-button,
input[type="time"]::-webkit-outer-spin-button {
    display: none;
}

@media (max-width: 768px) {
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box {
        max-width: none;
    }
    
    .tasks-table th,
    .tasks-table td {
        padding: 12px;
    }
    
    .modal-content {
        margin: 10% auto;
        width: 95%;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Custom SweetAlert dengan border radius
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    customClass: {
        popup: 'swal-rounded'
    },
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

// Tambahkan CSS untuk SweetAlert rounded
const style = document.createElement('style');
style.textContent = `
    .swal-rounded {
        border-radius: 20px !important;
    }
    .swal2-popup {
        border-radius: 20px !important;
    }
    .swal2-confirm, .swal2-cancel {
        border-radius: 40px !important;
    }
`;
document.head.appendChild(style);

let currentEditId = null;

// Fungsi untuk memformat waktu ke format HH:MM (24 jam)
function formatTime24(timeStr) {
    if (!timeStr) return '';
    
    // Hapus spasi di awal/akhir
    timeStr = timeStr.trim();
    
    // Jika sudah dalam format HH:MM, return as-is
    if (/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/.test(timeStr)) {
        return timeStr;
    }
    
    // Jika format HH:MM:SS (dengan detik), ambil HH:MM saja
    if (/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/.test(timeStr)) {
        return timeStr.substring(0, 5);
    }
    
    // Coba parse dengan Date
    try {
        // Jika format "14:30:00" atau "14:30"
        const parts = timeStr.split(':');
        if (parts.length >= 2) {
            let hours = parseInt(parts[0]);
            let minutes = parseInt(parts[1]);
            
            if (!isNaN(hours) && !isNaN(minutes) && hours >= 0 && hours <= 23 && minutes >= 0 && minutes <= 59) {
                return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
            }
        }
    } catch(e) {
        console.log('Error parsing time:', e);
    }
    
    // Jika tidak bisa diparse, return string kosong
    return '';
}

// Fungsi untuk mengatur input time agar selalu format 24 jam
function setupTimeInput(inputElement) {
    if (!inputElement) return;
    
    // Set step=60 untuk menghindari detik
    inputElement.setAttribute('step', '60');
    
    // Format ulang nilai yang ada
    const currentValue = inputElement.value;
    if (currentValue) {
        const formatted = formatTime24(currentValue);
        if (formatted !== currentValue) {
            inputElement.value = formatted;
        }
    }
    
    // Event listener untuk memastikan format 24 jam
    inputElement.addEventListener('change', function() {
        if (this.value) {
            const formatted = formatTime24(this.value);
            if (formatted) {
                this.value = formatted;
            } else {
                this.value = '';
                Toast.fire({
                    icon: 'error',
                    title: 'Format waktu tidak valid! Gunakan HH:MM (00:00 - 23:59)'
                });
            }
        }
    });
    
    // Event listener untuk input langsung
    inputElement.addEventListener('input', function() {
        let value = this.value;
        // Hanya izinkan angka dan titik dua
        value = value.replace(/[^0-9:]/g, '');
        if (value.length === 2 && !value.includes(':')) {
            value = value + ':';
        }
        this.value = value;
    });
}

// Form HTML untuk modal
function getTaskForm(taskData = null) {
    const isEdit = taskData !== null;
    const today = new Date().toISOString().split('T')[0];
    
    // Format waktu untuk input time (24 jam format)
    let formattedTime = '';
    if (isEdit && taskData.due_time) {
        formattedTime = formatTime24(taskData.due_time);
    }
    
    const formHtml = `
        <form id="taskForm" method="POST">
            <input type="hidden" name="task_id" id="task_id" value="${isEdit ? taskData.id : ''}">
            
            <div class="form-group">
                <label for="title">Judul Tugas <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-control" 
                       value="${isEdit ? escapeHtml(taskData.title) : ''}" required>
            </div>
            
            <div class="form-group">
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description" class="form-control" rows="3">${isEdit ? escapeHtml(taskData.description || '') : ''}</textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">Kategori</label>
                    <select id="category_id" name="category_id" class="form-control">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" ${isEdit && taskData.category_id == <?= $cat['id'] ?> ? 'selected' : ''}>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="priority">Prioritas <span class="required">*</span></label>
                    <select id="priority" name="priority" class="form-control" required>
                        <option value="low" ${isEdit && taskData.priority == 'low' ? 'selected' : ''}>Low</option>
                        <option value="medium" ${isEdit && taskData.priority == 'medium' ? 'selected' : ''} selected>Medium</option>
                        <option value="high" ${isEdit && taskData.priority == 'high' ? 'selected' : ''}>High</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="due_date">Tanggal Deadline <span class="required">*</span></label>
                    <input type="date" id="due_date" name="due_date" class="form-control" 
                           value="${isEdit ? taskData.due_date : ''}" min="${today}" required>
                </div>
                
                <div class="form-group">
                    <label for="due_time">Jam Deadline (Format 24 Jam)</label>
                    <input type="text" id="due_time" name="due_time" class="form-control" 
                           value="${formattedTime}" placeholder="HH:MM (contoh: 14:30)" maxlength="5">
                    <small style="color: #6b7280; font-size: 11px;">Contoh: 14:30 atau 09:00</small>
                </div>
                
                <div class="form-group">
                    <label for="estimated_hours">Estimasi Jam</label>
                    <input type="number" id="estimated_hours" name="estimated_hours" class="form-control" 
                           step="0.5" min="0" value="${isEdit ? (taskData.estimated_hours || 0) : 0}">
                </div>
            </div>
            
            ${isEdit ? `
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="pending" ${taskData.status == 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="in_progress" ${taskData.status == 'in_progress' ? 'selected' : ''}>In Progress</option>
                    <option value="completed" ${taskData.status == 'completed' ? 'selected' : ''}>Completed</option>
                    <option value="cancelled" ${taskData.status == 'cancelled' ? 'selected' : ''}>Cancelled</option>
                </select>
            </div>
            ` : ''}
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Simpan</button>
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
            </div>
        </form>
    `;
    return formHtml;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Open modal for add task
function openAddModal() {
    currentEditId = null;
    document.getElementById('modalTitle').textContent = 'Tambah Tugas Baru';
    document.getElementById('modalBody').innerHTML = getTaskForm();
    document.getElementById('taskModal').style.display = 'block';
    attachFormHandler();
    
    // Setup time input
    const timeInput = document.getElementById('due_time');
    if (timeInput) {
        setupTimeInput(timeInput);
    }
}

// Open modal for edit task
function openEditModal(taskId) {
    currentEditId = taskId;
    document.getElementById('modalTitle').textContent = 'Edit Tugas';
    document.getElementById('modalBody').innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';
    document.getElementById('taskModal').style.display = 'block';
    
    // Fetch task data
    fetch(`get_task.php?id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalBody').innerHTML = getTaskForm(data.task);
                attachFormHandler();
                
                // Setup time input
                const timeInput = document.getElementById('due_time');
                if (timeInput) {
                    setupTimeInput(timeInput);
                }
            } else {
                document.getElementById('modalBody').innerHTML = `<div class="alert error">${data.message}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('modalBody').innerHTML = '<div class="alert error">Gagal memuat data tugas</div>';
        });
}

// Close modal
function closeModal() {
    document.getElementById('taskModal').style.display = 'none';
    currentEditId = null;
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target == document.getElementById('taskModal')) {
        closeModal();
    }
}

// Attach form submit handler
function attachFormHandler() {
    const form = document.getElementById('taskForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validasi waktu (format 24 jam)
        const timeInput = document.getElementById('due_time');
        if (timeInput && timeInput.value) {
            const timePattern = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
            if (!timePattern.test(timeInput.value)) {
                Toast.fire({
                    icon: 'error',
                    title: 'Format waktu tidak valid! Gunakan HH:MM (00:00 - 23:59)'
                });
                return;
            }
        }
        
        const formData = new FormData(form);
        const url = currentEditId ? 'edit.php' : 'add.php';
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Toast.fire({
                    icon: 'success',
                    title: data.message
                });
                closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
            }
        })
        .catch(error => {
            Toast.fire({
                icon: 'error',
                title: 'Terjadi kesalahan pada server'
            });
        });
    });
}

// Category filter
const categoryFilter = document.getElementById('categoryFilter');
if (categoryFilter) {
    categoryFilter.addEventListener('change', function() {
        const categoryId = this.value;
        if (categoryId) {
            window.location.href = `?category_id=${categoryId}`;
        } else {
            window.location.href = '?filter=all';
        }
    });
}

// Search input debounce
const searchInput = document.getElementById('searchInput');
let searchTimeout;
if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const keyword = this.value;
            if (keyword.length >= 2 || keyword.length === 0) {
                window.location.href = `?search=${encodeURIComponent(keyword)}`;
            }
        }, 500);
    });
}

// Task checkbox toggle
document.querySelectorAll('.task-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const taskId = this.dataset.id;
        const isChecked = this.checked;
        
        fetch('toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: taskId, completed: isChecked })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                setTimeout(() => location.reload(), 300);
            } else {
                this.checked = !isChecked;
                Toast.fire({
                    icon: 'error',
                    title: data.message || 'Gagal mengupdate tugas'
                });
            }
        })
        .catch(error => {
            this.checked = !isChecked;
            Toast.fire({
                icon: 'error',
                title: 'Terjadi kesalahan'
            });
        });
    });
});

// Delete task function
function deleteTask(taskId) {
    Swal.fire({
        title: 'Hapus Tugas?',
        text: "Tugas yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'swal-rounded',
            confirmButton: 'swal2-confirm',
            cancelButton: 'swal2-cancel'
        },
        buttonsStyling: false,
        preConfirm: () => {
            return fetch('delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: taskId })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message);
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Error: ${error.message}`);
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Toast.fire({
                icon: 'success',
                title: result.value.message
            });
            setTimeout(() => location.reload(), 1000);
        }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>