<?php

namespace App\Filament\Resources\RpzResource\Pages;

use App\Filament\Resources\RpzResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRpz extends EditRecord
{
    protected static string $resource = RpzResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
