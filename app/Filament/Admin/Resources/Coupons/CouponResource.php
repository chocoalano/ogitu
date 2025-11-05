<?php

namespace App\Filament\Admin\Resources\Coupons;

use App\Filament\Admin\Resources\Coupons\Pages\ManageCoupons;
use App\Models\Coupon;
use App\Models\Shop;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi & Finansial';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('shop');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(12)->schema([
                TextInput::make('code')
                    ->label('Kode Kupon')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->reactive()
                    ->afterStateUpdated(fn (Set $set, $state) => $set('code', strtoupper(trim((string) $state))))
                    ->helperText('Huruf besar otomatis. Gunakan kode unik (contoh: VAPE10, LIQUID25).')
                    ->columnSpan(4),

                Select::make('type')
                    ->label('Tipe Diskon')
                    ->options(['percent' => 'Percent (%)', 'amount' => 'Amount (nominal)'])
                    ->required()
                    ->native(false)
                    ->reactive()
                    ->helperText('Percent: 1–100. Amount: nominal dalam mata uang toko/platform.')
                    ->columnSpan(4),

                TextInput::make('value')
                    ->label('Nilai Diskon')
                    ->numeric()
                    ->required()
                    ->rule(function (Get $get) {
                        return $get('type') === 'percent'
                            ? ['numeric', 'min:1', 'max:100']
                            : ['numeric', 'min:0.01'];
                    })
                    ->helperText('Percent: 1–100. Amount: minimal 0.01.')
                    ->columnSpan(4),

                Select::make('applies_to')
                    ->label('Berlaku Untuk')
                    ->options(['platform' => 'Platform', 'shop' => 'Shop'])
                    ->default('platform')
                    ->required()
                    ->reactive()
                    ->helperText('Pilih "Platform" untuk semua toko, atau "Shop" untuk kupon khusus toko.')
                    ->columnSpan(4),

                Select::make('shop_id')
                    ->label('Toko (jika Shop)')
                    ->relationship('shop', 'name')
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get) => $get('applies_to') === 'shop')
                    ->visible(fn (Get $get) => $get('applies_to') === 'shop')
                    ->helperText('Wajib diisi jika kupon hanya untuk satu toko.')
                    ->columnSpan(8),

                TextInput::make('min_order')
                    ->label('Min. Order (opsional)')
                    ->numeric()
                    ->rule(['nullable', 'numeric', 'min:0'])
                    ->helperText('Pesanan minimal agar kupon bisa dipakai. Kosongkan jika tidak ada batas.')
                    ->columnSpan(4),

                TextInput::make('max_uses')
                    ->label('Maks. Pemakaian (opsional)')
                    ->numeric()
                    ->rule(['nullable', 'integer', 'min:1'])
                    ->helperText('Batas total penggunaan kupon. Kosongkan jika tak terbatas.')
                    ->columnSpan(4),

                TextInput::make('used')
                    ->label('Sudah Dipakai')
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Diisi oleh sistem saat checkout. Tidak dapat diubah manual.')
                    ->columnSpan(4),

                DateTimePicker::make('starts_at')
                    ->label('Mulai Berlaku')
                    ->seconds(false)
                    ->helperText('Kosongkan untuk aktif segera.')
                    ->columnSpan(6),

                DateTimePicker::make('ends_at')
                    ->label('Berakhir')
                    ->seconds(false)
                    ->rule(['nullable', 'after_or_equal:starts_at'])
                    ->helperText('Tanggal/waktu berakhir. Harus ≥ waktu mulai.')
                    ->columnSpan(6),

                Select::make('status')
                    ->label('Status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                    ->default('active')
                    ->required()
                    ->helperText('Inactive untuk menonaktifkan kupon tanpa menghapus.')
                    ->columnSpan(4),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('code')->label('Kode'),
            TextEntry::make('type')->label('Tipe')->badge(),
            TextEntry::make('value')->label('Nilai'),
            TextEntry::make('applies_to')->label('Scope')->badge(),
            TextEntry::make('shop.name')->label('Toko')->placeholder('-'),
            TextEntry::make('min_order')->label('Min. Order')->placeholder('-'),
            TextEntry::make('max_uses')->label('Maks. Pemakaian')->placeholder('-'),
            TextEntry::make('used')->label('Sudah Dipakai'),
            TextEntry::make('starts_at')->label('Mulai')->dateTime()->placeholder('-'),
            TextEntry::make('ends_at')->label('Berakhir')->dateTime()->placeholder('-'),
            TextEntry::make('status')->label('Status')->badge(),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->sortable(),
                TextColumn::make('type')->label('Tipe')->badge()->sortable(),
                TextColumn::make('value')->label('Nilai')->sortable(),

                TextColumn::make('applies_to')
                    ->label('Scope')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'shop' ? 'Shop' : 'Platform')
                    ->sortable(),

                TextColumn::make('shop.name')
                    ->label('Toko')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('min_order')->label('Min. Order')->sortable()->toggleable(),
                TextColumn::make('used')->label('Used')->sortable(),
                TextColumn::make('max_uses')->label('Max Uses')->sortable()->toggleable(),

                TextColumn::make('usage_ratio')
                    ->label('Usage')
                    ->formatStateUsing(fn ($s, Coupon $r) => $r->max_uses ? "{$r->used} / {$r->max_uses}" : "{$r->used}")
                    ->toggleable(),

                TextColumn::make('starts_at')->label('Mulai')->dateTime()->sortable(),
                TextColumn::make('ends_at')->label('Berakhir')->dateTime()->sortable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),

                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive']),

                SelectFilter::make('applies_to')
                    ->label('Scope')
                    ->options(['platform' => 'Platform', 'shop' => 'Shop']),

                SelectFilter::make('shop_id')
                    ->label('Toko')
                    ->options(fn () => Shop::orderBy('name')->pluck('name', 'id')->toArray()),

                // Kupon yang aktif saat ini: status=active dan (now di dalam range / tidak ada range), serta tidak habis kuota
                Filter::make('currently_active')
                    ->label('Sedang Aktif Sekarang')
                    ->query(function (Builder $q) {
                        $now = now();
                        $q->where('status', 'active')
                            ->where(function (Builder $x) use ($now) {
                                $x->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                            })
                            ->where(function (Builder $x) use ($now) {
                                $x->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                            })
                            ->where(function (Builder $x) {
                                // jika max_uses ada, used < max_uses
                                $x->whereNull('max_uses')->orWhereColumn('used', '<', 'max_uses');
                            });
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Kupon')
                    ->modalDescription('Menghapus kupon akan menonaktifkan penggunaannya pada checkout. Lanjutkan?'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCoupons::route('/'),
        ];
    }
}
