<x-tutorial.section title="Apa itu jasa custom">
    <p>Selain sesi Learning dan Consulting, perusahaan menerima <strong>pesanan pengerjaan</strong>, misalnya membuatkan Excel dashboard. Bedanya dengan program: harganya tidak tetap per katalog, melainkan ditentukan lewat <strong>penawaran (quote)</strong> setelah Anda membaca kebutuhan customer, dan pembayarannya mengikuti hasil kerja (DP lalu pelunasan), bukan jumlah pertemuan.</p>
    <p>Alurnya: <strong>Brief</strong> → <strong>Quote sent</strong> → <strong>Waiting for down payment</strong> → <strong>In progress</strong> → <strong>Delivered for review</strong> → <strong>Completed</strong>. Customer yang menolak penawaran berstatus <em>Quote declined</em>; pesanan yang dibatalkan berstatus <em>Cancelled</em>.</p>
</x-tutorial.section>

<x-tutorial.section title="Menyiapkan paket">
    <p>Buka <strong>Services → Packages</strong> dan klik <strong>Add Package</strong>. Paket hanyalah etalase: customer melihat harga <em>mulai dari</em>, isi paket, dan add-on, sedangkan harga akhir selalu dari penawaran.</p>
    <ul>
        <li><strong>Starting price</strong>, <strong>Revisions included</strong>, dan <strong>Estimated working days</strong> menjadi nilai awal di formulir penawaran.</li>
        <li><strong>What is included</strong>: satu poin per baris, tampil sebagai daftar di formulir customer.</li>
        <li><strong>Turnaround options</strong>: satu per baris dengan format <em>hari kerja | selisih harga</em>, misalnya <em>3 | 750000</em> (kilat, lebih mahal), <em>7 | 0</em> (standar), dan <em>14 | -150000</em> (lebih lama, lebih murah). Customer memilih salah satu di brief; harganya makin tinggi untuk pengerjaan yang makin cepat. Selisihnya masuk sebagai baris di penawaran dan jumlah harinya mengisi <em>Estimated working days</em>. Kosongkan bila tidak ada pilihan.</li>
        <li><strong>Add-ons</strong>: satu per baris dengan format <em>nama | harga</em>, misalnya <em>Extra dashboard page | 300000</em>. Customer mencentangnya di brief, lalu setiap add-on menjadi baris di penawaran.</li>
        <li>Paket yang sudah punya pesanan tidak bisa dihapus; tandai <strong>Inactive</strong> saja. Customer tetap bisa mengirim brief tanpa memilih paket (<em>Custom / not sure yet</em>).</li>
    </ul>
</x-tutorial.section>

<x-tutorial.section title="Brief masuk">
    <p>Ada dua jalur:</p>
    <ul>
        <li><strong>Customer lama</strong> membuka <strong>Customer Portal → Orders → Order custom work</strong>. Anggota perusahaan atau grup yang bergabung lewat tautan peserta tidak bisa memesan; pesanan dibuat oleh customer yang mendaftar.</li>
        <li><strong>Customer baru</strong>: di <strong>Sales → Add Customer → Customer fills in (QR code)</strong> pilih <strong>Signing up for → Custom work</strong>. Customer mengisi data diri sekaligus brief. Pendaftarannya muncul di daftar <em>Pending approval</em>; klik <strong>Review</strong> lalu <strong>Approve &amp; Send Email</strong> (tanpa program) untuk memberi ID dan login portal.</li>
    </ul>
    <p>Pesanan baru dari portal mengirim email ke semua user aktif yang punya permission <code>sales.manage</code>. Brief berisi tujuan, KPI, sumber dan volume data, versi Excel, deadline, kebutuhan refresh, add-on yang dicentang, dan file contoh data. Mintakan data yang sudah dianonimkan bila ada informasi rahasia.</p>
</x-tutorial.section>

<x-tutorial.section title="Menyusun dan mengirim penawaran">
    <x-tutorial.steps>
        <li>Buka <strong>Services → Orders</strong>, lalu klik <strong>Open</strong> pada pesanan berstatus <em>Brief received</em>. Filter dan pencarian tersedia di atas daftar.</li>
        <li>Baca brief dan unduh file dari customer. Perlu klarifikasi? Hubungi customer lewat email atau WhatsApp di bagian atas halaman.</li>
        <li>Di kartu <strong>Prepare the quote</strong>, baris awal sudah terisi dari harga paket dan add-on. Sesuaikan baris, tambah baris dengan <strong>+ Add line</strong>, dan lihat total di bawahnya.</li>
        <li>Isi <strong>Discount</strong> bila ada: pilih <em>Rp</em> atau <em>%</em> lalu isi nilainya (kosongkan nilainya bila tidak ada diskon), dan bila perlu beri <strong>Discount label</strong> seperti <em>Early-bird</em>. Diskon dihitung dari subtotal dan tampil di penawaran customer; DP dan pelunasan dihitung dari total setelah diskon. Total setelah diskon harus lebih dari nol. Baris penawaran boleh bernilai negatif untuk potongan lain.</li>
        <li>Isi <strong>Down payment %</strong> (10–100; bawaan 50%, isi 100 bila bayar lunas di awal), <strong>Estimated working days</strong>, <strong>Revisions included</strong>, dan <strong>Quote valid until</strong>. Tulis catatan lingkup dan asumsi di <strong>Notes to the customer</strong>.</li>
        <li>Klik <strong>Send quote</strong>. Customer menerima email dan melihat penawaran di portalnya. Selama belum diterima, penawaran masih bisa Anda ubah dengan <strong>Update &amp; resend quote</strong>.</li>
    </x-tutorial.steps>
</x-tutorial.section>

<x-tutorial.section title="DP, pengerjaan, dan serah terima">
    <x-tutorial.steps>
        <li>Customer menerima penawaran di portal. Sistem membuat dua invoice (DP dan pelunasan) dan langsung mengirim <strong>invoice DP</strong> lewat email, lengkap dengan rekening dari <em>Settings → System Settings</em>. Anda menerima email pemberitahuan.</li>
        <li>Setelah uang masuk, klik <strong>Record payment</strong> pada baris DP (butuh permission <code>finance.manage</code>). Pembayaran tercatat sebagai pemasukan di Finance, kategori <em>Service Revenue</em>, dan customer menerima email terima kasih. Status otomatis menjadi <em>In progress</em>.</li>
        <li>Kerjakan pesanan. Di kartu <strong>Work and files</strong>, unggah file dengan tipe <strong>Draft</strong> (selalu bisa diunduh customer, untuk dicek) atau <strong>Final</strong> (hanya terbuka bagi customer setelah semua invoice lunas).</li>
        <li>Klik <strong>Deliver for review</strong>. Status menjadi <em>Delivered for review</em>, customer diberi tahu, dan <strong>invoice pelunasan</strong> terkirim.</li>
        <li>Bila customer meminta perubahan, status kembali ke <em>In progress</em> dan permintaan tampil di kartu <strong>Work and files</strong> (Anda juga dapat email). Kerjakan, unggah versi baru, lalu klik <strong>Deliver the new version</strong>; permintaan yang terbuka otomatis ditandai selesai (atau pakai <strong>Mark done</strong>).</li>
        <li>Saat pelunasan dicatat, status menjadi <em>Completed</em> dan file <em>Final</em> terbuka bagi customer.</li>
    </x-tutorial.steps>
    <ul>
        <li><strong>Revisi di luar kuota</strong> ditandai merah. Tambahkan biayanya lewat <strong>Extra charge</strong> (deskripsi dan nominal); sistem langsung menagihnya lewat email dan pesanan baru <em>Completed</em> setelah biaya itu juga lunas.</li>
        <li>Salah mencatat pembayaran? <strong>Cancel payment</strong> menghapus pemasukannya di Finance dan menyesuaikan status pesanan.</li>
        <li><strong>Cancel this order</strong> mengakhiri pesanan: invoice yang belum dibayar dihapus, sedangkan pembayaran yang sudah tercatat tetap ada di Finance (kembalikan uangnya secara manual bila perlu).</li>
    </ul>
</x-tutorial.section>

<x-tutorial.section title="Yang dilihat customer di portal">
    <p>Menu <strong>Orders</strong> di header portal memuat daftar pesanan. Di tiap pesanan customer melihat langkah progres, penawaran (dengan tombol <strong>Accept quote</strong> dan <strong>Decline</strong>), invoice dan statusnya, file draf dan final, riwayat revisi dengan form <strong>Ask for changes</strong>, serta briefnya sendiri (boleh menambah file selama pesanan belum selesai). Selama status <em>Brief received</em> atau <em>Quote sent</em>, customer bisa menarik permintaannya sendiri. Setelah pengerjaan disepakati, pembatalan lewat Anda.</p>
    <p>Pratinjau portal dari menu <em>Customer Portal</em> tetap read-only, jadi Anda bisa melihat tampilan ini tanpa mengubah apa pun.</p>
</x-tutorial.section>
