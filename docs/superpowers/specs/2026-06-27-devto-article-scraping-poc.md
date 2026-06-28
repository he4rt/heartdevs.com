# PoC: Script de Extração de Reactions History do dev.to

## Contexto

O dev.to não expõe dados de "Reactions History" via API pública. Este snippet
JavaScript é executado manualmente no console do browser na página `/stats` de
um artigo para extrair essas informações.

## Como usar

1. Abra a página `/stats` de um artigo no dev.to (requer login)
2. Abra o console do browser (F12 → Console)
3. Cole o script abaixo e pressione Enter
4. O JSON será copiado pro clipboard automaticamente

## Script

````javascript
(() => {
  if (!window.location.pathname.endsWith('/stats')) {
    console.error(' Execute este script na página /stats de um artigo do dev.to');
    return;
  }

  const headers = document.querySelectorAll('h2');
  let rhHeader = null;
  headers.forEach(h => {
    if (h.textContent.trim() === 'Reactions History') rhHeader = h;
  });

  if (!rhHeader) {
    console.warn(' Nenhuma seção "Reactions History" encontrada');
    return;
  }

  const container = rhHeader.parentElement;
  const entries = container.querySelectorAll('div.fs-sm.py-2.flex.items-center');
  const reactions = [];
  const summary = {};

  entries.forEach(entry => {
    const img = entry.querySelector('img');
    const rightSide = entry.querySelector('div.flex-1');
    if (!rightSide) return;

    const infoDiv = rightSide.querySelector('div');
    const reactionType = infoDiv?.querySelector('strong')?.textContent.trim();
    const link = infoDiv?.querySelector('a');
    const username = link?.textContent.trim();
    const userProfileUrl = link?.href;
    const userAvatarUrl = img?.src;
    const date = rightSide.querySelector('div.fs-xs')?.textContent.trim();

    reactions.push({ type: reactionType, username, userProfileUrl, userAvatarUrl, date });
    summary[reactionType] = (summary[reactionType] || 0) + 1;
  });

  const result = {
    articleUrl: window.location.href,
    articleSlug: window.location.pathname.split('/')[2],
    extractedAt: new Date().toISOString(),
    totalReactions: reactions.length,
    reactions,
    summary
  };

  navigator.clipboard.writeText(JSON.stringify(result, null, 2))
    .then(() => console.log(' JSON copiado pro clipboard!'))
    .catch(() => console.warn(' Não foi possível copiar automaticamente'));

  console.log(` ${result.articleSlug} — Total: ${result.totalReactions}`);
  Object.entries(result.summary).forEach(([type, count]) => {
    console.log(`  ${type}: ${count}`);
  });

  return result;
})();
` ``

## Saída esperada

```json
{
  "articleUrl": "https://dev.to/user/article/stats",
  "articleSlug": "article-slug",
  "extractedAt": "2026-06-27T00:00:00.000Z",
  "totalReactions": 6,
  "reactions": [...],
  "summary": {
    "like": 2,
    "fire": 1,
    "unicorn": 1
  }
}
` ``
```