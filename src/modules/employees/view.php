<?php
require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Get employee ID
$employeeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$employeeId) {
    header('Location: list.php');
    exit();
}

// Get employee data
$sql = "SELECT k.*, dp.*
        FROM karyawan k
        LEFT JOIN data_personal dp ON k.nama_lengkap = dp.nama_lengkap
        WHERE k.id = ? AND k.deleted_at IS NULL";

$employee = fetchOne($sql, [$employeeId]);

if (!$employee) {
    header('Location: list.php');
    exit();
}

// Set page title and breadcrumbs
$pageTitle = 'Detail Pegawai: ' . $employee['nama_lengkap'];
$breadcrumbs = [
    'Data Pegawai' => 'list.php',
    'Detail Pegawai' => ''
];

// Include header
include '../../includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <!-- Employee Profile Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <!-- Photo Column -->
                        <div class="col-md-3 text-center">
                            <?php if ($employee['pas_photo']): ?>
                                <img src="../../<?php echo htmlspecialchars($employee['pas_photo']); ?>" 
                                     alt="Foto <?php echo htmlspecialchars($employee['nama_lengkap']); ?>"
                                     class="img-thumbnail mb-3" style="max-width: 200px;">
                            <?php else: ?>
                                <div class="placeholder-image mb-3">
                                    <i class="bi bi-person-circle display-1"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2">
                                <a href="edit.php?id=<?php echo $employeeId; ?>" class="btn btn-warning btn-sm">
                                    <i class="bi bi-pencil me-1"></i>Edit Data
                                </a>
                                <?php if (isAdmin() || isHRD()): ?>
                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                        <i class="bi bi-trash me-1"></i>Hapus Data
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Info Column -->
                        <div class="col-md-9">
                            <h4><?php echo htmlspecialchars($employee['nama_lengkap']); ?></h4>
                            <p class="text-muted mb-4">
                                <?php echo htmlspecialchars($employee['jabatan']); ?> - 
                                <?php echo htmlspecialchars($employee['wilayah']); ?>
                            </p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="150">NIK</th>
                                            <td><?php echo htmlspecialchars($employee['nik']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $employee['status_kerja'] === 'Kartap' ? 'success' : 
                                                         ($employee['status_kerja'] === 'Kontrak' ? 'warning' : 'info'); 
                                                ?>">
                                                    <?php echo htmlspecialchars($employee['status_kerja']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Masuk</th>
                                            <td><?php echo date('d/m/Y', strtotime($employee['tanggal_masuk'])); ?></td>
                                        </tr>
                                        <?php if ($employee['status_kerja'] === 'Kontrak'): ?>
                                            <tr>
                                                <th>Periode Kontrak</th>
                                                <td>
                                                    <?php 
                                                    echo date('d/m/Y', strtotime($employee['tanggal_kontrak'])) . ' - ' . 
                                                         date('d/m/Y', strtotime($employee['tanggal_hbs_kontrak']));
                                                    
                                                    $daysRemaining = floor((strtotime($employee['tanggal_hbs_kontrak']) - time()) / (60 * 60 * 24));
                                                    $badgeClass = $daysRemaining <= 30 ? 'danger' : 
                                                                ($daysRemaining <= 90 ? 'warning' : 'success');
                                                    ?>
                                                    <span class="badge bg-<?php echo $badgeClass; ?>">
                                                        <?php echo $daysRemaining; ?> hari tersisa
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <th>Masa Kerja</th>
                                            <td><?php echo htmlspecialchars($employee['masa_kerja']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Jatah Cuti</th>
                                            <td><?php echo htmlspecialchars($employee['jatah_cuti']); ?> hari</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="150">Tempat, Tgl Lahir</th>
                                            <td>
                                                <?php 
                                                echo htmlspecialchars($employee['tempat']) . ', ' . 
                                                     date('d/m/Y', strtotime($employee['tanggal_lahir']));
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Alamat</th>
                                            <td><?php echo nl2br(htmlspecialchars($employee['alamat'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>No. HP</th>
                                            <td><?php echo htmlspecialchars($employee['no_handphone']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Additional Information Tabs -->
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#personal">
                                <i class="bi bi-person me-1"></i>Data Personal
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#documents">
                                <i class="bi bi-file-earmark-text me-1"></i>Dokumen
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#attendance">
                                <i class="bi bi-calendar-check me-1"></i>Kehadiran
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#history">
                                <i class="bi bi-clock-history me-1"></i>Riwayat
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content pt-3">
                        <!-- Personal Data Tab -->
                        <div class="tab-pane fade show active" id="personal">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="200">Pendidikan Terakhir</th>
                                            <td><?php echo htmlspecialchars($employee['pend_terakhir']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td><?php echo htmlspecialchars($employee['status_person']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Jumlah Anak</th>
                                            <td><?php echo htmlspecialchars($employee['jumlah_anak']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>No. Darurat</th>
                                            <td><?php echo htmlspecialchars($employee['no_darurat']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="200">Nama Rekening</th>
                                            <td><?php echo htmlspecialchars($employee['nama_rekening']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Bank</th>
                                            <td><?php echo htmlspecialchars($employee['bank']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>No. Rekening</th>
                                            <td><?php echo htmlspecialchars($employee['no_rekening']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Documents Tab -->
                        <div class="tab-pane fade" id="documents">
                            <div class="table-responsive">
                                <table class="table table-hover" id="documentsTable">
                                    <thead>
                                        <tr>
                                            <th>Nama Dokumen</th>
                                            <th>Jenis</th>
                                            <th>Tanggal Upload</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Will be populated via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if (isAdmin() || isHRD()): ?>
                                <button type="button" class="btn btn-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                    <i class="bi bi-upload me-1"></i>Upload Dokumen
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Attendance Tab -->
                        <div class="tab-pane fade" id="attendance">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <select class="form-select form-select-sm" id="attendanceMonth">
                                        <?php
                                        for ($i = 1; $i <= 12; $i++) {
                                            $selected = $i == date('n') ? 'selected' : '';
                                            echo "<option value=\"$i\" $selected>" . date('F', mktime(0, 0, 0, $i, 1)) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select form-select-sm" id="attendanceYear">
                                        <?php
                                        $currentYear = date('Y');
                                        for ($i = $currentYear - 1; $i <= $currentYear + 1; $i++) {
                                            $selected = $i == $currentYear ? 'selected' : '';
                                            echo "<option value=\"$i\" $selected>$i</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover" id="attendanceTable">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Status</th>
                                            <th>Waktu</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Will be populated via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- History Tab -->
                        <div class="tab-pane fade" id="history">
                            <div class="timeline">
                                <!-- Will be populated via AJAX -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus data pegawai ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Hapus</button>
            </div>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Dokumen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Jenis Dokumen</label>
                        <select class="form-select" name="jenis_dokumen" required>
                            <option value="">Pilih Jenis</option>
                            <option value="KTP">KTP</option>
                            <option value="Ijazah">Ijazah</option>
                            <option value="CV">CV</option>
                            <option value="Surat Lamaran">Surat Lamaran</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File</label>
                        <input type="file" class="form-control" name="file" required>
                        <div class="form-text">Format: PDF, DOC, DOCX, JPG, PNG (max 5MB)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Add custom JavaScript
$extraJS = '
<script>
    const employeeId = ' . $employeeId . ';
</script>
<script src="../../assets/js/modules/employee-view.js"></script>
';

// Include footer
include '../../includes/footer.php';
?>
