<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ config('app.name') }} API — Swagger</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui.css">
    <style>
        body { margin: 0; background: #fafafa; }
        .topbar-custom { display: flex; align-items: center; gap: .75rem; padding: 14px 24px; background: #1e1b4b; color: #fff; font: 600 15px/1.4 system-ui, sans-serif; }
        .topbar-custom a { color: #c7c2ff; margin-left: auto; text-decoration: none; font-weight: 500; }
        .swagger-ui .topbar { display: none; }
    </style>
</head>
<body>
<div class="topbar-custom">
    <span>🚚 {{ config('app.name') }} API</span>
    <a href="{{ route('docs.spec') }}" download="openapi.yaml">openapi.yaml</a>
</div>
<div id="swagger-ui"></div>
<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui-bundle.js" crossorigin></script>
<script>
    window.ui = SwaggerUIBundle({
        url: @json(route('docs.spec')),
        dom_id: '#swagger-ui',
        deepLinking: true,
        persistAuthorization: true,   // token sahifa yangilanganda saqlanadi
        displayRequestDuration: true,
        docExpansion: 'list',
        filter: true,
        tryItOutEnabled: true,
    });
</script>
</body>
</html>
