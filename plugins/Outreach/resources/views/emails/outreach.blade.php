<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #2d3748;
            line-height: 1.6;
            font-size: 15px;
            margin: 0;
            padding: 20px;
        }
        .content {
            max-width: 600px;
            margin: 0 auto;
        }
        p { margin-bottom: 1em; }
        a { color: #0056b3; }
    </style>
</head>
<body>
    <div class="content">
        @php
            $renderedBody = preg_replace_callback('/<img[^>]+src="([^">]+)"/i', function ($match) use ($message) {
                $src = $match[1];

                // Extrai o caminho relativo (ex: /storage/media/logo.png)
                $relativePath = parse_url($src, PHP_URL_PATH);
                $localPath = public_path(ltrim($relativePath, '/'));

                // Se o arquivo físico existir no disco do servidor, embute via CID
                if (file_exists($localPath)) {
                    $cid = $message->embed($localPath);
                    return str_replace($src, $cid, $match[0]);
                }

                // Fallback: se por algum motivo for uma URL externa da internet, mantém ela
                return $match[0];
            }, $rawBody);
        @endphp

        {!! $renderedBody !!}
    </div>
</body>
</html>
