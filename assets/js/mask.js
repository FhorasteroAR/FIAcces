/**
 * FIAcces — Máscara de auditoría del orden de foco (solo administradores)
 *
 * Superpone insignias numeradas sobre cada elemento enfocable en el orden real
 * de tabulación (tabindex positivo primero, luego el orden del DOM). Marca en
 * ámbar los tabindex positivos y en rojo los elementos que parecen interactivos
 * (onclick/role) pero NO son enfocables por teclado.
 *
 * Es una ayuda de auditoría visual: no modifica el marcado del sitio ni
 * sustituye una revisión manual con teclado y lector de pantalla.
 */
(function () {
    'use strict';

    var cfg  = window.FIAccesMask || {};
    var i18n = cfg.i18n || {};

    function t(key, fallback) { return i18n[key] || fallback; }

    var maskLayer = null;

    var FOCUSABLE_SEL = 'a[href], button, input:not([type="hidden"]), select, textarea, ' +
        '[tabindex], [contenteditable="true"], audio[controls], video[controls], summary';

    function isVisible(el) {
        if (el.disabled) return false;
        var r = el.getClientRects();
        if (!r.length) return false;
        var cs = getComputedStyle(el);
        return cs.visibility !== 'hidden' && cs.display !== 'none';
    }

    function focusableInOrder() {
        var els = Array.prototype.slice.call(document.querySelectorAll(FOCUSABLE_SEL))
            .filter(function (el) {
                if (el.closest('#fiacces-root') || el.closest('.fiacces-mask-toggle')) return false;
                var ti = el.getAttribute('tabindex');
                if (ti !== null && parseInt(ti, 10) < 0) return false;
                return isVisible(el);
            });
        // Orden Tab: tabindex positivo primero (asc), luego orden del DOM
        var positive = [], natural = [];
        els.forEach(function (el) {
            var ti = parseInt(el.getAttribute('tabindex'), 10);
            if (ti > 0) positive.push(el); else natural.push(el);
        });
        positive.sort(function (a, b) {
            return parseInt(a.getAttribute('tabindex'), 10) - parseInt(b.getAttribute('tabindex'), 10);
        });
        return positive.concat(natural);
    }

    // Elementos que parecen interactivos pero NO son enfocables
    function clickableNotFocusable(focusSet) {
        var candidates = document.querySelectorAll('[onclick], [role="button"], [role="link"], [role="tab"], [role="menuitem"]');
        var out = [];
        Array.prototype.forEach.call(candidates, function (el) {
            if (el.closest('#fiacces-root') || el.closest('.fiacces-mask-toggle')) return;
            if (focusSet.indexOf(el) !== -1) return;
            var ti = el.getAttribute('tabindex');
            if (ti !== null && parseInt(ti, 10) >= 0) return;
            if (isVisible(el)) out.push(el);
        });
        return out;
    }

    function clearMask() {
        if (maskLayer) { maskLayer.remove(); maskLayer = null; }
    }

    function badge(el, text, cls) {
        var r = el.getBoundingClientRect();
        var b = document.createElement('span');
        b.className = 'fiacces-mask__badge ' + cls;
        b.textContent = text;
        b.style.top = (r.top + window.scrollY) + 'px';
        b.style.left = (r.left + window.scrollX) + 'px';
        maskLayer.appendChild(b);
        var box = document.createElement('span');
        box.className = 'fiacces-mask__box ' + cls;
        box.style.top = (r.top + window.scrollY) + 'px';
        box.style.left = (r.left + window.scrollX) + 'px';
        box.style.width = r.width + 'px';
        box.style.height = r.height + 'px';
        maskLayer.appendChild(box);
    }

    function draw() {
        var order = focusableInOrder();
        order.forEach(function (el, i) {
            var ti = parseInt(el.getAttribute('tabindex'), 10);
            var cls = ti > 0 ? 'fiacces-mask--warn' : 'fiacces-mask--ok';
            badge(el, String(i + 1) + (ti > 0 ? ' (tabindex ' + ti + ')' : ''), cls);
        });
        clickableNotFocusable(order).forEach(function (el) {
            badge(el, t('not_focusable', '⚠ no enfocable'), 'fiacces-mask--err');
        });
    }

    function toggleMask() {
        if (maskLayer) { clearMask(); return; }
        maskLayer = document.createElement('div');
        maskLayer.className = 'fiacces-mask';
        document.body.appendChild(maskLayer);
        draw();
    }

    function buildUI() {
        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'fiacces-mask-toggle';
        toggle.textContent = t('toggle', 'Máscara de foco (Tab)');
        toggle.setAttribute('aria-pressed', 'false');
        toggle.addEventListener('click', function () {
            toggleMask();
            toggle.setAttribute('aria-pressed', maskLayer ? 'true' : 'false');
        });
        document.body.appendChild(toggle);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', buildUI);
    } else {
        buildUI();
    }
})();
