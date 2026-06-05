-- =====================================================================
-- DATABASE : SPK Penilaian Kader PKK Terbaik - Metode MAUT
-- Posyandu Lansia Kelurahan Pulogebang, Cakung, Jakarta Timur
-- =====================================================================
-- Import melalui phpMyAdmin atau: mysql -u root -p < spk_maut.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS spk_maut
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spk_maut;

-- ----------------------------------------------------------------
-- Tabel User (autentikasi)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(60) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','user') DEFAULT 'admin',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password default untuk semua akun di bawah: admin123
INSERT INTO users (nama, username, password, role) VALUES
('Administrator', 'admin', '$2y$10$WdBbIQIfdLszFP/Fn3RqJ.AfwL2T8VaDWDwmonysRcLKij5GKqwUq', 'admin');

-- ----------------------------------------------------------------
-- Tabel Kriteria
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kriteria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(10) NOT NULL UNIQUE,
  nama VARCHAR(100) NOT NULL,
  bobot DECIMAL(5,3) NOT NULL DEFAULT 0,
  jenis ENUM('benefit','cost') NOT NULL DEFAULT 'benefit',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bobot kriteria sesuai Tabel 4.2 pada paper
INSERT INTO kriteria (kode, nama, bobot, jenis) VALUES
('C1', 'Kehadiran',          0.300, 'benefit'),
('C2', 'Pelayanan Kesehatan',0.250, 'benefit'),
('C3', 'Kerjasama Tim',      0.200, 'benefit'),
('C4', 'Tanggung Jawab',     0.150, 'benefit'),
('C5', 'Inisiatif',          0.100, 'benefit');

-- ----------------------------------------------------------------
-- Tabel Alternatif (Kader PKK)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS alternatif (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(10) NOT NULL UNIQUE,
  nama VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data alternatif sesuai Tabel 4.3 pada paper
INSERT INTO alternatif (kode, nama) VALUES
('A01', 'Kader 1'),
('A02', 'Kader 2'),
('A03', 'Kader 3'),
('A04', 'Kader 4'),
('A05', 'Kader 5');

-- ----------------------------------------------------------------
-- Tabel Nilai (matriks keputusan)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS nilai (
  id INT AUTO_INCREMENT PRIMARY KEY,
  alternatif_id INT NOT NULL,
  kriteria_id INT NOT NULL,
  nilai DECIMAL(6,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uniq_alt_kri (alternatif_id, kriteria_id),
  FOREIGN KEY (alternatif_id) REFERENCES alternatif(id) ON DELETE CASCADE,
  FOREIGN KEY (kriteria_id)   REFERENCES kriteria(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Matriks keputusan sesuai Tabel 4.4 pada paper
-- A01: 10 8 9 8 7
INSERT INTO nilai (alternatif_id, kriteria_id, nilai) VALUES
(1,1,10),(1,2,8),(1,3,9),(1,4,8),(1,5,7),
(2,1,8), (2,2,9),(2,3,8),(2,4,9),(2,5,8),
(3,1,7), (3,2,8),(3,3,7),(3,4,8),(3,5,9),
(4,1,9), (4,2,7),(4,3,8),(4,4,7),(4,5,8),
(5,1,6), (5,2,7),(5,3,6),(5,4,7),(5,5,7);

-- ----------------------------------------------------------------
-- Tabel Riwayat Perhitungan (historis)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS riwayat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
  metode VARCHAR(40) DEFAULT 'MAUT Linear',
  nama_penghitung VARCHAR(120) NULL,
  total_alternatif INT,
  total_kriteria INT,
  kader_terbaik VARCHAR(120),
  nilai_terbaik DECIMAL(8,4),
  detail LONGTEXT,
  user_id INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
