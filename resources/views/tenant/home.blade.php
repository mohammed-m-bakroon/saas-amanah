<!DOCTYPE html>
<html lang="{{ $tenant->settings['locale'] ?? 'ar' }}" dir="{{ ($tenant->settings['locale'] ?? 'ar') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tenant->name }}</title>
</head>
<body>
    <main>
        <h1>{{ $tenant->name }}</h1>
    </main>
</body>
</html>
