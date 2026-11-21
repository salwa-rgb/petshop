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

// Daftar pemilik untuk dropdown
$daftar_pemilik = $koneksi->query("SELECT id_pemilik, nama_pemilik FROM tbl_pemilik_15 ORDER BY id_pemilik")
                          ->fetch_all(MYSQLI_ASSOC);
$id_pemilik_valid = array_column($daftar_pemilik, 'id_pemilik');

$message = "";
$mode = "create";
$form = ['id_hewan' => '', 'id_pemilik' => '', 'jenis' => '', 'nama_hewan' => '', 'ras' => '', 'umur' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Permintaan tidak valid.");
    }
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'delete') {
            $id = trim($_POST['id_hewan'] ?? '');
            $stmt = $koneksi->prepare("DELETE FROM tbl_hewan_15 WHERE id_hewan = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $stmt->close();
            header("Location: hewan.php?success=3");
            exit();
        }

        if ($aksi === 'create' || $aksi === 'update') {
            $mode = $aksi;
            $form = [
                'id_hewan'   => trim($_POST['id_hewan'] ?? ''),
                'id_pemilik' => trim($_POST['id_pemilik'] ?? ''),
                'jenis'      => trim($_POST['jenis'] ?? ''),
                'nama_hewan' => trim($_POST['nama_hewan'] ?? ''),
                'ras'        => trim($_POST['ras'] ?? ''),
                'umur'       => trim($_POST['umur'] ?? ''),
            ];

            if ($form['id_hewan'] === '' || $form['id_pemilik'] === '' || $form['jenis'] === '' || $form['nama_hewan'] === '') {
                $message = "ID, pemilik, jenis, dan nama hewan wajib diisi!";
            } elseif (strlen($form['id_hewan']) > 4 || mb_strlen($form['jenis']) > 15
                      || mb_strlen($form['nama_hewan']) > 15 || mb_strlen($form['ras']) > 15) {
                $message = "Panjang data melebihi batas kolom!";
            } elseif (!in_array($form['id_pemilik'], $id_pemilik_valid, true)) {
                $message = "Pemilik tidak valid!";
            } elseif ($form['umur'] !== '' && (!ctype_digit($form['umur']) || (int)$form['umur'] > 100)) {
                $message = "Umur harus berupa bilangan bulat (0-100)!";
            } else {
                $ras  = $form['ras']  === '' ? null : $form['ras'];
                $umur = $form['umur'] === '' ? null : $form['umur'];
                if ($aksi === 'create') {
                    $stmt = $koneksi->prepare(
                        "INSERT INTO tbl_hewan_15 (id_hewan, id_pemilik, jenis, nama_hewan, ras, umur)
                         VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssss", $form['id_hewan'], $form['id_pemilik'], $form['jenis'],
                                      $form['nama_hewan'], $ras, $umur);
                    $kode = 1;
                } else {
                    $stmt = $koneksi->prepare(
                        "UPDATE tbl_hewan_15
                         SET id_pemilik = ?, jenis = ?, nama_hewan = ?, ras = ?, umur = ?
                         WHERE id_hewan = ?");
                    $stmt->bind_param("ssssss", $form['id_pemilik'], $form['jenis'], $form['nama_hewan'],
                                      $ras, $umur, $form['id_hewan']);
                    $kode = 2;
                }
                $stmt->execute();
                $stmt->close();
                header("Location: hewan.php?success=$kode");
                exit();
            }
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $message = "Data tidak dapat dihapus karena digunakan pada tabel lain.";
        } elseif ($e->getCode() == 1062) {
            $message = "ID Hewan sudah terdaftar!";
        } else {
            error_log($e->getMessage());
            $message = "Operasi database gagal. Periksa koneksi dan struktur tabel.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {
    $id = trim($_GET['edit']);
    $stmt = $koneksi->prepare("SELECT * FROM tbl_hewan_15 WHERE id_hewan = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $mode = 'update';
        $form = array_map(fn($v) => (string)$v, $row);
    } else {
        $message = "Data hewan tidak ditemukan!";
    }
}

$result = $koneksi->query(
    "SELECT h.*, p.nama_pemilik
     FROM tbl_hewan_15 h
     JOIN tbl_pemilik_15 p ON p.id_pemilik = h.id_pemilik
     ORDER BY h.id_hewan ASC"
);
$pesan_sukses = [
    '1' => 'Data hewan berhasil ditambahkan!',
    '2' => 'Data hewan berhasil diperbarui!',
    '3' => 'Data hewan berhasil dihapus!',
];
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Hewan - Petshop</title>
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
                <li><a href="detail_layanan.php">Data Detail Layanan</a></li>
                <li><a href="hewan.php" class="active">Data Hewan</a></li>
            </ul>
        </nav>
    </header>

<div class="container">
    <h2>🐾 Data Hewan</h2>

    <?php if ($message !== ''): ?>
        <div class="alert error"><?= aman($message); ?></div>
    <?php endif; ?>
    <?php if (isset($pesan_sukses[$success])): ?>
        <div class="alert success"><?= aman($pesan_sukses[$success]); ?></div>
    <?php endif; ?>

    <form method="POST" action="hewan.php">
        <h3><?= $mode === 'update' ? "✏️ Edit Data Hewan" : "➕ Tambah Data Hewan"; ?></h3>
        <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="aksi" value="<?= $mode; ?>">

        <label>ID Hewan:</label>
        <input type="text" name="id_hewan" maxlength="4" placeholder="Contoh: 1hwn"
               value="<?= aman($form['id_hewan']); ?>" <?= $mode === 'update' ? 'readonly' : ''; ?> required>

        <label>Pemilik:</label>
        <select name="id_pemilik" required>
            <option value="">-- Pilih Pemilik --</option>
            <?php foreach ($daftar_pemilik as $p): ?>
                <option value="<?= aman($p['id_pemilik']); ?>" <?= $form['id_pemilik'] === $p['id_pemilik'] ? 'selected' : ''; ?>>
                    <?= aman($p['id_pemilik'] . ' - ' . $p['nama_pemilik']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Jenis:</label>
        <input type="text" name="jenis" maxlength="15" placeholder="Kucing / Anjing / ..."
               value="<?= aman($form['jenis']); ?>" required>

        <label>Nama Hewan:</label>
        <input type="text" name="nama_hewan" maxlength="15" placeholder="Nama hewan"
               value="<?= aman($form['nama_hewan']); ?>" required>

        <label>Ras (opsional):</label>
        <input type="text" name="ras" maxlength="15" placeholder="Contoh: Persia"
               value="<?= aman($form['ras']); ?>">

        <label>Umur / tahun (opsional):</label>
        <input type="number" name="umur" min="0" max="100" step="1" placeholder="Contoh: 3"
               value="<?= aman($form['umur']); ?>">

        <div class="form-aksi">
            <button type="submit"><?= $mode === 'update' ? '💾 Update' : '✅ Tambah'; ?></button>
            <?php if ($mode === 'update'): ?>
                <a href="hewan.php" class="btn-cancel">Batal</a>
            <?php endif; ?>
        </div>
    </form>

    <h3>📋 Daftar Hewan</h3>
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr><th>ID Hewan</th><th>Pemilik</th><th>Jenis</th><th>Nama Hewan</th><th>Ras</th><th>Umur</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= aman($row['id_hewan']); ?></td>
                        <td><?= aman($row['nama_pemilik']); ?></td>
                        <td><?= aman($row['jenis']); ?></td>
                        <td><?= aman($row['nama_hewan']); ?></td>
                        <td><?= aman($row['ras'] ?? '-'); ?></td>
                        <td><?= aman($row['umur'] ?? '-'); ?></td>
                        <td>
                            <a href="hewan.php?edit=<?= urlencode($row['id_hewan']); ?>" class="btn-edit">Edit</a>
                            <form method="POST" action="hewan.php"
                                  onsubmit="return confirm('Yakin ingin menghapus data hewan ini?');">
                                <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="aksi" value="delete">
                                <input type="hidden" name="id_hewan" value="<?= aman($row['id_hewan']); ?>">
                                <button type="submit" class="btn-delete">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="kosong">Belum ada data hewan.</td></tr>
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