<?php

namespace App\Livewire;

use App\Models\Waitlist;
use Filament\Notifications\Notification;
use Livewire\Component;

class LandingPage extends Component
{
    public $email = '';

    public function render()
    {
        return view('livewire.landing-page');
    }

    public function submit()
    {
        if ($this->email == '') {
            $this->email = '';

            return Notification::make('failed')
                ->danger()
                ->title('Please enter your email!')
                ->send();
        }

        if (! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->email = '';

            return Notification::make('failed')
                ->danger()
                ->title('Please enter a valid email!')
                ->send();
        }

        $check_waitlist = Waitlist::query()->where('email', $this->email)->first();

        if ($check_waitlist) {
            $this->email = '';

            return Notification::make('failed')
                ->info()
                ->title('You already join to our waitlist!')
                ->send();
        }

        Waitlist::create([
            'email' => $this->email,
        ]);

        $this->email = '';

        Notification::make('success')
            ->success()
            ->title('Successfully join to our waitlist!')
            ->send();
    }
}
