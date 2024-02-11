<div class="pt-32 md:py-12  m-auto px-6 md:px-12">
    <div aria-hidden="true"
        class="absolute inset-0 my-auto w-96 h-32 rotate-45 bg-gradient-to-r from-primaryLight to-secondaryLight blur-3xl opacity-50 dark:opacity-20">
    </div>
    <div class="relative lg:flex lg:items-center lg:gap-12">
        <div class="text-center lg:text-left md:mt-12 lg:mt-0 sm:w-10/12 md:w-2/3 sm:mx-auto lg:mr-auto ">
            <h1 class="text-gray-900 font-bold text-4xl md:text-6xl lg:text-5xl xl:text-6xl dark:text-white">Get Started
                Today<span class="text-primary dark:text-primaryLight"></span></h1>
            <p class="mt-8 text-gray-600 dark:text-gray-300">Ready to revolutionize your deployment process? Start
                deploying with ease using CodeDeploy. Join our waiting list now and experience the future of web
                deployment.</p>

            <div>
                <form action="" class="w-full mt-12" wire:submit="submit">
                    <x-filament::input.wrapper prefix-icon="heroicon-m-at-symbol" :valid="!$errors->has('name')">
                        <x-filament::input type="text" wire:model="email" />
                    </x-filament::input.wrapper>

                    <x-filament::button class="mt-2" type="submit">
                        Join now
                    </x-filament::button>
                </form>
            </div>

            <p class="mt-8 text-gray-600 dark:text-gray-300">
                CodeDeploy is an Opensource Project. <a style="color:blue" href="mailto:rupadanawayan@gmail.com">Contact me</a> if you want to contribute. Stay tuned for public repository!
            </p>
            
            <div class="overflow-hidden w-full lg:w-7/12 lg:-mr-16">
                <img src="{{ url('images/deploy-illustration.svg') }}" alt="project illustration" height=""
                    width="">
            </div>
        </div>
    </div>
</div>
