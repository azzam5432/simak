<?php
// ============================================
// controllers/MahasiswaController.php
// Logika dashboard, jadwal, IRS, KHS, tugas, presensi
// ============================================

class MahasiswaController {
    private $pdo;
    private $mahasiswa_id;
    
    public function __construct($pdo, $mahasiswa_id = null) {
        $this->pdo = $pdo;
        $this->mahasiswa_id = $mahasiswa_id;
    }
    
    // ============================================
    // DASHBOARD
    // ============================================
    
    public function getDashboardStats() {
        $stats = [];
        
        // Total SKS
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(c.sks), 0) as total_sks
            FROM irs
            JOIN courses c ON irs.course_id = c.id
            WHERE irs.mahasiswa_id = ? AND irs.status = 'approved'
        ");
        $stmt->execute([$this->mahasiswa_id]);
        $stats['total_sks'] = $stmt->fetch()['total_sks'] ?? 0;
        
        // Total Mata Kuliah
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total
            FROM irs
            WHERE mahasiswa_id = ? AND status = 'approved'
        ");
        $stmt->execute([$this->mahasiswa_id]);
        $stats['total_matakuliah'] = $stmt->fetch()['total'] ?? 0;
        
        // Tugas Mendatang (7 hari)
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total
            FROM tasks t
            JOIN irs i ON t.course_id = i.course_id
            WHERE i.mahasiswa_id = ? 
                AND t.deadline > NOW() 
                AND t.deadline < DATE_ADD(NOW(), INTERVAL 7 DAY)
                AND i.status = 'approved'
        ");
        $stmt->execute([$this->mahasiswa_id]);
        $stats['tugas_mendatang'] = $stmt->fetch()['total'] ?? 0;
        
        // IPK
        $stats['ipk'] = $this->hitungIPK();
        
        // Pengumuman belum dibaca
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total
            FROM announcements a
            LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id 
                AND ar.user_id = (SELECT user_id FROM mahasiswa WHERE id = ?)
            WHERE a.target_role IN ('all', 'mahasiswa')
                AND ar.id IS NULL
        ");
        $stmt->execute([$this->mahasiswa_id]);
        $stats['belum_dibaca'] = $stmt->fetch()['total'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Get announcements dengan limit
     * PERBAIKAN: LIMIT menggunakan intval untuk keamanan
     */
    public function getAnnouncements($limit = 5) {
        // PERBAIKAN: Cast limit ke integer untuk keamanan
        $limit = intval($limit);
        if ($limit < 1) $limit = 5;
        
        $stmt = $this->pdo->prepare("
            SELECT a.*, u.nama as sender_name, u.role as sender_role,
                   CASE WHEN ar.id IS NOT NULL THEN 1 ELSE 0 END as is_read
            FROM announcements a
            JOIN users u ON a.sender_id = u.id
            LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id 
                AND ar.user_id = (SELECT user_id FROM mahasiswa WHERE id = ?)
            WHERE a.target_role IN ('all', 'mahasiswa')
            ORDER BY a.created_at DESC
            LIMIT " . $limit . "  -- ← PERBAIKAN: LIMIT langsung di SQL
        ");
        $stmt->execute([$this->mahasiswa_id]);
        return $stmt->fetchAll();
    }
    
    // ============================================
    // JADWAL
    // ============================================
    
    public function getJadwalKuliah() {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.nama as dosen_nama, d.nidn
            FROM irs
            JOIN courses c ON irs.course_id = c.id
            LEFT JOIN dosen d ON c.dosen_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE irs.mahasiswa_id = ? AND irs.status = 'approved'
            ORDER BY FIELD(c.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), c.jam_mulai
        ");
        $stmt->execute([$this->mahasiswa_id]);
        return $stmt->fetchAll();
    }
    
    public function getJadwalHariIni() {
        $hari = $this->getHariIndonesia(date('N'));
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.nama as dosen_nama
            FROM irs
            JOIN courses c ON irs.course_id = c.id
            LEFT JOIN dosen d ON c.dosen_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE irs.mahasiswa_id = ? 
                AND irs.status = 'approved'
                AND c.hari = ?
            ORDER BY c.jam_mulai ASC
        ");
        $stmt->execute([$this->mahasiswa_id, $hari]);
        return $stmt->fetchAll();
    }
    
    private function getHariIndonesia($dayNumber) {
        $hari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 
                 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        return $hari[$dayNumber] ?? 'Senin';
    }
    
    // ============================================
    // TUGAS
    // ============================================
    
    public function getAllTugas() {
        $stmt = $this->pdo->prepare("
            SELECT t.*, c.kode_mk, c.nama_mk,
                   ts.id as submission_id, ts.status as submission_status,
                   ts.nilai as submission_nilai, ts.submitted_at,
                   CASE 
                       WHEN t.deadline < NOW() THEN 'expired'
                       WHEN ts.id IS NOT NULL AND ts.status = 'submitted' THEN 'submitted'
                       WHEN t.deadline < DATE_ADD(NOW(), INTERVAL 1 DAY) THEN 'urgent'
                       ELSE 'active'
                   END as status_tugas,
                   TIMESTAMPDIFF(HOUR, NOW(), t.deadline) as jam_tersisa
            FROM tasks t
            JOIN courses c ON t.course_id = c.id
            JOIN irs i ON t.course_id = i.course_id
            LEFT JOIN task_submissions ts ON t.id = ts.task_id AND ts.mahasiswa_id = ?
            WHERE i.mahasiswa_id = ? AND i.status = 'approved'
            ORDER BY t.deadline ASC
        ");
        $stmt->execute([$this->mahasiswa_id, $this->mahasiswa_id]);
        return $stmt->fetchAll();
    }
    
    public function getTugasMendekat() {
        $stmt = $this->pdo->prepare("
            SELECT t.*, c.kode_mk, c.nama_mk,
                   ts.id as submission_id, ts.status as submission_status,
                   TIMESTAMPDIFF(HOUR, NOW(), t.deadline) as jam_tersisa
            FROM tasks t
            JOIN courses c ON t.course_id = c.id
            JOIN irs i ON t.course_id = i.course_id
            LEFT JOIN task_submissions ts ON t.id = ts.task_id AND ts.mahasiswa_id = ?
            WHERE i.mahasiswa_id = ? 
                AND i.status = 'approved'
                AND t.deadline > NOW()
                AND t.deadline < DATE_ADD(NOW(), INTERVAL 2 DAY)
                AND (ts.id IS NULL OR ts.status != 'submitted')
            ORDER BY t.deadline ASC
        ");
        $stmt->execute([$this->mahasiswa_id, $this->mahasiswa_id]);
        return $stmt->fetchAll();
    }
    
    public function submitTugas($task_id, $file_path = null, $catatan = null) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM task_submissions 
                WHERE task_id = ? AND mahasiswa_id = ?
            ");
            $stmt->execute([$task_id, $this->mahasiswa_id]);
            
            if ($stmt->fetch()) {
                $sql = "UPDATE task_submissions SET status = 'submitted', submitted_at = NOW()";
                $params = [];
                
                if ($file_path) {
                    $sql .= ", file_path = ?";
                    $params[] = $file_path;
                }
                if ($catatan) {
                    $sql .= ", catatan = ?";
                    $params[] = $catatan;
                }
                
                $sql .= " WHERE task_id = ? AND mahasiswa_id = ?";
                $params[] = $task_id;
                $params[] = $this->mahasiswa_id;
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                $stmt = $this->pdo->prepare("
                    INSERT INTO task_submissions (task_id, mahasiswa_id, file_path, catatan, status)
                    VALUES (?, ?, ?, ?, 'submitted')
                ");
                $stmt->execute([$task_id, $this->mahasiswa_id, $file_path, $catatan]);
            }
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // ============================================
    // IRS
    // ============================================
    
    public function getIRS($semester = null) {
        $sql = "
            SELECT irs.*, c.kode_mk, c.nama_mk, c.sks, c.ruang, 
                   u.nama as dosen_nama
            FROM irs
            JOIN courses c ON irs.course_id = c.id
            LEFT JOIN dosen d ON c.dosen_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE irs.mahasiswa_id = ?
        ";
        $params = [$this->mahasiswa_id];
        
        if ($semester) {
            $sql .= " AND irs.semester = ?";
            $params[] = $semester;
        }
        
        $sql .= " ORDER BY c.kode_mk ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getAvailableCourses($semester) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.nama as dosen_nama
            FROM courses c
            LEFT JOIN dosen d ON c.dosen_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE c.semester = ? 
            AND c.program_studi = (
                SELECT program_studi FROM mahasiswa WHERE id = ?
            )
            AND c.id NOT IN (
                SELECT course_id FROM irs 
                WHERE mahasiswa_id = ? AND semester = ?
            )
            ORDER BY c.kode_mk ASC
        ");
        $stmt->execute([
            $semester, 
            $this->mahasiswa_id,
            $this->mahasiswa_id,
            $semester
        ]);
        return $stmt->fetchAll();
    }
    
    public function getTotalSksIRS($semester) {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(c.sks), 0) as total_sks
            FROM irs
            JOIN courses c ON irs.course_id = c.id
            WHERE irs.mahasiswa_id = ? AND irs.semester = ?
        ");
        $stmt->execute([$this->mahasiswa_id, $semester]);
        $result = $stmt->fetch();
        return $result['total_sks'] ?? 0;
    }
    
    public function getIRSStatus($semester) {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT status 
            FROM irs 
            WHERE mahasiswa_id = ? AND semester = ?
            LIMIT 1
        ");
        $stmt->execute([$this->mahasiswa_id, $semester]);
        $result = $stmt->fetch();
        return $result['status'] ?? null;
    }
    
    public function createIRS($data) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM irs 
                WHERE mahasiswa_id = ? AND course_id = ? AND semester = ?
            ");
            $stmt->execute([
                $this->mahasiswa_id,
                $data['course_id'],
                $data['semester']
            ]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Mata kuliah sudah terdaftar di IRS'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO irs (mahasiswa_id, course_id, semester, status)
                VALUES (?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $this->mahasiswa_id,
                $data['course_id'],
                $data['semester']
            ]);
            
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deleteIRS($irs_id) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM irs 
                WHERE id = ? AND mahasiswa_id = ? AND status = 'pending'
            ");
            $stmt->execute([$irs_id, $this->mahasiswa_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getIRSForPrint($semester) {
        $stmt = $this->pdo->prepare("
            SELECT irs.*, 
                   c.kode_mk, c.nama_mk, c.sks, c.ruang, 
                   u.nama as dosen_nama,
                   m.nim, m.program_studi, m.semester as mhs_semester,
                   us.nama as mahasiswa_nama
            FROM irs
            JOIN courses c ON irs.course_id = c.id
            LEFT JOIN dosen d ON c.dosen_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            JOIN mahasiswa m ON irs.mahasiswa_id = m.id
            JOIN users us ON m.user_id = us.id
            WHERE irs.mahasiswa_id = ? AND irs.semester = ?
            ORDER BY c.kode_mk ASC
        ");
        $stmt->execute([$this->mahasiswa_id, $semester]);
        return $stmt->fetchAll();
    }
    
    // ============================================
    // KHS & IPK
    // ============================================
    
    public function getKHS($semester = null) {
        $sql = "
            SELECT g.*, c.kode_mk, c.nama_mk, c.sks,
                   u.nama as dosen_nama
            FROM grades g
            JOIN courses c ON g.course_id = c.id
            LEFT JOIN dosen d ON c.dosen_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE g.mahasiswa_id = ? AND g.status_verifikasi = 'verified'
        ";
        $params = [$this->mahasiswa_id];
        
        if ($semester) {
            $sql .= " AND g.semester = ?";
            $params[] = $semester;
        }
        
        $sql .= " ORDER BY c.kode_mk ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getSemestersWithGrades() {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT semester 
            FROM grades 
            WHERE mahasiswa_id = ? AND status_verifikasi = 'verified'
            ORDER BY semester ASC
        ");
        $stmt->execute([$this->mahasiswa_id]);
        return $stmt->fetchAll();
    }
    
    public function hitungIPS($semester) {
        $stmt = $this->pdo->prepare("
            SELECT g.nilai_akhir, c.sks
            FROM grades g
            JOIN courses c ON g.course_id = c.id
            WHERE g.mahasiswa_id = ? AND g.semester = ? AND g.status_verifikasi = 'verified'
        ");
        $stmt->execute([$this->mahasiswa_id, $semester]);
        $grades = $stmt->fetchAll();
        return $this->calculateGPA($grades);
    }
    
    public function hitungIPK() {
        $stmt = $this->pdo->prepare("
            SELECT g.nilai_akhir, c.sks
            FROM grades g
            JOIN courses c ON g.course_id = c.id
            WHERE g.mahasiswa_id = ? AND g.status_verifikasi = 'verified'
        ");
        $stmt->execute([$this->mahasiswa_id]);
        $grades = $stmt->fetchAll();
        return $this->calculateGPA($grades);
    }
    
    private function calculateGPA($grades) {
        $totalSKS = 0;
        $totalBobot = 0;
        
        foreach ($grades as $row) {
            $nilai = floatval($row['nilai_akhir']);
            $sks = intval($row['sks']);
            
            if ($nilai >= 85) $bobot = 4.0;
            elseif ($nilai >= 75) $bobot = 3.0;
            elseif ($nilai >= 65) $bobot = 2.0;
            elseif ($nilai >= 50) $bobot = 1.0;
            else $bobot = 0.0;
            
            $totalBobot += ($bobot * $sks);
            $totalSKS += $sks;
        }
        
        return ($totalSKS > 0) ? round($totalBobot / $totalSKS, 2) : 0;
    }
    
    public function getDetailIPK() {
        $semesters = $this->getSemestersWithGrades();
        $detail = [];
        
        foreach ($semesters as $s) {
            $semester = $s['semester'];
            $detail[$semester] = [
                'ips' => $this->hitungIPS($semester),
                'total_sks' => $this->getTotalSKSBySemester($semester),
                'mata_kuliah' => count($this->getKHS($semester))
            ];
        }
        
        return $detail;
    }
    
    private function getTotalSKSBySemester($semester) {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(c.sks), 0) as total_sks
            FROM grades g
            JOIN courses c ON g.course_id = c.id
            WHERE g.mahasiswa_id = ? AND g.semester = ? AND g.status_verifikasi = 'verified'
        ");
        $stmt->execute([$this->mahasiswa_id, $semester]);
        $result = $stmt->fetch();
        return $result['total_sks'] ?? 0;
    }
    
    // ============================================
    // PRESENSI
    // ============================================
    
    public function getRekapPresensi() {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.kode_mk,
                c.nama_mk,
                COUNT(p.id) as total_pertemuan,
                COALESCE(SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END), 0) as hadir,
                COALESCE(SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END), 0) as izin,
                COALESCE(SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END), 0) as sakit,
                COALESCE(SUM(CASE WHEN p.status = 'alpa' THEN 1 ELSE 0 END), 0) as alpa,
                COALESCE(ROUND(SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) * 100.0 / COUNT(p.id), 2), 0) as persentase
            FROM presensi p
            JOIN courses c ON p.course_id = c.id
            WHERE p.mahasiswa_id = ?
            GROUP BY p.course_id, c.kode_mk, c.nama_mk
            ORDER BY c.kode_mk ASC
        ");
        $stmt->execute([$this->mahasiswa_id]);
        return $stmt->fetchAll();
    }
    
    public function konfirmasiPresensi($course_id, $kode) {
        $sesi = $_SESSION['presensi_sesi'] ?? null;
        
        if (!$sesi || $sesi['course_id'] != $course_id) {
            return ['success' => false, 'message' => 'Sesi presensi tidak aktif'];
        }
        
        if ($sesi['kode'] != $kode) {
            return ['success' => false, 'message' => 'Kode presensi salah'];
        }
        
        $tanggal = date('Y-m-d');
        $stmt = $this->pdo->prepare("
            SELECT id FROM presensi 
            WHERE course_id = ? AND mahasiswa_id = ? AND tanggal = ?
        ");
        $stmt->execute([$course_id, $this->mahasiswa_id, $tanggal]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Anda sudah melakukan presensi hari ini'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO presensi (course_id, mahasiswa_id, tanggal, status)
                VALUES (?, ?, ?, 'hadir')
            ");
            $stmt->execute([$course_id, $this->mahasiswa_id, $tanggal]);
            return ['success' => true, 'message' => 'Presensi berhasil!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>