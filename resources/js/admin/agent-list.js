import { Tooltip } from 'bootstrap';

document.addEventListener('DOMContentLoaded', function () {
    const agentListContainer = document.getElementById('agent-list-container');
    const perPageSelect = document.getElementById('perPageSelect');
    const deactivateButton = document.getElementById('deactivate-selected-btn');

    const initializeTableInteractions = () => {
        const toggleDeactivateButton = () => {
            if (!deactivateButton) return;
            const totalChecked = document.querySelectorAll('.row-checkbox:not(:disabled):checked').length;
            deactivateButton.classList.toggle('d-none', totalChecked === 0);
        };

        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const rowCheckboxes = document.querySelectorAll('.row-checkbox:not(:disabled)');

        if (selectAllCheckbox && rowCheckboxes.length > 0) {
            selectAllCheckbox.addEventListener('change', function() {
                rowCheckboxes.forEach(checkbox => { checkbox.checked = this.checked; });
                toggleDeactivateButton();
            });

            rowCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const totalEnabled = document.querySelectorAll('.row-checkbox:not(:disabled)').length;
                    const totalChecked = document.querySelectorAll('.row-checkbox:not(:disabled):checked').length;
                    selectAllCheckbox.checked = totalEnabled === totalChecked;
                    toggleDeactivateButton();
                });
            });
        }

        const deactivateBtn = document.getElementById('deactivate-selected-btn');
        const selectedAgentsInput = document.getElementById('selectedAgentsInput');
        if (deactivateBtn && selectedAgentsInput) {
            deactivateBtn.addEventListener('click', () => {
                const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
                const agentIds = Array.from(checkedCheckboxes).map(cb => cb.value);
                selectedAgentsInput.value = JSON.stringify(agentIds);
            });
        }

        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) { return new Tooltip(tooltipTriggerEl); });

        toggleDeactivateButton();
    };

    const fetchAgents = async (url) => {
        if (!agentListContainer) return;
        agentListContainer.style.opacity = '0.5';
        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await response.text();
            agentListContainer.innerHTML = html;
            history.pushState(null, '', url);
            initializeTableInteractions();
        } catch (error) {
            console.error('Ошибка при загрузке данных:', error);
        } finally {
            agentListContainer.style.opacity = '1';
        }
    };

    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            const perPage = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', perPage);
            url.searchParams.delete('page');
            fetchAgents(url.toString());
        });
    }
    if (agentListContainer) {
        agentListContainer.addEventListener('click', function(event) {
            const link = event.target.closest('.pagination a, .sortable-link');
            if (link) {
                event.preventDefault();
                fetchAgents(link.getAttribute('href'));
            }
        });
    }
    window.addEventListener('popstate', () => { fetchAgents(window.location.href); });

    const deactivateModal = document.getElementById('deactivateAgentModal');
    if (deactivateModal) {
        const requesterSelectWrapper = document.getElementById('requester-select-wrapper');
        const requesterSelect = document.getElementById('new_requester_id');
        const deactivateForm = document.getElementById('deactivateForm');

        if (deactivateForm && requesterSelectWrapper) {
            deactivateForm.addEventListener('change', function(event) {
                if (event.target.name === 'requested_tickets_action') {
                    const shouldShow = event.target.value === 'change_requester' && event.target.checked;
                    requesterSelectWrapper.classList.toggle('d-none', !shouldShow);
                }
            });
        }

        deactivateModal.addEventListener('show.bs.modal', function () {
            if (!requesterSelect) return;

            const selectedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
            const selectedAgentIds = Array.from(selectedCheckboxes).map(cb => cb.value);
            const allOptions = requesterSelect.querySelectorAll('option');

            allOptions.forEach(option => {
                const agentId = option.getAttribute('data-agent-id');
                option.hidden = (agentId && selectedAgentIds.includes(agentId));
            });

            requesterSelect.value = "";
        });
    }

    initializeTableInteractions();
});
