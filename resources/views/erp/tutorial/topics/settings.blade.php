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
            <li><strong>Certificate default signer name</strong> dan <strong>title</strong>: penandatangan sertifikat bila sesi tidak punya instruktur.</li>
            <li><strong>Default income tax</strong>: pajak yang otomatis dipilih di setiap pemasukan baru, termasuk pembayaran training customer. Pilih <em>No automatic tax</em> bila tidak ingin pajak otomatis. Tetap bisa diubah per transaksi.</li>
        </ul>
        <p>Klik <strong>Save Settings</strong> untuk menyimpan.</p>
    </x-tutorial.section>
@endcan
