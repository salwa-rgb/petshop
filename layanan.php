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

$daftar_layanan = ['Mandi', 'Grooming', 'Vitamin'];

$message = "";
$mode = "create";
$form = ['id_layanan' => '', 'nama_layanan' => '', 'tarif' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Permintaan tidak valid.");
    }
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'delete') {
            $id = trim($_POST['id_layanan'] ?? '');
            $stmt = $koneksi->prepare("DELETE FROM tbl_layanan_15 WHERE id_layanan = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $stmt->close();
            header("Location: layanan.php?success=3");
            exit();
        }

        if ($aksi === 'create' || $aksi === 'update') {
            $mode = $aksi;
            $form = [
                'id_layanan'   => trim($_POST['id_layanan'] ?? ''),
                'nama_layanan' => trim($_POST['nama_layanan'] ?? ''),
                'tarif'        => trim($_POST['tarif'] ?? ''),
            ];

            if (in_array('', $form, true)) {
                $message = "Semua field wajib diisi!";
            } elseif (strlen($form['id_layanan']) > 4) {
                $message = "Panjang ID melebihi batas kolom!";
            } elseif (!in_array($form['nama_layanan'], $daftar_layanan, true)) {
                $message = "Nama layanan tidak valid!";
            } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $form['tarif'])) {
                $message = "Tarif harus berupa angka (maksimal 8 digit dan 2 desimal)!";
            } else {
                if ($aksi === 'create') {
                    $stmt = $koneksi->prepare(
                        "INSERT INTO tbl_layanan_15 (id_layanan, nama_layanan, tarif) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $form['id_layanan'], $form['nama_layanan'], $form['tarif']);
                    $kode = 1;
                } else {
                    $stmt = $koneksi->prepare(
                        "UPDATE tbl_layanan_15 SET nama_layanan = ?, tarif = ? WHERE id_layanan = ?");
                    $stmt->bind_param("sss", $form['nama_layanan'], $form['tarif'], $form['id_layanan']);
                    $kode = 2;
                }
                $stmt->execute();
                $stmt->close();
                header("Location: layanan.php?success=$kode");
                exit();
            }
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $message = "Data tidak dapat dihapus karena digunakan pada tabel lain.";
        } elseif ($e->getCode() == 1062) {
            $message = "ID Layanan sudah terdaftar!";
        } else {
            error_log($e->getMessage());
            $message = "Operasi database gagal. Periksa koneksi dan struktur tabel.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {
    $id = trim($_GET['edit']);
    $stmt = $koneksi->prepare("SELECT * FROM tbl_layanan_15 WHERE id_layanan = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $mode = 'update';
        $form = array_map(fn($v) => (string)$v, $row);
    } else {
        $message = "Data layanan tidak ditemukan!";
    }
}

$result = $koneksi->query("SELECT * FROM tbl_layanan_15 ORDER BY id_layanan ASC");
$pesan_sukses = [
    '1' => 'Data layanan berhasil ditambahkan!',
    '2' => 'Data layanan berhasil diperbarui!',
    '3' => 'Data layanan berhasil dihapus!',
];
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Layanan - Petshop</title>
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
                <li><a href="pembayaran.php">Data Pembayaran</a></li>
                <li><a href="layanan.php" class="active">Data Layanan</a></li>
                <li><a href="detail_layanan.php">Data Detail Layanan</a></li>
                <li><a href="hewan.php">Data Hewan</a></li>
            </ul>
        </nav>
    </header>

<div class="container">
    <h2>✂️ Data Layanan</h2>

    <?php if ($message !== ''): ?>
        <div class="alert error"><?= aman($message); ?></div>
    <?php endif; ?>
    <?php if (isset($pesan_sukses[$success])): ?>
        <div class="alert success"><?= aman($pesan_sukses[$success]); ?></div>
    <?php endif; ?>

    <form method="POST" action="layanan.php">
        <h3><?= $mode === 'update' ? "✏️ Edit Data Layanan" : "➕ Tambah Data Layanan"; ?></h3>
        <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="aksi" value="<?= $mode; ?>">

        <label>ID Layanan:</label>
        <input type="text" name="id_layanan" maxlength="4" placeholder="Contoh: 1LYN"
               value="<?= aman($form['id_layanan']); ?>" <?= $mode === 'update' ? 'readonly' : ''; ?> required>

        <label>Nama Layanan:</label>
        <select name="nama_layanan" required>
            <option value="">-- Pilih Layanan --</option>
            <?php foreach ($daftar_layanan as $n): ?>
                <option value="<?= aman($n); ?>" <?= $form['nama_layanan'] === $n ? 'selected' : ''; ?>><?= aman($n); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Tarif (Rp):</label>
        <input type="number" name="tarif" min="0" max="99999999.99" step="0.01" placeholder="Contoh: 50000"
               value="<?= aman($form['tarif']); ?>" required>

        <div class="form-aksi">
            <button type="submit"><?= $mode === 'update' ? '💾 Update' : '✅ Tambah'; ?></button>
            <?php if ($mode === 'update'): ?>
                <a href="layanan.php" class="btn-cancel">Batal</a>
            <?php endif; ?>
        </div>
    </form>

    <h3>📋 Daftar Layanan</h3>
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr><th>ID Layanan</th><th>Nama Layanan</th><th>Tarif</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= aman($row['id_layanan']); ?></td>
                        <td><?= aman($row['nama_layanan']); ?></td>
                        <td>Rp <?= number_format((float)$row['tarif'], 0, ',', '.'); ?></td>
                        <td>
                            <a href="layanan.php?edit=<?= urlencode($row['id_layanan']); ?>" class="btn-edit">Edit</a>
                            <form method="POST" action="layanan.php"
                                  onsubmit="return confirm('Yakin ingin menghapus data layanan ini?');">
                                <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="aksi" value="delete">
                                <input type="hidden" name="id_layanan" value="<?= aman($row['id_layanan']); ?>">
                                <button type="submit" class="btn-delete">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" class="kosong">Belum ada data layanan.</td></tr>
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