<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class QuestionCategory extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Manajemen Soal';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.question-category';

    protected static bool $shouldRegisterNavigation = false;
}
