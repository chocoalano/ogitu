<?php

namespace App\Filament\Admin\Resources\AgeChecks;

use App\Filament\Admin\Resources\AgeChecks\Pages\ManageAgeChecks;
use App\Models\AgeCheck;
use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AgeCheckResource extends Resource
{
    protected static ?string $model = AgeCheck::class;

    protected static string|UnitEnum|null $navigationGroup = 'User & Vendor';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('customer');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // Relasi Customer (bukan angka mentah)
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
                ->helperText('Pilih pelanggan yang diverifikasi usia (18+).'),

            Select::make('method')
                ->label('Metode')
                ->options([
                    'kyc' => 'KYC (dokumen resmi)',
                    'selfie_liveness' => 'Selfie + Liveness',
                    'manual' => 'Manual (admin/CS)',
                ])
                ->required()
                ->native(false)
                ->helperText('Cara verifikasi usia yang digunakan.'),

            Select::make('result')
                ->label('Hasil')
                ->options([
                    'pass' => 'Pass (Lolos 18+)',
                    'fail' => 'Fail (Tidak Lolos)',
                ])
                ->required()
                ->native(false)
                ->helperText('Status lolos/tidaknya verifikasi usia.'),

            DateTimePicker::make('checked_at')
                ->label('Diverifikasi Pada')
                ->seconds(false)
                ->default(now())
                ->required()
                ->helperText('Waktu verifikasi dilakukan.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('customer.customer_code')->label('CST Code'),
            TextEntry::make('customer.name')->label('Customer'),
            TextEntry::make('method')->label('Metode')->badge(),
            TextEntry::make('result')->label('Hasil')->badge(),
            TextEntry::make('checked_at')->label('Waktu')->dateTime(),

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
                TextColumn::make('method')->label('Metode')->badge()->sortable(),
                TextColumn::make('result')->label('Hasil')->badge()->sortable(),
                TextColumn::make('checked_at')->label('Waktu')->dateTime()->sortable(),

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
                SelectFilter::make('method')
                    ->label('Metode')
                    ->options([
                        'kyc' => 'KYC',
                        'selfie_liveness' => 'Selfie + Liveness',
                        'manual' => 'Manual',
                    ]),

                SelectFilter::make('result')
                    ->label('Hasil')
                    ->options(['pass' => 'Pass', 'fail' => 'Fail']),

                Filter::make('checked_range')
                    ->label('Rentang Waktu')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dari'),
                        \Filament\Forms\Components\DatePicker::make('to')->label('Sampai'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        if (! empty($data['from'])) {
                            $q->whereDate('checked_at', '>=', $data['from']);
                        }
                        if (! empty($data['to'])) {
                            $q->whereDate('checked_at', '<=', $data['to']);
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Age Check')
                    ->modalDescription('Menghapus catatan verifikasi usia akan menghilangkan riwayat audit. Lanjutkan?'),
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
            'index' => ManageAgeChecks::route('/'),
        ];
    }
}
