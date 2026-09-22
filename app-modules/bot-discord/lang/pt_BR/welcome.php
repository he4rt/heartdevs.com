<?php

declare(strict_types=1);

return [
    'dm' => [
        'description' => "Que bom ter você por aqui, **:username**! 💜\n\nA He4rt é uma das maiores comunidades de desenvolvedores do Brasil — um lugar pra aprender junto, trocar ideia, participar de eventos e evoluir com gente que curte código. 💻",
    ],
    'fallback' => [
        'description' => "Tentei te dar as boas-vindas na sua DM, mas parece que ela está fechada 👀\n\nSem problema — dá pra começar por aqui mesmo!",
        'body' => ':mention 👋',
    ],
    'embed' => [
        'title' => 'Bem-vindo(a) à He4rt! 💜',
        'cta_title' => '🙋 Comece se apresentando',
        'cta' => 'Toca em **Me apresentar** aqui embaixo (te levo direto pro canal certo) e manda `/apresentar`. Leva menos de um minuto — nome, nickname e um pouco sobre você. É assim que a comunidade te conhece e você desbloqueia o resto do servidor. 🚀',
        'footer' => ':year © He4rt Developers',
    ],
    'buttons' => [
        'present' => 'Me apresentar',
        'portal' => 'Portal',
        'socials' => 'Nossas redes',
    ],
    'announcement' => [
        'title' => 'Novo membro chegou',
        'message' => 'Seja bem-vindo(a), :username!',
        'body' => ':mention acabou de chegar. Para começar, use o comando `/apresentar` e conte um pouco sobre você para a comunidade.',
    ],
    'profile_failure' => [
        'title' => 'Novo membro',
        'message' => 'Seja bem-vindo(a)!',
        'body' => ':mention entrou no servidor, mas houve um problema ao inicializar seu perfil. Caso algo não funcione, fale com a moderação.',
    ],
];
