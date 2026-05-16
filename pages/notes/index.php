<?php
$root_path = dirname(dirname(__DIR__));
require_once $root_path . '/config/config.php';
require_once $root_path . '/config/session.php';
require_once $root_path . '/includes/functions.php';
require_once $root_path . '/classes/Database.php';
require_once $root_path . '/classes/Note.php';
require_once $root_path . '/classes/Task.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$note = new Note();
$task = new Task();

// Ambil semua notes
$notes = $note->getAllNotes($user_id);
$pinnedNotes = $note->getPinnedNotes($user_id);
$recentNotes = $note->getRecentNotes($user_id, 10);

// Ambil tasks untuk dropdown (opsional)
$sql = "SELECT id, title FROM tasks WHERE user_id = ? AND is_archived = 0 ORDER BY due_date ASC";
$db = DB::getInstance();
$tasks = $db->fetchAll($sql, [$user_id]);

$page_title = 'Catatan - TaskFlow';
?>
<?php include '../../includes/header.php'; ?>

<div class="notes-container">
    <!-- Header -->
    <div class="notes-header">
        <div class="header-info">
            <h1><i class="fas fa-sticky-note"></i> Catatan</h1>
            <p>Simpan ide, catatan penting, dan dokumentasi Anda</p>
        </div>
        <button type="button" class="btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Catatan Baru
        </button>
    </div>

    <!-- Search Bar -->
    <div class="search-section">
        <div class="search-box-large">
            <i class="fas fa-search"></i>
            <input type="text" id="searchNotes" placeholder="Cari catatan... (minimal 2 karakter)">
        </div>
        <div class="filter-buttons">
            <button class="filter-btn active" data-type="all">Semua</button>
            <button class="filter-btn" data-type="personal">Personal</button>
            <button class="filter-btn" data-type="work">Work</button>
            <button class="filter-btn" data-type="idea">Idea</button>
            <button class="filter-btn" data-type="task">Task</button>
        </div>
    </div>

    <!-- Pinned Notes Section -->
    <?php if(!empty($pinnedNotes)): ?>
    <div class="notes-section">
        <div class="section-title">
            <i class="fas fa-thumbtack"></i>
            <h2>Catatan Disematkan</h2>
        </div>
        <div class="notes-grid" id="pinnedNotesGrid">
            <?php foreach($pinnedNotes as $pin_note): ?>
                <div class="note-card pinned" data-note-id="<?= $pin_note['id'] ?>" data-type="<?= htmlspecialchars($pin_note['note_type']) ?>">
                    <div class="note-card-header">
                        <div class="note-type-badge type-<?= htmlspecialchars($pin_note['note_type']) ?>">
                            <i class="fas <?= getNoteTypeIcon($pin_note['note_type']) ?>"></i>
                            <?= ucfirst(htmlspecialchars($pin_note['note_type'])) ?>
                        </div>
                        <div class="note-actions">
                            <button onclick="togglePin(<?= $pin_note['id'] ?>)" class="btn-icon" title="Lepas pin">
                                <i class="fas fa-thumbtack"></i>
                            </button>
                            <button onclick="openEditModal(<?= $pin_note['id'] ?>)" class="btn-icon" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteNote(<?= $pin_note['id'] ?>)" class="btn-icon danger" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="note-card-body">
                        <h3 class="note-title"><?= htmlspecialchars($pin_note['title']) ?></h3>
                        <p class="note-preview"><?= htmlspecialchars(substr($pin_note['content'] ?? '', 0, 100)) ?></p>
                    </div>
                    <div class="note-card-footer">
                        <span class="note-date">
                            <i class="fas fa-clock"></i>
                            <?= date('d M Y', strtotime($pin_note['updated_at'])) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- All Notes Section -->
    <div class="notes-section">
        <div class="section-title">
            <i class="fas fa-book"></i>
            <h2>Semua Catatan</h2>
            <span class="note-count" id="noteCount"><?= count($notes) ?> catatan</span>
        </div>
        <div class="notes-grid" id="allNotesGrid">
            <?php if(empty($notes)): ?>
                <div class="empty-state-notes">
                    <i class="fas fa-sticky-note"></i>
                    <h3>Belum ada catatan</h3>
                    <p>Buat catatan pertama Anda untuk menyimpan ide dan informasi penting</p>
                    <button type="button" class="btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Buat Catatan
                    </button>
                </div>
            <?php else: ?>
                <?php foreach($notes as $note_item): ?>
                    <?php if($note_item['is_pinned'] != 1): ?>
                    <div class="note-card" data-note-id="<?= $note_item['id'] ?>" data-type="<?= htmlspecialchars($note_item['note_type']) ?>">
                        <div class="note-card-header">
                            <div class="note-type-badge type-<?= htmlspecialchars($note_item['note_type']) ?>">
                                <i class="fas <?= getNoteTypeIcon($note_item['note_type']) ?>"></i>
                                <?= ucfirst(htmlspecialchars($note_item['note_type'])) ?>
                            </div>
                            <div class="note-actions">
                                <button onclick="togglePin(<?= $note_item['id'] ?>)" class="btn-icon" title="Sematkan">
                                    <i class="fas fa-thumbtack"></i>
                                </button>
                                <button onclick="openEditModal(<?= $note_item['id'] ?>)" class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteNote(<?= $note_item['id'] ?>)" class="btn-icon danger" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="note-card-body">
                            <h3 class="note-title"><?= htmlspecialchars($note_item['title']) ?></h3>
                            <p class="note-preview"><?= htmlspecialchars(substr($note_item['content'] ?? '', 0, 100)) ?></p>
                        </div>
                        <div class="note-card-footer">
                            <span class="note-date">
                                <i class="fas fa-clock"></i>
                                <?= date('d M Y', strtotime($note_item['updated_at'])) ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal untuk Add/Edit Note -->
<div id="noteModal" class="modal">
    <div class="modal-content modal-note">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Catatan Baru</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i> Memuat...
            </div>
        </div>
    </div>
</div>

<?php
// Helper function untuk icon note type
function getNoteTypeIcon($type) {
    $icons = [
        'personal' => 'fa-user',
        'work' => 'fa-briefcase',
        'idea' => 'fa-lightbulb',
        'task' => 'fa-check-circle'
    ];
    return $icons[$type] ?? 'fa-sticky-note';
}
?>

<style>
/* Notes Container */
.notes-container {
    max-width: 1400px;
    margin: 0 auto;
}

/* Header */
.notes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
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

/* Search Section */
.search-section {
    margin-bottom: 32px;
}

.search-box-large {
    position: relative;
    margin-bottom: 16px;
}

.search-box-large i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
}

.search-box-large input {
    width: 100%;
    padding: 14px 16px 14px 46px;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    font-size: 14px;
    background: white;
    transition: all 0.3s;
}

.search-box-large input:focus {
    outline: none;
    border-color: #2c7a6e;
    box-shadow: 0 0 0 3px rgba(44, 122, 110, 0.1);
}

.filter-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 8px 20px;
    border-radius: 40px;
    border: 1px solid #e5e7eb;
    background: white;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 13px;
    color: #6b7280;
}

.filter-btn:hover {
    background: #f3f4f6;
}

.filter-btn.active {
    background: #2c7a6e;
    color: white;
    border-color: #2c7a6e;
}

/* Notes Section */
.notes-section {
    margin-bottom: 48px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e5e7eb;
}

.section-title i {
    font-size: 20px;
    color: #2c7a6e;
}

.section-title h2 {
    font-size: 18px;
    color: #1f2937;
    margin: 0;
}

.note-count {
    margin-left: auto;
    font-size: 13px;
    color: #6b7280;
}

/* Notes Grid */
.notes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.note-card {
    background: white;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
    display: flex;
    flex-direction: column;
}

.note-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.note-card.pinned {
    border-left: 4px solid #f59e0b;
    background: linear-gradient(135deg, #fff9f0 0%, white 100%);
}

.note-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.note-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
}

.type-personal {
    background: #e0f2fe;
    color: #0284c7;
}

.type-work {
    background: #fef3c7;
    color: #d97706;
}

.type-idea {
    background: #fae8ff;
    color: #a855f7;
}

.type-task {
    background: #d1fae5;
    color: #059669;
}

.note-actions {
    display: flex;
    gap: 8px;
}

.btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px;
    border-radius: 8px;
    color: #6b7280;
    transition: all 0.3s;
    font-size: 14px;
}

.btn-icon:hover {
    background: #f3f4f6;
    color: #2c7a6e;
}

.btn-icon.danger:hover {
    background: #fee2e2;
    color: #ef4444;
}

.note-card-body {
    flex: 1;
    margin-bottom: 16px;
}

.note-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.note-preview {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.note-card-footer {
    padding-top: 12px;
    border-top: 1px solid #f3f4f6;
}

.note-date {
    font-size: 11px;
    color: #9ca3af;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Empty State */
.empty-state-notes {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 20px;
}

.empty-state-notes i {
    font-size: 64px;
    color: #d1d5db;
    margin-bottom: 20px;
}

.empty-state-notes h3 {
    font-size: 18px;
    margin-bottom: 8px;
    color: #1f2937;
}

.empty-state-notes p {
    color: #6b7280;
    margin-bottom: 24px;
}

/* Modal */
.modal-note {
    max-width: 600px;
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
    margin: 5% auto;
    width: 90%;
    border-radius: 20px;
    animation: slideDown 0.3s;
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
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
    color: #1f2937;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #9ca3af;
    transition: color 0.3s;
}

.modal-close:hover {
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

/* Form Styles */
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
    font-size: 14px;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #2c7a6e;
    box-shadow: 0 0 0 3px rgba(44, 122, 110, 0.1);
}

textarea.form-control {
    resize: vertical;
    font-family: inherit;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-checkbox input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.form-checkbox label {
    margin: 0;
    cursor: pointer;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
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
}

.btn-secondary:hover {
    background: #e5e7eb;
    color: #374151;
}

/* Alert */
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

/* Responsive */
@media (max-width: 768px) {
    .notes-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
        padding: 20px;
    }
    
    .notes-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        margin: 20% auto;
        width: 95%;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentEditId = null;
let currentFilter = 'all';
let currentSearch = '';

// Fungsi untuk mendapatkan form note
function getNoteForm(noteData = null) {
    const isEdit = noteData !== null;
    const typeOptions = ['personal', 'work', 'idea', 'task'];
    
    let typeOptionsHtml = '';
    typeOptions.forEach(type => {
        const selected = isEdit && noteData.note_type === type ? 'selected' : '';
        const icons = {
            personal: 'fa-user',
            work: 'fa-briefcase',
            idea: 'fa-lightbulb',
            task: 'fa-check-circle'
        };
        typeOptionsHtml += `<option value="${type}" ${selected}>${icons[type]} ${type.charAt(0).toUpperCase() + type.slice(1)}</option>`;
    });
    
    let tasksOptionsHtml = '<option value="">-- Tidak terkait tugas --</option>';
    <?php foreach($tasks as $task_item): ?>
    tasksOptionsHtml += `<option value="<?= $task_item['id'] ?>" ${isEdit && noteData.task_id == <?= $task_item['id'] ?> ? 'selected' : ''}><?= htmlspecialchars($task_item['title']) ?></option>`;
    <?php endforeach; ?>
    
    const formHtml = `
        <form id="noteForm" method="POST">
            <input type="hidden" name="note_id" id="note_id" value="${isEdit ? noteData.id : ''}">
            
            <div class="form-group">
                <label for="title">Judul Catatan <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-control" 
                       value="${isEdit ? escapeHtml(noteData.title) : ''}" 
                       placeholder="Contoh: Ide Proyek Baru" required>
            </div>
            
            <div class="form-group">
                <label for="content">Isi Catatan</label>
                <textarea id="content" name="content" class="form-control" rows="6" 
                          placeholder="Tulis catatan Anda di sini...">${isEdit ? escapeHtml(noteData.content || '') : ''}</textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="note_type">Tipe Catatan</label>
                    <select id="note_type" name="note_type" class="form-control">
                        ${typeOptionsHtml}
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="task_id">Terkait Tugas</label>
                    <select id="task_id" name="task_id" class="form-control">
                        ${tasksOptionsHtml}
                    </select>
                </div>
            </div>
            
            <div class="form-group form-checkbox">
                <input type="checkbox" id="is_pinned" name="is_pinned" value="1" ${isEdit && noteData.is_pinned == 1 ? 'checked' : ''}>
                <label for="is_pinned">Sematkan catatan ini</label>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    `;
    return formHtml;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Open modal for add note
function openAddModal() {
    currentEditId = null;
    document.getElementById('modalTitle').textContent = 'Tambah Catatan Baru';
    document.getElementById('modalBody').innerHTML = getNoteForm();
    document.getElementById('noteModal').style.display = 'block';
    attachFormHandler();
}

// Open modal for edit note
function openEditModal(noteId) {
    currentEditId = noteId;
    document.getElementById('modalTitle').textContent = 'Edit Catatan';
    document.getElementById('modalBody').innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';
    document.getElementById('noteModal').style.display = 'block';
    
    fetch(`get_note.php?id=${noteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalBody').innerHTML = getNoteForm(data.note);
                attachFormHandler();
            } else {
                document.getElementById('modalBody').innerHTML = `<div class="alert error">${data.message}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('modalBody').innerHTML = '<div class="alert error">Gagal memuat data catatan</div>';
        });
}

// Close modal
function closeModal() {
    document.getElementById('noteModal').style.display = 'none';
    currentEditId = null;
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('noteModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Attach form submit handler
function attachFormHandler() {
    const form = document.getElementById('noteForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const titleInput = document.getElementById('title');
        if (!titleInput.value.trim()) {
            Toast.fire({ icon: 'error', title: 'Judul catatan tidak boleh kosong!' });
            return;
        }
        
        const formData = new FormData(form);
        const url = currentEditId ? 'edit_note.php' : 'save_note.php';
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Toast.fire({ icon: 'success', title: data.message });
                closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                Toast.fire({ icon: 'error', title: data.message });
            }
        })
        .catch(error => {
            Toast.fire({ icon: 'error', title: 'Terjadi kesalahan pada server' });
        });
    });
}

// Toggle pin note
// Toggle pin note
function togglePin(noteId) {
    // Tampilkan loading
    Swal.fire({
        title: 'Memproses...',
        text: 'Mohon tunggu',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch('toggle_pin.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: noteId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        Swal.close();
        if (data.success) {
            Toast.fire({ 
                icon: 'success', 
                title: data.message 
            });
            setTimeout(() => location.reload(), 500);
        } else {
            Toast.fire({ 
                icon: 'error', 
                title: data.message || 'Gagal mengubah status pin' 
            });
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Error:', error);
        Toast.fire({ 
            icon: 'error', 
            title: 'Terjadi kesalahan pada server' 
        });
    });
}

// Delete note
function deleteNote(noteId) {
    Swal.fire({
        title: 'Hapus Catatan?',
        text: "Catatan yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        customClass: { popup: 'swal-rounded' }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('delete_note.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: noteId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Toast.fire({ icon: 'success', title: data.message });
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Toast.fire({ icon: 'error', title: data.message });
                }
            });
        }
    });
}

// Search and filter functionality
const searchInput = document.getElementById('searchNotes');
const filterBtns = document.querySelectorAll('.filter-btn');

function filterNotes() {
    const searchTerm = currentSearch.toLowerCase();
    const filterType = currentFilter;
    
    document.querySelectorAll('.note-card').forEach(card => {
        const title = card.querySelector('.note-title')?.textContent.toLowerCase() || '';
        const preview = card.querySelector('.note-preview')?.textContent.toLowerCase() || '';
        const noteType = card.dataset.type;
        
        let matchesSearch = true;
        let matchesFilter = true;
        
        if (searchTerm.length >= 2) {
            matchesSearch = title.includes(searchTerm) || preview.includes(searchTerm);
        }
        
        if (filterType !== 'all') {
            matchesFilter = noteType === filterType;
        }
        
        if (matchesSearch && matchesFilter) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Update visible count
    const visibleCount = document.querySelectorAll('.note-card:not([style*="display: none"])').length;
    const noteCountElem = document.getElementById('noteCount');
    if (noteCountElem) {
        noteCountElem.textContent = `${visibleCount} catatan`;
    }
}

// Search input handler
if (searchInput) {
    searchInput.addEventListener('input', function() {
        currentSearch = this.value;
        filterNotes();
    });
}

// Filter buttons handler
filterBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        filterBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentFilter = this.dataset.type;
        filterNotes();
    });
});

// Toast
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    customClass: { popup: 'swal-rounded' }
});
</script>

<?php include '../../includes/footer.php'; ?>