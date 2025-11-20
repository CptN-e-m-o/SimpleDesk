<div class="card-body">
    <div class="row g-5 mb-3">
        <div class="col-md-6">
            <label for="email" class="form-label fw-bold">
                {{ __('lang.teams_form.name') }} <span class="text-danger">*</span>
            </label>
            <input type="text"
                   id="name"
                   name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   placeholder="{{ __('lang.teams_form.name_placeholder') }}"
                   value="{{ old('name', $team->name ?? '') }}">
            @error('email')
            <div class="invalid-feedback">{{ __('lang.agents_form.name_error') }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">{{ __('lang.teams_form.lead') }}:</label>
            <select name="agent_id" class="form-select select2"
                    data-placeholder="{{ __('Выберите агента') }}">
                <option value=""></option>
                @foreach($agents as $agent)
                    <option
                        value="{{ $agent->id }}"
                        @selected(old('agent_id', $ticket->agent_id ?? null) == $agent->id)
                    >
                        {{ $agent->full_name }}
                    </option>
                @endforeach
            </select>

        </div>
    </div>

    <label for="admin_notes" class="form-label fw-bold">
        {{ __('lang.teams_form.admin_notes') }}
    </label>
    <textarea id="admin_notes" name="admin_notes" class="form-control rich-editor">
        {!! old('admin_notes', $team->admin_notes ?? '') !!}
    </textarea>

</div>
