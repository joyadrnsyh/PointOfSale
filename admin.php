<?php
include 'config.php';

// Fungsi bantuan untuk redirect dengan notifikasi
function redirect_with_notification($message, $location = 'admin.php') {
    $_SESSION['notification'] = $message;
    header("Location: $location");
    exit();
}

// Menangani notifikasi untuk ditampilkan ke pengguna
$notification = '';
if (isset($_SESSION['notification'])) {
    $notification = $_SESSION['notification'];
    unset($_SESSION['notification']);
}

// Proses Tambah Barang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_barang'])) {
    $nama = trim($_POST['nama_produk']);
    $harga = filter_input(INPUT_POST, 'harga', FILTER_VALIDATE_INT);
    $satuan = $_POST['satuan'];
    $gambar = 'default.jpg';

    if (empty($nama) || $harga === false || $harga < 0 || !in_array($satuan, ['pcs', 'kg'])) {
        redirect_with_notification("Error: Data produk tidak valid.");
    }

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['gambar'];
        $target_dir = "uploads/";
        $safe_nama_file = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($file["name"]));
        $target_file = $target_dir . uniqid() . "_" . $safe_nama_file;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($imageFileType, $allowed_types)) redirect_with_notification("Error: Hanya file JPG, JPEG, PNG & GIF yang diperbolehkan.");
        if ($file["size"] > 2000000) redirect_with_notification("Error: Ukuran gambar terlalu besar (maks 2MB).");
        
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            $gambar = basename($target_file);
        } else {
            redirect_with_notification("Error: Gagal memindahkan file gambar.");
        }
    }

    $stmt = $koneksi->prepare("INSERT INTO produk (nama_produk, harga, satuan, gambar) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $nama, $harga, $satuan, $gambar);
    if ($stmt->execute()) {
        redirect_with_notification("Sukses: Barang baru berhasil ditambahkan!");
    } else {
        redirect_with_notification("Error: Gagal menambahkan barang. " . $stmt->error);
    }
    $stmt->close();
}

// Proses Hapus Barang Permanen
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_produk = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id_produk) redirect_with_notification("Error: ID Produk tidak valid.");

    $stmt_img = $koneksi->prepare("SELECT gambar FROM produk WHERE id_produk = ?");
    $stmt_img->bind_param("i", $id_produk);
    $stmt_img->execute();
    $result_img = $stmt_img->get_result()->fetch_assoc();
    $stmt_img->close();

    if ($result_img && $result_img['gambar'] != 'default.jpg' && file_exists('uploads/' . $result_img['gambar'])) {
        unlink('uploads/' . $result_img['gambar']);
    }

    $stmt_delete = $koneksi->prepare("DELETE FROM produk WHERE id_produk = ?");
    $stmt_delete->bind_param("i", $id_produk);
    if ($stmt_delete->execute()) {
        redirect_with_notification("Sukses: Produk berhasil dihapus permanen.");
    } else {
        redirect_with_notification("Error: Gagal menghapus produk. " . $stmt_delete->error);
    }
    $stmt_delete->close();
}

// Ambil semua data produk dari database
$query_produk = $koneksi->query("SELECT * FROM produk ORDER BY nama_produk ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Toko Sayuran</title>
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
        <header class="header"><h2>Kelola Barang</h2></header>

        <?php if ($notification): ?>
            <div class="notification <?= strpos(strtolower($notification), 'error') !== false ? 'error' : 'sukses' ?>">
                <?= htmlspecialchars($notification); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3><i class="fas fa-plus-circle"></i> Tambah Barang Baru</h3>
            <form class="form-grid" action="admin.php" method="post" enctype="multipart/form-data">
                <div class="form-group span-2"> <label for="nama_produk">Nama Sayuran:</label>
                    <input type="text" id="nama_produk" name="nama_produk" placeholder="Cth: Bayam Segar" required>
                </div>
                <div class="form-group">
                    <label for="harga">Harga:</label>
                    <input type="number" id="harga" name="harga" placeholder="Cth: 3000" min="0" required>
                </div>
                <div class="form-group">
                    <label for="satuan">Satuan:</label>
                    <select id="satuan" name="satuan" required>
                        <option value="pcs">Per Buah / Ikat (pcs)</option>
                        <option value="kg">Per Kilogram (kg)</option>
                    </select>
                </div>
                <div class="form-group span-2"> <label for="gambar">Gambar Produk (Maks 2MB):</label>
                    <input type="file" id="gambar" name="gambar" accept="image/jpeg, image/png, image/gif">
                </div>
                <div class="form-group span-full"> <button type="submit" name="tambah_barang"><i class="fas fa-plus"></i> Tambah Barang</button>
                </div>
            </form>
        </div>
        <div class="card">
            <h3><i class="fas fa-list"></i> Daftar Barang</h3>
            <div class="product-grid">
                <?php if ($query_produk && $query_produk->num_rows > 0) : ?>
                    <?php while ($produk = $query_produk->fetch_assoc()) : ?>
                    <div class="product-item">
                        <img src="uploads/<?= htmlspecialchars($produk['gambar']); ?>" alt="<?= htmlspecialchars($produk['nama_produk']); ?>">
                        <div class="product-item-info">
                            <h4><?= htmlspecialchars($produk['nama_produk']); ?></h4>
                            <p class="price"><?= format_rupiah($produk['harga']); ?> / <?= htmlspecialchars($produk['satuan']); ?></p>
                        </div>
                        <div class="product-item-actions">
                            <a href="edit_barang.php?id=<?= $produk['id_produk']; ?>" class="edit-button">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="admin.php?action=delete&id=<?= $produk['id_produk']; ?>" class="delete-button" onclick="return confirm('Anda yakin ingin MENGHAPUS PERMANEN produk \'<?= htmlspecialchars($produk['nama_produk']); ?>\'? Aksi ini tidak bisa dibatalkan!')">
                                <i class="fas fa-trash-alt"></i> Hapus
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else : ?>
                    <p>Belum ada barang di toko.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>