/**
 * FIAcces — Analizador de accesibilidad (solo administradores)
 *
 * Detecta incumplimientos WCAG automatizables en la página actual, los reporta
 * con guía de arreglo y permite editar/guardar el texto alt de las imágenes.
 * No sustituye una auditoría manual completa.
 */
(function () {
    'use strict';

    var cfg  = window.FIAccesScanner || {};
    var i18n = cfg.i18n || {};

    var panel, listEl, summaryEl, highlighted = null;

    // ---------- utilidades ----------
    function t(key, fallback) { return i18n[key] || fallback; }

    function accessibleName(el) {
        var name = (el.getAttribute('aria-label') || '').trim();
        if (name) return name;
        var labelledby = el.getAttribute('aria-labelledby');
        if (labelledby) {
            var ref = document.getElementById(labelledby);
            if (ref && ref.textContent.trim()) return ref.textContent.trim();
        }
        if (el.textContent && el.textContent.trim()) return el.textContent.trim();
        var img = el.querySelector('img[alt]');
        if (img && img.getAttribute('alt').trim()) return img.getAttribute('alt').trim();
        if ((el.getAttribute('title') || '').trim()) return el.getAttribute('title').trim();
        return '';
    }

    function hasLabel(field) {
        if ((field.getAttribute('aria-label') || '').trim()) return true;
        if (field.getAttribute('aria-labelledby')) return true;
        if ((field.getAttribute('title') || '').trim()) return true;
        if (field.id) {
            var lbl = document.querySelector('label[for="' + CSS.escape(field.id) + '"]');
            if (lbl) return true;
        }
        if (field.closest('label')) return true;
        return false;
    }

    // ---------- contraste ----------
    function parseColor(str) {
        var m = str && str.match(/rgba?\(([^)]+)\)/);
        if (!m) return null;
        var p = m[1].split(',').map(function (x) { return parseFloat(x); });
        return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 };
    }
    function lum(c) {
        var a = [c.r, c.g, c.b].map(function (v) {
            v /= 255;
            return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * a[0] + 0.7152 * a[1] + 0.0722 * a[2];
    }
    function ratio(c1, c2) {
        var l1 = lum(c1), l2 = lum(c2);
        return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
    }
    function effectiveBg(el) {
        var node = el;
        while (node && node.nodeType === 1) {
            var bg = parseColor(getComputedStyle(node).backgroundColor);
            if (bg && bg.a > 0) return bg;
            node = node.parentElement;
        }
        return { r: 255, g: 255, b: 255, a: 1 };
    }

    // ---------- checks ----------
    function checkImages(issues) {
        var imgs = document.querySelectorAll('img:not([alt])');
        imgs.forEach(function (img) {
            var src = img.currentSrc || img.src || '';
            issues.push({
                severity: 'error', level: 'A', criterion: '1.1.1',
                title: 'Imagen sin atributo alt',
                howto: 'Describe la imagen (o déjala vacía si es decorativa). Puedes escribir el alt aquí abajo.',
                el: img,
                edit: src ? { type: 'img_alt', key: src, current: '' } : null
            });
        });
    }

    function checkLang(issues) {
        if (!(document.documentElement.getAttribute('lang') || '').trim()) {
            issues.push({
                severity: 'error', level: 'A', criterion: '3.1.1',
                title: 'La página no declara idioma (lang)',
                howto: 'Activa "Definir el idioma de la página" en Ajustes → FIAcces, o añade lang="es" al <html> del tema.',
                el: document.documentElement, edit: null
            });
        }
    }

    function checkFormLabels(issues) {
        var fields = document.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]), select, textarea');
        fields.forEach(function (f) {
            if (!hasLabel(f)) {
                issues.push({
                    severity: 'error', level: 'A', criterion: '3.3.2',
                    title: 'Campo de formulario sin etiqueta',
                    howto: 'Añade un <label for> asociado o un aria-label descriptivo al campo.',
                    el: f, edit: null
                });
            }
        });
    }

    function checkControlNames(issues) {
        document.querySelectorAll('a[href], button').forEach(function (el) {
            if (el.closest('#fiacces-root') || el.closest('.fiacces-scanner')) return;
            if (!accessibleName(el)) {
                issues.push({
                    severity: 'error', level: 'A', criterion: '2.4.4 / 4.1.2',
                    title: (el.tagName === 'A' ? 'Enlace' : 'Botón') + ' sin texto accesible',
                    howto: 'Añade texto visible o un aria-label. Si es un icono, dale nombre con aria-label.',
                    el: el, edit: null
                });
            }
        });
    }

    function checkDuplicateIds(issues) {
        var seen = {}, dupes = {};
        document.querySelectorAll('[id]').forEach(function (el) {
            var id = el.id;
            if (seen[id]) { dupes[id] = true; } else { seen[id] = el; }
        });
        Object.keys(dupes).forEach(function (id) {
            issues.push({
                severity: 'warning', level: 'A', criterion: '4.1.1',
                title: 'id duplicado: "' + id + '"',
                howto: 'Los id deben ser únicos. Renombra los duplicados en el tema/contenido.',
                el: document.getElementById(id), edit: null
            });
        });
    }

    function checkHeadings(issues) {
        var hs = Array.prototype.slice.call(document.querySelectorAll('h1,h2,h3,h4,h5,h6'))
            .filter(function (h) { return !h.closest('#fiacces-root') && !h.closest('.fiacces-scanner'); });
        if (!document.querySelector('h1')) {
            issues.push({
                severity: 'warning', level: 'A', criterion: '1.3.1',
                title: 'La página no tiene un <h1>',
                howto: 'Cada página debe tener un encabezado principal <h1> único.',
                el: null, edit: null
            });
        }
        var prev = 0;
        hs.forEach(function (h) {
            var lvl = parseInt(h.tagName.substring(1), 10);
            if (prev && lvl > prev + 1) {
                issues.push({
                    severity: 'warning', level: 'A', criterion: '1.3.1',
                    title: 'Salto en la jerarquía de encabezados (de h' + prev + ' a h' + lvl + ')',
                    howto: 'No saltes niveles de encabezado; usa un orden consecutivo.',
                    el: h, edit: null
                });
            }
            prev = lvl;
        });
    }

    function checkNav(issues) {
        var navs = document.querySelectorAll('nav:not([aria-label]):not([aria-labelledby])');
        if (navs.length > 1) {
            navs.forEach(function (n) {
                issues.push({
                    severity: 'warning', level: 'A', criterion: '4.1.2',
                    title: 'Menú de navegación sin nombre accesible',
                    howto: 'Actívalo en Ajustes → FIAcces o añade aria-label a cada <nav>.',
                    el: n, edit: null
                });
            });
        }
    }

    function checkSkipLink(issues) {
        var first = document.querySelector('a[href^="#"]');
        var hasSkip = document.querySelector('.fiacces-skip-link') ||
            (first && /content|main|contenido|principal/i.test(first.getAttribute('href') || ''));
        if (!hasSkip) {
            issues.push({
                severity: 'review', level: 'A', criterion: '2.4.1',
                title: 'No se detecta un enlace "Saltar al contenido"',
                howto: 'Actívalo en Ajustes → FIAcces (Ayudas de conformidad).',
                el: null, edit: null
            });
        }
    }

    function checkContrast(issues) {
        var nodes = document.querySelectorAll('p, span, a, li, td, th, h1, h2, h3, h4, h5, h6, button, label');
        var count = 0;
        for (var i = 0; i < nodes.length && count < 400; i++) {
            var el = nodes[i];
            if (el.closest('#fiacces-root') || el.closest('.fiacces-scanner')) continue;
            var text = (el.textContent || '').trim();
            if (!text || el.children.length > 0) continue; // solo nodos hoja con texto
            count++;
            var cs = getComputedStyle(el);
            if (cs.visibility === 'hidden' || cs.display === 'none') continue;
            var fg = parseColor(cs.color);
            if (!fg) continue;
            var bg = effectiveBg(el);
            var r = ratio(fg, bg);
            var size = parseFloat(cs.fontSize);
            var bold = (parseInt(cs.fontWeight, 10) || 400) >= 700;
            var large = size >= 24 || (size >= 18.66 && bold);
            var min = large ? 3 : 4.5;
            if (r < min) {
                issues.push({
                    severity: 'review', level: 'AA', criterion: '1.4.3',
                    title: 'Contraste bajo (' + r.toFixed(2) + ':1, mínimo ' + min + ':1)',
                    howto: 'Ajusta color de texto o fondo. La detección automática puede tener falsos positivos (fondos con imagen/degradado).',
                    el: el, edit: null
                });
            }
        }
    }

    function scan() {
        var issues = [];
        [checkImages, checkLang, checkFormLabels, checkControlNames, checkDuplicateIds,
         checkHeadings, checkNav, checkSkipLink, checkContrast].forEach(function (fn) {
            try { fn(issues); } catch (e) {}
        });
        return issues;
    }

    // ---------- interfaz ----------
    function severityRank(s) { return s === 'error' ? 0 : (s === 'warning' ? 1 : 2); }
    function sevLabel(s) {
        return s === 'error' ? t('sev_error', 'Error')
             : s === 'warning' ? t('sev_warning', 'Advertencia')
             : t('sev_review', 'Revisar');
    }

    function highlight(el) {
        if (highlighted) highlighted.style.outline = '';
        if (!el) return;
        el.style.outline = '3px solid #dc2626';
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        highlighted = el;
    }

    function saveAlt(issue, input, statusEl) {
        var value = input.value;
        statusEl.textContent = '…';
        fetch(cfg.restUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
            body: JSON.stringify({ type: 'img_alt', key: issue.edit.key, value: value })
        }).then(function (res) {
            if (!res.ok) throw new Error();
            return res.json();
        }).then(function () {
            if (issue.el) issue.el.setAttribute('alt', value);
            statusEl.textContent = '✓ ' + t('saved', 'Guardado');
        }).catch(function () {
            statusEl.textContent = '⚠ ' + t('save_error', 'Error al guardar');
        });
    }

    function render(issues) {
        issues.sort(function (a, b) { return severityRank(a.severity) - severityRank(b.severity); });
        summaryEl.textContent = issues.length
            ? issues.length + ' ' + t('issues_found', 'problemas detectados')
            : t('no_issues', 'Sin problemas automáticos detectados.');
        listEl.innerHTML = '';

        issues.forEach(function (issue) {
            var item = document.createElement('div');
            item.className = 'fiacces-scanner__item fiacces-scanner__item--' + issue.severity;

            var head = document.createElement('div');
            head.className = 'fiacces-scanner__item-head';
            head.innerHTML = '<span class="fiacces-scanner__chip">' + sevLabel(issue.severity) +
                '</span><span class="fiacces-scanner__crit">WCAG ' + issue.criterion + ' (' + issue.level + ')</span>';
            item.appendChild(head);

            var title = document.createElement('p');
            title.className = 'fiacces-scanner__title';
            title.textContent = issue.title;
            item.appendChild(title);

            var howto = document.createElement('p');
            howto.className = 'fiacces-scanner__howto';
            howto.textContent = issue.howto;
            item.appendChild(howto);

            if (issue.el) {
                var hl = document.createElement('button');
                hl.type = 'button';
                hl.className = 'fiacces-scanner__btn';
                hl.textContent = t('highlight', 'Resaltar');
                hl.addEventListener('click', function () { highlight(issue.el); });
                item.appendChild(hl);
            }

            if (issue.edit && issue.edit.type === 'img_alt') {
                var row = document.createElement('div');
                row.className = 'fiacces-scanner__edit';
                var input = document.createElement('input');
                input.type = 'text';
                input.value = issue.edit.current || '';
                input.placeholder = t('alt_placeholder', 'Describe la imagen…');
                var save = document.createElement('button');
                save.type = 'button';
                save.className = 'fiacces-scanner__btn fiacces-scanner__btn--primary';
                save.textContent = t('save', 'Guardar');
                var status = document.createElement('span');
                status.className = 'fiacces-scanner__status';
                save.addEventListener('click', function () { saveAlt(issue, input, status); });
                row.appendChild(input);
                row.appendChild(save);
                row.appendChild(status);
                item.appendChild(row);
            }

            listEl.appendChild(item);
        });
    }

    function buildUI() {
        var root = document.createElement('div');
        root.className = 'fiacces-scanner';

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'fiacces-scanner__toggle';
        toggle.textContent = 'A11y';
        toggle.setAttribute('aria-label', t('open', 'Analizar accesibilidad'));

        panel = document.createElement('div');
        panel.className = 'fiacces-scanner__panel';
        panel.hidden = true;

        var header = document.createElement('div');
        header.className = 'fiacces-scanner__header';
        header.innerHTML = '<strong>' + t('panel_title', 'Analizador de accesibilidad') + '</strong>';

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'fiacces-scanner__close';
        closeBtn.textContent = '✕';
        closeBtn.setAttribute('aria-label', t('close', 'Cerrar'));
        closeBtn.addEventListener('click', function () { panel.hidden = true; });
        header.appendChild(closeBtn);

        summaryEl = document.createElement('div');
        summaryEl.className = 'fiacces-scanner__summary';

        var rescan = document.createElement('button');
        rescan.type = 'button';
        rescan.className = 'fiacces-scanner__btn';
        rescan.textContent = t('rescan', 'Volver a analizar');
        rescan.addEventListener('click', function () { render(scan()); });

        listEl = document.createElement('div');
        listEl.className = 'fiacces-scanner__list';

        var note = document.createElement('p');
        note.className = 'fiacces-scanner__note';
        note.textContent = t('admin_only', 'Solo tú (administrador) ves este panel.');

        panel.appendChild(header);
        panel.appendChild(summaryEl);
        panel.appendChild(rescan);
        panel.appendChild(listEl);
        panel.appendChild(note);

        toggle.addEventListener('click', function () {
            panel.hidden = !panel.hidden;
            if (!panel.hidden) render(scan());
        });

        root.appendChild(toggle);
        root.appendChild(panel);
        document.body.appendChild(root);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', buildUI);
    } else {
        buildUI();
    }
})();
