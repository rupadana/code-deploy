<?php

namespace App\Filament\App\Resources\ServerResource\Pages;

use App\Filament\App\Resources\ServerResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateServer extends CreateRecord
{
    protected static string $resource = ServerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
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

        $data['ssh_key_name'] = $ssh_key_name;
        if (! isset($data['created_by'])) {
            $data['created_by'] = auth()->user()->id;
        }

        return $data;
    }
}
