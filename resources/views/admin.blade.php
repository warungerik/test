<!DOCTYPE html>
<html lang="en" dir="ltr" data-startbar="dark" data-bs-theme="dark">

<head>
    <link rel="shortcut icon" href="{{ asset($website->header->favicon) }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite('resources/js/admin.js')
    @inertiaHead
</head>

<body>
    @routes
    @inertia

</body>

</html>
