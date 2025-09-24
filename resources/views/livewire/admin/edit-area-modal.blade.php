<div wire:ignore.self class="modal fade" id="editAreaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Edit Parking Area</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Area Name</label>
          <input type="text" class="form-control" wire:model="areaName">
          @error('areaName') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Slot Prefix</label>
            <input type="text" class="form-control" wire:model="slotPrefix" disabled>
          </div>

          <div class="col-md-4 mb-3">
            <label class="form-label">Car Slots</label>
            <input type="number" class="form-control" wire:model="carSlots" disabled>
          </div>

          <div class="col-md-4 mb-3">
            <label class="form-label">Motorcycle Slots</label>
            <input type="number" class="form-control" wire:model="motorcycleSlots">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Generated Slots</label>
          <input type="text" class="form-control" readonly 
                 value="{{ implode(', ', $generatedSlots) }}">
          <small class="text-muted">These are auto-generated and cannot be edited.</small>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" wire:click="updateArea">Save Changes</button>
      </div>

    </div>
  </div>
</div>