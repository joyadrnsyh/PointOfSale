<?php
include 'config.php';

if (isset($_POST['checkout']) && !empty($_SESSION['keranjang'])) {
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $payment_details = $_POST['payment_details'] ?? null; // Ambil detail pembayaran
    $uang_bayar = isset($_POST['uang_bayar']) && $_POST['uang_bayar'] !== '' ? (int)$_POST['uang_bayar'] : 0;
    $diskon = isset($_POST['diskon']) && $_POST['diskon'] !== '' ? (int)$_POST['diskon'] : 0;
    
    $subtotal = 0;
    foreach ($_SESSION['keranjang'] as $item) {
        $subtotal += $item['subtotal'];
    }

    $total_akhir = $subtotal - $diskon;

    if ($metode_pembayaran == 'CASH' && $uang_bayar < $total_akhir) {
        echo "<script>alert('Uang tunai tidak cukup!'); window.location.href='index.php';</script>";
        exit();
    }
    
    // Jika metode QRIS, detail pembayaran tidak boleh kosong
    if ($metode_pembayaran == 'QRIS' && empty($payment_details)) {
        echo "<script>alert('Silakan pilih bank atau e-wallet untuk pembayaran QRIS!'); window.location.href='index.php';</script>";
        exit();
    }

    $kembalian = ($metode_pembayaran == 'CASH') ? $uang_bayar - $total_akhir : 0;

    $koneksi->begin_transaction();
    try {
        // PERBARUI QUERY INSERT
        $stmt_trans = $koneksi->prepare("INSERT INTO transaksi (total_harga, diskon, metode_pembayaran, payment_details, jumlah_bayar, kembalian) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_trans->bind_param("iissii", $subtotal, $diskon, $metode_pembayaran, $payment_details, $uang_bayar, $kembalian);
        $stmt_trans->execute();
        $id_transaksi_terakhir = $koneksi->insert_id;
        $stmt_trans->close();

        $stmt_detail = $koneksi->prepare("INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah, subtotal) VALUES (?, ?, ?, ?)");
        foreach ($_SESSION['keranjang'] as $item) {
            $stmt_detail->bind_param("iidi", $id_transaksi_terakhir, $item['id'], $item['jumlah'], $item['subtotal']);
            $stmt_detail->execute();
        }
        $stmt_detail->close();

        $koneksi->commit();
        $_SESSION['id_transaksi_terakhir'] = $id_transaksi_terakhir;
        unset($_SESSION['keranjang']);
        header("Location: struk.php");
        exit();
    } catch (mysqli_sql_exception $exception) {
        $koneksi->rollback();
        die("Transaksi Gagal: " . $exception->getMessage());
    }
} else {
    header("Location: index.php");
    exit();
}
?>