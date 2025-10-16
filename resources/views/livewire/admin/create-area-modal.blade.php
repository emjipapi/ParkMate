{{-- resources\views\livewire\admin\create-area-modal.blade.php --}}
<div wire:ignore.self class="modal fade" id="createAreaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Create Parking Area</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        {{-- Area Name (parking_areas.name) --}}
        <div class="mb-3">
          <label class="form-label">Area Name</label>
          <input type="text" class="form-control @error('areaName') is-invalid @enderror" wire:model="areaName">
          @error('areaName')
          <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="row">
          {{-- Slot Prefix --}}
          <div class="col-md-4 mb-3">
            <label class="form-label">Slot Prefix</label>
            <select class="form-select @error('slotPrefix') is-invalid @enderror" wire:model="slotPrefix">
              <option value="">-- Select Letter --</option>
              @foreach (range('A', 'Z') as $letter)
              <option value="{{ $letter }}">{{ $letter }}</option>
              @endforeach
            </select>
            <small class="text-muted">Ex: D + 14 → D1…D14</small>
            @error('slotPrefix')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          {{-- Car Slots --}}
          <div class="col-md-4 mb-3">
            <label class="form-label">Car Slots</label>
            <input type="number" class="form-control @error('carSlots') is-invalid @enderror" wire:model="carSlots"
              min="0">
            @error('carSlots')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          {{-- Motorcycle Slots --}}
          <div class="col-md-4 mb-3">
            <label class="form-label">Motorcycles Count</label>
            <input type="number" class="form-control @error('motorcycleSlots') is-invalid @enderror"
              wire:model="motorcycleSlots" min="0">
            @error('motorcycleSlots')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
<div class="mb-3">
    <label class="form-label">Allowed User Types</label>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="chkStudents" wire:model="allowStudents">
        <label class="form-check-label" for="chkStudents">Students</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="chkEmployees" wire:model="allowEmployees">
        <label class="form-check-label" for="chkEmployees">Employees</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="chkGuests" wire:model="allowGuests">
        <label class="form-check-label" for="chkGuests">Guests</label>
    </div>
    @error('allowStudents')
    <div class="text-danger small mt-2">{{ $message }}</div>
    @enderror
</div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" wire:click="createArea">Save</button>
      </div>
    </div>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    window.addEventListener('close-create-area-modal', () => {
        const modalEl = document.getElementById('createAreaModal');
        if (!modalEl) return;
        // Bootstrap 5 way
        const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        bsModal.hide();
    });

    // optional toast/alert handler
    window.addEventListener('notify', (e) => {
        // replace with your toast mechanism
        console.log('NOTIFY', e.detail);
        // Example: show a simple alert (replace with nicer UI)
        // alert(e.detail.message);
    });
});
</script>