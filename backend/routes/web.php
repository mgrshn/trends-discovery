<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// OpenAPI spec (served from public/ — bind-mounted from host)
Route::get('/api/openapi.yaml', function () {
    $path = public_path('api-docs.yaml');
    abort_unless(file_exists($path), 404);
    return response(file_get_contents($path), 200, ['Content-Type' => 'application/yaml']);
});

// Swagger UI (CDN)
Route::get('/api/docs', function () {
    $specUrl = url('/api/openapi.yaml');
    return response("<!DOCTYPE html>
<html>
<head>
  <title>Trends Discovery API</title>
  <meta charset=\"utf-8\">
  <link rel=\"stylesheet\" href=\"https://unpkg.com/swagger-ui-dist@5/swagger-ui.css\">
</head>
<body>
  <div id=\"swagger-ui\"></div>
  <script src=\"https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js\"></script>
  <script>
    SwaggerUIBundle({
      url: '{$specUrl}',
      dom_id: '#swagger-ui',
      presets: [SwaggerUIBundle.presets.apis, SwaggerUIBundle.SwaggerUIStandalonePreset],
      layout: 'BaseLayout',
      deepLinking: true,
    })
  </script>
</body>
</html>", 200, ['Content-Type' => 'text/html']);
});
