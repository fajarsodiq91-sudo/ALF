<x-tutorial.section title="Gambaran singkat">
    <p>Modul <strong>Projects</strong> dipakai untuk proyek internal maupun proyek untuk customer. Setiap proyek punya kode, penanggung jawab (project manager), tenggat, nilai kontrak, dan daftar <strong>task</strong> yang bisa diberikan ke karyawan. Progres proyek dihitung dari jumlah task yang sudah <em>Done</em>.</p>
</x-tutorial.section>

<x-tutorial.section title="Membuat proyek">
    <x-tutorial.steps>
        <li>Buka <strong>Projects</strong> dan klik <strong>Add Project</strong>.</li>
        <li><strong>Project Code</strong> sudah terisi usulan (misalnya <code>PRJ-2026-001</code>) yang boleh Anda ganti; kodenya harus unik. Isi <strong>Project Name</strong>.</li>
        <li>Pilih <strong>Customer</strong> (kosongkan bila proyek internal) dan <strong>Project Manager</strong> (karyawan yang belum berstatus resigned).</li>
        <li>Isi <strong>Start Date</strong>, <strong>Deadline</strong>, dan <strong>Contract Value (Rp)</strong>, lalu pilih <strong>Status</strong>: <em>Planned</em>, <em>In Progress</em>, <em>On Hold</em>, <em>Completed</em>, atau <em>Cancelled</em>.</li>
        <li>Klik <strong>Create Project</strong>. Anda langsung diarahkan ke halaman proyek.</li>
    </x-tutorial.steps>
    <p>Daftar proyek bisa dicari berdasarkan nama atau kode, difilter menurut status, atau dipersempit dengan <strong>Overdue only</strong> (melewati deadline dan belum selesai/batal) dan <strong>Has overdue tasks</strong>. Kolom <em>Progress</em> menunjukkan task yang sudah selesai.</p>
</x-tutorial.section>

<x-tutorial.section title="Mengelola task">
    <p>Halaman proyek menampilkan ringkasan (status, customer, manager, nilai kontrak, tanggal, progres) dan papan task dengan tiga kolom: <strong>To Do</strong>, <strong>In Progress</strong>, dan <strong>Done</strong>.</p>
    <x-tutorial.steps>
        <li>Klik <strong>+ Add task</strong> di bawah papan.</li>
        <li>Isi <strong>Title</strong>, <strong>Notes</strong>, <strong>Progress</strong> (status), <strong>Priority</strong> (<em>Low</em>, <em>Medium</em>, <em>High</em>, <em>Urgent</em>), <strong>Start date</strong>, <strong>Due date</strong> (bawaannya seminggu dari hari ini), dan centang karyawan penanggung jawab di bagian <strong>Assign to</strong> (boleh lebih dari satu).</li>
        <li>Klik <strong>Add task &amp; notify assignees</strong>.</li>
    </x-tutorial.steps>
    <ul>
        <li>Ubah status task cepat lewat dropdown di kartu task; task berpindah kolom. Gunakan <strong>Edit</strong> untuk mengubah isi task dan <strong>Delete</strong> untuk menghapusnya.</li>
        <li>Task yang lewat <em>due date</em> dan belum <em>Done</em> ditandai merah <strong>(overdue)</strong> dan dihitung di kartu <strong>Project tasks overdue</strong> pada Dashboard.</li>
        <li>Menghapus proyek ikut menghapus semua task di dalamnya.</li>
    </ul>
</x-tutorial.section>

<x-tutorial.section title="Notifikasi email ke karyawan">
    <p>Sistem otomatis mengirim email ke karyawan yang terkait dengan task:</p>
    <ul>
        <li>ditugaskan ke task baru, atau ditambahkan ke task yang sudah ada;</li>
        <li>dilepas dari sebuah task;</li>
        <li>task mereka berubah (judul, catatan, status, prioritas, tanggal, atau daftar penanggung jawab). Email memuat nilai sebelum dan sesudah perubahan.</li>
    </ul>
    <x-tutorial.note type="warning">Email hanya terkirim ke karyawan yang alamat emailnya sudah diisi di HR → Employees. Kegagalan mengirim email tidak menggagalkan penyimpanan task.</x-tutorial.note>
    <x-tutorial.note type="info">Menambah dan mengubah proyek atau task butuh permission <code>projects.manage</code>; dengan <code>projects.view</code> Anda hanya melihat.</x-tutorial.note>
</x-tutorial.section>
