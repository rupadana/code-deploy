<?php

namespace App\Filament\App\Resources\ServerResource\Pages;

use App\Filament\App\Resources\ServerResource;
use App\Infolists\Components\SshPubView;
use App\Models\Server;
use App\Services\DeployScript;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Parallax\FilamentSyntaxEntry\SyntaxEntry;

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

                    $process = DeployScript::make($record)
                        ->execute("echo 'connection success'");

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
                }),
            Action::make('recreate-ssh-key')
                ->action(function (Server $record) {
                    $ssh_key_name = str()->uuid()->toString();

                    $rsa = new \phpseclib\Crypt\RSA();
                    $rsa->setPrivateKeyFormat(\phpseclib\Crypt\RSA::PUBLIC_FORMAT_OPENSSH);
                    $rsa->setPublicKeyFormat(\phpseclib\Crypt\RSA::PUBLIC_FORMAT_OPENSSH);
                    $rsa->setComment('deployer@deploy.codecrafters.id');
                    $keys = $rsa->createKey(4096);
                    $publicKey = $keys['publickey'];
                    $privateKey = $keys['privatekey'];

                    Storage::disk('private')->put($ssh_key_name, $privateKey);
                    Storage::disk('private')->put($ssh_key_name.'.pub', $publicKey);

                    $path = storage_path('private/'.$ssh_key_name);

                    exec('chmod 600 '.$path);

                    $record->ssh_key_name = $ssh_key_name;

                    $record->save();

                    return Notification::make('success-notification')
                        ->success()
                        ->title('SSH Key recreated successfully')
                        ->send();
                }),
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

        $pub = Storage::disk('private')->get($record->ssh_key_name.'.pub');

        $data['ssh_pub'] = $pub;
        $data['installation_bash'] = 'curl -sS https://installer.cloudpanel.io/ce/v2/install.sh -o install.sh; echo "85762db0edc00ce19a2cd5496d1627903e6198ad850bbbdefb2ceaa46bd20cbd install.sh" | sha256sum -c && sudo DB_ENGINE=MARIADB_10.11 bash install.sh';

        return $infolist
            ->state($data)
            ->schema([
                Section::make('Installation')
                    ->description(function () {
                        return new HtmlString('Currently this project is Optimized for CloudPanel you can follow <a style="color:blue" href="https://www.cloudpanel.io/docs/v2/getting-started/other" target="_blank">Official Documentation</a>');
                    })
                    ->schema([
                        SyntaxEntry::make('installation_bash')
                            ->label('Installation Script')
                            ->language('bash')
                            ->helperText('This script only for Ubuntu 22.04 with MariaDB 10.11'),
                    ])
                    ->columnSpanFull(),
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
