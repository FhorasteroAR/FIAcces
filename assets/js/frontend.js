/**
 * FIAcces — Barra de accesibilidad para el usuario final.
 *
 * Aplica y persiste (en el navegador del visitante) los ajustes de: tamaño de
 * texto, contraste, filtros de daltonismo, legibilidad, pausa de animaciones y
 * cursor. No hay interacción con el servidor ni con el administrador.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'fiacces_prefs';
    var cfg  = window.FIAcces || {};
    var i18n = cfg.i18n || {};

    function t(key, fallback) { return i18n[key] || fallback; }

    var defaults = {
        textScale: 1,
        contrast: '',    // '', 'high', 'inverted', 'gray'
        colorblind: '',  // '', 'protanopia', 'deuteranopia', 'tritanopia'
        dyslexia: false,
        underline: false,
        pauseAnim: false,
        cursor: ''       // '', 'large'
    };
    var state = Object.assign({}, defaults);

    var root, fab, panel, closeBtn, announceEl;
    var lastFocus = null;
    var pausedVideos = [];

    // -------- Persistencia (localStorage con respaldo en cookie) --------
    function loadState() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (raw) Object.assign(state, JSON.parse(raw));
        } catch (e) {
            var m = document.cookie.match(/(?:^|;\s*)fiacces_prefs=([^;]+)/);
            if (m) { try { Object.assign(state, JSON.parse(decodeURIComponent(m[1]))); } catch (_) {} }
        }
    }

    function saveState() {
        var data = JSON.stringify(state);
        try {
            localStorage.setItem(STORAGE_KEY, data);
        } catch (e) {
            var exp = new Date();
            exp.setFullYear(exp.getFullYear() + 1);
            document.cookie = 'fiacces_prefs=' + encodeURIComponent(data) +
                '; expires=' + exp.toUTCString() + '; path=/; SameSite=Lax';
        }
    }

    // -------- Escala de texto por elemento --------
    // Muchos temas fijan el font-size en px (a veces con !important). Recorremos
    // cada elemento, guardamos su tamaño base una sola vez y aplicamos la escala
    // con prioridad 'important' para ganar al CSS del tema.
    function applyTextScale(scale) {
        if (!document.body) return;
        var nodes = document.body.querySelectorAll('*');
        for (var i = 0; i < nodes.length; i++) {
            var el = nodes[i];
            if (el.closest && el.closest('#fiacces-root')) continue;

            var base = el.getAttribute('data-fiacces-base-font');
            if (base === null) {
                base = parseFloat(window.getComputedStyle(el).fontSize);
                if (!base || isNaN(base)) continue;
                el.setAttribute('data-fiacces-base-font', base);
            } else {
                base = parseFloat(base);
            }

            if (scale === 1) {
                el.style.removeProperty('font-size');
            } else {
                el.style.setProperty('font-size', (base * scale) + 'px', 'important');
            }
        }
    }

    // -------- Pausa de animaciones/vídeos --------
    function applyAnimationPause() {
        var videos = document.querySelectorAll('video');
        if (state.pauseAnim) {
            videos.forEach(function (v) {
                if (!v.paused) { pausedVideos.push(v); try { v.pause(); } catch (_) {} }
            });
        } else {
            pausedVideos.forEach(function (v) { try { v.play().catch(function () {}); } catch (_) {} });
            pausedVideos = [];
        }
    }

    // -------- Aplicar todo el estado al DOM --------
    function applyState() {
        var h = document.documentElement;
        h.className = h.className
            .replace(/fiacces-(contrast|cursor|daltonism)-\S+/g, '')
            .replace(/fiacces-(dyslexia|underline-links|pause-animations)/g, '')
            .replace(/\s+/g, ' ').trim();

        if (state.contrast)   h.classList.add('fiacces-contrast-' + state.contrast);
        if (state.colorblind) h.classList.add('fiacces-daltonism-' + state.colorblind);
        if (state.cursor)     h.classList.add('fiacces-cursor-' + state.cursor);
        if (state.dyslexia)   h.classList.add('fiacces-dyslexia');
        if (state.underline)  h.classList.add('fiacces-underline-links');
        if (state.pauseAnim)  h.classList.add('fiacces-pause-animations');

        applyTextScale(state.textScale);
        applyAnimationPause();
        syncUI();
    }

    // -------- Reflejar el estado en los controles del panel --------
    function syncUI() {
        if (!panel) return;

        var disp = panel.querySelector('[data-display="text-scale"]');
        if (disp) disp.textContent = Math.round(state.textScale * 100) + '%';

        panel.querySelectorAll('[data-action="contrast"]').forEach(function (b) {
            b.setAttribute('aria-pressed', b.dataset.value === state.contrast ? 'true' : 'false');
        });
        panel.querySelectorAll('[data-action="colorblind"]').forEach(function (b) {
            b.setAttribute('aria-pressed', b.dataset.value === state.colorblind ? 'true' : 'false');
        });

        var map = { dyslexia: 'dyslexia', underline: 'underline', 'pause-anim': 'pauseAnim', cursor: 'cursor' };
        Object.keys(map).forEach(function (action) {
            var input = panel.querySelector('input[data-action="' + action + '"]');
            if (!input) return;
            var val = state[map[action]];
            input.checked = action === 'cursor' ? val === 'large' : !!val;
        });
    }

    function announce(text) {
        if (!announceEl) return;
        announceEl.textContent = '';
        setTimeout(function () { announceEl.textContent = text; }, 50);
    }

    // -------- Acciones del panel --------
    function handleAction(action) {
        switch (action.type) {
            case 'text-increase': state.textScale = Math.min(2, +(state.textScale + 0.1).toFixed(2)); break;
            case 'text-decrease': state.textScale = Math.max(0.8, +(state.textScale - 0.1).toFixed(2)); break;
            case 'contrast':      state.contrast = state.contrast === action.value ? '' : action.value; break;
            case 'colorblind':    state.colorblind = state.colorblind === action.value ? '' : action.value; break;
            case 'dyslexia':      state.dyslexia = !state.dyslexia; break;
            case 'underline':     state.underline = !state.underline; break;
            case 'pause-anim':    state.pauseAnim = !state.pauseAnim; break;
            case 'cursor':        state.cursor = state.cursor === 'large' ? '' : 'large'; break;
            case 'reset':         state = Object.assign({}, defaults); break;
        }
        applyState();
        saveState();
        announce(t('applied', 'Ajuste aplicado'));
    }

    // -------- Abrir / cerrar panel --------
    function openPanel() {
        lastFocus = document.activeElement;
        panel.hidden = false;
        fab.setAttribute('aria-expanded', 'true');
        setTimeout(function () {
            var first = panel.querySelector('button, input, [tabindex]:not([tabindex="-1"])');
            if (first) first.focus();
        }, 30);
        announce(t('announce_open', 'Panel de accesibilidad abierto'));
    }

    function closePanel() {
        if (panel.hidden) return;
        panel.hidden = true;
        fab.setAttribute('aria-expanded', 'false');
        if (lastFocus && lastFocus.focus) lastFocus.focus();
        announce(t('announce_close', 'Panel de accesibilidad cerrado'));
    }

    function trapFocus(e) {
        if (panel.hidden || e.key !== 'Tab') return;
        var f = panel.querySelectorAll('button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])');
        if (!f.length) return;
        var first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }

    // -------- Inicialización --------
    function init() {
        root = document.getElementById('fiacces-root');
        if (!root) return;
        fab        = document.getElementById('fiacces-toggle');
        panel      = document.getElementById('fiacces-panel');
        closeBtn   = document.getElementById('fiacces-close');
        announceEl = document.getElementById('fiacces-announce');

        loadState();
        applyState();

        fab.addEventListener('click', function () { panel.hidden ? openPanel() : closePanel(); });
        if (closeBtn) closeBtn.addEventListener('click', closePanel);

        panel.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-action]');
            if (!btn || btn.tagName === 'INPUT') return;
            handleAction({ type: btn.dataset.action, value: btn.dataset.value || '' });
        });

        panel.addEventListener('change', function (e) {
            if (e.target.tagName === 'INPUT' && e.target.dataset.action) {
                handleAction({ type: e.target.dataset.action });
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !panel.hidden) { e.preventDefault(); closePanel(); }
            if (!panel.hidden) trapFocus(e);
            if (e.altKey && !e.ctrlKey && !e.shiftKey && !e.metaKey) {
                var key = (cfg.shortcutKey || 'A').toUpperCase();
                if (e.key.toUpperCase() === key) {
                    e.preventDefault();
                    panel.hidden ? openPanel() : closePanel();
                }
            }
        });

        // Re-aplicar ajustes al contenido cargado dinámicamente (AJAX, sliders…).
        if (window.MutationObserver) {
            var timer = null;
            new MutationObserver(function () {
                if (state.pauseAnim) applyAnimationPause();
                if (state.textScale !== 1) {
                    clearTimeout(timer);
                    timer = setTimeout(function () { applyTextScale(state.textScale); }, 150);
                }
            }).observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
