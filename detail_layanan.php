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

$daftar_penitipan = $koneksi->query(
    "SELECT p.id_penitipan, h.nama_hewan, p.tgl_masuk
     FROM tbl_penitipan_15 p JOIN tbl_hewan_15 h ON h.id_hewan = p.id_hewan
     ORDER BY p.id_penitipan")->fetch_all(MYSQLI_ASSOC);
$daftar_layanan = $koneksi->query(
    "SELECT id_layanan, nama_layanan, tarif FROM tbl_layanan_15 ORDER BY id_layanan")->fetch_all(MYSQLI_ASSOC);

$message = "";
$mode = "create";
$form = ['id_detail' => '', 'id_penitipan' => '', 'id_layanan' => '', 'jumlah' => '1', 'tarif' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Permintaan tidak valid.");
    }
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'delete') {
            $id = trim($_POST['id_detail'] ?? '');
            $stmt = $koneksi->prepare("DELETE FROM tbl_detail_layanan_15 WHERE id_detail = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $stmt->close();
            header("Location: detail_layanan.php?success=3");
            exit();
        }

        if ($aksi === 'create' || $aksi === 'update') {
            $mode = $aksi;
            foreach ($form as $k => $v) {
                $form[$k] = trim($_POST[$k] ?? '');
            }

            if (in_array('', $form, true)) {
                $message = "Semua field wajib diisi!";
            } elseif (strlen($form['id_detail']) > 4) {
                $message = "Panjang ID melebihi batas kolom!";
            } elseif (!in_array($form['id_penitipan'], array_column($daftar_penitipan, 'id_penitipan'), true)) {
                $message = "Penitipan tidak valid!";
            } elseif (!in_array($form['id_layanan'], array_column($daftar_layanan, 'id_layanan'), true)) {
                $message = "Layanan tidak valid!";
            } elseif (!ctype_digit($form['jumlah']) || (int)$form['jumlah'] < 1) {
                $message = "Jumlah harus berupa bilangan bulat minimal 1!";
            } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $form['tarif'])) {
                $message = "Tarif harus berupa angka (maksimal 8 digit dan 2 desimal)!";
            } else {
                if ($aksi === 'create') {
                    $stmt = $koneksi->prepare(
                        "INSERT INTO tbl_detail_layanan_15 (id_detail, id_penitipan, id_layanan, jumlah, tarif)
                         VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $form['id_detail'], $form['id_penitipan'], $form['id_layanan'],
                                      $form['jumlah'], $form['tarif']);
                    $kode = 1;
                } else {
                    $stmt = $koneksi->prepare(
                        "UPDATE tbl_detail_layanan_15
                         SET id_penitipan = ?, id_layanan = ?, jumlah = ?, tarif = ?
                         WHERE id_detail = ?");
                    $stmt->bind_param("sssss", $form['id_penitipan'], $form['id_layanan'], $form['jumlah'],
                                      $form['tarif'], $form['id_detail']);
                    $kode = 2;
                }
                $stmt->execute();
                $stmt->close();
                header("Location: detail_layanan.php?success=$kode");
                exit();
            }
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $message = "Data tidak dapat dihapus karena digunakan pada tabel lain.";
        } elseif ($e->getCode() == 1062) {
            $message = "ID Detail sudah terdaftar!";
        } else {
            error_log($e->getMessage());
            $message = "Operasi database gagal. Periksa koneksi dan struktur tabel.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {
    $id = trim($_GET['edit']);
    $stmt = $koneksi->prepare("SELECT * FROM tbl_detail_layanan_15 WHERE id_detail = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $mode = 'update';
        $form = array_map(fn($v) => (string)$v, $row);
    } else {
        $message = "Data detail layanan tidak ditemukan!";
    }
}

$result = $koneksi->query(
    "SELECT d.*, l.nama_layanan, (d.jumlah * d.tarif) AS subtotal
     FROM tbl_detail_layanan_15 d
     JOIN tbl_layanan_15 l ON l.id_layanan = d.id_layanan
     ORDER BY d.id_detail ASC"
);
$pesan_sukses = [
    '1' => 'Data detail layanan berhasil ditambahkan!',
    '2' => 'Data detail layanan berhasil diperbarui!',
    '3' => 'Data detail layanan berhasil dihapus!',
];
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Detail Layanan - Petshop</title>
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
                <li><a href="layanan.php">Data Layanan</a></li>
                <li><a href="detail_layanan.php" class="active">Data Detail Layanan</a></li>
                <li><a href="hewan.php">Data Hewan</a></li>
            </ul>
        </nav>
    </header>

<div class="container">
    <h2>🧾 Data Detail Layanan</h2>

    <?php if ($message !== ''): ?>
        <div class="alert error"><?= aman($message); ?></div>
    <?php endif; ?>
    <?php if (isset($pesan_sukses[$success])): ?>
        <div class="alert success"><?= aman($pesan_sukses[$success]); ?></div>
    <?php endif; ?>

    <form method="POST" action="detail_layanan.php">
        <h3><?= $mode === 'update' ? "✏️ Edit Detail Layanan" : "➕ Tambah Detail Layanan"; ?></h3>
        <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="aksi" value="<?= $mode; ?>">

        <label>ID Detail:</label>
        <input type="text" name="id_detail" maxlength="4" placeholder="Contoh: 11DT"
               value="<?= aman($form['id_detail']); ?>" <?= $mode === 'update' ? 'readonly' : ''; ?> required>

        <label>Penitipan:</label>
        <select name="id_penitipan" required>
            <option value="">-- Pilih Penitipan --</option>
            <?php foreach ($daftar_penitipan as $p): ?>
                <option value="<?= aman($p['id_penitipan']); ?>" <?= $form['id_penitipan'] === $p['id_penitipan'] ? 'selected' : ''; ?>>
                    <?= aman($p['id_penitipan'] . ' - ' . $p['nama_hewan'] . ' (' . date('d-m-Y', strtotime($p['tgl_masuk'])) . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Layanan:</label>
        <select name="id_layanan" required>
            <option value="">-- Pilih Layanan --</option>
            <?php foreach ($daftar_layanan as $l): ?>
                <option value="<?= aman($l['id_layanan']); ?>" <?= $form['id_layanan'] === $l['id_layanan'] ? 'selected' : ''; ?>>
                    <?= aman($l['nama_layanan'] . ' (Rp ' . number_format((float)$l['tarif'], 0, ',', '.') . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Jumlah:</label>
        <input type="number" name="jumlah" min="1" step="1" value="<?= aman($form['jumlah']); ?>" required>

        <label>Tarif (Rp):</label>
        <input type="number" name="tarif" min="0" max="99999999.99" step="0.01" placeholder="Contoh: 50000"
               value="<?= aman($form['tarif']); ?>" required>

        <div class="form-aksi">
            <button type="submit"><?= $mode === 'update' ? '💾 Update' : '✅ Tambah'; ?></button>
            <?php if ($mode === 'update'): ?>
                <a href="detail_layanan.php" class="btn-cancel">Batal</a>
            <?php endif; ?>
        </div>
    </form>

    <h3>📋 Daftar Detail Layanan</h3>
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr><th>ID Detail</th><th>ID Penitipan</th><th>Layanan</th><th>Jumlah</th><th>Tarif</th><th>Subtotal</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= aman($row['id_detail']); ?></td>
                        <td><?= aman($row['id_penitipan']); ?></td>
                        <td><?= aman($row['nama_layanan']); ?></td>
                        <td><?= aman($row['jumlah']); ?></td>
                        <td>Rp <?= number_format((float)$row['tarif'], 0, ',', '.'); ?></td>
                        <td>Rp <?= number_format((float)$row['subtotal'], 0, ',', '.'); ?></td>
                        <td>
                            <a href="detail_layanan.php?edit=<?= urlencode($row['id_detail']); ?>" class="btn-edit">Edit</a>
                            <form method="POST" action="detail_layanan.php"
                                  onsubmit="return confirm('Yakin ingin menghapus detail layanan ini?');">
                                <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="aksi" value="delete">
                                <input type="hidden" name="id_detail" value="<?= aman($row['id_detail']); ?>">
                                <button type="submit" class="btn-delete">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="kosong">Belum ada data detail layanan.</td></tr>
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