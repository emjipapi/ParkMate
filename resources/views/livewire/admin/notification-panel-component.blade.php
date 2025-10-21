  {{-- resources\views\livewire\admin\notification-panel-component.blade.php --}}
<div class="notification-panel" id="notifPanel">
  <div class="notification-header">Notifications</div>

  @forelse ($logs as $log)
    <div class="notif-item">
      🚫 {{ $log->details }}
      <div style="font-size: 0.75rem; color: #888;">
        {{ $log->created_at->diffForHumans() }}
      </div>
    </div>
  @empty
    <div class="notif-item text-muted">No denied entries found.</div>
  @endforelse
</div>
