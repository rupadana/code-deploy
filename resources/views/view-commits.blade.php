{{-- @php
    dd($commits);
@endphp --}}
<div>
    @vite('resources/css/app.css')
    <div class="flex flex-col">
    <div class="overflow-x-auto">
        <div class="inline-block min-w-full">
            <div class="overflow-hidden">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead>
                        <tr class="">
                            <th class="px-5 py-3 text-xs font-medium text-left uppercase">Author</th>
                            <th class="px-5 py-3 text-xs font-medium text-left uppercase">Message</th>
                            <th class="px-5 py-3 text-xs font-medium text-right uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200">
                        @foreach ($commits as $commit)
                            <tr class="">
                                <td class="px-5 py-4 text-sm font-medium whitespace-nowrap">{{$commit['author']['login']}}
                                </td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap">
                                    {{$commit['commit']['message']}}
                                </td>
                                <td class="px-5 py-4 text-sm font-medium text-right whitespace-nowrap">
                                    <a class="text-blue-600 hover:text-blue-700" href="{{$commit['html_url']}}" target="_blank">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
    {{-- <table>
        @foreach ($commits as $commit )
            
            <tr>
                <td>
                    <p>{{$commit['commit']['message']}}</p>
                    <p>{{$commit['sha']}}</p>
                </td>
                <td>
                    <a href="{{$commit['html_url']}}">View</a>
                </td>
            </tr>
        @endforeach
    </table> --}}
</div>