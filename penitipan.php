<?php
session_start();
require_once 'koneksi.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$koneksi->set_charset("utf8mb4");

function aman($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Daftar untuk dropdown
$daftar_hewan   = $koneksi->query("SELECT id_hewan, nama_hewan FROM tbl_hewan_15 ORDER BY id_hewan")->fetch_all(MYSQLI_ASSOC);
$daftar_kandang = $koneksi->query("SELECT id_kandang, kode_kandang, kategori FROM tbl_kandang_hewan_15 ORDER BY id_kandang")->fetch_all(MYSQLI_ASSOC);
$daftar_petugas = $koneksi->query("SELECT id_petugas, nama_petugas FROM tbl_petugas_15 ORDER BY id_petugas")->fetch_all(MYSQLI_ASSOC);

$message = "";
$mode = "create";
$form = ['id_penitipan' => '', 'id_hewan' => '', 'id_kandang' => '', 'id_petugas' => '',
         'tgl_masuk' => '', 'tgl_keluar' => '', 'biaya_penitipan' => ''];

function tanggal_valid($t) {
    $d = DateTime::createFromFormat('Y-m-d', $t);
    return $d && $d->format('Y-m-d') === $t;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Permintaan tidak valid.");
    }
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'delete') {
            $id = trim($_POST['id_penitipan'] ?? '');
            $stmt = $koneksi->prepare("DELETE FROM tbl_penitipan_15 WHERE id_penitipan = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $stmt->close();
            header("Location: penitipan.php?success=3");
            exit();
        }

        if ($aksi === 'create' || $aksi === 'update') {
            $mode = $aksi;
            foreach ($form as $k => $v) {
                $form[$k] = trim($_POST[$k] ?? '');
            }

            if ($form['id_penitipan'] === '' || $form['id_hewan'] === '' || $form['id_kandang'] === ''
                || $form['id_petugas'] === '' || $form['tgl_masuk'] === '') {
                $message = "ID, hewan, kandang, petugas, dan tanggal masuk wajib diisi!";
            } elseif (strlen($form['id_penitipan']) > 4) {
                $message = "Panjang ID melebihi batas kolom!";
            } elseif (!in_array($form['id_hewan'], array_column($daftar_hewan, 'id_hewan'), true)) {
                $message = "Hewan tidak valid!";
            } elseif (!in_array($form['id_kandang'], array_column($daftar_kandang, 'id_kandang'), true)) {
                $message = "Kandang tidak valid!";
            } elseif (!in_array($form['id_petugas'], array_column($daftar_petugas, 'id_petugas'), true)) {
                $message = "Petugas tidak valid!";
            } elseif (!tanggal_valid($form['tgl_masuk']) || ($form['tgl_keluar'] !== '' && !tanggal_valid($form['tgl_keluar']))) {
                $message = "Format tanggal tidak valid!";
            } elseif ($form['tgl_keluar'] !== '' && $form['tgl_keluar'] < $form['tgl_masuk']) {
                $message = "Tanggal keluar tidak boleh lebih awal dari tanggal masuk!";
            } elseif ($form['biaya_penitipan'] !== '' && !preg_match('/^\d{1,10}(\.\d{1,2})?$/', $form['biaya_penitipan'])) {
                $message = "Biaya harus berupa angka (maksimal 10 digit dan 2 desimal)!";
            } else {
                $keluar = $form['tgl_keluar'] === '' ? null : $form['tgl_keluar'];
                $biaya  = $form['biaya_penitipan'] === '' ? '0' : $form['biaya_penitipan'];
                if ($aksi === 'create') {
                    $stmt = $koneksi->prepare(
                        "INSERT INTO tbl_penitipan_15
                         (id_penitipan, id_hewan, id_kandang, id_petugas, tgl_masuk, tgl_keluar, biaya_penitipan)
                         VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $form['id_penitipan'], $form['id_hewan'], $form['id_kandang'],
                                      $form['id_petugas'], $form['tgl_masuk'], $keluar, $biaya);
                    $kode = 1;
                } else {
                    $stmt = $koneksi->prepare(
                        "UPDATE tbl_penitipan_15
                         SET id_hewan = ?, id_kandang = ?, id_petugas = ?, tgl_masuk = ?, tgl_keluar = ?, biaya_penitipan = ?
                         WHERE id_penitipan = ?");
                    $stmt->bind_param("sssssss", $form['id_hewan'], $form['id_kandang'], $form['id_petugas'],
                                      $form['tgl_masuk'], $keluar, $biaya, $form['id_penitipan']);
                    $kode = 2;
                }
                $stmt->execute();
                $stmt->close();
                header("Location: penitipan.php?success=$kode");
                exit();
            }
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $message = "Data tidak dapat dihapus karena digunakan pada tabel lain.";
        } elseif ($e->getCode() == 1062) {
            $message = "ID Penitipan sudah terdaftar!";
        } else {
            error_log($e->getMessage());
            $message = "Operasi database gagal. Periksa koneksi dan struktur tabel.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {
    $id = trim($_GET['edit']);
    $stmt = $koneksi->prepare("SELECT * FROM tbl_penitipan_15 WHERE id_penitipan = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $mode = 'update';
        $form = array_map(fn($v) => (string)$v, $row);
    } else {
        $message = "Data penitipan tidak ditemukan!";
    }
}

$result = $koneksi->query(
    "SELECT p.*, h.nama_hewan, k.kode_kandang, t.nama_petugas
     FROM tbl_penitipan_15 p
     JOIN tbl_hewan_15 h ON h.id_hewan = p.id_hewan
     JOIN tbl_kandang_hewan_15 k ON k.id_kandang = p.id_kandang
     JOIN tbl_petugas_15 t ON t.id_petugas = p.id_petugas
     ORDER BY p.id_penitipan ASC"
);
$pesan_sukses = [
    '1' => 'Data penitipan berhasil ditambahkan!',
    '2' => 'Data penitipan berhasil diperbarui!',
    '3' => 'Data penitipan berhasil dihapus!',
];
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penitipan - Petshop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>PETSHOP ME.</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="pemilik.php">Data Pemilik</a></li>
                <li><a href="petugas.php">Data Petugas</a></li>
                <li><a href="penitipan.php" class="active">Data Penitipan</a></li>
                <li><a href="kandang_hewan.php">Data Kandang Hewan</a></li>
                <li><a href="pembayaran.php">Data Pembayaran</a></li>
                <li><a href="layanan.php">Data Layanan</a></li>
                <li><a href="detail_layanan.php">Data Detail Layanan</a></li>
                <li><a href="hewan.php">Data Hewan</a></li>
            </ul>
        </nav>
    </header>

<div class="container">
    <h2>📦 Data Penitipan</h2>

    <?php if ($message !== ''): ?>
        <div class="alert error"><?= aman($message); ?></div>
    <?php endif; ?>
    <?php if (isset($pesan_sukses[$success])): ?>
        <div class="alert success"><?= aman($pesan_sukses[$success]); ?></div>
    <?php endif; ?>

    <form method="POST" action="penitipan.php">
        <h3><?= $mode === 'update' ? "✏️ Edit Data Penitipan" : "➕ Tambah Data Penitipan"; ?></h3>
        <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="aksi" value="<?= $mode; ?>">

        <label>ID Penitipan:</label>
        <input type="text" name="id_penitipan" maxlength="4" placeholder="Contoh: 1PNT"
               value="<?= aman($form['id_penitipan']); ?>" <?= $mode === 'update' ? 'readonly' : ''; ?> required>

        <label>Hewan:</label>
        <select name="id_hewan" required>
            <option value="">-- Pilih Hewan --</option>
            <?php foreach ($daftar_hewan as $h): ?>
                <option value="<?= aman($h['id_hewan']); ?>" <?= $form['id_hewan'] === $h['id_hewan'] ? 'selected' : ''; ?>>
                    <?= aman($h['id_hewan'] . ' - ' . $h['nama_hewan']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Kandang:</label>
        <select name="id_kandang" required>
            <option value="">-- Pilih Kandang --</option>
            <?php foreach ($daftar_kandang as $k): ?>
                <option value="<?= aman($k['id_kandang']); ?>" <?= $form['id_kandang'] === $k['id_kandang'] ? 'selected' : ''; ?>>
                    <?= aman($k['kode_kandang'] . ' - ' . $k['kategori']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Petugas:</label>
        <select name="id_petugas" required>
            <option value="">-- Pilih Petugas --</option>
            <?php foreach ($daftar_petugas as $t): ?>
                <option value="<?= aman($t['id_petugas']); ?>" <?= $form['id_petugas'] === $t['id_petugas'] ? 'selected' : ''; ?>>
                    <?= aman($t['id_petugas'] . ' - ' . $t['nama_petugas']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Tanggal Masuk:</label>
        <input type="date" name="tgl_masuk" value="<?= aman($form['tgl_masuk']); ?>" required>

        <label>Tanggal Keluar (opsional):</label>
        <input type="date" name="tgl_keluar" value="<?= aman($form['tgl_keluar']); ?>">

        <label>Biaya Penitipan (Rp):</label>
        <input type="number" name="biaya_penitipan" min="0" step="0.01" placeholder="Contoh: 50000"
               value="<?= aman($form['biaya_penitipan']); ?>">

        <div class="form-aksi">
            <button type="submit"><?= $mode === 'update' ? '💾 Update' : '✅ Tambah'; ?></button>
            <?php if ($mode === 'update'): ?>
                <a href="penitipan.php" class="btn-cancel">Batal</a>
            <?php endif; ?>
        </div>
    </form>

    <h3>📋 Daftar Penitipan</h3>
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr><th>ID</th><th>Hewan</th><th>Kandang</th><th>Petugas</th><th>Masuk</th><th>Keluar</th><th>Biaya</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= aman($row['id_penitipan']); ?></td>
                        <td><?= aman($row['nama_hewan']); ?></td>
                        <td><?= aman($row['kode_kandang']); ?></td>
                        <td><?= aman($row['nama_petugas']); ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tgl_masuk'])); ?></td>
                        <td><?= $row['tgl_keluar'] ? date('d-m-Y', strtotime($row['tgl_keluar'])) : '-'; ?></td>
                        <td>Rp <?= number_format((float)$row['biaya_penitipan'], 0, ',', '.'); ?></td>
                        <td>
                            <a href="penitipan.php?edit=<?= urlencode($row['id_penitipan']); ?>" class="btn-edit">Edit</a>
                            <form method="POST" action="penitipan.php"
                                  onsubmit="return confirm('Yakin ingin menghapus data penitipan ini?');">
                                <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="aksi" value="delete">
                                <input type="hidden" name="id_penitipan" value="<?= aman($row['id_penitipan']); ?>">
                                <button type="submit" class="btn-delete">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8" class="kosong">Belum ada data penitipan.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="index.php" class="btn-back">⬅️ Kembali ke Dashboard</a>
</div>

    <footer>
        <p>&copy; 2026 Petshop Me. All rights reserved.</p>
        <p>
            <a href="https://www.instagram.com/salwahanning?igsh=eXQ4OGFvejlybm9q" target="_blank">Instagram</a>
            <a href="https://www.tiktok.com/@random.salwa?_r=1&_t=ZS-979Bhj3TxcC" target="_blank">Tiktok</a>
        </p>
        <p>Contact: <a href="mailto:2559201015@umko.ac.id">2559201015@umko.ac.id</a></p>
    </footer>
</body>
</html>