<?php

declare(strict_types=1);

use He4rt\Documentation\Helpers\DocumentationHelper;

use function Laravel\Folio\name;

name('docs.version.index');

// Redirect to first page by order
$allPages = DocumentationHelper::getVersionPages($version);

abort_if($allPages === [], 404);

$firstPage = $allPages[0]['slug'];
$redirectUrl = sprintf('/docs/%s/%s', $version, $firstPage);

?>

<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="refresh" content="0;url={{ $redirectUrl }}" />
        <script>
            window.location.href = '{{ $redirectUrl }}';
        </script>
    </head>
    <body>
        <p>Redirecionando...</p>
    </body>
</html>
