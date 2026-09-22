# GoDoPay — API

API de uma carteira digital: contas, transferências entre contatos, depósitos e estorno.

Laravel 12 (PHP 8.5), MySQL e autenticação via Sanctum.

O front que consome esta API fica no repositório `godopay-front`.

## Rodando
Renomeie o arquivo `.env.example` para `.env`

Precisa apenas de Docker.

```bash
docker compose up -d
```

Na primeira subida o container instala as dependências, roda as migrations e popula o banco — pode levar alguns minutos.

### Usuário de teste

```
e-mail: test@example.com
senha:  123456
```

## Padrões

**Valores em centavos.** `balance` e `amount` são `bigInteger` — R$ 10,00 é `1000`. Nunca use float para dinheiro.

**Regras de negócio em Services.** `MakeTransfer`, `RevertTransfer` e `CreateAccount` concentram a lógica; os controllers só orquestram. Transferências usam `lockForUpdate()` para evitar corrida.

**Validação em FormRequests**, com escopo por usuário onde faz sentido — por exemplo, `contact_id` no `MakeTransferRequest` só aceita contatos de quem está autenticado.

**Mensagens de erro em português**, direto no código. Traduções de validação em `lang/pt_BR/`.

**Formatação:** rode `vendor/bin/pint` antes de finalizar.

## Logs

Tudo em `storage/logs/laravel.log`:

- Exceções, com URL, método e usuário (`bootstrap/app.php`)
- Eventos de negócio via observers — transação, conta e contato

Os observers usam `afterCommit`, então nada que sofra rollback aparece no log.

```bash
tail -f storage/logs/laravel.log
```

## Endpoints

| Método | Rota | O que faz |
|---|---|---|
| POST | `/register` | cria usuário e primeira conta |
| POST | `/login` | autentica |
| POST | `/refresh` | renova o access token |
| POST | `/logout` | revoga os tokens |
| GET | `/me` | usuário e conta atual |
| GET | `/accounts` | contas do usuário |
| POST | `/account/create` | cria conta |
| PATCH | `/switch-account/{account}` | troca a conta ativa |
| POST | `/deposit/{account}` | deposita |
| GET | `/contacts` | contatos |
| POST | `/contacts` | adiciona contato por ag/conta/dígito |
| POST | `/transfer` | transfere para um contato |
| POST | `/return/{transaction}` | estorna |
| GET | `/transactions` | extrato, com filtro e paginação |
| GET | `/transactions/{transaction}` | comprovante |

O access token dura 1 hora; o refresh, 1 dia. Um refresh renova o access sem prorrogar o próprio refresh, então após 24h é preciso logar de novo.
