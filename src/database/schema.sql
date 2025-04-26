-- Database schema for kepeg_sbe

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL COMMENT 'Password hash (using MD5)',
    level ENUM('admin', 'hrd', 'karyawan') NOT NULL COMMENT 'Level akses pengguna',
    karyawan_id INT DEFAULT NULL COMMENT 'Link ke tabel karyawan jika level karyawan',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Timestamp soft delete',
    PRIMARY KEY (id),
    UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employee data table
CREATE TABLE IF NOT EXISTS karyawan (
    id INT NOT NULL AUTO_INCREMENT,
    nik VARCHAR(20) NOT NULL COMMENT 'Nomor Induk Karyawan',
    nama_lengkap VARCHAR(100) NOT NULL,
    tanggal_masuk DATE DEFAULT NULL COMMENT 'Tanggal bergabung/masuk kerja',
    tanggal_kontrak DATE DEFAULT NULL COMMENT 'Tanggal Mulai Kontrak Kerja',
    tanggal_hbs_kontrak DATE DEFAULT NULL COMMENT 'Tanggal Habis Kontrak Kerja',
    status_kerja ENUM('Kontrak','Kartap','Freelance','Magang','MD','PHK','Tambahan1','Tambahan2') DEFAULT NULL,
    masa_kerja VARCHAR(50) NOT NULL,
    jatah_cuti VARCHAR(3) NOT NULL,
    jabatan ENUM('Direksi','Dir. Pelaksana','Manager','HRD','Cashier','Admin','Accounting','IT Support','Driver LT','Kurir','Outbound','Inbound','Adm. Inbound','SCO','Undel','Staff','Marketing','xxx8','xxx7','xxx6','xxx5','xxx4','xxx3','xxx2','xxx1') DEFAULT NULL,
    wilayah ENUM('Kantor Utama','KP Patrol','KP Cibereng','KP Eretan','KP Widasari','KP Karangampel') DEFAULT NULL,
    kinerja ENUM('Baik','Sedang','Biasa','Teguran 1','Teguran 2','Teguran 3') NOT NULL,
    tindakan VARCHAR(255) NOT NULL,
    cat_tindakan VARCHAR(255) NOT NULL COMMENT 'Catatan Mediasi',
    tanggal_lahir DATE DEFAULT NULL,
    alamat TEXT DEFAULT NULL,
    no_hp VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY nik (nik)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Personal data table
CREATE TABLE IF NOT EXISTS data_personal (
    id_person INT NOT NULL AUTO_INCREMENT,
    nama_lengkap VARCHAR(50) NOT NULL,
    tempat VARCHAR(20) NOT NULL,
    tanggal_lahir DATE NOT NULL,
    alamat VARCHAR(100) NOT NULL,
    pend_terakhir ENUM('SMP','SMA','D1','D2','D3','S1','S2') NOT NULL,
    status_person ENUM('Single','Menikah','Cerai Hidup','Cerai Mati','') NOT NULL,
    jumlah_anak VARCHAR(5) NOT NULL,
    email VARCHAR(50) NOT NULL,
    nama_rekening VARCHAR(50) NOT NULL,
    bank ENUM('BANK BCA','BANK BNI','BANK BRI','BANK MANDIRI','BANK JABAR BANTEN') NOT NULL,
    no_rekening VARCHAR(50) NOT NULL,
    no_handphone VARCHAR(15) NOT NULL,
    no_darurat VARCHAR(15) NOT NULL,
    pas_photo VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_person)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Attendance table
CREATE TABLE IF NOT EXISTS absensi (
    id INT NOT NULL AUTO_INCREMENT,
    karyawan_id INT NOT NULL,
    tanggal DATE NOT NULL,
    waktu TIME NOT NULL,
    status_hadir ENUM('masuk','pulang','ijin','sakit','lembur','cuti') NOT NULL,
    keterangan TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY karyawan_id (karyawan_id),
    CONSTRAINT absensi_ibfk_1 FOREIGN KEY (karyawan_id) REFERENCES karyawan (id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Monthly attendance summary
CREATE TABLE IF NOT EXISTS absensi_bulanan (
    id INT NOT NULL AUTO_INCREMENT,
    karyawan_id INT DEFAULT NULL,
    bulan INT(11) DEFAULT NULL,
    tahun INT(11) DEFAULT NULL,
    izin INT(11) DEFAULT 0,
    sakit INT(11) DEFAULT 0,
    alpha INT(11) DEFAULT 0,
    cuti INT(11) DEFAULT 0,
    PRIMARY KEY (id),
    KEY karyawan_id (karyawan_id),
    CONSTRAINT absensi_bulanan_ibfk_1 FOREIGN KEY (karyawan_id) REFERENCES karyawan (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Salary earnings table
CREATE TABLE IF NOT EXISTS pendapatan_gaji (
    id INT NOT NULL AUTO_INCREMENT,
    karyawan_id INT NOT NULL,
    bulan INT(11) NOT NULL,
    tahun INT(11) NOT NULL,
    gaji_pokok DECIMAL(15,2) DEFAULT 0.00,
    tunjangan_prestasi DECIMAL(15,2) DEFAULT 0.00,
    tunjangan_jabatan DECIMAL(15,2) DEFAULT 0.00,
    tunjangan_kdk DECIMAL(15,2) DEFAULT 0.00,
    tunjangan_prest25 DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_pendapatan_karyawan_bulan_tahun (karyawan_id,bulan,tahun),
    CONSTRAINT pendapatan_gaji_ibfk_1 FOREIGN KEY (karyawan_id) REFERENCES karyawan (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Salary deductions table
CREATE TABLE IF NOT EXISTS potongan_gaji (
    id INT NOT NULL AUTO_INCREMENT,
    karyawan_id INT NOT NULL,
    bulan INT(11) NOT NULL,
    tahun INT(11) NOT NULL,
    bpjs_naker DECIMAL(15,2) DEFAULT 0.00,
    bpjs_kesehatan DECIMAL(15,2) DEFAULT 0.00,
    denda_telat DECIMAL(15,2) DEFAULT 0.00,
    potongan_kasbon DECIMAL(15,2) DEFAULT 0.00,
    potongan_klaim DECIMAL(15,2) DEFAULT 0.00,
    potongan_sp DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_potongan_karyawan_bulan_tahun (karyawan_id,bulan,tahun),
    CONSTRAINT potongan_gaji_ibfk_1 FOREIGN KEY (karyawan_id) REFERENCES karyawan (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employee documents table
CREATE TABLE IF NOT EXISTS dokumen_karyawan (
    id INT NOT NULL AUTO_INCREMENT,
    karyawan_id INT NOT NULL,
    nama_dokumen VARCHAR(255) NOT NULL,
    jenis_dokumen VARCHAR(100) DEFAULT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY karyawan_id (karyawan_id),
    CONSTRAINT dokumen_karyawan_ibfk_1 FOREIGN KEY (karyawan_id) REFERENCES karyawan (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity log table
CREATE TABLE IF NOT EXISTS activity_log (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    activity_type VARCHAR(50) NOT NULL,
    page_url VARCHAR(255) DEFAULT NULL,
    table_name VARCHAR(100) DEFAULT NULL,
    record_id INT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    status VARCHAR(20) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    CONSTRAINT activity_log_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
