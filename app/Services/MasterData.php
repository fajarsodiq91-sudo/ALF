<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\MasterDataItem;
use App\Models\SiteItem;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use Illuminate\Support\Collection;

/**
 * Admin-managed dropdown options. Each group maps to the model column that stores the
 * option's `code`, so usage can be counted and in-use options are protected from deletion.
 * Registered as a singleton, so lookups are cached for the length of a request.
 */
class MasterData
{
    /**
     * `protected` codes carry business logic elsewhere: they can be renamed but never removed or deactivated.
     *
     * @var array<string, array{label: string, description: string, model: class-string, column: string, protected: list<string>}>
     */
    public const GROUPS = [
        'customer_type' => [
            'label' => 'Customer Type',
            'description' => 'Types of customers (Sales).',
            'model' => Customer::class,
            'column' => 'customer_type',
            'protected' => [],
        ],
        'asset_category' => [
            'label' => 'Asset Category',
            'description' => 'Categories of company assets.',
            'model' => Asset::class,
            'column' => 'category',
            'protected' => [],
        ],
        'employment_type' => [
            'label' => 'Employment Type',
            'description' => 'How employees are employed (HR).',
            'model' => Employee::class,
            'column' => 'employment_type',
            'protected' => [],
        ],
        'leave_type' => [
            'label' => 'Leave Type',
            'description' => 'Kinds of leave. "Annual Leave" draws from the yearly quota, so it is fixed.',
            'model' => LeaveRequest::class,
            'column' => 'leave_type',
            'protected' => [LeaveRequest::ANNUAL],
        ],
        'program_type' => [
            'label' => 'Program Type',
            'description' => 'Kinds of training programs, e.g. Learning or Consulting.',
            'model' => TrainingProgram::class,
            'column' => 'program_type',
            'protected' => [],
        ],
        'delivery_mode' => [
            'label' => 'Training Delivery Mode',
            'description' => 'How training sessions are delivered.',
            'model' => TrainingSession::class,
            'column' => 'delivery_mode',
            'protected' => [],
        ],
        'portfolio_category' => [
            'label' => 'Portfolio Category',
            'description' => 'Categories of the public website portfolio. Each one becomes a filter button on the Portfolio page.',
            'model' => SiteItem::class,
            'column' => 'category',
            'protected' => [],
        ],
    ];

    /** @var array<string, Collection<int, MasterDataItem>> */
    private array $cache = [];

    public function flush(): void
    {
        $this->cache = [];
    }

    /**
     * @return Collection<int, MasterDataItem>
     */
    public function items(string $group): Collection
    {
        return $this->cache[$group] ??= MasterDataItem::where('group', $group)->orderBy('sort_order')->orderBy('label')->get();
    }

    /**
     * Active options as code => label. Pass the record's current code so an option that was
     * deactivated later still shows up when editing that record.
     *
     * @return array<string, string>
     */
    public static function options(string $group, ?string $include = null): array
    {
        return app(self::class)->items($group)
            ->filter(fn (MasterDataItem $item) => $item->is_active || $item->code === $include)
            ->pluck('label', 'code')
            ->all();
    }

    /** The label for a stored code, whether or not the option is still active. */
    public static function label(string $group, ?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        return app(self::class)->items($group)->firstWhere('code', $code)?->label ?? $code;
    }

    /**
     * Every known code, active or not (used to validate submitted values).
     *
     * @return list<string>
     */
    public static function codes(string $group): array
    {
        return app(self::class)->items($group)->pluck('code')->all();
    }

    /** How many records currently use the given option. */
    public static function usageCount(string $group, string $code): int
    {
        $definition = self::GROUPS[$group];

        return $definition['model']::where($definition['column'], $code)->count();
    }

    public static function isProtected(string $group, string $code): bool
    {
        return in_array($code, self::GROUPS[$group]['protected'], true);
    }
}
