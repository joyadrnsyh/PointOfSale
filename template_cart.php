<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!function_exists('format_rupiah')) include 'config.php';
$subtotal_harga = 0;
if (!empty($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $item) {
        $subtotal_harga += $item['subtotal'];
    }
}
?>
<?php if (!empty($_SESSION['keranjang'])) : ?>
    <form action="proses_transaksi.php" method="post" id="cart-form">
        <div class="cart-items">
            <?php foreach ($_SESSION['keranjang'] as $item) : ?>
            <div class="cart-item">
                <img src="uploads/<?= htmlspecialchars($item['gambar']); ?>" alt="<?= htmlspecialchars($item['nama']); ?>">
                <div class="item-details">
                    <h4><?= htmlspecialchars($item['nama']); ?></h4>
                    <p>
                        <?php if ($item['satuan'] == 'kg') { echo number_format($item['jumlah'], 3, ',', '.') . ' kg'; } 
                        else { echo (int)$item['jumlah'] . ' ' . htmlspecialchars($item['satuan']); } ?> @ <?= format_rupiah($item['harga']); ?>
                    </p>
                    <span class="item-subtotal"><?= format_rupiah($item['subtotal']); ?></span>
                </div>
                <a href="#" class="remove-item-btn" data-id="<?= $item['id']; ?>" title="Hapus"><i class="fas fa-times-circle"></i></a>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="cart-total">
            <span>Subtotal</span>
            <span class="total-amount" data-total="<?= $subtotal_harga ?>"><?= format_rupiah($subtotal_harga); ?></span>
        </div>
        <div class="discount-section">
            <label for="diskonInput">Diskon (Rp)</label>
            <input type="number" name="diskon" id="diskonInput" placeholder="0" min="0">
        </div>
        <div class="cart-grand-total">
            <span>Total Akhir</span>
            <span id="grandTotalAmount"><?= format_rupiah($subtotal_harga); ?></span>
        </div>
        <div class="payment-section">
            <h3><i class="fas fa-money-bill-wave"></i> Pembayaran</h3>
            <div class="form-group payment-method">
                <label><input type="radio" name="metode_pembayaran" value="CASH" checked onchange="togglePaymentDetails()"> <i class="fas fa-hand-holding-usd"></i> Cash</label>
                <label><input type="radio" name="metode_pembayaran" value="QRIS" onchange="togglePaymentDetails()"> <i class="fas fa-qrcode"></i> QRIS</label>
            </div>

            <div class="form-group" id="cash-details">
                <label for="uang_bayar">Uang Bayar:</label>
                <input type="number" id="uang_bayar" name="uang_bayar" placeholder="Masukkan jumlah uang" class="full-width">
            </div>

            <div class="form-group" id="qris-details" style="display: none;">
                <label for="qris_bank">Pilih Bank / E-Wallet:</label>
                <select id="qris_bank" name="payment_details" class="full-width">
                    <option value="BCA">BCA</option>
                    <option value="Mandiri">Mandiri (Livin')</option>
                    <option value="BRI">BRI (BRImo)</option>
                    <option value="BNI">BNI</option>
                    <option value="GoPay">GoPay</option>
                    <option value="OVO">OVO</option>
                    <option value="Dana">Dana</option>
                    <option value="ShopeePay">ShopeePay</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
        </div>
        <div class="checkout-actions"><button type="submit" name="checkout" class="btn-checkout"><i class="fas fa-check-circle"></i> Checkout</button><button href="#" id="reset-cart-btn" class="btn-reset"><i class="fas fa-trash"></i> Reset</button></div>
    </form>
<?php else : ?>
    <div class="empty-cart-message"><i class="fas fa-info-circle"></i><p>Keranjang kosong.</p><span>Klik produk untuk memulai.</span></div>
<?php endif; ?>