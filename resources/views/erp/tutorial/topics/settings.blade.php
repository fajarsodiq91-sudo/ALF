@can('settings.manage-users')
    <x-tutorial.section title="Users: akun staf" id="users">
        <p>Menu <a href="{{ route('settings.users') }}">Settings → Users</a> mendaftar semua akun yang bisa masuk ke ERP beserta rolenya.</p>
        <x-tutorial.steps>
            <li>Klik <strong>Add User</strong>.</li>
            <li>Isi <strong>Name</strong>, <strong>Email</strong>, dan <strong>Password</strong> awal, lalu pilih <strong>Role</strong>. Setiap user memiliki tepat satu role.</li>
            <li>Biarkan <strong>Active (can sign in)</strong> tercentang, lalu simpan. Akun langsung siap dipakai tanpa verifikasi email; bagikan email dan password-nya kepada yang bersangkutan.</li>
        </x-tutorial.steps>
        <ul>
            <li>Untuk mengubah nama, email, atau role, klik <strong>Edit</strong>. Isi <strong>New password</strong> hanya bila ingin menggantinya, atau kosongkan untuk mempertahankan yang lama.</li>
            <li>User tidak bisa dihapus, tetapi bisa dinonaktifkan dengan mencabut centang <strong>Active</strong>. User nonaktif langsung tidak bisa masuk, termasuk yang sedang login.</li>
            <li>Anda tidak bisa menonaktifkan akun Anda sendiri, dan minimal satu Super Admin aktif harus selalu ada.</li>
        </ul>
    </x-tutorial.section>
@endcan

@can('settings.manage-roles')
    <x-tutorial.section title="Roles: hak akses" id="roles">
        <p>Menu <a href="{{ route('settings.roles') }}">Settings → Roles</a> menentukan apa yang boleh dilakukan tiap role. Kolom tabelnya menunjukkan jumlah user dan jumlah permission tiap role.</p>
        <x-tutorial.steps>
            <li>Klik <strong>Add Role</strong> untuk membuat role baru, isi <strong>Role name</strong>.</li>
            <li>Centang permission yang diperlukan. Permission dikelompokkan per modul: <code>.view</code> untuk melihat data, <code>.manage</code> untuk mengubahnya, sedangkan <code>access-erp</code> wajib agar bisa masuk ke ERP sama sekali. Ada juga <code>finance.reports</code> dan <code>hr.payroll</code> untuk bagian khusus.</li>
            <li>Simpan, lalu tetapkan role itu ke user di <em>Settings → Users</em>.</li>
        </x-tutorial.steps>
        <ul>
            <li><strong>Super Admin</strong> selalu memiliki semua permission dan tidak bisa diedit.</li>
            <li><strong>Finance</strong>, <strong>Staff</strong>, dan <strong>Viewer</strong> adalah role bawaan: isi permission-nya bisa diubah, tetapi tidak bisa diganti nama atau dihapus. Perubahan Anda tidak tertimpa saat aplikasi diperbarui.</li>
            <li>Role buatan sendiri baru bisa dihapus bila tidak dipakai user mana pun.</li>
        </ul>
        <x-tutorial.note type="tip">Memberi akses hanya <code>.view</code> berarti user melihat data dan tombol aksinya disembunyikan. Beri <code>.manage</code> hanya kepada yang bertanggung jawab mengubah data. Permission baru dari fitur baru dapat muncul di sini setelah aplikasi diperbarui.</x-tutorial.note>
    </x-tutorial.section>
@endcan

@can('settings.manage-system')
    <x-tutorial.section title="System Settings: profil perusahaan" id="system">
        <p>Menu <a href="{{ route('settings.system') }}">Settings → System Settings</a> menyimpan profil perusahaan dan beberapa pengaturan default.</p>
        <ul>
            <li><strong>Company name</strong>, <strong>Address</strong>, <strong>Phone</strong>, <strong>Email</strong>, dan <strong>NPWP (tax ID)</strong>. Nama perusahaan tampil di judul halaman ERP.</li>
            <li><strong>Company bank account</strong>: nomor rekening perusahaan (boleh beberapa baris). Teks ini dicantumkan di email invoice yang dikirim ke customer sebagai tujuan pembayaran.</li>
            <li><strong>Certificate default signer name</strong> dan <strong>title</strong>: penandatangan sertifikat bila sesi tidak punya instruktur.</li>
            <li><strong>Default income tax</strong>: pajak yang otomatis dipilih di setiap pemasukan baru, termasuk pembayaran training customer. Pilih <em>No automatic tax</em> bila tidak ingin pajak otomatis. Tetap bisa diubah per transaksi.</li>
        </ul>
        <p>Klik <strong>Save Settings</strong> untuk menyimpan.</p>
    </x-tutorial.section>
@endcan

@can('settings.manage-system')
    <x-tutorial.section title="Certificate Templates: desain sertifikat" id="certificate-templates">
        <p>Menu <a href="{{ route('settings.certificate-templates.index') }}">Settings → Certificate Templates</a> mengatur latar sertifikat dan posisi tiap isiannya. Tanpa template, sertifikat memakai desain bawaan.</p>
        <x-tutorial.steps>
            <li>Klik tambah, isi <strong>Name</strong>, lalu unggah latar berformat <strong>PNG A4 landscape</strong> (297 × 210 mm; disarankan 300 DPI = 3508 × 2480 px, minimal lebar 1600 px).</li>
            <li>Atur posisi tiap isian: nomor sertifikat, nama penerima, kalimat deskripsi, tanggal terbit, QR code, nomor ID, gambar tanda tangan, nama dan jabatan penandatangan. <strong>X</strong> adalah titik tengah horizontal, <strong>Y</strong> tepi atas, keduanya diukur dari pojok kiri atas halaman; <strong>W</strong> adalah lebar kotak teks (atau ukuran gambar untuk QR dan tanda tangan). Kosongkan untuk memakai nilai bawaan.</li>
            <li>Simpan, lalu klik <strong>Preview</strong> untuk melihat sertifikat contoh dan koreksi posisinya bila perlu.</li>
        </x-tutorial.steps>
        <ul>
            <li>Centang <strong>default</strong> untuk menjadikannya template utama; hanya satu yang bisa jadi default. Sertifikat yang programnya tidak punya template khusus memakai yang default.</li>
            <li>Template khusus dipasang per program lewat isian <em>Certificate template</em> di data program (lihat topik <em>Program &amp; Kategori Training</em>).</li>
            <li>Mengganti template tidak mengubah isi sertifikat lama; unggah gambar baru hanya bila ingin mengganti latarnya.</li>
        </ul>
    </x-tutorial.section>
@endcan
