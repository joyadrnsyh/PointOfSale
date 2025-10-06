<?php
include 'config.php';

// Cek apakah ada ID transaksi di session, jika tidak, redirect ke halaman kasir
if (!isset($_SESSION['id_transaksi_terakhir']) || !is_numeric($_SESSION['id_transaksi_terakhir'])) {
    header("Location: index.php");
    exit();
}

$id_transaksi = $_SESSION['id_transaksi_terakhir'];

// --- PERBAIKAN UTAMA DIMULAI DI SINI ---

// 1. Ambil data transaksi utama menggunakan Prepared Statement
$stmt_trans = $koneksi->prepare("SELECT * FROM transaksi WHERE id_transaksi = ?");
$stmt_trans->bind_param("i", $id_transaksi);
$stmt_trans->execute();
$transaksi_res = $stmt_trans->get_result();

// Cek apakah transaksi ditemukan
if ($transaksi_res->num_rows === 0) {
    // Jika tidak ditemukan, hapus session dan redirect dengan pesan error
    unset($_SESSION['id_transaksi_terakhir']);
    die("Error: Transaksi tidak ditemukan.");
}
$transaksi = $transaksi_res->fetch_assoc();
$stmt_trans->close();


// 2. Ambil data detail transaksi menggunakan Prepared Statement
$stmt_detail = $koneksi->prepare("
    SELECT dt.*, p.nama_produk, p.harga, p.satuan 
    FROM detail_transaksi dt 
    LEFT JOIN produk p ON dt.id_produk = p.id_produk 
    WHERE dt.id_transaksi = ?
");
$stmt_detail->bind_param("i", $id_transaksi);
$stmt_detail->execute();
$detail_res = $stmt_detail->get_result();

// --- AKHIR PERBAIKAN ---

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Pembelian</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f4f4f4; font-family: 'Space Mono', monospace; color: #333; }
        .struk-wrapper { display: flex; flex-direction: column; align-items: center; padding: 20px; }
        .struk-container { max-width: 350px; width: 100%; background: #fff; padding: 20px; border: 1px dashed #ccc; }
        .struk-header { text-align: center; margin-bottom: 20px; }
        .struk-table { width: 100%; border-collapse: collapse; }
        .struk-table td { padding: 5px 0; }
        .text-right { text-align: right; }
        .footer { text-align: center; margin-top: 20px; font-size: 0.9em; }
        .print-actions { margin-top: 20px; text-align: center; }
        @media print { body * { visibility: hidden; } .struk-container, .struk-container * { visibility: visible; } .struk-container { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0; border: none;} .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="struk-wrapper">
        <div class="struk-container">
            <div class="struk-header">
                <h2>TOKO SAYURAN SEGAR</h2>
                <p>Jl. Merdeka No. 123</p>
                <p>Tanggal: <?= date('d M Y, H:i:s', strtotime($transaksi['waktu_transaksi'])); ?></p>
            </div>
            <table class="struk-table">
                <?php if ($detail_res->num_rows > 0): ?>
                    <?php while ($item = $detail_res->fetch_assoc()) : ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($item['nama_produk'] ?? 'Produk Telah Dihapus'); ?><br>
                            <small>
                            <?php
                            if (isset($item['satuan']) && $item['satuan'] == 'kg') { 
                                echo number_format($item['jumlah'], 3, ',', '.') . ' kg'; 
                            } else { 
                                echo (int)$item['jumlah'] . ' ' . htmlspecialchars($item['satuan'] ?? 'pcs'); 
                            } 
                            ?> x <?= format_rupiah($item['harga'] ?? 0); ?>
                            </small>
                        </td>
                        <td class="text-right"><?= format_rupiah($item['subtotal']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                
                <tr style="border-top: 1px dashed #000;">
                    <td>Subtotal</td><td class="text-right"><?= format_rupiah($transaksi['total_harga']); ?></td>
                </tr>
                <?php if ($transaksi['diskon'] > 0) : ?>
                <tr><td>Diskon</td><td class="text-right">- <?= format_rupiah($transaksi['diskon']); ?></td></tr>
                <tr>
                    <td>Metode Bayar</td>
                    <td class="text-right">
                        <?= htmlspecialchars($transaksi['metode_pembayaran']); ?>
                        <?php if ($transaksi['metode_pembayaran'] == 'QRIS' && !empty($transaksi['payment_details'])): ?>
                            (<?= htmlspecialchars($transaksi['payment_details']); ?>)
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr style="font-weight: bold;">
                    <td>Total Akhir</td><td class="text-right"><?= format_rupiah($transaksi['total_harga'] - $transaksi['diskon']); ?></td>
                </tr>
                
                <?php if ($transaksi['metode_pembayaran'] == 'CASH') : ?>
                <tr><td>Tunai</td><td class="text-right"><?= format_rupiah($transaksi['jumlah_bayar']); ?></td></tr>
                <tr><td>Kembalian</td><td class="text-right"><?= format_rupiah($transaksi['kembalian']); ?></td></tr>
                <?php endif; ?>
            </table>
            <div class="footer"><p>--- Terima Kasih ---</p></div>
        </div>
        <div class="print-actions no-print">
            <button onclick="window.print()">🖨️ Cetak Struk</button>
            <a href="index.php" class="button" style="background-color: #2ecc71;">Transaksi Baru</a>
        </div>
    </div>
</body>
</html>
<?php 
// Hapus session id transaksi setelah halaman dimuat untuk mencegah cetak ulang dengan me-refresh halaman
unset($_SESSION['id_transaksi_terakhir']); 
?>