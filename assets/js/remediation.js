/**
 * FIAcces — Ayudas de conformidad (remediación de cliente)
 *
 * Aplica correcciones WCAG seguras y mecánicas sobre el frontend del sitio.
 * NO garantiza conformidad: los criterios que exigen juicio humano (alt
 * significativos, subtítulos, contraste de marca, encabezados con sentido)
 * deben revisarse manualmente. Ver docs/wcag-2.1-fia.md.
 */
(function () {
    'use strict';

    var cfg   = window.FIAccesRemediation || {};
    var flags = cfg.flags || {};
    var i18n  = cfg.i18n || {};

    var SR_ONLY = 'position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;' +
                  'overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;';

    // 3.1.1 — Idioma de la página
    function fixLang() {
        var html = document.documentElement;
        var lang = (html.getAttribute('lang') || '').trim();
        if (!lang && cfg.lang) {
            html.setAttribute('lang', cfg.lang);
        }
    }

    // 2.4.1 — Asegurar destino del enlace "Saltar al contenido"
    function fixSkipTarget() {
        if (document.getElementById('fiacces-main-content')) return;
        var main = document.querySelector('main, [role="main"], #main, #content, #primary, .site-main');
        if (!main) {
            var h1 = document.querySelector('h1');
            main = h1 ? (h1.closest('section, article, div') || h1) : null;
        }
        if (main) {
            main.id = main.id || 'fiacces-main-content';
            if (!main.hasAttribute('tabindex')) main.setAttribute('tabindex', '-1');
        }
    }

    // 1.1.1 — Aplicar alt corregidos por el administrador (por ruta de la imagen)
    function applyAltOverrides() {
        var map = cfg.imgAltMap;
        if (!map) return;
        var imgs = document.querySelectorAll('img');
        for (var i = 0; i < imgs.length; i++) {
            var img = imgs[i];
            var src = img.currentSrc || img.getAttribute('src') || '';
            var path;
            try { path = new URL(src, window.location.href).pathname; }
            catch (e) { path = src; }
            if (Object.prototype.hasOwnProperty.call(map, path)) {
                img.setAttribute('alt', map[path]);
            }
        }
    }

    // 1.1.1 (parcial) — Imágenes sin alt: marcarlas como decorativas
    function fixImgAlt() {
        var imgs = document.querySelectorAll('img:not([alt])');
        for (var i = 0; i < imgs.length; i++) {
            imgs[i].setAttribute('alt', '');
        }
    }

    // 2.4.4 / seguridad — Enlaces que abren en nueva pestaña
    function fixExternalLinks() {
        var links = document.querySelectorAll('a[target="_blank"]');
        for (var i = 0; i < links.length; i++) {
            var a = links[i];

            // rel seguro
            var rel = (a.getAttribute('rel') || '').split(/\s+/).filter(Boolean);
            if (rel.indexOf('noopener') === -1) rel.push('noopener');
            if (rel.indexOf('noreferrer') === -1) rel.push('noreferrer');
            a.setAttribute('rel', rel.join(' '));

            // Aviso accesible de "nueva pestaña" (si no lo tiene ya)
            if (a.getAttribute('data-fiacces-newtab')) continue;
            var label = (a.getAttribute('aria-label') || a.textContent || '').toLowerCase();
            if (i18n.newTab && label.indexOf('pestaña') === -1 && label.indexOf('ventana') === -1) {
                var span = document.createElement('span');
                span.textContent = ' ' + i18n.newTab;
                span.setAttribute('style', SR_ONLY);
                a.appendChild(span);
                a.setAttribute('data-fiacces-newtab', '1');
            }
        }
    }

    // 4.1.2 — Landmarks de navegación sin nombre accesible
    function fixNavLabels() {
        var navs = document.querySelectorAll('nav:not([aria-label]):not([aria-labelledby])');
        for (var i = 0; i < navs.length; i++) {
            if (i18n.nav) navs[i].setAttribute('aria-label', i18n.nav);
        }
    }

    function run() {
        try { applyAltOverrides(); } catch (e) {}
        try { if (flags.langAttr)      fixLang(); } catch (e) {}
        try { if (flags.skipLink)      fixSkipTarget(); } catch (e) {}
        try { if (flags.imgAlt)        fixImgAlt(); } catch (e) {}
        try { if (flags.externalLinks) fixExternalLinks(); } catch (e) {}
        try { if (flags.navLabels)     fixNavLabels(); } catch (e) {}
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
