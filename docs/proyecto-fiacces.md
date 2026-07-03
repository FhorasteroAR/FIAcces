# Miniproyecto — Plugin FIAcces

> Plugin de accesibilidad para WordPress (barra de herramientas de asistencia en el frontend).
> Repositorio: FhorasteroAR/FIAcces · Estado: en desarrollo activo.

## 1. Visión y objetivo

FIAcces es un plugin de WordPress que añade una **barra de herramientas de asistencia a la
accesibilidad** en el frontend, permitiendo a cada visitante adaptar la presentación del
sitio a sus necesidades (texto, contraste, color, lectura, movimiento, cursor).

**Alcance honesto:** FIAcces es una *capa de asistencia al usuario*, **no** una herramienta
de certificación WCAG. La conformidad WCAG se logra en el sitio (ver `docs/wcag-2.1-fia.md`).
FIAcces complementa ese trabajo y mejora la usabilidad real.

## 2. Principios de desarrollo (cómo trabajamos)

- **Incremental y verificable:** cambios pequeños, con `php -l` / `node --check` antes de
  cada push, y commits descriptivos.
- **Estándar WordPress:** hooks, sanitización (`sanitize_*`, `esc_*`), opciones vía
  `get_option`, nada propietario innecesario.
- **Sin romper el tema:** el widget es autónomo (footer + JS), excluye su propia UI de los
  filtros, y no depende del tema activo.
- **Accesible él mismo:** foco atrapado en el panel, `aria-*`, anuncios `aria-live`,
  `prefers-reduced-motion`.
- **Honestidad:** no prometer conformidad; el texto del aviso es editable y por defecto
  prudente.

## 3. Arquitectura

```
fiacces.php                      Bootstrap (singleton, constantes, activación)
includes/
  class-fiacces-settings.php     Defaults, get_options(), sanitize()
  class-fiacces-frontend.php     Enqueue, render del widget, script anti-FOUC, filtros SVG
  class-fiacces-admin.php        Página de ajustes (Settings API)
  class-fiacces-rest.php         Endpoints REST (protegidos por manage_options)
assets/
  css/frontend.css  js/frontend.js   UI de cara al visitante
  css/admin.css     js/admin.js      UI del panel de administración
languages/                       Traducciones (text domain: fiacces)
docs/                            Documentación del proyecto
```

**Patrón de cada funcionalidad de frontend:**
1. Opción en `features` (settings) + checkbox en admin.
2. Render del control en el panel (`class-fiacces-frontend.php`).
3. Estado en JS (`state`) + acción en `handleAction` + `syncUI`.
4. Clase en `<html>` + CSS asociado.
5. Persistencia (localStorage, fallback cookie) + script anti-FOUC en `wp_head`.

## 4. Estado actual (hecho)

- [x] Renombrado del plugin a **FIAcces**.
- [x] Cabecera correcta (`Requires at least: 5.0`, `Tested up to: 6.5`).
- [x] Script inline **compatible con CSP** (`wp_print_inline_script_tag`).
- [x] **Tamaño de texto** por elemento (cubre fuentes en px, no solo rem/em).
- [x] **Contraste**: alto contraste, invertido, escala de grises.
- [x] **Daltonismo**: filtros de *corrección* (protanopia, deuteranopia, tritanopia).
- [x] **Legibilidad**: fuente para dislexia, subrayado de enlaces.
- [x] **Animaciones**: pausa de animaciones/sliders/vídeos.
- [x] **Cursor**: grande y extra grande.
- [x] **Navegación por teclado**: atajo `Alt + letra`, foco atrapado, Escape.
- [x] **Posición configurable** del botón (4 esquinas).
- [x] **Aviso emergente** sobre el icono, **texto configurable**, con animación de
      escalado al abrir/cerrar y `prefers-reduced-motion`.
- [x] Persistencia de preferencias entre páginas.

## 5. Roadmap (pendiente / mejoras)

### Fase A — Robustez y calidad
- [ ] **Pruebas:** suite PHPUnit para `sanitize()` y defaults; tests JS (Jest) para
      `applyState`/`applyTextScale`.
- [ ] **Linters en CI:** PHPCS con `WordPress-Coding-Standards`, ESLint, GitHub Actions.
- [ ] **Escape/seguridad:** auditar toda salida (`esc_html/attr/url`) y nonces en REST.
- [ ] **Rendimiento:** revisar el recorrido del DOM de `applyTextScale` en páginas grandes
      (posible límite o `requestIdleCallback`).

### Fase B — Funcionalidades
- [ ] **Perfiles rápidos** (presets): "baja visión", "dislexia", "daltonismo", "epilepsia".
- [ ] **Espaciado de texto** (interlineado, letra, palabra) — apoya WCAG 1.4.12.
- [ ] **Resaltar enlaces / títulos / foco** como toggles independientes.
- [ ] **Máscara de lectura / regla** y **guía de lectura**.
- [ ] **Lectura en voz alta** (TTS con la Web Speech API).
- [ ] **Detener parpadeos/GIFs** (reemplazo por fotograma estático).

### Fase C — Administración y i18n
- [ ] **Migración de datos** para instalaciones previas (`wpa11y_settings` → `fiacces_settings`).
- [ ] **Traducciones**: generar `.pot` y al menos `es_ES` / `es_AR`.
- [ ] **Importar/exportar** configuración.
- [ ] **Vista previa** en vivo dentro del panel de admin.

### Fase D — Distribución
- [ ] Cabeceras/`readme.txt` finales (autor, URI reales) y captura de pantalla.
- [ ] Versionado semántico y `CHANGELOG`.
- [ ] Empaquetado `.zip` reproducible (script de build que excluya `docs/`, `.git`, tests).
- [ ] Opcional: publicación en el repositorio de plugins de WordPress.org.

## 6. Calidad y verificación

- **Antes de cada push:** `php -l` en los `.php` tocados y `node --check` en el JS.
- **Manual:** abrir el panel con teclado, probar cada toggle, recargar (persistencia),
  incógnito (caché/aviso), zoom 200%.
- **Accesibilidad del propio widget:** axe sobre el panel abierto; navegación con lector
  de pantalla.
- **Compatibilidad:** probar con un tema por defecto (Twenty Twenty-*) y con el tema real
  del sitio.

## 7. Flujo de trabajo de Git

- Rama de desarrollo: `claude/vibrant-keller-rr2lti` (PR #1).
- Commits descriptivos en español, un cambio lógico por commit.
- Push a la rama de la feature; el PR se actualiza solo.
- No se abre PR nuevo salvo petición explícita.

## 8. Riesgos y decisiones tomadas

| Tema | Decisión |
|------|----------|
| Overlays no certifican WCAG | Documentado; aviso con texto prudente y editable |
| Escalar solo `html` no cubre px | `applyTextScale` por elemento en JS |
| Filtros de daltonismo | Corrección (daltonización) en vez de simulación |
| CSP estricta (`.gob.ar`) | `wp_print_inline_script_tag` para soportar nonce |
| Caché de página | Lógica en cliente; documentado el purgado tras cambios |
| Doble escalado de texto | Eliminado el escalado de `html`; JS único responsable |

## 9. Próximo paso sugerido

Cerrar **Fase A** (CI + linters + tests de `sanitize`) para asegurar la base antes de
añadir nuevas funcionalidades de la Fase B.
