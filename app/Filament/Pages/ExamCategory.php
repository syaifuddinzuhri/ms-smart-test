<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ExamCategory extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Manajemen Ujian';

    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.pages.exam-category';

    protected static bool $shouldRegisterNavigation = false;
}
