<?php
// ============================================
// controllers/MasterDataController.php
// Logika untuk Fakultas & Jurusan
// ============================================

class MasterDataController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // ============================================
    // FAKULTAS
    // ============================================
    
    public function getAllFakultas() {
        $stmt = $this->pdo->prepare("
            SELECT f.*, 
                   (SELECT COUNT(*) FROM jurusan j WHERE j.fakultas_id = f.id) as jumlah_jurusan,
                   (SELECT COUNT(*) FROM mahasiswa m WHERE m.fakultas_id = f.id) as jumlah_mahasiswa
            FROM fakultas f
            ORDER BY f.nama ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getFakultasById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM fakultas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getFakultasList() {
        $stmt = $this->pdo->prepare("SELECT id, kode, nama FROM fakultas ORDER BY nama ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function createFakultas($data) {
        try {
            $kode = strtoupper(trim($data['kode']));
            
            // Cek duplikat
            $stmt = $this->pdo->prepare("SELECT id FROM fakultas WHERE kode = ?");
            $stmt->execute([$kode]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Kode fakultas sudah digunakan!'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO fakultas (kode, nama, dekan)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $kode,
                trim($data['nama']),
                trim($data['dekan'] ?? '')
            ]);
            
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateFakultas($id, $data) {
        try {
            $kode = strtoupper(trim($data['kode']));
            
            // Cek duplikat (selain dirinya)
            $stmt = $this->pdo->prepare("SELECT id FROM fakultas WHERE kode = ? AND id != ?");
            $stmt->execute([$kode, $id]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Kode fakultas sudah digunakan!'];
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE fakultas SET kode = ?, nama = ?, dekan = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $kode,
                trim($data['nama']),
                trim($data['dekan'] ?? ''),
                $id
            ]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deleteFakultas($id) {
        try {
            // Cek apakah ada jurusan
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM jurusan WHERE fakultas_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetch()['total'];
            
            if ($count > 0) {
                return ['success' => false, 'message' => "Tidak bisa hapus, masih ada {$count} jurusan!"];
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM fakultas WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // ============================================
    // JURUSAN
    // ============================================
    
    public function getAllJurusan($fakultas_id = null) {
        $sql = "
            SELECT j.*, f.nama as fakultas_nama, f.kode as fakultas_kode,
                   (SELECT COUNT(*) FROM mahasiswa m WHERE m.jurusan_id = j.id) as jumlah_mahasiswa
            FROM jurusan j
            JOIN fakultas f ON j.fakultas_id = f.id
            WHERE 1=1
        ";
        $params = [];
        
        if ($fakultas_id) {
            $sql .= " AND j.fakultas_id = ?";
            $params[] = $fakultas_id;
        }
        
        $sql .= " ORDER BY f.nama ASC, j.nama ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getJurusanById($id) {
        $stmt = $this->pdo->prepare("
            SELECT j.*, f.nama as fakultas_nama, f.kode as fakultas_kode
            FROM jurusan j
            JOIN fakultas f ON j.fakultas_id = f.id
            WHERE j.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getJurusanList($fakultas_id = null) {
        $sql = "SELECT id, kode, nama, jenjang, fakultas_id FROM jurusan";
        $params = [];
        
        if ($fakultas_id) {
            $sql .= " WHERE fakultas_id = ?";
            $params[] = $fakultas_id;
        }
        
        $sql .= " ORDER BY nama ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function createJurusan($data) {
        try {
            $kode = strtoupper(trim($data['kode']));
            
            // Cek duplikat
            $stmt = $this->pdo->prepare("SELECT id FROM jurusan WHERE kode = ?");
            $stmt->execute([$kode]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Kode jurusan sudah digunakan!'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO jurusan (fakultas_id, kode, nama, jenjang, ketua_jurusan)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                intval($data['fakultas_id']),
                $kode,
                trim($data['nama']),
                $data['jenjang'],
                trim($data['ketua_jurusan'] ?? '')
            ]);
            
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateJurusan($id, $data) {
        try {
            $kode = strtoupper(trim($data['kode']));
            
            $stmt = $this->pdo->prepare("SELECT id FROM jurusan WHERE kode = ? AND id != ?");
            $stmt->execute([$kode, $id]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Kode jurusan sudah digunakan!'];
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE jurusan 
                SET fakultas_id = ?, kode = ?, nama = ?, jenjang = ?, ketua_jurusan = ?
                WHERE id = ?
            ");
            $stmt->execute([
                intval($data['fakultas_id']),
                $kode,
                trim($data['nama']),
                $data['jenjang'],
                trim($data['ketua_jurusan'] ?? ''),
                $id
            ]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deleteJurusan($id) {
        try {
            // Cek apakah ada mahasiswa
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM mahasiswa WHERE jurusan_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetch()['total'];
            
            if ($count > 0) {
                return ['success' => false, 'message' => "Tidak bisa hapus, masih ada {$count} mahasiswa!"];
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM jurusan WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // ============================================
    // HELPER: TAHUN AJARAN & SEMESTER OTOMATIS
    // ============================================
    
    /**
     * Hitung tahun ajaran otomatis berdasarkan bulan
     * Juli-Desember = tahun/tahun+1
     * Januari-Juni  = tahun-1/tahun
     */
    public static function getTahunAjaranOtomatis() {
        $bulan = intval(date('n'));
        $tahun = intval(date('Y'));
        
        if ($bulan >= 7) {
            // Juli - Desember
            return $tahun . '/' . ($tahun + 1);
        } else {
            // Januari - Juni
            return ($tahun - 1) . '/' . $tahun;
        }
    }
    
    /**
     * Hitung semester otomatis berdasarkan tingkat + bulan
     * Tingkat 1 + Ganjil = Semester 1
     * Tingkat 1 + Genap  = Semester 2
     * Tingkat 2 + Ganjil = Semester 3
     * dst.
     */
    public static function getSemesterOtomatis($tingkat) {
        $bulan = intval(date('n'));
        $tingkat = intval($tingkat);
        
        if ($bulan >= 7) {
            // Ganjil
            return ($tingkat * 2) - 1;
        } else {
            // Genap
            return $tingkat * 2;
        }
    }
    
    /**
     * Cek apakah sekarang semester ganjil
     */
    public static function isSemesterGanjil() {
        $bulan = intval(date('n'));
        return $bulan >= 7;
    }
    
    /**
     * Get label semester (Ganjil/Genap)
     */
    public static function getSemesterLabel() {
        return self::isSemesterGanjil() ? 'Ganjil' : 'Genap';
    }
}
?>