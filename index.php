<?php
include 'config.php';
$produk_list = $koneksi->query("SELECT * FROM produk ORDER BY nama_produk ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - Toko Sayuran Modern</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="cashier-page">
    <div id="product-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <button id="close-modal-btn" class="modal-close-btn"><i class="fas fa-times"></i></button>
            <div id="modal-body"></div>
        </div>
    </div>

    <div id="confirmModal" class="modal-overlay" style="display: none;">
        <div class="modal-content small-modal">
            <div class="modal-body-content">
                <h3 id="confirmTitle" class="modal-title">Konfirmasi</h3>
                <p id="confirmMessage">Apakah Anda yakin?</p>
                <div class="confirm-actions">
                    <button id="confirmYesBtn" class="button delete-button">Ya</button>
                    <button id="confirmNoBtn" class="button">Batal</button>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar">
        <div class="sidebar-header"><h3>🛒 Menu Kasir</h3></div>
        <ul class="nav-links">
            <li><a href="admin.php"><i class="fas fa-boxes"></i> Kelola Barang</a></li>
            <li><a href="index.php" class="active"><i class="fas fa-cash-register"></i> Menu Kasir</a></li>
        </ul>
    </div>
    <div class="main-content">
        <header class="header"><h2>Transaksi Baru</h2></header>
        <div class="content-grid">
            <div class="product-selection-scrollable">
                <div class="card product-selection">
                    <div class="product-selection-header">
                        <h3><i class="fas fa-carrot"></i> Pilih Barang</h3>
                        <div class="search-container"><i class="fas fa-search"></i><input type="text" id="searchInput" placeholder="Cari nama barang..."></div>
                    </div>
                    <div class="product-cards-container">
                        <div id="noResultsMessage" style="display: none; text-align: center; padding: 20px; color: var(--text-color-light);"><p>Produk tidak ditemukan.</p></div>
                        <?php if ($produk_list && $produk_list->num_rows > 0) : while ($p = $produk_list->fetch_assoc()) : ?>
                            <div class="product-card" data-id="<?= $p['id_produk']; ?>" data-nama="<?= htmlspecialchars($p['nama_produk']); ?>" data-harga="<?= $p['harga']; ?>" data-gambar="uploads/<?= htmlspecialchars($p['gambar']); ?>" data-satuan="<?= htmlspecialchars($p['satuan']); ?>">
                                <img src="uploads/<?= htmlspecialchars($p['gambar']); ?>" alt="<?= htmlspecialchars($p['nama_produk']); ?>">
                                <div class="product-info"><h4><?= htmlspecialchars($p['nama_produk']); ?></h4><p class="price"><?= format_rupiah($p['harga']); ?> / <?= htmlspecialchars($p['satuan']); ?></p></div>
                            </div>
                        <?php endwhile; else : ?><p>Belum ada produk.</p><?php endif; ?>
                    </div>
                </div>
            </div>
            <div id="cart-container" class="card cart-summary"><?php include 'template_cart.php'; ?></div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('product-modal'), closeModalBtn = document.getElementById('close-modal-btn'), modalBody = document.getElementById('modal-body'), cartContainer = document.getElementById('cart-container'), searchInput = document.getElementById('searchInput'), productCards = document.querySelectorAll('.product-card'), noResultsMessage = document.getElementById('noResultsMessage');
        
        // --- Elemen Modal Konfirmasi Kustom BARU ---
        const confirmModal = document.getElementById('confirmModal');
        const confirmTitle = document.getElementById('confirmTitle');
        const confirmMessage = document.getElementById('confirmMessage');
        const confirmYesBtn = document.getElementById('confirmYesBtn');
        const confirmNoBtn = document.getElementById('confirmNoBtn');

        // Fungsi untuk menampilkan modal konfirmasi kustom
        function showConfirmation(title, message, onConfirm) {
            confirmTitle.textContent = title;
            confirmMessage.textContent = message;
            confirmModal.style.display = 'flex';
            
            // Hapus event listener lama untuk menghindari pemanggilan ganda
            const newConfirmBtn = confirmYesBtn.cloneNode(true);
            confirmYesBtn.parentNode.replaceChild(newConfirmBtn, confirmYesBtn);
            
            newConfirmBtn.addEventListener('click', () => {
                onConfirm();
                hideConfirmation();
            });
        }
        function hideConfirmation() { confirmModal.style.display = 'none'; }
        confirmNoBtn.addEventListener('click', hideConfirmation);
        confirmModal.addEventListener('click', (e) => { if (e.target === confirmModal) hideConfirmation(); });
        
        // --- Sisa Logika ---
        searchInput.addEventListener('keyup', () => { 
            const searchTerm = searchInput.value.toLowerCase();
            let productsFound = 0;
            productCards.forEach(card => {
                const productName = card.dataset.nama.toLowerCase();
                if (productName.includes(searchTerm)) { card.style.display = 'block'; productsFound++; } 
                else { card.style.display = 'none'; }
            });
            noResultsMessage.style.display = (productsFound === 0) ? 'block' : 'none';
        });
        
        function openModal(id, nama, harga, gambar, satuan) {
            let formInputHtml = (satuan === 'kg') ? `
                <div class="input-toggle">
                    <button type="button" class="toggle-btn active" data-input="berat">Input Berat (kg)</button>
                    <button type="button" class="toggle-btn" data-input="harga">Input Harga (Rp)</button>
                </div>
                <div class="form-group" id="berat-input-group">
                    <label for="berat_kg">Berat (kg):</label>
                    <input type="number" id="berat_kg" name="jumlah" value="1" step="0.001" min="0.001" class="qty-input full-width">
                </div>
                <div class="form-group" id="harga-input-group" style="display:none;">
                    <label for="total_harga">Total Harga Otomatis (Rp):</label>
                    <input type="number" id="total_harga" placeholder="cth: 10000" step="100" min="100" class="qty-input full-width">
                </div>
                <div class="manual-price-container">
                    <label class="manual-price-toggle"><input type="checkbox" id="manualPriceToggle"> Harga Manual (Timpa Harga)</label>
                    <div class="form-group" id="manual-price-input-group" style="display:none;">
                        <label for="manual_harga">Harga Final Ditetapkan:</label>
                        <input type="number" id="manual_harga_input" name="manual_harga" class="qty-input full-width" placeholder="Masukkan harga dari timbangan">
                    </div>
                </div>` : `
                <div class="form-group">
                    <label for="jumlah">Jumlah:</label>
                    <div class="qty-control">
                        <button type="button" class="qty-btn minus">-</button>
                        <input type="number" id="jumlah" name="jumlah" value="1" min="1" required class="qty-input">
                        <button type="button" class="qty-btn plus">+</button>
                    </div>
                </div>`;

            modalBody.innerHTML = `
                <div class="modal-header-image" style="background-image: url('${gambar}');"></div>
                <div class="modal-body-content">
                    <h2 class="modal-title">${nama}</h2>
                    <p class="modal-price">${formatRupiah(harga)} / ${satuan}</p>
                    <form id="add-to-cart-modal-form">
                        <input type="hidden" name="id_produk" value="${id}">${formInputHtml}
                        <button type="submit" class="btn btn-add-to-cart-modal"><i class="fas fa-cart-plus"></i> Tambah ke Keranjang</button>
                    </form>
                </div>`;
            modal.style.display = 'flex';

            if (satuan === 'kg') {
                const beratInput = document.getElementById('berat_kg');
                const hargaInput = document.getElementById('total_harga');
                const manualToggle = document.getElementById('manualPriceToggle');
                const manualPriceGroup = document.getElementById('manual-price-input-group');
                const manualHargaInput = document.getElementById('manual_harga_input');
                const hargaPerKg = parseFloat(harga);
                let isManualMode = false;

                const updateHarga = () => { if (!isManualMode && beratInput.value) hargaInput.value = Math.round(parseFloat(beratInput.value) * hargaPerKg); };
                const updateBerat = () => { if (!isManualMode && hargaInput.value) beratInput.value = (parseFloat(hargaInput.value) / hargaPerKg).toFixed(3); };
                
                updateHarga();
                beratInput.addEventListener('input', updateHarga);
                hargaInput.addEventListener('input', updateBerat);

                manualToggle.addEventListener('change', () => {
                    isManualMode = manualToggle.checked;
                    manualPriceGroup.style.display = isManualMode ? 'block' : 'none';
                    hargaInput.disabled = isManualMode;
                    if (isManualMode) {
                        manualHargaInput.value = hargaInput.value;
                        manualHargaInput.focus();
                    } else {
                        updateHarga();
                    }
                });

                document.querySelectorAll('.toggle-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        if (isManualMode) return;
                        document.querySelector('.toggle-btn.active').classList.remove('active');
                        btn.classList.add('active');
                        document.getElementById('berat-input-group').style.display = (btn.dataset.input === 'berat') ? 'block' : 'none';
                        document.getElementById('harga-input-group').style.display = (btn.dataset.input === 'harga') ? 'block' : 'none';
                    });
                });
            }
        }
        function closeModal() { modal.style.display = 'none'; }
        
        productCards.forEach(card => card.addEventListener('click', () => openModal(card.dataset.id, card.dataset.nama, card.dataset.harga, card.dataset.gambar, card.dataset.satuan)));

        closeModalBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

        async function updateCart(formData) {
            try {
                const response = await fetch('api_update_cart.php', { method: 'POST', body: formData });
                cartContainer.innerHTML = await response.text();
                calculateTotals();
            } catch (error) { console.error('Error:', error); }
        }
        
        modalBody.addEventListener('submit', async (e) => {
            if (e.target.id === 'add-to-cart-modal-form') {
                e.preventDefault();
                const formData = new FormData(e.target);
                formData.append('action', 'add');
                await updateCart(formData);
                closeModal();
            }
        });

        cartContainer.addEventListener('input', (e) => { if (e.target.id === 'diskonInput' || e.target.id === 'uang_bayar') calculateTotals(); });

        cartContainer.addEventListener('click', async (e) => {
            const removeBtn = e.target.closest('.remove-item-btn');
            const resetBtn = e.target.closest('#reset-cart-btn');

            if (removeBtn) {
                e.preventDefault();
                showConfirmation('Konfirmasi Hapus', 'Anda yakin ingin menghapus item ini dari keranjang?', () => {
                    const formData = new FormData();
                    formData.append('action', 'remove');
                    formData.append('id_produk', removeBtn.dataset.id);
                    updateCart(formData);
                });
            }
            if (resetBtn) {
                e.preventDefault();
                showConfirmation('Konfirmasi Reset', 'Anda yakin ingin mengosongkan seluruh keranjang belanja?', () => {
                    const formData = new FormData();
                    formData.append('action', 'reset');
                    updateCart(formData);
                });
            }
        });

        modalBody.addEventListener('click', (e) => {
            const qtyInput = modalBody.querySelector('#jumlah');
            if (!qtyInput) return;
            let currentValue = parseInt(qtyInput.value);
            if (e.target.classList.contains('plus')) qtyInput.value = currentValue + 1;
            else if (e.target.classList.contains('minus') && currentValue > 1) qtyInput.value = currentValue - 1;
        });

        function calculateTotals() {
            const totalAmountEl = document.querySelector('.total-amount');
            if (!totalAmountEl) return;
            const subtotal = parseFloat(totalAmountEl.dataset.total) || 0;
            const diskon = parseFloat(document.getElementById('diskonInput').value) || 0;
            const grandTotal = subtotal - diskon;
            document.getElementById('grandTotalAmount').textContent = formatRupiah(grandTotal > 0 ? grandTotal : 0);
        }

        function formatRupiah(angka) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka); }
        calculateTotals();
    });
    
        // Ganti fungsi toggleUangBayar(isCash) yang lama dengan ini
        function togglePaymentDetails() {
            const isCash = document.querySelector('input[name="metode_pembayaran"][value="CASH"]').checked;
            const cashDetails = document.getElementById('cash-details');
            const qrisDetails = document.getElementById('qris-details');

            if (cashDetails && qrisDetails) {
                cashDetails.style.display = isCash ? 'block' : 'none';
                qrisDetails.style.display = isCash ? 'none' : 'block';
                if (!isCash) {
                    document.getElementById('uang_bayar').value = '';
                }
            }
        }

        // Tambahkan ini di dalam event listener DOMContentLoaded untuk memastikan tampilan awal benar
        document.addEventListener('DOMContentLoaded', () => {
            // ... (kode Anda yang lain di dalam sini) ...
            
            // Panggil fungsi ini sekali saat halaman dimuat
            togglePaymentDetails(); 
        });
    </script>
</body>
</html>