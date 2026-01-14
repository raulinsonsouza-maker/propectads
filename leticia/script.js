// Configuração do WhatsApp
const WHATSAPP_CONFIG = {
    // CONFIGURE AQUI O NÚMERO DO WHATSAPP (apenas números, sem espaços ou caracteres especiais)
    // Exemplo: '5511999999999' (código do país + DDD + número)
    number: '5519994703684',
    
    // Mensagens pré-formatadas para cada tipo de CTA
    messages: {
        hero: 'Olá! Gostaria de agendar minha avaliação gratuita.',
        benefits: 'Olá! Gostaria de agendar minha avaliação gratuita.',
        evaluation: 'Olá! Gostaria de agendar minha avaliação gratuita.',
        final: 'Olá! Gostaria de agendar minha avaliação gratuita.',
        float: 'Olá! Gostaria de agendar minha avaliação gratuita.'
    }
};

// Google Ads - Conversão (Contato)
const GOOGLE_ADS_CONVERSION = {
    send_to: 'AW-17875991631/HJk0CO72keMbEM_498tC',
    value: 1.0,
    currency: 'BRL'
};

// Função para gerar link do WhatsApp
function generateWhatsAppLink(messageType) {
    const message = WHATSAPP_CONFIG.messages[messageType] || WHATSAPP_CONFIG.messages.final;
    const encodedMessage = encodeURIComponent(message);
    return `https://wa.me/${WHATSAPP_CONFIG.number}?text=${encodedMessage}`;
}

function fireGoogleAdsConversion(callback) {
    try {
        if (typeof window.gtag !== 'function') {
            if (typeof callback === 'function') callback();
            return;
        }

        let called = false;
        const safeCallback = () => {
            if (called) return;
            called = true;
            if (typeof callback === 'function') callback();
        };

        window.gtag('event', 'conversion', {
            send_to: GOOGLE_ADS_CONVERSION.send_to,
            value: GOOGLE_ADS_CONVERSION.value,
            currency: GOOGLE_ADS_CONVERSION.currency,
            event_callback: safeCallback
        });

        // Fallback: não travar a navegação se o callback não disparar
        setTimeout(safeCallback, 650);
    } catch (e) {
        if (typeof callback === 'function') callback();
    }
}

function bindConversionToWhatsAppClicks() {
    const clickTargets = document.querySelectorAll('[data-whatsapp], #whatsappFloat');

    clickTargets.forEach((el) => {
        el.addEventListener('click', (e) => {
            const href = el.getAttribute('href');
            if (!href) return;

            // Garantir que não abra duas vezes (default + callback)
            e.preventDefault();

            // Abrir a aba imediatamente (para não ser bloqueado por popup blockers)
            const newWin = window.open('', '_blank', 'noopener,noreferrer');

            fireGoogleAdsConversion(() => {
                if (newWin && !newWin.closed) {
                    newWin.location.href = href;
                } else {
                    window.location.href = href;
                }
            });
        }, { passive: false });
    });
}

// Inicializar links do WhatsApp quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    // Configurar todos os botões CTA com links do WhatsApp
    const ctaButtons = document.querySelectorAll('[data-whatsapp]');
    
    ctaButtons.forEach(button => {
        const messageType = button.getAttribute('data-whatsapp');
        const whatsappLink = generateWhatsAppLink(messageType);
        button.href = whatsappLink;
        button.target = '_blank';
        button.rel = 'noopener noreferrer';
    });
    
    // Configurar botão flutuante do WhatsApp
    const whatsappFloat = document.getElementById('whatsappFloat');
    if (whatsappFloat) {
        const whatsappLink = generateWhatsAppLink('float');
        whatsappFloat.href = whatsappLink;
        whatsappFloat.target = '_blank';
        whatsappFloat.rel = 'noopener noreferrer';
    }
    
    // Adicionar animações de entrada
    initScrollAnimations();
    
    // Scroll suave para links internos (se houver)
    initSmoothScroll();

    // Conversão no clique (WhatsApp/Contato)
    bindConversionToWhatsAppClicks();
});

// Função para animações de entrada ao fazer scroll
function initScrollAnimations() {
    // Criar Intersection Observer para detectar quando elementos entram na viewport
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                // Opcional: parar de observar após animar
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Adicionar classe fade-in aos elementos que devem ser animados
    const elementsToAnimate = document.querySelectorAll(
        '.pain-section, .frustration-section, .turnaround-section, ' +
        '.method-section, .benefits-section, .evaluation-section, ' +
        '.for-who-section, .about-section, .testimonials-section, .final-cta-section'
    );
    
    elementsToAnimate.forEach(element => {
        element.classList.add('fade-in');
        observer.observe(element);
    });
    
    // Animar listas e itens individuais
    const listItems = document.querySelectorAll('.pain-list li, .frustration-list li, .benefits-list li, .for-who-list li');
    listItems.forEach((item, index) => {
        item.classList.add('fade-in');
        item.style.transitionDelay = `${index * 0.1}s`;
        observer.observe(item);
    });
    
    // Animar steps da avaliação
    const steps = document.querySelectorAll('.step');
    steps.forEach((step, index) => {
        step.classList.add('fade-in');
        step.style.transitionDelay = `${index * 0.2}s`;
        observer.observe(step);
    });
    
    // Animar highlights
    const highlights = document.querySelectorAll('.highlight-item');
    highlights.forEach((highlight, index) => {
        highlight.classList.add('fade-in');
        highlight.style.transitionDelay = `${index * 0.1}s`;
        observer.observe(highlight);
    });
}

// Função para scroll suave
function initSmoothScroll() {
    // O scroll suave já está configurado no CSS com scroll-behavior: smooth
    // Esta função pode ser expandida para adicionar funcionalidades extras se necessário
    
    // Adicionar comportamento de scroll suave para links âncora (se houver)
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            // Só aplicar se não for um link vazio
            if (href !== '#' && href.length > 1) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
}

// Adicionar efeito de hover nos botões CTA
document.addEventListener('DOMContentLoaded', function() {
    const ctaButtons = document.querySelectorAll('.cta-button');
    
    ctaButtons.forEach(button => {
        // Adicionar feedback visual ao clicar
        button.addEventListener('click', function(e) {
            // Pequena animação de "pulse"
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
});

// Função para validar número do WhatsApp (opcional - apenas para desenvolvimento)
function validateWhatsAppNumber() {
    if (WHATSAPP_CONFIG.number === '5511999999999' || !WHATSAPP_CONFIG.number) {
        console.warn('⚠️ ATENÇÃO: Configure o número do WhatsApp no arquivo script.js na variável WHATSAPP_CONFIG.number');
        return false;
    }
    return true;
}

// Validar número ao carregar (apenas em desenvolvimento)
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    validateWhatsAppNumber();
}

// Adicionar classe para melhorar performance de animações
if ('requestAnimationFrame' in window) {
    document.documentElement.classList.add('has-raf');
}

// Prevenir comportamento padrão de formulários (se houver no futuro)
document.addEventListener('submit', function(e) {
    // Esta função pode ser expandida se formulários forem adicionados
});

