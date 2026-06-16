(function () {
    const leadForm = document.getElementById('leadForm');
    const leadMessage = document.getElementById('leadMessage');
    const buyButton = document.getElementById('buyButton');
    let leadId = null;

    if (leadForm) {
        leadForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            leadMessage.textContent = 'Enviando...';
            leadMessage.className = 'form-message';

            try {
                const response = await fetch('api/lead.php', {
                    method: 'POST',
                    body: new FormData(leadForm),
                    credentials: 'same-origin'
                });
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Não foi possível cadastrar.');
                }
                leadId = data.lead_id;
                leadMessage.textContent = 'Cadastro recebido. Você já pode continuar.';
                leadMessage.classList.add('success');
            } catch (error) {
                leadMessage.textContent = error.message;
                leadMessage.classList.add('error');
            }
        });
    }

    if (buyButton) {
        buyButton.addEventListener('click', async function () {
            buyButton.disabled = true;
            const formData = new FormData();
            formData.append('button_id', buyButton.dataset.buttonId || 'main_buy');
            formData.append('visitor_uuid', buyButton.dataset.visitorUuid || '');
            formData.append('headline_id', buyButton.dataset.headlineId || '');
            formData.append('offer_id', buyButton.dataset.offerId || '');
            if (leadId) {
                formData.append('lead_id', leadId);
            }

            try {
                const response = await fetch('api/click.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await response.json();
                if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                    return;
                }
                throw new Error(data.message || 'Não foi possível abrir a oferta.');
            } catch (error) {
                alert(error.message);
                buyButton.disabled = false;
            }
        });
    }
})();

