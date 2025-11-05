<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Set enum schema.org page type yang didukung.
     * Harus konsisten dengan App\Enums\SchemaPageType (nilai string-nya).
     */
    private array $schemaTypes = [
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

    public function up(): void
    {
        Schema::create('web_pages', function (Blueprint $table) {
            $table->id();

            // Identitas & routing
            $table->string('name', 120);              // Nama halaman (untuk admin/menu)
            $table->string('slug', 160)->unique();    // contoh: 'tentang-kami'
            $table->string('path', 255)->unique();    // contoh: '/tentang-kami' (relative path)
            $table->string('route_name', 191)->nullable(); // optional: nama route laravel
            $table->unsignedInteger('position')->default(0); // urutan di menu/footer
            $table->enum('layout', ['topbar', 'navbar', 'footer'])->default('footer');

            // Schema.org Page Type (enum)
            // Catatan: di SQLite enum akan dipetakan menjadi string biasa oleh Laravel.
            $table->enum('schema_type', $this->schemaTypes)
                ->default('tentang-kami')
                ->index();

            $table->json('content')->nullable();

            // SEO
            $table->string('seo_title', 60)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->json('meta_keywords')->nullable();

            // Indexing flags
            $table->boolean('noindex')->default(false);
            $table->boolean('nofollow')->default(false);
            $table->boolean('is_active')->default(true);

            // Opsional konten ringan (untuk landing sederhana)
            $table->text('excerpt')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indeks tambahan
            $table->index(['is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_pages');
    }
};
