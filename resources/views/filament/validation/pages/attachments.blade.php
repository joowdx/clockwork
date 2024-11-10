<?php
use App\Enums\AttachmentClassification;
?>

<section class="grid gap-y-2">
    @foreach($attachments as $attachment)
        <article class="grid gap-y-2">
            @switch($attachment->classification)
                @case(AttachmentClassification::ACCOMPLISHMENT)
                    <div
                        @class(['fi-in-entry-wrp-label inline-flex items-center gap-x-3'])
                    >
                        <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                            Accomplishment
                        </span>

                        <div class="flex space-x-2">
                            <x-filament::icon-button
                                tag="a"
                                href="{{ route('download.attachment', $attachment) }}"
                                icon="heroicon-o-arrow-down-tray"
                                outlined
                                download
                            />

                            <x-filament::modal width="7xl">
                                <x-slot name="trigger">
                                    <x-filament::icon-button
                                        icon="heroicon-o-arrow-top-right-on-square"
                                    />
                                </x-slot>

                                <iframe
                                    title="Accomplishment"
                                    loading="lazy"
                                    class="w-full rounded-xl h-[calc(100vh-8rem)]"
                                    src="{{ route('download.attachment', ['attachment' => $attachment, 'inline' => 1]) }}#view=FitH"
                                    type="application/pdf"
                                    frameBorder="0"
                                >

                                </iframe>
                            </x-filament::modal>
                        </div>
                    </div>

                    <iframe
                        title="Accomplishment"
                        loading="lazy"
                        class="w-full rounded-xl"
                        src="{{ route('download.attachment', ['attachment' => $attachment, 'inline' => 1]) }}#view=FitH&toolbar=0&navpanes=0"
                        type="application/pdf"
                        height="400"
                        frameBorder="0"
                    >

                    </iframe>
                @break
            @endswitch
        </article>
    @endforeach
</section

