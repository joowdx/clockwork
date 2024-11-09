<?php
use App\Enums\AttachmentClassification;
?>

<section>
    @foreach($attachments as $attachment)
        <article class="space-y-2">
            @switch($attachment->classification)
                @case(AttachmentClassification::ACCOMPLISHMENT)
                    <h2 class="text-sm font-bold">
                        {{ $attachment->classification->getLabel() }}
                    </h2>

                    <x-filament::button
                        href="{{ route('export', $attachment) }}"
                        tag="a"
                    >
                        Download
                    </x-filament::button>

                    <embed
                        class="w-full rounded-xl h-[36rem]"
                        src="data:application/pdf;base64,{{base64_encode($attachment->content)}}"
                        type="application/pdf"
                    >
                @break
            @endswitch
        </article>
    @endforeach
</section

