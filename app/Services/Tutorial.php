<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The in-app tutorial (ERP → Tutorial). Every topic is a Blade view in
 * resources/views/erp/tutorial/topics/{slug}.blade.php.
 *
 * Keep it in step with the app: when a feature, menu, form or workflow changes, update the
 * matching topic in the same change. `covers` lists the route names a topic documents;
 * tests/Feature/TutorialTest.php fails when a page exists that no topic covers, or when a
 * topic covers a route that no longer exists.
 */
class Tutorial
{
    /** Sidebar/index headings, in display order. */
    public const GROUPS = ['Mulai', 'Finance', 'Sales & Training', 'Operasional', 'Pengaturan'];

    /**
     * `permission`: null = every ERP user, otherwise the permission(s) of which the user needs one.
     * `route`: the page the topic's "Buka halaman" button leads to.
     * `covers`: route-name patterns (`*` wildcard) this topic documents.
     *
     * @var array<string, array{title: string, summary: string, group: string, permission: string|list<string>|null, route: string|null, covers: list<string>}>
     */
    public const TOPICS = [
        'getting-started' => [
            'title' => 'Memulai',
            'summary' => 'Login, mengenal tampilan, dashboard, profil, dan hak akses per role.',
            'group' => 'Mulai',
            'permission' => null,
            'route' => 'dashboard',
            'covers' => ['dashboard', 'profile.edit', 'tutorial.*'],
        ],
        'finance-overview' => [
            'title' => 'Dashboard, Laporan & Transaksi',
            'summary' => 'Membaca ringkasan keuangan, laporan, dan buku besar semua transaksi.',
            'group' => 'Finance',
            'permission' => 'finance.view',
            'route' => 'finance.dashboard',
            'covers' => ['finance.dashboard', 'finance.reports.*', 'finance.transactions'],
        ],
        'finance-setup' => [
            'title' => 'Akun & Kategori',
            'summary' => 'Menyiapkan rekening/kas dan kategori pemasukan-pengeluaran sebelum mencatat transaksi.',
            'group' => 'Finance',
            'permission' => 'finance.view',
            'route' => 'finance.accounts',
            'covers' => ['finance.accounts*', 'finance.categories*'],
        ],
        'finance-transactions' => [
            'title' => 'Pemasukan, Pengeluaran, Transfer & Pinjaman',
            'summary' => 'Mencatat uang masuk dan keluar, transfer antar akun, penarikan pemilik, pinjaman, serta bukti transaksi.',
            'group' => 'Finance',
            'permission' => 'finance.view',
            'route' => 'finance.income',
            'covers' => ['finance.income*', 'finance.expenses*', 'finance.owner-draws*', 'finance.transfers*', 'finance.loans*', 'finance.proofs.*'],
        ],
        'finance-tax' => [
            'title' => 'Pajak',
            'summary' => 'Mengatur jenis pajak (PPN, PPh, PPh Final) dan mencatat setoran pajak.',
            'group' => 'Finance',
            'permission' => 'finance.view',
            'route' => 'finance.taxes',
            'covers' => ['finance.taxes*', 'finance.tax-payments*'],
        ],
        'sales-customers' => [
            'title' => 'Customer & Pendaftaran',
            'summary' => 'Mengundang customer lewat QR code, meninjau pendaftaran, menyetujui, dan mengelola data customer.',
            'group' => 'Sales & Training',
            'permission' => 'sales.view',
            'route' => 'sales.index',
            'covers' => ['sales.*', 'customer-registration.*'],
        ],
        'customer-portal' => [
            'title' => 'Customer Portal',
            'summary' => 'Apa yang dilihat dan dilakukan customer di portal, cara membuka pratinjau, dan portofolio publik.',
            'group' => 'Sales & Training',
            'permission' => 'sales.view',
            'route' => 'customer-portal.index',
            'covers' => ['customer-portal.*', 'portal.*', 'customer-portfolio.*'],
        ],
        'training-programs' => [
            'title' => 'Program & Kategori Training',
            'summary' => 'Membuat katalog program: harga, diskon, harga grup, syarat & ketentuan, dan foto.',
            'group' => 'Sales & Training',
            'permission' => 'training.view',
            'route' => 'training.programs.index',
            'covers' => ['training.programs.*', 'training.categories.*'],
        ],
        'training-sessions' => [
            'title' => 'Sesi Training',
            'summary' => 'Menjadwalkan pertemuan, mencatat pembayaran, menangani reschedule, dan menyelesaikan sesi.',
            'group' => 'Sales & Training',
            'permission' => 'training.view',
            'route' => 'training.index',
            'covers' => ['training.index', 'training.create', 'training.edit', 'training.payments.*', 'participant.*'],
        ],
        'projects' => [
            'title' => 'Projects',
            'summary' => 'Mengelola proyek beserta task, penanggung jawab, dan notifikasi email.',
            'group' => 'Operasional',
            'permission' => 'projects.view',
            'route' => 'projects.index',
            'covers' => ['projects.*'],
        ],
        'hr-employees' => [
            'title' => 'HR: Karyawan',
            'summary' => 'Data karyawan, nomor karyawan otomatis, foto, dan tanda tangan sertifikat.',
            'group' => 'Operasional',
            'permission' => 'hr.view',
            'route' => 'hr.index',
            'covers' => ['hr.index', 'hr.create', 'hr.edit'],
        ],
        'hr-leave-attendance' => [
            'title' => 'HR: Cuti & Absensi',
            'summary' => 'Mengajukan dan menyetujui cuti, serta mencatat kehadiran harian.',
            'group' => 'Operasional',
            'permission' => 'hr.view',
            'route' => 'hr.leaves.index',
            'covers' => ['hr.leaves.*', 'hr.attendance.*'],
        ],
        'hr-payroll' => [
            'title' => 'HR: Payroll',
            'summary' => 'Membuat slip gaji bulanan dan membayarnya sehingga tercatat sebagai pengeluaran.',
            'group' => 'Operasional',
            'permission' => 'hr.payroll',
            'route' => 'hr.payroll.index',
            'covers' => ['hr.payroll.*'],
        ],
        'assets' => [
            'title' => 'Assets',
            'summary' => 'Mencatat aset perusahaan, statusnya, dan nilai perolehan.',
            'group' => 'Operasional',
            'permission' => 'assets.view',
            'route' => 'assets.index',
            'covers' => ['assets.*'],
        ],
        'master-data' => [
            'title' => 'Master Data',
            'summary' => 'Pilihan dropdown, jam operasional, blokir slot, dan teks perjanjian pendaftaran.',
            'group' => 'Pengaturan',
            'permission' => 'masterdata.manage',
            'route' => 'masterdata.index',
            'covers' => ['masterdata.*'],
        ],
        'settings' => [
            'title' => 'Settings: User, Role & Sistem',
            'summary' => 'Membuat akun staf, mengatur role dan permission, serta profil perusahaan.',
            'group' => 'Pengaturan',
            'permission' => ['settings.manage-users', 'settings.manage-roles', 'settings.manage-system'],
            'route' => null,
            'covers' => ['settings.*'],
        ],
    ];

    /** @return list<string> */
    public static function slugs(): array
    {
        return array_keys(self::TOPICS);
    }

    public static function canView(string $slug, User $user): bool
    {
        if (! isset(self::TOPICS[$slug])) {
            return false;
        }

        $permission = self::TOPICS[$slug]['permission'];

        return $permission === null || $user->canAny((array) $permission);
    }

    /**
     * The topics a user may read, grouped in display order: heading => [slug => topic].
     *
     * @return Collection<string, Collection<string, array<string, mixed>>>
     */
    public static function groupedFor(User $user): Collection
    {
        $visible = collect(self::TOPICS)->filter(fn (array $topic, string $slug) => self::canView($slug, $user));

        return collect(self::GROUPS)
            ->mapWithKeys(fn (string $group) => [$group => $visible->where('group', $group)])
            ->filter(fn (Collection $topics) => $topics->isNotEmpty());
    }

    /** The page a topic's "Buka halaman" button leads to, when it has one. */
    public static function url(string $slug): ?string
    {
        $route = self::TOPICS[$slug]['route'] ?? null;

        return $route ? route($route) : null;
    }

    /** Whether some topic documents the route with this name. */
    public static function documents(string $routeName): bool
    {
        return collect(self::TOPICS)->contains(fn (array $topic) => Str::is($topic['covers'], $routeName));
    }
}
