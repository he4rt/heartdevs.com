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

```javascript
(() => {
  // Validação: está na página certa?
  if (!window.location.pathname.endsWith('/stats')) {
    console.error('❌ Execute este script na página /stats de um artigo do dev.to');
    return;
  }

  const articleUrl = window.location.href.replace('/stats', '');
  const articleSlug = window.location.pathname.replace('/stats', '').split('/').filter(Boolean).pop();

  function copyToClipboard(text) {
    if (navigator.clipboard && document.hasFocus()) {
      return navigator.clipboard.writeText(text);
    }
    // fallback legado para quando o foco está no DevTools
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    try {
      const ok = document.execCommand('copy');
      document.body.removeChild(textarea);
      return ok ? Promise.resolve() : Promise.reject(new Error('execCommand falhou'));
    } catch (err) {
      document.body.removeChild(textarea);
      return Promise.reject(err);
    }
  }

  function finish(result) {
    const jsonOutput = JSON.stringify(result, null, 2);

    copyToClipboard(jsonOutput)
      .then(() => console.log('✅ JSON copiado para o clipboard!'))
      .catch(() => {
        console.warn('⚠️ Não foi possível copiar para o clipboard. JSON abaixo:');
      });

    console.log(`📊 ${result.articleSlug}`);
    console.log(`   Total: ${result.totalReactions} reações`);
    Object.entries(result.summary).forEach(([type, count]) => {
      console.log(`   ${type}: ${count}`);
    });
    console.log(jsonOutput);

    return result;
  }

  // Encontrar a seção "Reactions History"
  const headers = document.querySelectorAll('h2');
  let rhHeader = null;
  headers.forEach(h => {
    if (h.textContent.trim() === 'Reactions History') rhHeader = h;
  });

  // Edge case: artigo sem reações / sem seção
  if (!rhHeader) {
    console.warn('⚠️ Nenhuma seção "Reactions History" encontrada.');
    return finish({
      articleUrl,
      articleSlug,
      extractedAt: new Date().toISOString(),
      totalReactions: 0,
      reactions: [],
      summary: {}
    });
  }

  const container = rhHeader.parentElement;
  const entries = container.querySelectorAll('.fs-sm.py-2.flex.items-center');

  const reactions = [];
  const summary = {};

  entries.forEach(entry => {
    const imgs = entry.querySelectorAll('img');
    const rightSide = entry.querySelector('.flex-1');
    if (!rightSide) return;

    const infoDivs = rightSide.children;

    // Tipo de reação: texto antes da quebra de linha no primeiro div filho
    const rawText = infoDivs[0]?.textContent.trim() || '';
    const reactionType = rawText.split('\n')[0].trim();

    // Usuário
    const link = infoDivs[0]?.querySelector('a');
    const username = link?.textContent.trim() || 'unknown';
    const userProfileUrl = link?.href || '';

    // Avatar
    const userAvatarUrl = imgs[0]?.src || '';

    // Data (mantida como string original, sem parsear — evita bugs de timezone)
    const date = infoDivs[1]?.textContent.trim() || '';

    reactions.push({
      type: reactionType,
      username,
      userProfileUrl,
      userAvatarUrl,
      date
    });

    summary[reactionType] = (summary[reactionType] || 0) + 1;
  });

  return finish({
    articleUrl,
    articleSlug,
    extractedAt: new Date().toISOString(),
    totalReactions: reactions.length,
    reactions,
    summary
  });
})();
```

## Edge cases cobertos

- **Artigo sem reações ou sem seção "Reactions History"**: o script loga um
  aviso e ainda assim monta o JSON estruturado, com `totalReactions: 0`,
  `reactions: []` e `summary: {}`.
- **Falha ao copiar automaticamente** (ex: foco do browser está no painel do
  DevTools, não na página): o script tenta um fallback via `execCommand('copy')`
  antes de desistir; se mesmo assim falhar, o JSON completo é impresso no
  console para cópia manual.
- **Página incorreta**: se o script for executado fora de uma URL terminada em
  `/stats`, ele loga um erro explicativo e interrompe a execução.

## Saída esperada

```json
{
  "articleUrl": "https://dev.to/user/article",
  "articleSlug": "article-slug",
  "extractedAt": "2026-06-27T00:00:00.000Z",
  "totalReactions": 6,
  "reactions": [
    {
      "type": "like",
      "username": "Some User",
      "userProfileUrl": "https://dev.to/someuser",
      "userAvatarUrl": "https://media2.dev.to/dynamic/image/...",
      "date": "Jun 27"
    }
  ],
  "summary": {
    "like": 2,
    "fire": 1,
    "unicorn": 1
  }
}
```