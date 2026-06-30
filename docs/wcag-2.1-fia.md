# Implementación WCAG 2.1 AA — Web de la FIA

> Sitio objetivo: https://fialp.gob.ar/
> Nivel de conformidad objetivo: **WCAG 2.1 AA**
> Estado de este documento: plan de trabajo + checklist de auditoría.

## 0. Alcance y aclaración

Este miniproyecto define **cómo llevar la web de la FIA a conformidad WCAG 2.1 nivel AA**,
respetando el enfoque que seguimos al construir el plugin **FIAcces**: mejoras
incrementales, sin romper el tema, verificables y con preferencia por soluciones
estándar de WordPress.

Distinción clave que mantuvimos durante todo el desarrollo:

- **FIAcces** = *herramienta de asistencia al usuario* (ajustes de texto, contraste,
  daltonismo, cursor, pausa de animaciones…). **Ayuda**, pero **no sustituye** la
  conformidad.
- **Conformidad WCAG** = se logra en el **diseño y el marcado del propio sitio**
  (contenido, plantillas, media). Es lo que este documento aborda.

> Nota: la auditoría fina debe hacerse sobre el HTML real del sitio. Este plan está
> estructurado por los 4 principios POUR y prioriza los criterios A/AA más habituales
> en sitios institucionales WordPress.

---

## 1. Fases del proyecto

| Fase | Objetivo | Entregable |
|------|----------|------------|
| 1. Auditoría | Detectar incumplimientos | Informe con criterios fallidos por página/plantilla |
| 2. Correcciones críticas (A) | Resolver bloqueos de acceso | PRs sobre tema/plantillas |
| 3. Correcciones AA | Alcanzar el nivel objetivo | PRs + ajustes de contenido |
| 4. Verificación | Confirmar conformidad | Re-test + declaración de accesibilidad |
| 5. Mantenimiento | Evitar regresiones | Guía editorial + checklist de publicación |

---

## 2. Herramientas de auditoría recomendadas

- **Automáticas:** axe DevTools, WAVE, Lighthouse (Accesibilidad), Pa11y CI.
- **Manuales (imprescindibles):**
  - Navegación **solo con teclado** (Tab/Shift+Tab/Enter/Esc/flechas).
  - Lector de pantalla (NVDA en Windows, VoiceOver en macOS).
  - Zoom del navegador al **200%** y **400%**.
  - Verificador de **contraste** (TPGi Color Contrast Analyzer).

> Lo automático cubre ~30-40% de los criterios; el resto exige revisión manual.

---

## 3. Checklist por principio (WCAG 2.1 AA)

Marca cada criterio: `[ ]` pendiente · `[~]` parcial · `[x]` cumplido.

### 3.1 Perceptible

- [ ] **1.1.1 Contenido no textual (A):** toda `<img>` con `alt` significativo;
      imágenes decorativas con `alt=""`; iconos con nombre accesible.
- [ ] **1.2.x Multimedia (A/AA):** vídeos con **subtítulos**; audio con transcripción.
- [ ] **1.3.1 Información y relaciones (A):** uso correcto de `<h1>`–`<h6>` (un solo
      `h1`, sin saltos de nivel), listas reales (`<ul>/<ol>`), tablas de datos con
      `<th scope>`, formularios con `<label for>`.
- [ ] **1.3.2 Secuencia significativa (A):** el orden del DOM = orden de lectura.
- [ ] **1.3.4 Orientación (AA):** no bloquear vertical/horizontal.
- [ ] **1.3.5 Identificar propósito de entrada (AA):** `autocomplete` en campos de
      datos personales.
- [ ] **1.4.1 Uso del color (A):** la información no depende solo del color
      (enlaces distinguibles por algo más que color; estados de error con texto/icono).
- [ ] **1.4.3 Contraste mínimo (AA):** texto normal ≥ **4.5:1**, texto grande ≥ **3:1**.
- [ ] **1.4.4 Redimensionar texto (AA):** legible al 200% sin pérdida de contenido.
- [ ] **1.4.5 Imágenes de texto (AA):** usar texto real, no imágenes con texto.
- [ ] **1.4.10 Reflujo (AA):** sin scroll horizontal a 320px de ancho (responsive).
- [ ] **1.4.11 Contraste no textual (AA):** bordes de inputs, iconos y estados de
      foco ≥ **3:1**.
- [ ] **1.4.12 Espaciado de texto (AA):** no romper al aumentar interlineado/espaciado.
- [ ] **1.4.13 Contenido al pasar el puntero/foco (AA):** tooltips/menús descartables
      y persistentes.

### 3.2 Operable

- [ ] **2.1.1 Teclado (A):** toda la funcionalidad usable con teclado.
- [ ] **2.1.2 Sin trampas de teclado (A):** se puede entrar y salir de cada componente.
- [ ] **2.1.4 Atajos de un carácter (A):** configurables/desactivables (FIAcces ya usa
      `Alt + letra`, no un solo carácter).
- [ ] **2.4.1 Evitar bloques (A):** enlace **"Saltar al contenido"** al inicio.
- [ ] **2.4.2 Título de página (A):** `<title>` único y descriptivo por página.
- [ ] **2.4.3 Orden del foco (A):** orden lógico al tabular.
- [ ] **2.4.4 Propósito del enlace (A):** texto de enlace claro (evitar "clic aquí").
- [ ] **2.4.5 Múltiples vías (AA):** menú + buscador o mapa del sitio.
- [ ] **2.4.6 Encabezados y etiquetas (AA):** descriptivos.
- [ ] **2.4.7 Foco visible (AA):** indicador de foco claro y con contraste suficiente.
- [ ] **2.5.1–2.5.4 (A):** gestos simples, cancelación de acciones, nombre accesible
      que incluya el texto visible, sin depender de movimiento del dispositivo.

### 3.3 Comprensible

- [ ] **3.1.1 Idioma de la página (A):** `<html lang="es">`.
- [ ] **3.1.2 Idioma de partes (AA):** marcar fragmentos en otro idioma con `lang`.
- [ ] **3.2.1 Al recibir foco (A):** el foco no dispara cambios de contexto.
- [ ] **3.2.2 Al introducir datos (A):** sin envíos/cambios inesperados.
- [ ] **3.2.3 Navegación coherente (AA):** menús y estructura consistentes.
- [ ] **3.2.4 Identificación coherente (AA):** mismos iconos/nombres para misma función.
- [ ] **3.3.1 Identificación de errores (A):** errores de formulario en texto.
- [ ] **3.3.2 Etiquetas o instrucciones (A):** todo campo etiquetado.
- [ ] **3.3.3 Sugerencia ante errores (AA):** mensajes que orienten a corregir.
- [ ] **3.3.4 Prevención de errores (AA):** confirmación/reversión en envíos sensibles.

### 3.4 Robusto

- [ ] **4.1.1 Análisis sintáctico (A):** HTML válido, sin `id` duplicados.
- [ ] **4.1.2 Nombre, función, valor (A):** componentes interactivos con roles/estados
      ARIA correctos (menús, acordeones, modales, sliders).
- [ ] **4.1.3 Mensajes de estado (AA):** notificaciones vía `role="status"` /
      `aria-live` (FIAcces ya aplica este patrón en sus anuncios).

---

## 4. Tareas concretas sobre el sitio (alto impacto / bajo esfuerzo)

Estas suelen ser las que más mueven la aguja en un WordPress institucional:

1. **`<html lang="es">`** en la cabecera del tema (3.1.1).
2. **Enlace "Saltar al contenido"** al inicio del `<body>`, visible al foco (2.4.1).
3. **Foco visible** global: no eliminar `outline`; estilizarlo con contraste ≥ 3:1 (2.4.7).
4. **`alt` en todas las imágenes** del contenido y de la biblioteca de medios (1.1.1).
5. **Contraste de la paleta** del tema (botones, enlaces, textos sobre fondo) (1.4.3).
6. **Encabezados jerárquicos** en plantillas y entradas (1.3.1).
7. **Formularios etiquetados** (contacto, buscador) con `<label>` y errores en texto (3.3.x).
8. **Menús accesibles** por teclado y con ARIA (4.1.2).
9. **Subtítulos** en vídeos institucionales (1.2.2).
10. **Página/Declaración de Accesibilidad** publicada (requisito habitual en `.gob.ar`).

---

## 5. Qué aporta FIAcces (y qué NO cubre)

**Aporta (mejora de usabilidad real):**
- Escalado de texto por elemento (incluye fuentes en px).
- Modos de contraste (alto contraste, invertido, escala de grises).
- Filtros de **daltonización** (protanopia, deuteranopia, tritanopia).
- Fuente para dislexia, subrayado de enlaces, cursor grande.
- Pausa de animaciones/vídeos (apoya a usuarios sensibles al movimiento).
- Anuncios `aria-live`, foco atrapado en el panel, atajo `Alt + letra`.

**NO cubre (debe resolverse en el sitio):**
- Texto `alt`, jerarquía de encabezados, etiquetas de formularios.
- Contraste base de la paleta del tema.
- Subtítulos/transcripciones de multimedia.
- Orden y visibilidad del foco en componentes del tema.
- Validez del HTML y semántica/ARIA de los widgets del tema.

> Conclusión honesta: instalar s **no hace** que el sitio "cumpla WCAG". Es una
> capa de asistencia. La conformidad se certifica corrigiendo el sitio según las
> secciones 3 y 4.

---

## 6. Verificación y cierre

1. Re-ejecutar axe/WAVE/Lighthouse en las plantillas clave (home, listado, entrada,
   contacto, búsqueda).
2. Prueba manual de teclado y lector de pantalla en esos mismos flujos.
3. Zoom 200%/400% y ancho 320px.
4. Redactar la **Declaración de Accesibilidad** (estado de conformidad, fecha,
   método de evaluación, vía de contacto para reportar barreras).
5. Ajustar el texto del aviso de  para que sea **veraz** respecto al estado
   real (campo "Texto del aviso" en Ajustes → ).

---

## 7. Mantenimiento (evitar regresiones)

- **Guía editorial** para quien publica: `alt` obligatorio, encabezados en orden,
  enlaces descriptivos, vídeos con subtítulos.
- **Checklist de publicación** integrado en el flujo de WordPress.
- Pa11y CI o Lighthouse CI en cada despliegue del tema.
- Revisión de accesibilidad al instalar/actualizar plugins que generen frontend.
