<?php

namespace App\Filament\Admin\Resources\Customers;

use App\Filament\Admin\Resources\Customers\Pages\ManageCustomers;
use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|UnitEnum|null $navigationGroup = 'User & Vendor';

    public static function getEloquentQuery(): Builder
    {
        // Tampilkan konteks audit: jumlah KYC & Wallet (butuh relasi di model Customer)
        return parent::getEloquentQuery()
            ->withCount(['kyc_profiles', 'wallet_accounts']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(12)->schema([
                TextInput::make('customer_code')
                    ->label('Customer Code')
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpan(4)
                    ->helperText('Otomatis dibuat. Format contoh: CST-00001.'),

                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(150)
                    ->columnSpan(8)
                    ->helperText('Nama yang akan tampil di invoice & pengiriman.'),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(150)
                    ->columnSpan(6)
                    ->helperText('Gunakan email aktif. Email harus unik.'),

                TextInput::make('phone')
                    ->label('No. HP')
                    ->tel()
                    ->maxLength(30)
                    ->columnSpan(6)
                    ->helperText('Contoh: 08xxxx atau +62xxxx.'),

                DatePicker::make('dob')
                    ->label('Tanggal Lahir')
                    ->native(false)
                    ->columnSpan(4)
                    ->helperText('Diperlukan untuk verifikasi usia (produk vape 18+).'),

                // Setel Password Baru (akan mengisi kolom password_hash)
                TextInput::make('password')
                    ->label('Setel Password Baru')
                    ->password()
                    ->revealable()
                    ->maxLength(100)
                    ->statePath('password_hash') // simpan ke kolom password_hash
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->columnSpan(8)
                    ->helperText('Kosongkan jika tidak ingin mengubah password. Disimpan dalam bentuk hash yang aman.'),

                Select::make('status')
                    ->label('Status Akun')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                    ])
                    ->default('active')
                    ->required()
                    ->columnSpan(4)
                    ->helperText('Suspended akan membatasi aktivitas login/transaksi.'),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('customer_code')->label('Customer Code'),
            TextEntry::make('name')->label('Nama'),
            TextEntry::make('email')->label('Email'),
            TextEntry::make('phone')->label('No. HP')->placeholder('-'),
            TextEntry::make('dob')->label('Tanggal Lahir')->date()->placeholder('-'),
            TextEntry::make('status')->label('Status')->badge(),
            TextEntry::make('kyc_profiles_count')->label('Jumlah KYC'),
            TextEntry::make('wallet_accounts_count')->label('Jumlah Wallet'),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_code')->label('Code')->searchable()->sortable(),
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('phone')->label('No. HP')->searchable()->toggleable(),
                TextColumn::make('dob')->label('DOB')->date()->sortable()->toggleable(),

                TextColumn::make('kyc_profiles_count')
                    ->label('KYC')
                    ->sortable()
                    ->tooltip('Jumlah profil KYC yang dimiliki'),

                TextColumn::make('wallet_accounts_count')
                    ->label('Wallet')
                    ->sortable()
                    ->tooltip('Jumlah wallet e-money milik customer'),

                TextColumn::make('status')->label('Status')->badge()->sortable(),

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
                    ->options(['active' => 'Active', 'suspended' => 'Suspended']),

                Filter::make('dob_range')
                    ->label('Rentang DOB')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('to')->label('Sampai'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        if (! empty($data['from'])) {
                            $q->whereDate('dob', '>=', $data['from']);
                        }
                        if (! empty($data['to'])) {
                            $q->whereDate('dob', '<=', $data['to']);
                        }
                    }),

                // Sudah punya KYC Verified?
                Tables\Filters\TernaryFilter::make('has_verified_kyc')
                    ->label('KYC Verified?')
                    ->placeholder('Semua')
                    ->queries(
                        true: fn (Builder $q) => $q->whereHas('kycProfiles', fn (Builder $k) => $k->where('status', 'verified')),
                        false: fn (Builder $q) => $q->whereDoesntHave('kycProfiles', fn (Builder $k) => $k->where('status', 'verified')),
                        blank: fn (Builder $q) => $q
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Customer')
                    ->modalDescription('Menghapus customer akan memengaruhi histori dan kepemilikan wallet. Pastikan tindakan ini sesuai kebijakan.'),
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
            'index' => ManageCustomers::route('/'),
        ];
    }
}
