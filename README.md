# Gestão de Amostras — Teste Prático

Mini-módulo de gestão de amostras de um laboratório ambiental: cadastro, listagem com
filtros e transição de status governada por regras de negócio.

- **Backend:** PHP 8 + Slim Framework, Clean Architecture, MySQL
- **Frontend:** Next.js (App Router) + TypeScript + React Query
- **Testes:** PHPUnit — 87 testes cobrindo as regras de negócio 1 a 5

```
backend/    src/Domain (entidade, enums, value object, regras) · src/Application (casos de
            uso, DTOs) · src/Infrastructure (controller Slim, repositório MySQL, middlewares)
            tests/Unit · tests/Integration · tests/Support · database/*.sql
frontend/   app/ (páginas e componentes) · lib/ (tipos e cliente HTTP)
docs/       COMO-TESTAR.md (roteiro manual) · bruno/ (13 requisições da API)
```

---

## Como rodar

**Pré-requisito: Docker com Docker Compose — e mais nada.** PHP, Composer, Node e MySQL
rodam dentro dos containers. Da raiz do repositório:

```bash
docker compose up
```

A primeira execução constrói as imagens e leva alguns minutos; as seguintes são quase
imediatas. Depois:

| Serviço | Endereço |
| --- | --- |
| Interface | <http://localhost:3001> |
| API | <http://localhost:8081> |
| MySQL | `localhost:3307` |

Para confirmar que a API subiu: `curl -i http://localhost:8081/health` → `200` e
`{"status":"ok"}`.

Comandos úteis: `docker compose up -d` (segundo plano), `logs -f api` (log de um serviço),
`down` (para, preservando o banco), `down -v` (para e **apaga** o banco).

⚠️ **As portas não são as padrão** (MySQL 3307, API 8081, frontend 3001): a máquina onde o
projeto foi escrito já tinha as usuais ocupadas. Nenhuma está fixa no código — todas saem do
`docker-compose.yml`. Ao trocar a porta do frontend, atualize também
`CORS_ORIGENS_PERMITIDAS`, senão o navegador bloqueia as chamadas à API.

**Não é preciso criar `.env`:** no Docker a configuração vem do bloco `environment:` do
compose. **A tabela também não precisa ser criada na mão:** os `.sql` montados em
`/docker-entrypoint-initdb.d/` criam a tabela `amostras` e o banco de teste — mas rodam
**apenas em volume novo**. Para recriar do zero: `docker compose down -v && docker compose up`.

| Variável | Padrão | Para que serve |
| --- | --- | --- |
| `DB_HOST` / `DB_PORT` | `mysql`/`3306` no Docker; `127.0.0.1`/`3307` fora | onde o MySQL está |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | `ultralims` | banco e credenciais |
| `CODIGO_PREFIXO` | `ISABELLA` | prefixo do código: `{PREFIXO}-{ANO}-{SEQUENCIAL}` |
| `APP_DEBUG` | `true` | em erro 500, mostra a mensagem real (**só desenvolvimento**) |
| `CORS_ORIGENS_PERMITIDAS` | `http://localhost:3001` | origens autorizadas, separadas por vírgula |
| `NEXT_PUBLIC_API_URL` | `http://localhost:8081` | endereço da API usado pelo frontend |

### Rodar sem Docker

Para quem já tem PHP 8.2+ (com `pdo_mysql`), Composer e Node 20. Três abas de terminal:

```bash
docker compose up -d mysql          # só o banco

cd backend                          # aba 2 — API em http://localhost:8081
cp .env.example .env && composer install && composer start

cd frontend                         # aba 3 — interface em http://localhost:3001
cp .env.example .env.local && npm install && npm run dev
```

Aqui o `.env` é obrigatório — fora do compose ninguém define as variáveis. Ele aponta para
`127.0.0.1:3307` porque, de fora da rede do Docker, o MySQL só é alcançável pela porta publicada.

---

## Como rodar os testes

Com os containers no ar:

```bash
docker compose exec api ./vendor/bin/phpunit --testdox
```

Sem Docker, a partir de `backend/`: `./vendor/bin/phpunit --testdox`. O `--testdox` imprime
cada teste como uma frase legível — a saída vira uma lista de requisitos verificados.

Saída atual: **`OK (87 tests, 117 assertions)`** — 80 unitários (não precisam de banco) e
7 de integração (precisam). Para rodar só uma suíte: `--testsuite Unit` ou `--testsuite Integration`.

**As regras não precisam de banco nem servidor para serem testadas:** elas moram na entidade
`Amostra` e no enum `StatusAmostra`, que não conhecem SQL nem HTTP, e os testes de caso de uso
usam um repositório em memória (`tests/Support/`) no lugar do MySQL. A suíte roda em menos de
um segundo e falha por regra quebrada, nunca por banco fora do ar.

Roteiro de verificação manual ponta a ponta em **[`docs/COMO-TESTAR.md`](docs/COMO-TESTAR.md)**;
coleção Bruno em `docs/bruno/` (*Open Collection*, environment `local`).

---

## A API

Base: `http://localhost:8081`

| Verbo | Rota | Corpo | Resposta |
| --- | --- | --- | --- |
| `GET` | `/health` | — | `200` `{"status":"ok"}` |
| `POST` | `/amostras` | `{"tipo", "data_recebimento", "responsavel_tecnico"?}` | `201` + amostra |
| `GET` | `/amostras?status=&tipo=` | — | `200` + lista |
| `GET` | `/amostras/{id}` | — | `200` + amostra, ou `404` |
| `PATCH` | `/amostras/{id}/status` | `{"status", "data_conclusao"?, "responsavel_tecnico"?}` | `200` + amostra, ou `422` |

- `tipo`: `Agua`, `Solo`, `Ar`, `Efluente` · `status`: `Recebida`, `EmAnalise`, `Concluida`,
  `Rejeitada` · datas em ISO (`2026-08-19`)
- O `codigo` é gerado pelo backend: sequencial de 4 dígitos, único por prefixo, **reinicia a
  cada ano** (o ano é o da `data_recebimento`). Ex.: `ISABELLA-2026-0001`.

```json
{ "id": 1, "codigo": "ISABELLA-2026-0001", "tipo": "Agua", "status": "Recebida",
  "responsavel_tecnico": null, "data_recebimento": "2026-08-10", "data_conclusao": null }
```

**Erros** saem sempre no mesmo envelope, `{"erro": "mensagem"}`: `400` payload inválido ·
`404` amostra ou rota inexistente · `405` método não permitido · `422` regra de negócio
violada · `500` erro inesperado. A distinção entre `400` e `422` é proposital: `400` é "seu
pedido está malformado", `422` é "o pedido está bem formado, mas viola uma regra".

**Por que `PATCH /amostras/{id}/status` e não `PUT /amostras/{id}`:** transição de status não
é edição de campo — tem pré-condições (responsável preenchido, data válida, estado de origem
permitido) que uma atualização comum não tem. Uma rota própria concentra essas regras, e o
`PATCH` comunica que só aquele pedaço do recurso muda.

---

## Regras de negócio

1. Toda amostra nasce com status **Recebida**.
2. Só vai para **EmAnalise** se o `responsavel_tecnico` estiver preenchido.
3. Só vai para **Concluida** se o status atual for `EmAnalise`, houver `data_conclusao` e ela
   for **maior ou igual** à `data_recebimento`.
4. Vai para **Rejeitada** a partir de `Recebida` ou `EmAnalise` — nunca de `Concluida`.
5. **Concluida** e **Rejeitada** são estados finais: não podem mais ser alterados.

```
[Recebida] ──(tem responsável)──> [EmAnalise] ──(data_conclusao ok)──> [Concluida] (final)
     │                                 │
     └──────────────┬──────────────────┘
                    v
              [Rejeitada] (final)
```

| Regra | Onde no código |
| --- | --- |
| 1 | `Amostra::criar()` |
| 2 | `Amostra::garantirResponsavelTecnicoPreenchido()` |
| 3 | `StatusAmostra::transicoesPermitidas()` + `Amostra::registrarConclusao()` |
| 4 | `StatusAmostra::transicoesPermitidas()` |
| 5 | `StatusAmostra::ehFinal()` + `Amostra::garantirQueNaoEstaFinalizada()` |

Todas em `backend/src/Domain/`, cobertas por `tests/Unit/Domain/AmostraTest.php`.

---

## Decisões técnicas

**Clean Architecture — a seta aponta para dentro.** `Infrastructure → Application → Domain`.
O Domain (entidade, enums, value object, exceptions, interface do repositório) não importa
nada das camadas de fora. É isso que permite testar as regras sem subir banco nem servidor, e
trocar o MySQL sem tocar nas regras.

**O mapa de transições mora no enum `StatusAmostra`.** "Quem pode virar quem" é pergunta sobre
o *status*, não sobre a amostra. A alternativa — uma cadeia de `if` no controller — espalharia
a regra por cada rota e exigiria banco e servidor no ar para testá-la.

**Construtor privado: `criar()` e `restaurar()`.** Não existe `new Amostra(...)`. Ou nasce
`Recebida` pelo `criar()` (regra 1), ou vem do banco pelo `restaurar()` — que não valida
transição porque não está criando um fato novo. Efeito: não existe amostra em estado inválido.

**`CodigoAmostra` como Value Object.** O código não é `string` solta: é classe imutável que só
existe no formato correto. Formato é regra de negócio, então mora no Domain.

**Uma hierarquia de exceptions, um middleware.** Toda exception de regra herda de
`RegraDeNegocioException`; o `TratadorDeErros` captura só esse tipo e devolve `422`. Regra
nova não exige mexer na camada HTTP.

**Prefixo do código em variável de ambiente.** `CODIGO_PREFIXO` no `.env`, não fixo no código:
prefixo é dado de configuração, não regra. `CodigoAmostra::gerar()` recebe e não decide nada.

**Unicidade do código: índice `UNIQUE` + retry limitado a 3.** Quem decide a unicidade é o
banco, não a aplicação — um `SELECT MAX()+1` sozinho tem condição de corrida real (dois
pedidos leem o mesmo número). O retry é invisível: ninguém pediu "a amostra 6", colisão é
problema interno. E é limitado porque retry infinito transforma bug em travamento.

**Ambiente inteiro em containers.** O compose é a receita exata (versões, credenciais, portas)
em vez de um README pedindo "instale PHP 8.2 com `pdo_mysql`…" — que era o tropeço mais
provável. Os dados ficam em volume nomeado, não em pasta do projeto. Três detalhes que isso
exigiu:

1. **`NEXT_PUBLIC_API_URL` continua `localhost:8081`, não `api:8081`.** A chamada à API parte
   do **navegador**, que não enxerga a rede do Docker. Nome de serviço vale entre containers
   (é assim que a API fala com o MySQL); porta publicada vale para o navegador.
2. **A imagem serve em `0.0.0.0:8081`.** `php -S localhost` só aceitaria conexão de dentro do
   container, e a porta publicada não responderia.
3. **`force="true"` no `DB_DATABASE` do `phpunit.xml`.** O `<env>` do PHPUnit não sobrescreve
   variável existente; dentro do container ela existe valendo `ultralims`. Sem o `force`, os
   testes de integração rodariam contra o banco de desenvolvimento e o `DELETE FROM` do
   `setUp` apagaria dados reais.

**`schema.sql` aplicado pelo próprio MySQL.** Elimina passos manuais sem ferramenta nova. O
`banco-de-teste.sql` termina com `SOURCE` do schema em vez de repetir o `CREATE TABLE`, então
a estrutura é declarada num lugar só. Não há migration porque é uma tabela só: Phinx custaria
instalação, configuração e manutenção sem benefício neste tamanho.

**`tipo` e `status` como `VARCHAR`, não `ENUM` do MySQL.** A lista de valores válidos já existe
nos enums do Domain; repetir criaria duas fontes de verdade que podem divergir. O banco guarda
— não legisla.

**Filtros tipados na interface do repositório.** `listar(?StatusAmostra, ?TipoAmostra)`, não
`(?string, ?string)`. A conversão acontece uma vez, na borda HTTP: texto inválido vira `400`
com mensagem clara, em vez de lista vazia silenciosa.

**CORS no backend, não com proxy do Next.** Uma API que só funciona atrás do proxy de um
framework está acoplada a ele — o rewrite esconderia o problema em vez de resolvê-lo. O
middleware responde o preflight `OPTIONS` sozinho (`204`), é o mais externo da pilha (senão as
respostas de erro sairiam sem cabeçalho de CORS e o navegador esconderia justamente a mensagem
de `422` que a tela precisa exibir) e ecoa a origem recebida com `Vary: Origin`, em vez de `*`.

**Frontend: TypeScript e React Query.** O formato da `Amostra` é declarado uma vez em
`lib/tipos.ts` — campo errado vira erro no editor, não tela em branco. O React Query cuida de
cache e dos estados de carregando/erro: depois de um `PATCH`, a listagem se atualiza por
invalidação. O cliente (`lib/api.ts`) converte resposta de erro em exceção, porque `fetch`
**não** lança em `404`/`422` — sem checar `resposta.ok`, a tela trataria `{"erro": "…"}` como
se fosse uma amostra. **O mapa de transições não foi reimplementado no frontend:** a tela
oferece os destinos e a API decide; a única duplicação consciente é esconder a ação em estado
final, para não oferecer algo que sempre falharia.

**Nomes: teste em inglês, domínio em português.** `testShouldFazerAlgo` é a convenção do
PHPUnit e força o nome a descrever comportamento — com `--testdox`, vira lista de requisitos.
O domínio fica em português porque são os termos do enunciado, e os valores de status vão para
o banco e o JSON exatamente como a especificação escreve.

**Acentuação: mensagens sim, identificadores não.** Texto lido por gente leva acento; valores
de `tipo` e `status` não, porque são identificadores que vão para query string e para o banco.
O acento é resolvido na exibição: `ROTULOS_DE_STATUS` (`lib/tipos.ts`) no frontend e
`StatusAmostra::rotulo()` no backend, usado pelas mensagens de erro. Sem isso a tela mostrava
"de EmAnalise para Recebida", grudado, enquanto a listagem ao lado exibia "Em análise".

**Sem comentários no código.** Onde caberia um `// Regra 2: …` existe um método chamado
`garantirResponsavelTecnicoPreenchido()`, que diz o mesmo e não pode divergir do que o código
faz. O "porquê" está aqui e nas mensagens de commit.

---

## Limitações conhecidas

- **`APP_DEBUG=true` é configuração de desenvolvimento** — em produção seria `false`, e o `500`
  devolveria apenas "Erro interno no servidor.".
- **Sem autenticação** — o enunciado não pede, e adicionar tokens aumentaria a superfície do
  projeto sem acrescentar nada ao que está sendo avaliado.
- **Sem paginação** — para o volume do exercício os filtros bastam; com dados reais,
  `GET /amostras` precisaria de `limit`/`offset`.
- **O sequencial do código vai até `9999` por ano**, consequência do formato de 4 dígitos.
- **Os containers são de desenvolvimento** — a API roda em `php -S` (single-thread) e o
  frontend em `next dev`. Em produção seriam PHP-FPM atrás de Nginx e `next build`/`start`.
- **O `<input type="date">` segue o idioma do navegador** — exibe `mm/dd/aaaa` em navegador em
  inglês; o valor enviado é sempre ISO.
