<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\StudentResource\Pages;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static ?string $navigationLabel = 'Peserta';
    protected static ?string $modelLabel = 'Peserta';
    protected static ?string $pluralModelLabel = 'Daftar Peserta';
    protected static ?string $navigationGroup = 'Manajemen Peserta';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // BAGIAN 1: DATA AKUN (Model User)
                Section::make('Akun Login')
                    ->description('Data untuk login siswa ke sistem.')
                    ->schema([
                        TextInput::make('user_name')
                            ->label('Nama Lengkap')
                            ->required(),

                        TextInput::make('user_username')
                            ->label('Username')
                            ->required()
                            ->unique(table: \App\Models\User::class, column: 'username', ignorable: fn($record) => $record?->user)
                            ->extraInputAttributes(['style' => 'text-transform: lowercase'])
                            ->extraAttributes([
                                'x-on:input' => "\$el.querySelector('input').value = \$el.querySelector('input').value.toLowerCase().replace(/[^a-z0-9]/g, '')",
                            ])
                            ->hint('Gunakan huruf kecil & angka saja')
                            ->hintColor('primary')
                            ->regex('/^[a-z0-9]+$/')
                            ->validationMessages([
                                'regex' => 'Username hanya boleh berisi huruf kecil dan angka tanpa spasi.',
                            ]),

                        TextInput::make('user_password')
                            ->label('Password')
                            ->password()
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create')
                            ->placeholder(fn($context) => $context === 'edit' ? 'Kosongkan jika tidak ingin mengubah' : ''),

                        TextInput::make('user_email')
                            ->label('Email')
                            ->email(),
                        // Hidden role agar otomatis tersimpan sebagai STUDENT
                        Forms\Components\Hidden::make('user_role')
                            ->default(UserRole::STUDENT->value),
                    ])->columns(2),

                // BAGIAN 2: BIODATA (Model Student)
                Section::make('Biodata Siswa')
                    ->description('Lengkapi detail informasi siswa.')
                    ->schema([
                        Select::make('classroom_id')
                            ->label('Kelas')
                            ->relationship(
                                name: 'classroom',
                                titleAttribute: 'name',
                                // Eager load relasi major agar query efisien
                                modifyQueryUsing: fn($query) => $query->with('major')
                            )
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} — " . ($record->major?->name ?? 'Umum'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('nisn')
                            ->label('NISN')
                            ->numeric()
                            ->unique(ignoreRecord: true)
                            ->required(),

                        TextInput::make('pob')
                            ->label('Tempat Lahir'),

                        DatePicker::make('dob')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])->columns(2),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['user', 'classroom.major']))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.username')
                    ->label('Username')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas/Jurusan')
                    ->formatStateUsing(function ($record) {
                        $classroom = $record->classroom;
                        $major = $classroom?->major?->name;
                        $label = $classroom?->name;
                        if ($major) {
                            $label .= " - {$major}";
                        }
                        return $label;
                    })
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('nisn')
                    ->label('NISN')
                    ->sortable()
                    ->copyable()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('classroom_id')
                    ->label('Kelas')
                    ->relationship('classroom', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('major')
                    ->label('Jurusan')
                    ->relationship('classroom.major', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
