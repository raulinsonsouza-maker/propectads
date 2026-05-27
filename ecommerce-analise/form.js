(function () {
    const form = document.getElementById('lead-form');
    if (!form) return;

    const whatsappInput = form.querySelector('#whatsapp');
    const errorEl = form.querySelector('.lead-form__error');
    const submitBtn = form.querySelector('.btn--submit');
    const formCard = form.closest('.ecommerce-form-card');

    function maskWhatsApp(value) {
        const digits = value.replace(/\D/g, '').slice(0, 11);
        if (digits.length <= 2) return digits;
        if (digits.length <= 7) return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
    }

    if (whatsappInput) {
        whatsappInput.addEventListener('input', (e) => {
            e.target.value = maskWhatsApp(e.target.value);
        });
    }

    const investimentoSelect = form.querySelector('#investimento');
    if (investimentoSelect) {
        const syncInvestimentoStyle = () => {
            investimentoSelect.classList.toggle('is-placeholder', investimentoSelect.value === '');
        };
        syncInvestimentoStyle();
        investimentoSelect.addEventListener('change', syncInvestimentoStyle);
    }

    function showError(message) {
        if (!errorEl) return;
        errorEl.textContent = message;
        errorEl.classList.add('visible');
    }

    function hideError() {
        if (!errorEl) return;
        errorEl.classList.remove('visible');
    }

    function getDigits(value) {
        return value.replace(/\D/g, '');
    }

    function validate() {
        const nome = form.nome.value.trim();
        const whatsapp = getDigits(form.whatsapp.value);
        const loja = form.loja.value.trim();
        const investimento = form.investimento.value;

        if (!nome) return 'Informe seu nome.';
        if (whatsapp.length < 10) return 'Informe um WhatsApp válido.';
        if (!loja) return 'Informe a URL do seu e-commerce ou loja virtual.';
        if (!investimento) return 'Selecione quanto você investe em anúncios por mês.';
        return null;
    }

    function showSuccess() {
        form.innerHTML = `
            <div class="lead-form__success">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="currentColor"/>
                </svg>
                <h3>Solicitação recebida</h3>
                <p>Em breve nossa equipe entra em contato pelo WhatsApp com a análise da sua loja e as prioridades para crescer.</p>
            </div>
        `;
        if (formCard) {
            formCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    document.querySelectorAll('[data-scroll-analise]').forEach((el) => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.getElementById('analise');
            if (target) {
                const offset = 90;
                const top = target.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError();

        const validationError = validate();
        if (validationError) {
            showError(validationError);
            return;
        }

        const investimento = form.investimento.value;
        const payload = {
            nome: form.nome.value.trim(),
            whatsapp: getDigits(form.whatsapp.value),
            loja: form.loja.value.trim(),
            investimento,
            website: form.website.value,
        };

        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';

        try {
            const res = await fetch('../api/submit-lead.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Não foi possível enviar. Tente novamente.');
            }

            if (typeof gtag === 'function') {
                gtag('event', 'generate_lead', {
                    event_category: 'ecommerce_analise',
                    event_label: investimento,
                });
            }

            showSuccess();
        } catch (err) {
            showError(err.message || 'Erro ao enviar. Tente novamente ou fale pelo WhatsApp.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });

    document.querySelectorAll('.ecommerce-faq .faq__item').forEach((item) => {
        const question = item.querySelector('.faq__question');
        if (!question) return;

        question.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            document.querySelectorAll('.ecommerce-faq .faq__item').forEach((el) => {
                el.classList.remove('active');
            });
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });
})();
