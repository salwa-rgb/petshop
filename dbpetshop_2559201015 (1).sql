-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2026 at 07:51 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbpetshop_2559201015`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_detail_layanan_15`
--

CREATE TABLE `tbl_detail_layanan_15` (
  `id_detail` varchar(4) NOT NULL,
  `id_penitipan` varchar(4) NOT NULL,
  `id_layanan` varchar(4) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `tarif` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_detail_layanan_15`
--

INSERT INTO `tbl_detail_layanan_15` (`id_detail`, `id_penitipan`, `id_layanan`, `jumlah`, `tarif`) VALUES
('11DT', '1PNT', '1LYN', 1, 5000000.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_hewan_15`
--

CREATE TABLE `tbl_hewan_15` (
  `id_hewan` varchar(4) NOT NULL,
  `id_pemilik` varchar(4) NOT NULL,
  `jenis` varchar(15) NOT NULL,
  `nama_hewan` varchar(15) NOT NULL,
  `ras` varchar(15) DEFAULT NULL,
  `umur` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_hewan_15`
--

INSERT INTO `tbl_hewan_15` (`id_hewan`, `id_pemilik`, `jenis`, `nama_hewan`, `ras`, `umur`) VALUES
('1hwn', '1SAL', 'Kucing', 'Cuki', 'Persia', 3);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_kandang_hewan_15`
--

CREATE TABLE `tbl_kandang_hewan_15` (
  `id_kandang` varchar(4) NOT NULL,
  `kode_kandang` varchar(2) NOT NULL,
  `kategori` varchar(30) NOT NULL,
  `kapasitas` int(11) NOT NULL,
  `tarif_per_hari` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_kandang_hewan_15`
--

INSERT INTO `tbl_kandang_hewan_15` (`id_kandang`, `kode_kandang`, `kategori`, `kapasitas`, `tarif_per_hari`) VALUES
('1KND', '01', 'Kucing Reguler', 1, 5000000.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_layanan_15`
--

CREATE TABLE `tbl_layanan_15` (
  `id_layanan` varchar(4) NOT NULL,
  `nama_layanan` enum('Mandi','Grooming','Vitamin') NOT NULL,
  `tarif` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_layanan_15`
--

INSERT INTO `tbl_layanan_15` (`id_layanan`, `nama_layanan`, `tarif`) VALUES
('1LYN', 'Grooming', 5000000.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pembayaran_15`
--

CREATE TABLE `tbl_pembayaran_15` (
  `id_pembayaran` varchar(4) NOT NULL,
  `id_penitipan` varchar(4) NOT NULL,
  `tgl_bayar` date NOT NULL,
  `metode` enum('Qris','Cash','Debit','Ewallet') NOT NULL,
  `status_bayar` enum('Menunggu','Berhasil','Gagal','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_pembayaran_15`
--

INSERT INTO `tbl_pembayaran_15` (`id_pembayaran`, `id_penitipan`, `tgl_bayar`, `metode`, `status_bayar`) VALUES
('1BYR', '1PNT', '2026-11-05', 'Qris', 'Menunggu');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pemilik_15`
--

CREATE TABLE `tbl_pemilik_15` (
  `id_pemilik` varchar(4) NOT NULL,
  `nama_pemilik` varchar(25) NOT NULL,
  `no_hp` varchar(13) NOT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_pemilik_15`
--

INSERT INTO `tbl_pemilik_15` (`id_pemilik`, `nama_pemilik`, `no_hp`, `alamat`) VALUES
('1SAL', 'Salwa Hanning Azahra', '085768458075', 'Way Kanan, Negeri Besar, Negeri Jaya, RT 001');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_penitipan_15`
--

CREATE TABLE `tbl_penitipan_15` (
  `id_penitipan` varchar(4) NOT NULL,
  `id_hewan` varchar(4) NOT NULL,
  `id_kandang` varchar(4) NOT NULL,
  `id_petugas` varchar(4) NOT NULL,
  `tgl_masuk` date NOT NULL,
  `tgl_keluar` date DEFAULT NULL,
  `biaya_penitipan` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_penitipan_15`
--

INSERT INTO `tbl_penitipan_15` (`id_penitipan`, `id_hewan`, `id_kandang`, `id_petugas`, `tgl_masuk`, `tgl_keluar`, `biaya_penitipan`) VALUES
('1PNT', '1hwn', '1KND', '1PTS', '2026-11-04', '2026-11-05', 50000.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_petugas_15`
--

CREATE TABLE `tbl_petugas_15` (
  `id_petugas` varchar(4) NOT NULL,
  `nama_petugas` varchar(25) NOT NULL,
  `no_hp` varchar(13) NOT NULL,
  `jabatan` enum('Admin','Dokter Hewan','Groomer','Penjaga Kandang') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_petugas_15`
--

INSERT INTO `tbl_petugas_15` (`id_petugas`, `nama_petugas`, `no_hp`, `jabatan`) VALUES
('1PTS', 'Dadang Dungding', '085768458075', 'Dokter Hewan');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_detail_layanan_15`
--
ALTER TABLE `tbl_detail_layanan_15`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_penitipan` (`id_penitipan`),
  ADD KEY `id_layanan` (`id_layanan`);

--
-- Indexes for table `tbl_hewan_15`
--
ALTER TABLE `tbl_hewan_15`
  ADD PRIMARY KEY (`id_hewan`),
  ADD KEY `id_pemilik` (`id_pemilik`);

--
-- Indexes for table `tbl_kandang_hewan_15`
--
ALTER TABLE `tbl_kandang_hewan_15`
  ADD PRIMARY KEY (`id_kandang`);

--
-- Indexes for table `tbl_layanan_15`
--
ALTER TABLE `tbl_layanan_15`
  ADD PRIMARY KEY (`id_layanan`);

--
-- Indexes for table `tbl_pembayaran_15`
--
ALTER TABLE `tbl_pembayaran_15`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `id_penitipan` (`id_penitipan`);

--
-- Indexes for table `tbl_pemilik_15`
--
ALTER TABLE `tbl_pemilik_15`
  ADD PRIMARY KEY (`id_pemilik`);

--
-- Indexes for table `tbl_penitipan_15`
--
ALTER TABLE `tbl_penitipan_15`
  ADD PRIMARY KEY (`id_penitipan`),
  ADD KEY `id_hewan` (`id_hewan`),
  ADD KEY `id_kandang` (`id_kandang`),
  ADD KEY `id_petugas` (`id_petugas`);

--
-- Indexes for table `tbl_petugas_15`
--
ALTER TABLE `tbl_petugas_15`
  ADD PRIMARY KEY (`id_petugas`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_detail_layanan_15`
--
ALTER TABLE `tbl_detail_layanan_15`
  ADD CONSTRAINT `tbl_detail_layanan_15_ibfk_1` FOREIGN KEY (`id_penitipan`) REFERENCES `tbl_penitipan_15` (`id_penitipan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_detail_layanan_15_ibfk_2` FOREIGN KEY (`id_layanan`) REFERENCES `tbl_layanan_15` (`id_layanan`) ON UPDATE CASCADE;

--
-- Constraints for table `tbl_hewan_15`
--
ALTER TABLE `tbl_hewan_15`
  ADD CONSTRAINT `tbl_hewan_15_ibfk_1` FOREIGN KEY (`id_pemilik`) REFERENCES `tbl_pemilik_15` (`id_pemilik`) ON UPDATE CASCADE;

--
-- Constraints for table `tbl_pembayaran_15`
--
ALTER TABLE `tbl_pembayaran_15`
  ADD CONSTRAINT `tbl_pembayaran_15_ibfk_1` FOREIGN KEY (`id_penitipan`) REFERENCES `tbl_penitipan_15` (`id_penitipan`) ON UPDATE CASCADE;

--
-- Constraints for table `tbl_penitipan_15`
--
ALTER TABLE `tbl_penitipan_15`
  ADD CONSTRAINT `tbl_penitipan_15_ibfk_1` FOREIGN KEY (`id_hewan`) REFERENCES `tbl_hewan_15` (`id_hewan`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_penitipan_15_ibfk_2` FOREIGN KEY (`id_kandang`) REFERENCES `tbl_kandang_hewan_15` (`id_kandang`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_penitipan_15_ibfk_3` FOREIGN KEY (`id_petugas`) REFERENCES `tbl_petugas_15` (`id_petugas`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
