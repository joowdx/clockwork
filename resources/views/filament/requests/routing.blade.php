@php($requests ??= $schedule->requests)

<section class="space-y-3">
    <div>
        <h2 class="text-xl font-bold tracking-tight">
            Request History
        </h2>

        <p class="text-gray-600">
            {{ isset($schedule) ? ($schedule->request->completed ? 'Completed' : 'Pending') : null }}
        </p>
    </div>

    <div class="pl-[0.75rem] space-y-3">
        <ol class="relative border-gray-200 border-s dark:border-gray-700">
            @foreach ($requests as $request)
                <li class="mb-4 ms-6">
                    <span
                        @class([
                            'absolute flex items-center justify-center w-6 h-6 rounded-full -start-3 ring-8 ring-white dark:ring-gray-900',
                            'bg-gray-500' => $request->requested,
                            'bg-purple-500' => $request->cancelled,
                            'bg-red-500' => $request->rejected,
                            'bg-green-500' => $request->approved,
                            'bg-yellow-500' => $request->returned || $request->deflected,
                            'bg-sky-500' => $request->escalated,
                        ])
                    >
                        @switch(true)
                            @case($request->requested)
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                    <path d="M280-420q25 0 42.5-17.5T340-480q0-25-17.5-42.5T280-540q-25 0-42.5 17.5T220-480q0 25 17.5 42.5T280-420Zm200 0q25 0 42.5-17.5T540-480q0-25-17.5-42.5T480-540q-25 0-42.5 17.5T420-480q0 25 17.5 42.5T480-420Zm200 0q25 0 42.5-17.5T740-480q0-25-17.5-42.5T680-540q-25 0-42.5 17.5T620-480q0 25 17.5 42.5T680-420ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>
                                @break
                            @case($request->cancelled || $request->rejected)
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                    <path d="m336-280 144-144 144 144 56-56-144-144 144-144-56-56-144 144-144-144-56 56 144 144-144 144 56 56ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>
                                @break
                            @case($request->approved)
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                    <path d="m424-296 282-282-56-56-226 226-114-114-56 56 170 170Zm56 216q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>
                                @break
                            @case($request->returned)
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed" transform="scale(-1, -1)">
                                    <path d="M480-280q83 0 141.5-58.5T680-480h-60q0 58-41 99t-99 41q-58 0-99-41t-41-99q0-58 41-99t99-41h3l-49 50 42 43 120-120-120-120-43 43 44 44q-82 2-139.5 60T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>
                                @break
                            @case($request->escalated)
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                    <path d="m356-300 204-204v90h80v-226H414v80h89L300-357l56 57ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>
                                @break
                            @case($request->deflected)
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed" transform="rotate(-60)">
                                    <path d="M240-400h80q0-59 43-99.5T466-540q36 0 67 16.5t51 43.5h-64v80h200v-200h-80v62q-32-38-76.5-60T466-620q-95 0-160.5 64T240-400ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>
                                @break
                            @default
                        @endswitch
                    </span>

                    <h3 class="flex items-center mb-1 text-base">
                        <span
                            @class([
                                'font-bold uppercase' => $request->status->value !== 'requested',
                                'text-purple-500' => $request->cancelled,
                                'text-red-500' => $request->rejected,
                                'text-green-500' => $request->approved,
                                'text-yellow-500' => $request->returned || $request->deflected,
                                'text-sky-500' => $request->escalated,
                            ])
                        >
                            {{ "{$request->status->getLabel()}" }}&nbsp;
                        </span>

                        @if (in_array($request->status->value, ['approved', 'rejected', 'returned']))
                            by the {{ $request->to ?? 'system' }}
                            @if ($request->returned)
                                for revision
                            @endif
                        @elseif($request->deflected)
                            back to the {{ $request->to }}
                        @elseif($request->status->value === 'escalated')
                            to the {{ $request->to }}
                        @else
                            @if ($request->for && ! $request->cancelled)
                                for {{ $request->for }}
                            @endif

                            @if ($request->to)
                                of {{ $request->to }}
                            @endif
                        @endif
                    </h3>

                    <time class="block mb-2 text-sm font-light leading-none text-neutral-500">
                        <span class="font-bold">
                            {{
                                $request->user->employee?->titled_name ??
                                ($request->user === null ? 'Forwarded automatically' : $request->user->name)
                            }}
                        </span>

                        on {{ $request->created_at->format('jS \of F Y \a\t H:i:s.') }}
                    </time>

                    @if ($request->remarks)
                        <blockquote class="p-3 text-base bg-gray-100 rounded-md dark:bg-gray-800">
                            <svg class="w-5 h-5 mb-2 text-gray-400 dark:text-gray-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 14">
                                <path d="M6 0H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h4v1a3 3 0 0 1-3 3H2a1 1 0 0 0 0 2h1a5.006 5.006 0 0 0 5-5V2a2 2 0 0 0-2-2Zm10 0h-4a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h4v1a3 3 0 0 1-3 3h-1a1 1 0 0 0 0 2h1a5.006 5.006 0 0 0 5-5V2a2 2 0 0 0-2-2Z"/>
                            </svg>
                            <p class="text-base leading-none">
                                {{
                                    str($request->remarks)
                                        ->replace('<ul>', '<ul class="list-disc list-inside">')
                                        ->replace('<ol>', '<ol class="list-decimal list-inside">')
                                        ->toHtmlString()
                                }}
                            </p>
                        </blockquote>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</section>
