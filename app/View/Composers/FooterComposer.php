<?php

namespace App\View\Composers;

use App\Models\WebPage;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class FooterComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $footerPages = Cache::remember('footer_pages', 3600, function () {
            return WebPage::active()
                ->layout('footer')
                ->ordered()
                ->get(['name', 'slug', 'path', 'route_name', 'position', 'schema_type'])
                ->groupBy(function ($page) {
                    // Group by category based on schema_type
                    return $this->getCategoryFromSchemaType($page->schema_type);
                });
        });

        // Organize into footer sections
        $footerNav = [
            [
                'judul' => 'Tentang',
                'tautan' => $this->mapPagesToLinks($footerPages->get('tentang', collect())),
            ],
            [
                'judul' => 'Perusahaan',
                'tautan' => $this->mapPagesToLinks($footerPages->get('perusahaan', collect())),
            ],
            [
                'judul' => 'Dukungan',
                'tautan' => $this->mapPagesToLinks($footerPages->get('dukungan', collect())),
            ],
        ];

        $legal = $this->mapPagesToLinks($footerPages->get('legal', collect()));

        $view->with([
            'footerNav' => $footerNav,
            'legal' => $legal,
        ]);
    }

    /**
     * Get category from schema type
     */
    protected function getCategoryFromSchemaType(string $schemaType): string
    {
        return match ($schemaType) {
            'tentang-kami', 'fitur', 'berita', 'karier', 'layanan' => 'tentang',
            'tim-kami', 'kemitraan', 'faq', 'blog' => 'perusahaan',
            'pusat-bantuan', 'masukan', 'kontak', 'aksesibilitas' => 'dukungan',
            'syarat', 'privasi', 'cookie' => 'legal',
            default => 'tentang',
        };
    }

    /**
     * Map pages collection to links array
     */
    protected function mapPagesToLinks($pages): array
    {
        return $pages->map(function ($page) {
            // Use route_name if exists, otherwise use pages.show route
            $url = $page->route_name
                ? route($page->route_name)
                : route('pages.show', ['slug' => $page->slug]);

            return [
                'teks' => $page->name,
                'url' => $url,
            ];
        })->toArray();
    }
}
