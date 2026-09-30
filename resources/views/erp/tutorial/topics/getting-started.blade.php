<x-tutorial.section title="Masuk ke ERP">
    <x-tutorial.steps>
        <li>Buka halaman login, lalu isi <strong>email</strong> dan <strong>password</strong> akun Anda.</li>
        <li>Centang <strong>Remember me</strong> bila ingin tetap masuk di perangkat pribadi.</li>
        <li>Lupa password? Klik <strong>Forgot your password?</strong> di halaman login dan ikuti tautan yang dikirim ke email Anda.</li>
    </x-tutorial.steps>
    <p>Akun staf dibuat oleh Super Admin lewat <strong>Settings → Users</strong>. Akun yang dinonaktifkan tidak bisa masuk. Jika setelah login muncul halaman <strong>403 Forbidden</strong>, akun Anda belum diberi role, jadi minta Super Admin memberikannya.</p>
</x-tutorial.section>

<x-tutorial.section title="Mengenal tampilan">
    <ul>
        <li><strong>Sidebar kiri</strong> berisi menu modul. Menu yang punya panah (Finance, Training, HR, Settings) dibuka dengan klik; membuka satu grup menutup grup lain. Di HP, buka sidebar dengan tombol ☰ di pojok kiri atas.</li>
        <li><strong>Header</strong> menampilkan judul halaman, tautan <strong>View public site</strong> (website perusahaan), dan menu nama Anda di pojok kanan atas: <strong>Profile</strong> dan <strong>Log Out</strong>.</li>
        <li>Menu <strong>Tutorial</strong> di bagian bawah sidebar membuka panduan ini. Pilih topik di sebelah kiri; topik yang tampil menyesuaikan hak akses Anda, dan tombol <strong>Buka halaman</strong> membawa Anda langsung ke menu yang dibahas.</li>
        <li>Setelah menyimpan atau menghapus data, muncul pesan <strong>hijau</strong> (berhasil) atau <strong>merah</strong> (gagal, lengkap dengan alasannya) di atas halaman.</li>
        <li>Halaman daftar umumnya punya kolom pencarian, filter, dan pindah halaman di bagian bawah. Filter ikut tersimpan di alamat halaman, jadi tautan yang sudah terfilter bisa dibagikan.</li>
    </ul>
    <x-tutorial.note type="tip">
        Tidak melihat sebuah menu? Berarti role Anda belum punya hak akses ke modul itu (lihat bagian <a href="#hak-akses">Hak akses per role</a>).
    </x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="Dashboard">
    <p>Dashboard adalah halaman pertama setelah login. Isinya dua bagian:</p>
    <ul>
        <li><strong>Needs your attention</strong>: kartu berisi jumlah pekerjaan yang menunggu tindakan Anda. Kartu hanya muncul bila jumlahnya lebih dari nol dan sesuai hak akses Anda. Klik kartu untuk langsung membuka daftar yang sudah terfilter.</li>
        <li><strong>Calendar &amp; operating hours</strong>: kalender slot jadwal. Slot terbuka masih bisa dipesan; slot terisi sudah dipesan customer atau diblokir perusahaan. Arahkan kursor atau klik waktu yang terisi untuk melihat detailnya.</li>
    </ul>
    <p>Kartu yang bisa muncul:</p>
    <ul>
        <li><strong>Customer registrations awaiting approval</strong> (Sales): pendaftaran customer yang perlu ditinjau.</li>
        <li><strong>Training payments awaiting confirmation</strong> (Training): pembayaran yang belum dicatat.</li>
        <li><strong>Training sessions ended, mark as done</strong> (Training): sesi yang tanggal akhirnya lewat tetapi belum diselesaikan.</li>
        <li><strong>Reschedule requests awaiting review</strong> (Training): permintaan ubah jadwal dari customer.</li>
        <li><strong>Leave requests awaiting review</strong> (HR): pengajuan cuti pending.</li>
        <li><strong>Payroll runs still in draft</strong> (HR Payroll): slip gaji yang belum dibayar.</li>
        <li><strong>Projects overdue</strong> dan <strong>Project tasks overdue</strong> (Projects): melewati tenggat.</li>
    </ul>
</x-tutorial.section>

<x-tutorial.section title="Profil Anda">
    <p>Klik nama Anda di pojok kanan atas, lalu <strong>Profile</strong>. Di sana Anda bisa mengubah nama dan email, serta mengganti password. Gunakan password yang panjang dan unik.</p>
</x-tutorial.section>

<x-tutorial.section title="Hak akses per role" id="hak-akses">
    <p>Hak akses diatur lewat <strong>role</strong>, dan setiap role berisi kumpulan <strong>permission</strong>. Pola umumnya: permission <code>.view</code> boleh melihat data, sedangkan <code>.manage</code> boleh menambah, mengubah, dan menghapus. Tombol yang tidak boleh Anda pakai (misalnya <strong>Add</strong>, <strong>Edit</strong>, <strong>Delete</strong>) tidak ditampilkan.</p>
    <p>Role bawaan saat pertama kali dibuat:</p>
    <ul>
        <li><strong>Super Admin</strong>: semua akses, tidak bisa diubah.</li>
        <li><strong>Finance</strong>: Finance, Assets, Sales, HR (termasuk Payroll), Training, dan Projects. Tanpa Master Data dan Settings.</li>
        <li><strong>Staff</strong>: hanya masuk ERP (Dashboard dan Tutorial).</li>
        <li><strong>Viewer</strong>: masuk ERP dan melihat Finance.</li>
    </ul>
    <p>Super Admin dapat mengubah isi role dan membuat role baru di <strong>Settings → Roles</strong>, jadi susunan di perusahaan Anda bisa berbeda dari daftar di atas.</p>
</x-tutorial.section>

<x-tutorial.section title="Kebiasaan yang berlaku di seluruh aplikasi">
    <ul>
        <li><strong>Data yang sudah dipakai tidak bisa dihapus.</strong> Misalnya akun dengan transaksi, customer dengan sesi, atau opsi master data yang terpakai. Nonaktifkan lewat kotak <strong>Active</strong>; datanya tetap ada untuk riwayat.</li>
        <li><strong>Nomor dokumen dibuat otomatis</strong> (transaksi <code>INC-…</code>, <code>EXP-…</code>, <code>TRF-…</code>, ID customer, nomor karyawan, nomor sertifikat). Anda tidak mengisinya sendiri.</li>
        <li><strong>Bukti transaksi</strong> di Finance (PDF/gambar maks. 5 MB atau tautan) disimpan di penyimpanan privat dan hanya bisa dibuka lewat ERP.</li>
        <li><strong>Email otomatis</strong> dikirim oleh sistem: kepada customer (konfirmasi pendaftaran, persetujuan atau penolakan, invoice, terima kasih atas pembayaran, hasil reschedule), kepada karyawan (notifikasi task, keputusan cuti), dan kepada staf yang berhak (pendaftaran customer baru dan permintaan reschedule). Jika email gagal terkirim, pesan merah muncul dan Anda perlu menghubungi penerimanya langsung; datanya sendiri tetap tersimpan.</li>
    </ul>
</x-tutorial.section>
