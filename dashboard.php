<?php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'classes/Database.php';
require_once 'classes/Task.php';
require_once 'classes/Category.php';
require_once 'classes/Note.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Inisialisasi class
$task = new Task();
$category = new Category();
$note = new Note();

// Statistik Tugas
$total_tasks = $task->getTotalTasks($user_id);
$completed_tasks = $task->getCompletedTasks($user_id);
$pending_tasks = $task->getPendingTasks($user_id);
$completion_rate = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

// Tugas hari ini
$today_tasks = $task->getTodayTasks($user_id);
$today_completed = 0;
foreach ($today_tasks as $t) {
    if ($t['status'] == 'completed') $today_completed++;
}
$today_progress = count($today_tasks) > 0 ? round(($today_completed / count($today_tasks)) * 100) : 0;

// Tugas deadline mendekat (3 hari)
$upcoming_tasks = $task->getUpcomingTasks($user_id, 3);

// Tugas tertunda
$overdue_tasks = $task->getOverdueTasks($user_id);

// Statistik per kategori
$category_stats = $category->getTaskStats($user_id);

// Catatan terbaru
$recent_notes = $note->getRecentNotes($user_id, 5);

// Aktivitas terbaru
$recent_activities = $task->getRecentActivities($user_id, 10);

$page_title = 'Dashboard - TaskFlow';
$page_heading = 'Dashboard';
?>
<?php include 'includes/header.php'; ?>

<style>
/* Modal Styles */
.modal {
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s;
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    width: 90%;
    max-width: 700px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    animation: slideDown 0.3s;
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 1.25rem;
}

.modal-close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #9ca3af;
    transition: color 0.3s;
}

.modal-close:hover {
    color: #ef4444;
}

.modal-body {
    padding: 25px;
    max-height: 60vh;
    overflow-y: auto;
}

.note-meta-info {
    padding-bottom: 15px;
    margin-bottom: 20px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.note-meta-info small {
    color: #6b7280;
    font-size: 0.85rem;
}

.note-meta-info i {
    margin-right: 5px;
}

.note-content {
    line-height: 1.6;
    color: #374151;
    white-space: pre-wrap;
    word-wrap: break-word;
    font-size: 1rem;
}

.modal-footer {
    padding: 15px 25px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
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

.note-item {
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 10px;
    background: #f9fafb;
}

.note-item:hover {
    background: #e5e7eb;
    transform: translateX(5px);
}

.text-center {
    text-align: center;
}

.text-danger {
    color: #dc2626;
}

.btn-secondary {
    background: #6b7280;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-primary {
    background: #2c7a6e;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-primary:hover {
    background: #205b51;
}

.btn-secondary:hover {
    background: #4b5563;
}

.fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<div class="dashboard-container">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="welcome-text">
            <h3>Halo, <?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username']) ?>! 👋</h3>
            <p>Semangat menyelesaikan tugas hari ini!</p>
        </div>
        <div class="date-time">
            <i class="fas fa-calendar-alt"></i>
            <span id="currentDateTime"></span>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($total_tasks) ?></h3>
                <p>Total Tugas</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($completed_tasks) ?></h3>
                <p>Selesai</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($pending_tasks) ?></h3>
                <p>Belum Selesai</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-info">
                <h3><?= $completion_rate ?>%</h3>
                <p>Tingkat Penyelesaian</p>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="charts-row">
        <div class="chart-card">
            <div class="card-header">
                <h4><i class="fas fa-chart-pie"></i> Progress Tugas</h4>
                <span class="badge">Update realtime</span>
            </div>
            <div class="card-body">
                <canvas id="completionChart" width="400" height="300"></canvas>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="card-header">
                <h4><i class="fas fa-calendar-day"></i> Progress Hari Ini</h4>
                <span class="badge"><?= count($today_tasks) ?> tugas</span>
            </div>
            <div class="card-body">
                <div class="today-progress-circle">
                    <svg viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                        <circle class="progress-ring" cx="60" cy="60" r="54" fill="none" 
                                stroke="#2c7a6e" stroke-width="8" 
                                stroke-dasharray="<?= 2 * pi() * 54 ?>" 
                                stroke-dashoffset="<?= 2 * pi() * 54 * (1 - $today_progress/100) ?>"/>
                    </svg>
                    <div class="progress-text">
                        <span class="percent"><?= $today_progress ?>%</span>
                        <small>selesai</small>
                    </div>
                </div>
                <div class="today-stats">
                    <div class="stat-item">
                        <span class="label">Selesai:</span>
                        <span class="value"><?= $today_completed ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Tersisa:</span>
                        <span class="value"><?= count($today_tasks) - $today_completed ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tasks Sections -->
    <div class="tasks-row">
        <!-- Upcoming Tasks -->
        <div class="tasks-card">
            <div class="card-header">
                <h4><i class="fas fa-hourglass-half"></i> Deadline Mendekat</h4>
                <a href="pages/tasks/index.php?filter=upcoming" class="view-all">Lihat semua →</a>
            </div>
            <div class="card-body">
                <?php if(empty($upcoming_tasks)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>Tidak ada tugas dengan deadline mendekat</p>
                    </div>
                <?php else: ?>
                    <div class="task-list">
                        <?php foreach($upcoming_tasks as $task_item): ?>
                            <div class="task-item">
                                <div class="task-check">
                                    <input type="checkbox" class="task-checkbox" 
                                           data-id="<?= $task_item['id'] ?>"
                                           <?= $task_item['status'] == 'completed' ? 'checked' : '' ?>>
                                </div>
                                <div class="task-details">
                                    <div class="task-title <?= $task_item['status'] == 'completed' ? 'completed' : '' ?>">
                                        <?= htmlspecialchars($task_item['title']) ?>
                                    </div>
                                    <div class="task-meta">
                                        <span class="deadline">
                                            <i class="fas fa-calendar"></i> 
                                            <?= date('d M Y', strtotime($task_item['due_date'])) ?>
                                        </span>
                                        <span class="priority priority-<?= $task_item['priority'] ?>">
                                            <i class="fas fa-flag"></i> 
                                            <?= ucfirst($task_item['priority']) ?>
                                        </span>
                                        <?php if($task_item['category_name']): ?>
                                            <span class="category">
                                                <i class="fas fa-tag"></i> 
                                                <?= htmlspecialchars($task_item['category_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <a href="pages/tasks/edit.php?id=<?= $task_item['id'] ?>" class="btn-icon">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Overdue Tasks -->
        <div class="tasks-card">
            <div class="card-header">
                <h4><i class="fas fa-exclamation-triangle"></i> Tugas Tertunda</h4>
                <a href="pages/tasks/index.php?filter=overdue" class="view-all">Lihat semua →</a>
            </div>
            <div class="card-body">
                <?php if(empty($overdue_tasks)): ?>
                    <div class="empty-state success">
                        <i class="fas fa-thumbs-up"></i>
                        <p>Mantap! Tidak ada tugas yang tertunda 🎉</p>
                    </div>
                <?php else: ?>
                    <div class="task-list">
                        <?php foreach($overdue_tasks as $task_item): ?>
                            <div class="task-item overdue">
                                <div class="task-check">
                                    <input type="checkbox" class="task-checkbox" 
                                           data-id="<?= $task_item['id'] ?>">
                                </div>
                                <div class="task-details">
                                    <div class="task-title"><?= htmlspecialchars($task_item['title']) ?></div>
                                    <div class="task-meta">
                                        <span class="deadline overdue">
                                            <i class="fas fa-clock"></i> 
                                            Terlambat <?= round((time() - strtotime($task_item['due_date'])) / 86400) ?> hari
                                        </span>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <a href="pages/tasks/edit.php?id=<?= $task_item['id'] ?>" class="btn-icon">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bottom Sections -->
    <div class="bottom-row">
        <!-- Category Stats -->
        <div class="info-card">
            <div class="card-header">
                <h4><i class="fas fa-chart-simple"></i> Statistik per Kategori</h4>
                <a href="pages/categories/index.php" class="view-all">Kelola →</a>
            </div>
            <div class="card-body">
                <?php if(empty($category_stats)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>Belum ada kategori. Buat kategori dulu yuk!</p>
                        <a href="pages/categories/add.php" class="btn-sm btn-primary">Buat Kategori</a>
                    </div>
                <?php else: ?>
                    <div class="category-stats">
                        <?php foreach($category_stats as $cat): ?>
                            <div class="category-item">
                                <div class="category-info">
                                    <span class="category-name">
                                        <i class="fas fa-circle" style="color: <?= $cat['color'] ?? '#2c7a6e' ?>"></i>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </span>
                                    <span class="task-count"><?= $cat['total'] ?> tugas</span>
                                </div>
                                <div class="category-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= $cat['completion_rate'] ?>%; background: <?= $cat['color'] ?? '#2c7a6e' ?>"></div>
                                    </div>
                                    <span class="completion-rate"><?= $cat['completion_rate'] ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Notes -->
        <div class="info-card">
            <div class="card-header">
                <h4><i class="fas fa-sticky-note"></i> Catatan Terbaru</h4>
                <a href="pages/notes/index.php" class="btn-sm btn-primary">+ Catatan Baru</a>
            </div>
            <div class="card-body">
                <?php if(empty($recent_notes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-edit"></i>
                        <p>Belum ada catatan. Buat catatan pertama!</p>
                    </div>
                <?php else: ?>
                    <div class="notes-list">
                        <?php foreach($recent_notes as $note_item): ?>
                            <div class="note-item" onclick="showNoteModal(<?= $note_item['id'] ?>)">
                                <div class="note-title">
                                    <i class="fas fa-file-alt"></i>
                                    <span><?= htmlspecialchars($note_item['title']) ?></span>
                                </div>
                                <div class="note-meta">
                                    <small><i class="fas fa-clock"></i> <?= time_ago($note_item['created_at']) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk menampilkan note -->
<div id="noteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalNoteTitle"></h3>
            <span class="modal-close" onclick="closeNoteModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="note-meta-info">
                <small><i class="fas fa-calendar"></i> Dibuat: <span id="noteCreatedAt"></span></small>
                <small><i class="fas fa-edit"></i> Diupdate: <span id="noteUpdatedAt"></span></small>
            </div>
            <div class="note-content" id="modalNoteContent">
                <!-- Content akan diisi via AJAX -->
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeNoteModal()">Tutup</button>
            <a href="pages/notes/index.php" class="btn-primary">Lihat Semua Catatan</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart.js - Completion Chart
const ctx = document.getElementById('completionChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Selesai', 'Belum Selesai'],
        datasets: [{
            data: [<?= $completed_tasks ?>, <?= $pending_tasks ?>],
            backgroundColor: ['#2c7a6e', '#f97316'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: { family: 'Inter', size: 12 },
                    padding: 15
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                        return `${label}: ${value} tugas (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Update date and time
function updateDateTime() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    const dateTimeElement = document.getElementById('currentDateTime');
    if (dateTimeElement) {
        dateTimeElement.textContent = now.toLocaleDateString('id-ID', options);
    }
}
updateDateTime();
setInterval(updateDateTime, 60000);

// Task checkbox toggle
document.querySelectorAll('.task-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const taskId = this.dataset.id;
        const isChecked = this.checked;
        
        fetch('pages/tasks/toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: taskId, completed: isChecked })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                setTimeout(() => location.reload(), 500);
            } else {
                this.checked = !isChecked;
                Swal.fire('Error', 'Gagal mengupdate tugas', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.checked = !isChecked;
            Swal.fire('Error', 'Terjadi kesalahan', 'error');
        });
    });
});

// Function untuk menampilkan modal dengan data note
// Function untuk menampilkan modal dengan data note
function showNoteModal(noteId) {
    const modal = document.getElementById('noteModal');
    const modalTitle = document.getElementById('modalNoteTitle');
    const modalContent = document.getElementById('modalNoteContent');
    const noteCreatedAt = document.getElementById('noteCreatedAt');
    const noteUpdatedAt = document.getElementById('noteUpdatedAt');
    
    modalTitle.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...';
    modalContent.innerHTML = '<p class="text-center">Memuat catatan...</p>';
    modal.style.display = 'block';
    
    fetch('pages/notes/get_note.php?id=' + noteId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalTitle.innerHTML = `
                    <i class="fas fa-sticky-note"></i> 
                    ${escapeHtml(data.note.title)}
                `;
                modalContent.innerHTML = formatNoteContent(escapeHtml(data.note.content));
                noteCreatedAt.innerHTML = formatDate(data.note.created_at);
                noteUpdatedAt.innerHTML = formatDate(data.note.updated_at);
            } else {
                modalTitle.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                modalContent.innerHTML = `<p class="text-danger">${data.message}</p>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalTitle.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
            modalContent.innerHTML = '<p class="text-danger">Gagal memuat catatan. Silakan coba lagi.</p>';
        });
}

function closeNoteModal() {
    document.getElementById('noteModal').style.display = 'none';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNoteContent(content) {
    if (!content) return '<em>Catatan kosong</em>';
    return content.replace(/\n/g, '<br>');
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('noteModal');
    if (event.target == modal) {
        closeNoteModal();
    }
}

function closeNoteModal() {
    document.getElementById('noteModal').style.display = 'none';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNoteContent(content) {
    if (!content) return '<em>Catatan kosong</em>';
    return content.replace(/\n/g, '<br>');
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

window.onclick = function(event) {
    const modal = document.getElementById('noteModal');
    if (event.target == modal) {
        closeNoteModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>