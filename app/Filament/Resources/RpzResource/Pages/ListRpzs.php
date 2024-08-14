<?php

namespace App\Filament\Resources\RpzResource\Pages;

use App\Filament\Resources\RpzResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRpzs extends ListRecords
{
    protected static string $resource = RpzResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
