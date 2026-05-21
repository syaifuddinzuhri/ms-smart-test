<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\ClassroomResource\Pages;
use App\Models\Classroom;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClassroomResource extends Resource
{
    protected static ?string $model = Classroom::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Kelas';

    protected static ?string $modelLabel = 'Kelas';
    protected static ?string $pluralModelLabel = 'Daftar Kelas';

    protected static ?string $navigationGroup = 'Manajemen Peserta';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->role !== UserRole::TEACHER;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->schema([
                        TextInput::make('name')
                            ->label('Nama Kelas')
                            ->placeholder('Contoh: XI MIPA')
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn($state, $set) => $set('code', strtoupper(str_replace(' ', '-', $state)))),

                        TextInput::make('code')
                            ->label('Kode Kelas')
                            ->required()
                            ->readOnly()
                            ->dehydrated()
                            ->unique(ignoreRecord: true),

                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                    ])
                    ->columns(1)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->label('Nama Kelas')
                ->searchable(),
            TextColumn::make('code')
                ->label('Kode')
                ->searchable(),
            IconColumn::make('is_active')
                ->label('Aktif')
                ->boolean(),
        ])
            ->actions([
                Tables\Actions\EditAction::make()->modalWidth('md'),
                Tables\Actions\DeleteAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassrooms::route('/'),
        ];
    }
}
