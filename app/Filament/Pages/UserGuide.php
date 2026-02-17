<?php

namespace App\Filament\Pages;

use App\Models\TutorialVideo;
use Filament\Pages\Page;

class UserGuide extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Guide d\'utilisation';
    protected static ?string $title = 'Guide d\'utilisation';
    protected static ?int $navigationSort = 100;
    protected static ?string $slug = 'guide';

    protected static string $view = 'filament.pages.user-guide';

    public string $activeSection = 'overview';

    public function setSection(string $section): void
    {
        $this->activeSection = $section;
    }

    public function getSections(): array
    {
        return [
            'overview' => ['icon' => 'heroicon-o-home', 'label' => 'Vue d\'ensemble'],
            'sales' => ['icon' => 'heroicon-o-shopping-cart', 'label' => 'Ventes'],
            'pos' => ['icon' => 'heroicon-o-calculator', 'label' => 'Point de Vente (Caisse)'],
            'stock' => ['icon' => 'heroicon-o-cube', 'label' => 'Stocks & Achats'],
            'accounting' => ['icon' => 'heroicon-o-banknotes', 'label' => 'Comptabilité'],
            'hr' => ['icon' => 'heroicon-o-user-group', 'label' => 'Ressources Humaines'],
            'invoicing' => ['icon' => 'heroicon-o-document-text', 'label' => 'Facturation & DGI'],
            'admin' => ['icon' => 'heroicon-o-cog-6-tooth', 'label' => 'Administration'],
        ];
    }

    /**
     * Récupère les vidéos tutoriels pour la section active
     */
    public function getSectionVideos(): \Illuminate\Database\Eloquent\Collection
    {
        return TutorialVideo::active()
            ->forSection($this->activeSection)
            ->ordered()
            ->get();
    }
}
