<x-filament-widgets::widget>
    <x-filament::section heading="Quick links">
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
            @foreach ($this->getLinks() as $link)
                <x-filament::button tag="a" :href="$link['url']" :color="$link['color']" :icon="$link['icon']">
                    {{ $link['label'] }}
                </x-filament::button>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>