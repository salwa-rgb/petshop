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

$message = "";
$mode = "create";
$form = ['id_pemilik' => '', 'nama_pemilik' => '', 'no_hp' => '', 'alamat' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Permintaan tidak valid.");
    }
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'delete') {
            $id = trim($_POST['id_pemilik'] ?? '');
            $stmt = $koneksi->prepare("DELETE FROM tbl_pemilik_15 WHERE id_pemilik = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $stmt->close();
            header("Location: pemilik.php?success=3");
            exit();
        }

        if ($aksi === 'create' || $aksi === 'update') {
            $mode = $aksi;
            $form = [
                'id_pemilik'   => trim($_POST['id_pemilik'] ?? ''),
                'nama_pemilik' => trim($_POST['nama_pemilik'] ?? ''),
                'no_hp'        => trim($_POST['no_hp'] ?? ''),
                'alamat'       => trim($_POST['alamat'] ?? ''),
            ];

            if ($form['id_pemilik'] === '' || $form['nama_pemilik'] === '' || $form['no_hp'] === '') {
                $message = "ID, nama, dan No. HP wajib diisi!";
            } elseif (strlen($form['id_pemilik']) > 4 || mb_strlen($form['nama_pemilik']) > 25 || strlen($form['no_hp']) > 13) {
                $message = "Panjang data melebihi batas kolom!";
            } elseif (!preg_match('/^\+?\d{8,13}$/', $form['no_hp'])) {
                $message = "No. HP harus berupa angka (8-13 digit)!";
            } else {
                $alamat = $form['alamat'] === '' ? null : $form['alamat'];
                if ($aksi === 'create') {
                    $stmt = $koneksi->prepare(
                        "INSERT INTO tbl_pemilik_15 (id_pemilik, nama_pemilik, no_hp, alamat) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $form['id_pemilik'], $form['nama_pemilik'], $form['no_hp'], $alamat);
                    $kode = 1;
                } else {
                    $stmt = $koneksi->prepare(
                        "UPDATE tbl_pemilik_15 SET nama_pemilik = ?, no_hp = ?, alamat = ? WHERE id_pemilik = ?");
                    $stmt->bind_param("ssss", $form['nama_pemilik'], $form['no_hp'], $alamat, $form['id_pemilik']);
                    $kode = 2;
                }
                $stmt->execute();
                $stmt->close();
                header("Location: pemilik.php?success=$kode");
                exit();
            }
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $message = "Data tidak dapat dihapus karena digunakan pada tabel lain.";
        } elseif ($e->getCode() == 1062) {
            $message = "ID Pemilik sudah terdaftar!";
        } else {
            error_log($e->getMessage());
            $message = "Operasi database gagal. Periksa koneksi dan struktur tabel.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {
    $id = trim($_GET['edit']);
    $stmt = $koneksi->prepare("SELECT * FROM tbl_pemilik_15 WHERE id_pemilik = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $mode = 'update';
        $form = array_map(fn($v) => (string)$v, $row);
    } else {
        $message = "Data pemilik tidak ditemukan!";
    }
}

$result = $koneksi->query("SELECT * FROM tbl_pemilik_15 ORDER BY id_pemilik ASC");
$pesan_sukses = [
    '1' => 'Data pemilik berhasil ditambahkan!',
    '2' => 'Data pemilik berhasil diperbarui!',
    '3' => 'Data pemilik berhasil dihapus!',
];
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pemilik - Petshop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>PETSHOP ME.</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="pemilik.php" class="active">Data Pemilik</a></li>
                <li><a href="petugas.php">Data Petugas</a></li>
                <li><a href="penitipan.php">Data Penitipan</a></li>
                <li><a href="kandang_hewan.php">Data Kandang Hewan</a></li>
                <li><a href="pembayaran.php">Data Pembayaran</a></li>
                <li><a href="layanan.php">Data Layanan</a></li>
                <li><a href="detail_layanan.php">Data Detail Layanan</a></li>
                <li><a href="hewan.php">Data Hewan</a></li>
            </ul>
        </nav>
    </header>

<div class="container">
    <h2>👤 Data Pemilik</h2>

    <?php if ($message !== ''): ?>
        <div class="alert error"><?= aman($message); ?></div>
    <?php endif; ?>
    <?php if (isset($pesan_sukses[$success])): ?>
        <div class="alert success"><?= aman($pesan_sukses[$success]); ?></div>
    <?php endif; ?>

    <form method="POST" action="pemilik.php">
        <h3><?= $mode === 'update' ? "✏️ Edit Data Pemilik" : "➕ Tambah Data Pemilik"; ?></h3>
        <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="aksi" value="<?= $mode; ?>">

        <label>ID Pemilik:</label>
        <input type="text" name="id_pemilik" maxlength="4" placeholder="Contoh: 1SAL"
               value="<?= aman($form['id_pemilik']); ?>" <?= $mode === 'update' ? 'readonly' : ''; ?> required>

        <label>Nama Pemilik:</label>
        <input type="text" name="nama_pemilik" maxlength="25" placeholder="Nama lengkap"
               value="<?= aman($form['nama_pemilik']); ?>" required>

        <label>No. HP:</label>
        <input type="text" name="no_hp" maxlength="13" placeholder="08xxxxxxxxxx"
               value="<?= aman($form['no_hp']); ?>" required>

        <label>Alamat:</label>
        <textarea name="alamat" rows="3" placeholder="Alamat lengkap"><?= aman($form['alamat']); ?></textarea>

        <div class="form-aksi">
            <button type="submit"><?= $mode === 'update' ? '💾 Update' : '✅ Tambah'; ?></button>
            <?php if ($mode === 'update'): ?>
                <a href="pemilik.php" class="btn-cancel">Batal</a>
            <?php endif; ?>
        </div>
    </form>

    <h3>📋 Daftar Pemilik</h3>
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr><th>ID Pemilik</th><th>Nama Pemilik</th><th>No. HP</th><th>Alamat</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= aman($row['id_pemilik']); ?></td>
                        <td><?= aman($row['nama_pemilik']); ?></td>
                        <td><?= aman($row['no_hp']); ?></td>
                        <td><?= aman($row['alamat'] ?? '-'); ?></td>
                        <td>
                            <a href="pemilik.php?edit=<?= urlencode($row['id_pemilik']); ?>" class="btn-edit">Edit</a>
                            <form method="POST" action="pemilik.php"
                                  onsubmit="return confirm('Yakin ingin menghapus data pemilik ini?');">
                                <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="aksi" value="delete">
                                <input type="hidden" name="id_pemilik" value="<?= aman($row['id_pemilik']); ?>">
                                <button type="submit" class="btn-delete">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="kosong">Belum ada data pemilik.</td></tr>
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