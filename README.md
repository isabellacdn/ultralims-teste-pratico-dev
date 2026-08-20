# Gestão de Amostras: Teste Prático

Mini-módulo de gestão de amostras de laboratório ambiental: cadastro, listagem com filtros e
transição de status governada pelas regras de negócio do enunciado.

- **Backend:** PHP 8 + Slim, Clean Architecture (`Domain` / `Application` / `Infrastructure`), MySQL
- **Frontend:** Next.js (App Router) + TypeScript + React Query
- **Testes:** PHPUnit, 87 testes cobrindo as regras 1 a 5

---

## Como rodar

Pré-requisito: **Docker com Docker Compose**. PHP, Composer, Node e MySQL rodam nos containers.

```bash
docker compose up
```

| Serviço | Endereço |
| --- | --- |
| Interface | `localhost:3001` |
| API | `localhost:8081` |
| MySQL | `localhost:3307` |

### Sem Docker

Com PHP 8.2+ (`pdo_mysql`), Composer e Node 20, em três terminais:

```bash
docker compose up -d mysql                                        # só o banco

cd backend  && cp .env.example .env      && composer install && composer start
cd frontend && cp .env.example .env.local && npm install     && npm run dev
```

---

## Como rodar os testes

```bash
docker compose exec api ./vendor/bin/phpunit --testdox     # ou, em backend/: ./vendor/bin/phpunit --testdox
```

Saída atual: `OK (87 tests, 117 assertions)`, sendo 80 unitários (sem banco) e 7 de integração.
Filtrar por suíte: `--testsuite Unit` ou `--testsuite Integration`.

Roteiro manual ponta a ponta em [`docs/COMO-TESTAR.md`](docs/COMO-TESTAR.md); coleção Bruno em
`docs/bruno/`.

---

## API

| Verbo | Rota | Corpo | Resposta |
| --- | --- | --- | --- |
| `GET` | `/health` | sem corpo | `200` `{"status":"ok"}` |
| `POST` | `/amostras` | `{"tipo", "data_recebimento", "responsavel_tecnico"?}` | `201` + amostra |
| `GET` | `/amostras?status=&tipo=` | sem corpo | `200` + lista |
| `GET` | `/amostras/{id}` | sem corpo | `200` + amostra, ou `404` |
| `PATCH` | `/amostras/{id}/status` | `{"status", "data_conclusao"?, "responsavel_tecnico"?}` | `200` + amostra, ou `422` |

`tipo`: `Agua`, `Solo`, `Ar`, `Efluente` · `status`: `Recebida`, `EmAnalise`, `Concluida`,
`Rejeitada` · datas em ISO. O `codigo` é gerado pelo backend no formato
`{PREFIXO}-{ANO}-{SEQUENCIAL}` (ex.: `ISABELLA-2026-0001`), com sequencial de 4 dígitos que
reinicia a cada ano.

---

## Decisões técnicas

**As regras de negócio moram no Domain.** `Infrastructure → Application → Domain`: o Domain não
importa nada das camadas de fora, então as cinco regras são testáveis sem subir banco nem
servidor. O mapa "quem pode virar quem" fica no enum `StatusAmostra`.

**Amostra sem construtor público.** Ou nasce `Recebida` por `criar()` (regra 1), ou vem do banco
por `restaurar()`. Não existe amostra em estado inválido.

**`CodigoAmostra` é Value Object.** Formato é regra de negócio, então é classe imutável no
Domain, não `string` solta. O prefixo vem de `CODIGO_PREFIXO` no ambiente, porque é
configuração, não regra.

**Uma hierarquia de exceptions, um middleware.** Toda exception de regra herda de
`RegraDeNegocioException` e o `TratadorDeErros` a converte em `422`.

**Acentuação: mensagens sim, identificadores não.** `tipo` e `status` viajam sem acento porque vão
para query string e banco; o acento é resolvido na exibição (`ROTULOS_DE_STATUS` no frontend,
`StatusAmostra::rotulo()` nas mensagens de erro).

**Ambiente todo em containers.** O compose é a receita exata de versões, credenciais e portas.
