<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Infolists\Components\SshPubView;
use App\Models\Server;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Storage;
use Spatie\Ssh\Ssh;
use Symfony\Component\Process\Process;

class ViewServer extends ViewRecord
{
    protected static string $resource = ServerResource::class;

    // protected static string $view = 'view-server';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('check-connection')
                ->color(Color::Green)
                ->action(function (Server $record) {

                    $host = $record->host;
                    $user = $record->user;

                    $path = storage_path('private/' . $record->ssh_key_name);

                    $process = Ssh::create($user, $host)
                        ->disablePasswordAuthentication()
                        ->enableQuietMode()
                        ->usePrivateKey($path)
                        ->execute("echo 'connection success'");

                    dd($process->isSuccessful(), $process->getErrorOutput(), $process->getOutput());

                    if ($process->isSuccessful()) {
                        return Notification::make('success-notification')
                            ->success()
                            ->title('Connection successfully')
                            ->send();
                    }


                    return Notification::make('failed-notification')
                        ->danger()
                        ->title('Connection failed')
                        ->body($process->getErrorOutput())
                        ->send();
                })
        ];
    }

    protected function hasInfolist(): bool
    {
        return true;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $record = $this->getRecord();
        $data = $record->toArray();


        $pub = Storage::disk('private')->get($record->ssh_key_name . '.pub');

        $data['ssh_pub'] = $pub;

        return $infolist
            ->state($data)
            ->schema([
                Section::make('SSH Key')
                    ->schema([
                        SshPubView::make('ssh_pub')
                            ->label('SSH Public Key'),
                    ])
                    ->description('Make sure this SSH key is present in the /user/.ssh/authorized_keys or /root/.ssh/authorized_keys file')
                    ->columnSpan(1),
                Section::make('Server Detail')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('user'),
                        TextEntry::make('host'),
                        TextEntry::make('ssh_port'),
                    ])->columnSpan(1),

            ]);
    }
}
