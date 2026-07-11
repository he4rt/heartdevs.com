/**
 * dev.to Reactions History Extractor
 * Issue: he4rt/heartdevs.com #181 - feat(integration-devto): article scraping PoC
 */

(() => {
  const REACTIONS_HEADER_TEXT = 'Reactions History';
  const STATS_PATH_PATTERN = /\/stats\/?$/;
  const UNKNOWN_VALUE = 'unknown';

  const SELECTORS = {
    reactionsHeader: 'h2',
    reactionsCard: '.crayons-card',
    reactionEntry: '.fs-sm.py-2.flex.items-center',
    reactionDetails: '.flex-1',
    reactionDate: '.fs-xs',
    userAvatar: 'img.crayons-avatar.h-8.w-8:not(.absolute)',
  };

  function isStatsPage({ hostname, pathname }) {
    return hostname === 'dev.to' && STATS_PATH_PATTERN.test(pathname);
  }

  function getArticleMetadata() {
    const currentUrl = new URL(window.location.href);
    const articlePath = currentUrl.pathname.replace(STATS_PATH_PATTERN, '');
    const articleSlug = articlePath.split('/').filter(Boolean).at(-1) || '';

    return {
      articleUrl: `${currentUrl.origin}${articlePath}`,
      articleSlug,
      articleTitle: document.title,
      extractedAt: new Date().toISOString(),
    };
  }

  function findReactionsContainer() {
    const header = [...document.querySelectorAll(SELECTORS.reactionsHeader)].find(
      (element) => element.textContent.trim() === REACTIONS_HEADER_TEXT,
    );

    if (!header) {
      return null;
    }

    return header.closest(SELECTORS.reactionsCard) || header.parentElement;
  }

  function extractReaction(entry) {
    const detailsElement = entry.querySelector(SELECTORS.reactionDetails);

    if (!detailsElement) {
      return null;
    }

    const informationElement = detailsElement.children[0];

    if (!informationElement) {
      return null;
    }

    const rawText = informationElement.textContent.trim() || '';
    const type = rawText.split(/\r?\n/)[0].trim() || UNKNOWN_VALUE;

    const userLink = informationElement.querySelector('a');

    return {
      type,
      username: userLink?.textContent.trim() || UNKNOWN_VALUE,
      userProfileUrl: userLink?.href || '',
      userAvatarUrl: entry.querySelector(SELECTORS.userAvatar)?.src || '',
      date:
        detailsElement.querySelector(SELECTORS.reactionDate)?.textContent.trim() ||
        '',
    };
  }

  function extractReactions(container) {
    return [...container.querySelectorAll(SELECTORS.reactionEntry)]
      .map(extractReaction)
      .filter(Boolean);
  }

  function buildSummary(reactions) {
    return reactions.reduce((summary, reaction) => {
      summary[reaction.type] = (summary[reaction.type] || 0) + 1;

      return summary;
    }, {});
  }

  function buildResult(metadata, reactions) {
    return {
      ...metadata,
      totalReactions: reactions.length,
      reactions,
      summary: buildSummary(reactions),
    };
  }

  function copyToClipboardFallback(text) {
    const textarea = document.createElement('textarea');

    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    try {
      return document.execCommand('copy');
    } catch {
      return false;
    } finally {
      document.body.removeChild(textarea);
    }
  }

  function copyToClipboard(json) {
    const useFallback = () => {
      const copied = copyToClipboardFallback(json);

      console.log(
        copied
          ? '✅ JSON copiado para o clipboard (via fallback)!'
          : '⚠️ Não foi possível copiar automaticamente. Copie o JSON exibido no console.',
      );
    };

    if (typeof navigator.clipboard?.writeText !== 'function') {
      useFallback();

      return;
    }

    navigator.clipboard
      .writeText(json)
      .then(() => console.log('✅ JSON copiado para o clipboard!'))
      .catch(useFallback);
  }

  function outputResult(result) {
    const json = JSON.stringify(result, null, 2);

    copyToClipboard(json);

    console.log(`📊 ${result.articleSlug || 'artigo'}`);
    console.log(`   Total: ${result.totalReactions} reações`);

    Object.entries(result.summary).forEach(([type, count]) => {
      console.log(`   ${type}: ${count}`);
    });

    console.log(json);

    return result;
  }

  if (!isStatsPage(window.location)) {
    console.error('❌ Execute este script na página /stats de um artigo do dev.to.');

    return;
  }

  const metadata = getArticleMetadata();
  const reactionsContainer = findReactionsContainer();

  if (!reactionsContainer) {
    console.warn('⚠️ Nenhuma seção "Reactions History" encontrada.');

    return outputResult(buildResult(metadata, []));
  }

  const reactions = extractReactions(reactionsContainer);

  if (reactions.length === 0) {
    console.debug('⚠️ Container "Reactions History" encontrado, mas nenhuma entrada foi extraída. O seletor pode estar desatualizado.');
  }

  return outputResult(buildResult(metadata, reactions));
})();
