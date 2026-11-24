<?php

namespace App\Filament\Doctor\Pages;

use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

/**
 * The doctor's own profile.
 * The doctor can change only: photo, phone, language, availability.
 * Name, email and the professional details are managed by the Admin (shown read-only).
 */
class MyProfile extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Account';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'My profile';

    protected string $view = 'filament.doctor.pages.my-profile';

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->doctor, 403);

        $this->form->fill($this->currentData());
    }

    private function currentData(): array
    {
        $user = auth()->user();
        $doctor = $user->doctor;

        return [
            // editable
            'photo_path' => $doctor->photo_path,
            'phone' => $user->phone,
            'preffered_language' => $user->preffered_language ?: 'en',
            'availability_status' => $doctor->availability_status ?: 'available',

            // read-only (the Admin manages these)
            'name' => $user->name,
            'email' => $user->email,
            'staff_number' => $doctor->staff_number,
            'professional_type' => $doctor->professional_type === 'therapist' ? 'Therapist' : 'Doctor',
            'specialization' => $doctor->specialization,
            'qualification' => $doctor->qualification,
            'license_number' => $doctor->license_number ?: '-',
            'joining_date' => $doctor->joining_date?->format('d M Y'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Photo and contact')
                    ->description('You can change these yourself.')
                    ->columns(2)
                    ->components([
                        // private folder: the photo cannot be opened with a public URL
                        FileUpload::make('photo_path')
                            ->label('Photo')
                            ->image()
                            ->avatar()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk('local')
                            ->directory('doctor-photos')
                            ->visibility('private')
                            ->maxSize(2048)
                            ->columnSpanFull(),

                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(20)
                            ->regex('/^[0-9+\-\s]{7,20}$/')
                            ->validationMessages([
                                'regex' => 'Use only digits, spaces, + and -.',
                            ]),

                        Select::make('preffered_language')
                            ->label('Preferred language')
                            ->options([
                                'en' => 'English',
                                'ur' => 'Urdu',
                            ])
                            ->required(),

                        Select::make('availability_status')
                            ->label('Availability')
                            ->options([
                                'available' => 'Available',
                                'on_leave' => 'On leave',
                                'unavailable' => 'Unavailable',
                            ])
                            ->required(),
                    ]),

                Section::make('Official details')
                    ->description('Managed by the Admin. To change these, please ask the Admin.')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')->label('Full name')->disabled()->dehydrated(false),
                        TextInput::make('email')->label('Email (login)')->disabled()->dehydrated(false),
                        TextInput::make('staff_number')->label('Staff number')->disabled()->dehydrated(false),
                        TextInput::make('professional_type')->label('Type')->disabled()->dehydrated(false),
                        TextInput::make('specialization')->disabled()->dehydrated(false),
                        TextInput::make('qualification')->disabled()->dehydrated(false),
                        TextInput::make('license_number')->label('License number')->disabled()->dehydrated(false),
                        TextInput::make('joining_date')->label('Joining date')->disabled()->dehydrated(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $user = auth()->user();
        $doctor = $user?->doctor;

        abort_unless($doctor, 403);

        $oldPhoto = $doctor->photo_path;
        $newPhoto = $data['photo_path'] ?? null;

        DB::transaction(function () use ($user, $doctor, $data, $newPhoto) {
            $user->update([
                'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
                'preffered_language' => $data['preffered_language'],
            ]);

            $doctor->update([
                'availability_status' => $data['availability_status'],
                'photo_path' => $newPhoto ?: null,
            ]);
        });

        // the old photo file is deleted once a new photo (or no photo) is saved
        if ($oldPhoto && $oldPhoto !== $newPhoto) {
            try {
                Storage::disk('local')->delete($oldPhoto);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        Notification::make()
            ->title('Profile updated')
            ->success()
            ->send();
            $this->redirect(static::getUrl(), navigate: false);
    }
}