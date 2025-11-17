let utilsLoadedPromise = null;

function loadUtilsOnce(url = "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js") {
    if (utilsLoadedPromise) return utilsLoadedPromise;

    utilsLoadedPromise = new Promise((resolve, reject) => {
        if (window.intlTelInputUtils) {
            resolve();
            return;
        }
        const existing = Array.from(document.querySelectorAll('script[src]'))
            .find(s => s.src && s.src.includes('intl-tel-input') && s.src.includes('utils.js'));
        if (existing) {
            existing.addEventListener('load', () => resolve());
            existing.addEventListener('error', () => reject(new Error('Failed to load utils.js')));
            return;
        }

        const s = document.createElement('script');
        s.src = url;
        s.async = true;
        s.defer = true;
        s.onload = () => resolve();
        s.onerror = () => reject(new Error('Failed to load utils.js'));
        document.head.appendChild(s);
    });

    return utilsLoadedPromise;
}

export async function initPhoneInput(selector, hiddenSelector) {
    const field = document.querySelector(selector);
    if (!field) {
        //console.warn(`initPhoneInput: элемент не найден по селектору ${selector}`);
        return null;
    }

    const hidden = document.querySelector(hiddenSelector);
    if (!hidden) {
        //console.warn(`initPhoneInput: скрытый input не найден по селектору ${hiddenSelector}`);
    }

    if (typeof window.intlTelInput !== 'function') {
        //console.error('initPhoneInput: window.intlTelInput не найден. Убедитесь, что скрипт intl-tel-input подключён раньше этого модуля.');
        return null;
    }

    try {
        await loadUtilsOnce();
    } catch (e) {
        //console.warn('initPhoneInput: не удалось подгрузить utils.js, продолжим без него.', e);
    }

    if (field.dataset.itiInitialized === '1') {
        //console.info(`initPhoneInput: поле ${selector} уже инициализировано`);
        return field._itiInstance || null;
    }

    const iti = window.intlTelInput(field, {
        initialCountry: "auto",
        geoIpLookup: function(callback) {
            fetch("https://ipapi.co/json")
                .then(res => res.json())
                .then(data => callback(data.country_code || "us"))
                .catch(() => callback("us"));
        },
        separateDialCode: true,
    });

    field._itiInstance = iti;
    field.dataset.itiInitialized = '1';

    const form = field.closest("form");
    if (!form) {
        //console.warn(`initPhoneInput: не найден form для поля ${selector}`);
    } else {
        form.addEventListener("submit", () => {
            if (!hidden) return;
            try {
                hidden.value = iti.getNumber();
            } catch (e) {
                hidden.value = field.value;
            }
        });
    }

    return iti;
}
