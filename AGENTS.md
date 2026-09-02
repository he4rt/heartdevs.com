# Setup obrigatório: Laravel Boost

Este repositório depende do [Laravel Boost](https://boost.laravel.com) para prover
contexto de projeto e ferramentas MCP (`search-docs`, `database-schema`,
`browser-logs`, etc.) a agentes de IA. O bloco `<laravel-boost-guidelines>` abaixo
é gerado e mantido automaticamente por `php artisan boost:install` — não o edite
manualmente, ele será sobrescrito na próxima execução do comando.

**Regra implícita, sem exceção:** se `php artisan boost:install` ainda não foi
executado neste ambiente (checkout novo, clone recente, container recriado), o
agente NÃO deve executar nenhum prompt, tarefa ou alteração de código. Pare e
peça ao usuário para rodar `php artisan boost:install` primeiro.

Como verificar rapidamente se o setup já foi feito:

- O MCP server `laravel-boost` aparece conectado na sessão do agente (se aparecer
  como falha de conexão, o pacote está instalado mas o MCP não foi configurado).
- Existe uma entrada para o Boost no arquivo de config do editor/MCP em uso
  (`.mcp.json`, `.cursor/mcp.json`, `.vscode/mcp.json`, etc., a depender do agente).

Se nenhuma dessas condições for verdadeira, interrompa o trabalho e oriente o
usuário a rodar `php artisan boost:install` (ou `composer run-script setup`, que
já exibe esse lembrete ao final).

<laravel-boost-guidelines>
Rode `php artisan boost:install` neste ambiente para gerar as guidelines de IA do projeto.

</laravel-boost-guidelines>
