import { defineConfig } from 'vite';

// Compila assets/src/js/app.js (que importa app.scss) hacia assets/dist/
// con nombres estables (app.js / app.css) para poder encolarlos con
// filemtime() como cache-buster.
export default defineConfig({
  // URLs relativas en el CSS compilado: el tema vive en
  // /wp-content/themes/diario-del-norte/ y no en la raíz del dominio.
  base: './',
  css: {
    preprocessorOptions: {
      scss: { api: 'modern-compiler' },
    },
  },
  build: {
    outDir: 'assets/dist',
    emptyOutDir: true,
    manifest: false,
    cssCodeSplit: false,
    rollupOptions: {
      input: 'assets/src/js/app.js',
      output: {
        entryFileNames: 'app.js',
        assetFileNames: (info) => {
          const name = info.name || '';
          if (name.endsWith('.css')) return 'app.css';
          if (/\.(woff2?|otf|ttf|eot)$/.test(name)) return 'fonts/[name][extname]';
          return 'assets/[name][extname]';
        },
      },
    },
  },
});
