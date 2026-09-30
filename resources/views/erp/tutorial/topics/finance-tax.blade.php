<x-tutorial.section title="Dua langkah: jenis pajak, lalu setoran">
    <p>Pajak di aplikasi ini dikelola dalam dua tahap. Pertama, Anda mendefinisikan <strong>jenis pajak</strong> (nama, tipe, tarif) yang dipilih saat mencatat pemasukan atau pengeluaran. Kedua, ketika pajak sudah disetor ke kantor pajak, Anda mencatat <strong>setoran pajak</strong> agar kewajibannya lunas.</p>
</x-tutorial.section>

<x-tutorial.section title="Jenis pajak (Taxes)">
    <x-tutorial.steps>
        <li>Buka <strong>Finance → Taxes</strong> dan klik <strong>Add Tax</strong>.</li>
        <li>Isi <strong>Name</strong> (misalnya <em>PPN 12%</em>) dan <strong>Rate (%)</strong>.</li>
        <li>Pilih <strong>Type</strong>:
            <ul>
                <li><em>PPN / VAT</em>: ditambahkan ke jumlah transaksi.</li>
                <li><em>PPh / Withholding</em>: dipotong dari jumlah transaksi.</li>
                <li><em>PPh Final</em>: dibayar perusahaan; jumlah transaksi diterima penuh dan pajaknya dicatat sebagai kewajiban (untuk UMKM).</li>
            </ul>
        </li>
        <li>Centang <strong>Active</strong> lalu simpan. Hanya pajak aktif yang muncul di form transaksi.</li>
    </x-tutorial.steps>
    <p><strong>Pajak default untuk pemasukan</strong> dipilih di <em>Settings → System Settings → Default income tax</em>. Pajak itu otomatis terpilih pada setiap pemasukan baru, termasuk pembayaran customer training, dan tetap bisa diganti per transaksi.</p>
    <x-tutorial.note>Pajak yang sudah dipakai transaksi tidak bisa dihapus; nonaktifkan saja.</x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="Mencatat setoran pajak (Tax Payments)">
    <x-tutorial.steps>
        <li>Buka <strong>Finance → Tax Payments</strong> dan klik <strong>Record Tax Payment</strong>.</li>
        <li>Pilih <strong>Tax</strong>: <em>PPN / VAT</em>, <em>PPh / Withholding</em>, atau <em>PPh Final (UMKM)</em>, dan <strong>Tax period (month)</strong> yang disetor (bawaannya bulan lalu).</li>
        <li>Isi <strong>Payment date</strong>, <strong>Amount (Rp)</strong>, <strong>Paid from account</strong>, dan <strong>Reference (NTPN / billing code)</strong>.</li>
        <li>Lampirkan bukti setor lalu klik <strong>Record Payment</strong>.</li>
    </x-tutorial.steps>
    <p>Uang yang keluar ke kantor pajak otomatis dicatat juga sebagai pengeluaran (kategori <em>Tax Payments</em>, penerima <em>Tax office</em>) supaya saldo akun benar. Anda tidak perlu menginputnya lagi di Expenses. Mengubah atau menghapus setoran ikut memperbarui pengeluaran tersebut.</p>
</x-tutorial.section>

<x-tutorial.section title="Memantau kewajiban pajak">
    <p>Laporan <strong>Finance → Reports → Tax Summary</strong> menampilkan per bulan berapa pajak yang timbul, sudah disetor, dan masih tersisa (<em>outstanding</em>) untuk PPN, PPh yang dipotong, dan PPh Final. Bandingkan dengan Tax Payments untuk memastikan semua kewajiban sudah disetor. Grafik <em>Tax Paid — Last 6 Months</em> di Finance Dashboard menunjukkan setoran terbaru.</p>
    <x-tutorial.note type="info">Menambah, mengubah, dan menghapus jenis pajak maupun setoran butuh permission <code>finance.manage</code>.</x-tutorial.note>
</x-tutorial.section>
