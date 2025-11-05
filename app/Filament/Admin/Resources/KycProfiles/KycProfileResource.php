<?php

namespace App\Filament\Admin\Resources\KycProfiles;

use App\Filament\Admin\Resources\KycProfiles\Pages\ManageKycProfiles;
use App\Models\KycProfile;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use UnitEnum;

class KycProfileResource extends Resource
{
    protected static ?string $model = KycProfile::class;

    protected static string|UnitEnum|null $navigationGroup = 'User & Vendor';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('customer');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // CUSTOMER
            Select::make('customer_id')
                ->label('Customer')
                ->relationship('customer', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->helperText('Pilih pemilik profil KYC. Pastikan data Customer (nama, tanggal lahir) sudah benar.'),

            // ID TYPE
            Select::make('id_type')
                ->label('Jenis Identitas')
                ->options([
                    'ktp' => 'KTP',
                    'passport' => 'Passport',
                    'other' => 'Lainnya',
                ])
                ->required()
                ->native(false)
                ->helperText('Jenis dokumen identitas resmi yang digunakan.'),

            // ID NUMBER
            TextInput::make('id_number')
                ->label('Nomor Identitas')
                ->required()
                ->maxLength(100)
                ->reactive()
                ->afterStateUpdated(fn (Set $set, $state) => $set('id_number', trim((string) $state)))
                ->helperText('Contoh: NIK 16 digit, nomor paspor, atau nomor identitas lain yang sah.'),

            // FULL NAME ON ID
            TextInput::make('full_name_on_id')
                ->label('Nama Sesuai Identitas')
                ->required()
                ->maxLength(150)
                ->helperText('Tulis persis seperti yang tertera pada dokumen identitas.'),

            // STATUS
            Select::make('status')
                ->label('Status Verifikasi')
                ->options([
                    'pending' => 'Pending',
                    'verified' => 'Verified',
                    'rejected' => 'Rejected',
                ])
                ->default('pending')
                ->required()
                ->reactive()
                ->helperText('Pending: menunggu pemeriksaan. Verified: lolos KYC. Rejected: ditolak.'),

            // VERIFIED AT (hanya saat status verified)
            DateTimePicker::make('verified_at')
                ->label('Terverifikasi Pada')
                ->seconds(false)
                ->visible(fn (Get $get) => $get('status') === 'verified')
                ->helperText('Wajib diisi saat status = Verified. Kosongkan untuk Pending/Rejected.'),

            // NOTES
            Textarea::make('notes')
                ->label('Catatan (opsional)')
                ->rows(3)
                ->columnSpanFull()
                ->helperText('Catatan internal terkait verifikasi atau alasan penolakan.'),

            // VALIDASI UNIK KOMPOSIT: (id_type, id_number)
            Hidden::make('unique_guard')
                ->dehydrated(false)
                ->rule(function (Get $get) {
                    return Rule::unique('kyc_profiles')
                        ->where(fn ($q) => $q
                            ->where('id_type', (string) $get('id_type'))
                            ->where('id_number', (string) $get('id_number'))
                        )
                        ->ignore(request()->route('record'));
                }),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('customer.name')->label('Customer'),
            TextEntry::make('customer.customer_code')->label('Customer Code')->placeholder('-'),
            TextEntry::make('id_type')->label('Jenis ID')->badge(),
            TextEntry::make('id_number')->label('Nomor ID'),
            TextEntry::make('full_name_on_id')->label('Nama di ID'),
            TextEntry::make('status')->label('Status')->badge(),
            TextEntry::make('verified_at')->label('Terverifikasi')->dateTime()->placeholder('-'),
            TextEntry::make('notes')->label('Catatan')->placeholder('-')->columnSpanFull(),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.customer_code')->label('CST Code')->toggleable(),
                TextColumn::make('customer.name')->label('Customer')->searchable()->sortable(),
                TextColumn::make('id_type')->label('Jenis ID')->badge()->sortable(),
                TextColumn::make('id_number')->label('Nomor ID')->searchable(),
                TextColumn::make('full_name_on_id')->label('Nama di ID')->searchable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('verified_at')->label('Terverifikasi')->dateTime()->sortable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                    ]),
                SelectFilter::make('id_type')
                    ->label('Jenis ID')
                    ->options([
                        'ktp' => 'KTP',
                        'passport' => 'Passport',
                        'other' => 'Lainnya',
                    ]),
                Filter::make('verified_range')
                    ->label('Rentang Verifikasi')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dari'),
                        \Filament\Forms\Components\DatePicker::make('to')->label('Sampai'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        if (! empty($data['from'])) {
                            $q->whereDate('verified_at', '>=', $data['from']);
                        }
                        if (! empty($data['to'])) {
                            $q->whereDate('verified_at', '<=', $data['to']);
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus KYC Profile')
                    ->modalDescription('Menghapus data KYC akan menghilangkan riwayat verifikasi. Pastikan ini memang diperlukan.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Massal KYC')
                        ->modalDescription('Tindakan ini akan menghapus banyak data KYC sekaligus. Pastikan Anda memahami risikonya.'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageKycProfiles::route('/'),
        ];
    }
}
