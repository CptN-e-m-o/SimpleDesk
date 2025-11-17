import { initPhoneInput } from './helpers/phone-input.js';

document.addEventListener('DOMContentLoaded', function () {
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatar-preview');
    const avatarWrapper = document.querySelector('.avatar-wrapper');
    const spinner = document.getElementById('avatar-spinner');

    if (!avatarInput || !avatarPreview || !avatarWrapper || !spinner) {
        return;
    }

    const uploadUrl = avatarWrapper.dataset.uploadUrl;

    avatarInput.addEventListener('change', function (event) {
        const file = event.target.files[0];
        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.src = e.target.result;
        };
        reader.readAsDataURL(file);

        uploadAvatar(file);
    });

    function uploadAvatar(file) {
        const formData = new FormData();
        formData.append('avatar', file);

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        spinner.classList.remove('d-none');
        avatarPreview.style.opacity = '0.5';

        fetch(uploadUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log(data.message);
                    avatarPreview.src = data.new_avatar_url;
                } else {
                    alert('Ошибка: ' + (data.message || 'Не удалось загрузить изображение.'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла системная ошибка.');
            })
            .finally(() => {
                spinner.classList.add('d-none');
                avatarPreview.style.opacity = '1';
                avatarInput.value = '';
            });
    }

    initPhoneInput("#phone_number", "#phone_number_hidden");
});
