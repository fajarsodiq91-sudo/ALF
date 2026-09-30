<x-tutorial.section title="Aturan umum semua transaksi">
    <ul>
        <li>Semua transaksi butuh permission <code>finance.manage</code> untuk ditambah, diubah, atau dihapus. Nomor transaksi (<code>INC-</code>, <code>EXP-</code>, <code>TRF-</code>, <code>LN-</code>, <code>LR-</code>) dibuat otomatis.</li>
        <li>Akun dan kategori harus sudah ada (lihat topik <em>Akun &amp; Kategori</em>). Hanya akun dan kategori yang <strong>Active</strong> yang bisa dipilih.</li>
        <li><strong>Bukti transaksi</strong>: lampirkan file bukti (<strong>Proof file</strong>: PDF atau gambar, maks. 5 MB) dan/atau <strong>Proof link</strong> (misalnya Google Drive). Bukti tersimpan privat dan bisa dibuka dari daftar transaksi maupun dari buku besar (<em>Transactions</em>). Mengunggah file baru menggantikan yang lama; centang <strong>Remove</strong> untuk menghapusnya.</li>
        <li>Untuk mengubah atau menghapus transaksi, klik <strong>Edit</strong> atau <strong>Delete</strong> di baris transaksinya. Saldo akun dan laporan ikut menyesuaikan.</li>
    </ul>
</x-tutorial.section>

<x-tutorial.section title="Pemasukan (Income)">
    <x-tutorial.steps>
        <li>Buka <strong>Finance → Income</strong> dan klik <strong>Record Income</strong>.</li>
        <li>Isi <strong>Date</strong> dan <strong>Amount before tax (Rp)</strong>. Kolom <strong>Tax</strong> sudah terisi pajak default dari <em>Settings → System Settings</em> (bila ada) dan bisa diganti atau dikosongkan (<em>No tax</em>). Ringkasan pajaknya tampil langsung di bawah pilihan.</li>
        <li>Pilih <strong>Account</strong> dan <strong>Category</strong>, isi <strong>Source / Client</strong>, <strong>Description</strong>, <strong>Payment Method</strong>, dan <strong>Notes</strong>, lalu lampirkan bukti.</li>
        <li>Klik <strong>Record Income</strong>.</li>
    </x-tutorial.steps>
    <p>Cara pajak memengaruhi jumlah yang tercatat di akun:</p>
    <ul>
        <li><strong>PPN / VAT</strong>: pajak <em>ditambahkan</em> ke jumlah.</li>
        <li><strong>PPh / Withholding</strong>: pajak <em>dipotong</em> dari jumlah, sehingga yang masuk ke akun adalah jumlah bersih.</li>
        <li><strong>PPh Final</strong>: jumlah diterima <em>penuh</em>, dan pajaknya dicatat sebagai kewajiban (<em>accrued</em>) yang disetor kemudian lewat <em>Tax Payments</em>.</li>
    </ul>
    <x-tutorial.note type="info">Pembayaran customer training tidak perlu diinput di sini. Konfirmasi pembayarannya di halaman sesi training dan sistem otomatis membuat income-nya.</x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="Pengeluaran (Expenses)">
    <p>Caranya sama dengan pemasukan: <strong>Finance → Expenses</strong>, klik <strong>Record Expense</strong>, isi <strong>Date</strong>, <strong>Amount before tax (Rp)</strong>, <strong>Tax</strong>, <strong>Account</strong>, <strong>Category</strong>, <strong>Payee / Vendor</strong>, <strong>Payment Method</strong>, <strong>Description</strong>, dan <strong>Notes</strong>, lalu simpan. Perlakuan pajaknya sama seperti di pemasukan.</p>
    <p>Sebagian pengeluaran dibuat otomatis oleh sistem: gaji dari HR Payroll, setoran pajak, biaya admin bank (bulanan atau saat transfer), dan penarikan pemilik. Ubah atau batalkan dari modul asalnya.</p>
</x-tutorial.section>

<x-tutorial.section title="Penarikan pemilik (Owner Draw)">
    <p>Untuk uang yang diambil pemilik dari perusahaan. Buka <strong>Finance → Owner Draw</strong>.</p>
    <x-tutorial.steps>
        <li>Pilih <strong>Type</strong>: <em>Dividend (PPh final 10%)</em> (pembagian laba, bukan biaya operasional) atau <em>Salary (PPh 21)</em> (biaya operasional).</li>
        <li>Isi <strong>Date</strong>, <strong>Owner / CEO name</strong>, <strong>Gross amount before tax (Rp)</strong>, dan <strong>Paid from account</strong>. Pajaknya terpilih otomatis sesuai jenis; untuk gaji, isi <strong>Tax amount override</strong> dengan PPh 21 yang tepat dari perhitungan progresif Anda.</li>
        <li>Klik <strong>Record Owner Draw</strong>. Ringkasan <em>Tax withheld</em> dan <em>Owner receives</em> muncul sebelum Anda menyimpan.</li>
    </x-tutorial.steps>
    <p>Hanya jumlah bersih yang keluar dari akun. Pajak yang dipotong menjadi kewajiban yang baru lunas saat Anda mencatat setorannya di <strong>Tax Payments</strong>. Transaksinya muncul di daftar Expenses.</p>
</x-tutorial.section>

<x-tutorial.section title="Transfer antar akun (Transfers)">
    <p>Memindahkan uang antar akun perusahaan, misalnya dari bank ke kas. Di <strong>Finance → Transfers</strong>, klik <strong>Record Transfer</strong> lalu isi <strong>Date</strong>, <strong>Amount (Rp)</strong>, <strong>From Account</strong>, <strong>To Account</strong> (harus berbeda), dan <strong>Description</strong>. Bila bank mengenakan biaya, isi <strong>Bank admin fee (Rp)</strong>; biaya itu otomatis tercatat sebagai pengeluaran (<em>Bank Charges</em>) pada akun asal. Transfer tidak memengaruhi total pemasukan atau pengeluaran, hanya memindahkan saldo.</p>
</x-tutorial.section>

<x-tutorial.section title="Pinjaman (Loans)">
    <p>Mencatat uang yang berpindah antara pemilik dan perusahaan yang harus dikembalikan. Buka <strong>Finance → Loans</strong> lalu klik <strong>Record Loan</strong>.</p>
    <ul>
        <li><strong>Direction</strong>: <em>Owner borrows from company</em> (kas keluar dari akun perusahaan sekarang dan kembali lewat pembayaran) atau <em>Company borrows from owner</em> (kas masuk ke akun perusahaan sekarang dan keluar lewat pembayaran). Arah tidak bisa diubah setelah pinjaman dibuat.</li>
        <li>Isi <strong>Date</strong>, <strong>Amount (Rp)</strong>, <strong>Company Account</strong>, <strong>Owner / Party Name</strong>, <strong>Description</strong>, dan <strong>Notes</strong>.</li>
        <li>Halaman daftar menampilkan total sisa pinjaman untuk kedua arah. Klik sebuah pinjaman untuk melihat jumlah, yang sudah dikembalikan (<em>Repaid</em>), dan sisa (<em>Outstanding</em>).</li>
        <li>Di halaman pinjaman, gunakan <strong>Record Repayment</strong> (tanggal, jumlah, akun perusahaan, catatan, bukti) untuk tiap pembayaran kembali. Jumlahnya tidak boleh melebihi sisa pinjaman. Pinjaman berstatus <em>settled</em> saat sisanya nol.</li>
        <li>Pinjaman yang sudah punya pembayaran tidak bisa dihapus; hapus pembayarannya dulu.</li>
    </ul>
</x-tutorial.section>
