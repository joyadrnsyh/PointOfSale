<?php
include 'config.php';

$action = $_POST['action'] ?? '';

if ($action == 'add') {
    $id_produk = (int)($_POST['id_produk'] ?? 0);
    $jumlah = (float)($_POST['jumlah'] ?? 0);
    $manual_harga = isset($_POST['manual_harga']) && $_POST['manual_harga'] !== '' ? (int)$_POST['manual_harga'] : null;

    $stmt = $koneksi->prepare("SELECT * FROM produk WHERE id_produk = ?");
    $stmt->bind_param("i", $id_produk);
    $stmt->execute();
    $produk = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($produk && $jumlah > 0) {
        // --- PERBAIKAN UTAMA ADA DI SINI ---
        // Hitung subtotal final saat item ditambahkan, berdasarkan harga manual atau kalkulasi otomatis.
        $subtotal = ($manual_harga !== null) ? $manual_harga : round($produk['harga'] * $jumlah);

        $item = [
            'id' => $produk['id_produk'],
            'nama' => $produk['nama_produk'],
            'harga' => $produk['harga'], // Harga dasar per unit/kg
            'jumlah' => $jumlah,
            'gambar' => $produk['gambar'],
            'satuan' => $produk['satuan'],
            'subtotal' => $subtotal // Simpan subtotal final ke dalam array item
        ];
        // --- AKHIR PERBAIKAN ---

        if (!isset($_SESSION['keranjang'])) {
            $_SESSION['keranjang'] = [];
        }
        
        $item_exists = false;
        foreach ($_SESSION['keranjang'] as &$cart_item) {
            if ($cart_item['id'] == $id_produk) {
                // Selalu timpa item kiloan, tambahkan item satuan
                if ($cart_item['satuan'] == 'kg') {
                    $cart_item['jumlah'] = $jumlah;
                    $cart_item['subtotal'] = $subtotal; // Pastikan subtotal juga di-update
                } else {
                    $cart_item['jumlah'] += $jumlah;
                    $cart_item['subtotal'] += $subtotal; // Akumulasi subtotal untuk item pcs
                }
                $item_exists = true;
                break;
            }
        }
        if (!$item_exists) {
            $_SESSION['keranjang'][] = $item;
        }
    }
} elseif ($action == 'remove') {
    $id_to_remove = (int)($_POST['id_produk'] ?? 0);
    if (isset($_SESSION['keranjang'])) {
        foreach ($_SESSION['keranjang'] as $key => $item) {
            if ($item['id'] == $id_to_remove) {
                unset($_SESSION['keranjang'][$key]);
                break;
            }
        }
        $_SESSION['keranjang'] = array_values($_SESSION['keranjang']);
    }
} elseif ($action == 'reset') {
    unset($_SESSION['keranjang']);
}

include 'template_cart.php';
?>