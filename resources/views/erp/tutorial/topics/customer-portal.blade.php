<x-tutorial.section title="Untuk apa portal ini?">
    <p>Customer Portal adalah situs terpisah untuk customer yang sudah disetujui. Di sana customer memantau jadwal, progres, pembayaran, materi, dan sertifikatnya sendiri, tanpa perlu menghubungi tim. Menu <strong>Customer Portal</strong> di sidebar ERP membantu Anda melihat portal persis seperti yang dilihat customer.</p>
</x-tutorial.section>

<x-tutorial.section title="Membuka pratinjau portal seorang customer">
    <x-tutorial.steps>
        <li>Buka menu <strong>Customer Portal</strong>. Tabelnya memuat customer yang sudah disetujui, jumlah program, dan status login portalnya.</li>
        <li>Klik <strong>Open as customer</strong> pada baris customer. Portal terbuka di tab baru dengan bilah kuning <strong>Preview mode</strong>.</li>
        <li>Selesai memeriksa, klik <strong>Exit preview</strong> di pojok kanan atas portal.</li>
    </x-tutorial.steps>
    <p>Pratinjau bersifat <strong>read-only</strong>: Anda tidak bisa mengganti password, mengunggah proyek, atau mengajukan reschedule atas nama customer. Kolom <strong>Portal login</strong> memberi tahu apakah customer sudah mengganti password awalnya (<em>Active</em>), belum (<em>Initial password not changed yet</em>), atau memang tidak punya login karena ditambahkan manual oleh staf (<em>No login (added by staff)</em>).</p>
    <p>Di halaman yang sama ada tautan <strong>Login page for customers</strong> yang bisa Anda salin dan kirim ke customer.</p>
</x-tutorial.section>

<x-tutorial.section title="Login customer">
    <ul>
        <li><strong>Username</strong> adalah ID customer (misalnya <code>260901</code>). <strong>Password awal</strong> sama dengan ID tersebut.</li>
        <li>Saat login pertama, customer wajib membuat password baru: minimal 8 karakter dan tidak boleh sama dengan ID-nya. Sesudahnya, tautan <strong>Password</strong> di header portal dipakai untuk menggantinya kapan saja.</li>
        <li>Lima kali salah berturut-turut mengunci login sementara (sekitar satu menit) untuk ID dan perangkat tersebut.</li>
        <li>Saat ini belum ada fitur reset password customer, baik di portal maupun di ERP. Customer yang lupa password perlu ditangani lewat pengembang aplikasi.</li>
    </ul>
    <x-tutorial.note type="info">
        Karyawan atau anggota grup yang mendaftar lewat tautan peserta juga mendapat ID dan login portal sendiri, dengan aturan password yang sama.
    </x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="Yang bisa dilakukan customer">
    <p>Halaman utama <strong>My Programs</strong> memuat satu kartu per program:</p>
    <ul>
        <li><strong>Progres</strong> dan tabel pertemuan (tanggal, jam, tempat, topik, dan status <em>Done</em> atau <em>Upcoming</em>).</li>
        <li><strong>Request reschedule</strong>: customer memilih tanggal dan jam baru di kalender (hanya slot kosong dan sesuai jam operasional), boleh menulis alasan, lalu mengirim. Hanya satu permintaan pending per pertemuan, dan permintaan bisa dibatalkan selama masih pending. Hanya pemilik sesi yang bisa mengajukannya, dan hanya untuk pertemuan yang belum terlaksana. Tim memprosesnya di halaman sesi (lihat topik <em>Sesi Training</em>).</li>
        <li><strong>Payments</strong>: total biaya dan tiap termin dengan status <em>Paid</em>, <em>Due</em>, atau <em>Upcoming</em>.</li>
        <li><strong>Learning materials</strong> dan tombol <strong>Certificate</strong> muncul bila tautannya sudah Anda isi atau sertifikatnya sudah terbit.</li>
        <li><strong>Participants</strong> (untuk sesi perusahaan atau grup): tautan dan QR code pendaftaran peserta beserta daftar yang sudah bergabung.</li>
        <li><strong>Your project</strong>: mengunggah proyek hasil belajar berupa file (ZIP, PDF, Office, atau gambar, maks. 10 MB) atau tautan (Google Drive, GitHub, dan sebagainya), lengkap dengan judul dan deskripsi.</li>
        <li><strong>Our operating hours</strong>: kalender jam operasional dan slot yang masih tersedia (bagian yang bisa dilipat).</li>
    </ul>
    <p>Menu <strong>Certificates</strong> di header portal mendaftar semua sertifikat customer. Setiap sertifikat bisa dilihat dan diunduh sebagai PDF.</p>
</x-tutorial.section>

<x-tutorial.section title="Sertifikat dan portofolio publik">
    <ul>
        <li>Sertifikat terbit otomatis untuk sesi bertipe <strong>Learning</strong> ketika sesi ditandai selesai. Nomornya berformat <code>ALF/LRN/{tahun}/{bulan romawi}/{urutan}-{kode 3 karakter}</code>, dengan tahun, bulan, dan urutan diambil dari ID customer.</li>
        <li>Tanda tangan di sertifikat adalah tanda tangan instruktur sesi tersebut (diunggah di data karyawan). Bila tidak ada instruktur, dipakai nama dan jabatan penandatangan di <em>Settings → System Settings</em>.</li>
        <li>Setiap sertifikat memuat <strong>QR code</strong> yang mengarah ke halaman verifikasi publik <code>/verify/{kode}</code>. Halaman tanpa login itu menyatakan sertifikat <em>Valid</em> beserta nama penerima, program, nomor sertifikat, nomor ID, tanggal terbit, dan tautan ke portofolio publik customer. Kode yang tidak dikenal menampilkan halaman <em>tidak valid</em> (404).</li>
        <li>Halaman portofolio publik customer juga tanpa login dan memuat sertifikatnya serta proyek yang sudah Anda tandai <strong>In portfolio</strong> (di halaman detail customer di Sales). File proyek hanya bisa diunduh publik bila sudah ditandai seperti itu.</li>
    </ul>
    <x-tutorial.note type="tip">Cabut tanda portofolio kapan saja dengan menekan tombol <strong>In portfolio</strong> lagi di halaman detail customer.</x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="ID Card di portal">
    <p>Di dashboard portal, ikon <strong>ID Card</strong> di sebelah kanan <em>Welcome</em> membuka ID Card customer sendiri di tab baru, siap dicetak. Isinya sama dengan kartu yang dicetak dari menu Customer, dan customer hanya bisa melihat kartunya sendiri.</p>
</x-tutorial.section>
