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

// Check permission
if (!isAdmin() && !isHRD()) {
    header('Location: ../../index.php?error=unauthorized');
    exit();
}

// Set page title and breadcrumbs
$pageTitle = 'Data Pegawai';
$breadcrumbs = ['Data Pegawai' => ''];

// Include header
include '../../includes/header.php';
?>

<!-- Main Content -->
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Pegawai</h5>
                    <div>
                        <a href="add.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Tambah Pegawai
                        </a>
                        <button type="button" class="btn btn-success btn-sm" id="exportCsv">
                            <i class="bi bi-file-earmark-excel"></i> Export CSV
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" id="exportPdf">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="filterWilayah">
                                <option value="">Semua Wilayah</option>
                                <option value="Kantor Utama">Kantor Utama</option>
                                <option value="KP Patrol">KP Patrol</option>
                                <option value="KP Cibereng">KP Cibereng</option>
                                <option value="KP Eretan">KP Eretan</option>
                                <option value="KP Widasari">KP Widasari</option>
                                <option value="KP Karangampel">KP Karangampel</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="filterStatus">
                                <option value="">Semua Status</option>
                                <option value="Kontrak">Kontrak</option>
                                <option value="Kartap">Karyawan Tetap</option>
                                <option value="Freelance">Freelance</option>
                                <option value="Magang">Magang</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="filterJabatan">
                                <option value="">Semua Jabatan</option>
                                <!-- Will be populated via JavaScript -->
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" id="searchInput" 
                                   placeholder="Cari nama atau NIK...">
                        </div>
                    </div>

                    <!-- Employee Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="employeeTable">
                            <thead>
                                <tr>
                                    <th>NIK</th>
                                    <th>Nama Lengkap</th>
                                    <th>Jabatan</th>
                                    <th>Wilayah</th>
                                    <th>Status</th>
                                    <th>Masa Kerja</th>
                                    <th>Kontrak</th>
                                    <th>Kinerja</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Will be populated via JavaScript -->
                            </tbody>
                        </table>
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

<!-- View Contract Modal -->
<div class="modal fade" id="contractModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Kontrak</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Will be populated via JavaScript -->
            </div>
        </div>
    </div>
</div>

<?php
// Add custom JavaScript
$extraJS = '<script src="../../assets/js/modules/employees.js"></script>';

// Include footer
include '../../includes/footer.php';
?>
