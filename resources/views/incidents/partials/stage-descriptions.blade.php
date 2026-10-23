<div class="mt-4 space-y-4 md:col-span-2">
    @foreach (['investigation_description' => 'Investigation Description', 'resolution_description' => 'Resolution Description'] as $field => $label)
        @continue(isset($descriptionStage) && $field !== $descriptionStage.'_description')
        @php
            $stage = $field === 'investigation_description' ? 'investigation' : 'resolution';
            $photoInput = $stage === 'investigation' ? 'investigation_photos[]' : 'proof_photos[]';
            $savedPhotos = isset($incident) && (
                $incident->proofPhotos->contains('stage', $stage)
                || ($stage === 'resolution' && $incident->isResolvedOrDone() && $incident->proof_photo_path)
            );
            $note = old($field, isset($incident) ? $incident->$field : '');
        @endphp
        <div x-data="{
            savedPhotos: @js((bool) $savedPhotos),
            photoInput: @js($photoInput),
            note: @js($note ?? ''),
            uploaded: false,
            refreshPhotos() {
                const form = this.$el.closest('form');
                this.uploaded = Array.from(form.querySelectorAll('input[type=file]')).some(input => input.name === this.photoInput && !input.disabled && input.files.length > 0);
            }
        }" x-init="$nextTick(() => refreshPhotos())"
            @change.window="if ($event.target.form === $el.closest('form')) $nextTick(() => refreshPhotos())"
            x-show="savedPhotos || uploaded || note.length > 0 || @js($errors->has($field))" x-cloak>
            <label for="{{ $field }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
            <textarea id="{{ $field }}" name="{{ $field }}" x-model="note" rows="4" maxlength="5000" class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="{{ $field === 'investigation_description' ? 'Describe findings and actions taken during investigation.' : 'Describe how the incident was resolved and the outcome.' }}">{{ old($field, isset($incident) ? $incident->$field : '') }}</textarea>
            <x-input-error :messages="$errors->get($field)" class="mt-1" />
        </div>
    @endforeach
</div>

