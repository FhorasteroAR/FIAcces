/**
 * FIAcces — Frontend
 * Cumple WCAG 2.1 AA
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'fiacces_prefs';
    var settings    = (window.FIAcces && window.FIAcces.settings) || {};
    var i18n        = (window.FIAcces && window.FIAcces.i18n) || {};

    // Estado actual del usuario
    var state = {
        textScale: 1,
        contrast: '',   // '', 'high', 'inverted', 'gray'
        colorblind: '', // '', 'protanopia', 'deuteranopia', 'tritanopia'
        dyslexia: false,
        underline: false,
        pauseAnim: false,
        cursor: ''      // '', 'large', 'xl'
    };

    var root, fab, panel, closeBtn, resetBtn, announceEl;
    var lastFocus = null;
    var pausedVideos = [];

    // -------- Persistencia --------
    function loadState() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (raw) Object.assign(state, JSON.parse(raw));
        } catch (e) {
            // localStorage bloqueado: usar cookie fallback
            var match = document.cookie.match(/(?:^|;\s*)fiacces_prefs=([^;]+)/);
            if (match) {
                try { Object.assign(state, JSON.parse(decodeURIComponent(match[1]))); } catch (_) {}
            }
        }
    }

    function saveState() {
        var data = JSON.stringify(state);
        try {
            localStorage.setItem(STORAGE_KEY, data);
        } catch (e) {
            // Fallback a cookies (1 año)
            var expires = new Date();
            expires.setFullYear(expires.getFullYear() + 1);
            document.cookie = 'fiacces_prefs=' + encodeURIComponent(data) +
                              '; expires=' + expires.toUTCString() +
                              '; path=/; SameSite=Lax';
        }
    }

    // -------- Aplicar estado al DOM --------
    function applyState() {
        var html = document.documentElement;

        // Limpiar clases previas
        html.className = html.className.replace(/fiacces-(contrast|cursor|daltonism)-\S+/g, '')
                                       .replace(/fiacces-(dyslexia|underline-links|pause-animations)/g, '')
                                       .replace(/\s+/g, ' ').trim();

        html.style.setProperty('--fiacces-text-scale', state.textScale);
        applyTextScale(state.textScale);

        if (state.contrast)   html.classList.add('fiacces-contrast-' + state.contrast);
        if (state.colorblind) html.classList.add('fiacces-daltonism-' + state.colorblind);
        if (state.cursor)     html.classList.add('fiacces-cursor-' + state.cursor);
        if (state.dyslexia)  html.classList.add('fiacces-dyslexia');
        if (state.underline) html.classList.add('fiacces-underline-links');
        if (state.pauseAnim) html.classList.add('fiacces-pause-animations');

        applyAnimationPause();
        syncUI();
    }

    // -------- Escala de texto por elemento --------
    // Escalar el font-size del <html> solo afecta a textos en rem/em. Muchos temas
    // fijan el tamaño en px, por lo que recorremos los elementos y multiplicamos su
    // font-size computado real, guardando el valor base en un data-attribute.
    function applyTextScale(scale) {
        if (!document.body) return;
        var nodes = document.body.querySelectorAll('*');
        for (var i = 0; i < nodes.length; i++) {
            var el = nodes[i];
            // No tocar la propia UI del widget
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
                el.style.fontSize = '';
            } else {
                el.style.fontSize = (base * scale) + 'px';
            }
        }
    }

    function applyAnimationPause() {
        var videos = document.querySelectorAll('video');
        if (state.pauseAnim) {
            videos.forEach(function (v) {
                if (!v.paused) {
                    pausedVideos.push(v);
                    try { v.pause(); } catch (_) {}
                }
            });
        } else {
            pausedVideos.forEach(function (v) {
                try { v.play().catch(function () {}); } catch (_) {}
            });
            pausedVideos = [];
        }
    }

    // -------- Sincronizar UI con el estado --------
    function syncUI() {
        if (!panel) return;

        // Display del % de texto
        var scaleDisplay = panel.querySelector('[data-display="text-scale"]');
        if (scaleDisplay) scaleDisplay.textContent = Math.round(state.textScale * 100) + '%';

        // Toggles de contraste
        panel.querySelectorAll('[data-action="contrast"]').forEach(function (btn) {
            btn.setAttribute('aria-pressed', btn.dataset.value === state.contrast ? 'true' : 'false');
        });

        // Toggles de daltonismo
        panel.querySelectorAll('[data-action="colorblind"]').forEach(function (btn) {
            btn.setAttribute('aria-pressed', btn.dataset.value === state.colorblind ? 'true' : 'false');
        });

        // Toggles de cursor
        panel.querySelectorAll('[data-action="cursor"]').forEach(function (btn) {
            btn.setAttribute('aria-pressed', btn.dataset.value === state.cursor ? 'true' : 'false');
        });

        // Switches
        var dys = panel.querySelector('[data-action="dyslexia"]');
        if (dys) dys.checked = state.dyslexia;

        var und = panel.querySelector('[data-action="underline"]');
        if (und) und.checked = state.underline;

        var pa = panel.querySelector('[data-action="pause-anim"]');
        if (pa) pa.checked = state.pauseAnim;
    }

    // -------- Anuncios para lectores de pantalla --------
    function announce(text) {
        if (!announceEl) return;
        announceEl.textContent = '';
        // truco para forzar re-anuncio
        setTimeout(function () { announceEl.textContent = text; }, 50);
    }

    // -------- Acciones --------
    function handleAction(action, value) {
        switch (action) {
            case 'text-increase':
                state.textScale = Math.min(2, state.textScale + 0.1);
                break;
            case 'text-decrease':
                state.textScale = Math.max(0.8, state.textScale - 0.1);
                break;
            case 'contrast':
                state.contrast = (state.contrast === value) ? '' : value;
                break;
            case 'colorblind':
                state.colorblind = (state.colorblind === value) ? '' : value;
                break;
            case 'cursor':
                state.cursor = (state.cursor === value) ? '' : value;
                break;
            case 'dyslexia':
                state.dyslexia = !state.dyslexia;
                break;
            case 'underline':
                state.underline = !state.underline;
                break;
            case 'pause-anim':
                state.pauseAnim = !state.pauseAnim;
                break;
            case 'reset':
                state = { textScale: 1, contrast: '', colorblind: '', dyslexia: false, underline: false, pauseAnim: false, cursor: '' };
                break;
        }
        applyState();
        saveState();
        announce(i18n.applied || 'Aplicado');
    }

    // -------- Modal: abrir / cerrar --------
    function openPanel() {
        if (!panel) return;
        lastFocus = document.activeElement;
        panel.hidden = false;
        fab.setAttribute('aria-expanded', 'true');
        // Foco al primer elemento interactivo del panel
        setTimeout(function () {
            var first = panel.querySelector('button, [tabindex]:not([tabindex="-1"]), input');
            if (first) first.focus();
        }, 50);
        announce(i18n.announce_open || 'Panel abierto');
    }

    function closePanel() {
        if (!panel || panel.hidden) return;
        panel.hidden = true;
        fab.setAttribute('aria-expanded', 'false');
        if (lastFocus && lastFocus.focus) lastFocus.focus();
        announce(i18n.announce_close || 'Panel cerrado');
    }

    // -------- Focus trap --------
    function trapFocus(e) {
        if (panel.hidden) return;
        if (e.key !== 'Tab') return;

        var focusable = panel.querySelectorAll(
            'button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        if (focusable.length === 0) return;

        var first = focusable[0];
        var last  = focusable[focusable.length - 1];

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    // -------- Init --------
    function init() {
        root       = document.getElementById('fiacces-root');
        if (!root) return;

        fab        = document.getElementById('fiacces-toggle');
        panel      = document.getElementById('fiacces-panel');
        closeBtn   = document.getElementById('fiacces-close');
        resetBtn   = document.getElementById('fiacces-reset');
        announceEl = document.getElementById('fiacces-announce');

        loadState();
        applyState();

        // Aplicar color primario desde admin
        if (settings.primaryColor) {
            root.style.setProperty('--fiacces-primary', settings.primaryColor);
        }

        // Toggle del FAB
        fab.addEventListener('click', function () {
            if (panel.hidden) openPanel(); else closePanel();
        });

        // Cerrar (solo con la X o el botón Cerrar, NO al hacer clic fuera)
        if (closeBtn) closeBtn.addEventListener('click', closePanel);

        // Reset
        if (resetBtn) resetBtn.addEventListener('click', function () { handleAction('reset'); });

        // Botones con data-action / data-value
        panel.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-action]');
            if (!btn || btn === resetBtn) return;
            if (btn.tagName === 'INPUT') return; // los checkbox tienen su propio handler
            var action = btn.dataset.action;
            var value  = btn.dataset.value || '';
            handleAction(action, value);
        });

        // Checkboxes (switches)
        panel.addEventListener('change', function (e) {
            if (e.target.tagName === 'INPUT' && e.target.dataset.action) {
                handleAction(e.target.dataset.action);
            }
        });

        // Teclado: Escape cierra el panel
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !panel.hidden) {
                e.preventDefault();
                closePanel();
            }
            // Focus trap
            if (!panel.hidden) trapFocus(e);

            // Atajo Alt + tecla configurada
            if (e.altKey && !e.ctrlKey && !e.shiftKey && !e.metaKey) {
                var key = (settings.shortcutKey || 'A').toUpperCase();
                if (e.key.toUpperCase() === key) {
                    e.preventDefault();
                    if (panel.hidden) openPanel(); else closePanel();
                }
            }
        });

        // Re-aplicar ajustes si entra contenido nuevo al DOM (AJAX, sliders, etc.)
        if (window.MutationObserver) {
            var reapplyTimer = null;
            var observer = new MutationObserver(function () {
                if (state.pauseAnim) applyAnimationPause();
                // Debounce: re-escalar el texto solo si hay una escala activa
                if (state.textScale !== 1) {
                    clearTimeout(reapplyTimer);
                    reapplyTimer = setTimeout(function () {
                        applyTextScale(state.textScale);
                    }, 150);
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
