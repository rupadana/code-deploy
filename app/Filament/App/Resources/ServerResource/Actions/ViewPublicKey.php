<?php

namespace App\Filament\App\Resources\ServerResource\Actions;

use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ViewPublicKey extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->modalContent(function (Model $record) {

            $pub = Storage::disk('private')->get($record->ssh_key_name.'.pub');

            return view('view-public-key', compact('pub'));
        });
    }
}
