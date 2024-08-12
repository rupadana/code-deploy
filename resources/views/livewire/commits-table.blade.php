@php
    $commits = collect($commits)->splice(0,5)
@endphp
<div>
    <div ax-load x-data="table" class="fi-ta" data-has-alpine-state="true" x-ignore=""
        ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('table', 'filament/tables') }}">
        <x-filament-tables::container>
            <div
                class="fi-ta-ctn divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
                <div
                    class="fi-ta-header-ctn divide-y divide-gray-200 dark:divide-white/10">
                    <!--[if BLOCK]><![endif]-->
                    <div class="fi-ta-header flex flex-col gap-3 p-4 sm:px-6 sm:flex-row sm:items-center">
                        <!--[if BLOCK]><![endif]-->
                        <div class="grid gap-y-1">
                            <!--[if BLOCK]><![endif]-->
                            <h3 class="fi-ta-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                                Deployment History
                            </h3>
        
                        </div>
                    </div>
    
        
                    <div
                        class="fi-ta-header-toolbar flex items-center justify-between gap-x-4 px-4 py-3 sm:px-6"
                        style="display: none;">
                        <div class="flex shrink-0 items-center gap-x-4">
                        </div>
                    </div>
                </div>
        
                <div
                    class="fi-ta-content divide-y divide-gray-200 overflow-x-auto dark:divide-white/10 dark:border-t-white/10">
                    <!--[if BLOCK]><![endif]-->
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                        <!--[if BLOCK]><![endif]-->
                        <thead class="divide-y divide-gray-200 dark:divide-white/5">
        
        
                            <tr class="bg-gray-50 dark:bg-white/5">
                                <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 fi-table-header-cell-domain"
                                    style=";">
                                    <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                                        <span
                                            class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">
                                            Author
                                        </span>
        
        
                                    </span>
                                </th>
                                <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 fi-table-header-cell-domain"
                                    style=";">
                                    <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                                        <span
                                            class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">
                                            Message
                                        </span>
        
        
                                    </span>
                                </th>
                                <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 fi-table-header-cell-domain"
                                    style=";">
                                    <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                                        <span
                                            class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">
                                            Status
                                        </span>
        
        
                                    </span>
                                </th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
        
                        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                            @foreach ($commits as $commit)
                                <tr 
                                    class="fi-ta-row [@media(hover:hover)]:transition [@media(hover:hover)]:duration-75 hover:bg-gray-50 dark:hover:bg-white/5"
                                    wire:key="M6K6XsWIk8PQlHDRWSZi.table.records.40">
                                    <td
                                        class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3 fi-table-cell-domain">
                                        <div class="fi-ta-col-wrp">
                                            <!--[if BLOCK]><![endif]-->
                                            <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4">
                                                <!--[if BLOCK]><![endif]--> <!--[if BLOCK]><![endif]-->
        
        
                                                <div class="flex ">
        
                                                    <div class="flex max-w-max">
                                                        <!--[if BLOCK]><![endif]-->
                                                        <div class="fi-ta-text-item inline-flex items-center gap-1.5 ">
        
        
                                                            <span
                                                                class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white  "
                                                                style="">
                                                                {{ $commit['author']['login'] }}
                                                            </span>
        
        
                                                        </div>
        
                                                    </div>
        
        
        
        
                                                </div>
        
        
        
                                            </div>
        
        
                                        </div>
                                    </td>
        
                                    <td
                                        class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3 fi-table-cell-domain">
                                        <div class="fi-ta-col-wrp">
                                            <!--[if BLOCK]><![endif]-->
                                            <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4">
                                                <!--[if BLOCK]><![endif]--> <!--[if BLOCK]><![endif]-->
        
        
                                                <div class="flex ">
        
                                                    <div class="flex max-w-max">
                                                        <!--[if BLOCK]><![endif]-->
                                                        <div class="fi-ta-text-item inline-flex items-center gap-1.5 ">
        
        
                                                            <span
                                                                class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white  "
                                                                style="">
                                                                {{ $commit['commit']['message'] }}
                                                            </span>
        
        
                                                        </div>
        
                                                    </div>
        
        
        
        
                                                </div>
        
        
        
                                            </div>
        
        
                                        </div>
                                    </td>
        
                                    <td
                                        class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3 fi-table-cell-domain">
                                        <div class="fi-ta-col-wrp">
                                            <!--[if BLOCK]><![endif]-->
                                            <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4">
                                                <!--[if BLOCK]><![endif]--> <!--[if BLOCK]><![endif]-->
        
        
                                                <div class="flex ">
        
                                                    <div class="flex max-w-max">
                                                        <!--[if BLOCK]><![endif]-->
                                                        <div class="fi-ta-text-item inline-flex items-center gap-1.5 ">
        
        
                                                            <span
                                                                class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white  "
                                                                style="">
                                                                @if ($commit['sha'] == $record->current_sha)
                                                                    <x-filament::badge>
                                                                        Active
                                                                    </x-filament::badge>
                                                                @endif
                                                            </span>
        
        
                                                        </div>
        
                                                    </div>
        
        
        
        
                                                </div>
        
                                            </div>
                                        </div>
                                    </td>
        
        
                                    <!--[if BLOCK]><![endif]-->
                                    <td
                                        class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                        <div class="whitespace-nowrap px-3 py-4">
                                            <div class="fi-ta-actions flex shrink-0 items-center gap-3 justify-end">
        
                                                <x-filament::button target="_blank" href="{{ $commit['html_url'] }}"
                                                    tag="a">
                                                    View
                                                </x-filament::button>
        
                                                <x-filament::button color="success" href="{{ $commit['html_url'] }}"
                                                    wire:click="deploy('{{ $commit['sha'] }}')">
                                                    Deploy
                                                </x-filament::button>
        
        
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
        
            </div>
            
        </x-filament-tables::container>
    </div>
</div>
