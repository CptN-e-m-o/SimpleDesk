document.addEventListener('DOMContentLoaded', () => {
    $('.select2').each(function () {
        $(this).select2({
            placeholder: $(this).data('placeholder'),
            allowClear: true
        })
    })
})
