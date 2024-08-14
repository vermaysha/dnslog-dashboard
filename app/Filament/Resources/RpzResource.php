<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RpzResource\Pages;
use App\Filament\Resources\RpzResource\RelationManagers;
use App\Models\Rpz;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use MongoDB\Laravel\Eloquent\Model;
use HusamTariq\FilamentTimePicker\Forms\Components\TimePickerField;


class RpzResource extends Resource
{
    protected static ?string $model = Rpz::class;

    protected static ?string $navigationIcon = 'heroicon-c-signal-slash';

    protected static ?string $modelLabel = 'RPZ Log';

    protected static ?string $pluralModelLabel = 'RPZ Log';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('action'),
                Forms\Components\TextInput::make('action_class'),
                Forms\Components\TextInput::make('action_dns'),
                Forms\Components\TextInput::make('action_host'),
                Forms\Components\TextInput::make('action_note'),
                Forms\Components\TextInput::make('action_type'),
                Forms\Components\TextInput::make('action_via'),
                Forms\Components\TextInput::make('class'),
                Forms\Components\TextInput::make('client_ip'),
                Forms\Components\TextInput::make('client_port'),
                Forms\Components\TextInput::make('date'),
                Forms\Components\TextInput::make('dns_description'),
                Forms\Components\TextInput::make('domain'),
                Forms\Components\TextInput::make('log_level'),
                Forms\Components\TextInput::make('memory_address'),
                Forms\Components\TextInput::make('policy'),
                Forms\Components\TextInput::make('policy_type'),
                Forms\Components\TextInput::make('request_date')
                    ->numeric(),
                Forms\Components\DatePicker::make('time'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('request_date', 'desc')
            ->poll()
            ->striped()
            ->paginated([10, 20, 50, 100, 500])
            ->defaultPaginationPageOption(50)
            ->filtersFormColumns([
                'sm' => 1,
                'md' => 2,
                'lg' => 4,
            ])
            ->recordUrl(null)
            ->recordAction(Tables\Actions\ViewAction::class)
            ->columns([
                Tables\Columns\TextColumn::make('action_note')
                    ->label('Action Note')
                    ->searchable(),
                Tables\Columns\TextColumn::make('client_ip')
                    ->label('Client IP')
                    ->placeholder('Client IP')
                    ->description(function (Model $record) {
                        return $record?->client;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('client_port')
                    ->label('Port')
                    ->searchable(),
                Tables\Columns\TextColumn::make('action_host')
                    ->label('Request Host')
                    ->placeholder('Request Host')
                    ->description(function (Model $record) {
                        return $record->action . ' to ' . $record->action_via;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('request_date')
                    ->formatStateUsing(function (Model $record) {
                        $unix = $record->request_date;

                        return Carbon::createFromTimestamp($unix, 'Asia/Jakarta')
                            ->isoFormat('dddd, DD MMMM YYYY HH:mm');
                    }),
            ])
            ->filters([
                Tables\Filters\MultiSelectFilter::make('note')
                    ->label('DNS Note')
                    ->options(function () {
                        return Cache::remember('rpz_dns_note', 300, function () {
                            $result = [];
                            $data = Rpz::select('action_note')->distinct()->get()->toArray();
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
                        return Cache::remember('rpz_client_ip', 300, function () {
                            $result = [];
                            $data = Rpz::select('client_ip')->distinct()->get()->toArray();
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
                //     ->showDropdowns()
                //     ->alwaysShowCalendar()
                //     ->autoApply()
                //     ->defaultToday()
                //     // ->timePicker()
                //     // ->timePickerSecond()
                //     // ->timePickerIncrement(1)
                //     // ->timePicker24()
                //     ->linkedCalendars()
                //     ->withIndicator()
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
            ]);;
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
            'index' => Pages\ListRpzs::route('/'),
            'create' => Pages\CreateRpz::route('/create'),
            'view' => Pages\ViewRpz::route('/{record}'),
            'edit' => Pages\EditRpz::route('/{record}/edit'),
        ];
    }
}
