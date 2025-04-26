<?php
/**
 * Employee Model
 * 
 * Handles employee data operations and validations
 */
class Employee extends Model {
    protected $table = 'karyawan';
    protected $fillable = [
        'nik',
        'nama_lengkap',
        'tanggal_masuk',
        'tanggal_kontrak',
        'tanggal_hbs_kontrak',
        'status_kerja',
        'masa_kerja',
        'jatah_cuti',
        'jabatan',
        'wilayah',
        'kinerja',
        'tindakan',
        'cat_tindakan',
        'tanggal_lahir',
        'alamat',
        'no_hp'
    ];
    
    /**
     * Get employee with personal data
     */
    public function findWithPersonalData($id) {
        $sql = "SELECT k.*, dp.*
                FROM {$this->table} k
                LEFT JOIN data_personal dp ON k.nama_lengkap = dp.nama_lengkap
                WHERE k.id = ? AND k.deleted_at IS NULL";
        
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Get employees by status
     */
    public function findByStatus($status) {
        return $this->findAll('status_kerja = ?', [$status]);
    }
    
    /**
     * Get employees by location
     */
    public function findByLocation($location) {
        return $this->findAll('wilayah = ?', [$location]);
    }
    
    /**
     * Get employees with expiring contracts
     */
    public function findExpiringContracts($days = 30) {
        $date = date('Y-m-d', strtotime("+{$days} days"));
        
        return $this->findAll(
            'status_kerja = ? AND tanggal_hbs_kontrak <= ? AND tanggal_hbs_kontrak >= CURDATE()',
            ['Kontrak', $date],
            'tanggal_hbs_kontrak ASC'
        );
    }
    
    /**
     * Get employees with birthdays today
     */
    public function findBirthdaysToday() {
        return $this->findAll(
            'DATE_FORMAT(tanggal_lahir, "%m-%d") = DATE_FORMAT(CURDATE(), "%m-%d")'
        );
    }
    
    /**
     * Get employee attendance summary
     */
    public function getAttendanceSummary($id, $month, $year) {
        $sql = "SELECT 
                    COUNT(CASE WHEN status_hadir = 'masuk' THEN 1 END) as hadir,
                    COUNT(CASE WHEN status_hadir = 'ijin' THEN 1 END) as ijin,
                    COUNT(CASE WHEN status_hadir = 'sakit' THEN 1 END) as sakit,
                    COUNT(CASE WHEN status_hadir = 'cuti' THEN 1 END) as cuti,
                    COUNT(CASE WHEN status_hadir = 'lembur' THEN 1 END) as lembur,
                    COUNT(DISTINCT DATE(tanggal)) as total_hari,
                    COUNT(CASE WHEN TIME(waktu) > '08:00:00' AND status_hadir = 'masuk' THEN 1 END) as telat
                FROM absensi
                WHERE karyawan_id = ?
                AND MONTH(tanggal) = ?
                AND YEAR(tanggal) = ?
                AND deleted_at IS NULL";
        
        return $this->db->fetchOne($sql, [$id, $month, $year]);
    }
    
    /**
     * Get employee documents
     */
    public function getDocuments($id) {
        $sql = "SELECT * FROM dokumen_karyawan 
                WHERE karyawan_id = ? AND deleted_at IS NULL
                ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, [$id]);
    }
    
    /**
     * Get employee salary data
     */
    public function getSalaryData($id, $month, $year) {
        // Get earnings
        $sql = "SELECT * FROM pendapatan_gaji 
                WHERE karyawan_id = ? AND bulan = ? AND tahun = ? 
                AND deleted_at IS NULL";
        $earnings = $this->db->fetchOne($sql, [$id, $month, $year]);
        
        // Get deductions
        $sql = "SELECT * FROM potongan_gaji 
                WHERE karyawan_id = ? AND bulan = ? AND tahun = ? 
                AND deleted_at IS NULL";
        $deductions = $this->db->fetchOne($sql, [$id, $month, $year]);
        
        return [
            'earnings' => $earnings,
            'deductions' => $deductions
        ];
    }
    
    /**
     * Get employee activity history
     */
    public function getActivityHistory($id, $limit = 50) {
        $sql = "SELECT * FROM activity_log 
                WHERE (table_name = 'karyawan' AND record_id = ?)
                   OR (table_name = 'dokumen_karyawan' AND record_id IN 
                       (SELECT id FROM dokumen_karyawan WHERE karyawan_id = ?))
                   OR (table_name = 'absensi' AND record_id IN 
                       (SELECT id FROM absensi WHERE karyawan_id = ?))
                ORDER BY created_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$id, $id, $id, $limit]);
    }
    
    /**
     * Validate employee data
     */
    protected function validate() {
        // Required fields
        $required = ['nik', 'nama_lengkap', 'tanggal_masuk', 'status_kerja', 'jabatan', 'wilayah'];
        foreach ($required as $field) {
            if (empty($this->data[$field])) {
                $this->addError($field, $this->getLabel($field) . ' is required');
            }
        }
        
        // NIK format
        if (!empty($this->data['nik'])) {
            if (!preg_match('/^\d{7}$/', $this->data['nik'])) {
                $this->addError('nik', 'NIK must be 7 digits');
            } else {
                // Check unique NIK
                $where = 'nik = ?';
                $params = [$this->data['nik']];
                
                if (!empty($this->data['id'])) {
                    $where .= ' AND id != ?';
                    $params[] = $this->data['id'];
                }
                
                if ($this->db->exists($this->table, $where, $params)) {
                    $this->addError('nik', 'NIK already exists');
                }
            }
        }
        
        // Contract dates validation
        if ($this->data['status_kerja'] === 'Kontrak') {
            if (empty($this->data['tanggal_kontrak'])) {
                $this->addError('tanggal_kontrak', 'Contract start date is required');
            }
            
            if (empty($this->data['tanggal_hbs_kontrak'])) {
                $this->addError('tanggal_hbs_kontrak', 'Contract end date is required');
            }
            
            if (!empty($this->data['tanggal_kontrak']) && !empty($this->data['tanggal_hbs_kontrak'])) {
                if (strtotime($this->data['tanggal_hbs_kontrak']) <= strtotime($this->data['tanggal_kontrak'])) {
                    $this->addError('tanggal_hbs_kontrak', 'Contract end date must be after start date');
                }
            }
        }
        
        // Phone number format
        if (!empty($this->data['no_hp']) && !preg_match('/^\d{10,15}$/', $this->data['no_hp'])) {
            $this->addError('no_hp', 'Invalid phone number format');
        }
        
        return empty($this->errors);
    }
    
    /**
     * After create hook
     */
    protected function afterCreate($id, $data) {
        // Create user account for permanent employees
        if ($data['status_kerja'] === 'Kartap') {
            $username = strtolower(explode(' ', $data['nama_lengkap'])[0]) . $data['nik'];
            $password = md5($data['nik']); // Initial password is NIK
            
            $sql = "INSERT INTO users (username, password, level, karyawan_id)
                    VALUES (?, ?, 'karyawan', ?)";
            execute($sql, [$username, $password, $id]);
        }
        
        return true;
    }
}
