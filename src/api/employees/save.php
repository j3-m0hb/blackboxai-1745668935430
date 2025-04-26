<?php
require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if user has permission
if (!isAdmin() && !isHRD()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

try {
    // Start transaction
    $pdo = getConnection();
    $pdo->beginTransaction();
    
    try {
        // Validate required fields
        $requiredFields = ['nik', 'nama_lengkap', 'jabatan', 'wilayah', 'status_kerja', 'tanggal_masuk', 'jatah_cuti'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Field $field harus diisi");
            }
        }
        
        // Validate NIK format
        if (!preg_match('/^\d{7}$/', $_POST['nik'])) {
            throw new Exception('Format NIK tidak valid');
        }
        
        // Check if NIK already exists
        $sql = "SELECT id FROM karyawan WHERE nik = ? AND deleted_at IS NULL";
        $existing = fetchOne($sql, [$_POST['nik']]);
        if ($existing) {
            throw new Exception('NIK sudah terdaftar');
        }
        
        // Handle photo upload
        $photoPath = null;
        if (isset($_FILES['pas_photo']) && $_FILES['pas_photo']['error'] === UPLOAD_ERR_OK) {
            $photo = $_FILES['pas_photo'];
            
            // Validate photo
            $allowedTypes = ['image/jpeg', 'image/png'];
            if (!in_array($photo['type'], $allowedTypes)) {
                throw new Exception('Format foto tidak valid');
            }
            
            if ($photo['size'] > 2097152) { // 2MB
                throw new Exception('Ukuran foto terlalu besar');
            }
            
            // Generate filename
            $ext = pathinfo($photo['name'], PATHINFO_EXTENSION);
            $filename = $_POST['nik'] . '_photo.' . $ext;
            $photoPath = 'uploads/photos/' . $filename;
            
            // Create directory if not exists
            if (!file_exists(dirname('../../' . $photoPath))) {
                mkdir(dirname('../../' . $photoPath), 0755, true);
            }
            
            // Move uploaded file
            if (!move_uploaded_file($photo['tmp_name'], '../../' . $photoPath)) {
                throw new Exception('Gagal mengupload foto');
            }
        }
        
        // Insert personal data
        $personalData = [
            'nama_lengkap' => $_POST['nama_lengkap'],
            'tempat' => $_POST['tempat'] ?? null,
            'tanggal_lahir' => $_POST['tanggal_lahir'] ?? null,
            'alamat' => $_POST['alamat'] ?? null,
            'pend_terakhir' => $_POST['pend_terakhir'] ?? null,
            'status_person' => $_POST['status_person'] ?? null,
            'jumlah_anak' => $_POST['jumlah_anak'] ?? '0',
            'email' => $_POST['email'] ?? null,
            'nama_rekening' => $_POST['nama_rekening'] ?? null,
            'bank' => $_POST['bank'] ?? null,
            'no_rekening' => $_POST['no_rekening'] ?? null,
            'no_handphone' => $_POST['no_handphone'] ?? null,
            'no_darurat' => $_POST['no_darurat'] ?? null,
            'pas_photo' => $photoPath
        ];
        
        $sql = "INSERT INTO data_personal (
                    nama_lengkap, tempat, tanggal_lahir, alamat, pend_terakhir,
                    status_person, jumlah_anak, email, nama_rekening, bank,
                    no_rekening, no_handphone, no_darurat, pas_photo
                ) VALUES (
                    :nama_lengkap, :tempat, :tanggal_lahir, :alamat, :pend_terakhir,
                    :status_person, :jumlah_anak, :email, :nama_rekening, :bank,
                    :no_rekening, :no_handphone, :no_darurat, :pas_photo
                )";
        
        execute($sql, $personalData);
        
        // Insert employee data
        $employeeData = [
            'nik' => $_POST['nik'],
            'nama_lengkap' => $_POST['nama_lengkap'],
            'tanggal_masuk' => $_POST['tanggal_masuk'],
            'tanggal_kontrak' => $_POST['tanggal_kontrak'] ?? null,
            'tanggal_hbs_kontrak' => $_POST['tanggal_hbs_kontrak'] ?? null,
            'status_kerja' => $_POST['status_kerja'],
            'masa_kerja' => '0y 0m', // Will be calculated by trigger
            'jatah_cuti' => $_POST['jatah_cuti'],
            'jabatan' => $_POST['jabatan'],
            'wilayah' => $_POST['wilayah'],
            'kinerja' => $_POST['kinerja'] ?? 'Baik',
            'tindakan' => $_POST['tindakan'] ?? '',
            'cat_tindakan' => $_POST['cat_tindakan'] ?? '',
            'tanggal_lahir' => $_POST['tanggal_lahir'] ?? null,
            'alamat' => $_POST['alamat'] ?? null,
            'no_hp' => $_POST['no_handphone'] ?? null
        ];
        
        $sql = "INSERT INTO karyawan (
                    nik, nama_lengkap, tanggal_masuk, tanggal_kontrak,
                    tanggal_hbs_kontrak, status_kerja, masa_kerja, jatah_cuti,
                    jabatan, wilayah, kinerja, tindakan, cat_tindakan,
                    tanggal_lahir, alamat, no_hp
                ) VALUES (
                    :nik, :nama_lengkap, :tanggal_masuk, :tanggal_kontrak,
                    :tanggal_hbs_kontrak, :status_kerja, :masa_kerja, :jatah_cuti,
                    :jabatan, :wilayah, :kinerja, :tindakan, :cat_tindakan,
                    :tanggal_lahir, :alamat, :no_hp
                )";
        
        $employeeId = insert($sql, $employeeData);
        
        // Handle document uploads
        $documentTypes = [
            'doc_ktp' => 'KTP',
            'doc_ijazah' => 'Ijazah',
            'doc_cv' => 'CV',
            'doc_lamaran' => 'Surat Lamaran'
        ];
        
        foreach ($documentTypes as $inputName => $docType) {
            if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$inputName];
                
                // Validate file
                $allowedTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg',
                    'image/png'
                ];
                
                if (!in_array($file['type'], $allowedTypes)) {
                    continue; // Skip invalid files
                }
                
                if ($file['size'] > 5242880) { // 5MB
                    continue; // Skip large files
                }
                
                // Generate filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = $_POST['nik'] . '_' . strtolower($docType) . '.' . $ext;
                $filePath = 'uploads/documents/' . $filename;
                
                // Create directory if not exists
                if (!file_exists(dirname('../../' . $filePath))) {
                    mkdir(dirname('../../' . $filePath), 0755, true);
                }
                
                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], '../../' . $filePath)) {
                    // Save document record
                    $sql = "INSERT INTO dokumen_karyawan (
                                karyawan_id, nama_dokumen, jenis_dokumen, file_path
                            ) VALUES (?, ?, ?, ?)";
                    execute($sql, [$employeeId, $file['name'], $docType, $filePath]);
                }
            }
        }
        
        // Handle additional documents
        if (isset($_FILES['doc_other'])) {
            $otherFiles = $_FILES['doc_other'];
            for ($i = 0; $i < count($otherFiles['name']); $i++) {
                if ($otherFiles['error'][$i] === UPLOAD_ERR_OK) {
                    // Similar validation and upload process for other documents
                    $file = [
                        'name' => $otherFiles['name'][$i],
                        'type' => $otherFiles['type'][$i],
                        'tmp_name' => $otherFiles['tmp_name'][$i],
                        'error' => $otherFiles['error'][$i],
                        'size' => $otherFiles['size'][$i]
                    ];
                    
                    // Validate and save the file (similar to above)
                    // ...
                }
            }
        }
        
        // Create user account if status is permanent
        if ($_POST['status_kerja'] === 'Kartap') {
            $username = strtolower(explode(' ', $_POST['nama_lengkap'])[0]) . $_POST['nik'];
            $password = md5($_POST['nik']); // Initial password is NIK
            
            $sql = "INSERT INTO users (username, password, level, karyawan_id)
                    VALUES (?, ?, 'karyawan', ?)";
            execute($sql, [$username, $password, $employeeId]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Log the activity
        logActivity(
            $_SESSION['user_id'],
            'create',
            'Menambah pegawai baru: ' . $_POST['nama_lengkap'] . ' (' . $_POST['nik'] . ')',
            'success',
            'karyawan',
            $employeeId
        );
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Data pegawai berhasil disimpan',
            'data' => [
                'id' => $employeeId,
                'nik' => $_POST['nik'],
                'nama_lengkap' => $_POST['nama_lengkap']
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Log the error
    error_log("Error in employee save: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
