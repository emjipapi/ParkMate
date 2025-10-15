{{-- resources\views\livewire\admin\register-tag-modal-component.blade.php --}}
<div>
    {{-- Register Tag Modal --}}
    <div class="modal fade" wire:ignore.self id="registerTagModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form wire:submit.prevent="saveTag">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $isEditMode ? 'Edit Guest Tag' : 'Register New Guest Tag' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="tagName" class="form-label">Tag Name</label>
                            <input type="text" class="form-control" 
                                   placeholder="e.g., Guest Pass 01" 
                                   wire:model.live="tagName">
                            @error('tagName') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="tagId" class="form-label">Tag ID / RFID Code</label>
                            <div class="input-group">
                                <input type="text" class="form-control" 
                                       placeholder="Scan or enter the tag ID" 
                                       wire:model.live="tagId">
                                <button type="button" wire:click="scanRfid" class="btn btn-primary" 
                                        wire:loading.attr="disabled" wire:target="scanRfid">
                                    <span wire:loading.remove wire:target="scanRfid">Scan</span>
                                    <span wire:loading wire:target="scanRfid">
                                        <span class="spinner-border spinner-border-sm me-1"></span>
                                        Scanning...
                                    </span>
                                </button>
                            </div>
                            @error('tagId') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">
                            {{ $isEditMode ? 'Update Tag' : 'Register Tag' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@script
<script>
    const modalEl = document.getElementById('registerTagModal');
    
    modalEl.addEventListener('shown.bs.modal', function() {
        $wire.$refresh();
    });
    
    modalEl.addEventListener('hidden.bs.modal', function() {
        $wire.call('resetForm');
    });
</script>
@endscript