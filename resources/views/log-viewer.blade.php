<x-filament-panels::page>
    <link rel="stylesheet" href="{{ $css }}">

    <div
        x-data="{
            async init() {
                const { default: makeEditor } = await import(@js($js))
                const editor = makeEditor({
                    maxLines: @js(35),
                    minLines: @js(10),
                    fontSize: @js(12)
                })

                Object.assign(this, editor)
                editor.init.call(this)
            }
        }"
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
