<script>
    (() => {
        const provider = new URL(window.location.href).searchParams.get('oauth_provider');
        const supportedProviders = ['discord', 'github', 'twitch'];

        if (!supportedProviders.includes(provider)) {
            return;
        }

        try {
            window.localStorage.setItem('lastAuthProvider', provider);
        } catch (error) {
            // Ignore browsers that block localStorage access.
        }

        const url = new URL(window.location.href);
        url.searchParams.delete('oauth_provider');
        window.history.replaceState({}, document.title, url);
    })();
</script>
