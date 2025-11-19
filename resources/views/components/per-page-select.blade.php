<label for="perPageSelect" class="form-label mb-0 align-middle">{{ __('lang.agents_list.show_by') }}</label>
<select id="perPageSelect" class="form-select form-select-sm" style="width: auto;">
    @foreach ($options as $option)
        <option value="{{ $option }}" {{ $perPage == $option ? 'selected' : '' }}>
            {{ $option }}
        </option>
    @endforeach
</select>
