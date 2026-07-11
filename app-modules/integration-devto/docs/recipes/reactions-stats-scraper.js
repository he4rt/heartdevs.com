(() => {
  const UNKNOWN_VALUE = 'unknown';

  const SELECTORS = {
    sectionHeading: 'h2',
    headingText: 'Reactions History',
    sectionCard: '.crayons-card.p-4.overflow-auto',
    reactionEntry: '.fs-sm.py-2.flex.items-center',
    userAvatar: 'img',
    information: '.flex-1',
    reactionDate: '.fs-xs',
  };

  function isStatsPage(location) {
    return /(\/stats)\/?$/.test(location.pathname);
  }

  function getArticleMetadata() {
    const pathParts = window.location.pathname.split('/').filter(Boolean);
    const articleSlug = pathParts.length >= 2 ? pathParts.at(-2) : '';
    const articleUrl =
      window.location.origin +
      window.location.pathname.replace(/(\/stats)\/?$/, '');
    const articleTitle = document.title;
    const extractedAt = new Date().toISOString();
    return { articleUrl, articleSlug, articleTitle, extractedAt };
  }

  function findReactionsContainer() {
    for (const h of document.querySelectorAll(SELECTORS.sectionHeading)) {
      if (h.textContent.trim() === SELECTORS.headingText) {
        return h.closest(SELECTORS.sectionCard) || h.parentElement;
      }
    }
    return null;
  }

  function extractReaction(entry) {
    const informationElement = entry.querySelector(SELECTORS.information);
    if (!informationElement) return null;

    const infoDivs = informationElement.children;
    const rawText = (infoDivs[0]?.textContent || '').trim();
    const type = rawText.split('\n')[0].trim() || UNKNOWN_VALUE;

    const detailsElement = informationElement;
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
    textarea.focus();
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
          : '⚠️ Não foi possível copiar automaticamente. Copie o JSON exibido no console.'
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
    console.error(
      '❌ Execute este script na página /stats de um artigo do dev.to.'
    );
    return;
  }

  const metadata = getArticleMetadata();
  const reactionsContainer = findReactionsContainer();

  if (!reactionsContainer) {
    console.warn('⚠️ Nenhuma seção "Reactions History" encontrada.');
    return outputResult(buildResult(metadata, []));
  }

  const reactions = extractReactions(reactionsContainer);
  return outputResult(buildResult(metadata, reactions));
})();
