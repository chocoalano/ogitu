<?php

namespace App\Http\Controllers;

use App\Models\WebPage;

class WebPageController extends Controller
{
    /**
     * Display the specified web page.
     */
    public function show(string $slug)
    {
        $page = WebPage::active()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('pages.webpage.show', compact('page'));
    }
}
