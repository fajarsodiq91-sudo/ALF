<x-tutorial.section title="Cara kerja payroll">
    <p>Payroll dibuat per karyawan per bulan. Slip berstatus <strong>Draft</strong> sampai dibayar; saat dibayar, sistem otomatis mencatat gaji sebagai pengeluaran di Finance. Menu ini butuh permission <code>hr.payroll</code>, dan untuk membayar juga <code>finance.manage</code>.</p>
</x-tutorial.section>

<x-tutorial.section title="Membuat slip gaji">
    <x-tutorial.steps>
        <li>Buka <strong>HR → Payroll</strong>. Secara bawaan daftar menampilkan bulan berjalan; ubah <strong>periode</strong> dan <strong>status</strong> lalu klik <strong>Filter</strong>. Total gaji bersih yang tampil mengikuti filter.</li>
        <li>Klik <strong>New Payroll</strong>.</li>
        <li>Pilih <strong>Employee</strong> dan <strong>Period</strong> (bulan), lalu isi <strong>Basic Salary (Rp)</strong>, <strong>Allowances (Rp)</strong>, <strong>Deductions (Rp)</strong>, dan <strong>Notes</strong>. Gaji bersih = gaji pokok + tunjangan − potongan, dan harus lebih dari nol.</li>
        <li>Simpan. Satu karyawan hanya boleh punya satu slip per periode.</li>
    </x-tutorial.steps>
    <p>Klik <strong>Slip</strong> pada baris untuk melihat slip gaji karyawan. Selama masih <em>Draft</em>, slip bisa diubah (<strong>Edit</strong>) atau dihapus (<strong>Delete</strong>). Kartu <strong>Payroll runs still in draft</strong> di Dashboard menghitung slip yang belum dibayar.</p>
</x-tutorial.section>

<x-tutorial.section title="Membayar gaji">
    <x-tutorial.steps>
        <li>Klik <strong>Pay</strong> pada slip yang akan dibayar.</li>
        <li>Pilih <strong>Pay from account</strong>, <strong>Paid date</strong>, dan <strong>Method</strong> (<em>Bank Transfer</em> atau <em>Cash</em>).</li>
        <li>Klik <strong>Confirm Payment</strong>. Slip berubah menjadi <em>Paid</em> dan muncul sebagai pengeluaran di Finance (kategori <em>Salaries &amp; Wages</em>, penerima nama karyawan).</li>
    </x-tutorial.steps>
    <x-tutorial.note type="warning">Slip yang sudah dibayar terkunci: tidak bisa diubah atau dihapus. Salah bayar? Klik <strong>Cancel Payment</strong> (butuh <code>finance.manage</code>). Slip kembali menjadi <em>Draft</em> dan pengeluaran di Finance ikut terhapus, lalu Anda bisa memperbaikinya.</x-tutorial.note>
</x-tutorial.section>
