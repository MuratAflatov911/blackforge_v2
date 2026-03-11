<?php

declare(strict_types=1);

require_once __DIR__ . '/view.php';

function ensure_uploads_dir(): string
{
    $cfg = app_config();
    $dir = (string)$cfg['app']['uploads_dir'];
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

function uploads_url_for(string $filename): string
{
    $cfg = app_config();
    $base = rtrim((string)$cfg['app']['uploads_url'], '/');
    return $base . '/' . ltrim($filename, '/');
}

function image_ext_from_mime(string $mime): ?string
{
    return match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => null,
    };
}

function save_uploaded_image(array $file): string
{
    $cfg = app_config();
    $max = (int)$cfg['app']['max_upload_bytes'];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Ошибка загрузки файла.');
    }
    if (!isset($file['tmp_name']) || !is_string($file['tmp_name'])) {
        throw new RuntimeException('Некорректный файл.');
    }
    if (!isset($file['size']) || (int)$file['size'] <= 0 || (int)$file['size'] > $max) {
        throw new RuntimeException('Слишком большой файл.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!is_string($mime)) {
        throw new RuntimeException('Не удалось определить тип файла.');
    }
    $ext = image_ext_from_mime($mime);
    if ($ext === null) {
        throw new RuntimeException('Поддерживаются только JPG/PNG/WebP.');
    }

    $dir = ensure_uploads_dir();
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $path = $dir . DIRECTORY_SEPARATOR . $name;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new RuntimeException('Не удалось сохранить файл.');
    }

    return uploads_url_for($name);
}

function save_image_from_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        throw new RuntimeException('Укажи URL изображения.');
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('Некорректный URL.');
    }

    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('cURL недоступен.');
    }

    $cfg = app_config();
    $max = (int)$cfg['app']['max_upload_bytes'];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 4,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'BLACKFORGE/1.0',
    ]);

    $data = curl_exec($ch);
    $ct = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $len = (int)curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    curl_close($ch);

    if ($code < 200 || $code >= 300 || $data === false) {
        throw new RuntimeException('Не удалось скачать изображение.');
    }
    if ($len > 0 && $len > $max) {
        throw new RuntimeException('Изображение слишком большое.');
    }
    if (strlen($data) > $max) {
        throw new RuntimeException('Изображение слишком большое.');
    }

    $mime = is_string($ct) ? trim(explode(';', $ct)[0]) : '';
    $ext = $mime !== '' ? image_ext_from_mime($mime) : null;
    if ($ext === null) {
        // fallback: try to detect from bytes
        $tmp = tmpfile();
        if ($tmp === false) {
            throw new RuntimeException('Ошибка сохранения.');
        }
        fwrite($tmp, $data);
        $meta = stream_get_meta_data($tmp);
        $path = (string)($meta['uri'] ?? '');
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $det = $path !== '' ? $finfo->file($path) : null;
        fclose($tmp);
        if (!is_string($det)) {
            throw new RuntimeException('Не удалось определить тип изображения.');
        }
        $ext = image_ext_from_mime($det);
    }
    if ($ext === null) {
        throw new RuntimeException('Поддерживаются только JPG/PNG/WebP.');
    }

    $dir = ensure_uploads_dir();
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $path = $dir . DIRECTORY_SEPARATOR . $name;
    if (file_put_contents($path, $data) === false) {
        throw new RuntimeException('Не удалось сохранить изображение.');
    }

    return uploads_url_for($name);
}

