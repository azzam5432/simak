<?php
// ============================================
// controllers/AdminController.php
// Logika bisnis master data, approval, verifikasi
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

class AdminController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // ============================================
    // MANAJEMEN MAHASISWA
    // ============================================
    
    /**
     * Get semua mahasiswa dengan alias yang jelas
     */
    public function getAllMahasiswa() {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id as user_id,
                u.username,
                u.nama,
                u.email,
                u.role,
                m.id as mahasiswa_id,
                m.nim,
                m.angkatan,
                m.program_studi,
                m.semester,
                m.ipk
            FROM users u
            JOIN mahasiswa m ON u.id = m.user_id
            ORDER BY m.nim ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get mahasiswa by ID (dari tabel mahasiswa)
     */
    public function getMahasiswaById($mahasiswa_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id as user_id,
                u.username,
                u.nama,
                u.email,
                u.role,
                m.id as mahasiswa_id,
                m.nim,
                m.angkatan,
                m.program_studi,
                m.semester,
                m.ipk
            FROM users u
            JOIN mahasiswa m ON u.id = m.user_id
            WHERE m.id = ?
        ");
        $stmt->execute([$mahasiswa_id]);
        return $stmt->fetch();
    }
    
    /**
     * Tambah mahasiswa baru
     */
    public function createMahasiswa($data) {
        try {
            $this->pdo->beginTransaction();
            
            // Bersihkan data
            $nim = trim(preg_replace('/[^a-zA-Z0-9]/', '', $data['nim']));
            $username = trim($data['username']);
            $nama = trim($data['nama']);
            $email = trim($data['email'] ?? '');
            $angkatan = intval($data['angkatan']);
            $semester = intval($data['semester'] ?? 1);
            $program_studi = trim($data['program_studi']);
            
            if (empty($nim)) {
                return ['success' => false, 'message' => 'NIM tidak boleh kosong!'];
            }
            if (empty($username)) {
                return ['success' => false, 'message' => 'Username tidak boleh kosong!'];
            }
            
            // Cek duplikat
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username "' . $username . '" sudah digunakan!'];
            }
            
            $stmt = $this->pdo->prepare("SELECT id FROM mahasiswa WHERE nim = ?");
            $stmt->execute([$nim]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'NIM "' . $nim . '" sudah terdaftar!'];
            }
            
            // Insert ke users
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password, nama, nip_nim, email, role)
                VALUES (?, ?, ?, ?, ?, 'mahasiswa')
            ");
            $stmt->execute([$username, $hashedPassword, $nama, $nim, $email]);
            $userId = $this->pdo->lastInsertId();
            
            // Insert ke mahasiswa
            $stmt = $this->pdo->prepare("
                INSERT INTO mahasiswa (user_id, nim, angkatan, program_studi, semester)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $nim, $angkatan, $program_studi, $semester]);
            
            $this->pdo->commit();
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update mahasiswa
     */
    public function updateMahasiswa($mahasiswa_id, $data) {
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("SELECT user_id FROM mahasiswa WHERE id = ?");
            $stmt->execute([$mahasiswa_id]);
            $mahasiswa = $stmt->fetch();
            
            if (!$mahasiswa) {
                return ['success' => false, 'message' => 'Mahasiswa tidak ditemukan'];
            }
            
            $userId = $mahasiswa['user_id'];
            $nim = trim(preg_replace('/[^a-zA-Z0-9]/', '', $data['nim']));
            
            // Cek duplikat NIM
            $stmt = $this->pdo->prepare("SELECT id FROM mahasiswa WHERE nim = ? AND id != ?");
            $stmt->execute([$nim, $mahasiswa_id]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'NIM "' . $nim . '" sudah digunakan!'];
            }
            
            // Update users
            $sql = "UPDATE users SET nama = ?, email = ?";
            $params = [trim($data['nama']), trim($data['email'] ?? '')];
            
            if (!empty($data['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $userId;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            // Update mahasiswa
            $stmt = $this->pdo->prepare("
                UPDATE mahasiswa 
                SET nim = ?, angkatan = ?, program_studi = ?, semester = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $nim,
                intval($data['angkatan']),
                trim($data['program_studi']),
                intval($data['semester']),
                $mahasiswa_id
            ]);
            
            $this->pdo->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Hapus mahasiswa
     */
    public function deleteMahasiswa($mahasiswa_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT user_id FROM mahasiswa WHERE id = ?");
            $stmt->execute([$mahasiswa_id]);
            $mahasiswa = $stmt->fetch();
            
            if (!$mahasiswa) {
                return ['success' => false, 'message' => 'Mahasiswa tidak ditemukan'];
            }
            
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("DELETE FROM mahasiswa WHERE id = ?");
            $stmt->execute([$mahasiswa_id]);
            
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$mahasiswa['user_id']]);
            
            $this->pdo->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // ============================================
    // MANAJEMEN DOSEN
    // ============================================
    
    public function getAllDosen() {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id as user_id,
                u.username,
                u.nama,
                u.email,
                u.role,
                d.id as dosen_id,        -- ← Alias jelas: dosen_id
                d.nidn,
                d.program_studi,
                d.jabatan
            FROM users u
            JOIN dosen d ON u.id = d.user_id
            ORDER BY d.nidn ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getDosenById($dosen_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id as user_id,
                u.username,
                u.nama,
                u.email,
                u.role,
                d.id as dosen_id,
                d.nidn,
                d.program_studi,
                d.jabatan
            FROM users u
            JOIN dosen d ON u.id = d.user_id
            WHERE d.id = ?      -- ← Pakai d.id, BUKAN u.id
        ");
        $stmt->execute([$dosen_id]);
        return $stmt->fetch();
    }
    public function createDosen($data) {
        try {
            $this->pdo->beginTransaction();
            
            // Bersihkan data
            $nidn = trim(preg_replace('/[^a-zA-Z0-9]/', '', $data['nidn']));
            $username = trim($data['username']);
            
            // Cek duplikat username
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username sudah digunakan!'];
            }
            
            // Cek duplikat NIDN
            $stmt = $this->pdo->prepare("SELECT id FROM dosen WHERE nidn = ?");
            $stmt->execute([$nidn]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'NIDN sudah terdaftar!'];
            }
            
            // Insert ke users
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password, nama, nip_nim, email, role)
                VALUES (?, ?, ?, ?, ?, 'dosen')
            ");
            $stmt->execute([
                $username,
                $hashedPassword,
                trim($data['nama']),
                $nidn,
                trim($data['email'] ?? '')
            ]);
            $userId = $this->pdo->lastInsertId();
            
            // Insert ke dosen
            $stmt = $this->pdo->prepare("
                INSERT INTO dosen (user_id, nidn, program_studi, jabatan)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $nidn,
                trim($data['program_studi']),
                trim($data['jabatan'] ?? 'Lektor')
            ]);
            
            $this->pdo->commit();
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update dosen (by dosen_id)
     */
    public function updateDosen($dosen_id, $data) {
        try {
            $this->pdo->beginTransaction();
            
            // Get user_id dari dosen
            $stmt = $this->pdo->prepare("SELECT user_id FROM dosen WHERE id = ?");
            $stmt->execute([$dosen_id]);
            $dosen = $stmt->fetch();
            
            if (!$dosen) {
                return ['success' => false, 'message' => 'Dosen tidak ditemukan'];
            }
            
            $userId = $dosen['user_id'];
            
            // Bersihkan NIDN
            $nidn = trim(preg_replace('/[^a-zA-Z0-9]/', '', $data['nidn']));
            
            // Update users
            $sql = "UPDATE users SET nama = ?, email = ?";
            $params = [trim($data['nama']), trim($data['email'] ?? '')];
            
            if (!empty($data['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $userId;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            // Update dosen
            $stmt = $this->pdo->prepare("
                UPDATE dosen 
                SET nidn = ?, program_studi = ?, jabatan = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $nidn,
                trim($data['program_studi']),
                trim($data['jabatan']),
                $dosen_id   // ← Pakai dosen_id
            ]);
            
            $this->pdo->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Hapus dosen (by dosen_id)
     */
    public function deleteDosen($dosen_id) {
        try {
            // Cek apakah dosen ada
            $stmt = $this->pdo->prepare("SELECT user_id FROM dosen WHERE id = ?");
            $stmt->execute([$dosen_id]);
            $dosen = $stmt->fetch();
            
            if (!$dosen) {
                return ['success' => false, 'message' => 'Dosen tidak ditemukan'];
            }
            
            $userId = $dosen['user_id'];
            
            $this->pdo->beginTransaction();
            
            // Hapus dari dosen
            $stmt = $this->pdo->prepare("DELETE FROM dosen WHERE id = ?");
            $stmt->execute([$dosen_id]);
            
            // Hapus dari users
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            $this->pdo->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // ============================================
    // MANAJEMEN MATA KULIAH
    // ============================================
    
    public function getAllMatakuliah() {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.nama as dosen_nama, d.nidn as dosen_nidn,
                d.id as dosen_id
            FROM courses c
            LEFT JOIN dosen d ON c.dosen_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            ORDER BY c.kode_mk ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getMatakuliahById($id) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.nama as dosen_nama, d.nidn as dosen_nidn
            FROM courses c
            LEFT JOIN dosen d ON c.dosen_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function createMatakuliah($data) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM courses WHERE kode_mk = ?");
            $stmt->execute([$data['kode_mk']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Kode mata kuliah sudah digunakan!'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO courses (
                    kode_mk, nama_mk, sks, semester, program_studi,
                    dosen_id, ruang, hari, jam_mulai, jam_selesai, kapasitas
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['kode_mk']),
                trim($data['nama_mk']),
                intval($data['sks']),
                intval($data['semester']),
                trim($data['program_studi']),
                !empty($data['dosen_id']) ? intval($data['dosen_id']) : null,
                trim($data['ruang'] ?? ''),
                trim($data['hari'] ?? ''),
                $data['jam_mulai'] ?? null,
                $data['jam_selesai'] ?? null,
                intval($data['kapasitas'] ?? 30)
            ]);
            
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateMatakuliah($id, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE courses SET
                    kode_mk = ?,
                    nama_mk = ?,
                    sks = ?,
                    semester = ?,
                    program_studi = ?,
                    dosen_id = ?,
                    ruang = ?,
                    hari = ?,
                    jam_mulai = ?,
                    jam_selesai = ?,
                    kapasitas = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($data['kode_mk']),
                trim($data['nama_mk']),
                intval($data['sks']),
                intval($data['semester']),
                trim($data['program_studi']),
                !empty($data['dosen_id']) ? intval($data['dosen_id']) : null,
                trim($data['ruang'] ?? ''),
                trim($data['hari'] ?? ''),
                $data['jam_mulai'] ?? null,
                $data['jam_selesai'] ?? null,
                intval($data['kapasitas'] ?? 30),
                $id
            ]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deleteMatakuliah($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // ============================================
    // VALIDASI IRS
    // ============================================
    
    public function getPendingIRS() {
        $stmt = $this->pdo->prepare("
            SELECT irs.*, 
                   c.kode_mk, c.nama_mk, c.sks, c.ruang,
                   m.nim, m.program_studi, m.semester as mhs_semester,
                   u.nama as mahasiswa_nama
            FROM irs
            JOIN courses c ON irs.course_id = c.id
            JOIN mahasiswa m ON irs.mahasiswa_id = m.id
            JOIN users u ON m.user_id = u.id
            WHERE irs.status = 'pending'
            ORDER BY irs.created_at ASC, m.nim ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function approveIRS($irs_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE irs SET status = 'approved', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$irs_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function rejectIRS($irs_id, $catatan = null) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE irs SET status = 'rejected', catatan = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$catatan, $irs_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function approveAllIRS($mahasiswa_id, $semester) {
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("
                UPDATE irs 
                SET status = 'approved', updated_at = NOW()
                WHERE mahasiswa_id = ? AND semester = ? AND status = 'pending'
            ");
            $stmt->execute([$mahasiswa_id, $semester]);
            
            $this->pdo->commit();
            return ['success' => true, 'affected' => $stmt->rowCount()];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getIRSStats() {
        $stats = [];
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM irs WHERE status = 'pending'");
        $stats['pending'] = $stmt->fetch()['total'];
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM irs WHERE status = 'approved'");
        $stats['approved'] = $stmt->fetch()['total'];
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM irs WHERE status = 'rejected'");
        $stats['rejected'] = $stmt->fetch()['total'];
        
        return $stats;
    }
    
    // ============================================
    // VERIFIKASI NILAI
    // ============================================
    
    public function getDraftGrades() {
        $stmt = $this->pdo->prepare("
            SELECT g.*, 
                   c.kode_mk, c.nama_mk, c.sks,
                   u.nama as mahasiswa_nama, m.nim,
                   dosu.nama as dosen_nama
            FROM grades g
            JOIN courses c ON g.course_id = c.id
            JOIN mahasiswa m ON g.mahasiswa_id = m.id
            JOIN users u ON m.user_id = u.id
            LEFT JOIN dosen dos ON c.dosen_id = dos.id
            LEFT JOIN users dosu ON dos.user_id = dosu.id
            WHERE g.status_verifikasi = 'draft'
            ORDER BY g.created_at ASC, m.nim ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getDraftGradesByCourse($course_id) {
        $stmt = $this->pdo->prepare("
            SELECT g.*, 
                   u.nama as mahasiswa_nama, m.nim,
                   c.kode_mk, c.nama_mk, c.sks
            FROM grades g
            JOIN mahasiswa m ON g.mahasiswa_id = m.id
            JOIN users u ON m.user_id = u.id
            JOIN courses c ON g.course_id = c.id
            WHERE g.course_id = ? AND g.status_verifikasi = 'draft'
            ORDER BY m.nim ASC
        ");
        $stmt->execute([$course_id]);
        return $stmt->fetchAll();
    }
    
    public function verifyGrade($grade_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE grades SET status_verifikasi = 'verified', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$grade_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function verifyAllGrades($course_id) {
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("
                UPDATE grades 
                SET status_verifikasi = 'verified', updated_at = NOW()
                WHERE course_id = ? AND status_verifikasi = 'draft'
            ");
            $stmt->execute([$course_id]);
            
            $this->pdo->commit();
            return ['success' => true, 'affected' => $stmt->rowCount()];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getCoursesWithDraft() {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT 
                c.id, c.kode_mk, c.nama_mk, c.sks,
                u.nama as dosen_nama,
                COUNT(g.id) as total_draft
            FROM grades g
            JOIN courses c ON g.course_id = c.id
            LEFT JOIN dosen d ON c.dosen_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE g.status_verifikasi = 'draft'
            GROUP BY c.id, c.kode_mk, c.nama_mk, c.sks, u.nama
            ORDER BY c.kode_mk ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getNilaiStats() {
        $stats = [];
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM grades WHERE status_verifikasi = 'draft'");
        $stats['draft'] = $stmt->fetch()['total'];
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM grades WHERE status_verifikasi = 'verified'");
        $stats['verified'] = $stmt->fetch()['total'];
        
        $stmt = $this->pdo->query("
            SELECT COUNT(DISTINCT course_id) as total 
            FROM grades 
            WHERE status_verifikasi = 'draft'
        ");
        $stats['courses_with_draft'] = $stmt->fetch()['total'];
        
        return $stats;
    }
    
    // ============================================
    // DASHBOARD STATISTICS
    // ============================================
    
    public function getDashboardStats() {
        $stats = [];
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM mahasiswa");
        $stats['total_mahasiswa'] = $stmt->fetch()['total'];
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM dosen");
        $stats['total_dosen'] = $stmt->fetch()['total'];
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM courses");
        $stats['total_matakuliah'] = $stmt->fetch()['total'];
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM irs WHERE status = 'pending'");
        $stats['irs_pending'] = $stmt->fetch()['total'];
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM grades WHERE status_verifikasi = 'draft'");
        $stats['nilai_draft'] = $stmt->fetch()['total'];
        
        return $stats;
    }
}
?>