<x-tutorial.section title="Data karyawan">
    <p>Menu <strong>HR → Employees</strong> menyimpan data seluruh karyawan. Data ini dipakai modul lain: karyawan menjadi <em>instruktur</em> sesi training, <em>project manager</em> dan penanggung jawab task di Projects, serta penerima gaji di Payroll.</p>
</x-tutorial.section>

<x-tutorial.section title="Menambah karyawan">
    <x-tutorial.steps>
        <li>Klik <strong>Add Employee</strong>.</li>
        <li><strong>Employee Number</strong> dibuat otomatis saat disimpan (format <code>YYMM</code> + nomor urut, misalnya <code>260901</code>), unik, dan tidak bisa diubah.</li>
        <li>Isi <strong>Full Name</strong>, <strong>Position</strong>, <strong>Department</strong>, dan <strong>Employment Type</strong> (pilihannya diatur di Master Data).</li>
        <li>Pilih <strong>Status</strong>: <em>Active</em>, <em>On Leave</em>, atau <em>Resigned</em>. Isi juga <strong>Join Date</strong>, <strong>Email</strong>, <strong>Phone</strong>, alamat, dan catatan.</li>
        <li><strong>Annual Leave Quota (days)</strong> menentukan jatah cuti tahunan (bawaannya 12 hari).</li>
        <li>Opsional: unggah <strong>Profile Photo</strong> (tampil di kartu task Projects) dan <strong>Signature (for certificates)</strong>.</li>
        <li>Klik <strong>Create Employee</strong>.</li>
    </x-tutorial.steps>
    <x-tutorial.note type="tip">
        <strong>Email karyawan penting</strong>: notifikasi task project dan keputusan atas pengajuan cuti dikirim ke alamat ini. <strong>Tanda tangan</strong> yang diunggah (PNG transparan paling bagus) muncul di sertifikat sesi yang diajar karyawan itu; tanpa tanda tangan, sertifikat memakai nama instruktur dalam huruf sambung.
    </x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="Mencari, mengubah, dan menghapus">
    <ul>
        <li>Daftar bisa dicari berdasarkan nama, nomor karyawan, jabatan, atau departemen, dan difilter menurut status serta tipe kepegawaian.</li>
        <li><strong>Edit</strong> mengubah data. Karyawan berstatus <em>Resigned</em> tidak muncul lagi di pilihan instruktur, project manager, penanggung jawab task, dan cuti baru.</li>
        <li><strong>Delete</strong> hanya bisa untuk karyawan yang belum punya data payroll. Bila sudah ada, ubah statusnya menjadi <em>Resigned</em>.</li>
    </ul>
    <x-tutorial.note type="info">Menambah, mengubah, dan menghapus karyawan butuh permission <code>hr.manage</code>; dengan <code>hr.view</code> Anda hanya melihat daftar.</x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="ID Card karyawan">
    <p>Klik ikon <strong>ID Card</strong> di baris karyawan untuk membuka kartu dalam tab baru, lalu <strong>Cetak ID Card</strong>. Ukuran kartu 54 × 85,6 mm (CR80). Halaman depan memuat logo Alfajar, tulisan <em>[jabatan] PT Alfajar Logic Futura</em>, foto, nomor karyawan, dan nama; halaman belakang memuat QR Code.</p>
    <ul>
        <li>Tombol <strong>Cetak semua ID Card</strong> di samping tombol Filter mencetak kartu semua karyawan yang tampil menurut filter yang sedang aktif (maksimal 200 kartu).</li>
        <li>Foto kartu diambil dari <strong>Profile Photo</strong>; tanpa foto, kartu menampilkan inisial nama.</li>
        <li>Memindai QR Code membuka halaman verifikasi publik yang menampilkan foto, nama, jabatan, dan status. Karyawan berstatus <em>Resigned</em> ditandai <strong>ID Card tidak berlaku</strong>.</li>
    </ul>
</x-tutorial.section>

<x-tutorial.section title="Merekam kartu RFID">
    <p>Di formulir karyawan (dan customer) ada kolom <strong>RFID Card UID</strong>. Klik kolom itu, lalu tempelkan kartu ke reader USB; reader mengetikkan nomor kartu otomatis (tombol Enter dari reader tidak menyimpan formulir). Simpan formulirnya untuk merekam kartu.</p>
    <ul>
        <li>Nomor kartu dirapikan otomatis (huruf besar, tanpa spasi atau titik dua), jadi format dari berbagai reader tetap dianggap sama.</li>
        <li>Satu kartu hanya boleh dimiliki satu orang, baik karyawan maupun customer. Mendaftarkan kartu yang sudah dipakai ditolak dengan menyebut pemiliknya.</li>
        <li>Kartu yang hilang atau diganti: kosongkan atau ganti nomornya lalu simpan.</li>
    </ul>
</x-tutorial.section>
