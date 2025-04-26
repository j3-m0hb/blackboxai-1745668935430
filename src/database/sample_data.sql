-- Sample data for testing

-- Insert sample users
INSERT INTO users (username, password, level) VALUES
('admin', 'a66abb5684c45962d887564f08346e8d', 'admin'),
('hrd', 'a66abb5684c45962d887564f08346e8d', 'hrd'),
('karyawan1', 'a66abb5684c45962d887564f08346e8d', 'karyawan');

-- Insert sample employee data
INSERT INTO karyawan (nik, nama_lengkap, tanggal_masuk, tanggal_kontrak, tanggal_hbs_kontrak, status_kerja, masa_kerja, jatah_cuti, jabatan, wilayah, kinerja, tindakan, cat_tindakan, tanggal_lahir, alamat, no_hp) VALUES
('2025001', 'John Doe', '2025-01-01', '2025-01-01', '2026-01-01', 'Kontrak', '0y 0m', '12', 'Manager', 'Kantor Utama', 'Baik', '', '', '1990-05-15', 'Jl. Contoh No. 1', '081234567890'),
('2025002', 'Jane Smith', '2025-01-01', '2025-01-01', '2026-01-01', 'Kontrak', '0y 0m', '12', 'Admin', 'KP Patrol', 'Baik', '', '', '1992-08-20', 'Jl. Sample No. 2', '081234567891'),
('2025003', 'Bob Wilson', '2025-01-01', '2025-01-01', '2025-07-01', 'Kontrak', '0y 0m', '12', 'Kurir', 'KP Cibereng', 'Baik', '', '', '1995-03-10', 'Jl. Test No. 3', '081234567892');

-- Insert sample personal data
INSERT INTO data_personal (nama_lengkap, tempat, tanggal_lahir, alamat, pend_terakhir, status_person, jumlah_anak, email, nama_rekening, bank, no_rekening, no_handphone, no_darurat, pas_photo) VALUES
('John Doe', 'Jakarta', '1990-05-15', 'Jl. Contoh No. 1', 'S1', 'Menikah', '2', 'john@example.com', 'John Doe', 'BANK BCA', '1234567890', '081234567890', '081234567899', 'default.jpg'),
('Jane Smith', 'Bandung', '1992-08-20', 'Jl. Sample No. 2', 'S1', 'Single', '0', 'jane@example.com', 'Jane Smith', 'BANK BNI', '0987654321', '081234567891', '081234567898', 'default.jpg'),
('Bob Wilson', 'Surabaya', '1995-03-10', 'Jl. Test No. 3', 'SMA', 'Menikah', '1', 'bob@example.com', 'Bob Wilson', 'BANK BRI', '1122334455', '081234567892', '081234567897', 'default.jpg');

-- Insert sample attendance data
INSERT INTO absensi (karyawan_id, tanggal, waktu, status_hadir, keterangan) VALUES
(1, CURDATE(), '08:00:00', 'masuk', 'Tepat waktu'),
(2, CURDATE(), '08:15:00', 'masuk', 'Terlambat 15 menit'),
(3, CURDATE(), '00:00:00', 'ijin', 'Urusan keluarga');

-- Insert sample monthly attendance
INSERT INTO absensi_bulanan (karyawan_id, bulan, tahun, izin, sakit, alpha, cuti) VALUES
(1, MONTH(CURDATE()), YEAR(CURDATE()), 1, 0, 0, 1),
(2, MONTH(CURDATE()), YEAR(CURDATE()), 0, 1, 0, 0),
(3, MONTH(CURDATE()), YEAR(CURDATE()), 2, 0, 0, 0);

-- Insert sample salary earnings
INSERT INTO pendapatan_gaji (karyawan_id, bulan, tahun, gaji_pokok, tunjangan_prestasi, tunjangan_jabatan, tunjangan_kdk, tunjangan_prest25) VALUES
(1, MONTH(CURDATE()), YEAR(CURDATE()), 5000000.00, 500000.00, 1000000.00, 300000.00, 250000.00),
(2, MONTH(CURDATE()), YEAR(CURDATE()), 4000000.00, 400000.00, 500000.00, 300000.00, 200000.00),
(3, MONTH(CURDATE()), YEAR(CURDATE()), 3500000.00, 350000.00, 300000.00, 300000.00, 175000.00);

-- Insert sample salary deductions
INSERT INTO potongan_gaji (karyawan_id, bulan, tahun, bpjs_naker, bpjs_kesehatan, denda_telat, potongan_kasbon, potongan_klaim, potongan_sp) VALUES
(1, MONTH(CURDATE()), YEAR(CURDATE()), 100000.00, 100000.00, 0.00, 0.00, 0.00, 0.00),
(2, MONTH(CURDATE()), YEAR(CURDATE()), 80000.00, 80000.00, 50000.00, 0.00, 0.00, 0.00),
(3, MONTH(CURDATE()), YEAR(CURDATE()), 70000.00, 70000.00, 0.00, 100000.00, 0.00, 0.00);

-- Insert sample documents
INSERT INTO dokumen_karyawan (karyawan_id, nama_dokumen, jenis_dokumen, file_path) VALUES
(1, 'KTP', 'Identitas', 'documents/ktp_2025001.jpg'),
(1, 'Ijazah', 'Pendidikan', 'documents/ijazah_2025001.pdf'),
(2, 'KTP', 'Identitas', 'documents/ktp_2025002.jpg'),
(3, 'KTP', 'Identitas', 'documents/ktp_2025003.jpg');

-- Insert sample activity logs
INSERT INTO activity_log (user_id, activity_type, description, status, ip_address) VALUES
(1, 'login', 'Login berhasil', 'success', '127.0.0.1'),
(2, 'view', 'Melihat daftar karyawan', 'success', '127.0.0.1'),
(3, 'update', 'Update data personal', 'success', '127.0.0.1');
