import { Tooltip } from 'bootstrap';

document.addEventListener('DOMContentLoaded', function () {

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new Tooltip(tooltipTriggerEl);
    });

    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox:not(:disabled)');

    if (selectAllCheckbox && rowCheckboxes.length > 0) {

        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const totalEnabled = document.querySelectorAll('.row-checkbox:not(:disabled)').length;
                const totalChecked = document.querySelectorAll('.row-checkbox:not(:disabled):checked').length;

                selectAllCheckbox.checked = totalEnabled === totalChecked;
            });
        });
    }


    const agentListContainer = document.getElementById('agent-list-container');
    const perPageSelect = document.getElementById('perPageSelect');

    if (!agentListContainer) {
        return;
    }

    const fetchAgents = async (url) => {
        agentListContainer.style.opacity = '0.5';

        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const html = await response.text();
            agentListContainer.innerHTML = html;
            history.pushState(null, '', url);
        } catch (error) {
            console.error('Ошибка при загрузке данных:', error);
        } finally {
            agentListContainer.style.opacity = '1';
        }
    };

    if(perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            const perPage = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', perPage);
            url.searchParams.delete('page');
            fetchAgents(url.toString());
        });
    }


    agentListContainer.addEventListener('click', function(event) {
        const paginationLink = event.target.closest('.pagination a');
        const sortableLink = event.target.closest('.sortable-link');

        if (paginationLink || sortableLink) {
            event.preventDefault();
            const url = (paginationLink || sortableLink).getAttribute('href');
            fetchAgents(url);
        }
    });

    window.addEventListener('popstate', () => {
        fetchAgents(window.location.href);
    });
});
