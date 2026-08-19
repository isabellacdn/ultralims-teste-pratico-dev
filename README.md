# Gestão de Amostras — Teste Prático

Mini-módulo de gestão de amostras de um laboratório ambiental: cadastro de amostras,
listagem com filtros e transição de status governada por regras de negócio.

- **Backend:** PHP 8 + Slim Framework, Clean Architecture, MySQL
- **Frontend:** Next.js (App Router) + TypeScript + React Query
- **Testes:** PHPUnit — 79 testes cobrindo as regras de negócio 1 a 5

---

## Sumário

- [Estrutura do repositório](#estrutura-do-repositório)
- [Pré-requisitos](#pré-requisitos)
- [Portas fora do padrão](#portas-fora-do-padrão)
- [Como rodar](#como-rodar)
- [Como rodar os testes](#como-rodar-os-testes)
- [A API](#a-api)
- [Regras de negócio](#regras-de-negócio)
- [Decisões técnicas](#decisões-técnicas)
- [Limitações conhecidas](#limitações-conhecidas)

---

## Estrutura do repositório

```
desafio-dev/
├── backend/              # API em PHP + Slim
│   ├── database/
│   │   └── schema.sql    # estrutura da tabela amostras
│   ├── public/index.php  # entrada da aplicação: middlewares e rotas
│   ├── src/
│   │   ├── Domain/          # entidade, enums, value object, regras, contratos
│   │   ├── Application/     # casos de uso e DTOs
│   │   └── Infrastructure/  # controller, repositório MySQL, middlewares, config
│   └── tests/
│       ├── Unit/            # regras de negócio e casos de uso (sem banco)
│       ├── Integration/     # repositório MySQL contra o banco real
│       └── Support/         # repositório em memória usado nos testes
├── frontend/             # interface em Next.js
│   ├── app/                 # páginas e componentes
│   └── lib/                 # tipos da amostra e cliente HTTP da API
├── docs/
│   ├── COMO-TESTAR.md       # roteiro de verificação manual ponta a ponta
│   └── bruno/               # coleção Bruno com as 13 requisições da API
└── docker-compose.yml    # MySQL 8
```

A separação em `Domain / Application / Infrastructure` é descrita em
[Decisões técnicas](#decisões-técnicas).

---

## Pré-requisitos

| Ferramenta | Versão | Observação |
| --- | --- | --- |
| PHP | 8.2 ou superior | precisa da extensão **`pdo_mysql`** — no Ubuntu: `sudo apt install php8.3-mysql` |
| Composer | 2.x | gerenciador de dependências do PHP |
| Node.js | 20 ou superior | com npm |
| Docker | com Docker Compose | usado **apenas** para o MySQL |

Conferir a extensão do PHP antes de começar (sem ela a API sobe e quebra na primeira
consulta ao banco):

```bash
php -m | grep pdo_mysql
```

---

## Portas fora do padrão

⚠️ Este projeto **não usa as portas padrão**, porque a máquina de desenvolvimento onde ele
foi escrito já tinha as portas usuais ocupadas por outros serviços:

| Serviço | Porta usada | Porta padrão |
| --- | --- | --- |
| MySQL | **3307** | 3306 |
| API | **8081** | 8000 |
| Frontend | **3001** | 3000 |

Nenhuma delas está fixa no código — todas saem de configuração:

| Porta | Onde muda |
| --- | --- |
| MySQL | `docker-compose.yml` (lado esquerdo de `"3307:3306"`) **e** `DB_PORT` em `backend/.env` |
| API | script `start` em `backend/composer.json` **e** `NEXT_PUBLIC_API_URL` em `frontend/.env.local` |
| Frontend | script `dev` em `frontend/package.json` **e** `CORS_ORIGENS_PERMITIDAS` em `backend/.env` |

> Se trocar a porta do frontend, é obrigatório atualizar `CORS_ORIGENS_PERMITIDAS`, senão
> o navegador bloqueia as chamadas à API.

---

## Como rodar

São três processos independentes: banco, API e frontend. Use uma aba de terminal para
cada um — as abas da API e do frontend ficam ocupadas enquanto os serviços estiverem no ar.

### 1. Subir o banco

```bash
docker compose up -d
```

O container tem *healthcheck*. Espere aparecer `healthy` antes de seguir:

```bash
docker compose ps
```

### 2. Criar a tabela

```bash
docker exec -i ultralims-mysql mysql -uultralims -pultralims ultralims \
  < backend/database/schema.sql
```

O arquivo usa `CREATE TABLE IF NOT EXISTS`, então rodar de novo não quebra nada.

### 3. Configurar e subir a API

```bash
cd backend
cp .env.example .env
composer install
composer start
```

A API sobe em `http://localhost:8081`. Para confirmar:

```bash
curl -i http://localhost:8081/health
```

Esperado: `HTTP/1.1 200 OK` e `{"status":"ok"}`.

#### As variáveis do `.env`

| Variável | Padrão | Para que serve |
| --- | --- | --- |
| `DB_HOST` / `DB_PORT` | `127.0.0.1` / `3307` | onde o MySQL do Docker está publicado |
| `DB_DATABASE` | `ultralims` | banco de desenvolvimento |
| `DB_USERNAME` / `DB_PASSWORD` | `ultralims` / `ultralims` | credenciais criadas pelo `docker-compose.yml` |
| `CODIGO_PREFIXO` | `ISABELLA` | prefixo do código da amostra: `{PREFIXO}-{ANO}-{SEQUENCIAL}` |
| `APP_DEBUG` | `true` | em erro 500, mostra a mensagem real. **Configuração de desenvolvimento** — em produção seria `false`, e a resposta viraria apenas "Erro interno no servidor." |
| `CORS_ORIGENS_PERMITIDAS` | `http://localhost:3001` | origens autorizadas a chamar a API pelo navegador, separadas por vírgula |

### 4. Subir o frontend

Em outra aba, a partir da raiz do repositório:

```bash
cd frontend
cp .env.example .env.local
npm install
npm run dev
```

A interface abre em `http://localhost:3001`. Ela tem uma tela só: listagem com contagem
por status, filtros de status e tipo, botão **+ Nova amostra** (cadastro em modal) e a
ação de transição de status em cada linha da tabela.

A única variável do frontend é `NEXT_PUBLIC_API_URL`. O prefixo `NEXT_PUBLIC_` é
obrigatório: sem ele a variável não chega ao navegador, que é de onde as chamadas partem.

---

## Como rodar os testes

Os testes de integração usam um banco **separado** (`ultralims_teste`), para nunca apagar
os dados de desenvolvimento. Criar esse banco uma única vez, com o container no ar:

```bash
docker exec ultralims-mysql mysql -uroot -proot \
  -e "CREATE DATABASE IF NOT EXISTS ultralims_teste CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; \
      GRANT ALL PRIVILEGES ON ultralims_teste.* TO 'ultralims'@'%'; FLUSH PRIVILEGES;"

docker exec -i ultralims-mysql mysql -uultralims -pultralims ultralims_teste \
  < backend/database/schema.sql
```

Rodar a suíte completa:

```bash
cd backend
./vendor/bin/phpunit --testdox
```

O `--testdox` imprime cada teste como uma frase legível, o que transforma a saída em uma
lista de requisitos verificados — serve como evidência de cobertura das regras.

Saída atual: **`OK (79 tests, 109 assertions)`**.

| Suíte | Testes | Precisa de banco? |
| --- | --- | --- |
| `Unit` | 72 | **não** |
| `Integration` | 7 | sim |

```bash
./vendor/bin/phpunit --testsuite Unit --testdox         # regras de negócio, sem infraestrutura
./vendor/bin/phpunit --testsuite Integration --testdox  # repositório MySQL contra o banco real
```

**Por que os testes das regras não precisam de banco nem servidor:** as regras moram na
entidade `Amostra` e no enum `StatusAmostra`, que não conhecem SQL nem HTTP. Os testes de
caso de uso usam um repositório em memória (`tests/Support/AmostraRepositoryEmMemoria.php`)
no lugar do MySQL — é o que a interface `AmostraRepositoryInterface` permite. O resultado
é uma suíte que roda em menos de um segundo e falha por regra quebrada, nunca por banco fora
do ar.

### Verificação manual

O roteiro completo de verificação ponta a ponta — API pelo Bruno e interface pelo navegador,
com o que esperar em cada passo — está em **[`docs/COMO-TESTAR.md`](docs/COMO-TESTAR.md)**.
A coleção do Bruno fica em `docs/bruno/` (abrir com *Open Collection* e selecionar o
environment `local`).

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

Valores aceitos:

- `tipo`: `Agua`, `Solo`, `Ar`, `Efluente`
- `status`: `Recebida`, `EmAnalise`, `Concluida`, `Rejeitada`
- datas: formato ISO (`2026-08-19`)

### Formato da amostra

```json
{
  "id": 1,
  "codigo": "ISABELLA-2026-0001",
  "tipo": "Agua",
  "status": "Recebida",
  "responsavel_tecnico": null,
  "data_recebimento": "2026-08-10",
  "data_conclusao": null
}
```

O `codigo` é gerado pelo backend no formato `{PREFIXO}-{ANO}-{SEQUENCIAL}`. O sequencial
tem 4 dígitos, é único por prefixo e **reinicia a cada ano** — o ano usado é o da
`data_recebimento`.

### Erros

Sempre no mesmo envelope, `{"erro": "mensagem"}`, com o código HTTP adequado:

| Situação | HTTP |
| --- | --- |
| Payload inválido (campo faltando, valor fora da lista) | `400` |
| Amostra não existe | `404` |
| Rota inexistente | `404` |
| Método não permitido na rota | `405` |
| Regra de negócio violada | `422` |
| Erro inesperado | `500` |

A distinção entre `400` e `422` é proposital: `400` significa "seu pedido está malformado";
`422` significa "o pedido está bem formado, mas viola uma regra de negócio".

### Por que `PATCH /amostras/{id}/status` e não `PUT /amostras/{id}`

Transição de status não é edição de campo: ela tem pré-condições (responsável técnico
preenchido, data de conclusão válida, estado de origem permitido) que uma atualização comum
não tem. Uma rota própria deixa essas regras num lugar só, e o `PATCH` comunica que só
aquele pedaço do recurso está sendo alterado — `PUT` sugeriria substituir a amostra inteira.

---

## Regras de negócio

1. Toda amostra nasce com status **Recebida**.
2. Só vai para **EmAnalise** se o `responsavel_tecnico` estiver preenchido.
3. Só vai para **Concluida** se o status atual for `EmAnalise`, houver `data_conclusao`
   e ela for **maior ou igual** à `data_recebimento`.
4. Vai para **Rejeitada** a partir de `Recebida` ou `EmAnalise` — nunca a partir de `Concluida`.
5. **Concluida** e **Rejeitada** são estados finais: não podem mais ser alterados.

```
                  (criação)
                      |
                      v
                 [Recebida] ----------------+
                      |                     |
     (tem responsável)|                     |
                      v                     v
                [EmAnalise] ---------> [Rejeitada]  (final)
                      |
 (data_conclusao ok)  |
                      v
                [Concluida]  (final)
```

### Onde cada regra está no código

| Regra | Onde |
| --- | --- |
| 1 — nasce `Recebida` | `Amostra::criar()` |
| 2 — análise exige responsável | `Amostra::garantirResponsavelTecnicoPreenchido()` |
| 3 — conclusão exige data válida | `StatusAmostra::transicoesPermitidas()` + `Amostra::registrarConclusao()` |
| 4 — rejeição só de `Recebida` ou `EmAnalise` | `StatusAmostra::transicoesPermitidas()` |
| 5 — estados finais | `StatusAmostra::ehFinal()` + `Amostra::garantirQueNaoEstaFinalizada()` |

Todas em `backend/src/Domain/`, cobertas por `tests/Unit/Domain/AmostraTest.php`.

---

## Decisões técnicas

### Clean Architecture: a seta aponta para dentro

```
Infrastructure  →  Application  →  Domain
```

- **Domain** — entidade `Amostra`, enums, value object `CodigoAmostra`, exceptions e a
  interface do repositório. Não conhece banco nem HTTP.
- **Application** — casos de uso (`CadastrarAmostra`, `ListarAmostras`, `ConsultarAmostra`,
  `TransicionarStatusAmostra`) e DTOs. Orquestram o domínio; não conhecem Slim nem SQL.
- **Infrastructure** — controller Slim, repositório MySQL, middlewares e configuração.
  Conhece as outras duas.

O Domain nunca importa nada das camadas de fora. É isso que permite testar as regras sem
subir banco nem servidor, e é o que torna possível trocar o MySQL por outra coisa sem tocar
nas regras.

### O mapa de transições mora no enum `StatusAmostra`

`StatusAmostra::transicoesPermitidas()` devolve, para cada status, a lista de destinos
válidos; `Amostra::transicionarPara()` consulta esse mapa. "Quem pode virar quem" é uma
pergunta sobre o *status*, não sobre a amostra — deixando no enum, a regra fica num lugar só.

A alternativa seria uma cadeia de `if` no controller: espalharia a regra por cada rota e
exigiria banco e servidor no ar para testá-la.

### Construtor privado: `criar()` e `restaurar()`

Não existe `new Amostra(...)`. Ou é `Amostra::criar()` — que nasce `Recebida`, aplicando a
regra 1 — ou `Amostra::restaurar()`, usado pelo repositório para trazer do banco um estado
que já aconteceu. Por isso o `restaurar()` não valida transição: ele não está criando um
fato novo. O efeito é que não existe amostra em estado inválido dentro do sistema.

### `CodigoAmostra` como Value Object

O código não é uma `string` solta: é uma classe imutável que só existe se estiver no formato
correto. O formato é regra de negócio, então mora no Domain. Ganho prático: é impossível
passar adiante um código malformado por engano.

### Hierarquia de exceptions + um middleware de erro

Todas as exceptions de regra herdam de `RegraDeNegocioException`. O middleware
`TratadorDeErros` captura **um** tipo só e devolve `422` com a mensagem — cobre as quatro
regras de transição de uma vez, e regra nova não exige mexer na camada HTTP.

### O prefixo do código é variável de ambiente

`CODIGO_PREFIXO` no `.env`, não fixo no código. Prefixo é dado de configuração, não regra de
negócio: `CodigoAmostra::gerar()` recebe o prefixo como parâmetro e não decide nada sobre ele.

### Unicidade do código: índice `UNIQUE` + retry limitado

A coluna `codigo` tem índice `UNIQUE` no banco, e o caso de uso `CadastrarAmostra` tenta
gravar até **3 vezes**: se o MySQL recusar por chave duplicada, ele pega o próximo número e
tenta de novo.

Quem decide a unicidade é o banco, não a aplicação — assim não existe janela de corrida.
Um `SELECT MAX() + 1` sozinho tem condição de corrida real: dois pedidos simultâneos leem o
mesmo número e ambos tentam gravar o mesmo código.

O retry é invisível ao usuário de propósito: o número é gerado pelo sistema, ninguém pediu
"a amostra 6" — colisão é problema interno, não erro de quem chamou. E é limitado a 3
porque retry infinito transforma bug em travamento: três colisões seguidas não são
concorrência normal, e aí o certo é falhar com `500`.

### MySQL no Docker; PHP e Node fora

Só o banco está containerizado, por três motivos: **reprodutibilidade** (o
`docker-compose.yml` é a receita exata — versão, usuário, senha, base; a alternativa seria
um README dizendo "instale o MySQL 8 e crie o usuário X"), **isolamento** (a máquina de
desenvolvimento roda outros serviços) e **descarte limpo** (`docker compose down -v` e não
sobra nada).

PHP e Node ficaram fora por decisão de escopo dentro do prazo: já estavam instalados e
funcionando. Containerizar os três daria um ambiente mais uniforme, mas custaria tempo que
foi investido nas regras de negócio e nos testes. O banco foi o caso em que o container
resolvia um problema real.

Os dados ficam em um volume nomeado (`mysql_data`), não em uma pasta do projeto: evita
problema de permissão de arquivo e não suja o repositório.

### `schema.sql` na mão, sem ferramenta de migration

Uma tabela só. Phinx (ou equivalente) custaria instalação, configuração e mais uma peça para
manter, sem benefício neste tamanho. O arquivo é auto-explicativo: dá para abrir e entender
a estrutura em dez segundos.

### `tipo` e `status` como `VARCHAR`, não `ENUM` do MySQL

A lista de valores válidos já existe nos enums do Domain. Repetir no banco criaria duas
fontes de verdade que podem divergir. Nesta arquitetura, o banco guarda — não legisla.

### Filtros tipados na interface do repositório

`listar(?StatusAmostra, ?TipoAmostra)`, não `listar(?string, ?string)`. A conversão de texto
para enum acontece uma vez, na borda HTTP: texto inválido vira `400` com mensagem clara, em
vez de virar lista vazia silenciosa.

### CORS resolvido no backend, não com proxy do Next

O middleware `Cors` fica no Slim, com a lista de origens em `CORS_ORIGENS_PERMITIDAS`.
A alternativa (`rewrites` no `next.config.ts`) foi descartada: o enunciado pede uma API
consumida por um frontend, e uma API que só funciona atrás do proxy de um framework
específico está acoplada a ele. O rewrite não resolveria o problema, apenas o esconderia.

Três detalhes da implementação:

1. **O middleware responde o preflight sozinho.** Antes de um `POST`/`PATCH` cross-origin, o
   navegador manda um `OPTIONS`. Como não há rota `OPTIONS` declarada, essa requisição cairia
   no tratador de erros como `405`; o middleware intercepta e devolve `204`.
2. **É o middleware mais externo da pilha.** No Slim, o último adicionado é o primeiro a
   executar. Se ficasse por dentro do `TratadorDeErros`, as respostas de erro sairiam sem os
   cabeçalhos de CORS e o navegador esconderia justamente a mensagem de `422` que a tela
   precisa exibir.
3. **Ecoa a origem recebida, não `*`.** O curinga liberaria qualquer site e é incompatível
   com envio de credenciais. Junto vai `Vary: Origin`, para que um cache intermediário não
   sirva ao site B a resposta liberada para o site A.

### Frontend: TypeScript e React Query

**TypeScript** porque o backend é todo tipado e o formato da `Amostra` é declarado uma vez em
`lib/tipos.ts` — um nome de campo errado vira erro no editor, não tela em branco.

**React Query** cuida de cache, dos estados de carregando/erro e da recarga após mutação:
depois de um `PATCH` de status, a listagem se atualiza sozinha por invalidação de cache.
O custo assumido é uma dependência a mais.

O cliente HTTP (`lib/api.ts`) converte resposta de erro em exceção. Isso é necessário porque
`fetch` **não** lança erro em `404` ou `422` — para ele, "o servidor respondeu" já é sucesso.
Sem checar `resposta.ok`, a tela trataria `{"erro": "..."}` como se fosse uma amostra.

**O mapa de transições não foi reimplementado no frontend:** a tela oferece os destinos e a
API decide. A única duplicação consciente é esconder o botão de ação em status final — em
troca, não se oferece uma ação que sempre falharia. O servidor continua barrando com `422`
mesmo que alguém force a chamada.

### Nomes: teste em inglês, domínio em português

Métodos de teste seguem `testShouldFazerAlgo` — convenção dominante no PHPUnit, e o `should`
força o nome a descrever comportamento esperado. O efeito colateral é que `--testdox` vira
uma lista de requisitos legível.

O código de domínio fica em português (`Amostra`, `StatusAmostra::Recebida`,
`transicionarPara()`) porque são os termos do enunciado, e os valores de status vão para o
banco e para o JSON exatamente como a especificação escreve. Traduzir criaria um vocabulário
paralelo e um ponto de tradução a cada camada.

### Acentuação: mensagens sim, identificadores não

| O quê | Acento |
| --- | --- |
| Mensagens de erro e rótulos da interface | **sim** — texto lido por gente |
| Valores de `tipo` e `status` no banco e na API | **não** — são identificadores; vão para query string e linhas do banco |
| Nome de variável, chave JSON, classe CSS | **não** |

O acento nos valores é resolvido na exibição: `ROTULOS_DE_TIPO` e `ROTULOS_DE_STATUS` em
`lib/tipos.ts` mostram "Água" e "Em análise", com o `value` do `<option>` continuando `Agua`
e `EmAnalise`. Como são tipados por `Record<TipoAmostra, string>`, um valor novo sem rótulo
vira erro de compilação.

### Sem comentários no código

O código não tem comentários. Onde caberia um `// Regra 2: ...`, existe um método chamado
`garantirResponsavelTecnicoPreenchido()`, que diz o mesmo e não pode divergir do que o código
faz. O "porquê" das decisões está aqui no README e nas mensagens de commit.

---

## Limitações conhecidas

- **`APP_DEBUG=true` é configuração de desenvolvimento.** Com ele, um erro `500` devolve a
  mensagem real da exception. Em produção seria `false`, e a resposta viraria apenas
  "Erro interno no servidor.".
- **Sem autenticação.** O enunciado não pede, e adicionar tokens sem necessidade real
  aumentaria a superfície do projeto sem acrescentar nada ao que está sendo avaliado.
- **Sem paginação na listagem.** Para o volume do exercício, os filtros de status e tipo
  bastam; com dados de verdade, `GET /amostras` precisaria de `limit`/`offset`.
- **PHP e Node não estão containerizados** — ver a decisão sobre Docker acima.
- **O campo de data segue o idioma do navegador.** O `<input type="date">` exibe `mm/dd/aaaa`
  em navegador configurado em inglês; o valor enviado é sempre ISO. Não é controlável pelo
  site.
