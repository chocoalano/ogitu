<?php

namespace App\View\Composers;

use App\Models\WebPage;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class NavbarComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $topbarLinks = Cache::remember('topbar_links', 3600, function () {
            return WebPage::active()
                ->layout('topbar')
                ->ordered()
                ->get(['name', 'slug', 'route_name'])
                ->map(function ($page) {
                    return [
                        'label' => $page->name,
                        'url' => $page->route_name
                            ? route($page->route_name)
                            : route('pages.show', ['slug' => $page->slug]),
                    ];
                })
                ->toArray();
        });

        $view->with('topbarLinks', $topbarLinks);
    }
}
