---
title: Gerenciamento de E-mails
icon: heroicon-o-envelope
order: 2
---

# Gerenciamento de E-mails

A plataforma inclui um sistema integrado de gerenciamento de e-mails para monitorar e revisar e-mails enviados. Navegue até a seção **Sistema** para acessá-lo.

## Visualizando E-mails Enviados

O log de e-mails mostra todos os e-mails enviados pela plataforma, incluindo:

- **Destinatário** — Para quem o e-mail foi enviado
- **Assunto** — A linha de assunto do e-mail
- **Status** — Se o e-mail foi enviado com sucesso
- **Data/Hora** — Quando o e-mail foi despachado

## Pré-visualização de E-mail

Você pode clicar em qualquer entrada de e-mail para pré-visualizar seu conteúdo como foi enviado. Isso é útil para verificar se os templates foram renderizados corretamente e se as informações corretas foram incluídas.

## Solução de Problemas

Se os e-mails não estão sendo entregues:

1. Verifique se o driver de e-mail está configurado corretamente (SMTP, SES, Mailgun, etc.)
2. Confirme que o worker de fila está em execução se seus e-mails estão enfileirados
3. Revise os logs de e-mail para quaisquer erros relacionados ao envio de e-mails
4. Entre em contato com o administrador do sistema para questões de configuração do servidor de e-mail
