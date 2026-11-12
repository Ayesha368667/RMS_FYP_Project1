<div style="display:flex;justify-content:flex-end;margin-bottom:12px">
    <x-filament::button size="sm" wire:click="markAllAsRead">Mark all as read</x-filament::button>
</div>

<div style="display:flex;flex-direction:column;gap:12px">
    @forelse ($notifications as $n)
        <x-filament::section>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
                <div>
                    <div style="display:flex;align-items:center;gap:8px;font-weight:600">
                        {{ data_get($n->data, 'title') }}
                        @if (is_null($n->read_at))
                            <x-filament::badge color="success" size="sm">New</x-filament::badge>
                        @endif
                    </div>
                    <div>{{ data_get($n->data, 'body') }}</div>
                    <div style="font-size:12px;opacity:.7;margin-top:4px">{{ $n->created_at->diffForHumans() }}</div>
                </div>

                <div style="display:flex;gap:8px">
                    <x-filament::button size="xs" color="gray" wire:click="openNotification('{{ $n->id }}')">Open</x-filament::button>
                    @if (is_null($n->read_at))
                        <x-filament::button size="xs" wire:click="markOneAsRead('{{ $n->id }}')">Mark as read</x-filament::button>
                    @endif
                </div>
            </div>
        </x-filament::section>
    @empty
        <x-filament::section>No notifications yet.</x-filament::section>
    @endforelse
</div>