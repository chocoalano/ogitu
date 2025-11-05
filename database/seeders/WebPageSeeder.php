<?php

namespace Database\Seeders;

use App\Models\WebPage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WebPageSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $app = config('app.name');

        // Daftar halaman wajib (schema_type = slug)
        $items = [
            ['slug' => 'tentang-kami',   'name' => 'Tentang Kami',   'layout' => 'navbar'],
            ['slug' => 'fitur',          'name' => 'Fitur',          'layout' => 'navbar'],
            ['slug' => 'berita',         'name' => 'Berita',         'layout' => 'navbar'],
            ['slug' => 'karier',         'name' => 'Karier',         'layout' => 'navbar'],
            ['slug' => 'layanan',        'name' => 'Layanan',        'layout' => 'navbar'],
            ['slug' => 'tim-kami',       'name' => 'Tim Kami',       'layout' => 'footer'],
            ['slug' => 'kemitraan',      'name' => 'Kemitraan',      'layout' => 'footer'],
            ['slug' => 'faq',            'name' => 'FAQ',            'layout' => 'navbar'],
            ['slug' => 'blog',           'name' => 'Blog',           'layout' => 'navbar'],
            ['slug' => 'pusat-bantuan',  'name' => 'Pusat Bantuan',  'layout' => 'footer'],
            ['slug' => 'masukan',        'name' => 'Masukan',        'layout' => 'footer'],
            ['slug' => 'kontak',         'name' => 'Kontak',         'layout' => 'navbar'],
            ['slug' => 'aksesibilitas',  'name' => 'Aksesibilitas',  'layout' => 'footer'],
            ['slug' => 'syarat',         'name' => 'Syarat',         'layout' => 'footer'],
            ['slug' => 'privasi',        'name' => 'Privasi',        'layout' => 'footer'],
            ['slug' => 'cookie',         'name' => 'Cookie',         'layout' => 'footer'],
        ];

        $position = 1;

        foreach ($items as $item) {
            $slug   = $item['slug'];
            $name   = $item['name'];
            $layout = $item['layout'];

            // ----- CONTENT BLOCKS (format array sesuai permintaan) -----
            // Heading h2
            $headingText = Str::headline($name.' '.$app);
            $headingBlock = [
                'type' => 'heading',
                'data' => [
                    'level'   => 'h2',
                    'content' => $headingText,
                ],
            ];

            // Paragraph HTML dengan <br><br> antar sub-paragraf
            $p1 = 'Quia rerum repellat est voluptatibus nostrum praesentium. Assumenda et iure qui aspernatur.';
            $p2 = 'Consequatur fugit omnis odit officiis incidunt aut nobis. Repudiandae dolores dolorem quo non alias facilis.';
            $p3 = 'Recusandae ducimus velit incidunt provident illo velit modi dolorem. Debitis vel soluta rerum corporis vel voluptas.';
            $paragraphHtml = '<p>'.e($p1).'<br><br>'.e($p2).'<br><br>'.e($p3).'</p>';

            $paragraphBlock = [
                'type' => 'paragraph',
                'data' => [
                    'content' => $paragraphHtml,
                ],
            ];

            $blocks = [$headingBlock, $paragraphBlock];

            // ----- Upsert via Eloquent agar casts JSON jalan -----
            WebPage::updateOrCreate(
                ['slug' => $slug],
                [
                    'name'              => $name,
                    'path'              => '/'.$slug,
                    'route_name'        => null,
                    'position'          => $position++,
                    'layout'            => $layout,      // enum: topbar|navbar|footer

                    'schema_type'       => $slug,        // HARUS salah satu enum di migration (slug)

                    'content'           => $blocks,      // array -> di-cast ke JSON oleh model

                    'seo_title'         => Str::limit($name, 60, ''),
                    'meta_description'  => "Halaman {$name} - {$app}",
                    'meta_keywords'     => ['informasi', Str::slug($name), Str::slug($app)],

                    'noindex'           => false,
                    'nofollow'          => false,
                    'is_active'         => true,

                    'excerpt'           => 'Informasi '.Str::lower($name).' dari '.$app,
                    'updated_at'        => $now,
                ]
            );
        }
    }
}
