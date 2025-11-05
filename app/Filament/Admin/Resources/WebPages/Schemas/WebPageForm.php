<?php

namespace App\Filament\Admin\Resources\WebPages\Schemas;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WebPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Dasar')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->helperText('Nama internal untuk identifikasi halaman (tidak ditampilkan kepada publik).'),
                        TextInput::make('slug')
                            ->required()
                            ->readOnly()
                            ->helperText('Versi ramah URL dari nama halaman. Gunakan huruf kecil dan pisahkan kata dengan tanda hubung (-). Contoh: tentang-perusahaan.'),
                        TextInput::make('path')
                            ->required()
                            ->readOnly()
                            ->helperText('Jalur URL relatif halaman. Harus diawali dengan tanda miring (/). Contoh: /tentang-kami.'),
                        TextInput::make('route_name')
                            ->helperText('Nama rute jika diperlukan untuk navigasi programatik.'),
                    ]),
                Section::make('Pengaturan Lainnya')
                    ->columns(2)
                    ->schema([
                        TextInput::make('position')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Angka urutan tampilan. Angka yang lebih kecil akan muncul lebih dulu.'),
                        Select::make('layout')
                            ->options(['topbar' => 'Topbar', 'navbar' => 'Navbar', 'footer' => 'Footer'])
                            ->default('footer')
                            ->required()
                            ->helperText('Posisi tempat tautan halaman ini akan muncul di navigasi situs.'),
                        Select::make('schema_type')
                            ->options([
                                'tentang-kami' => 'Tentang kami',
                                'fitur' => 'Fitur',
                                'berita' => 'Berita',
                                'karier' => 'Karier',
                                'layanan' => 'Layanan',
                                'tim-kami' => 'Tim kami',
                                'kemitraan' => 'Kemitraan',
                                'faq' => 'Faq',
                                'blog' => 'Blog',
                                'pusat-bantuan' => 'Pusat bantuan',
                                'masukan' => 'Masukan',
                                'kontak' => 'Kontak',
                                'aksesibilitas' => 'Aksesibilitas',
                                'syarat' => 'Syarat',
                                'privasi' => 'Privasi',
                                'cookie' => 'Cookie',
                            ])
                            ->default('tentang-kami')
                            ->required()
                            ->helperText('Pilih tipe skema konten yang paling sesuai untuk optimasi SEO Schema.org.'),
                    ]),
                Section::make('Konten Halaman')
                    ->schema([
                        Builder::make('content')
                            ->blocks([
                                Block::make('heading')
                                    ->schema([
                                        TextInput::make('content')
                                            ->label('Heading')
                                            ->required()
                                            ->helperText('Masukkan teks untuk judul atau subjudul.'),
                                        Select::make('level')
                                            ->options([
                                                'h1' => 'Heading 1',
                                                'h2' => 'Heading 2',
                                                'h3' => 'Heading 3',
                                                'h4' => 'Heading 4',
                                                'h5' => 'Heading 5',
                                                'h6' => 'Heading 6',
                                            ])
                                            ->required()
                                            ->helperText('Pilih tingkat kepentingan judul (H1 harus unik di halaman).'),
                                    ])
                                    ->columns(2),
                                Block::make('paragraph')
                                    ->schema([
                                        RichEditor::make('content')
                                            ->label('Paragraph')
                                            ->fileAttachmentsDisk('public')
                                            ->fileAttachmentsDirectory('pages')
                                            ->fileAttachmentsVisibility('public')
                                            ->fileAttachmentsAcceptedFileTypes(['image/png', 'image/jpeg'])
                                            ->fileAttachmentsMaxSize(5120)
                                            ->extraInputAttributes(['style' => 'min-height: 40rem; max-height: 70vh; overflow-y: auto;'])
                                            ->required()
                                            ->helperText('Tuliskan konten utama halaman, Anda dapat menyisipkan gambar dan format teks.'),
                                    ]),
                                Block::make('image')
                                    ->schema([
                                        FileUpload::make('url')
                                            ->label('Image')
                                            ->image()
                                            ->required()
                                            ->helperText('Unggah gambar yang akan ditampilkan.'),
                                        TextInput::make('alt')
                                            ->label('Alt text')
                                            ->required()
                                            ->helperText('Deskripsi gambar untuk aksesibilitas dan SEO.'),
                                    ]),
                            ])
                            ->helperText('Gunakan blok di bawah untuk menyusun konten halaman secara visual.'),
                    ])->columnSpanFull(),
                Section::make('SEO dan Metadata')
                    ->schema([
                        TextInput::make('seo_title')
                            ->helperText('Judul yang akan muncul di hasil pencarian (maksimal 60 karakter).'),
                        Textarea::make('meta_description')
                            ->helperText('Deskripsi singkat untuk hasil pencarian (maksimal 160 karakter).')
                            ->columnSpanFull(),
                        TextInput::make('meta_keywords')
                            ->helperText('Kata kunci utama yang dipisahkan dengan koma (opsional).'),
                    ]),
                Section::make('Indexing dan Status')
                    ->columns(2)
                    ->schema([
                        Toggle::make('noindex')
                            ->required()
                            ->helperText('Aktifkan untuk MENCEGAH mesin pencari mengindeks halaman ini.'),
                        Toggle::make('nofollow')
                            ->required()
                            ->helperText('Aktifkan untuk MENCEGAH mesin pencari mengikuti tautan yang ada di halaman ini.'),
                        Toggle::make('is_active')
                            ->required()
                            ->helperText('Nonaktifkan untuk menyembunyikan halaman dari publik.'),
                        Textarea::make('excerpt')
                            ->helperText('Ringkasan singkat yang akan digunakan untuk kartu pratinjau (preview card) atau daftar artikel.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
