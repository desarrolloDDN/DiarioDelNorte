# PDF.js (vendorizado)

- Versión: **pdfjs-dist 3.11.174** (build UMD, no-módulo).
- Origen: https://unpkg.com/pdfjs-dist@3.11.174/build/
- Licencia: Apache-2.0 (`@licstart` en las cabeceras de los archivos).
- Uso: visor embebido de la edición impresa (`single-ddn_edition.php` +
  `initEditionReader()` en `assets/src/js/app.js`). Se carga bajo demanda,
  solo al pulsar «Leer en línea».

Para actualizar: descargar `pdf.min.js` y `pdf.worker.min.js` de la misma
ruta con la versión nueva y reemplazar estos dos archivos.
