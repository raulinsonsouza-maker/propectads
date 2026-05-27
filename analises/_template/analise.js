(function () {
    'use strict';

    function animateCount(el, target, duration) {
        if (!el) return;
        if (target <= 0) {
            el.textContent = '0';
            return;
        }
        var start = 0;
        var startTime = null;
        function step(ts) {
            if (!startTime) startTime = ts;
            var p = Math.min((ts - startTime) / duration, 1);
            el.textContent = Math.round(start + (target - start) * p);
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    function setupAnimatedBars(container, barSelector) {
        if (!container) return;

        container.querySelectorAll(barSelector).forEach(function (bar) {
            var w = bar.style.width;
            bar.style.width = '0';
            bar.style.setProperty('--bar-width', w);
        });

        var obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (!e.isIntersecting) return;
                container.classList.add('is-visible');
                container.querySelectorAll(barSelector).forEach(function (bar) {
                    bar.style.width = bar.style.getPropertyValue('--bar-width') || bar.getAttribute('data-width') || '0';
                });
                obs.unobserve(container);
            });
        }, { threshold: 0.3 });

        obs.observe(container);
    }

    var ring = document.querySelector('.analise-score-ring');
    if (ring) {
        var score = parseInt(ring.getAttribute('data-score') || '0', 10);
        var fg = ring.querySelector('.analise-score-ring__fg');
        var num = ring.querySelector('.analise-score-ring__num');
        var circ = 326.73;
        var offset = circ - (circ * score / 100);
        requestAnimationFrame(function () {
            if (fg) fg.style.strokeDashoffset = String(offset);
            animateCount(num, score, 1200);
        });
    }

    var heroMaturidade = document.querySelector('.analise-hero__maturidade-card');
    if (heroMaturidade) {
        setupAnimatedBars(heroMaturidade, '.analise-hero__mini-bar span');
        heroMaturidade.querySelectorAll('[data-count]').forEach(function (countEl) {
            var target = parseInt(countEl.getAttribute('data-count') || '0', 10);
            animateCount(countEl, target, 900);
        });
    }

    document.querySelectorAll('.analise-score-card').forEach(function (card) {
        var bar = card.querySelector('.analise-score-card__bar span');
        var countEl = card.querySelector('[data-count]');
        if (bar) {
            var w = bar.style.width;
            bar.style.width = '0';
            card.style.setProperty('--bar-width', w);
        }
        var obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) {
                    card.classList.add('is-visible');
                    if (bar) bar.style.width = card.style.getPropertyValue('--bar-width') || bar.getAttribute('data-width') || '0';
                    if (countEl) {
                        var t = parseInt(countEl.getAttribute('data-count') || '0', 10);
                        animateCount(countEl, t, 800);
                    }
                    obs.unobserve(card);
                }
            });
        }, { threshold: 0.3 });
        obs.observe(card);
    });

    var lightbox = document.querySelector('.analise-lightbox');
    var lbImg = lightbox && lightbox.querySelector('img');
    var lbCap = lightbox && lightbox.querySelector('figcaption');
    var lbClose = lightbox && lightbox.querySelector('.analise-lightbox__close');

    function openLightbox(src, caption) {
        if (!lightbox || !lbImg) return;
        lbImg.src = src;
        lbImg.alt = caption || '';
        if (lbCap) lbCap.textContent = caption || '';
        lightbox.hidden = false;
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        if (!lightbox) return;
        lightbox.hidden = true;
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (lbImg) lbImg.src = '';
    }

    document.querySelectorAll('[data-lightbox]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openLightbox(btn.getAttribute('data-lightbox'), btn.getAttribute('data-caption'));
        });
    });

    if (lbClose) lbClose.addEventListener('click', closeLightbox);
    if (lightbox) {
        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox) closeLightbox();
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeLightbox();
    });

    document.querySelectorAll('.analise-header__nav a').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var id = a.getAttribute('href');
            if (id && id.charAt(0) === '#') {
                var el = document.querySelector(id);
                if (el) {
                    e.preventDefault();
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });
})();
