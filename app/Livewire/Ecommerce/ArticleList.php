<?php

namespace App\Livewire\Ecommerce;

use App\Models\Article;
use App\Models\ArticleCategory;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ArticleList extends Component
{
    use WithPagination;

    #[Url(as: 'category')]
    public ?string $selectedCategory = null;

    #[Url(as: 'search')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedCategory(): void
    {
        $this->resetPage();
    }

    public function filterByCategory(?string $categorySlug): void
    {
        $this->selectedCategory = $categorySlug;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->selectedCategory = null;
        $this->search = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = Article::query()
            ->with(['category', 'author'])
            ->published()
            ->latest('published_at');

        // Filter by search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('excerpt', 'like', '%'.$this->search.'%')
                    ->orWhere('tags', 'like', '%'.$this->search.'%');
            });
        }

        // Filter by category
        if ($this->selectedCategory) {
            $query->whereHas('category', function ($q) {
                $q->where('slug', $this->selectedCategory);
            });
        }

        $articles = $query->paginate(9);

        // Get all categories for filter
        $categories = ArticleCategory::query()
            ->whereHas('articles', function ($q) {
                $q->published();
            })
            ->withCount(['articles' => function ($q) {
                $q->published();
            }])
            ->orderBy('name')
            ->get();

        return view('livewire.ecommerce.article-list', [
            'articles' => $articles,
            'categories' => $categories,
        ]);
    }
}
