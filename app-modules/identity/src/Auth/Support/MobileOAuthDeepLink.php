<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Support;

final class MobileOAuthDeepLink
{
    public static function build(string $path, ?string $error = null, ?string $code = null): string
    {
        $scheme = config('services.he4rt_app.deeplink_scheme');
        $query = array_filter(['error' => $error, 'code' => $code]);

        return sprintf('%s://oauth/%s%s', $scheme, $path, $query === [] ? '' : '?'.http_build_query($query));
    }
}
