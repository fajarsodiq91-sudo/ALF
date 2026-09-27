import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const codespaceName = process.env.CODESPACE_NAME;
const forwardingDomain = process.env.GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        ...(codespaceName && forwardingDomain
            ? {
                  hmr: {
                      protocol: 'wss',
                      host: `${codespaceName}-5173.${forwardingDomain}`,
                      clientPort: 443,
                  },
              }
            : {}),
    },
});
