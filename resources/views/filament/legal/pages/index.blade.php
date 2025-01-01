<x-filament-panels::page>
    <div class="text-base leading-none prose max-w-4xl w-full rounded-md dark:prose-invert">
        {{ str($this->agreement())->markdown()->toHtmlString() }}
    </div>
</x-filament-panels::page>
