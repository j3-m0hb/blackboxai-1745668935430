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

try {
    // Get filters
    $wilayah = isset($_GET['wilayah']) ? $_GET['wilayah'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $jabatan = isset($_GET['jabatan']) ? $_GET['jabatan'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';
    
    // Base query
    $baseQuery = "FROM karyawan k 
                  LEFT JOIN data_personal dp ON k.nama_lengkap = dp.nama_lengkap
                  WHERE k.deleted_at IS NULL";
    
    // Add filters
    $params = [];
    
    if ($wilayah) {
        $baseQuery .= " AND k.wilayah = ?";
        $params[] = $wilayah;
    }
    
    if ($status) {
        $baseQuery .= " AND k.status_kerja = ?";
        $params[] = $status;
    }
    
    if ($jabatan) {
        $baseQuery .= " AND k.jabatan = ?";
        $params[] = $jabatan;
    }
    
    if ($search) {
        $baseQuery .= " AND (k.nik LIKE ? OR k.nama_lengkap LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Get data
    $sql = "SELECT 
                k.nik,
                k.nama_lengkap,
                k.jabatan,
                k.wilayah,
                k.status_kerja,
                k.tanggal_masuk,
                k.tanggal_kontrak,
                k.tanggal_hbs_kontrak,
                k.masa_kerja,
                k.jatah_cuti,
                k.kinerja,
                k.tanggal_lahir,
                k.alamat,
                k.no_hp,
                dp.tempat as tempat_lahir,
                dp.pend_terakhir,
                dp.status_person,
                dp.jumlah_anak,
                dp.email,
                dp.nama_rekening,
                dp.bank,
                dp.no_rekening,
                dp.no_handphone,
                dp.no_darurat
            " . $baseQuery . " 
            ORDER BY k.nama_lengkap ASC";
    
    $data = fetchAll($sql, $params);
    
    // Prepare headers
    $headers = [
        'NIK',
        'Nama Lengkap',
        'Jabatan',
        'Wilayah',
        'Status',
        'Tanggal Masuk',
        'Tanggal Kontrak',
        'Tanggal Habis Kontrak',
        'Masa Kerja',
        'Jatah Cuti',
        'Kinerja',
        'Tempat Lahir',
        'Tanggal Lahir',
        'Alamat',
        'Pendidikan',
        'Status',
        'Jumlah Anak',
        'Email',
        'Nama Rekening',
        'Bank',
        'No. Rekening',
        'No. HP',
        'No. Darurat'
    ];
    
    if ($format === 'csv') {
        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="data_pegawai_' . date('Y-m-d_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data
        foreach ($data as $row) {
            $csvRow = [
                $row['nik'],
                $row['nama_lengkap'],
                $row['jabatan'],
                $row['wilayah'],
                $row['status_kerja'],
                $row['tanggal_masuk'],
                $row['tanggal_kontrak'],
                $row['tanggal_hbs_kontrak'],
                $row['masa_kerja'],
                $row['jatah_cuti'],
                $row['kinerja'],
                $row['tempat_lahir'],
                $row['tanggal_lahir'],
                $row['alamat'],
                $row['pend_terakhir'],
                $row['status_person'],
                $row['jumlah_anak'],
                $row['email'],
                $row['nama_rekening'],
                $row['bank'],
                $row['no_rekening'],
                $row['no_handphone'],
                $row['no_darurat']
            ];
            fputcsv($output, $csvRow);
        }
        
        fclose($output);
        
    } else if ($format === 'pdf') {
        // Generate PDF using TCPDF
        require_once '../../vendor/tecnickcom/tcpdf/tcpdf.php';
        
        class MYPDF extends TCPDF {
            public function Header() {
                $this->SetFont('helvetica', 'B', 12);
                $this->Cell(0, 15, 'Data Pegawai PT. Sejahtera Bersama Express', 0, false, 'C', 0, '', 0, false, 'M', 'M');
                $this->Ln(10);
            }
            
            public function Footer() {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 8);
                $this->Cell(0, 10, 'Halaman '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            }
        }
        
        // Create new PDF document
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('PT. Sejahtera Bersama Express');
        $pdf->SetTitle('Data Pegawai');
        
        // Set margins
        $pdf->SetMargins(10, 20, 10);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Add a page
        $pdf->AddPage('L', 'A4');
        
        // Create the table
        $pdf->SetFont('helvetica', '', 8);
        
        // Calculate column widths
        $w = array(20, 35, 25, 25, 20, 25, 25, 25, 20, 15, 20, 25);
        
        // Header
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell($w[0], 7, 'NIK', 1, 0, 'C', true);
        $pdf->Cell($w[1], 7, 'Nama Lengkap', 1, 0, 'C', true);
        $pdf->Cell($w[2], 7, 'Jabatan', 1, 0, 'C', true);
        $pdf->Cell($w[3], 7, 'Wilayah', 1, 0, 'C', true);
        $pdf->Cell($w[4], 7, 'Status', 1, 0, 'C', true);
        $pdf->Cell($w[5], 7, 'Tgl Masuk', 1, 0, 'C', true);
        $pdf->Cell($w[6], 7, 'Tgl Kontrak', 1, 0, 'C', true);
        $pdf->Cell($w[7], 7, 'Tgl Habis', 1, 0, 'C', true);
        $pdf->Cell($w[8], 7, 'Masa Kerja', 1, 0, 'C', true);
        $pdf->Cell($w[9], 7, 'Cuti', 1, 0, 'C', true);
        $pdf->Cell($w[10], 7, 'Kinerja', 1, 0, 'C', true);
        $pdf->Cell($w[11], 7, 'No. HP', 1, 1, 'C', true);
        
        // Data
        $pdf->SetFillColor(255, 255, 255);
        foreach($data as $row) {
            $pdf->Cell($w[0], 6, $row['nik'], 1, 0, 'L');
            $pdf->Cell($w[1], 6, $row['nama_lengkap'], 1, 0, 'L');
            $pdf->Cell($w[2], 6, $row['jabatan'], 1, 0, 'L');
            $pdf->Cell($w[3], 6, $row['wilayah'], 1, 0, 'L');
            $pdf->Cell($w[4], 6, $row['status_kerja'], 1, 0, 'L');
            $pdf->Cell($w[5], 6, date('d/m/Y', strtotime($row['tanggal_masuk'])), 1, 0, 'C');
            $pdf->Cell($w[6], 6, date('d/m/Y', strtotime($row['tanggal_kontrak'])), 1, 0, 'C');
            $pdf->Cell($w[7], 6, date('d/m/Y', strtotime($row['tanggal_hbs_kontrak'])), 1, 0, 'C');
            $pdf->Cell($w[8], 6, $row['masa_kerja'], 1, 0, 'C');
            $pdf->Cell($w[9], 6, $row['jatah_cuti'], 1, 0, 'C');
            $pdf->Cell($w[10], 6, $row['kinerja'], 1, 0, 'L');
            $pdf->Cell($w[11], 6, $row['no_hp'], 1, 1, 'L');
        }
        
        // Output the PDF
        $pdf->Output('data_pegawai_' . date('Y-m-d_His') . '.pdf', 'D');
    }
    
    // Log the activity
    logActivity(
        $_SESSION['user_id'],
        'export',
        'Mengekspor data pegawai ke ' . strtoupper($format),
        'success'
    );

} catch (Exception $e) {
    // Log the error
    error_log("Error in employee export: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
