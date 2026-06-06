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
                    // JavaScript
                    'resources/js/app.js',
                    'resources/js/socket-manager.js',
                    'resources/js/nexus-soul.js',
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
                    'resources/js/legacy/mention-hashtag-autocomplete.js',
                    'resources/js/legacy/community-admin-inline.js',
                    'resources/js/legacy/life-chapters.js',
                    // CSS
                    'resources/css/app-layout.css',
                    'resources/css/mobile-header.css',
                    'resources/css/comments.css',
                    'resources/css/partial-posts.css',
                    'resources/css/modals.css',
                    'resources/css/posts-index.css',
                    'resources/css/pulse.css',
                    'resources/css/chat-show.css',
                    'resources/css/global-chat.css',
                    'resources/css/mini-chat.css',
                    'resources/css/activity.css',
                    'resources/css/ai-chat.css',
                    'resources/css/auth-login.css',
                    'resources/css/auth-register.css',
                    'resources/css/auth-forgot-password.css',
                    'resources/css/auth-reset-password.css',
                    'resources/css/auth-set-password.css',
                    'resources/css/auth-password-change.css',
                    'resources/css/auth-suspended.css',
                    'resources/css/auth-verify-email.css',
                    'resources/css/admin-comments.css',
                    'resources/css/admin-dashboard.css',
                    'resources/css/admin-posts.css',
                    'resources/css/admin-reports.css',
                    'resources/css/admin-stories.css',
                    'resources/css/admin-user-detail.css',
                    'resources/css/admin-user-edit.css',
                    'resources/css/admin-users.css',
                    'resources/css/hashtag.css',
                    'resources/css/hashtag-index.css',
                    'resources/css/my-reports.css',
                    'resources/css/report-detail-user.css',
                    'resources/css/stories-create.css',
                    'resources/css/stories-show.css',
                    'resources/css/stories-viewer.css',
                    'resources/css/stories-index.css',
                    'resources/css/communities.css',
                    'resources/css/communities-admin.css',
                    'resources/css/life-chapters.css',
                    'resources/css/call-modal.css',
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
