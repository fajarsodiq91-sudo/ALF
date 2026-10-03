<x-tutorial.section title="Gambaran singkat">
    <p>Satu <strong>sesi</strong> adalah satu program yang dipesan satu customer, lengkap dengan jadwal pertemuan, pembayaran, dan peserta. Sesi tanpa customer disebut <em>Public batch</em>. Sebagian besar sesi dibuat otomatis saat Anda menyetujui pendaftaran customer (lihat topik <em>Customer &amp; Pendaftaran</em>); Anda juga bisa membuatnya manual.</p>
    <p>Status sesi: <strong>Planned</strong>, <strong>Ongoing</strong>, <strong>Completed</strong>, dan <strong>Cancelled</strong>. Di daftar <strong>Training → Sessions</strong> Anda bisa memfilter berdasarkan program, status, dan pembayaran yang <em>Awaiting confirmation</em>.</p>
</x-tutorial.section>

<x-tutorial.section title="Membuat sesi secara manual">
    <x-tutorial.steps>
        <li>Klik <strong>Add Session</strong> di halaman <strong>Training → Sessions</strong>.</li>
        <li>Pilih <strong>Program</strong> dan <strong>Customer</strong> (atau biarkan <em>Public batch / no customer</em>), lalu isi <strong>Start Date</strong>, <strong>End Date</strong>, <strong>Delivery Mode</strong>, <strong>Location</strong>, <strong>Instructor</strong>, dan <strong>Status</strong>.</li>
        <li>Isi <strong>Fee (Rp)</strong> dan pilih <strong>Payment Plan</strong>. Jadwal pembayaran langsung dibuat dari dua isian ini.</li>
        <li>Opsional: <strong>Learning Materials Link</strong> dan <strong>Certificate Link</strong> (tampil di portal customer), serta <strong>Notes</strong>.</li>
        <li>Klik <strong>Create Session</strong>, lalu buka <strong>Edit</strong> sesi tersebut dan tambahkan pertemuannya di bagian <strong>Meeting schedule</strong>.</li>
    </x-tutorial.steps>
    <x-tutorial.note>Halaman <strong>Edit</strong> sesi terdiri dari beberapa bagian yang bisa dibuka-tutup: <em>Session details</em>, <em>Participants</em> (bila ada batas peserta), <em>Payments</em>, dan <em>Meeting schedule</em>. Kartu di Dashboard membuka langsung bagian yang relevan.</x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="Jadwal pertemuan">
    <ul>
        <li>Di <strong>Meeting schedule</strong>, klik <strong>Choose on calendar</strong> untuk memilih tanggal dan jam. Slot hijau berarti masih kosong. Isi <strong>Place</strong> dan <strong>Topic</strong> (opsional), lalu klik <strong>Add Meeting</strong>.</li>
        <li>Selama aturan jam operasional aktif (<em>Only allow meetings inside these hours</em> di Master Data), pertemuan harus tepat mengikuti slot yang dibuka, dan slot yang sudah dipesan customer lain atau diblokir ditolak. Bila aturan dimatikan, Anda bisa mengisi tanggal dan jam bebas, tetapi bentrok dengan pemesanan lain tetap ditolak.</li>
        <li>Tombol aksi di tabel berupa ikon; arahkan kursor ke ikon untuk melihat namanya. <strong>Mark done</strong> menandai pertemuan sudah terlaksana (<strong>Undo</strong> mengembalikannya ke <em>Upcoming</em>). Progres customer di portal mengikuti tanda ini, dan tanda ini bisa memicu invoice termin berikutnya.</li>
        <li><strong>Reschedule</strong> (ikon jadwal, hanya untuk pertemuan yang belum selesai) memindahkan pertemuan langsung tanpa menunggu permintaan customer: klik, pilih <strong>Choose new date &amp; time</strong> di kalender, lalu <strong>Save &amp; notify</strong>. Perubahan berlaku seketika, aturan jam operasional dan bentrok tetap diperiksa, permintaan reschedule customer yang masih pending untuk pertemuan itu otomatis ditutup, dan customer menerima email. Bila email gagal, muncul pesan agar Anda menghubungi customer manual.</li>
        <li><strong>Delete</strong> menghapus pertemuan.</li>
    </ul>
</x-tutorial.section>

<x-tutorial.section title="Permintaan reschedule dari customer">
    <p>Bila customer meminta pindah jadwal lewat portal, baris pertemuan itu berwarna kuning dengan tulisan <strong>Customer requested a reschedule</strong> beserta jadwal baru dan alasannya. Dashboard juga menampilkan kartu <strong>Reschedule requests awaiting review</strong>, dan setiap permintaan baru dikirimkan lewat email ke <strong>semua user aktif yang punya permission <code>training.manage</code></strong>.</p>
    <ul>
        <li><strong>Approve</strong>: pertemuan dipindah ke jadwal baru dan customer menerima email. Persetujuan gagal (dengan pesan merah) bila jadwal baru melanggar jam operasional atau ternyata sudah dipesan pihak lain; pertemuan tetap di jadwal lama.</li>
        <li><strong>Reject</strong>: jadwal lama dipertahankan dan customer menerima email penolakan.</li>
    </ul>
</x-tutorial.section>

<x-tutorial.section title="Email pemberitahuan ke customer">
    <p>Perubahan yang Anda lakukan pada program customer otomatis diberitahukan lewat email: sesi dijadwalkan, status sesi berubah, pertemuan ditambah, dipindah, dihapus, atau ditandai selesai/kembali upcoming, peserta dikeluarkan, dan sertifikat terbit. Kegagalan kirim tidak menggagalkan aksinya, hanya dicatat di log, kecuali reschedule yang menampilkan peringatan. Customer tanpa alamat email tidak dikirimi apa pun.</p>
</x-tutorial.section>

<x-tutorial.section title="Pembayaran">
    <p>Bagian <strong>Payments</strong> menampilkan tiap termin dengan status <em>Paid</em>, <em>Due now</em>, atau <em>Not yet due</em>. Termin dibuat sesuai <strong>Payment Plan</strong>:</p>
    <ul>
        <li><strong>Pay in full upfront</strong>: satu termin <em>Full payment</em> sebesar seluruh fee.</li>
        <li><strong>50% upfront, 50% at the middle meeting</strong>: <em>Down payment (50%)</em> di awal dan <em>Final payment (50%)</em> pada pertemuan tengah (pertemuan ke-4 dari 6, ke-7 dari 12; rumusnya jumlah pertemuan dibagi dua, ditambah satu). Untuk program sehari penuh (7 jam), termin kedua jatuh tempo setelah pelatihan selesai.</li>
        <li>Program dengan tepat satu pertemuan selalu dibayar lunas di awal, kecuali program sehari penuh.</li>
    </ul>
    <p><strong>Mencatat pembayaran</strong> (butuh permission <code>finance.manage</code>):</p>
    <x-tutorial.steps>
        <li>Klik ikon <strong>Record payment</strong> pada termin yang sudah diterima.</li>
        <li>Pilih <strong>Method</strong>: <em>Bank Transfer</em> atau <em>Cash</em>. Untuk transfer, pilih <strong>Received in account</strong> dan bila ada, lampirkan bukti (<strong>Proof of transfer (file)</strong> atau tautan). Pembayaran tunai dicatat ke akun bernama <em>Cash</em> (dibuat otomatis bila belum ada).</li>
        <li>Isi <strong>Date received</strong>, lalu klik <strong>Confirm &amp; add to Finance income</strong>.</li>
    </x-tutorial.steps>
    <p>Hasilnya, pembayaran otomatis muncul sebagai <strong>Income</strong> di Finance (kategori <em>Training Revenue</em>) dengan pajak default sesuai <em>Settings → System Settings</em>, dan customer menerima email terima kasih. Bila email itu gagal terkirim (misalnya customer tidak punya alamat email atau pengaturan email server bermasalah), pesan merah muncul, tetapi pembayarannya tetap tercatat di Finance. Salah catat? Klik ikon <strong>Cancel payment</strong>: income di Finance ikut terhapus dan termin kembali belum dibayar. Ikon <strong>View proof</strong> membuka bukti yang tersimpan.</p>
    <x-tutorial.note type="warning">Selama sudah ada pembayaran yang tercatat di Finance, <strong>Fee</strong> dan <strong>Payment Plan</strong> sesi tidak bisa diubah. Batalkan pembayarannya dulu bila perlu mengubahnya.</x-tutorial.note>
    <x-tutorial.note type="info">Invoice dikirim ke email customer otomatis ketika sebuah termin jatuh tempo (saat sesi disimpan, saat pertemuan ditandai selesai, atau lewat pengecekan harian pukul 08:00), masing-masing hanya sekali. Kartu <strong>Training payments awaiting confirmation</strong> di Dashboard menghitung termin yang belum dicatat.</x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="Peserta (untuk perusahaan atau grup)">
    <ul>
        <li>Isi <strong>Max participants (portal link)</strong> di <em>Session details</em>. Sistem membuat <strong>tautan dan QR code peserta</strong> yang juga muncul di portal customer. Batas tidak boleh lebih kecil dari jumlah peserta yang sudah bergabung.</li>
        <li>Setiap peserta membuka tautan itu dan mengisi nama, email, telepon (wajib), kota, alamat, dan foto, serta menyetujui syarat &amp; ketentuan program bila ada. Mereka langsung mendapat ID dan login portal sendiri. Email yang sama tidak bisa bergabung dua kali.</li>
        <li>Tautan otomatis tertutup saat kuota penuh, atau sesi berstatus <em>Completed</em> atau <em>Cancelled</em>. Daftar peserta ada di bagian <strong>Participants</strong>; klik <strong>Remove</strong> untuk mengeluarkan seseorang.</li>
        <li>Pada sesi yang memakai tautan peserta, sertifikat diterbitkan untuk <em>setiap peserta</em>, bukan untuk pemesannya.</li>
    </ul>
</x-tutorial.section>

<x-tutorial.section title="Menyelesaikan sesi dan sertifikat">
    <x-tutorial.steps>
        <li>Setelah tanggal akhir lewat, sesi diberi label <strong>Ended — mark as done</strong> dan masuk kartu <strong>Training sessions ended, mark as done</strong> di Dashboard.</li>
        <li>Klik <strong>Mark as done</strong> di daftar sesi dan konfirmasi. Status berubah menjadi <em>Completed</em>.</li>
        <li>Untuk program bertipe <strong>Learning</strong>, sertifikat terbit otomatis (satu per pemegang, tidak pernah dobel). Customer melihat dan mengunduhnya di portal. Memilih status <em>Completed</em> lewat <strong>Edit</strong> juga menerbitkan sertifikat.</li>
    </x-tutorial.steps>
    <p>Tanda tangan sertifikat memakai instruktur sesi, atau penandatangan default di <em>Settings → System Settings</em> bila tidak ada instruktur.</p>
    <x-tutorial.note>Menambah, mengubah, dan menyelesaikan sesi butuh permission <code>training.manage</code>; mencatat pembayaran butuh <code>finance.manage</code>.</x-tutorial.note>
</x-tutorial.section>
