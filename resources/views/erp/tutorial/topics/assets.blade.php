<x-tutorial.section title="Daftar aset perusahaan">
    <p>Menu <strong>Assets</strong> adalah register aset: laptop, kendaraan, perabot, dan lain-lain. Di bagian atas daftar tampil <strong>total nilai aset</strong> (jumlah <em>Purchase Cost</em> seluruh aset, tidak termasuk yang berstatus <em>Disposed</em>).</p>
</x-tutorial.section>

<x-tutorial.section title="Mencatat aset">
    <x-tutorial.steps>
        <li>Klik <strong>Add Asset</strong>.</li>
        <li>Isi <strong>Asset Code</strong> (harus unik, misalnya <code>LPT-001</code>) dan <strong>Name</strong>.</li>
        <li>Pilih <strong>Category</strong> (pilihannya diatur di Master Data → Asset Category) dan <strong>Status</strong>: <em>Active</em>, <em>In Repair</em>, atau <em>Disposed</em>.</li>
        <li>Isi <strong>Purchase Date</strong>, <strong>Purchase Cost (Rp)</strong>, <strong>Location</strong>, <strong>Assigned To</strong> (pemakai aset), dan <strong>Notes</strong>.</li>
        <li>Simpan formulirnya.</li>
    </x-tutorial.steps>
    <p>Daftar bisa dicari berdasarkan kode atau nama, dan difilter menurut status dan kategori. Gunakan <strong>Edit</strong> untuk memperbarui data, misalnya mengganti status menjadi <em>In Repair</em> saat aset diservis atau <em>Disposed</em> saat dilepas. Aset yang dilepas sebaiknya tidak dihapus supaya riwayatnya tetap ada.</p>
    <x-tutorial.note type="info">Menambah, mengubah, dan menghapus aset butuh permission <code>assets.manage</code>; dengan <code>assets.view</code> Anda hanya melihat daftar. Mencatat aset di sini tidak otomatis membuat pengeluaran di Finance; catat pembeliannya terpisah di <strong>Finance → Expenses</strong>.</x-tutorial.note>
</x-tutorial.section>
