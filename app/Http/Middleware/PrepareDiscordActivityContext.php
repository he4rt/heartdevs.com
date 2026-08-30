<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * O iframe da Activity carrega a partir de `discordsays.com`, terceiro para o documento
 * top-level do Discord — cookie `SameSite=Lax` (padrão da app) não sobrevive nele, e sem
 * sessão nem o Livewire funciona. Precisa rodar antes do StartSession (prioridade em
 * bootstrap/app.php). `discord-activity.auth` entra na checagem porque o login de
 * verdade acontece num fetch() separado, sem o `frame_id` da carga inicial da página.
 */
final class PrepareDiscordActivityContext
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isDiscordActivityContext = $request->filled('frame_id')
            || $request->routeIs('discord-activity.auth');

        if ($isDiscordActivityContext) {
            config([
                'session.same_site' => 'none',
                'session.secure' => true,
            ]);
        }

        return $next($request);
    }
}
