<?php
$root_path = dirname(dirname(__DIR__));
require_once $root_path . '/config/config.php';
require_once $root_path . '/config/session.php';
require_once $root_path . '/includes/functions.php';
require_once $root_path . '/classes/Database.php';
require_once $root_path . '/classes/Task.php';
require_once $root_path . '/classes/Category.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$task = new Task();
$category = new Category();

// Ambil semua kategori
$categories = $category->getAllCategories($user_id);

$page_title = 'Kalender - TaskFlow';
?>
<?php include '../../includes/header.php'; ?>

<div class="calendar-wrapper">
    <div class="calendar-sidebar">
        <div class="mini-calendar" id="miniCalendar"></div>
        
        <div class="upcoming-tasks">
            <h3><i class="fas fa-clock"></i> Tugas Mendatang</h3>
            <div id="upcomingTasksList" class="upcoming-list">
                <div class="loading-small">Memuat...</div>
            </div>
        </div>
    </div>
    
    <div class="calendar-main">
        <div class="calendar-toolbar">
            <div class="calendar-nav">
                <button class="nav-btn" id="prevMonthBtn">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <h2 id="currentMonthYear"></h2>
                <button class="nav-btn" id="nextMonthBtn">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button class="today-btn" id="todayBtn">Hari Ini</button>
            </div>
            <div class="calendar-legend">
                <span class="legend-item"><span class="legend-dot high"></span> High Priority</span>
                <span class="legend-item"><span class="legend-dot medium"></span> Medium Priority</span>
                <span class="legend-item"><span class="legend-dot low"></span> Low Priority</span>
                <span class="legend-item"><span class="legend-dot completed"></span> Selesai</span>
                <span class="legend-item"><span class="legend-dot overdue"></span> Terlewat</span>
            </div>
        </div>
        
        <div class="calendar-grid" id="calendarGrid">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i> Memuat kalender...
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Tugas -->
<div id="taskModal" class="modal">
    <div class="modal-content modal-detail">
        <div class="modal-header">
            <h3 id="modalTitle">Detail Tugas</h3>
            <button class="modal-close" onclick="closeTaskModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="loading-spinner">Memuat...</div>
        </div>
    </div>
</div>

<!-- Modal Tambah Tugas Cepat -->
<div id="quickTaskModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Tambah Tugas Baru</h3>
            <button class="modal-close" onclick="closeQuickTaskModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="quickTaskForm">
                <input type="hidden" id="quickDueDate" name="due_date">
                <div class="form-group">
                    <label>Judul Tugas <span class="required">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="Contoh: Mengerjakan laporan" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Deskripsi tugas..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Prioritas</label>
                        <select name="priority" class="form-control">
                            <option value="low">🟢 Low</option>
                            <option value="medium" selected>🟡 Medium</option>
                            <option value="high">🔴 High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jam (Opsional)</label>
                        <input type="time" name="due_time" class="form-control" step="60">
                    </div>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="category_id" class="form-control">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeQuickTaskModal()">Batal</button>
                    <button type="submit" class="btn-primary">Simpan Tugas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Wrapper */
.calendar-wrapper {
    display: flex;
    gap: 24px;
    max-width: 1400px;
    margin: 0 auto;
}

/* Sidebar */
.calendar-sidebar {
    width: 280px;
    flex-shrink: 0;
}

.mini-calendar {
    background: white;
    border-radius: 20px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.upcoming-tasks {
    background: white;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.upcoming-tasks h3 {
    font-size: 16px;
    margin-bottom: 16px;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 8px;
}

.upcoming-tasks h3 i {
    color: #2c7a6e;
}

.upcoming-list {
    max-height: 400px;
    overflow-y: auto;
}

.upcoming-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 8px;
    background: #f9fafb;
    cursor: pointer;
    transition: all 0.3s;
}

.upcoming-item:hover {
    background: #f3f4f6;
    transform: translateX(4px);
}

.upcoming-date {
    min-width: 45px;
    text-align: center;
}

.upcoming-day {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
}

.upcoming-month {
    font-size: 10px;
    color: #6b7280;
}

.upcoming-info {
    flex: 1;
}

.upcoming-title {
    font-size: 13px;
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 4px;
}

.upcoming-priority {
    font-size: 10px;
    display: inline-block;
    padding: 2px 8px;
    border-radius: 20px;
}

/* Main Calendar */
.calendar-main {
    flex: 1;
    background: white;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.calendar-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.calendar-nav {
    display: flex;
    align-items: center;
    gap: 12px;
}

.nav-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: #f3f4f6;
    cursor: pointer;
    transition: all 0.3s;
    color: #6b7280;
}

.nav-btn:hover {
    background: #2c7a6e;
    color: white;
}

.calendar-nav h2 {
    font-size: 20px;
    color: #1f2937;
    min-width: 180px;
    text-align: center;
}

.today-btn {
    padding: 8px 20px;
    border-radius: 40px;
    border: 1px solid #e5e7eb;
    background: white;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 13px;
}

.today-btn:hover {
    background: #2c7a6e;
    color: white;
    border-color: #2c7a6e;
}

.calendar-legend {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #6b7280;
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.legend-dot.high { background: #ef4444; }
.legend-dot.medium { background: #f59e0b; }
.legend-dot.low { background: #10b981; }
.legend-dot.completed { background: #9ca3af; }
.legend-dot.overdue { background: #dc2626; }

/* Calendar Grid */
.calendar-table {
    width: 100%;
    border-collapse: collapse;
}

.calendar-table th {
    padding: 12px;
    text-align: center;
    font-weight: 600;
    color: #6b7280;
    font-size: 13px;
}

.calendar-day {
    border: 1px solid #f0f0f0;
    vertical-align: top;
    min-height: 120px;
    background: white;
    transition: all 0.2s;
}

.calendar-day:hover {
    background: #fafafa;
}

.calendar-day.other-month {
    background: #fafafa;
}

.calendar-day.today {
    background: linear-gradient(135deg, #e6f7f5 0%, #f0fbf9 100%);
}

.day-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px;
}

.day-number {
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
}

.today .day-number {
    background: #2c7a6e;
    color: white;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.add-task-btn {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: none;
    background: transparent;
    cursor: pointer;
    color: #9ca3af;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.add-task-btn:hover {
    background: #2c7a6e;
    color: white;
}

.day-tasks {
    padding: 4px 8px;
}

.calendar-task {
    background: #f3f4f6;
    border-radius: 8px;
    padding: 6px 8px;
    margin-bottom: 4px;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.calendar-task:hover {
    transform: translateX(2px);
}

.calendar-task.high {
    background: #fee2e2;
    border-left: 3px solid #ef4444;
}

.calendar-task.medium {
    background: #fef3c7;
    border-left: 3px solid #f59e0b;
}

.calendar-task.low {
    background: #d1fae5;
    border-left: 3px solid #10b981;
}

.calendar-task.completed {
    background: #f3f4f6;
    border-left: 3px solid #9ca3af;
    opacity: 0.7;
    text-decoration: line-through;
}

.calendar-task.overdue {
    background: #fee2e2;
    border-left: 3px solid #dc2626;
}

.task-priority-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.task-priority-dot.high { background: #ef4444; }
.task-priority-dot.medium { background: #f59e0b; }
.task-priority-dot.low { background: #10b981; }

.task-title {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
}

.more-tasks {
    font-size: 10px;
    color: #6b7280;
    padding: 4px;
    text-align: center;
    cursor: pointer;
    margin-top: 4px;
}

.more-tasks:hover {
    color: #2c7a6e;
}

/* Modal Detail */
.modal-detail {
    max-width: 500px;
}

.task-detail {
    padding: 8px;
}

.task-detail-title {
    font-size: 20px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 16px;
}

.task-detail-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.task-detail-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #6b7280;
}

.task-detail-meta-item i {
    width: 16px;
    color: #2c7a6e;
}

.task-detail-description {
    background: #f9fafb;
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-size: 14px;
    line-height: 1.6;
    color: #374151;
}

.task-detail-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
}

/* Loading */
.loading-spinner {
    text-align: center;
    padding: 60px;
    color: #2c7a6e;
}

.loading-small {
    text-align: center;
    padding: 20px;
    color: #9ca3af;
    font-size: 13px;
}

/* Form */
.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
}

.required {
    color: #ef4444;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #2c7a6e;
    box-shadow: 0 0 0 3px rgba(44, 122, 110, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
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
}

.btn-secondary:hover {
    background: #e5e7eb;
}

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
    margin: 10% auto;
    width: 90%;
    max-width: 500px;
    border-radius: 20px;
    animation: slideDown 0.3s;
}

@keyframes slideDown {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #9ca3af;
}

.modal-body {
    padding: 24px;
}

/* Responsive */
@media (max-width: 900px) {
    .calendar-wrapper {
        flex-direction: column;
    }
    
    .calendar-sidebar {
        width: 100%;
    }
    
    .calendar-legend {
        display: none;
    }
    
    .calendar-task {
        font-size: 9px;
        padding: 4px 6px;
    }
}

@media (max-width: 600px) {
    .calendar-main {
        padding: 16px;
        overflow-x: auto;
    }
    
    .calendar-table {
        min-width: 600px;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentDate = new Date();
let tasksData = {};

// Mini Calendar (Month Picker)
function renderMiniCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const startDay = firstDay.getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    let html = `
        <div class="mini-calendar-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <button class="mini-prev" style="background: none; border: none; cursor: pointer; color: #6b7280;">&lt;</button>
            <span style="font-weight: 600;">${currentDate.toLocaleString('id-ID', { month: 'long', year: 'numeric' })}</span>
            <button class="mini-next" style="background: none; border: none; cursor: pointer; color: #6b7280;">&gt;</button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center;">
    `;
    
    const weekdays = ['S', 'S', 'R', 'K', 'J', 'S', 'M'];
    weekdays.forEach(day => {
        html += `<div style="font-size: 10px; color: #9ca3af; padding: 4px;">${day}</div>`;
    });
    
    for (let i = 0; i < startDay; i++) {
        html += `<div style="padding: 6px; font-size: 12px; color: #d1d5db;"></div>`;
    }
    
    for (let d = 1; d <= daysInMonth; d++) {
        const isToday = d === new Date().getDate() && 
                        year === new Date().getFullYear() && 
                        month === new Date().getMonth();
        html += `
            <div class="mini-date ${isToday ? 'active' : ''}" 
                 data-date="${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}"
                 style="padding: 6px; font-size: 12px; cursor: pointer; border-radius: 50%; ${isToday ? 'background: #2c7a6e; color: white;' : ''}">
                ${d}
            </div>
        `;
    }
    
    html += `</div>`;
    document.getElementById('miniCalendar').innerHTML = html;
    
    // Event listeners for mini calendar
    document.querySelectorAll('.mini-date').forEach(el => {
        el.addEventListener('click', () => {
            const dateStr = el.dataset.date;
            if (dateStr) {
                currentDate = new Date(dateStr);
                loadCalendar();
                renderMiniCalendar();
            }
        });
    });
    
    document.querySelector('.mini-prev')?.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderMiniCalendar();
        loadCalendar();
    });
    
    document.querySelector('.mini-next')?.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderMiniCalendar();
        loadCalendar();
    });
}

// Load tasks for calendar
function loadCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    fetch(`get_calendar_tasks.php?year=${year}&month=${month + 1}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                tasksData = data.tasks;
                renderCalendar(year, month);
                loadUpcomingTasks();
            }
        })
        .catch(error => {
            console.error('Error loading calendar:', error);
        });
}

function renderCalendar(year, month) {
    const firstDay = new Date(year, month, 1);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());
    
    document.getElementById('currentMonthYear').textContent = 
        currentDate.toLocaleString('id-ID', { month: 'long', year: 'numeric' });
    
    let html = `<table class="calendar-table"><thead><tr>`;
    const weekdays = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    weekdays.forEach(day => {
        html += `<th>${day}</th>`;
    });
    html += `</tr></thead><tbody>`;
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    let currentRow = new Date(startDate);
    for (let i = 0; i < 42; i++) {
        if (i % 7 === 0) html += '<tr>';
        
        const cellDate = new Date(currentRow);
        const isCurrentMonth = cellDate.getMonth() === month;
        const isToday = cellDate.toDateString() === today.toDateString();
        const dateKey = formatDateKey(cellDate);
        const dayTasks = tasksData[dateKey] || [];
        
        html += `<td class="calendar-day ${!isCurrentMonth ? 'other-month' : ''} ${isToday ? 'today' : ''}">`;
        html += `<div class="day-header">
                    <span class="day-number">${cellDate.getDate()}</span>
                    <button class="add-task-btn" onclick="openQuickTask('${dateKey}')" title="Tambah tugas">
                        <i class="fas fa-plus"></i>
                    </button>
                 </div>`;
        html += `<div class="day-tasks">`;
        
        // PERBAIKAN: Gunakan dayTasks, bukan visibleTasks
        const visibleTasks = dayTasks.slice(0, 3);
        visibleTasks.forEach(task => {
            const isOverdue = new Date(task.due_date) < today && task.status !== 'completed';
            const priorityClass = task.priority === 'high' ? 'high' : (task.priority === 'medium' ? 'medium' : 'low');
            const categoryIcon = task.category_icon ? `<i class="fas ${task.category_icon}" style="font-size: 8px; color: ${task.category_color}"></i>` : '';
            
            html += `
                <div class="calendar-task ${priorityClass} ${task.status === 'completed' ? 'completed' : ''} ${isOverdue ? 'overdue' : ''}"
                    onclick="event.stopPropagation(); showTaskDetail(${task.id})">
                    ${categoryIcon}
                    <div class="task-priority-dot ${priorityClass}"></div>
                    <span class="task-title">${escapeHtml(task.title)}</span>
                    ${task.due_time ? `<small style="color:#6b7280;">${task.due_time.substring(0,5)}</small>` : ''}
                </div>
            `;
        });
        
        if (dayTasks.length > 3) {
            html += `<div class="more-tasks" onclick="event.stopPropagation(); showMoreTasks('${dateKey}')">
                        +${dayTasks.length - 3} tugas lainnya
                     </div>`;
        }
        
        html += `</div></td>`;
        
        if ((i + 1) % 7 === 0) html += '</tr>';
        currentRow.setDate(currentRow.getDate() + 1);
    }
    
    html += `</tbody></table>`;
    document.getElementById('calendarGrid').innerHTML = html;
}

function loadUpcomingTasks() {
    fetch('get_upcoming_tasks.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.tasks.length > 0) {
                let html = '';
                data.tasks.forEach(task => {
                    const dueDate = new Date(task.due_date);
                    const today = new Date();
                    const diffDays = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
                    let dayText = '';
                    if (diffDays === 0) dayText = 'Hari ini';
                    else if (diffDays === 1) dayText = 'Besok';
                    else if (diffDays < 0) dayText = 'Terlewat';
                    else dayText = `${diffDays} hari lagi`;
                    
                    html += `
                        <div class="upcoming-item" onclick="showTaskDetail(${task.id})">
                            <div class="upcoming-date">
                                <div class="upcoming-day">${dueDate.getDate()}</div>
                                <div class="upcoming-month">${dueDate.toLocaleString('id-ID', { month: 'short' })}</div>
                            </div>
                            <div class="upcoming-info">
                                <div class="upcoming-title">${escapeHtml(task.title)}</div>
                                <div class="upcoming-priority" style="background: ${task.priority === 'high' ? '#fee2e2' : (task.priority === 'medium' ? '#fef3c7' : '#d1fae5')}; color: ${task.priority === 'high' ? '#ef4444' : (task.priority === 'medium' ? '#f59e0b' : '#10b981')}">
                                    ${task.priority}
                                </div>
                            </div>
                            <small style="color: #6b7280; font-size: 10px;">${dayText}</small>
                        </div>
                    `;
                });
                document.getElementById('upcomingTasksList').innerHTML = html;
            } else {
                document.getElementById('upcomingTasksList').innerHTML = '<div class="loading-small">Tidak ada tugas mendatang</div>';
            }
        })
        .catch(error => {
            console.error('Error loading upcoming tasks:', error);
            document.getElementById('upcomingTasksList').innerHTML = '<div class="loading-small">Gagal memuat tugas</div>';
        });
}

function openQuickTask(date) {
    document.getElementById('quickDueDate').value = date;
    document.getElementById('quickTaskForm').reset();
    document.getElementById('quickDueDate').value = date;
    document.getElementById('quickTaskModal').style.display = 'block';
}

function closeQuickTaskModal() {
    document.getElementById('quickTaskModal').style.display = 'none';
}

// Submit quick task
document.getElementById('quickTaskForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('status', 'pending');
    
    fetch('../tasks/add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Toast.fire({ icon: 'success', title: data.message });
            closeQuickTaskModal();
            loadCalendar();
        } else {
            Toast.fire({ icon: 'error', title: data.message });
        }
    })
    .catch(error => {
        Toast.fire({ icon: 'error', title: 'Terjadi kesalahan' });
    });
});

// HANYA SATU FUNGSI showTaskDetail (gunakan yang ini)
function showTaskDetail(taskId) {
    document.getElementById('modalBody').innerHTML = '<div class="loading-spinner">Memuat...</div>';
    document.getElementById('taskModal').style.display = 'block';
    
    fetch(`get_task.php?id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const task = data.task;
                const statusBadge = task.status === 'completed' ? 'Selesai' : (task.status === 'in_progress' ? 'Sedang Dikerjakan' : 'Belum Dikerjakan');
                const statusColor = task.status === 'completed' ? '#10b981' : (task.status === 'in_progress' ? '#f59e0b' : '#ef4444');
                
                // Tampilkan kategori dengan warna dan icon jika ada
                let categoryHtml = '';
                if (task.category_name) {
                    categoryHtml = `
                        <div class="task-detail-meta-item">
                            <i class="fas ${task.category_icon || 'fa-tag'}" style="color: ${task.category_color || '#6b7280'}"></i> 
                            <span style="color: ${task.category_color || '#6b7280'}">${escapeHtml(task.category_name)}</span>
                        </div>
                    `;
                } else {
                    categoryHtml = `
                        <div class="task-detail-meta-item">
                            <i class="fas fa-tag"></i> 
                            Tanpa Kategori
                        </div>
                    `;
                }
                
                // Format priority badge
                let priorityHtml = '';
                if (task.priority === 'high') {
                    priorityHtml = '<span style="background: #fee2e2; color: #ef4444; padding: 2px 8px; border-radius: 20px; font-size: 11px;">🔴 High</span>';
                } else if (task.priority === 'medium') {
                    priorityHtml = '<span style="background: #fef3c7; color: #f59e0b; padding: 2px 8px; border-radius: 20px; font-size: 11px;">🟡 Medium</span>';
                } else {
                    priorityHtml = '<span style="background: #d1fae5; color: #10b981; padding: 2px 8px; border-radius: 20px; font-size: 11px;">🟢 Low</span>';
                }
                
                document.getElementById('modalBody').innerHTML = `
                    <div class="task-detail">
                        <div class="task-detail-title">${escapeHtml(task.title)}</div>
                        <div class="task-detail-meta">
                            <div class="task-detail-meta-item">
                                <i class="fas fa-calendar"></i> 
                                ${task.due_date} ${task.due_time ? task.due_time.substring(0,5) : ''}
                            </div>
                            <div class="task-detail-meta-item">
                                <i class="fas fa-flag"></i> 
                                ${priorityHtml}
                            </div>
                            ${categoryHtml}
                            <div class="task-detail-meta-item">
                                <i class="fas fa-circle" style="color: ${statusColor}"></i> 
                                ${statusBadge}
                            </div>
                        </div>
                        ${task.description ? `<div class="task-detail-description">${escapeHtml(task.description)}</div>` : ''}
                        <div class="task-detail-actions">
                            <button class="btn-secondary" onclick="closeTaskModal()">Tutup</button>
                            <button class="btn-primary" onclick="editTask(${task.id})">Edit Tugas</button>
                        </div>
                    </div>
                `;
            } else {
                document.getElementById('modalBody').innerHTML = `<div class="alert error">${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div class="alert error">Gagal memuat detail tugas</div>';
        });
}

function editTask(taskId) {
    closeTaskModal();
    window.location.href = '../tasks/index.php';
}

function showMoreTasks(date) {
    const dayTasks = tasksData[date] || [];
    let tasksHtml = '<div style="max-height: 400px; overflow-y: auto;">';
    dayTasks.forEach(task => {
        const priorityClass = task.priority === 'high' ? 'high' : (task.priority === 'medium' ? 'medium' : 'low');
        tasksHtml += `
            <div class="calendar-task ${priorityClass}" style="margin-bottom: 8px; cursor: pointer;" onclick="showTaskDetail(${task.id})">
                <div class="task-priority-dot ${priorityClass}"></div>
                <span class="task-title">${escapeHtml(task.title)}</span>
            </div>
        `;
    });
    tasksHtml += '</div>';
    
    Swal.fire({
        title: `Tugas - ${date}`,
        html: tasksHtml,
        customClass: { popup: 'swal-rounded' }
    });
}

function closeTaskModal() {
    document.getElementById('taskModal').style.display = 'none';
}

function formatDateKey(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Navigation
document.getElementById('prevMonthBtn')?.addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderMiniCalendar();
    loadCalendar();
});

document.getElementById('nextMonthBtn')?.addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderMiniCalendar();
    loadCalendar();
});

document.getElementById('todayBtn')?.addEventListener('click', () => {
    currentDate = new Date();
    renderMiniCalendar();
    loadCalendar();
});

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('taskModal');
    const quickModal = document.getElementById('quickTaskModal');
    if (event.target === modal) closeTaskModal();
    if (event.target === quickModal) closeQuickTaskModal();
}

// Toast
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    customClass: { popup: 'swal-rounded' }
});

// Initialize
renderMiniCalendar();
loadCalendar();
</script>

<?php include '../../includes/footer.php'; ?>