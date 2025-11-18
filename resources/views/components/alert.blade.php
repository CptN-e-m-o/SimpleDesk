@if ($message)
    <div
        class="alert {{ $bsClass() }} alert-dismissible fade show shadow-sm position-fixed top-0 start-50 translate-middle-x mt-3 z-3"
        role="alert"
        style="min-width: 300px; max-width: 500px; z-index: 1080;"
    >
        <div class="d-flex align-items-center">
            @if($type === 'success')
                <i class="bi bi-check-circle-fill me-2"></i>
            @elseif($type === 'danger')
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
            @elseif($type === 'warning')
                <i class="bi bi-exclamation-circle-fill me-2"></i>
            @endif

            <span>{{ $message }}</span>
        </div>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 4000);
            }
        });
    </script>
@endif
