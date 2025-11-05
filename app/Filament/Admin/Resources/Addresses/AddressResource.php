<?php

namespace App\Filament\Admin\Resources\Addresses;

use App\Filament\Admin\Resources\Addresses\Pages\ManageAddresses;
use App\Models\Address;
use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class AddressResource extends Resource
{
    protected static ?string $model = Address::class;

    protected static string|UnitEnum|null $navigationGroup = 'User & Vendor';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('customer');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')
                ->label('Customer')
                ->relationship('customer', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->getOptionLabelUsing(function ($value) {
                    if (! $value) {
                        return '';
                    }
                    $c = Customer::query()->select(['id', 'customer_code', 'name'])->find($value);

                    return $c ? "{$c->customer_code} — {$c->name}" : '';
                })
                ->helperText('Pilih pemilik alamat.'),

            TextInput::make('label')
                ->label('Label (opsional)')
                ->maxLength(60)
                ->placeholder('Rumah / Kantor / Gudang')
                ->helperText('Nama singkat untuk membedakan alamat.'),

            TextInput::make('recipient_name')
                ->label('Nama Penerima')
                ->required()
                ->maxLength(120)
                ->helperText('Nama yang akan muncul pada resi.'),

            TextInput::make('phone')
                ->label('No. HP')
                ->tel()
                ->maxLength(30)
                ->helperText('Contoh: 08xxxx atau +62xxxx.'),

            TextInput::make('line1')
                ->label('Alamat (Baris 1)')
                ->required()
                ->maxLength(180)
                ->helperText('Nama jalan, nomor rumah/gedung.')
                ->columnSpanFull(),

            TextInput::make('line2')
                ->label('Alamat (Baris 2, opsional)')
                ->maxLength(180)
                ->helperText('RT/RW, kompleks, lantai, unit.')
                ->columnSpanFull(),

            TextInput::make('city')
                ->label('Kota/Kabupaten')
                ->required()
                ->maxLength(100),

            TextInput::make('state')
                ->label('Provinsi/State (opsional)')
                ->maxLength(100),

            TextInput::make('postal_code')
                ->label('Kode Pos (opsional)')
                ->maxLength(12)
                ->helperText('Boleh angka/huruf sesuai format negara.'),

            TextInput::make('country_code')
                ->label('Negara (ISO-2)')
                ->default('ID')
                ->required()
                ->maxLength(2)
                ->rule(['alpha:ascii', 'size:2'])
                ->reactive()
                ->afterStateUpdated(fn (Set $set, $state) => $set('country_code', Str::upper(trim((string) $state))))
                ->helperText('Gunakan kode ISO-3166-1 alpha-2. Contoh: ID, SG, MY, AU.'),

            Toggle::make('is_default_shipping')
                ->label('Default Shipping')
                ->default(false)
                ->helperText('Jika aktif, alamat lain milik customer ini akan dicabut status default shipping-nya.'),

            Toggle::make('is_default_billing')
                ->label('Default Billing')
                ->default(false)
                ->helperText('Jika aktif, alamat lain milik customer ini akan dicabut status default billing-nya.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('customer.customer_code')->label('CST Code'),
            TextEntry::make('customer.name')->label('Customer'),
            TextEntry::make('label')->label('Label')->placeholder('-'),
            TextEntry::make('recipient_name')->label('Penerima'),
            TextEntry::make('phone')->label('No. HP')->placeholder('-'),
            TextEntry::make('line1')->label('Alamat 1'),
            TextEntry::make('line2')->label('Alamat 2')->placeholder('-'),
            TextEntry::make('city')->label('Kota'),
            TextEntry::make('state')->label('Prov/State')->placeholder('-'),
            TextEntry::make('postal_code')->label('Kode Pos')->placeholder('-'),
            TextEntry::make('country_code')->label('Negara'),
            IconEntry::make('is_default_shipping')->label('Default Shipping')->boolean(),
            IconEntry::make('is_default_billing')->label('Default Billing')->boolean(),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.customer_code')->label('CST')->sortable(),
                TextColumn::make('customer.name')->label('Customer')->searchable()->sortable(),
                TextColumn::make('label')->label('Label')->searchable()->toggleable(),
                TextColumn::make('recipient_name')->label('Penerima')->searchable(),
                TextColumn::make('phone')->label('No. HP')->searchable()->toggleable(),
                TextColumn::make('line1')->label('Alamat 1')->searchable(),
                TextColumn::make('city')->label('Kota')->searchable(),
                TextColumn::make('state')->label('Prov/State')->searchable()->toggleable(),
                TextColumn::make('postal_code')->label('Kode Pos')->searchable()->toggleable(),
                TextColumn::make('country_code')->label('Negara')->searchable()->sortable(),
                IconColumn::make('is_default_shipping')->label('Ship Def')->boolean(),
                IconColumn::make('is_default_billing')->label('Bill Def')->boolean(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->options(fn () => Customer::orderBy('name')
                        ->get()
                        ->mapWithKeys(fn ($c) => [$c->id => "{$c->customer_code} — {$c->name}"])
                        ->toArray()),

                TernaryFilter::make('default_shipping')
                    ->label('Default Shipping?')
                    ->queries(
                        true: fn (Builder $q) => $q->where('is_default_shipping', true),
                        false: fn (Builder $q) => $q->where('is_default_shipping', false),
                        blank: fn (Builder $q) => $q
                    )
                    ->placeholder('Semua'),

                TernaryFilter::make('default_billing')
                    ->label('Default Billing?')
                    ->queries(
                        true: fn (Builder $q) => $q->where('is_default_billing', true),
                        false: fn (Builder $q) => $q->where('is_default_billing', false),
                        blank: fn (Builder $q) => $q
                    )
                    ->placeholder('Semua'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Alamat')
                    ->modalDescription('Menghapus alamat akan menghilangkan referensinya pada order/checkout. Lanjutkan?'),
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
            'index' => ManageAddresses::route('/'),
        ];
    }
}
