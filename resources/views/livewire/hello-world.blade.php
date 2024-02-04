<div x-data="{
    init() {
        Livewire.on('hello', function([data]) {
            console.log(data)
        })
    }
}">
    <x-filament::input.wrapper>
        <x-filament::textarea type="text" />
    </x-filament::input.wrapper>
</div>
