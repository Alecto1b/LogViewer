<x-filament-panels::page>
    <div
        ax-load
        ax-load-src="{{ $js }}"
        ax-load-css="{{ $css }}"
        x-data="editor({
            maxLines: @js(35),
            minLines: @js(10),
            fontSize: @js(12)
        })"
    >
        <div class="flex flex-wrap items-center justify-between gap-6">
            <div class="w-full">
                {{ $this->form }}
            </div>
        </div>

        <div
            class="mt-4 rounded-lg ace-filament"
            x-ref="editor"
            wire:ignore
        ></div>
    </div>
</x-filament-panels::page>
