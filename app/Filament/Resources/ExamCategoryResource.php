<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamCategoryResource\Pages;
use App\Models\ExamCategory;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class ExamCategoryResource extends Resource
{
    protected static ?string $model = ExamCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Kategori Ujian';

    protected static ?string $modelLabel = 'Kategori Ujian';
    protected static ?string $pluralModelLabel = 'Daftar Kategori Ujian';

    protected static ?string $navigationGroup = 'Manajemen Ujian';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('academic_year_id')
                            ->relationship('academicYear', 'name')
                            ->label('Tahun Ajaran')
                            ->live(onBlur: true)
                            ->reactive()
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: function (Unique $rule, $get) {
                                    return $rule->where('academic_year_id', $get('academic_year_id'));
                                }
                            )
                            ->validationMessages([
                                'unique' => 'Kategori dengan nama ini sudah ada di tahun ajaran tersebut.',
                            ])
                            ->label('Nama Kategori')
                            ->placeholder('Contoh: Ujian Tengah Semester')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academicYear.name')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Hanya Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->modalWidth('md'),
                Tables\Actions\DeleteAction::make()
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
            'index' => Pages\ListExamCategories::route('/'),
        ];
    }
}
