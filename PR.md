## Summary

- Ajusta a escalada de penalidades para alinhar com as regras esperadas pelos testes.
- Corrige o teste de execucao para validar plataforma realmente nao suportada.

## Changes

- `app-modules/moderation/src/Classification/Actions/Advisors/HistoryBasedPenaltyAdvisor.php`
- `app-modules/moderation/tests/Feature/Enforcement/ExecuteActionTest.php`

## Testing

- `vendor/bin/pest --ci --parallel`

## Notes

- O teste "skips platforms not in target list" foi ajustado porque o Discord agora e uma plataforma registrada; antes, nao existia adapter Discord e o teste passava mesmo com `discord` na lista. Agora a lista usa `skype` (plataforma inexistente) para garantir que a execucao seja realmente ignorada.
