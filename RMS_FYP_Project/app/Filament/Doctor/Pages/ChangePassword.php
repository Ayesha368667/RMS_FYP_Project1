<?php

namespace App\Filament\Doctor\Pages;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class ChangePassword extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Account';

    protected static ?string $title = 'Change password';

    protected string $view = 'filament.doctor.pages.change-password';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('current_password')
                    ->label('Current password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->autocomplete('current-password'),

                TextInput::make('password')
                    ->label('New password')
                    ->password()
                    ->revealable()
                    ->required()
                    // use the same rule A uses for passwords, if it is different
                    ->rule(Password::min(8)->letters()->numbers())
                    ->different('current_password')
                    ->same('passwordConfirmation')
                    ->autocomplete('new-password'),

                TextInput::make('passwordConfirmation')
                    ->label('Confirm new password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->dehydrated(false)
                    ->autocomplete('new-password'),
            ])
            ->statePath('data');
    }
    public function save()
    {
        $data = $this->form->getState();
        $user = Auth::user();

        // Current password check karna
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'data.current_password' => 'The current password is incorrect.',
            ]);
        }

        // A ki password reuse policy check
        if (method_exists($user, 'isOldPassword') && $user->isOldPassword($data['password'])) {
            throw ValidationException::withMessages([
                'data.password' => 'You cannot reuse your current or previous password.',
            ]);
        }

        // A ki logic ke mutabiq previous password save karna aur flag false karna
        $user->previous_password = $user->password;
        $user->password = Hash::make($data['password']);
        $user->must_change_password = false;
        $user->save();

        // Session update taaki password change hone ke baad session expire na ho
        session()->put([
            'password_hash_'.Filament::getAuthGuard() => $user->getAuthPassword(),
        ]);

        $this->form->fill();

        Notification::make()
            ->title('Password changed successfully')
            ->success()
            ->send();

        // Doctor dashboard par redirect karna
        return redirect()->to('/doctor');
    }

}