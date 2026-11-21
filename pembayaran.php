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

$daftar_metode = ['Qris', 'Cash', 'Debit', 'Ewallet'];
$daftar_status = ['Menunggu', 'Berhasil', 'Gagal'];
$daftar_penitipan = $koneksi->query(
    "SELECT p.id_penitipan, h.nama_hewan, p.tgl_masuk
     FROM tbl_penitipan_15 p JOIN tbl_hewan_15 h ON h.id_hewan = p.id_hewan
     ORDER BY p.id_penitipan")->fetch_all(MYSQLI_ASSOC);

$message = "";
$mode = "create";
$form = ['id_pembayaran' => '', 'id_penitipan' => '', 'tgl_bayar' => '', 'metode' => '', 'status_bayar' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Permintaan tidak valid.");
    }
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'delete') {
            $id = trim($_POST['id_pembayaran'] ?? '');
            $stmt = $koneksi->prepare("DELETE FROM tbl_pembayaran_15 WHERE id_pembayaran = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $stmt->close();
            header("Location: pembayaran.php?success=3");
            exit();
        }

        if ($aksi === 'create' || $aksi === 'update') {
            $mode = $aksi;
            foreach ($form as $k => $v) {
                $form[$k] = trim($_POST[$k] ?? '');
            }
            $tgl = DateTime::createFromFormat('Y-m-d', $form['tgl_bayar']);

            if (in_array('', $form, true)) {
                $message = "Semua field wajib diisi!";
            } elseif (strlen($form['id_pembayaran']) > 4) {
                $message = "Panjang ID melebihi batas kolom!";
            } elseif (!in_array($form['id_penitipan'], array_column($daftar_penitipan, 'id_penitipan'), true)) {
                $message = "Penitipan tidak valid!";
            } elseif (!$tgl || $tgl->format('Y-m-d') !== $form['tgl_bayar']) {
                $message = "Format tanggal tidak valid!";
            } elseif (!in_array($form['metode'], $daftar_metode, true)) {
                $message = "Metode pembayaran tidak valid!";
            } elseif (!in_array($form['status_bayar'], $daftar_status, true)) {
                $message = "Status pembayaran tidak valid!";
            } else {
                if ($aksi === 'create') {
                    $stmt = $koneksi->prepare(
                        "INSERT INTO tbl_pembayaran_15 (id_pembayaran, id_penitipan, tgl_bayar, metode, status_bayar)
                         VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $form['id_pembayaran'], $form['id_penitipan'], $form['tgl_bayar'],
                                      $form['metode'], $form['status_bayar']);
                    $kode = 1;
                } else {
                    $stmt = $koneksi->prepare(
                        "UPDATE tbl_pembayaran_15
                         SET id_penitipan = ?, tgl_bayar = ?, metode = ?, status_bayar = ?
                         WHERE id_pembayaran = ?");
                    $stmt->bind_param("sssss", $form['id_penitipan'], $form['tgl_bayar'], $form['metode'],
                                      $form['status_bayar'], $form['id_pembayaran']);
                    $kode = 2;
                }
                $stmt->execute();
                $stmt->close();
                header("Location: pembayaran.php?success=$kode");
                exit();
            }
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $message = "Data tidak dapat dihapus karena digunakan pada tabel lain.";
        } elseif ($e->getCode() == 1062) {
            $message = "ID Pembayaran sudah terdaftar!";
        } else {
            error_log($e->getMessage());
            $message = "Operasi database gagal. Periksa koneksi dan struktur tabel.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {
    $id = trim($_GET['edit']);
    $stmt = $koneksi->prepare("SELECT * FROM tbl_pembayaran_15 WHERE id_pembayaran = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $mode = 'update';
        $form = array_map(fn($v) => (string)$v, $row);
    } else {
        $message = "Data pembayaran tidak ditemukan!";
    }
}

$result = $koneksi->query("SELECT * FROM tbl_pembayaran_15 ORDER BY id_pembayaran ASC");
$pesan_sukses = [
    '1' => 'Data pembayaran berhasil ditambahkan!',
    '2' => 'Data pembayaran berhasil diperbarui!',
    '3' => 'Data pembayaran berhasil dihapus!',
];
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pembayaran - Petshop</title>
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
                <li><a href="penitipan.php">Data Penitipan</a></li>
                <li><a href="kandang_hewan.php">Data Kandang Hewan</a></li>
                <li><a href="pembayaran.php" class="active">Data Pembayaran</a></li>
                <li><a href="layanan.php">Data Layanan</a></li>
                <li><a href="detail_layanan.php">Data Detail Layanan</a></li>
                <li><a href="hewan.php">Data Hewan</a></li>
            </ul>
        </nav>
    </header>

<div class="container">
    <h2>💳 Data Pembayaran</h2>

    <?php if ($message !== ''): ?>
        <div class="alert error"><?= aman($message); ?></div>
    <?php endif; ?>
    <?php if (isset($pesan_sukses[$success])): ?>
        <div class="alert success"><?= aman($pesan_sukses[$success]); ?></div>
    <?php endif; ?>

    <form method="POST" action="pembayaran.php">
        <h3><?= $mode === 'update' ? "✏️ Edit Data Pembayaran" : "➕ Tambah Data Pembayaran"; ?></h3>
        <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="aksi" value="<?= $mode; ?>">

        <label>ID Pembayaran:</label>
        <input type="text" name="id_pembayaran" maxlength="4" placeholder="Contoh: 1BYR"
               value="<?= aman($form['id_pembayaran']); ?>" <?= $mode === 'update' ? 'readonly' : ''; ?> required>

        <label>Penitipan:</label>
        <select name="id_penitipan" required>
            <option value="">-- Pilih Penitipan --</option>
            <?php foreach ($daftar_penitipan as $p): ?>
                <option value="<?= aman($p['id_penitipan']); ?>" <?= $form['id_penitipan'] === $p['id_penitipan'] ? 'selected' : ''; ?>>
                    <?= aman($p['id_penitipan'] . ' - ' . $p['nama_hewan'] . ' (' . date('d-m-Y', strtotime($p['tgl_masuk'])) . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Tanggal Bayar:</label>
        <input type="date" name="tgl_bayar" value="<?= aman($form['tgl_bayar']); ?>" required>

        <label>Metode:</label>
        <select name="metode" required>
            <option value="">-- Pilih Metode --</option>
            <?php foreach ($daftar_metode as $m): ?>
                <option value="<?= aman($m); ?>" <?= $form['metode'] === $m ? 'selected' : ''; ?>><?= aman($m); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Status Bayar:</label>
        <select name="status_bayar" required>
            <option value="">-- Pilih Status --</option>
            <?php foreach ($daftar_status as $s): ?>
                <option value="<?= aman($s); ?>" <?= $form['status_bayar'] === $s ? 'selected' : ''; ?>><?= aman($s); ?></option>
            <?php endforeach; ?>
        </select>

        <div class="form-aksi">
            <button type="submit"><?= $mode === 'update' ? '💾 Update' : '✅ Tambah'; ?></button>
            <?php if ($mode === 'update'): ?>
                <a href="pembayaran.php" class="btn-cancel">Batal</a>
            <?php endif; ?>
        </div>
    </form>

    <h3>📋 Daftar Pembayaran</h3>
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr><th>ID Pembayaran</th><th>ID Penitipan</th><th>Tanggal Bayar</th><th>Metode</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= aman($row['id_pembayaran']); ?></td>
                        <td><?= aman($row['id_penitipan']); ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tgl_bayar'])); ?></td>
                        <td><?= aman($row['metode']); ?></td>
                        <td><?= aman($row['status_bayar']); ?></td>
                        <td>
                            <a href="pembayaran.php?edit=<?= urlencode($row['id_pembayaran']); ?>" class="btn-edit">Edit</a>
                            <form method="POST" action="pembayaran.php"
                                  onsubmit="return confirm('Yakin ingin menghapus data pembayaran ini?');">
                                <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="aksi" value="delete">
                                <input type="hidden" name="id_pembayaran" value="<?= aman($row['id_pembayaran']); ?>">
                                <button type="submit" class="btn-delete">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="kosong">Belum ada data pembayaran.</td></tr>
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