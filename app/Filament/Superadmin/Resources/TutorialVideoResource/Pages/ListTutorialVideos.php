<?php

namespace App\Filament\Superadmin\Resources\TutorialVideoResource\Pages;

use App\Filament\Superadmin\Resources\TutorialVideoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTutorialVideos extends ListRecords
{
    protected static string $resource = TutorialVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
