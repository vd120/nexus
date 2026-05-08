import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    // Load .env file
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: [
                    'resources/js/app.js',
                    'resources/js/socket-manager.js',
                    'resources/js/legacy/ui-utils.js',
                    'resources/js/legacy/posts.js',
                    'resources/js/legacy/home.js',
                    'resources/js/legacy/comments.js',
                    'resources/js/legacy/auth-login.js',
                    'resources/js/legacy/auth-register.js',
                    'resources/js/legacy/auth-forgot-password.js',
                    'resources/js/legacy/auth-reset-password.js',
                    'resources/js/legacy/auth-set-password.js',
                    'resources/js/legacy/auth-password-change.js',
                    'resources/js/legacy/auth-suspended.js',
                    'resources/js/legacy/auth-verify-email.js',
                    'resources/js/legacy/groups-show.js',
                    'resources/js/legacy/groups-edit.js',
                    'resources/js/legacy/ai-chat.js',
                    'resources/js/legacy/global-chat.js',
                ],
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        resolve: {
            alias: {
                '@': '/resources/js',
            },
        },
        define: {
            // Define environment variables for Vite
            'import.meta.env.VITE_APP_NAME': JSON.stringify(env.APP_NAME || 'Laravel'),
        },
        server: {
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
