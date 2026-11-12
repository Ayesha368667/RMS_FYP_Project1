@php
    $bellUser = auth()->user();
    $unread = $bellUser->unreadNotifications()->count();
    $latest = $bellUser->notifications()->latest()->take(5)->get();
@endphp

<style>
    details.bell > summary { list-style: none; }
    details.bell > summary::-webkit-details-marker { display: none; }
</style>

<details class="bell" style="position:relative;display:inline-block">
    <summary style="cursor:pointer;position:relative;padding:4px 8px;color:inherit">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2a6 6 0 0 0-6 6v4l-2 3h16l-2-3V8a6 6 0 0 0-6-6zm0 20a3 3 0 0 0 3-3H9a3 3 0 0 0 3 3z"/>
        </svg>
        @if ($unread > 0)
            <span style="position:absolute;top:-4px;right:-2px;background:#dc2626;color:#fff;font-size:11px;font-weight:700;min-width:18px;height:18px;line-height:18px;text-align:center;border-radius:9px;padding:0 4px">{{ $unread }}</span>
        @endif
    </summary>

    <div style="position:absolute;right:0;top:100%;width:320px;max-width:90vw;background:#fff;color:#27272a;border:1px solid #e4e4e7;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:1050;padding:12px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <strong>Notifications</strong>
            @if ($unread > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" style="border:0;background:none;color:#0f766e;font-size:13px;cursor:pointer">Mark all as read</button>
                </form>
            @endif
        </div>

        @forelse ($latest as $n)
            <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                @csrf
                <button type="submit" style="display:block;width:100%;text-align:left;border:0;border-top:1px solid #e4e4e7;background:{{ $n->read_at ? '#ffffff' : '#f0fdfa' }};padding:10px 8px;cursor:pointer">
                    <div style="font-weight:{{ $n->read_at ? '400' : '700' }};color:#115e59">{{ data_get($n->data, 'title') }}</div>
                    <div style="font-size:13px;color:#52525b">{{ data_get($n->data, 'body') }}</div>
                    <div style="font-size:11px;color:#71717a">{{ $n->created_at->diffForHumans() }}</div>
                </button>
            </form>
        @empty
            <div style="padding:12px 0;color:#71717a">No notifications yet.</div>
        @endforelse
    </div>
</details>