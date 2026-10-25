<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#0d0f12] text-[#f1f3f5] antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PDF Studio — Local & Private PDF Utility</title>
    <meta name="description" content="Offline, private, high-performance PDF manipulation suite. Merge, compress, split, and manage PDF pages securely on your machine.">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2338bdf8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z'/><path d='M14 2v4a2 2 0 0 0 2 2h4'/><path d='M10 13v6'/><path d='M14 17h-4'/></svg>">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="h-full font-sans bg-[#0c0d10] text-[#e6e8ec] selection:bg-amber-500/20 selection:text-amber-200">
    @inertia
</body>
</html>
