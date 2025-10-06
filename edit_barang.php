<?php
include 'config.php';

function redirect_with_notification($message, $location = 'admin.php') {
    $_SESSION['notification'] = $message;
    header("Location: $location");
    exit();
}

// Proses saat form perubahan disubmit (METHOD POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_perubahan'])) {
    $id_produk = filter_input(INPUT_POST, 'id_produk', FILTER_VALIDATE_INT);
    $nama = trim($_POST['nama_produk']);
    $harga = filter_input(INPUT_POST, 'harga', FILTER_VALIDATE_INT);
    $satuan = $_POST['satuan'];
    $gambar_lama = $_POST['gambar_lama'];
    $gambar = $gambar_lama;

    if (!$id_produk || empty($nama) || $harga === false || $harga < 0 || !in_array($satuan, ['pcs', 'kg'])) {
        redirect_with_notification("Error: Data yang dimasukkan tidak valid.");
    }

    // Cek apakah ada gambar baru yang diupload
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        if ($gambar_lama != 'default.jpg' && file_exists('uploads/' . $gambar_lama)) {
            unlink('uploads/' . $gambar_lama);
        }
        
        $file = $_FILES['gambar'];
        $target_dir = "uploads/";
        $safe_nama_file = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($file["name"]));
        $target_file = $target_dir . uniqid() . "_" . $safe_nama_file;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($imageFileType, $allowed_types)) redirect_with_notification("Error: Format gambar tidak valid.");
        if ($file["size"] > 2000000) redirect_with_notification("Error: Ukuran gambar terlalu besar.");
        
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            $gambar = basename($target_file);
        } else {
            redirect_with_notification("Error: Gagal mengupload gambar baru.");
        }
    }

    $stmt = $koneksi->prepare("UPDATE produk SET nama_produk = ?, harga = ?, satuan = ?, gambar = ? WHERE id_produk = ?");
    $stmt->bind_param("sissi", $nama, $harga, $satuan, $gambar, $id_produk);
    
    if ($stmt->execute()) {
        redirect_with_notification("Sukses: Data produk berhasil diperbarui!");
    } else {
        redirect_with_notification("Error: Gagal memperbarui data produk. " . $stmt->error);
    }
    $stmt->close();
}

// Proses saat halaman dimuat (METHOD GET)
$id_produk = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_produk) {
    redirect_with_notification("Error: ID Produk tidak ditemukan.");
}

$stmt = $koneksi->prepare("SELECT * FROM produk WHERE id_produk = ?");
$stmt->bind_param("i", $id_produk);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    redirect_with_notification("Error: Produk dengan ID tersebut tidak ada.");
}
$produk = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang - <?= htmlspecialchars($produk['nama_produk']) ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><h3>🥕 Admin Panel</h3></div>
        <ul class="nav-links">
            <li><a href="admin.php" class="active"><i class="fas fa-boxes"></i> Kelola Barang</a></li>
            <li><a href="index.php"><i class="fas fa-cash-register"></i> Menu Kasir</a></li>
        </ul>
    </div>
    <div class="main-content">
        <header class="header"><h2>Edit Barang</h2></header>

        <div class="card">
            <h3><i class="fas fa-edit"></i> Ubah Detail Produk</h3>
            <form action="edit_barang.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id_produk" value="<?= $produk['id_produk'] ?>">
                <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($produk['gambar']) ?>">

                <div class="form-group"><label for="nama_produk">Nama Sayuran:</label><input type="text" id="nama_produk" name="nama_produk" value="<?= htmlspecialchars($produk['nama_produk']) ?>" required></div>
                <div class="form-group"><label for="harga">Harga:</label><input type="number" id="harga" name="harga" value="<?= $produk['harga'] ?>" min="0" required></div>
                <div class="form-group"><label for="satuan">Satuan:</label><select id="satuan" name="satuan" required><option value="pcs" <?= ($produk['satuan'] == 'pcs') ? 'selected' : '' ?>>Per Buah / Ikat (pcs)</option><option value="kg" <?= ($produk['satuan'] == 'kg') ? 'selected' : '' ?>>Per Kilogram (kg)</option></select></div>
                <div class="form-group"><label>Gambar Saat Ini:</label><img src="uploads/<?= htmlspecialchars($produk['gambar']) ?>" alt="Gambar produk" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;"></div>
                <div class="form-group"><label for="gambar">Ganti Gambar (Opsional, Maks 2MB):</label><input type="file" id="gambar" name="gambar" accept="image/jpeg, image/png, image/gif"></div>
                <button type="submit" name="simpan_perubahan"><i class="fas fa-save"></i> Simpan Perubahan</button>
                <a href="admin.php" class="button" style="background-color: var(--text-color-light); margin-left: 10px;">Batal</a>
            </form>
        </div>
    </div>
</body>
</html>