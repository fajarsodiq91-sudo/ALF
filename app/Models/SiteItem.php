<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * One card / row of repeating content on the public company site, edited from Master Data → Website.
 * The `type` decides which columns are used: see TYPES. Fixed page text lives in App\Services\SiteContent.
 */
#[Fillable(['type', 'title', 'subtitle', 'body', 'icon', 'image_path', 'image_url', 'link_url', 'category', 'number', 'sort_order', 'is_active'])]
class SiteItem extends Model
{
    /**
     * `fields` are the inputs an admin fills in for that type (title is always the first): name => label/rules.
     * `page` is the site page the items appear on. `image` items may carry an uploaded picture.
     *
     * @var array<string, array{label: string, singular: string, description: string, page: string, fields: array<string, array<string, mixed>>}>
     */
    public const TYPES = [
        'home_service' => [
            'label' => 'Home Service Cards',
            'singular' => 'card',
            'description' => 'The cards under "Services" on the Home page.',
            'page' => 'home',
            'fields' => [
                'icon' => ['label' => 'Icon (emoji)', 'hint' => 'Paste one emoji, e.g. 📊.'],
                'title' => ['label' => 'Title', 'required' => true],
                'body' => ['label' => 'Description', 'required' => true],
            ],
        ],
        'service' => [
            'label' => 'Services',
            'singular' => 'service',
            'description' => 'The service cards on the Services page.',
            'page' => 'services',
            'fields' => [
                'icon' => ['label' => 'Icon (emoji)', 'hint' => 'Paste one emoji, e.g. 📊.'],
                'title' => ['label' => 'Title', 'required' => true],
                'body' => ['label' => 'Description', 'required' => true],
            ],
        ],
        'training' => [
            'label' => 'Training Programs',
            'singular' => 'program',
            'description' => 'The corporate training cards on the Services page.',
            'page' => 'services',
            'fields' => [
                'title' => ['label' => 'Title', 'required' => true],
                'body' => ['label' => 'Description', 'required' => true],
            ],
        ],
        'stat' => [
            'label' => 'Statistics',
            'singular' => 'statistic',
            'description' => 'The animated counters on the Home page.',
            'page' => 'home',
            'fields' => [
                'title' => ['label' => 'Label', 'required' => true, 'hint' => 'e.g. Projects'],
                'number' => ['label' => 'Number', 'required' => true],
            ],
        ],
        'timeline' => [
            'label' => 'Timeline',
            'singular' => 'milestone',
            'description' => 'The company timeline on the About page.',
            'page' => 'about',
            'fields' => [
                'title' => ['label' => 'Year', 'required' => true, 'max' => 20],
                'body' => ['label' => 'What happened', 'required' => true],
            ],
        ],
        'testimonial' => [
            'label' => 'Testimonials',
            'singular' => 'testimonial',
            'description' => 'The client reviews that rotate on the Home page.',
            'page' => 'home',
            'fields' => [
                'title' => ['label' => 'Name', 'required' => true],
                'subtitle' => ['label' => 'Role / company'],
                'body' => ['label' => 'Review', 'required' => true],
                'link_url' => ['label' => 'Link to the original review', 'external' => true, 'hint' => 'Optional, e.g. the Google Maps review.'],
                'image' => ['label' => 'Photo'],
            ],
        ],
        'portfolio' => [
            'label' => 'Portfolio',
            'singular' => 'project',
            'description' => 'The project cards on the Portfolio page.',
            'page' => 'portfolio',
            'fields' => [
                'title' => ['label' => 'Title', 'required' => true],
                'body' => ['label' => 'Description', 'required' => true],
                'category' => ['label' => 'Category', 'required' => true, 'hint' => 'Also the filter button. The list is managed in the Portfolio Category dropdown.'],
                'image' => ['label' => 'Image'],
            ],
        ],
        'footer_link' => [
            'label' => 'Footer Links',
            'singular' => 'link',
            'description' => 'The links in the footer\'s services column.',
            'page' => 'global',
            'fields' => [
                'title' => ['label' => 'Label', 'required' => true],
                'link_url' => ['label' => 'Link', 'required' => true, 'hint' => 'A page of this site such as /services, or a full https:// address.'],
            ],
        ],
    ];

    protected static function booted(): void
    {
        // Listeners must return nothing: Eloquent stops calling the rest once one returns a value.
        static::saved(function (SiteItem $item): void {
            Cache::forget(self::cacheKey($item->type));
        });

        static::deleted(function (SiteItem $item): void {
            Cache::forget(self::cacheKey($item->type));

            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return list<string> */
    public static function types(): array
    {
        return array_keys(self::TYPES);
    }

    /**
     * What visitors see: the active items of a type in display order. Cached until one of them changes.
     *
     * @return Collection<int, SiteItem>
     */
    public static function active(string $type): Collection
    {
        // Rows are cached as plain arrays: `cache.serializable_classes` is off, so cached models would come back as __PHP_Incomplete_Class.
        $rows = Cache::rememberForever(self::cacheKey($type), fn () => static::query()
            ->where('type', $type)->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (SiteItem $item) => $item->getAttributes())->all());

        return static::hydrate($rows);
    }

    /** The uploaded picture, else the built-in one (a path under public/ or a full URL), else nothing. */
    public function imageUrl(): ?string
    {
        if ($this->image_path) {
            return asset('storage/'.$this->image_path);
        }

        if (! $this->image_url) {
            return null;
        }

        return str_starts_with($this->image_url, 'http') ? $this->image_url : asset($this->image_url);
    }

    /** The link, made absolute when it points at a page of this site. */
    public function href(): ?string
    {
        if (! $this->link_url) {
            return null;
        }

        return str_starts_with($this->link_url, '/') ? url($this->link_url) : $this->link_url;
    }

    private static function cacheKey(string $type): string
    {
        return 'site.items.'.$type;
    }
}
