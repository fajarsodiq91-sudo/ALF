<x-tutorial.section title="Cuti (Leave)">
    <p>Buka <strong>HR → Leave</strong>. Bagian atas menampilkan <strong>Annual leave balance</strong> tahun berjalan: jatah (<em>Quota</em>), yang sudah terpakai (<em>Used</em>, hanya cuti tahunan yang sudah disetujui), dan <em>Remaining</em> untuk tiap karyawan. Di bawahnya ada daftar pengajuan yang bisa difilter menurut status (<em>Pending</em>, <em>Approved</em>, <em>Rejected</em>).</p>
    <x-tutorial.steps>
        <li>Klik <strong>New Leave Request</strong>.</li>
        <li>Pilih <strong>Employee</strong> dan <strong>Leave Type</strong> (pilihannya diatur di Master Data; <em>Annual Leave</em> mengurangi jatah tahunan), lalu isi <strong>Start Date</strong>, <strong>End Date</strong>, dan <strong>Reason</strong>.</li>
        <li>Simpan. Jumlah hari dihitung otomatis dari hari kerja Senin–Jumat, dan pengajuan berstatus <em>Pending</em>.</li>
        <li>Pada daftar, klik <strong>Approve</strong> atau <strong>Reject</strong>. Persetujuan cuti tahunan ditolak sistem bila jumlah harinya melebihi sisa jatah karyawan tahun itu.</li>
        <li>Setiap keputusan otomatis diberitahukan ke karyawan lewat email. Bila karyawan belum punya alamat email di data HR, atau email gagal terkirim, pesan merah muncul; keputusannya tetap tersimpan, jadi beri tahu karyawan secara langsung.</li>
    </x-tutorial.steps>
    <p>Kartu <strong>Leave requests awaiting review</strong> di Dashboard menghitung pengajuan yang masih <em>Pending</em>. Pengajuan bisa dihapus dengan <strong>Delete</strong>.</p>
</x-tutorial.section>

<x-tutorial.section title="Absensi (Attendance)">
    <p><strong>HR → Attendance</strong> mencatat kehadiran harian.</p>
    <ul>
        <li>Pilih bulan dan (opsional) karyawan di bagian filter. Rekap bulanan per karyawan (jumlah tiap status) dihitung untuk seluruh bulan, terlepas dari halaman daftar yang sedang Anda lihat.</li>
        <li>Klik <strong>Add Attendance</strong>, pilih <strong>Employee</strong> dan <strong>Date</strong>, lalu <strong>Status</strong>: <em>Present</em>, <em>Sick</em>, <em>Permit</em>, <em>Leave</em>, atau <em>Absent</em>. <strong>Check In</strong>, <strong>Check Out</strong> (jam keluar tidak boleh lebih awal dari jam masuk), dan <strong>Notes</strong> bersifat opsional.</li>
        <li>Setiap karyawan hanya boleh punya satu catatan per tanggal. Untuk koreksi, gunakan <strong>Edit</strong> pada catatan yang sudah ada.</li>
    </ul>
    <x-tutorial.note type="info">Mengajukan, menyetujui, atau menolak cuti dan mengubah absensi butuh permission <code>hr.manage</code>; dengan <code>hr.view</code> Anda hanya melihat.</x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="Absensi lewat tap kartu">
    <p>Karyawan yang kartu RFID-nya sudah direkam dapat absen dengan menempelkan kartu ke reader, atau memindai QR Code di belakang ID Card. Hasilnya muncul di daftar Attendance seperti input manual.</p>
    <ul>
        <li>Tap pertama hari itu mencatat <strong>masuk</strong> (status <em>Present</em>); tap berikutnya memperbarui jam <strong>pulang</strong> ke tap terakhir.</li>
        <li>Tap yang berselang kurang dari 2 menit dianggap tap yang sama.</li>
        <li>Karyawan yang tidak berstatus <em>Active</em>, atau yang hari itu sudah tercatat Sick, Permit, Leave, atau Absent, ditolak dan catatannya tidak ditimpa.</li>
        <li>Reader didaftarkan di Settings → Tap Devices.</li>
    </ul>
</x-tutorial.section>
