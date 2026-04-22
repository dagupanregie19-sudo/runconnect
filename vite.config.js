import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig(({ command }) => {
    const config = {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
            tailwindcss(),
            VitePWA({
                outDir: 'public',
                registerType: 'autoUpdate',
                injectRegister: false,
                manifest: {
                    id: '/',
                    name: 'RunConnect',
                    short_name: 'RunConnect',
                    description: 'Your Running Journey Starts Here',
                    theme_color: '#00D26A',
                    background_color: '#0D1117',
                    display: 'standalone',
                    orientation: 'portrait',
                    start_url: '/',
                    scope: '/',
                    icons: [{
                        src: '/logo.svg',
                        sizes: '512x512',
                        type: 'image/svg+xml',
                        purpose: 'any maskable'
                    }]
                },
                workbox: {
                    skipWaiting: true,
                    clientsClaim: true,
                    navigateFallback: null,
                    runtimeCaching: [
                        {
                            // Cache dashboard and other HTML pages
                            urlPattern: ({ request }) => request.mode === 'navigate',
                            handler: 'NetworkFirst',
                            options: {
                                cacheName: 'pages-cache',
                                expiration: {
                                    maxEntries: 50,
                                    maxAgeSeconds: 30 * 24 * 60 * 60, // 30 Days
                                }
                            }
                        },
                        {
                            // Cache API requests if needed later
                            urlPattern: ({ url }) => url.pathname.startsWith('/api'),
                            handler: 'NetworkFirst',
                            options: {
                                cacheName: 'api-cache',
                                cacheableResponse: {
                                    statuses: [0, 200]
                                }
                            }
                        },
                        {
                            // Cache CDNs (Bootstrap, FontAwesome, Google Fonts)
                            urlPattern: ({ url }) =>
                                url.origin.includes('cdn.jsdelivr.net') ||
                                url.origin.includes('cdnjs.cloudflare.com') ||
                                url.origin.includes('fonts.googleapis.com') ||
                                url.origin.includes('fonts.gstatic.com'),
                            handler: 'CacheFirst',
                            options: {
                                cacheName: 'cdn-cache',
                                cacheableResponse: {
                                    statuses: [0, 200]
                                },
                                expiration: {
                                    maxEntries: 30,
                                    maxAgeSeconds: 365 * 24 * 60 * 60, // 1 Year
                                }
                            }
                        },
                        {
                            // Cache locally hosted vendor/static assets
                            urlPattern: ({ url }) => url.pathname.startsWith('/vendor/') || url.pathname.startsWith('/data/'),
                            handler: 'CacheFirst',
                            options: {
                                cacheName: 'static-asset-cache',
                                cacheableResponse: {
                                    statuses: [0, 200]
                                },
                                expiration: {
                                    maxEntries: 50,
                                    maxAgeSeconds: 30 * 24 * 60 * 60, // 30 Days
                                }
                            }
                        }
                    ]
                },
                devOptions: {
                    enabled: true,
                    type: 'module',
                }
            })
        ],
        server: {
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,
            cors: true, // Allow all origins (fixes Ngrok CORS issues)
            headers: {
                'Access-Control-Allow-Origin': '*',
            },
            hmr: {
                host: 'localhost',
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };



    return config;
});
