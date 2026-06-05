<?php

declare(strict_types=1);

http_response_code(503);
header('Content-Type: text/html; charset=utf-8');
header('Retry-After: 21600');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require __DIR__.'/index.php';
exit;
