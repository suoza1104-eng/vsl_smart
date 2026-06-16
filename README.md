# VSL Smart

Sistema simples em PHP 8.1+, MySQL/MariaDB e JavaScript puro para página pública de VSL, captura de leads, testes de headlines/ofertas, dashboard e webhook.

## Estrutura

```txt
admin/      painel, login e logout
api/        endpoints JSON
assets/     CSS e JavaScript
includes/   configuração, banco, helpers, autenticação e webhook
install/    SQL e gerador de hash de senha
index.php   página pública da VSL
```

## Instalação no cPanel

1. Crie um banco MySQL no cPanel.
2. Crie um usuário do banco e vincule ao banco com todas as permissões.
3. Edite `includes/config.php`:
   - `BASE_URL`
   - `DB_HOST`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
   - `SUPERFUNCIONARIO_WEBHOOK_URL`, se for usar webhook
   - `SUPERFUNCIONARIO_TOKEN`, se o webhook exigir token
4. Importe `install/database.sql` pelo phpMyAdmin.
5. Acesse `/install/install.php`, gere um hash para sua senha admin e substitua `ADMIN_PASS_HASH` em `includes/config.php`.
6. Remova ou bloqueie a pasta `install` após configurar.

Usuário padrão inicial: `admin`  
Senha padrão inicial: `admin123`

Troque a senha antes de colocar em produção.

## Como usar

- Página pública da VSL: `/index.php`
- Admin: `/admin/login.php`

No admin você pode:

- Cadastrar, editar, ativar, desativar e excluir headlines.
- Cadastrar, editar, ativar, desativar e excluir ofertas.
- Configurar o link ou embed/script do vídeo vTurb.
- Ver visitas, visitantes únicos, leads, cliques, CTR e taxas.
- Filtrar por data, headline, oferta, UTM, dispositivo e botão.
- Acompanhar os logs do webhook.

## Vídeo vTurb

No painel, em **Vídeo VSL vTurb**, cole uma das opções:

- URL de embed do player.
- Código `<iframe>`.
- Código/script fornecido pelo vTurb.

A página pública renderiza o valor salvo em `settings.vturb_embed`.

## Webhook SuperFuncionário

Quando um lead é cadastrado, o sistema salva o lead e tenta enviar um webhook com:

- Dados do lead.
- Visitante.
- Headline vista.
- Oferta vista.
- UTM.
- Data de criação.

Mesmo se o webhook falhar, o lead permanece salvo. A tentativa aparece em `webhook_logs` e no painel.

## Observações de produção

- Defina `DEBUG_MODE` como `false` em `includes/config.php`.
- Remova `/install` após a instalação.
- Mantenha PHP 8.1+ com extensão PDO MySQL e cURL habilitadas.
- O Chart.js é carregado via CDN somente no admin.

