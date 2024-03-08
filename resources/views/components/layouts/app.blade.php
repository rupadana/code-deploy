<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="Ready to revolutionize your deployment process? Start deploying with ease using CodeDeploy. Join our waiting list now and experience the future of web deployment." />
    <meta name="keywords" content="Push To Deploy, PTD, Deployment" />
    <meta name="author" content="CodeCrafters" />

    <link rel="icon" href="{{ url('favicon.png') }}">

    <title>{{ config('app.name') }}</title>


    @filamentStyles
    @vite('resources/css/app.css')
    <style>
        :root {
            --primary-50: 239, 246, 255;
            --primary-100: 219, 234, 254;
            --primary-200: 191, 219, 254;
            --primary-300: 147, 197, 253;
            --primary-400: 96, 165, 250;
            --primary-500: 59, 130, 246;
            --primary-600: 37, 99, 235;
            --primary-700: 29, 78, 216;
            --primary-800: 30, 64, 175;
            --primary-900: 30, 58, 138;
            --primary-950: 23, 37, 84;
        }
    </style>
</head>

<body class="antialiased">
    {{ $slot }}

    @livewire('notifications')

    @filamentScripts
    @vite('resources/js/app.js')

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-JW17L5E5WM"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-JW17L5E5WM');
    </script>
</body>

</html>
