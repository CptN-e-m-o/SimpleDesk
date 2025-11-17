<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">!Подпись агента</h5>
    </div>

    <div class="card-body">
        <textarea id="signature" name="signature" class="form-control">
            {!! old('signature', $agent->signature ?? '') !!}
        </textarea>
    </div>
</div>
