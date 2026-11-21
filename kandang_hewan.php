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
$form = ['id_kandang' => '', 'kode_kandang' => '', 'kategori' => '', 'kapasitas' => '', 'tarif_per_hari' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Permintaan tidak valid.");
    }
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'delete') {
            $id = trim($_POST['id_kandang'] ?? '');
            $stmt = $koneksi->prepare("DELETE FROM tbl_kandang_hewan_15 WHERE id_kandang = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $stmt->close();
            header("Location: kandang_hewan.php?success=3");
            exit();
        }

        if ($aksi === 'create' || $aksi === 'update') {
            $mode = $aksi;
            $form = [
                'id_kandang'     => trim($_POST['id_kandang'] ?? ''),
                'kode_kandang'   => trim($_POST['kode_kandang'] ?? ''),
                'kategori'       => trim($_POST['kategori'] ?? ''),
                'kapasitas'      => trim($_POST['kapasitas'] ?? ''),
                'tarif_per_hari' => trim($_POST['tarif_per_hari'] ?? ''),
            ];

            if (in_array('', $form, true)) {
                $message = "Semua field wajib diisi!";
            } elseif (strlen($form['id_kandang']) > 4 || strlen($form['kode_kandang']) > 2 || mb_strlen($form['kategori']) > 30) {
                $message = "Panjang data melebihi batas kolom!";
            } elseif (!ctype_digit($form['kapasitas']) || (int)$form['kapasitas'] < 1) {
                $message = "Kapasitas harus berupa bilangan bulat minimal 1!";
            } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $form['tarif_per_hari'])) {
                $message = "Tarif harus berupa angka (maksimal 8 digit dan 2 desimal)!";
            } else {
                if ($aksi === 'create') {
                    $stmt = $koneksi->prepare(
                        "INSERT INTO tbl_kandang_hewan_15 (id_kandang, kode_kandang, kategori, kapasitas, tarif_per_hari)
                         VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $form['id_kandang'], $form['kode_kandang'], $form['kategori'],
                                      $form['kapasitas'], $form['tarif_per_hari']);
                    $kode = 1;
                } else {
                    $stmt = $koneksi->prepare(
                        "UPDATE tbl_kandang_hewan_15
                         SET kode_kandang = ?, kategori = ?, kapasitas = ?, tarif_per_hari = ?
                         WHERE id_kandang = ?");
                    $stmt->bind_param("sssss", $form['kode_kandang'], $form['kategori'], $form['kapasitas'],
                                      $form['tarif_per_hari'], $form['id_kandang']);
                    $kode = 2;
                }
                $stmt->execute();
                $stmt->close();
                header("Location: kandang_hewan.php?success=$kode");
                exit();
            }
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $message = "Data tidak dapat dihapus karena digunakan pada tabel lain.";
        } elseif ($e->getCode() == 1062) {
            $message = "ID Kandang sudah terdaftar!";
        } else {
            error_log($e->getMessage());
            $message = "Operasi database gagal. Periksa koneksi dan struktur tabel.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {
    $id = trim($_GET['edit']);
    $stmt = $koneksi->prepare("SELECT * FROM tbl_kandang_hewan_15 WHERE id_kandang = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $mode = 'update';
        $form = array_map(fn($v) => (string)$v, $row);
    } else {
        $message = "Data kandang tidak ditemukan!";
    }
}

$result = $koneksi->query("SELECT * FROM tbl_kandang_hewan_15 ORDER BY id_kandang ASC");
$pesan_sukses = [
    '1' => 'Data kandang berhasil ditambahkan!',
    '2' => 'Data kandang berhasil diperbarui!',
    '3' => 'Data kandang berhasil dihapus!',
];
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kandang Hewan - Petshop</title>
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
                <li><a href="kandang_hewan.php" class="active">Data Kandang Hewan</a></li>
                <li><a href="pembayaran.php">Data Pembayaran</a></li>
                <li><a href="layanan.php">Data Layanan</a></li>
                <li><a href="detail_layanan.php">Data Detail Layanan</a></li>
                <li><a href="hewan.php">Data Hewan</a></li>
            </ul>
        </nav>
    </header>

<div class="container">
    <h2>🏠 Data Kandang Hewan</h2>

    <?php if ($message !== ''): ?>
        <div class="alert error"><?= aman($message); ?></div>
    <?php endif; ?>
    <?php if (isset($pesan_sukses[$success])): ?>
        <div class="alert success"><?= aman($pesan_sukses[$success]); ?></div>
    <?php endif; ?>

    <form method="POST" action="kandang_hewan.php">
        <h3><?= $mode === 'update' ? "✏️ Edit Data Kandang" : "➕ Tambah Data Kandang"; ?></h3>
        <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="aksi" value="<?= $mode; ?>">

        <label>ID Kandang:</label>
        <input type="text" name="id_kandang" maxlength="4" placeholder="Contoh: 1KND"
               value="<?= aman($form['id_kandang']); ?>" <?= $mode === 'update' ? 'readonly' : ''; ?> required>

        <label>Kode Kandang:</label>
        <input type="text" name="kode_kandang" maxlength="2" placeholder="Contoh: 01"
               value="<?= aman($form['kode_kandang']); ?>" required>

        <label>Kategori:</label>
        <input type="text" name="kategori" maxlength="30" placeholder="Contoh: Kucing Reguler"
               value="<?= aman($form['kategori']); ?>" required>

        <label>Kapasitas:</label>
        <input type="number" name="kapasitas" min="1" step="1" placeholder="Contoh: 1"
               value="<?= aman($form['kapasitas']); ?>" required>

        <label>Tarif per Hari (Rp):</label>
        <input type="number" name="tarif_per_hari" min="0" max="99999999.99" step="0.01" placeholder="Contoh: 50000"
               value="<?= aman($form['tarif_per_hari']); ?>" required>

        <div class="form-aksi">
            <button type="submit"><?= $mode === 'update' ? '💾 Update' : '✅ Tambah'; ?></button>
            <?php if ($mode === 'update'): ?>
                <a href="kandang_hewan.php" class="btn-cancel">Batal</a>
            <?php endif; ?>
        </div>
    </form>

    <h3>📋 Daftar Kandang Hewan</h3>
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr><th>ID Kandang</th><th>Kode</th><th>Kategori</th><th>Kapasitas</th><th>Tarif / Hari</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= aman($row['id_kandang']); ?></td>
                        <td><?= aman($row['kode_kandang']); ?></td>
                        <td><?= aman($row['kategori']); ?></td>
                        <td><?= aman($row['kapasitas']); ?></td>
                        <td>Rp <?= number_format((float)$row['tarif_per_hari'], 0, ',', '.'); ?></td>
                        <td>
                            <a href="kandang_hewan.php?edit=<?= urlencode($row['id_kandang']); ?>" class="btn-edit">Edit</a>
                            <form method="POST" action="kandang_hewan.php"
                                  onsubmit="return confirm('Yakin ingin menghapus data kandang ini?');">
                                <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="aksi" value="delete">
                                <input type="hidden" name="id_kandang" value="<?= aman($row['id_kandang']); ?>">
                                <button type="submit" class="btn-delete">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="kosong">Belum ada data kandang.</td></tr>
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