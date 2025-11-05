<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * 1) Tabel Kategori: article_categories
         */
        Schema::create('article_categories', function (Blueprint $table) {
            $table->id();

            $table->string('name', 120);
            $table->string('slug', 160)->unique();
            $table->text('description')->nullable();

            // Hierarki (opsional)
            $table->foreignId('parent_id')->nullable()
                ->constrained('article_categories')->nullOnDelete();

            // SEO ringan
            $table->string('seo_title', 60)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->json('meta_keywords')->nullable();

            // Utilitas
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'is_active']);
            $table->index('position');
        });

        /**
         * 2) Tabel Artikel: articles
         */
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            // Konten utama
            $table->string('title', 160);
            $table->string('slug', 160)->unique();
            $table->text('excerpt')->nullable();

            // Konten blok (builder)
            $table->json('content');

            // Gambar cover
            $table->string('cover_url', 2048)->nullable();
            $table->string('cover_alt', 200)->nullable();

            // Kategori utama (opsional)
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('article_categories')
                ->nullOnDelete();

            // Tags & SEO
            $table->json('tags')->nullable();
            $table->string('seo_title', 60)->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->json('meta_keywords')->nullable();
            $table->boolean('noindex')->default(false);
            $table->boolean('nofollow')->default(false);

            // Publikasi
            $table->enum('status', ['draft', 'scheduled', 'published'])->default('draft');
            $table->dateTime('published_at')->nullable();

            // Penulis
            $table->foreignId('author_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indeks umum
            $table->index(['status', 'published_at']);
            $table->index('category_id');
            $table->index('author_id');
        });

        /**
         * 3) Pivot: article_content_categories (many-to-many antara articles & article_categories)
         */
        Schema::create('article_content_categories', function (Blueprint $table) {
            $table->foreignId('article_id')
                ->constrained('articles')
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained('article_categories')
                ->cascadeOnDelete();

            // Opsi tambahan
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            // Cegah duplikasi pasangan
            $table->unique(['article_id', 'category_id']);
            $table->index(['category_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_content_categories');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('article_categories');
    }
};
