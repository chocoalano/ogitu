<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class WebPage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'web_pages';

    /**
     * Daftar layout yang didukung (harus cocok dengan enum di migration).
     */
    public const LAYOUTS = ['topbar', 'navbar', 'footer'];

    /**
     * Daftar schema_type (berdasarkan slug halaman – HARUS sama dengan enum di migration).
     */
    public const SCHEMA_KEYS = [
        'tentang-kami',
        'fitur',
        'berita',
        'karier',
        'layanan',
        'tim-kami',
        'kemitraan',
        'faq',
        'blog',
        'pusat-bantuan',
        'masukan',
        'kontak',
        'aksesibilitas',
        'syarat',
        'privasi',
        'cookie',
    ];

    /**
     * Mapping slug schema_type -> Schema.org @type untuk JSON-LD.
     */
    public const SCHEMA_TO_SCHEMAORG = [
        'tentang-kami' => 'AboutPage',
        'fitur' => 'WebPage',
        'berita' => 'CollectionPage',
        'karier' => 'CollectionPage',
        'layanan' => 'CollectionPage',
        'tim-kami' => 'AboutPage',
        'kemitraan' => 'WebPage',
        'faq' => 'FAQPage',
        'blog' => 'Blog',
        'pusat-bantuan' => 'QAPage',
        'masukan' => 'WebPage',
        'kontak' => 'ContactPage',
        'aksesibilitas' => 'WebPage',
        'syarat' => 'TermsOfService',
        'privasi' => 'PrivacyPolicy',
        'cookie' => 'WebPage',
    ];

    protected $fillable = [
        'name',
        'slug',
        'path',
        'route_name',
        'position',
        'layout',
        'schema_type',
        'content',
        'seo_title',
        'meta_description',
        'meta_keywords',
        'noindex',
        'nofollow',
        'is_active',
        'excerpt',
    ];

    protected $casts = [
        'position' => 'integer',
        'content' => 'array',
        'meta_keywords' => 'array',
        'noindex' => 'boolean',
        'nofollow' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'url',
        'schema_org_type',
    ];

    /**
     * Route model binding pakai slug jika perlu.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * URL final halaman.
     * - Jika ada 'route_name' → pakai route()
     * - Jika tidak → pakai route pages.show dengan slug
     */
    public function getUrlAttribute(): string
    {
        if (! empty($this->route_name)) {
            try {
                return route($this->route_name);
            } catch (\Throwable $e) {
                // fallback jika route belum didefinisikan
            }
        }

        // Use pages.show route with slug
        try {
            return route('pages.show', ['slug' => $this->slug]);
        } catch (\Throwable $e) {
            // fallback ke path manual
            $path = ltrim((string) ($this->path ?? ''), '/');

            return URL::to($path);
        }
    }

    /**
     * Schema.org @type berdasar mapping SCHEMA_TO_SCHEMAORG.
     */
    public function getSchemaOrgTypeAttribute(): string
    {
        $key = (string) $this->schema_type;

        return static::SCHEMA_TO_SCHEMAORG[$key] ?? 'WebPage';
    }

    /* ============================
     |         Mutators
     ============================ */

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;
        // Auto-set slug jika kosong
        if (empty($this->attributes['slug']) && ! empty($value)) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    public function setSlugAttribute($value): void
    {
        $slug = Str::slug((string) $value);
        $this->attributes['slug'] = $slug;

        // Auto-set path jika kosong
        if (empty($this->attributes['path'])) {
            $this->attributes['path'] = '/'.$slug;
        }

        // Auto-guess schema_type jika kosong/invalid
        if (empty($this->attributes['schema_type']) || ! in_array($this->attributes['schema_type'], static::SCHEMA_KEYS, true)) {
            $this->attributes['schema_type'] = static::guessSchemaKeyFromSlug($slug);
        }
    }

    public function setPathAttribute($value): void
    {
        $p = trim((string) $value);
        $p = $p === '' ? ('/'.$this->attributes['slug'] ?? '/') : $p;
        $this->attributes['path'] = Str::start($p, '/');
    }

    public function setRouteNameAttribute($value): void
    {
        $this->attributes['route_name'] = $value ?: null;
    }

    public function setLayoutAttribute($value): void
    {
        $v = (string) $value;
        $this->attributes['layout'] = in_array($v, static::LAYOUTS, true) ? $v : 'footer';
    }

    public function setSchemaTypeAttribute($value): void
    {
        $v = (string) $value;
        if (! in_array($v, static::SCHEMA_KEYS, true)) {
            // coba tebak dari slug, jika gagal pakai default 'tentang-kami'
            $v = static::guessSchemaKeyFromSlug($this->attributes['slug'] ?? '') ?: 'tentang-kami';
        }
        $this->attributes['schema_type'] = $v;
    }

    /* ============================
     |           Scopes
     ============================ */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLayout($query, ?string $layout)
    {
        if ($layout && in_array($layout, static::LAYOUTS, true)) {
            $query->where('layout', $layout);
        }

        return $query;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.str_replace(' ', '%', $term).'%';

        return $query->where(function ($q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('slug', 'like', $like)
                ->orWhere('seo_title', 'like', $like)
                ->orWhere('meta_description', 'like', $like)
                ->orWhere('path', 'like', $like);
        });
    }

    /* ============================
     |           Helpers
     ============================ */

    /**
     * Tebak schema key dari slug (exact match daftar yang didukung).
     */
    public static function guessSchemaKeyFromSlug(string $slug): ?string
    {
        $slug = Str::of($slug)->lower()->toString();

        foreach (static::SCHEMA_KEYS as $key) {
            if ($slug === $key) {
                return $key;
            }
        }

        // fallback: mapping sederhana berdasar kata kunci
        return match (true) {
            str_contains($slug, 'tentang') || str_contains($slug, 'about') => 'tentang-kami',
            str_contains($slug, 'fitur') || str_contains($slug, 'features') => 'fitur',
            str_contains($slug, 'berita') || str_contains($slug, 'news') => 'berita',
            str_contains($slug, 'karier') || str_contains($slug, 'careers') => 'karier',
            str_contains($slug, 'layanan') || str_contains($slug, 'services') => 'layanan',
            str_contains($slug, 'tim') || str_contains($slug, 'team') => 'tim-kami',
            str_contains($slug, 'mitra') || str_contains($slug, 'partner') => 'kemitraan',
            str_contains($slug, 'faq') => 'faq',
            str_contains($slug, 'blog') => 'blog',
            str_contains($slug, 'bantuan') || str_contains($slug, 'support') => 'pusat-bantuan',
            str_contains($slug, 'masukan') || str_contains($slug, 'feedback') => 'masukan',
            str_contains($slug, 'kontak') || str_contains($slug, 'contact') => 'kontak',
            str_contains($slug, 'akses') || str_contains($slug, 'access') => 'aksesibilitas',
            str_contains($slug, 'syarat') || str_contains($slug, 'terms') => 'syarat',
            str_contains($slug, 'privasi') || str_contains($slug, 'privacy') => 'privasi',
            str_contains($slug, 'cookie') || str_contains($slug, 'cookies') => 'cookie',
            default => null,
        };
    }

    /**
     * Bangun JSON-LD minimal untuk halaman ini.
     *
     * @return array JSON-LD (gunakan json_encode untuk output)
     */
    public function toJsonLd(array $overrides = []): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => $this->schema_org_type, // terjemahan ke Schema.org @type
            'url' => $overrides['url'] ?? $this->url,
            'name' => $overrides['name'] ?? ($this->seo_title ?: $this->name),
            'description' => $overrides['description'] ?? $this->meta_description,
            'inLanguage' => $overrides['inLanguage'] ?? str_replace('_', '-', app()->getLocale() ?: 'id-ID'),
            'isPartOf' => $overrides['isPartOf'] ?? [
                '@type' => 'WebSite',
                'name' => config('app.name'),
                'url' => url('/'),
            ],
            'publisher' => $overrides['publisher'] ?? [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $overrides['logo'] ?? asset('favicon.ico'),
                ],
            ],
        ];

        // Hapus null agar rapi
        return array_filter($data, fn ($v) => ! is_null($v));
    }
}
