<x-filament-panels::page>
    {{-- <x-pulse> --}}
        <livewire:pulse.servers cols="full" />

        <livewire:pulse.usage cols="4" rows="2" />

        <livewire:pulse.queues cols="4" />

        <livewire:pulse.cache cols="4" />

        <livewire:pulse.slow-queries cols="8" />

        <livewire:pulse.exceptions cols="6" />

        <livewire:pulse.slow-requests cols="6" />

        <livewire:pulse.slow-jobs cols="6" />

        <livewire:pulse.slow-outgoing-requests cols="6" />
    <div>
        {{-- {!! Laravel\Pulse\Facades\Pulse::css() !!}
        {!! Laravel\Pulse\Facades\Pulse::js() !!} --}}
    </div>
    <div class="mx-auto grid default:grid-cols-12 default:gap-6 container">
        

    </div>
    {{-- </x-pulse> --}}
</x-filament-panels::page>
