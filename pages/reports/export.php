<?php
$root_path = dirname(dirname(__DIR__));
require_once $root_path . '/config/session.php';
require_once $root_path . '/config/config.php';
require_once $root_path . '/classes/Database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$export_type = $_GET['type'] ?? 'excel';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'overview';

$db = DB::getInstance();

// Get data for export
$sql = "SELECT t.*, c.name as category_name
        FROM tasks t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.is_archived = 0 AND t.due_date BETWEEN ? AND ?
        ORDER BY t.due_date ASC";
$tasks = $db->fetchAll($sql, [$user_id, $start_date, $end_date]);

// Get statistics
$sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
        FROM tasks 
        WHERE user_id = ? AND is_archived = 0 AND due_date BETWEEN ? AND ?";
$stats = $db->fetch($sql, [$user_id, $start_date, $end_date]);

if ($export_type == 'excel') {
    // Export to Excel (CSV format)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_tugas_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header laporan
    fputcsv($output, ['LAPORAN TUGAS']);
    fputcsv($output, ['Periode: ' . $start_date . ' s/d ' . $end_date]);
    fputcsv($output, ['Tanggal Export: ' . date('d-m-Y H:i:s')]);
    fputcsv($output, []);
    
    // Statistik
    fputcsv($output, ['STATISTIK']);
    fputcsv($output, ['Total Tugas', $stats['total']]);
    fputcsv($output, ['Selesai', $stats['completed']]);
    fputcsv($output, ['Pending', $stats['pending']]);
    fputcsv($output, ['In Progress', $stats['in_progress']]);
    fputcsv($output, ['Tingkat Penyelesaian', $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100, 2) . '%' : '0%']);
    fputcsv($output, []);
    
    // Data tugas
    fputcsv($output, ['DAFTAR TUGAS']);
    fputcsv($output, ['No', 'Judul Tugas', 'Deskripsi', 'Kategori', 'Prioritas', 'Status', 'Deadline', 'Waktu', 'Dibuat']);
    
    $no = 1;
    foreach ($tasks as $task) {
        fputcsv($output, [
            $no++,
            $task['title'],
            strip_tags($task['description'] ?? ''),
            $task['category_name'] ?? 'Tanpa Kategori',
            $task['priority'],
            $task['status'],
            $task['due_date'],
            $task['due_time'] ?? '',
            $task['created_at']
        ]);
    }
    
    fclose($output);
} else {
    // Export to PDF (HTML format with print styles)
    header('Content-Type: text/html');
    header('Content-Disposition: inline; filename="laporan_tugas_' . date('Y-m-d') . '.html"');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Laporan Tugas - TaskFlow</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 40px;
                color: #333;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #2c7a6e;
            }
            .header h1 {
                color: #2c7a6e;
                margin: 0;
            }
            .info {
                margin-bottom: 20px;
                padding: 10px;
                background: #f5f5f5;
                border-radius: 5px;
            }
            .stats {
                display: flex;
                gap: 20px;
                margin-bottom: 30px;
                flex-wrap: wrap;
            }
            .stat-card {
                flex: 1;
                padding: 15px;
                background: #f9fafb;
                border-radius: 10px;
                text-align: center;
                border-left: 4px solid #2c7a6e;
            }
            .stat-number {
                font-size: 28px;
                font-weight: bold;
                color: #2c7a6e;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left;
            }
            th {
                background: #2c7a6e;
                color: white;
            }
            tr:nth-child(even) {
                background: #f9fafb;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 12px;
                color: #999;
                padding-top: 20px;
                border-top: 1px solid #ddd;
            }
            @media print {
                body {
                    margin: 0;
                    padding: 20px;
                }
                .no-print {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="margin-bottom: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #2c7a6e; color: white; border: none; border-radius: 5px; cursor: pointer;">
                🖨️ Cetak / Simpan PDF
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                ✖ Tutup
            </button>
        </div>
        
        <div class="header">
            <h1>TaskFlow - Laporan Tugas</h1>
            <p>Periode: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></p>
        </div>
        
        <div class="info">
            <strong>Tanggal Export:</strong> <?= date('d/m/Y H:i:s') ?>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div>Total Tugas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['completed'] ?></div>
                <div>Selesai</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['pending'] ?></div>
                <div>Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['in_progress'] ?></div>
                <div>In Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100, 1) : 0 ?>%</div>
                <div>Tingkat Penyelesaian</div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul Tugas</th>
                    <th>Kategori</th>
                    <th>Prioritas</th>
                    <th>Status</th>
                    <th>Deadline</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($tasks as $task): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($task['title']) ?></td>
                    <td><?= htmlspecialchars($task['category_name'] ?? '-') ?></td>
                    <td><?= $task['priority'] ?></td>
                    <td><?= str_replace('_', ' ', $task['status']) ?></td>
                    <td><?= $task['due_date'] ?> <?= $task['due_time'] ? date('H:i', strtotime($task['due_time'])) : '' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="footer">
            <p>Dicetak dari TaskFlow - Sistem Manajemen Tugas</p>
        </div>
    </body>
    </html>
    <?php
}
?>