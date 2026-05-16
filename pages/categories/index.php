<?php
$root_path = dirname(dirname(__DIR__));
require_once $root_path . '/config/config.php';
require_once $root_path . '/config/session.php';
require_once $root_path . '/includes/functions.php';
require_once $root_path . '/classes/Database.php';
require_once $root_path . '/classes/Category.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$category = new Category();

// Ambil semua kategori dengan jumlah tugas
$categories = $category->getCategoriesWithCount($user_id);

$page_title = 'Manajemen Kategori - TaskFlow';
$page_heading = 'Manajemen Kategori';
?>
<?php include '../../includes/header.php'; ?>

<div class="categories-page-container">
    <!-- Header -->
    <div class="page-header">
        <div class="header-info">
            <h1><i class="fas fa-tags"></i> Kategori Tugas</h1>
            <p>Kelompokkan tugas Anda berdasarkan kategori untuk lebih terorganisir</p>
        </div>
        <button type="button" class="btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Kategori Baru
        </button>
    </div>

    <!-- Categories Grid -->
    <div class="categories-grid">
        <?php if(empty($categories)): ?>
            <div class="empty-state">
                <i class="fas fa-tags"></i>
                <h3>Belum ada kategori</h3>
                <p>Buat kategori pertama Anda untuk mengelompokkan tugas</p>
                <button type="button" class="btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Buat Kategori
                </button>
            </div>
        <?php else: ?>
            <?php foreach($categories as $cat): ?>
                <div class="category-card" data-category-id="<?= $cat['id'] ?>">
                    <div class="category-header">
                        <div class="category-color" style="background: <?= htmlspecialchars($cat['color']) ?>">
                            <i class="fas <?= htmlspecialchars($cat['icon'] ?? 'fa-tag') ?>"></i>
                        </div>
                        <div class="category-info">
                            <h3 class="category-name"><?= htmlspecialchars($cat['name']) ?></h3>
                            <span class="category-count">
                                <i class="fas fa-tasks"></i> 
                                <?= $cat['task_count'] ?? 0 ?> tugas
                            </span>
                        </div>
                    </div>
                    
                    <?php if(!empty($cat['description'])): ?>
                        <p class="category-description"><?= htmlspecialchars($cat['description']) ?></p>
                    <?php endif; ?>
                    
                    <div class="category-actions">
                        <button onclick="openEditModal(<?= $cat['id'] ?>)" class="btn-icon" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteCategory(<?= $cat['id'] ?>)" class="btn-icon danger" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal untuk Add/Edit Kategori -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Kategori Baru</h3>
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
/* Categories Page Styles */
.categories-page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    background: white;
    padding: 24px;
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

/* Categories Grid */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.category-card {
    background: white;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    position: relative;
    border: 1px solid #f0f0f0;
}

.category-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.category-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.category-color {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    flex-shrink: 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.category-info {
    flex: 1;
}

.category-name {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.category-count {
    font-size: 12px;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 4px;
}

.category-count i {
    font-size: 11px;
}

.category-description {
    color: #6b7280;
    font-size: 13px;
    line-height: 1.5;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f3f4f6;
}

.category-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    border-radius: 10px;
    color: #6b7280;
    transition: all 0.3s ease;
    font-size: 16px;
}

.btn-icon:hover {
    background: #f3f4f6;
    color: #2c7a6e;
}

.btn-icon.danger:hover {
    background: #fee2e2;
    color: #ef4444;
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 20px;
}

.empty-state i {
    font-size: 64px;
    color: #d1d5db;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 20px;
    margin-bottom: 8px;
    color: #1f2937;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 24px;
}

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
    margin: 10% auto;
    width: 90%;
    max-width: 500px;
    border-radius: 20px;
    animation: slideDown 0.3s;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
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
    border-radius: 20px 20px 0 0;
    background: white;
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

/* Color Picker */
.color-preview {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 8px;
}

.color-box {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    cursor: pointer;
    transition: all 0.3s ease;
}

.color-box:hover {
    transform: scale(1.05);
}

.color-input {
    flex: 1;
}

.color-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 12px;
}

.color-preset {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.color-preset:hover {
    transform: scale(1.1);
}

.color-preset.selected {
    border-color: #1f2937;
    transform: scale(1.05);
}

.icon-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 12px;
}

.icon-preset {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid #e5e7eb;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #6b7280;
}

.icon-preset:hover {
    background: #f3f4f6;
    transform: scale(1.05);
}

.icon-preset.selected {
    border-color: #2c7a6e;
    background: #e6f7f5;
    color: #2c7a6e;
}

.form-actions {
    display: flex;
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

@media (max-width: 768px) {
    .categories-page-container {
        padding: 16px;
    }
    
    .page-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        margin: 20% auto;
        width: 95%;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Custom SweetAlert
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    customClass: {
        popup: 'swal-rounded'
    }
});

// Warna preset untuk kategori
const colorPresets = [
    '#ef4444', '#f59e0b', '#eab308', '#22c55e', '#10b981',
    '#14b8a6', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6',
    '#a855f7', '#d946ef', '#ec4899', '#f43f5e', '#2c7a6e'
];

// Icon preset
const iconPresets = [
    'fa-tag', 'fa-folder', 'fa-book', 'fa-briefcase', 'fa-home',
    'fa-heart', 'fa-star', 'fa-code', 'fa-music', 'fa-gamepad',
    'fa-shopping-cart', 'fa-car', 'fa-plane', 'fa-graduation-cap'
];

let currentEditId = null;

// Fungsi untuk mendapatkan form kategori
function getCategoryForm(categoryData = null) {
    const isEdit = categoryData !== null;
    const defaultColor = categoryData ? categoryData.color : '#3b82f6';
    const defaultIcon = categoryData ? (categoryData.icon || 'fa-tag') : 'fa-tag';
    
    let formHtml = `
        <form id="categoryForm" method="POST">
            <input type="hidden" name="id" id="category_id" value="${isEdit ? categoryData.id : ''}">
            
            <div class="form-group">
                <label for="name">Nama Kategori <span class="required">*</span></label>
                <input type="text" id="name" name="name" class="form-control" 
                       value="${isEdit ? escapeHtml(categoryData.name) : ''}" 
                       placeholder="Contoh: Pekerjaan, Pribadi, Belajar" required>
            </div>
            
            <div class="form-group">
                <label for="color">Warna Kategori</label>
                <div class="color-preview">
                    <div class="color-box" id="colorBox" style="background: ${defaultColor}"></div>
                    <input type="text" id="color" name="color" class="form-control color-input" 
                           value="${defaultColor}" placeholder="#RRGGBB">
                </div>
                <div class="color-presets" id="colorPresets">
    `;
    
    // Tambahkan preset warna
    colorPresets.forEach(color => {
        const isSelected = color === defaultColor;
        formHtml += `
            <div class="color-preset ${isSelected ? 'selected' : ''}" 
                 style="background: ${color}"
                 onclick="selectColor('${color}')"></div>
        `;
    });
    
    formHtml += `
                </div>
            </div>
            
            <div class="form-group">
                <label for="icon">Icon Kategori</label>
                <div class="icon-presets" id="iconPresets">
    `;
    
    // Tambahkan preset icon
    iconPresets.forEach(icon => {
        const isSelected = icon === defaultIcon;
        formHtml += `
            <div class="icon-preset ${isSelected ? 'selected' : ''}" 
                 onclick="selectIcon('${icon}')">
                <i class="fas ${icon}"></i>
            </div>
        `;
    });
    
    formHtml += `
                </div>
                <input type="hidden" id="icon" name="icon" value="${defaultIcon}">
            </div>
            
            <div class="form-group">
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description" class="form-control" rows="3" 
                          placeholder="Deskripsi singkat tentang kategori ini...">${isEdit ? escapeHtml(categoryData.description || '') : ''}</textarea>
            </div>
            
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

function selectColor(color) {
    document.getElementById('color').value = color;
    document.getElementById('colorBox').style.background = color;
    
    // Update selected class pada presets
    document.querySelectorAll('.color-preset').forEach(preset => {
        preset.classList.remove('selected');
        if (preset.style.backgroundColor === color) {
            preset.classList.add('selected');
        }
    });
}

function selectIcon(icon) {
    document.getElementById('icon').value = icon;
    
    // Update selected class pada presets
    document.querySelectorAll('.icon-preset').forEach(preset => {
        preset.classList.remove('selected');
        const presetIcon = preset.querySelector('i').className.split(' ')[1];
        if (presetIcon === icon) {
            preset.classList.add('selected');
        }
    });
}

// Open modal for add category
function openAddModal() {
    currentEditId = null;
    document.getElementById('modalTitle').textContent = 'Tambah Kategori Baru';
    document.getElementById('modalBody').innerHTML = getCategoryForm();
    document.getElementById('categoryModal').style.display = 'block';
    attachFormHandler();
    
    // Setup color picker
    const colorInput = document.getElementById('color');
    const colorBox = document.getElementById('colorBox');
    
    if (colorInput) {
        colorInput.addEventListener('input', function() {
            colorBox.style.background = this.value;
        });
    }
}

// Open modal for edit category
function openEditModal(categoryId) {
    currentEditId = categoryId;
    document.getElementById('modalTitle').textContent = 'Edit Kategori';
    document.getElementById('modalBody').innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';
    document.getElementById('categoryModal').style.display = 'block';
    
    // Fetch category data using get_category.php
    fetch(`get_category.php?id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalBody').innerHTML = getCategoryForm(data.category);
                attachFormHandler();
                
                // Setup color picker
                const colorInput = document.getElementById('color');
                const colorBox = document.getElementById('colorBox');
                
                if (colorInput) {
                    colorInput.addEventListener('input', function() {
                        colorBox.style.background = this.value;
                    });
                }
            } else {
                document.getElementById('modalBody').innerHTML = `<div class="alert error">${data.message}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('modalBody').innerHTML = '<div class="alert error">Gagal memuat data kategori</div>';
        });
}

// Close modal
function closeModal() {
    document.getElementById('categoryModal').style.display = 'none';
    currentEditId = null;
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target == document.getElementById('categoryModal')) {
        closeModal();
    }
}

// Attach form submit handler
function attachFormHandler() {
    const form = document.getElementById('categoryForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validasi nama kategori
        const nameInput = document.getElementById('name');
        if (!nameInput.value.trim()) {
            Toast.fire({
                icon: 'error',
                title: 'Nama kategori tidak boleh kosong!'
            });
            return;
        }
        
        // Validasi format warna
        const colorInput = document.getElementById('color');
        const colorPattern = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;
        if (colorInput.value && !colorPattern.test(colorInput.value)) {
            Toast.fire({
                icon: 'error',
                title: 'Format warna tidak valid! Gunakan format HEX (#RRGGBB)'
            });
            return;
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

// Delete category function
function deleteCategory(categoryId) {
    Swal.fire({
        title: 'Hapus Kategori?',
        text: "Kategori yang dihapus akan menghapus relasi tugas di dalamnya!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'swal-rounded'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: categoryId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Toast.fire({
                        icon: 'success',
                        title: data.message
                    });
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
                    title: 'Terjadi kesalahan'
                });
            });
        }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>