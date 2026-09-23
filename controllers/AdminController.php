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
    // MANAJEMEN MAHASISWA (V2 - Fakultas/Jurusan)
    // ============================================
    
    /**
     * Get semua mahasiswa dengan join fakultas & jurusan
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
                m.tahun_ajaran,
                m.tingkat,
                m.semester,
                m.ipk,
                f.id as fakultas_id,
                f.kode as fakultas_kode,
                f.nama as fakultas_nama,
                j.id as jurusan_id,
                j.kode as jurusan_kode,
                j.nama as jurusan_nama,
                j.jenjang
            FROM users u
            JOIN mahasiswa m ON u.id = m.user_id
            LEFT JOIN fakultas f ON m.fakultas_id = f.id
            LEFT JOIN jurusan j ON m.jurusan_id = j.id
            ORDER BY m.nim ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get mahasiswa by ID
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
                m.tahun_ajaran,
                m.tingkat,
                m.semester,
                m.ipk,
                m.fakultas_id,
                m.jurusan_id,
                f.kode as fakultas_kode,
                f.nama as fakultas_nama,
                j.kode as jurusan_kode,
                j.nama as jurusan_nama,
                j.jenjang
            FROM users u
            JOIN mahasiswa m ON u.id = m.user_id
            LEFT JOIN fakultas f ON m.fakultas_id = f.id
            LEFT JOIN jurusan j ON m.jurusan_id = j.id
            WHERE m.id = ?
        ");
        $stmt->execute([$mahasiswa_id]);
        return $stmt->fetch();
    }
    
    /**
     * Create mahasiswa baru
     * Semester otomatis dihitung dari tingkat + bulan
     */
    public function createMahasiswa($data) {
        try {
            $this->pdo->beginTransaction();
            
            $nim = trim(preg_replace('/[^a-zA-Z0-9]/', '', $data['nim']));
            $username = trim($data['username']);
            
            // Cek duplikat
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Username sudah digunakan!'];
            }
            
            $stmt = $this->pdo->prepare("SELECT id FROM mahasiswa WHERE nim = ?");
            $stmt->execute([$nim]);
            if ($stmt->fetch()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'NIM sudah terdaftar!'];
            }
            
            // Hitung tahun ajaran & semester otomatis
            $tahun_ajaran = $data['tahun_ajaran'] ?? MasterDataController::getTahunAjaranOtomatis();
            $tingkat = intval($data['tingkat'] ?? 1);
            $semester = MasterDataController::getSemesterOtomatis($tingkat);
            
            // Insert ke users
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password, nama, nip_nim, email, role)
                VALUES (?, ?, ?, ?, ?, 'mahasiswa')
            ");
            $stmt->execute([
                $username,
                $hashedPassword,
                trim($data['nama']),
                $nim,
                trim($data['email'] ?? '')
            ]);
            $userId = $this->pdo->lastInsertId();
            
            // Insert ke mahasiswa
            $stmt = $this->pdo->prepare("
                INSERT INTO mahasiswa (
                    user_id, fakultas_id, jurusan_id, nim, 
                    tahun_ajaran, tingkat, semester
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                intval($data['fakultas_id']) ?: null,
                intval($data['jurusan_id']) ?: null,
                $nim,
                $tahun_ajaran,
                $tingkat,
                $semester
            ]);
            
            $this->pdo->commit();
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update mahasiswa
     * Semester otomatis dihitung ulang jika tingkat berubah
     */
    public function updateMahasiswa($mahasiswa_id, $data) {
        try {
            $this->pdo->beginTransaction();
            
            // Get user_id
            $stmt = $this->pdo->prepare("SELECT user_id FROM mahasiswa WHERE id = ?");
            $stmt->execute([$mahasiswa_id]);
            $mahasiswa = $stmt->fetch();
            
            if (!$mahasiswa) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Mahasiswa tidak ditemukan'];
            }
            
            $userId = $mahasiswa['user_id'];
            $nim = trim(preg_replace('/[^a-zA-Z0-9]/', '', $data['nim']));
            
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
            
            // Hitung semester otomatis
            $tingkat = intval($data['tingkat']);
            $semester = MasterDataController::getSemesterOtomatis($tingkat);
            
            // Update mahasiswa
            $stmt = $this->pdo->prepare("
                UPDATE mahasiswa 
                SET nim = ?, 
                    fakultas_id = ?, 
                    jurusan_id = ?, 
                    tahun_ajaran = ?, 
                    tingkat = ?, 
                    semester = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $nim,
                intval($data['fakultas_id']) ?: null,
                intval($data['jurusan_id']) ?: null,
                trim($data['tahun_ajaran']),
                $tingkat,
                $semester,
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
     * Delete mahasiswa (sama seperti sebelumnya)
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
    // MANAJEMEN DOSEN (REVISI)
    // ============================================
    
    /**
     * Get semua dosen dengan join fakultas & jurusan
     */
    public function getAllDosen() {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id as user_id,
                u.username,
                u.nama,
                u.email,
                d.id as dosen_id,
                d.nid,
                d.fakultas_id,
                d.jurusan_id,
                f.nama as fakultas_nama,
                f.kode as fakultas_kode,
                j.nama as jurusan_nama,
                j.kode as jurusan_kode,
                j.jenjang
            FROM users u
            JOIN dosen d ON u.id = d.user_id
            LEFT JOIN fakultas f ON d.fakultas_id = f.id
            LEFT JOIN jurusan j ON d.jurusan_id = j.id
            WHERE u.role = 'dosen'
            ORDER BY d.nid ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get dosen by ID
     */
    public function getDosenById($dosen_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id as user_id,
                u.username,
                u.nama,
                u.email,
                d.id as dosen_id,
                d.nid,
                d.fakultas_id,
                d.jurusan_id,
                f.nama as fakultas_nama,
                j.nama as jurusan_nama
            FROM users u
            JOIN dosen d ON u.id = d.user_id
            LEFT JOIN fakultas f ON d.fakultas_id = f.id
            LEFT JOIN jurusan j ON d.jurusan_id = j.id
            WHERE d.id = ? AND u.role = 'dosen'
        ");
        $stmt->execute([$dosen_id]);
        return $stmt->fetch();
    }
    
    /**
     * Get fakultas list untuk dropdown
     */
    public function getFakultasList() {
        $stmt = $this->pdo->prepare("
            SELECT id, kode, nama 
            FROM fakultas 
            ORDER BY nama ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get jurusan list by fakultas
     */
    public function getJurusanByFakultas($fakultas_id) {
        $stmt = $this->pdo->prepare("
            SELECT id, kode, nama, jenjang 
            FROM jurusan 
            WHERE fakultas_id = ?
            ORDER BY nama ASC
        ");
        $stmt->execute([$fakultas_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Tambah dosen baru
     */
    public function createDosen($data) {
        try {
            $this->pdo->beginTransaction();
            
            // Validasi input
            $username = trim($data['username']);
            $nid = trim($data['nid']);
            $nama = trim($data['nama']);
            $email = trim($data['email']);
            $fakultas_id = intval($data['fakultas_id']);
            $jurusan_id = intval($data['jurusan_id']);
            
            // Cek NID hanya angka
            if (!preg_match('/^[0-9]+$/', $nid)) {
                return ['success' => false, 'message' => 'NID hanya boleh berisi angka'];
            }
            
            // Cek duplikat username
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username sudah digunakan!'];
            }
            
            // Cek duplikat NID
            $stmt = $this->pdo->prepare("SELECT id FROM dosen WHERE nid = ?");
            $stmt->execute([$nid]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'NID sudah terdaftar!'];
            }
            
            // Cek duplikat email
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND email != ''");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email sudah digunakan!'];
            }
            
            // Insert ke users
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password, nama, nip_nim, email, role)
                VALUES (?, ?, ?, ?, ?, 'dosen')
            ");
            $stmt->execute([$username, $hashedPassword, $nama, $nid, $email]);
            $userId = $this->pdo->lastInsertId();
            
            // Insert ke dosen
            $stmt = $this->pdo->prepare("
                INSERT INTO dosen (user_id, nid, fakultas_id, jurusan_id)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $nid, $fakultas_id, $jurusan_id]);
            $dosenId = $this->pdo->lastInsertId();
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'dosen_id' => $dosenId,
                'user_id' => $userId
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update dosen
     */
    public function updateDosen($dosen_id, $data) {
        try {
            $this->pdo->beginTransaction();
            
            // Get user_id
            $stmt = $this->pdo->prepare("SELECT user_id FROM dosen WHERE id = ?");
            $stmt->execute([$dosen_id]);
            $dosen = $stmt->fetch();
            
            if (!$dosen) {
                return ['success' => false, 'message' => 'Dosen tidak ditemukan'];
            }
            
            $userId = $dosen['user_id'];
            
            // Validasi input
            $nid = trim($data['nid']);
            $nama = trim($data['nama']);
            $email = trim($data['email']);
            $fakultas_id = intval($data['fakultas_id']);
            $jurusan_id = intval($data['jurusan_id']);
            
            // Cek NID hanya angka
            if (!preg_match('/^[0-9]+$/', $nid)) {
                return ['success' => false, 'message' => 'NID hanya boleh berisi angka'];
            }
            
            // Cek duplikat NID (selain diri sendiri)
            $stmt = $this->pdo->prepare("SELECT id FROM dosen WHERE nid = ? AND id != ?");
            $stmt->execute([$nid, $dosen_id]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'NID sudah digunakan dosen lain!'];
            }
            
            // Cek duplikat email (selain diri sendiri)
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND email != ''");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email sudah digunakan user lain!'];
            }
            
            // Update users
            $sql = "UPDATE users SET nama = ?, email = ?";
            $params = [$nama, $email];
            
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
                SET nid = ?, fakultas_id = ?, jurusan_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$nid, $fakultas_id, $jurusan_id, $dosen_id]);
            
            $this->pdo->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Hapus dosen
     */
    public function deleteDosen($dosen_id) {
        try {
            // Cek dosen ada
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
            SELECT c.*, u.nama as dosen_nama, d.nid as dosen_nid,
                d.id as dosen_id,
                j.kode as jurusan_kode, j.nama as jurusan_nama, j.jenjang,
                f.kode as fakultas_kode, f.nama as fakultas_nama
            FROM courses c
            LEFT JOIN dosen d ON c.dosen_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            LEFT JOIN jurusan j ON c.jurusan_id = j.id
            LEFT JOIN fakultas f ON j.fakultas_id = f.id
            ORDER BY c.kode_mk ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getMatakuliahById($id) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.nama as dosen_nama, d.nid as dosen_nid,
                j.fakultas_id, j.kode as jurusan_kode, j.nama as jurusan_nama
            FROM courses c
            LEFT JOIN dosen d ON c.dosen_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            LEFT JOIN jurusan j ON c.jurusan_id = j.id
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
                    kode_mk, nama_mk, sks, semester, jurusan_id,
                    dosen_id, ruang, hari, jam_mulai, jam_selesai, kapasitas
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['kode_mk']),
                trim($data['nama_mk']),
                intval($data['sks']),
                intval($data['semester']),
                intval($data['jurusan_id']) ?: null,
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
                    jurusan_id = ?,
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
                intval($data['jurusan_id']) ?: null,
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
                   m.nim, j.nama as jurusan_nama, m.semester as mhs_semester,
                   u.nama as mahasiswa_nama
            FROM irs
            JOIN courses c ON irs.course_id = c.id
            JOIN mahasiswa m ON irs.mahasiswa_id = m.id
            LEFT JOIN jurusan j ON m.jurusan_id = j.id
            JOIN users u ON m.user_id = u.id
            WHERE irs.status = 'pending'
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

    /**
 * Reset password user dan kirim email notifikasi
 */
public function resetUserPassword($user_id) {
    try {
        // Get data user
        $stmt = $this->pdo->prepare("
            SELECT u.*, 
                   COALESCE(m.nim, d.nid) as identifier
            FROM users u
            LEFT JOIN mahasiswa m ON u.id = m.user_id
            LEFT JOIN dosen d ON u.id = d.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'User tidak ditemukan'];
        }
        
        // Generate password baru (8 karakter acak)
        $new_password = $this->generateRandomPassword(8);
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user_id]);
        
        // Kirim email notifikasi (jika ada email)
        $email_sent = false;
        if (!empty($user['email'])) {
            require_once __DIR__ . '/../helper/EmailHelper.php';
            require_once __DIR__ . '/../template/email/reset_password.php';
            
            $emailHelper = new EmailHelper();
            $body = renderResetPassword([
                'nama' => $user['nama'],
                'username' => $user['username'],
                'new_password' => $new_password,
                'reset_by' => $_SESSION['nama'] ?? 'Admin'
            ]);
            
            $result = $emailHelper->send(
                $user['email'],
                $user['nama'],
                'Password Akun SIMAK Anda Direset',
                $body
            );
            
            $email_sent = $result['success'];
        }
        
        return [
            'success' => true,
            'new_password' => $new_password,
            'email_sent' => $email_sent,
            'message' => 'Password berhasil direset' . ($email_sent ? ' dan email terkirim' : '')
        ];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Generate random password
 */
private function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * Approve IRS dan kirim email notifikasi
 */
public function approveIRSDenganEmail($irs_id, $mahasiswa_id, $semester) {
    try {
        // Approve IRS
        $stmt = $this->pdo->prepare("
            UPDATE irs 
            SET status = 'approved', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$irs_id]);
        
        // Get data mahasiswa & IRS
        $stmt = $this->pdo->prepare("
            SELECT u.nama, u.email, m.nim
            FROM mahasiswa m
            JOIN users u ON m.user_id = u.id
            WHERE m.id = ?
        ");
        $stmt->execute([$mahasiswa_id]);
        $mahasiswa = $stmt->fetch();
        
        // Get daftar MK yang di-approve di semester ini
        $stmt = $this->pdo->prepare("
            SELECT c.kode_mk, c.nama_mk, c.sks
            FROM irs i
            JOIN courses c ON i.course_id = c.id
            WHERE i.mahasiswa_id = ? AND i.semester = ? AND i.status = 'approved'
        ");
        $stmt->execute([$mahasiswa_id, $semester]);
        $matakuliah = $stmt->fetchAll();
        
        $total_sks = array_sum(array_column($matakuliah, 'sks'));
        
        // Kirim email
        $email_sent = false;
        if ($mahasiswa && !empty($mahasiswa['email'])) {
            require_once __DIR__ . '/../helper/EmailHelper.php';
            require_once __DIR__ . '/../template/email/irs_notification.php';
            
            $emailHelper = new EmailHelper();
            $body = renderIRSNotification([
                'nama' => $mahasiswa['nama'],
                'nim' => $mahasiswa['nim'],
                'semester' => $semester,
                'status' => 'approved',
                'catatan' => null,
                'total_sks' => $total_sks,
                'matakuliah' => $matakuliah
            ]);
            
            $result = $emailHelper->send(
                $mahasiswa['email'],
                $mahasiswa['nama'],
                'IRS Anda Telah Disetujui',
                $body
            );
            
            $email_sent = $result['success'];
        }
        
        return [
            'success' => true,
            'email_sent' => $email_sent
        ];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Reject IRS dan kirim email notifikasi
 */
public function rejectIRSDenganEmail($irs_id, $mahasiswa_id, $semester, $catatan = null) {
    try {
        // Reject IRS
        $stmt = $this->pdo->prepare("
            UPDATE irs 
            SET status = 'rejected', catatan = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$catatan, $irs_id]);
        
        // Get data mahasiswa
        $stmt = $this->pdo->prepare("
            SELECT u.nama, u.email, m.nim
            FROM mahasiswa m
            JOIN users u ON m.user_id = u.id
            WHERE m.id = ?
        ");
        $stmt->execute([$mahasiswa_id]);
        $mahasiswa = $stmt->fetch();
        
        // Get daftar MK
        $stmt = $this->pdo->prepare("
            SELECT c.kode_mk, c.nama_mk, c.sks
            FROM irs i
            JOIN courses c ON i.course_id = c.id
            WHERE i.id = ?
        ");
        $stmt->execute([$irs_id]);
        $matakuliah = $stmt->fetchAll();
        
        // Kirim email
        $email_sent = false;
        if ($mahasiswa && !empty($mahasiswa['email'])) {
            require_once __DIR__ . '/../helper/EmailHelper.php';
            require_once __DIR__ . '/../template/email/irs_notification.php';
            
            $emailHelper = new EmailHelper();
            $body = renderIRSNotification([
                'nama' => $mahasiswa['nama'],
                'nim' => $mahasiswa['nim'],
                'semester' => $semester,
                'status' => 'rejected',
                'catatan' => $catatan,
                'total_sks' => 0,
                'matakuliah' => $matakuliah
            ]);
            
            $result = $emailHelper->send(
                $mahasiswa['email'],
                $mahasiswa['nama'],
                'IRS Anda Ditolak',
                $body
            );
            
            $email_sent = $result['success'];
        }
        
        return [
            'success' => true,
            'email_sent' => $email_sent
        ];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Verifikasi nilai dan kirim email notifikasi
 */
public function verifyGradeDenganEmail($grade_id) {
    try {
        // Verify grade
        $stmt = $this->pdo->prepare("
            UPDATE grades 
            SET status_verifikasi = 'verified', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$grade_id]);
        
        // Get detail nilai
        $stmt = $this->pdo->prepare("
            SELECT 
                g.*,
                u.nama, u.email,
                m.nim,
                c.kode_mk, c.nama_mk, c.sks
            FROM grades g
            JOIN mahasiswa m ON g.mahasiswa_id = m.id
            JOIN users u ON m.user_id = u.id
            JOIN courses c ON g.course_id = c.id
            WHERE g.id = ?
        ");
        $stmt->execute([$grade_id]);
        $nilai = $stmt->fetch();
        
        // Kirim email
        $email_sent = false;
        if ($nilai && !empty($nilai['email'])) {
            require_once __DIR__ . '/../helper/EmailHelper.php';
            require_once __DIR__ . '/../template/email/nilai_notification.php';
            
            $emailHelper = new EmailHelper();
            $body = renderNilaiNotification([
                'nama' => $nilai['nama'],
                'nim' => $nilai['nim'],
                'kode_mk' => $nilai['kode_mk'],
                'nama_mk' => $nilai['nama_mk'],
                'sks' => $nilai['sks'],
                'nilai_tugas' => $nilai['nilai_tugas'],
                'nilai_uts' => $nilai['nilai_uts'],
                'nilai_uas' => $nilai['nilai_uas'],
                'nilai_akhir' => $nilai['nilai_akhir']
            ]);
            
            $result = $emailHelper->send(
                $nilai['email'],
                $nilai['nama'],
                'Nilai Anda Telah Diverifikasi',
                $body
            );
            
            $email_sent = $result['success'];
        }
        
        return [
            'success' => true,
            'email_sent' => $email_sent
        ];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Kirim email akun baru
 */
public function sendAkunBaruEmail($user_id, $plain_password) {
    try {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || empty($user['email'])) {
            return ['success' => false, 'message' => 'User tidak ditemukan atau email kosong'];
        }
        
        require_once __DIR__ . '/../helper/EmailHelper.php';
        require_once __DIR__ . '/../template/email/akun_baru.php';
        
        $emailHelper = new EmailHelper();
        $body = renderAkunBaru([
            'nama' => $user['nama'],
            'username' => $user['username'],
            'password' => $plain_password,
            'role' => $user['role']
        ]);
        
        return $emailHelper->send(
            $user['email'],
            $user['nama'],
            'Akun SIMAK Anda Telah Dibuat',
            $body
        );
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
}
?>