<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Radio;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Settings';

    protected string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'active_machine' => Setting::get('active_machine', 'both'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Agent Execution')
                    ->description('Controls which machine\'s OpenClaw agents are allowed to claim and work on items. Trigger-scripts on the excluded machine check this before firing.')
                    ->schema([
                        Radio::make('active_machine')
                            ->label('Active machine')
                            ->options([
                                'both' => 'Both -- Mac and VPS agents both run',
                                'mac' => 'Mac only -- VPS agents stand down',
                                'vps' => 'VPS only -- Mac agents stand down',
                            ])
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::set('active_machine', $state['active_machine']);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
