<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
    <link rel="icon" href="data:,">
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#0a0a0b">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Nexus">
        <link rel="apple-touch-icon" href="/images/icons/nexus-icon-192.png">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />


        
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
        <style>
            @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
            #app { animation: fadeIn 0.3s ease-out forwards; }
        </style>
    </head>
    <body class="font-sans antialiased">
@inertia
    </body>
</html>
