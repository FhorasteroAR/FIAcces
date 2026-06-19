=== WP Accesibilidad A11y ===
Contributors: tu-usuario
Tags: accessibility, wcag, a11y, accesibilidad, contrast
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin de accesibilidad WCAG 2.1 nivel AA. Añade una barra flotante con ajustes de texto, contraste, animaciones y cursor para los visitantes del sitio.

== Description ==

WP Accesibilidad A11y añade una **barra de herramientas de accesibilidad** flotante en el frontend de tu sitio WordPress, permitiendo a los usuarios ajustar la presentación del contenido según sus necesidades.

**Funcionalidades:**

* Ajuste del tamaño del texto (80% – 200%)
* Cuatro modos de contraste: normal, alto contraste, colores invertidos, escala de grises
* Fuente alternativa amigable para personas con dislexia
* Subrayado forzado de todos los enlaces
* Pausa global de animaciones y vídeos
* Cursor grande y extra grande
* Navegación completa por teclado (atajo configurable Alt + A)
* Soporte para lectores de pantalla con ARIA
* Persistencia de preferencias entre páginas (localStorage + cookies)

**Cumplimiento WCAG 2.1:**

El plugin cumple con los criterios de los cuatro principios: Perceptible, Operable, Comprensible y Robusto, en nivel AA.

== Installation ==

1. Sube la carpeta `wp-accesibilidad-a11y` a `/wp-content/plugins/`.
2. Activa el plugin desde el menú "Plugins" en WordPress.
3. Configura el plugin en "Ajustes > Accesibilidad A11y".
4. Visita el frontend de tu sitio para ver el botón flotante.

== Frequently Asked Questions ==

= ¿Funciona en cualquier tema? =

Sí. El plugin se carga en el footer del frontend y funciona con cualquier tema.

= ¿Las preferencias se guardan? =

Sí, en `localStorage` del navegador del usuario, con fallback a cookies.

= ¿Es compatible con cachés? =

Sí. Toda la lógica vive en el navegador del cliente, no afecta cachés de página.

== Changelog ==

= 1.0.0 =
* Lanzamiento inicial con las 3 fases completas.

== Upgrade Notice ==

= 1.0.0 =
Versión inicial.
