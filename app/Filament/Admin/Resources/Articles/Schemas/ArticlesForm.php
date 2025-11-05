<?php

namespace App\Filament\Admin\Resources\Articles\Schemas;

use App\Models\ArticleCategory;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticlesForm
{
    public static function configure(Schema $schema): Schema
    {
        $isDebug = (bool) config('app.debug');

        return $schema
            ->components([
                Section::make('Konten')
                    ->description('Judul, slug, ringkasan, isi, dan cover.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('title')
                                ->label('Judul Artikel')
                                ->maxLength(120)
                                ->required()
                                ->default(fn () => $isDebug ? fake()->sentence(6) : null)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (callable $set, ?string $state, callable $get) {
                                    if (blank($get('slug')) && filled($state)) {
                                        $set('slug', Str::slug($state));
                                    }
                                    if (blank($get('seo_title')) && filled($state)) {
                                        $set('seo_title', Str::limit($state, 60, ''));
                                    }
                                }),

                            TextInput::make('slug')
                                ->label('Slug')
                                ->maxLength(160)
                                ->required()
                                ->helperText('Huruf kecil, pakai tanda hubung. Contoh: minuman-kecantikan-kolagen')
                                ->prefix(url('articles').'/')
                                ->default(fn () => $isDebug ? Str::slug(fake()->unique()->sentence(3)) : null),
                        ]),

                        Textarea::make('excerpt')
                            ->label('Ringkasan')
                            ->rows(3)
                            ->maxLength(300)
                            ->helperText('Ideal 140–160 karakter, dipakai untuk meta description jika tidak diisi manual.')
                            ->default(fn () => $isDebug ? Str::limit(fake()->paragraphs(2, true), 180) : null),

                        Builder::make('content')
                            ->label('Isi Artikel (blok)')
                            ->blocks([
                                Block::make('heading')
                                    ->schema([
                                        TextInput::make('content')->label('Heading')->required(),
                                        Select::make('level')
                                            ->label('Level')
                                            ->options([
                                                'h1' => 'Heading 1',
                                                'h2' => 'Heading 2',
                                                'h3' => 'Heading 3',
                                                'h4' => 'Heading 4',
                                                'h5' => 'Heading 5',
                                                'h6' => 'Heading 6',
                                            ])->required(),
                                    ])->columns(2),

                                Block::make('paragraph')
                                    ->schema([
                                        RichEditor::make('content')->label('Paragraph')->required(),
                                    ]),

                                Block::make('image')
                                    ->schema([
                                        FileUpload::make('url')
                                            ->label('Image')
                                            ->image()
                                            ->directory('articles/blocks')
                                            ->imageEditor()
                                            ->helperText('Unggah gambar untuk blok.'),
                                        TextInput::make('alt')->label('Alt text')->maxLength(200),
                                    ]),
                            ])
                            ->default(function () use ($isDebug) {
                                if (! $isDebug) {
                                    return null;
                                }

                                return [
                                    [
                                        'type' => 'heading',
                                        'data' => [
                                            'content' => fake()->sentence(5),
                                            'level' => 'h2',
                                        ],
                                    ],
                                    [
                                        'type' => 'paragraph',
                                        'data' => [
                                            'content' => '<p>'.nl2br(e(fake()->paragraphs(3, true))).'</p>',
                                        ],
                                    ],
                                ];
                            }),

                        Grid::make(2)->schema([
                            FileUpload::make('cover_url')
                                ->label('Cover / OG Image')
                                ->image()
                                ->directory('articles/covers')
                                ->disk('public')
                                ->imageEditor()
                                ->imageResizeMode('cover')
                                ->imageResizeTargetWidth('1200')
                                ->imageResizeTargetHeight('630')
                                ->helperText('Disarankan 1200×630px (rasio 1.91:1) untuk preview sosial.'),

                            TextInput::make('cover_alt')
                                ->label('Alt text gambar (SEO)')
                                ->maxLength(200)
                                ->default(fn () => $isDebug ? fake()->sentence(6) : null)
                                ->helperText('Deskripsikan gambar dengan ringkas & relevan.'),
                        ]),
                    ])->columnSpanFull(),

                Section::make('Klasifikasi')
                    ->schema([
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => $isDebug ? ArticleCategory::query()->value('id') : null)
                            ->createOptionForm([
                                Section::make('Informasi Kategori')
                                    ->description('Nama, slug, dan deskripsi kategori.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('name')
                                                ->label('Nama Kategori')
                                                ->required()
                                                ->maxLength(120)
                                                ->default(fn () => $isDebug ? fake()->unique()->word() : null)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (callable $set, ?string $state, callable $get) {
                                                    if (blank($get('slug')) && filled($state)) {
                                                        $set('slug', Str::slug($state));
                                                    }
                                                    if (blank($get('seo_title')) && filled($state)) {
                                                        $set('seo_title', Str::limit($state, 60, ''));
                                                    }
                                                }),

                                            TextInput::make('slug')
                                                ->label('Slug')
                                                ->required()
                                                ->maxLength(160)
                                                ->default(fn () => $isDebug ? Str::slug(fake()->unique()->word()) : null)
                                                ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'])
                                                ->rule(fn (?ArticleCategory $record) => Rule::unique('article_categories', 'slug')->ignore($record?->id)
                                                )
                                                ->helperText('Huruf kecil, pakai tanda hubung. Contoh: minuman-kecantikan')
                                                ->prefix(url('articles/category').'/'),
                                        ]),

                                        Textarea::make('description')
                                            ->label('Deskripsi')
                                            ->default(fn () => $isDebug ? fake()->sentence(12) : null)
                                            ->rows(4)
                                            ->helperText('Ringkas & relevan. Bisa tampil di halaman kategori.'),

                                        Select::make('parent_id')
                                            ->label('Parent (opsional)')
                                            ->relationship('parent', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->helperText('Biarkan kosong jika ini kategori utama.'),
                                    ]),

                                Section::make('SEO')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('seo_title')
                                                ->label('Meta Title')
                                                ->maxLength(60)
                                                ->default(fn () => $isDebug ? Str::limit(fake()->unique()->word(), 60, '') : null)
                                                ->helperText('Ideal 50–60 karakter. Jika kosong, fallback ke Nama Kategori.'),

                                            Textarea::make('meta_description')
                                                ->label('Meta Description')
                                                ->rows(2)
                                                ->default(fn () => $isDebug ? Str::limit(fake()->paragraph(), 160) : null)
                                                ->maxLength(160)
                                                ->helperText('Ideal 150–160 karakter.'),
                                        ]),

                                        TagsInput::make('meta_keywords')
                                            ->label('Meta Keywords (opsional)')
                                            ->default(fn () => $isDebug ? ['matcha', 'collagen', 'minuman', 'kecantikan'] : null)
                                            ->placeholder('Tambah keyword…')
                                            ->helperText('Tidak wajib. Hindari keyword stuffing.'),
                                    ]),

                                Section::make('Status & Urutan')
                                    ->schema([
                                        Toggle::make('is_active')
                                            ->label('Aktif')
                                            ->default(true),

                                        TextInput::make('position')
                                            ->label('Urutan')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->helperText('Angka kecil tampil lebih dulu.'),
                                    ]),
                            ]),

                        TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Tambah tag')
                            ->default(fn () => $isDebug ? collect(fake()->unique()->words(4))->take(4)->values()->all() : null)
                            ->helperText('Maks 5–8 tag yang relevan.'),
                    ]),

                Section::make('SEO')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('seo_title')
                                ->label('Meta Title')
                                ->maxLength(60)
                                ->helperText('Ideal 50–60 karakter. Jika kosong, akan fallback ke Judul.')
                                ->default(fn () => $isDebug
                                    ? Str::limit(fake()->sentence(6), 60, '')
                                    : null),

                            TextInput::make('canonical_url')
                                ->label('Canonical URL')
                                ->url()
                                ->default(fn () => $isDebug ? url('articles/'.Str::slug(fake()->unique()->sentence(3))) : null)
                                ->helperText('Kosongkan untuk pakai URL artikel otomatis.'),
                        ]),

                        Textarea::make('meta_description')
                            ->label('Meta Description')
                            ->rows(3)
                            ->maxLength(160)
                            ->default(fn () => $isDebug
                                ? Str::limit(fake()->paragraph(), 160)
                                : null)
                            ->helperText('Ideal 150–160 karakter. Jika kosong, fallback ke Ringkasan.'),

                        TagsInput::make('meta_keywords')
                            ->label('Meta Keywords (opsional)')
                            ->default(fn () => $isDebug ? ['matcha', 'collagen', 'minuman', 'kecantikan'] : null)
                            ->helperText('Tidak wajib. Fokus pada konten & internal linking lebih penting.'),

                        Grid::make(2)->schema([
                            Toggle::make('noindex')->label('Noindex')->default(false),
                            Toggle::make('nofollow')->label('Nofollow')->default(false),
                        ]),
                    ]),

                Section::make('Publikasi')
                    ->schema([
                        ToggleButtons::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'scheduled' => 'Terjadwal',
                                'published' => 'Terbit',
                            ])
                            ->colors([
                                'draft' => 'gray',
                                'scheduled' => 'warning',
                                'published' => 'success',
                            ])
                            ->icons([
                                'draft' => 'heroicon-o-pencil',
                                'scheduled' => 'heroicon-o-clock',
                                'published' => 'heroicon-o-check-badge',
                            ])
                            ->inline()
                            ->required()
                            ->default('draft'),

                        DateTimePicker::make('published_at')
                            ->label('Waktu Terbit')
                            ->seconds(false)
                            ->native(false)
                            ->default(fn () => $isDebug ? now() : null)
                            ->helperText('Wajib jika status Terbit/Terjadwal.')
                            ->required(fn (callable $get): bool => in_array($get('status'), ['published', 'scheduled'])),

                        Select::make('author_id')
                            ->label('Penulis')
                            ->relationship('author', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => $isDebug ? (\App\Models\User::query()->value('id') ?? auth()->id()) : null),
                    ]),
            ]);
    }
}
