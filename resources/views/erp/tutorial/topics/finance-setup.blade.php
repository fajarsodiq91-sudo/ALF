<x-tutorial.section title="Mengapa perlu disiapkan dulu?">
    <p>Setiap transaksi Finance harus dicatat ke sebuah <strong>akun</strong> (tempat uang berada) dan diberi <strong>kategori</strong> (alasan uang masuk atau keluar). Siapkan keduanya sebelum mencatat pemasukan atau pengeluaran pertama Anda. Pajak juga perlu disiapkan; lihat topik <em>Pajak</em>.</p>
</x-tutorial.section>

<x-tutorial.section title="Akun (Accounts)">
    <p>Akun adalah sumber dana perusahaan: kas, rekening bank, atau dompet digital.</p>
    <x-tutorial.steps>
        <li>Buka <strong>Finance → Accounts</strong> dan klik <strong>Add Account</strong>.</li>
        <li>Isi <strong>Name</strong>, pilih <strong>Account Type</strong> (<em>Cash</em>, <em>Bank</em>, <em>E-Wallet</em>, atau <em>Other</em>), dan isi <strong>Account Number</strong> bila ada.</li>
        <li><strong>Opening Balance (Rp)</strong> adalah saldo pada saat Anda mulai memakai aplikasi. Saldo saat ini dihitung dari saldo awal ditambah semua transaksi.</li>
        <li><strong>Monthly Bank Admin Fee (Rp)</strong> (khusus akun <em>Bank</em>): bila diisi, sistem mencatat biaya admin bank sebagai pengeluaran otomatis setiap tanggal 1 (kategori <em>Bank Charges</em>), satu kali per bulan. Kosongkan bila tidak ada biaya bulanan.</li>
        <li>Centang <strong>Active</strong> lalu klik <strong>Create Account</strong>.</li>
    </x-tutorial.steps>
    <x-tutorial.note>Akun yang sudah memiliki transaksi tidak bisa dihapus. Nonaktifkan saja dengan mengubah <strong>Active</strong>; akun nonaktif tidak muncul lagi di pilihan akun pada form transaksi.</x-tutorial.note>
    <x-tutorial.note type="info">Akun bernama <strong>Cash</strong> dibuat otomatis ketika Anda mencatat pembayaran customer secara tunai, jika belum ada.</x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="Kategori (Categories)">
    <p>Kategori mengelompokkan transaksi agar laporan bermakna, misalnya <em>Training Revenue</em> untuk pemasukan atau <em>Office Rent</em> untuk pengeluaran.</p>
    <x-tutorial.steps>
        <li>Buka <strong>Finance → Categories</strong> dan klik <strong>Add Category</strong>.</li>
        <li>Isi <strong>Name</strong>, pilih <strong>Type</strong> (<em>Income</em> atau <em>Expense</em>), dan <strong>Description</strong> bila perlu.</li>
        <li>Klik <strong>Create Category</strong>. Kategori <em>Income</em> hanya muncul di form pemasukan, dan kategori <em>Expense</em> hanya di form pengeluaran.</li>
    </x-tutorial.steps>
    <p>Beberapa kategori dibuat otomatis oleh modul lain saat pertama kali dibutuhkan: <em>Training Revenue</em> (pembayaran customer), <em>Salaries &amp; Wages</em> (payroll), <em>Tax Payments</em> (setoran pajak), <em>Bank Charges</em>, <em>Owner Salary</em>, dan <em>Owner Dividends</em>. Modul-modul itu mencarinya berdasarkan nama, jadi bila Anda mengganti namanya, kategori baru dengan nama aslinya akan dibuat lagi saat dibutuhkan.</p>
    <x-tutorial.note>Kategori yang sudah dipakai transaksi tidak bisa dihapus; nonaktifkan saja.</x-tutorial.note>
    <x-tutorial.note type="info">Menambah, mengubah, dan menghapus akun serta kategori butuh permission <code>finance.manage</code>.</x-tutorial.note>
</x-tutorial.section>
