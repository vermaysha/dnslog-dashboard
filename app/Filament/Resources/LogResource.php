<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LogResource\Pages;
use App\Filament\Resources\LogResource\RelationManagers;
use App\Models\Log;
use Cache;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;


class LogResource extends Resource
{
    protected static ?string $model = Log::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $modelLabel = 'Query Log';

    protected static ?string $pluralModelLabel = 'Query Log';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('class'),
                Forms\Components\TextInput::make('client'),
                Forms\Components\TextInput::make('client_ip'),
                Forms\Components\TextInput::make('client_port'),
                Forms\Components\TextInput::make('date'),
                Forms\Components\TextInput::make('dns_server'),
                Forms\Components\TextInput::make('domain'),
                Forms\Components\TextInput::make('host'),
                Forms\Components\TextInput::make('log_level'),
                Forms\Components\TextInput::make('memory_address'),
                Forms\Components\TextInput::make('note'),
                Forms\Components\TextInput::make('recur'),
                Forms\Components\DatePicker::make('time'),
                Forms\Components\TextInput::make('type'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('request_date', 'desc')
            ->filtersFormColumns([
                'sm' => 1,
                'md' => 2,
                'lg' => 4,
            ])
            ->poll()
            ->striped()
            ->paginated([10, 20, 50, 100, 500])
            ->defaultPaginationPageOption(50)
            ->recordUrl(null)
            ->recordAction(Tables\Actions\ViewAction::class)
            ->columns([
                Tables\Columns\TextColumn::make('note')
                    ->label('DNS Note'),
                Tables\Columns\TextColumn::make('client_ip')
                    ->label('Client IP')
                    ->placeholder('Client IP')
                    ->description(function (Model $record) {
                        return $record?->client;
                    }),
                Tables\Columns\TextColumn::make('client_port'),
                Tables\Columns\TextColumn::make('request_date')
                    ->formatStateUsing(function (Model $record) {
                        $unix = $record->request_date;

                        return Carbon::createFromTimestamp($unix, 'Asia/Jakarta')
                            ->isoFormat('dddd, DD MMMM YYYY HH:mm');
                    }),
                // Tables\Columns\TextColumn::make('request_date')
                //     ->dateTime('l, d F Y H:m')
                //     ->label('Request Date')
                //     ->sortable()
                //     ->searchable(),
                Tables\Columns\TextColumn::make('class')
                    ->label('DNS Class'),
                Tables\Columns\TextColumn::make('host')
                    ->label('DNS Host')
                    ->description(function (Model $record) {
                        return 'Domain: ' . $record->domain;
                    })
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\MultiSelectFilter::make('note')
                    ->label('DNS Note')
                    ->options(function () {
                        return Cache::remember('log_dns_note', 300, function () {
                            $data = Log::select('note')->distinct()->get()->toArray();
                            $result = [];
                            foreach ($data as $row) {
                                $result[$row[0]] = $row[0];
                            }
                            return $result;
                        });
                    })
                    ->native(false)
                    ->multiple(true),
                Tables\Filters\SelectFilter::make('client_ip')
                    ->label('Client IP')
                    ->options(function () {
                        return Cache::remember('log_client_ip', 300, function () {
                            $result = [];
                            $data = Log::select('client_ip')->distinct()->get()->toArray();
                            foreach ($data as $row) {
                                $result[$row[0]] = $row[0];
                            }
                            return $result;
                        });
                    })
                    ->native(false)
                    ->multiple(true),
                // DateRangeFilter::make('request_date')
                //     ->label('Request Date')
                //     ->autoApply()
                //     ->defaultToday()
                //     ->modifyQueryUsing(function (Builder $query, ?Carbon $startDate , ?Carbon $endDate , $dateString) {
                //         if (!empty($startDate) && !empty($endDate)) {
                //             $startUnixTimestamp = (int) $startDate->format('U');
                //             $endUnixTimestamp = (int) $endDate->format('U');
                //             $query->whereBetween('request_date', [$startUnixTimestamp, $endUnixTimestamp]);
                //         }

                //         return $query;
                //     }),
                Tables\Filters\Filter::make('timepicker')
                    ->label('TimePicker')
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                    ])
                    ->columnSpan(2)
                    ->form([
                        Forms\Components\DateTimePicker::make('start')
                            ->label('Mulai')
                            ->placeholder('Mulai pada')
                            ->time()
                            ->format('H:i:s')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->live()
                            ->default(now()->startOfDay()),
                        Forms\Components\DateTimePicker::make('end')
                            ->label('Selesai')
                            ->placeholder('Selesai pada')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->minDate(function (Forms\Get $get) {
                                $start = $get('start');
                                if (!empty($start)) {
                                    return $start;
                                }
                            })->default(now()->endOfDay()),
                    ])
                    ->query(function(Builder $query, array $data) {
                        $start = $data['start'] ? Carbon::parse($data['start']) : null;
                        $end = $data['end'] ? Carbon::parse($data['end']) : null;

                        if (!empty($start) && !empty($end)) {
                            $query->whereBetween('request_date', [(int) $start->format('U'), (int) $end->format('U')]);
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data) {
                        $start = $data['start'] ? Carbon::parse($data['start']) : null;
                        $end = $data['end'] ? Carbon::parse($data['end']) : ($start ? $start->endOfDay() : null);

                        if (empty($start) || empty($end)) {
                            return null;
                        }

                        return 'Request Date: ' . $start->format('Y-m-d H:i:s') .' - '. $end->format('Y-m-d H:i:s');
                    }),
            ], FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    // Tables\Actions\EditAction::make(),
                ]),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLogs::route('/'),
            'create' => Pages\CreateLog::route('/create'),
            'view' => Pages\ViewLog::route('/{record}'),
            'edit' => Pages\EditLog::route('/{record}/edit'),
        ];
    }
}
