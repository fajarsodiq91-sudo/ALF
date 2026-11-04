<x-tutorial.section title="Gambaran singkat">
    <p>Satu <strong>sesi</strong> adalah satu program yang dipesan satu customer, lengkap dengan jadwal pertemuan, pembayaran, dan peserta. Sesi tanpa customer disebut <em>Public batch</em>. Sebagian besar sesi dibuat otomatis saat Anda menyetujui pendaftaran customer (lihat topik <em>Customer &amp; Pendaftaran</em>); Anda juga bisa membuatnya manual.</p>
    <p>Status sesi: <strong>Planned</strong>, <strong>Ongoing</strong>, <strong>Completed</strong>, dan <strong>Cancelled</strong>. Untuk sesi yang punya pertemuan, status mengikuti pertemuan secara otomatis: <em>Planned</em> sebelum ada pertemuan yang ditandai selesai, <em>Ongoing</em> selama sebagian sudah selesai, dan <em>Completed</em> setelah semuanya selesai. Di daftar <strong>Training → Sessions</strong> Anda bisa memfilter berdasarkan program, status, dan pembayaran yang <em>Awaiting confirmation</em>.</p>
</x-tutorial.section>

<x-tutorial.section title="Membuat sesi secara manual">
    <x-tutorial.steps>
        <li>Klik <strong>Add Session</strong> di halaman <strong>Training → Sessions</strong>.</li>
        <li>Pilih <strong>Program</strong> dan <strong>Customer</strong> (atau biarkan <em>Public batch / no customer</em>), lalu isi <strong>Start Date</strong>, <strong>End Date</strong>, <strong>Delivery Mode</strong>, <strong>Location</strong>, <strong>Instructor</strong>, dan <strong>Status</strong>.</li>
        <li>Isi <strong>Fee (Rp)</strong> dan pilih <strong>Payment Plan</strong>. Fee terisi otomatis dari harga program (setelah promo, dan mengikuti jumlah peserta untuk harga grup), tetapi tetap bisa Anda ubah. Jadwal pembayaran langsung dibuat dari dua isian ini. Sesi manual tidak menambahkan <em>registration fee</em>; biaya itu hanya muncul dari pendaftaran yang disetujui.</li>
        <li>Opsional: <strong>Learning Materials Link</strong> dan <strong>Certificate Link</strong> (tampil di portal customer), serta <strong>Notes</strong>.</li>
        <li>Klik <strong>Create Session</strong>, lalu buka <strong>Edit</strong> sesi tersebut dan tambahkan pertemuannya di bagian <strong>Meeting schedule</strong>.</li>
    </x-tutorial.steps>
    <x-tutorial.note>Halaman <strong>Edit</strong> sesi terdiri dari beberapa bagian yang bisa dibuka-tutup: <em>Session details</em>, <em>Participants</em> (bila ada batas peserta), <em>Payments</em>, dan <em>Meeting schedule</em>. Kartu di Dashboard membuka langsung bagian yang relevan.</x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="Jadwal pertemuan">
    <ul>
        <li>Di <strong>Meeting schedule</strong>, klik <strong>Choose on calendar</strong> untuk memilih tanggal dan jam. Slot hijau berarti masih kosong. Isi <strong>Place</strong> dan <strong>Topic</strong> (opsional), lalu klik <strong>Add Meeting</strong>.</li>
        <li>Selama aturan jam operasional aktif (<em>Only allow meetings inside these hours</em> di Master Data), pertemuan harus tepat mengikuti slot yang dibuka. Bila aturan dimatikan, Anda bisa mengisi tanggal dan jam bebas.</li>
        <li><strong>Bentrok jadwal:</strong> untuk customer yang mendaftar sendiri lewat QR code, slot yang sudah dipesan customer lain atau diblokir selalu ditolak. Untuk customer yang <em>Anda tambahkan manual</em> (lihat topik <em>Customer &amp; Pendaftaran</em>), pertemuan boleh ditaruh di waktu yang sudah terisi, karena customer seperti itu bisa berbagi sesi dengan customer lain. Aturan ini berlaku saat menambah dan saat memindah (<strong>Reschedule</strong>) pertemuan.</li>
        <li><strong>Mencatat pertemuan yang sudah lewat:</strong> kalender di halaman Edit sesi bisa digulir mundur hingga 36 bulan, jadi riwayat pertemuan bisa diisi belakangan. Pertemuan yang tanggalnya sudah lewat ditambahkan tanpa mengirim email ke customer.</li>
        <li>Tombol aksi di tabel berupa ikon; arahkan kursor ke ikon untuk melihat namanya. <strong>Mark done</strong> menandai pertemuan sudah terlaksana (<strong>Undo</strong> mengembalikannya ke <em>Upcoming</em>). Progres customer di portal mengikuti tanda ini, dan tanda ini bisa memicu invoice termin berikutnya. Status sesi ikut berubah otomatis, termasuk saat pertemuan ditambah atau dihapus. Rincian dialog <em>Mark done</em> ada di bagian <em>Menyelesaikan sesi dan sertifikat</em>.</li>
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
    <p>Perubahan yang Anda lakukan pada program customer otomatis diberitahukan lewat email: sesi dijadwalkan, status sesi berubah, pertemuan ditambah (kecuali pertemuan yang tanggalnya sudah lewat), dipindah, dihapus, atau ditandai selesai/kembali upcoming, peserta dikeluarkan, dan sertifikat terbit. Kegagalan kirim tidak menggagalkan aksinya, hanya dicatat di log, kecuali reschedule yang menampilkan peringatan. Customer tanpa alamat email tidak dikirimi apa pun.</p>
</x-tutorial.section>

<x-tutorial.section title="Pengingat pertemuan otomatis">
    <p>Sistem mengirim pengingat tanpa Anda melakukan apa pun, selama scheduler server berjalan (<code>schedule:run</code>, sama seperti pengiriman invoice harian):</p>
    <ul>
        <li><strong>H-1, pukul 08:00</strong>: email pengingat ke customer yang pertemuannya besok. Untuk sesi grup atau perusahaan, email juga dikirim ke setiap peserta yang sudah bergabung. Trainer sesi (kolom <em>Instructor</em>) menerima email pengingat yang sama, lengkap dengan nama customer.</li>
        <li><strong>1 jam sebelum mulai</strong>: email pengingat ke customer (dan pesertanya) untuk pertemuan yang dimulai dalam satu jam ke depan. Trainer juga mendapat pengingat ini. Pertemuan tanpa jam mulai tidak mendapat pengingat ini.</li>
        <li><strong>Ringkasan harian staf, pukul 07:00</strong>: satu email berisi pertemuan hari ini dan besok (jam, program, customer, tempat) untuk semua user aktif yang punya permission <code>training.manage</code>. Bila tidak ada pertemuan sama sekali, email tidak dikirim.</li>
        <li>Trainer hanya diingatkan bila sesinya punya instructor yang beremail dan belum berstatus <em>Resigned</em>.</li>
    </ul>
    <ul>
        <li>Hanya pertemuan yang belum ditandai selesai dan sesi yang tidak <em>Cancelled</em> yang diingatkan.</li>
        <li>Setiap pengingat dikirim sekali per pertemuan. Bila pertemuan dipindah ke tanggal atau jam lain, pengingatnya dikirim lagi untuk jadwal yang baru.</li>
        <li>Pengingat dianggap terkirim walau emailnya gagal (kegagalan dicatat di log), jadi alamat email yang salah tidak dicoba ulang tiap menit.</li>
    </ul>
    <x-tutorial.note type="info">Pengingat bisa dijalankan manual untuk pengujian lewat <code>php artisan meetings:send-reminders day-before</code>, <code>hour-before</code>, atau <code>staff-summary</code>.</x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="Pembayaran">
    <p>Bagian <strong>Payments</strong> menampilkan tiap termin dengan status <em>Paid</em>, <em>Due now</em>, <em>Not yet due</em>, atau <em>After registration fee</em>. Termin dibuat sesuai <strong>Payment Plan</strong>:</p>
    <ul>
        <li><strong>Pay in full upfront</strong>: satu termin <em>Full payment</em> sebesar seluruh fee.</li>
        <li><strong>50% upfront, 50% at the middle meeting</strong>: <em>Down payment (50%)</em> di awal dan <em>Final payment (50%)</em> pada pertemuan tengah (pertemuan ke-4 dari 6, ke-7 dari 12; rumusnya jumlah pertemuan dibagi dua, ditambah satu). Untuk program sehari penuh (7 jam), termin kedua jatuh tempo setelah pelatihan selesai.</li>
        <li>Program dengan tepat satu pertemuan selalu dibayar lunas di awal, kecuali program sehari penuh.</li>
    </ul>
    <p><strong>Registration fee.</strong> Bila program pertama customer punya biaya pendaftaran (lihat topik <em>Program &amp; Kategori Training</em> dan <em>Settings</em>), sesi pertama customer itu mendapat termin tambahan <em>Registration fee</em> yang jatuh tempo saat pendaftaran dan selalu menjadi termin serta invoice pertama. Biaya ini sekali seumur customer, tidak bisa dikembalikan, dan bukan bagian dari fee program.</p>
    <ul>
        <li>Selama registration fee belum dicatat, termin program berstatus <em>After registration fee</em>: tidak dianggap jatuh tempo, invoicenya belum dikirim, ikon <strong>Record payment</strong>-nya tidak tampil, dan pertemuan sesi itu belum bisa ditandai <em>Done</em> (muncul pesan merah agar registration fee dicatat dulu).</li>
        <li>Begitu registration fee dicatat, invoice termin program yang sudah jatuh tempo langsung dikirim.</li>
        <li>Registration fee tidak ikut dihitung saat Anda mengubah <strong>Fee</strong> atau <strong>Payment Plan</strong>: termin itu tetap ada dan tidak menghalangi perubahan, sekalipun sudah dibayar.</li>
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
        <li>Tandai tiap pertemuan dengan <strong>Mark done</strong> di halaman Edit sesi. Dialog <em>Mark meeting as done</em> meminta <strong>Date held</strong> (bawaan hari ini; pilih tanggal sebelumnya bila pertemuan sudah berlangsung tetapi baru dicatat, tidak boleh tanggal depan). Saat pertemuan pertama selesai status menjadi <em>Ongoing</em>; saat pertemuan terakhir selesai status menjadi <em>Completed</em>. Sebelum pertemuan terakhir ditandai, dialog memperingatkan bahwa sesi akan selesai dan sertifikat akan terbit.</li>
        <li><strong>Siapa yang hadir:</strong> menandai selesai sekaligus mencatat kehadiran. Pada sesi perorangan, customernya otomatis tercatat hadir. Pada sesi grup atau perusahaan, dialog menampilkan daftar <strong>Who attended?</strong> dengan semua peserta tercentang; hilangkan centang peserta yang absen. Peserta yang sudah punya catatan hadir (dari tap kartu atau tanda manual) ditampilkan <em>already present</em> dan tidak diubah. Catatannya tampil di <em>Attendance report</em> dengan label <em>on done</em>.</li>
        <li><strong>Undo</strong> hanya menghapus catatan hadir yang dibuat otomatis saat <em>Mark done</em>; hasil tap kartu dan tanda manual tetap ada.</li>
        <li>Salah memilih tanggal? Pada pertemuan yang sudah <em>Done</em>, klik ikon <strong>Edit completion date</strong>, pilih tanggal yang benar, lalu <strong>Save</strong>. Tanggal hari ini mempertahankan jam saat ini, tanggal sebelumnya dicatat sejak awal hari.</li>
        <li>Untuk program bertipe <strong>Learning</strong>, sertifikat terbit otomatis saat sesi <em>Completed</em> (satu per pemegang, tidak pernah dobel) dan customer diberi tahu lewat email. Customer melihat dan mengunduhnya di portal. Email tidak bisa ditarik, jadi periksa dulu sebelum menandai pertemuan terakhir.</li>
        <li>Bila pertemuan dibatalkan tandanya (<strong>Undo</strong>) atau pertemuan baru ditambahkan, sesi kembali menjadi <em>Ongoing</em> dan sertifikatnya disembunyikan dari customer sampai sesi <em>Completed</em> lagi; nomornya tetap sama.</li>
        <li>Setelah tanggal akhir lewat dan sesi belum <em>Completed</em>, daftar sesi memberi label <strong>Ended — meetings not all marked done</strong> dan sesi masuk kartu <strong>Training sessions ended, not completed yet</strong> di Dashboard.</li>
        <li>Kolom <strong>Status</strong> di form sesi yang punya pertemuan hanya bisa memilih <em>Cancelled</em> (atau <em>Reopen — set from meetings</em> untuk sesi yang dibatalkan); tombol <strong>Mark as done</strong> untuk sesi seperti itu tidak tersedia. Sesi tanpa pertemuan (misalnya konsultasi atau batch publik) tetap diatur manual, dengan tombol <strong>Mark as done</strong> setelah tanggal akhir lewat.</li>
    </x-tutorial.steps>
    <p><strong>Mencetak sertifikat sebelum sesi selesai:</strong> klik ikon sertifikat di daftar sesi (atau <strong>Download certificates (PDF)</strong> di halaman Edit) untuk mengunduh PDF satu halaman per pemegang sertifikat. Nomor dan QR code di PDF sudah final, jadi lembar yang dicetak sama dengan yang nanti terbit. Selama sesi belum <em>Completed</em>, sertifikat itu tidak tampil di portal customer dan halaman verifikasi QR menampilkan "Certificate not issued yet". Begitu sesi ditandai selesai, sertifikat otomatis aktif dan customer diberi tahu. Mengunduh ulang tidak membuat nomor baru. Untuk sesi korporat, tambahkan peserta lebih dulu.</p>
    <p><strong>Nama file PDF:</strong> bila hanya ada satu sertifikat, file diberi nama <code>Alfajar Certificate - {nama customer} [{ID customer}].pdf</code>, dan nama file yang sama dipakai saat customer mengunduhnya di portal. Bila ada beberapa sertifikat sekaligus (sesi dengan banyak peserta), nama filenya <code>certificates-{program}-{tanggal mulai}.pdf</code>.</p>
    <p>Tanda tangan sertifikat memakai instruktur sesi, atau penandatangan default di <em>Settings → System Settings</em> bila tidak ada instruktur.</p>
    <x-tutorial.note>Menambah, mengubah, dan menyelesaikan sesi butuh permission <code>training.manage</code>; mencatat pembayaran butuh <code>finance.manage</code>.</x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="Presensi peserta lewat tap kartu">
    <p>Customer (termasuk peserta yang bergabung lewat tautan perusahaan) dapat hadir dengan menempelkan kartu RFID atau memindai QR Code ID Card di reader. Sistem mencatat kehadiran pada pertemuan hari itu; jumlahnya tampil di kolom <strong>Attended</strong> pada <em>Meeting schedule</em> (arahkan kursor ke angka untuk melihat nama dan jam). Kehadiran juga bisa tercatat lewat dialog <em>Mark done</em> (lihat bagian sebelumnya) bila reader tidak dipakai.</p>
    <ul>
        <li>Presensi dibuka <strong>60 menit sebelum</strong> pertemuan dimulai sampai jam selesai (tanpa jam selesai: 4 jam setelah mulai).</li>
        <li>Hanya sesi yang belum <em>Completed</em> atau <em>Cancelled</em>, dan pertemuan yang belum ditandai selesai, yang menerima presensi.</li>
        <li>Tap kedua pada pertemuan yang sama tidak membuat catatan ganda.</li>
    </ul>
</x-tutorial.section>

<x-tutorial.section title="Laporan kehadiran sesi">
    <p>Klik <strong>Attendance report</strong> di bagian atas halaman edit sesi. Tabelnya menampilkan setiap peserta (atau customer itu sendiri untuk sesi perorangan) dan tiap pertemuan, dengan centang dan jam hadir, jumlah hadir, serta persentase kehadiran.</p>
    <ul>
        <li>Persentase dihitung dari pertemuan yang <strong>sudah berlangsung</strong> (tanggalnya hari ini atau lewat, atau sudah ditandai selesai); pertemuan mendatang tidak ikut dihitung.</li>
        <li>Warna persentase: hijau 80% ke atas, kuning 50–79%, merah di bawah 50%.</li>
        <li>Peserta yang lupa kartu bisa ditandai hadir manual dengan klik sel di tabel (butuh permission <code>training.manage</code>); klik lagi pada sel bercentang untuk membatalkan. Tanda manual diberi label <em>manual</em>, sedangkan yang tercatat otomatis saat pertemuan ditandai selesai diberi label <em>on done</em>.</li>
    </ul>
</x-tutorial.section>
