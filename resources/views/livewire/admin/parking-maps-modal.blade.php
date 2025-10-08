{{-- resources\views\livewire\admin\parking-maps-modal.blade.php --}}
<div>
    {{-- Toolbar --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="m-0">Choose a map</h6>
        <div>
            <button wire:click="loadMaps" class="btn btn-sm btn-outline-secondary">Refresh</button>
        </div>
    </div>

    {{-- Grid of thumbnails --}}
    <div class="row g-3">
        @forelse($maps as $map)
            <div class="col-12 col-md-4">
                <div class="card h-100 shadow-sm" style="cursor: pointer;">
                    <div style="height: 140px; overflow: hidden; display:flex; align-items:center; justify-content:center;">
                        @if($map->file_path)
                            <img src="{{ asset('storage/' . $map->file_path) }}" 
                                 alt="{{ $map->name }}" 
                                 class="img-fluid" 
                                 style="object-fit:cover; width:100%; height:100%;">
                        @else
                            <div class="d-flex align-items-center justify-content-center w-100 h-100 text-muted">
                                No image
                            </div>
                        @endif
                    </div>

                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold" style="font-size:0.95rem;">{{ $map->name }}</div>
                                <div class="text-muted small">{{ $map->created_at->format('Y-m-d') }}</div>
                            </div>
                        </div>

                        <div class="mt-2 d-flex gap-2">
                            <a
  href="{{ url('/map/' . $map->id) }}"
  target="_blank"
  class="btn btn-sm btn-primary w-100"
  rel="noopener noreferrer"
  onclick="(function(){ 
      const modalEl = document.getElementById('openMapModal');
      try {
          const inst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
          inst.hide();
      } catch(e) { /* ignore */ }
  })()"
>
  Open
</a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center text-muted py-6">
                    <p class="mb-0">No maps found. Upload maps in the manager to display thumbnails here.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
