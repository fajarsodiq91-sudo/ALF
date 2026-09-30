<x-tutorial.section title="Program dan kategori">
    <p><strong>Program</strong> adalah katalog layanan yang dipilih customer saat mendaftar (misalnya kelas Excel Basic atau paket konsultasi). <strong>Kategori</strong> hanya mengelompokkan program, misalnya <em>Excel Basic</em> di bawah <em>Data Analyst</em>. Customer melihat program per kategori di formulir pendaftaran; program tanpa kategori masuk ke kelompok <em>Lainnya</em>.</p>
    <p>Buat kategori dulu di <strong>Training → Categories</strong> (nama, deskripsi, <strong>Active</strong>), baru program di <strong>Training → Programs</strong>.</p>
</x-tutorial.section>

<x-tutorial.section title="Membuat program">
    <x-tutorial.steps>
        <li>Buka <strong>Training → Programs</strong> dan klik <strong>Add Program</strong>.</li>
        <li>Isi <strong>Program Name</strong>, <strong>Program Type</strong> (misalnya Learning atau Consulting), dan <strong>Category</strong>.</li>
        <li><strong>Number of meetings</strong>: customer wajib memilih tanggal untuk <em>tepat</em> sejumlah ini saat mendaftar.</li>
        <li><strong>Session length (minutes)</strong>: bila diisi, customer memilih jam mulai bebas di dalam jam operasional dan jam selesai mengikuti panjang sesi. Bila kosong, customer memesan satu slot jam operasional utuh.</li>
        <li><strong>Standard Price (Rp)</strong>, <strong>Description</strong>, dan <strong>Terms &amp; Conditions</strong>. Syarat &amp; ketentuan wajib disetujui customer saat mendaftar (misalnya kebijakan pembatalan atau kehadiran).</li>
        <li>Centang <strong>Active</strong> agar program tampil di formulir pendaftaran. Program nonaktif tidak bisa dipilih customer, tetapi sesi lama tetap aman.</li>
        <li>Klik <strong>Create Program</strong>.</li>
    </x-tutorial.steps>
</x-tutorial.section>

<x-tutorial.section title="Corporate training">
    <p>Centang <strong>Corporate training</strong> untuk program yang dijual ke perusahaan. Program ini membuka slot khusus corporate di jam operasional (slot ungu di <em>Master Data → Operating Hours</em>) yang disembunyikan dari program biasa. Program sehari penuh (7 jam, yaitu <em>Session length</em> 420 menit) diperlakukan khusus untuk pembayaran termin: pelunasan 50% kedua jatuh tempo setelah pelatihan selesai, bukan pada pertemuan tertentu.</p>
</x-tutorial.section>

<x-tutorial.section title="Harga grup (untuk customer Individual)">
    <p>Di bagian <strong>Group Pricing</strong> Anda bisa mengizinkan beberapa individu mendaftar bersama dengan harga per orang yang lebih murah.</p>
    <ul>
        <li>Isi <strong>Maximum people per group</strong>. Kosongkan bila program tidak menerima grup.</li>
        <li>Klik <strong>+ Add price tier</strong> untuk tiap titik perubahan harga. Contoh: mulai 2 orang Rp 650.000 per orang, mulai 4 orang Rp 500.000 per orang. Harga standar berlaku untuk 1 orang.</li>
        <li>Setiap anggota grup tetap mendapat ID customer sendiri. Perubahan harga hanya memengaruhi pendaftaran baru.</li>
    </ul>
</x-tutorial.section>

<x-tutorial.section title="Promo / diskon">
    <p>Di bagian <strong>Promo / Discount</strong>, pilih <strong>Discount type</strong> (<em>Percentage (%)</em> atau <em>Fixed amount (Rp)</em>), isi nilainya, dan tentukan <strong>Active until</strong>. Selama promo berjalan, customer melihat harga setelah diskon di formulir pendaftaran; promo berakhir tengah malam setelah tanggal tersebut. Bila ada harga grup, diskon diterapkan di atas harga per orang tiap tier.</p>
</x-tutorial.section>

<x-tutorial.section title="Foto ilustrasi">
    <p>Unggah hingga 10 foto (JPG, PNG, atau WebP, maks. 2 MB per foto) yang tampil kepada customer saat memilih program. Foto dikompres otomatis. Untuk menghapus foto, centang fotonya lalu simpan.</p>
</x-tutorial.section>

<x-tutorial.section title="Mengubah dan menghapus">
    <ul>
        <li>Perubahan harga, diskon, dan harga grup tidak mengubah sesi yang sudah dibuat.</li>
        <li>Program yang sudah punya sesi <strong>tidak bisa dihapus</strong>; matikan <strong>Active</strong> saja. Kategori yang masih berisi program juga tidak bisa dihapus.</li>
        <li>Hanya program bertipe <strong>Learning</strong> yang menghasilkan sertifikat ketika sesinya selesai.</li>
    </ul>
    <x-tutorial.note>Menambah, mengubah, dan menghapus butuh permission <code>training.manage</code>. Dengan <code>training.view</code> saja Anda hanya melihat daftar.</x-tutorial.note>
</x-tutorial.section>
