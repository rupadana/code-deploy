<?php

namespace App\Livewire;

use Livewire\Component;

class HelloWorld extends Component
{
    public function render()
    {
        return view('livewire.hello-world');
    }

    public function fetch() {
        $this->dispatch('hello', 'hello', 'sd');
    }
}
