<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Icons -->
    <link rel="icon" type="image/png" sizes="128x128" href="icons/favicon-128x128.png">
    <link rel="icon" type="image/png" sizes="96x96" href="icons/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="32x32" href="icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="icons/favicon-16x16.png">
    <link rel="icon" type="image/ico" href="favicon.ico">

    <!-- ios -->
    <link ref="apple-touch-icon" href="ios/100.png">
    <meta name="apple-mobile-web-app-status-bar" content="#ffffff">

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" crossorigin="anonymous">
    <link rel="manifest" href="manifest.webmanifest" />


    <!-- Scripts -->
    @routes
    @vite('resources/js/app.js')
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
