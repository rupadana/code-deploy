<?php

namespace App\Livewire;

use Filament\Pages\Auth\Login;

class LoginPage extends Login
{
    protected static string $view = 'livewire.login-page';

    protected static string $layout = 'components.layouts.app';

    // public function render()
    // {
    //     return view('livewire.login-page');
    // }
}
