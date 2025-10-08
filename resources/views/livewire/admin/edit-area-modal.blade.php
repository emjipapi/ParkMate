<div wire:ignore.self class="modal fade" id="editAreaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit Parking Area</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-2">
                    <strong>Parking Area ID:</strong> {{ $areaId }}
                </div>
                <div class="mb-3">
                    <label class="form-label">Area Name</label>
                    <!-- defer syncing until Save (reduces network calls) -->
                    <input type="text" class="form-control" wire:model.defer="areaName">
                    @error('areaName') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <!-- Slot Prefix (editable when adding new car slots to an area that had none) -->
                    @php
                    $isDisabled = $originallyHadCarSlots || !$carSlotsEnabled;
                    @endphp

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Slot Prefix</label>

                        <select id="slotPrefix" @if($isDisabled) disabled aria-disabled="true" @endif
                            wire:model.live="slotPrefix" aria-describedby="slotPrefixHelp" @class([ 'form-select'
                            , 'is-invalid'=> $errors->has('slotPrefix'),
                            // visual helpers to make it clearly disabled
                            'text-muted' => $isDisabled,
                            'opacity-75' => $isDisabled,
                            'cursor-not-allowed' => $isDisabled,
                            'bg-light' => $isDisabled,
                            ])>
                            <option value="">-- Select Letter --</option>
                            @foreach (range('A', 'Z') as $letter)
                            <option value="{{ $letter }}">{{ $letter }}</option>
                            @endforeach
                        </select>

                        @error('slotPrefix')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Car Slots: single checkbox + editable/readonly input depending on checkbox -->
                    <div class="col-md-4 mb-3 d-flex align-items-center gap-2">
                        <div class="form-check" style="min-width: 2rem;">
                            <input class="form-check-input" type="checkbox" id="chkCarSlots"
                                wire:model.live="carSlotsEnabled">
                        </div>

                        <div class="flex-grow-1">
                            <label class="form-label mb-1">Car Slots</label>
                            @error('carSlotsEnabled') <div class="text-danger small">{{ $message }}</div> @enderror
                            <!-- Use live binding to update immediately when checkbox changes -->
                            <input type="number" class="form-control" wire:model.live="carSlots" min="0"
                                @disabled(!$carSlotsEnabled) @class(['form-control', 'bg-light'=> !$carSlotsEnabled])>
                            @error('carSlots') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <!-- Motorcycle count + checkbox -->
                    <div class="col-md-4 mb-3 d-flex align-items-center gap-2">
                        <div class="form-check" style="min-width: 2rem;">
                            <input class="form-check-input" type="checkbox" id="chkMoto"
                                wire:model.live="motorcycleEnabled">
                        </div>

                        <div class="flex-grow-1">
                            <label class="form-label mb-1">Motorcycles Count</label>
                            @error('motorcycleEnabled') <div class="text-danger small">{{ $message }}</div> @enderror
                            <input type="number" class="form-control" wire:model.live="motorcycleSlots" min="0"
                                @disabled(!$motorcycleEnabled) @class(['form-control', 'bg-light'=>
                            !$motorcycleEnabled])>
                            @error('motorcycleSlots') <div class="text-danger small">{{ $message }}</div> @enderror

                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Generated Slots</label>
                    <input type="text" class="form-control bg-light" readonly
                        value="{{ implode(', ', $generatedSlots) }}">
                    <small class="text-muted">These are auto-generated and cannot be edited.</small>
                </div>

                <div>
                    <div>
                        @if($carSlots === 0 && !$carSlotsEnabled)
                        <small class="text-muted">Enable to add car slots for this area.</small>
                        @endif
                    </div>
                    <div>
                        @if($motorcycleSlots === 0 && !$motorcycleEnabled)
                        <small class="text-muted">Enable to add motorcycle parking for this area.</small>
                        @endif
                    </div>

                    {{-- explicit validation hint when both are disabled --}}
                    @if(!$carSlotsEnabled && !$motorcycleEnabled)
                        <div class="mt-2">
                            <small class="text-danger">@if($errors->has('area_flags')){{ $errors->first('area_flags') }}@else Please enable at least one slot type: Car Slots or Motorcycles. @endif</small>
                        </div>
                    @endif
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-danger me-auto" wire:click="deleteArea"
                    wire:confirm="Are you sure you want to DELETE this parking area? This action cannot be undone.">
                    Delete
                </button>

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                {{-- Save disabled if both types are unchecked --}}
                <button type="button"
                        class="btn btn-primary @if(!$carSlotsEnabled && !$motorcycleEnabled) disabled cursor-not-allowed @endif"
                        wire:click="updateArea"
                        @if(!$carSlotsEnabled && !$motorcycleEnabled) disabled aria-disabled="true" title="Enable at least one slot type to save changes" @endif>
                    Save Changes
                </button>
            </div>

        </div>
    </div>
</div>
