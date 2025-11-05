<?php

namespace App\Livewire\Ecommerce;

use App\Models\Article;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ArticleShow extends Component
{
    public Article $article;

    public function mount(string $slug): void
    {
        $this->article = Article::with(['category', 'categories', 'author'])
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();
    }

    public function getTitle(): string
    {
        return $this->article->seo_title ?: $this->article->title;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.ecommerce.article-show', ['title' => $this->getTitle()]);
    }
}
