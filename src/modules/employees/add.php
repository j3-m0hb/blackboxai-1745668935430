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
$pageTitle = 'Tambah Pegawai';
$breadcrumbs = [
    'Data Pegawai' => 'list.php',
    'Tambah Pegawai' => ''
];

// Include header
include '../../includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tambah Pegawai Baru</h5>
                </div>
                <div class="card-body">
                    <form id="employeeForm" method="post" enctype="multipart/form-data">
                        <!-- Form Tabs -->
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#employeeData">
                                    <i class="bi bi-person-badge me-1"></i>Data Pegawai
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#personalData">
                                    <i class="bi bi-person-lines-fill me-1"></i>Data Personal
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#documents">
                                    <i class="bi bi-file-earmark-text me-1"></i>Dokumen
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content pt-3">
                            <!-- Employee Data Tab -->
                            <div class="tab-pane fade show active" id="employeeData">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">NIK <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="nik" required
                                               pattern="[0-9]{7}" title="NIK harus 7 digit angka">
                                        <div class="form-text">Format: 7 digit angka</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="nama_lengkap" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Jabatan <span class="text-danger">*</span></label>
                                        <select class="form-select" name="jabatan" required>
                                            <option value="">Pilih Jabatan</option>
                                            <option value="Direksi">Direksi</option>
                                            <option value="Dir. Pelaksana">Dir. Pelaksana</option>
                                            <option value="Manager">Manager</option>
                                            <option value="HRD">HRD</option>
                                            <option value="Cashier">Cashier</option>
                                            <option value="Admin">Admin</option>
                                            <option value="Accounting">Accounting</option>
                                            <option value="IT Support">IT Support</option>
                                            <option value="Driver LT">Driver LT</option>
                                            <option value="Kurir">Kurir</option>
                                            <option value="Outbound">Outbound</option>
                                            <option value="Inbound">Inbound</option>
                                            <option value="Adm. Inbound">Adm. Inbound</option>
                                            <option value="SCO">SCO</option>
                                            <option value="Undel">Undel</option>
                                            <option value="Staff">Staff</option>
                                            <option value="Marketing">Marketing</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Wilayah <span class="text-danger">*</span></label>
                                        <select class="form-select" name="wilayah" required>
                                            <option value="">Pilih Wilayah</option>
                                            <option value="Kantor Utama">Kantor Utama</option>
                                            <option value="KP Patrol">KP Patrol</option>
                                            <option value="KP Cibereng">KP Cibereng</option>
                                            <option value="KP Eretan">KP Eretan</option>
                                            <option value="KP Widasari">KP Widasari</option>
                                            <option value="KP Karangampel">KP Karangampel</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Status Kerja <span class="text-danger">*</span></label>
                                        <select class="form-select" name="status_kerja" required>
                                            <option value="">Pilih Status</option>
                                            <option value="Kontrak">Kontrak</option>
                                            <option value="Kartap">Karyawan Tetap</option>
                                            <option value="Freelance">Freelance</option>
                                            <option value="Magang">Magang</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Masuk <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="tanggal_masuk" required>
                                    </div>

                                    <div class="col-md-6 contract-fields" style="display: none;">
                                        <label class="form-label">Tanggal Kontrak <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="tanggal_kontrak">
                                    </div>

                                    <div class="col-md-6 contract-fields" style="display: none;">
                                        <label class="form-label">Tanggal Habis Kontrak <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="tanggal_hbs_kontrak">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Jatah Cuti <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="jatah_cuti" required min="0" max="999">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Kinerja</label>
                                        <select class="form-select" name="kinerja">
                                            <option value="Baik">Baik</option>
                                            <option value="Sedang">Sedang</option>
                                            <option value="Biasa">Biasa</option>
                                            <option value="Teguran 1">Teguran 1</option>
                                            <option value="Teguran 2">Teguran 2</option>
                                            <option value="Teguran 3">Teguran 3</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Personal Data Tab -->
                            <div class="tab-pane fade" id="personalData">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Tempat Lahir</label>
                                        <input type="text" class="form-control" name="tempat">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Lahir</label>
                                        <input type="date" class="form-control" name="tanggal_lahir">
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Alamat</label>
                                        <textarea class="form-control" name="alamat" rows="3"></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Pendidikan Terakhir</label>
                                        <select class="form-select" name="pend_terakhir">
                                            <option value="">Pilih Pendidikan</option>
                                            <option value="SMP">SMP</option>
                                            <option value="SMA">SMA</option>
                                            <option value="D1">D1</option>
                                            <option value="D2">D2</option>
                                            <option value="D3">D3</option>
                                            <option value="S1">S1</option>
                                            <option value="S2">S2</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status_person">
                                            <option value="">Pilih Status</option>
                                            <option value="Single">Single</option>
                                            <option value="Menikah">Menikah</option>
                                            <option value="Cerai Hidup">Cerai Hidup</option>
                                            <option value="Cerai Mati">Cerai Mati</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Jumlah Anak</label>
                                        <input type="number" class="form-control" name="jumlah_anak" min="0">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Nama Rekening</label>
                                        <input type="text" class="form-control" name="nama_rekening">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Bank</label>
                                        <select class="form-select" name="bank">
                                            <option value="">Pilih Bank</option>
                                            <option value="BANK BCA">BANK BCA</option>
                                            <option value="BANK BNI">BANK BNI</option>
                                            <option value="BANK BRI">BANK BRI</option>
                                            <option value="BANK MANDIRI">BANK MANDIRI</option>
                                            <option value="BANK JABAR BANTEN">BANK JABAR BANTEN</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">No. Rekening</label>
                                        <input type="text" class="form-control" name="no_rekening">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">No. HP</label>
                                        <input type="tel" class="form-control" name="no_handphone">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">No. Darurat</label>
                                        <input type="tel" class="form-control" name="no_darurat">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Pas Foto</label>
                                        <input type="file" class="form-control" name="pas_photo" accept="image/*">
                                        <div class="form-text">Format: JPG, PNG, max 2MB</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Documents Tab -->
                            <div class="tab-pane fade" id="documents">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">KTP</label>
                                        <input type="file" class="form-control" name="doc_ktp" accept=".jpg,.jpeg,.png,.pdf">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Ijazah</label>
                                        <input type="file" class="form-control" name="doc_ijazah" accept=".jpg,.jpeg,.png,.pdf">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">CV</label>
                                        <input type="file" class="form-control" name="doc_cv" accept=".pdf,.doc,.docx">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Surat Lamaran</label>
                                        <input type="file" class="form-control" name="doc_lamaran" accept=".pdf,.doc,.docx">
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Dokumen Lainnya</label>
                                        <input type="file" class="form-control" name="doc_other[]" multiple>
                                        <div class="form-text">Format: PDF, DOC, DOCX, JPG, PNG (max 5 files)</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row mt-3">
                            <div class="col-12 text-end">
                                <a href="list.php" class="btn btn-secondary me-2">Batal</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>Simpan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Add custom JavaScript
$extraJS = '<script src="../../assets/js/modules/employee-form.js"></script>';

// Include footer
include '../../includes/footer.php';
?>
