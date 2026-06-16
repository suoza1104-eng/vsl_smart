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
   - `SUPERFUNCIONARIO_TOKEN`, token da API do SuperFuncionário
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

## Integração SuperFuncionário

Toda a comunicação com o SuperFuncionário fica centralizada em `includes/superfuncionario.php`.
Quando um lead é cadastrado, o sistema salva o lead e tenta sincronizar o contato com:

- Dados do lead.
- Visitante.
- Headline vista.
- Oferta vista.
- UTM.
- Data de criação.
- Tags como `novo-cadastro`, `lead` e `produto-{nome}`.
- Campos personalizados como `user_id`, `telefone`, `produto`, `data_cadastro`, `origem`, `ultimo_evento` e `ultima_sincronizacao`.

Mesmo se o SuperFuncionário falhar ou estiver fora do ar, o lead permanece salvo. A tentativa aparece em `webhook_logs` e no painel.

Configuração por variáveis de ambiente:

```txt
SUPERFUNCIONARIO_BASE_URL=https://app.superfuncionario.com.br/api
SUPERFUNCIONARIO_TOKEN=...
SUPERFUNCIONARIO_TIMEOUT=10
SUPERFUNCIONARIO_CONNECT_TIMEOUT=4
```

Conforme a Swagger oficial, a autenticação usa o header `X-ACCESS-TOKEN`. O serviço cria contatos em `POST /contacts`, busca existentes por `GET /contacts/find_by_custom_field`, aplica tags por `POST /contacts/{contact_id}/tags/{tag_id}` e campos por `POST /contacts/{contact_id}/custom_fields/{custom_field_id}`.

Para novos eventos do sistema, importe `includes/superfuncionario.php` e chame:

```php
sf_sync_contact_event(SF_EVENT_PAYMENT_APPROVED, $contact, $context);
```

## Observações de produção

- Defina `DEBUG_MODE` como `false` em `includes/config.php`.
- Remova `/install` após a instalação.
- Mantenha PHP 8.1+ com extensão PDO MySQL e cURL habilitadas.
- O Chart.js é carregado via CDN somente no admin.
