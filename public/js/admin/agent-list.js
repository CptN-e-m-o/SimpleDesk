import { Tooltip } from 'bootstrap';

document.addEventListener('DOMContentLoaded', function () {
    const agentListContainer = document.getElementById('agent-list-container');
    if (!agentListContainer) return;

    const perPageSelect = document.getElementById('perPageSelect');

    const fetchAgents = async (url) => {
        agentListContainer.style.opacity = '0.5';
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error('Network response was not ok');
            const html = await response.text();
            agentListContainer.innerHTML = html;
            history.pushState(null, '', url);
        } catch (error) {
            console.error('Ошибка при загрузке данных:', error);
            alert('Произошла ошибка при обновлении списка агентов.');
        } finally {
            agentListContainer.style.opacity = '1';
            initializeDynamicContent();
        }
    };

    const initializeDynamicContent = () => {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(el => new Tooltip(el));

        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const rowCheckboxes = document.querySelectorAll('.row-checkbox:not(:disabled)');

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                rowCheckboxes.forEach(checkbox => { checkbox.checked = this.checked; });
            });
        }
    };


    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', this.value);
            url.searchParams.set('page', 1);
            fetchAgents(url.toString());
        });
    }

    agentListContainer.addEventListener('click', function (event) {
        const target = event.target.closest('.pagination a, .sortable-link');

        if (target) {
            event.preventDefault();
            const url = target.getAttribute('href');
            if (url) {
                fetchAgents(url);
            }
        }
    });

    window.addEventListener('popstate', () => {
        fetchAgents(window.location.href);
    });

    initializeDynamicContent();
});
