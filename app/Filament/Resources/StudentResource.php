<?php

namespace App\Filament\Resources;

use App\Enums\GenderType;
use App\Enums\UserRole;
use App\Filament\Resources\StudentResource\Pages;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static ?string $navigationLabel = 'Peserta';
    protected static ?string $modelLabel = 'Peserta';
    protected static ?string $pluralModelLabel = 'Daftar Peserta';
    protected static ?string $navigationGroup = 'Manajemen Peserta';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()?->role !== UserRole::TEACHER;
    }

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
                            ->relationship('classroom', 'name')
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

                        Select::make('gender')
                            ->label('Jenis Kelamain')
                            ->options(GenderType::class)
                            ->live()
                            ->required(),
                    ])->columns(2),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['user', 'classroom']))
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
                    ->label('Kelas')
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
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('resetSession')
                        ->label('Reset Sesi')
                        ->icon('heroicon-m-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Reset Sesi Peserta?')
                        ->modalDescription('Tindakan ini akan mengeluarkan peserta dari perangkat yang sedang aktif agar bisa login kembali.')
                        ->modalSubmitActionLabel('Ya, Reset Sesi')
                        ->action(function ($record) {
                            if ($record->user) {
                                DB::table('sessions')
                                    ->where('user_id', $record->user->id)
                                    ->delete();

                                $record->user->tokens()->delete();

                                Notification::make()
                                    ->title('Sesi ' . $record->user->name . ' berhasil direset')
                                    ->success()
                                    ->send();
                            }
                        }),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size(ActionSize::Small)
                    ->color('gray')
                    ->button(),
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
