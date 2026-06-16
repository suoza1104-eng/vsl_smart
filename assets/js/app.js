(function () {
    const state = window.VSL_SMART || {};
    const modal = document.getElementById('leadModal');
    const leadForm = document.getElementById('leadForm');
    const leadMessage = document.getElementById('leadMessage');
    const closeButton = document.querySelector('.modal-close');
    const countdown = document.getElementById('countdown');
    let activeButtonId = 'cta';

    function refreshIcons() {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function openModal(buttonId) {
        activeButtonId = buttonId || 'cta';
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            const input = modal.querySelector('input[name="name"]');
            if (input) {
                input.focus();
            }
        }, 80);
    }

    function closeModal() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    async function registerClick(leadId) {
        const formData = new FormData();
        formData.append('button_id', activeButtonId);
        formData.append('visitor_uuid', state.visitorUuid || '');
        formData.append('headline_id', state.headlineId || '');
        formData.append('offer_id', state.offerId || '');
        if (leadId) {
            formData.append('lead_id', leadId);
        }

        const response = await fetch('api/click.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        return response.json();
    }

    document.querySelectorAll('.js-buy').forEach((button) => {
        button.addEventListener('click', () => openModal(button.dataset.buttonId));
    });

    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    if (leadForm) {
        leadForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const submit = leadForm.querySelector('button[type="submit"]');
            submit.disabled = true;
            leadMessage.textContent = 'Liberando seu acesso...';
            leadMessage.className = 'form-message';

            try {
                const leadResponse = await fetch('api/lead.php', {
                    method: 'POST',
                    body: new FormData(leadForm),
                    credentials: 'same-origin'
                });
                const leadData = await leadResponse.json();
                if (!leadData.success) {
                    throw new Error(leadData.message || 'Não foi possível cadastrar.');
                }

                const clickData = await registerClick(leadData.lead_id);
                if (!clickData.success || !clickData.redirect_url) {
                    throw new Error(clickData.message || 'Não foi possível abrir o checkout.');
                }

                leadMessage.textContent = 'Cadastro recebido. Redirecionando...';
                leadMessage.classList.add('success');
                window.location.href = clickData.redirect_url;
            } catch (error) {
                leadMessage.textContent = error.message;
                leadMessage.classList.add('error');
                submit.disabled = false;
            }
        });
    }

    if (countdown) {
        let end = parseInt(localStorage.getItem('vsl_offer_end') || '0', 10);
        if (!end || end < Date.now()) {
            end = Date.now() + 24 * 60 * 60 * 1000;
            localStorage.setItem('vsl_offer_end', String(end));
        }

        const pad = (value) => String(value).padStart(2, '0');
        const render = () => {
            const diff = Math.max(0, end - Date.now());
            const hours = Math.floor(diff / 3600000);
            const minutes = Math.floor((diff % 3600000) / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);
            countdown.innerHTML = `
                <span>${pad(hours)}<small>horas</small></span>
                <span>${pad(minutes)}<small>min</small></span>
                <span>${pad(seconds)}<small>seg</small></span>
            `;
        };
        render();
        setInterval(render, 1000);
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('open')) {
            closeModal();
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', refreshIcons);
    } else {
        refreshIcons();
    }
    window.addEventListener('load', refreshIcons);
})();
