<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agent Bridge API — Swagger</title>
    <link rel="icon" type="image/png" href="{{ asset('vendor/swagger-ui/favicon-32x32.png') }}" sizes="32x32">
    <link rel="stylesheet" href="{{ asset('vendor/swagger-ui/swagger-ui.css') }}">
    <style>
        body { margin: 0; background: #fafafa; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="{{ asset('vendor/swagger-ui/swagger-ui-bundle.js') }}"></script>
    <script src="{{ asset('vendor/swagger-ui/swagger-ui-standalone-preset.js') }}"></script>
    <script>
        window.onload = function () {
            window.ui = SwaggerUIBundle({
                url: @json(route('api.docs.openapi')),
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset,
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl,
                ],
                layout: 'StandaloneLayout',
            });
        };
    </script>
</body>
</html>
