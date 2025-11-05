<?php

namespace App\Filament\Admin\Resources\Vendors;

use App\Filament\Admin\Resources\Vendors\Pages\ManageVendors;
use App\Models\Customer;
use App\Models\Vendor;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use UnitEnum;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static string|UnitEnum|null $navigationGroup = 'User & Vendor';

    public static function getEloquentQuery(): Builder
    {
        // eager untuk customer + hitung shops (menambah kolom shops_count)
        return parent::getEloquentQuery()
            ->with('customer')
            ->withCount('shops');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')
                ->label('Customer Pemilik')
                ->searchable()
                ->options(fn () => Customer::query()
                    ->orderBy('name')
                    ->limit(1000)
                    ->get()
                    ->mapWithKeys(fn (Customer $c) => [$c->id => "{$c->name} ({$c->email})"])
                    ->toArray()
                )
                ->required()
                ->helperText('Pilih akun customer yang akan menjadi pemilik vendor/toko.')
                // Unik per customer (1 customer hanya boleh 1 vendor)
                ->rules([
                    fn (Get $get) => Rule::unique('vendors', 'customer_id')
                        ->ignore(request()->route('record')), // abaikan saat edit
                ]),

            TextInput::make('company_name')
                ->label('Nama Perusahaan / Brand')
                ->required()
                ->maxLength(150)
                ->helperText('Nama legal/brand vendor yang tampil di admin & laporan.'),

            TextInput::make('npwp')
                ->label('NPWP (opsional)')
                ->maxLength(25)
                ->rule('regex:/^[0-9.\- ]*$/')
                ->helperText('Format angka & tanda titik/strip diperbolehkan. Contoh: 12.345.678.9-012.345'),

            TextInput::make('phone')
                ->label('Nomor Telepon (opsional)')
                ->tel()
                ->maxLength(25)
                ->helperText('Nomor kontak vendor. Disarankan nomor WhatsApp yang aktif.'),

            Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Pending',
                    'active' => 'Active',
                    'suspended' => 'Suspended',
                ])
                ->default('pending')
                ->required()
                ->native(false)
                ->helperText('Pending: menunggu verifikasi. Active: dapat mengelola toko. Suspended: dibekukan.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('customer.name')
                ->label('Customer'),
            TextEntry::make('customer.email')
                ->label('Email'),
            TextEntry::make('company_name')->label('Perusahaan/Brand'),
            TextEntry::make('npwp')->label('NPWP')->placeholder('-'),
            TextEntry::make('phone')->label('Telepon')->placeholder('-'),
            TextEntry::make('status')->label('Status')->badge(),
            TextEntry::make('shops_count')->label('Jumlah Shop'),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('customer.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('company_name')
                    ->label('Perusahaan/Brand')
                    ->searchable(),
                TextColumn::make('npwp')
                    ->label('NPWP')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('shops_count')
                    ->label('Shops')
                    ->counts('shops') // otomatis hitung relasi shops
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                    ]),
                TernaryFilter::make('has_shops')
                    ->label('Punya Shop?')
                    ->queries(
                        true: fn (Builder $q) => $q->has('shops'),
                        false: fn (Builder $q) => $q->doesntHave('shops'),
                        blank: fn (Builder $q) => $q
                    )
                    ->placeholder('Semua'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                // Aksi cepat approve/suspend
                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheck)
                    ->requiresConfirmation()
                    ->visible(fn (Vendor $record) => $record->status !== 'active')
                    ->action(fn (Vendor $record) => $record->update(['status' => 'active'])),

                Action::make('suspend')
                    ->label('Suspend')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedCheck)
                    ->requiresConfirmation()
                    ->visible(fn (Vendor $record) => $record->status !== 'suspended')
                    ->action(fn (Vendor $record) => $record->update(['status' => 'suspended'])),

                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Vendor')
                    ->modalDescription('Hapus vendor hanya jika benar-benar diperlukan. Pastikan tidak ada shop/riwayat penting.'),
            ])
            ->headerActions([
                // Create via Manage page (default)
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Massal Vendor')
                        ->modalDescription('Pastikan tidak ada shop terkait sebelum menghapus massal.'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVendors::route('/'),
        ];
    }
}
