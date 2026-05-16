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

// Ambil data statistik
$taskStats = $task->getTaskStatistics($user_id);
$categories = $category->getTaskStats($user_id);

// Ambil rentang tanggal untuk laporan
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['type'] ?? 'overview';

$page_title = 'Laporan - TaskFlow';
?>
<?php include '../../includes/header.php'; ?>

<div class="reports-container">
    <!-- Header -->
    <div class="reports-header">
        <div class="header-info">
            <h1><i class="fas fa-chart-bar"></i> Laporan & Statistik</h1>
            <p>Analisis produktivitas dan performa tugas Anda</p>
        </div>
        <div class="header-actions">
            <button class="btn-secondary" onclick="exportReport('pdf')">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
            <button class="btn-primary" onclick="exportReport('excel')">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="filter-section">
        <form id="reportForm" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                </div>
                <div class="form-group">
                    <label>Tanggal Akhir</label>
                    <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                </div>
                <div class="form-group">
                    <label>Tipe Laporan</label>
                    <select name="type" class="form-control">
                        <option value="overview" <?= $report_type == 'overview' ? 'selected' : '' ?>>Overview</option>
                        <option value="category" <?= $report_type == 'category' ? 'selected' : '' ?>>Per Kategori</option>
                        <option value="priority" <?= $report_type == 'priority' ? 'selected' : '' ?>>Per Prioritas</option>
                        <option value="daily" <?= $report_type == 'daily' ? 'selected' : '' ?>>Harian</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-primary">Tampilkan</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-info">
                <h3>Total Tugas</h3>
                <p class="stat-number" id="totalTasks"><?= $taskStats['total'] ?? 0 ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon completed">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3>Selesai</h3>
                <p class="stat-number" id="completedTasks"><?= $taskStats['completed'] ?? 0 ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3>Pending</h3>
                <p class="stat-number" id="pendingTasks"><?= $taskStats['pending'] ?? 0 ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon progress">
                <i class="fas fa-spinner"></i>
            </div>
            <div class="stat-info">
                <h3>In Progress</h3>
                <p class="stat-number" id="inProgressTasks"><?= $taskStats['in_progress'] ?? 0 ?></p>
            </div>
        </div>
    </div>

    <!-- Completion Rate -->
    <div class="completion-card">
        <div class="completion-header">
            <h3><i class="fas fa-chart-line"></i> Tingkat Penyelesaian</h3>
            <span class="completion-percent" id="completionPercent">
                <?= $taskStats['total'] > 0 ? round(($taskStats['completed'] / $taskStats['total']) * 100) : 0 ?>%
            </span>
        </div>
        <div class="progress-bar-large">
            <div class="progress-fill" id="completionFill" style="width: <?= $taskStats['total'] > 0 ? round(($taskStats['completed'] / $taskStats['total']) * 100) : 0 ?>%"></div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-grid">
        <!-- Priority Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fas fa-flag"></i> Tugas Berdasarkan Prioritas</h3>
            </div>
            <div class="chart-body">
                <canvas id="priorityChart" width="400" height="300"></canvas>
                <div class="chart-legend" id="priorityLegend"></div>
            </div>
        </div>

        <!-- Category Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fas fa-tags"></i> Tugas Berdasarkan Kategori</h3>
            </div>
            <div class="chart-body">
                <canvas id="categoryChart" width="400" height="300"></canvas>
                <div class="chart-legend" id="categoryLegend"></div>
            </div>
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="status-card">
        <div class="chart-header">
            <h3><i class="fas fa-chart-pie"></i> Distribusi Status Tugas</h3>
        </div>
        <div class="status-bars">
            <div class="status-bar-item">
                <div class="status-label">
                    <span><i class="fas fa-clock"></i> Pending</span>
                    <span id="pendingPercent">0%</span>
                </div>
                <div class="status-bar-bg">
                    <div class="status-bar-fill pending-fill" id="pendingFill" style="width: 0%"></div>
                </div>
            </div>
            <div class="status-bar-item">
                <div class="status-label">
                    <span><i class="fas fa-spinner"></i> In Progress</span>
                    <span id="progressPercent">0%</span>
                </div>
                <div class="status-bar-bg">
                    <div class="status-bar-fill progress-fill" id="progressFill" style="width: 0%"></div>
                </div>
            </div>
            <div class="status-bar-item">
                <div class="status-label">
                    <span><i class="fas fa-check-circle"></i> Completed</span>
                    <span id="completedPercent">0%</span>
                </div>
                <div class="status-bar-bg">
                    <div class="status-bar-fill completed-fill" id="completedFill" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Tasks Table -->
    <div class="tasks-table-card">
        <div class="chart-header">
            <h3><i class="fas fa-list"></i> Daftar Tugas Terbaru</h3>
            <button class="btn-icon" onclick="refreshTable()">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="tasksTable">
                <thead>
                    <tr>
                        <th>Nama Tugas</th>
                        <th>Kategori</th>
                        <th>Prioritas</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody id="tasksTableBody">
                    <tr>
                        <td colspan="6" class="text-center">Memuat data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Reports Container */
.reports-container {
    max-width: 1400px;
    margin: 0 auto;
}

/* Header */
.reports-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    background: white;
    padding: 24px 32px;
    border-radius: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.header-info h1 {
    font-size: 24px;
    color: #1f2937;
    margin-bottom: 8px;
}

.header-info h1 i {
    color: #2c7a6e;
    margin-right: 10px;
}

.header-info p {
    color: #6b7280;
    font-size: 14px;
}

.header-actions {
    display: flex;
    gap: 12px;
}

/* Filter Section */
.filter-section {
    background: white;
    border-radius: 20px;
    padding: 20px 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.filter-form .form-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    align-items: end;
}

.form-group {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border-radius: 20px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.stat-icon.total {
    background: #e0f2fe;
    color: #0284c7;
}

.stat-icon.completed {
    background: #d1fae5;
    color: #059669;
}

.stat-icon.pending {
    background: #fef3c7;
    color: #d97706;
}

.stat-icon.progress {
    background: #dbeafe;
    color: #2563eb;
}

.stat-info h3 {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 8px;
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    color: #1f2937;
}

/* Completion Card */
.completion-card {
    background: white;
    border-radius: 20px;
    padding: 20px 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.completion-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.completion-header h3 {
    font-size: 16px;
    color: #1f2937;
}

.completion-header h3 i {
    color: #2c7a6e;
    margin-right: 8px;
}

.completion-percent {
    font-size: 24px;
    font-weight: 700;
    color: #2c7a6e;
}

.progress-bar-large {
    height: 12px;
    background: #f3f4f6;
    border-radius: 20px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2c7a6e, #3b9b8c);
    border-radius: 20px;
    transition: width 0.5s ease;
}

/* Charts Grid */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
    margin-bottom: 24px;
}

.chart-card, .status-card, .tasks-table-card {
    background: white;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e5e7eb;
}

.chart-header h3 {
    font-size: 16px;
    color: #1f2937;
}

.chart-header h3 i {
    color: #2c7a6e;
    margin-right: 8px;
}

.chart-body {
    display: flex;
    flex-direction: column;
    align-items: center;
}

canvas {
    max-width: 100%;
    height: auto;
}

.chart-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 16px;
    justify-content: center;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

/* Status Bars */
.status-bars {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.status-bar-item {
    width: 100%;
}

.status-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 6px;
    font-size: 13px;
    color: #6b7280;
}

.status-bar-bg {
    height: 24px;
    background: #f3f4f6;
    border-radius: 12px;
    overflow: hidden;
}

.status-bar-fill {
    height: 100%;
    border-radius: 12px;
    transition: width 0.5s ease;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 8px;
    color: white;
    font-size: 11px;
    font-weight: 500;
}

.pending-fill {
    background: #f59e0b;
}

.progress-fill {
    background: #3b82f6;
}

.completed-fill {
    background: #10b981;
}

/* Data Table */
.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    padding: 12px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
}

.data-table td {
    padding: 12px;
    font-size: 13px;
    color: #1f2937;
    border-bottom: 1px solid #f3f4f6;
}

.text-center {
    text-align: center;
}

/* Badges */
.priority-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
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

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
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

.progress-bar-small {
    width: 100px;
    height: 6px;
    background: #f3f4f6;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill-small {
    height: 100%;
    background: #2c7a6e;
    border-radius: 10px;
}

.btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px;
    border-radius: 8px;
    color: #6b7280;
    transition: all 0.3s;
}

.btn-icon:hover {
    background: #f3f4f6;
    color: #2c7a6e;
}

.btn-primary {
    padding: 10px 20px;
    background: #2c7a6e;
    color: white;
    border: none;
    border-radius: 40px;
    cursor: pointer;
    font-size: 13px;
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
    font-size: 13px;
    font-weight: 500;
    color: #6b7280;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

@media (max-width: 900px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .charts-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-form .form-row {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .reports-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let priorityChart = null;
let categoryChart = null;

// Load report data
function loadReport() {
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    const type = document.querySelector('select[name="type"]').value;
    
    fetch(`get_report_data.php?start_date=${startDate}&end_date=${endDate}&type=${type}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStats(data.stats);
                updateCharts(data);
                updateStatusBars(data.stats);
                updateTasksTable(data.tasks);
            }
        })
        .catch(error => console.error('Error:', error));
}

function updateStats(stats) {
    document.getElementById('totalTasks').textContent = stats.total || 0;
    document.getElementById('completedTasks').textContent = stats.completed || 0;
    document.getElementById('pendingTasks').textContent = stats.pending || 0;
    document.getElementById('inProgressTasks').textContent = stats.in_progress || 0;
    
    const percent = stats.total > 0 ? Math.round((stats.completed / stats.total) * 100) : 0;
    document.getElementById('completionPercent').textContent = `${percent}%`;
    document.getElementById('completionFill').style.width = `${percent}%`;
}

function updateCharts(data) {
    // Priority Chart
    if (priorityChart) priorityChart.destroy();
    
    const priorityCtx = document.getElementById('priorityChart').getContext('2d');
    priorityChart = new Chart(priorityCtx, {
        type: 'doughnut',
        data: {
            labels: ['High', 'Medium', 'Low'],
            datasets: [{
                data: [data.priority.high, data.priority.medium, data.priority.low],
                backgroundColor: ['#ef4444', '#f59e0b', '#10b981'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Category Chart
    if (categoryChart) categoryChart.destroy();
    
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryLabels = data.categories.map(c => c.name || 'Tanpa Kategori');
    const categoryData = data.categories.map(c => c.total);
    const categoryColors = data.categories.map(c => c.color || '#9ca3af');
    
    categoryChart = new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: categoryLabels,
            datasets: [{
                label: 'Jumlah Tugas',
                data: categoryData,
                backgroundColor: categoryColors,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
}

function updateStatusBars(stats) {
    const total = stats.total || 1;
    const pendingPercent = Math.round((stats.pending / total) * 100);
    const progressPercent = Math.round((stats.in_progress / total) * 100);
    const completedPercent = Math.round((stats.completed / total) * 100);
    
    document.getElementById('pendingPercent').textContent = `${pendingPercent}%`;
    document.getElementById('progressPercent').textContent = `${progressPercent}%`;
    document.getElementById('completedPercent').textContent = `${completedPercent}%`;
    
    document.getElementById('pendingFill').style.width = `${pendingPercent}%`;
    document.getElementById('progressFill').style.width = `${progressPercent}%`;
    document.getElementById('completedFill').style.width = `${completedPercent}%`;
}

function updateTasksTable(tasks) {
    const tbody = document.getElementById('tasksTableBody');
    if (!tasks || tasks.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Tidak ada data tugas</td></tr>';
        return;
    }
    
    let html = '';
    tasks.forEach(task => {
        const priorityClass = task.priority === 'high' ? 'priority-high' : (task.priority === 'medium' ? 'priority-medium' : 'priority-low');
        const statusClass = task.status === 'completed' ? 'status-completed' : (task.status === 'in_progress' ? 'status-in_progress' : 'status-pending');
        const progressPercent = task.status === 'completed' ? 100 : (task.status === 'in_progress' ? 50 : 0);
        
        html += `
            <tr>
                <td><strong>${escapeHtml(task.title)}</strong><br><small style="color:#9ca3af;">${escapeHtml(task.description?.substring(0, 50) || '')}</small></td>
                <td>${escapeHtml(task.category_name || '-')}</td>
                <td><span class="priority-badge ${priorityClass}">${task.priority}</span></td>
                <td>${task.due_date} ${task.due_time ? task.due_time.substring(0,5) : ''}</td>
                <td><span class="status-badge ${statusClass}">${task.status.replace('_', ' ')}</span></td>
                <td>
                    <div class="progress-bar-small">
                        <div class="progress-fill-small" style="width: ${progressPercent}%"></div>
                    </div>
                    <small>${progressPercent}%</small>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function refreshTable() {
    loadReport();
}

function exportReport(type) {
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    const reportType = document.querySelector('select[name="type"]').value;
    
    window.location.href = `export.php?type=${type}&start_date=${startDate}&end_date=${endDate}&report_type=${reportType}`;
}

// Form submit handler
document.getElementById('reportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    loadReport();
});

// Initial load
loadReport();
</script>

<?php include '../../includes/footer.php'; ?>