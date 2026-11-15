<?php

namespace App\Filament\Admin\Resources\FollowupForms;
use App\Models\FollowupForm;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class FollowupFormResource extends Resource
{
    protected static ?string $model = FollowupForm::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Follow-up Forms';

    protected static ?string $recordTitleAttribute = 'form_name';

    // Shows the locked core questions as a list
    private static function coreList(): HtmlString
    {
        $items = collect(FollowupForm::CORE_QUESTIONS)
            ->map(fn ($q) => '<li>'.e($q['label_en']).' — <span dir="rtl">'.e($q['label_ur']).'</span></li>')
            ->implode('');

        return new HtmlString('<ul style="list-style:disc;padding-inline-start:1.25rem">'.$items.'</ul>');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Form details')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('form_name')->label('Form name')->required()->maxLength(150),
                    Textarea::make('description')->label('Description')->rows(2),
                ]),

            Section::make('Core questions (locked - cannot be changed)')
                ->description(self::coreList())
                ->columnSpanFull()
                ->collapsible()
                ->schema([]),

            Section::make('Extra questions (English + Urdu)')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('extra_questions')
                        ->label('Extra questions')
                        ->defaultItems(0)
                        ->addActionLabel('Add question')
                        ->reorderable()
                        ->collapsible()
                        ->schema([
                            Hidden::make('key'),
                            TextInput::make('label_en')->label('Question (English)')->required(),
                            TextInput::make('label_ur')->label('Question (Urdu)')->required()
                                ->extraInputAttributes(['dir' => 'rtl']),
                            Select::make('type')->label('Answer type')->required()->default('boolean')
                                ->options([
                                    'boolean'  => 'Yes / No',
                                    'number'   => 'Number (0-10)',
                                    'text'     => 'Short text',
                                    'textarea' => 'Long text',
                                ]),
                            Toggle::make('required')->label('Required')->default(false),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('form_name')->label('Form')->searchable()->sortable(),
                TextColumn::make('version_number')->label('Version')->badge()->prefix('v')->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('creator.name')->label('Created by'),
                TextColumn::make('updated_at')->label('Last updated')->since()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (FollowupForm $record) => ! $record->is_active)
                    ->requiresConfirmation()
                    ->modalDescription('This will switch off the form that is active now.')
                    ->action(function (FollowupForm $record) {
                        DB::transaction(function () use ($record) {
                            FollowupForm::where('id', '!=', $record->id)->update(['is_active' => false]);
                            $record->update(['is_active' => true]);
                        });
                    }),

                Action::make('disable')
                    ->label('Disable')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (FollowupForm $record) => $record->is_active)
                    ->requiresConfirmation()
                    ->modalDescription('Patients will have no active form until you activate another one.')
                    ->action(fn (FollowupForm $record) => $record->update(['is_active' => false])),

                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFollowupForms::route('/'),
            'create' => Pages\CreateFollowupForm::route('/create'),
            'edit'   => Pages\EditFollowupForm::route('/{record}/edit'),
        ];
    }
}