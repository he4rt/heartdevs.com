import { DiscordSDK } from '@discord/embedded-app-sdk';

const clientId = import.meta.env.VITE_DISCORD_CLIENT_ID;
const discordSdk = new DiscordSDK(clientId);

/**
 * O iframe é sandboxed sem `allow-popups` (target="_blank" é bloqueado) e navegar a
 * própria aba destrói a Activity — todo link daqui precisa abrir fora, pelo SDK.
 */
function interceptLinksToOpenExternally() {
    const appHost = document.querySelector('meta[name="app-host"]')?.getAttribute('content');

    if (!appHost) {
        return;
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');

        if (!link) {
            return;
        }

        const href = link.getAttribute('href');

        if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
            return;
        }

        event.preventDefault();

        const url = new URL(href, `https://${appHost}`).href;

        discordSdk.commands.openExternalLink({ url });
    });
}

async function bootstrapDiscordActivity() {
    await discordSdk.ready();

    const { code } = await discordSdk.commands.authorize({
        client_id: clientId,
        response_type: 'code',
        scope: ['identify'],
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    const response = await fetch('/discord-activity/auth', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ code }),
    });

    const { linked, access_token: accessToken } = await response.json();

    await discordSdk.commands.authenticate({ access_token: accessToken });

    if (!linked) {
        window.dispatchEvent(new CustomEvent('discord-activity:not-linked'));

        return;
    }

    // A sessão Laravel já foi estabelecida no backend (auth()->login) — recarrega
    // pra a página renderizar como usuário autenticado e liberar o chat.
    window.location.reload();
}

interceptLinksToOpenExternally();
bootstrapDiscordActivity();
