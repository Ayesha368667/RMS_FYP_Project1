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

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'data.current_password' => 'The current password is incorrect.',
            ]);
        }

        // >>> If A has shared change-password logic (a service or action class),
        // >>> call it here instead of the next 2 lines.
        $user->password = Hash::make($data['password']);
        $user->save();

        // >>> If A's EnsurePasswordChanged middleware uses a flag column
        // >>> (for example must_change_password), reset it here too:
        // >>> $user->forceFill(['must_change_password' => false])->save();

        // keep the current session logged in after the password changed
        session()->put([
            'password_hash_'.Filament::getAuthGuard() => $user->getAuthPassword(),
        ]);

        $this->form->fill();

        Notification::make()
            ->title('Password changed successfully')
            ->success()
            ->send();
    }
}
