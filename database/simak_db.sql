-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 14, 2026 at 04:17 AM
-- Server version: 8.0.30
-- PHP Version: 8.3.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `simak_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int NOT NULL,
  `sender_id` int NOT NULL,
  `sender_role` enum('admin','dosen') NOT NULL,
  `target_role` enum('all','admin','dosen','mahasiswa') DEFAULT 'all',
  `judul` varchar(100) NOT NULL,
  `pesan` text NOT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `sender_id`, `sender_role`, `target_role`, `judul`, `pesan`, `priority`, `is_read`, `created_at`) VALUES
(1, 1, 'admin', 'dosen', 'bla', 'awas kebakaran', 'high', 0, '2026-08-21 09:14:00'),
(2, 1, 'admin', 'all', 'ada pembunuhan', 'hati-hati', 'low', 0, '2026-08-24 01:55:39'),
(3, 1, 'admin', 'dosen', 'api', 'item', 'medium', 0, '2026-09-05 03:05:18');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_reads`
--

CREATE TABLE `announcement_reads` (
  `id` int NOT NULL,
  `announcement_id` int NOT NULL,
  `user_id` int NOT NULL,
  `read_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int NOT NULL,
  `kode_mk` varchar(20) NOT NULL,
  `nama_mk` varchar(100) NOT NULL,
  `sks` int NOT NULL,
  `semester` int NOT NULL,
  `program_studi` varchar(100) DEFAULT NULL,
  `dosen_id` int DEFAULT NULL,
  `ruang` varchar(50) DEFAULT NULL,
  `hari` varchar(20) DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `kapasitas` int DEFAULT '30',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `kode_mk`, `nama_mk`, `sks`, `semester`, `program_studi`, `dosen_id`, `ruang`, `hari`, `jam_mulai`, `jam_selesai`, `kapasitas`, `created_at`) VALUES
(4, 'TI001', 'Laravel', 4, 1, 'Teknik Informatika', 5, 'lab 1', 'Jumat', '12:00:00', '14:00:00', 30, '2026-09-11 03:54:11');

-- --------------------------------------------------------

--
-- Table structure for table `dosen`
--

CREATE TABLE `dosen` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `nidn` varchar(20) NOT NULL,
  `program_studi` varchar(100) DEFAULT NULL,
  `jabatan` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`id`, `user_id`, `nidn`, `program_studi`, `jabatan`) VALUES
(5, 15, '100', 'Teknik Informatika', 'Lektor Kepala');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int NOT NULL,
  `mahasiswa_id` int NOT NULL,
  `course_id` int NOT NULL,
  `semester` varchar(10) NOT NULL,
  `nilai_tugas` decimal(5,2) DEFAULT '0.00',
  `nilai_uts` decimal(5,2) DEFAULT '0.00',
  `nilai_uas` decimal(5,2) DEFAULT '0.00',
  `nilai_akhir` decimal(5,2) DEFAULT '0.00',
  `status_verifikasi` enum('draft','verified') DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `mahasiswa_id`, `course_id`, `semester`, `nilai_tugas`, `nilai_uts`, `nilai_uas`, `nilai_akhir`, `status_verifikasi`, `created_at`, `updated_at`) VALUES
(1, 7, 4, '1', '80.00', '95.00', '100.00', '92.50', 'verified', '2026-09-11 07:39:41', '2026-09-11 07:40:17');

-- --------------------------------------------------------

--
-- Table structure for table `irs`
--

CREATE TABLE `irs` (
  `id` int NOT NULL,
  `mahasiswa_id` int NOT NULL,
  `course_id` int NOT NULL,
  `semester` varchar(10) NOT NULL,
  `tahun_akademik` varchar(10) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `catatan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `irs`
--

INSERT INTO `irs` (`id`, `mahasiswa_id`, `course_id`, `semester`, `tahun_akademik`, `status`, `catatan`, `created_at`, `updated_at`) VALUES
(2, 7, 4, '1', NULL, 'approved', NULL, '2026-09-11 03:57:09', '2026-09-11 03:57:52');

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `nim` varchar(20) NOT NULL,
  `angkatan` int DEFAULT NULL,
  `program_studi` varchar(100) DEFAULT NULL,
  `semester` int DEFAULT '1',
  `ipk` decimal(3,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `angkatan`, `program_studi`, `semester`, `ipk`) VALUES
(7, 17, '202610051', 1, 'Teknik Informatika', 1, '0.00');

-- --------------------------------------------------------

--
-- Table structure for table `presensi`
--

CREATE TABLE `presensi` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `mahasiswa_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `pertemuan_ke` int DEFAULT NULL,
  `status` enum('hadir','izin','sakit','alpa') DEFAULT 'alpa',
  `keterangan` text,
  `waktu_absen` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `presensi`
--

INSERT INTO `presensi` (`id`, `course_id`, `mahasiswa_id`, `tanggal`, `pertemuan_ke`, `status`, `keterangan`, `waktu_absen`) VALUES
(3, 4, 7, '2026-09-14', NULL, 'hadir', NULL, '2026-09-14 00:45:04');

-- --------------------------------------------------------

--
-- Table structure for table `presensi_sessions`
--

CREATE TABLE `presensi_sessions` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `dosen_id` int NOT NULL,
  `kode` varchar(10) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_mulai` datetime NOT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `presensi_sessions`
--

INSERT INTO `presensi_sessions` (`id`, `course_id`, `dosen_id`, `kode`, `tanggal`, `waktu_mulai`, `waktu_selesai`, `is_active`, `created_at`) VALUES
(1, 4, 5, '050327', '2026-09-14', '2026-09-14 07:44:28', '2026-09-14 07:45:38', 0, '2026-09-14 00:44:28'),
(2, 4, 5, '121733', '2026-09-14', '2026-09-14 11:07:25', '2026-09-14 11:07:28', 0, '2026-09-14 04:07:25');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `dosen_id` int NOT NULL,
  `judul` varchar(100) NOT NULL,
  `deskripsi` text,
  `deadline` datetime NOT NULL,
  `bobot_nilai` decimal(5,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `course_id`, `dosen_id`, `judul`, `deskripsi`, `deadline`, `bobot_nilai`, `created_at`) VALUES
(1, 4, 5, 'buat aplikasi', '', '2026-09-11 12:00:00', '30.00', '2026-09-11 03:56:00');

-- --------------------------------------------------------

--
-- Table structure for table `task_submissions`
--

CREATE TABLE `task_submissions` (
  `id` int NOT NULL,
  `task_id` int NOT NULL,
  `mahasiswa_id` int NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `catatan` text,
  `status` enum('draft','submitted','late') DEFAULT 'draft',
  `nilai` decimal(5,2) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `task_submissions`
--

INSERT INTO `task_submissions` (`id`, `task_id`, `mahasiswa_id`, `file_path`, `catatan`, `status`, `nilai`, `submitted_at`) VALUES
(1, 1, 7, NULL, '', 'submitted', NULL, '2026-09-11 03:58:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nip_nim` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','dosen','mahasiswa') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama`, `nip_nim`, `email`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', NULL, 'admin@simak.com', 'admin', '2026-08-20 03:48:06', '2026-08-20 03:48:06'),
(15, 'dosen', '$2y$10$8m7m2wVLl7zkINyorz8CjOZzZRzkiGteAWbasS8aoE5Wtmpoqbuo.', 'dosen', '100', 'dosen@gmail.com', 'dosen', '2026-09-09 01:39:39', '2026-09-09 01:39:39'),
(17, 'azzam', '$2y$10$bDW6mMH3oEw/CbzKuzvwm.AWJcYXMkKS/tYwPzaj2Z/PV7hn9nddi', 'Azzam Maulana Hamzah', '202610051', 'azzam@gmail.com', 'mahasiswa', '2026-09-11 03:51:24', '2026-09-11 03:51:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_read` (`announcement_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_mk` (`kode_mk`),
  ADD KEY `dosen_id` (`dosen_id`);

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `nidn` (`nidn`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_grade` (`mahasiswa_id`,`course_id`,`semester`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `irs`
--
ALTER TABLE `irs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_irs` (`mahasiswa_id`,`course_id`,`semester`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `nim` (`nim`);

--
-- Indexes for table `presensi`
--
ALTER TABLE `presensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_presensi` (`course_id`,`mahasiswa_id`,`tanggal`),
  ADD KEY `mahasiswa_id` (`mahasiswa_id`);

--
-- Indexes for table `presensi_sessions`
--
ALTER TABLE `presensi_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `dosen_id` (`dosen_id`),
  ADD KEY `idx_active` (`is_active`,`course_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `dosen_id` (`dosen_id`);

--
-- Indexes for table `task_submissions`
--
ALTER TABLE `task_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_submission` (`task_id`,`mahasiswa_id`),
  ADD KEY `mahasiswa_id` (`mahasiswa_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `nip_nim` (`nip_nim`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `irs`
--
ALTER TABLE `irs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `presensi`
--
ALTER TABLE `presensi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `presensi_sessions`
--
ALTER TABLE `presensi_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `task_submissions`
--
ALTER TABLE `task_submissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  ADD CONSTRAINT `announcement_reads_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_reads_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `dosen`
--
ALTER TABLE `dosen`
  ADD CONSTRAINT `dosen_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `irs`
--
ALTER TABLE `irs`
  ADD CONSTRAINT `irs_ibfk_1` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `irs_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `mahasiswa_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `presensi`
--
ALTER TABLE `presensi`
  ADD CONSTRAINT `presensi_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `presensi_ibfk_2` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `presensi_sessions`
--
ALTER TABLE `presensi_sessions`
  ADD CONSTRAINT `presensi_sessions_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `presensi_sessions_ibfk_2` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_submissions`
--
ALTER TABLE `task_submissions`
  ADD CONSTRAINT `task_submissions_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_submissions_ibfk_2` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
