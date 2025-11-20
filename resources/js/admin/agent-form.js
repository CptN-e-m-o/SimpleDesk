import { initPhoneInput } from '../helpers/phone-input.js';

document.addEventListener('DOMContentLoaded', async () => {
    await initPhoneInput("#phone_number", "#phone_number_hidden");
    await initPhoneInput("#mobile_phone", "#mobile_phone_hidden");
});
