import { initPhoneInput } from '../helpers/phone-input.js';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

document.addEventListener('DOMContentLoaded', async () => {
    await initPhoneInput("#phone_number", "#phone_number_hidden");
    await initPhoneInput("#mobile_phone", "#mobile_phone_hidden");

    const editorElement = document.querySelector('#signature');
    if (editorElement) {
        ClassicEditor
            .create(editorElement)
            .catch(error => {
                console.error(error);
            });
    }
});
