<?php

namespace App\Filament\Resources\RpzResource\Pages;

use App\Filament\Resources\RpzResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRpz extends ViewRecord
{
    protected static string $resource = RpzResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
