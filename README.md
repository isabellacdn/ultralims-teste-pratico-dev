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
│   ├── Dockerfile        # imagem da API (PHP 8.2 + pdo_mysql + Composer)
│   ├── database/
│   │   ├── schema.sql          # estrutura da tabela amostras
│   │   └── banco-de-teste.sql  # cria o banco usado pelos testes de integração
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
│   ├── Dockerfile           # imagem do frontend (Node 20)
│   ├── app/                 # páginas e componentes
│   └── lib/                 # tipos da amostra e cliente HTTP da API
├── docs/
│   ├── COMO-TESTAR.md       # roteiro de verificação manual ponta a ponta
│   └── bruno/               # coleção Bruno com as 13 requisições da API
└── docker-compose.yml    # MySQL 8, API e frontend
```

A separação em `Domain / Application / Infrastructure` é descrita em
[Decisões técnicas](#decisões-técnicas).

---

## Pré-requisitos

**Docker com Docker Compose** — e mais nada.

PHP, Composer, Node e MySQL rodam dentro dos containers; nenhum deles precisa estar
instalado na máquina. Para conferir:

```bash
docker compose version
```

Quem preferir rodar sem Docker precisa de PHP 8.2+ (com a extensão **`pdo_mysql`**),
Composer 2.x e Node 20+ — ver [Rodar sem Docker](#rodar-sem-docker).

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
| MySQL | `ports` do serviço `mysql` no `docker-compose.yml` (lado esquerdo de `"3307:3306"`) |
| API | `ports` do serviço `api` **e** `NEXT_PUBLIC_API_URL` no `docker-compose.yml` |
| Frontend | `ports` do serviço `frontend` **e** `CORS_ORIGENS_PERMITIDAS` no `docker-compose.yml` |

Rodando sem Docker, os mesmos valores saem de `backend/.env`, `frontend/.env.local` e dos
scripts `start` (em `backend/composer.json`) e `dev` (em `frontend/package.json`).

> Se trocar a porta do frontend, é obrigatório atualizar `CORS_ORIGENS_PERMITIDAS`, senão
> o navegador bloqueia as chamadas à API.

---

## Como rodar

Banco, API e frontend sobem juntos, com um comando só, a partir da raiz do repositório:

```bash
docker compose up
```

Na primeira execução o Docker constrói as imagens da API e do frontend — leva alguns
minutos. Nas seguintes é quase imediato. Quando terminar:

| Serviço | Endereço |
| --- | --- |
| Interface | <http://localhost:3001> |
| API | <http://localhost:8081> |
| MySQL | `localhost:3307` |

Para confirmar que a API respondeu:

```bash
curl -i http://localhost:8081/health
```

Esperado: `HTTP/1.1 200 OK` e `{"status":"ok"}`.

| Comando | O que faz |
| --- | --- |
| `docker compose up -d` | sobe em segundo plano e devolve o terminal |
| `docker compose ps` | mostra o estado dos três containers |
| `docker compose logs -f api` | acompanha o log de um serviço |
| `docker compose down` | para tudo, **preservando** os dados do banco |
| `docker compose down -v` | para tudo e **apaga** os dados do banco |

### Não é preciso criar nenhum `.env`

A configuração dos containers vem do bloco `environment:` do `docker-compose.yml`, não de
arquivo. `Ambiente::carregar()` usa o `safeLoad()` do phpdotenv, que não reclama quando o
`.env` não existe — dentro do container as variáveis chegam direto do compose.

Os `.env.example` continuam versionados porque são necessários para rodar sem Docker.

### A tabela é criada sozinha

Os `.sql` montados em `/docker-entrypoint-initdb.d/` rodam quando o volume do MySQL é criado:
`schema.sql` cria a tabela `amostras`, e `banco-de-teste.sql` cria o banco `ultralims_teste`
que os testes de integração usam.

⚠️ Eles rodam **apenas em volume novo**. Se já existir um volume de uma execução anterior, o
MySQL ignora os scripts. Para forçar a recriação do zero:

```bash
docker compose down -v && docker compose up
```

### As variáveis de configuração

Ficam em `environment:` no `docker-compose.yml` (modo Docker) e nos `.env` (modo local):

| Variável | Padrão | Para que serve |
| --- | --- | --- |
| `DB_HOST` / `DB_PORT` | `mysql` / `3306` no Docker; `127.0.0.1` / `3307` fora | onde o MySQL está |
| `DB_DATABASE` | `ultralims` | banco de desenvolvimento |
| `DB_USERNAME` / `DB_PASSWORD` | `ultralims` / `ultralims` | credenciais criadas pelo `docker-compose.yml` |
| `CODIGO_PREFIXO` | `ISABELLA` | prefixo do código da amostra: `{PREFIXO}-{ANO}-{SEQUENCIAL}` |
| `APP_DEBUG` | `true` | em erro 500, mostra a mensagem real. **Configuração de desenvolvimento** — em produção seria `false`, e a resposta viraria apenas "Erro interno no servidor." |
| `CORS_ORIGENS_PERMITIDAS` | `http://localhost:3001` | origens autorizadas a chamar a API pelo navegador, separadas por vírgula |
| `NEXT_PUBLIC_API_URL` | `http://localhost:8081` | endereço da API usado pelo frontend |

### Rodar sem Docker

Alternativa para quem já tem PHP 8.2+ com `pdo_mysql`, Composer e Node 20 na máquina. São
três processos, um por aba de terminal.

```bash
docker compose up -d mysql          # só o banco

cd backend                          # aba 2 — API em http://localhost:8081
cp .env.example .env
composer install
composer start

cd frontend                         # aba 3 — interface em http://localhost:3001
cp .env.example .env.local
npm install
npm run dev
```

Aqui o `.env` é obrigatório: fora do compose ninguém define as variáveis de ambiente. E é
por isso que ele aponta para `127.0.0.1:3307` — de fora da rede do Docker, o MySQL só é
alcançável pela porta publicada.

---

## Como rodar os testes

Com os containers no ar, a suíte roda dentro do container da API:

```bash
docker compose exec api ./vendor/bin/phpunit --testdox
```

Sem Docker, a partir de `backend/`: `./vendor/bin/phpunit --testdox`.

O banco de teste (`ultralims_teste`) é criado pelo `banco-de-teste.sql` junto com o volume
do MySQL — não há passo manual.

O `--testdox` imprime cada teste como uma frase legível, o que transforma a saída em uma
lista de requisitos verificados — serve como evidência de cobertura das regras.

Saída atual: **`OK (79 tests, 109 assertions)`**.

| Suíte | Testes | Precisa de banco? |
| --- | --- | --- |
| `Unit` | 72 | **não** |
| `Integration` | 7 | sim |

```bash
docker compose exec api ./vendor/bin/phpunit --testsuite Unit --testdox
docker compose exec api ./vendor/bin/phpunit --testsuite Integration --testdox
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

### Ambiente inteiro em containers

Os três serviços — MySQL, API e frontend — estão no `docker-compose.yml`, e `docker compose
up` sobe o projeto do zero. O ganho é **reprodutibilidade**: o compose é a receita exata
(versões, usuário, senha, base, portas), em vez de um README pedindo "instale PHP 8.2 com
`pdo_mysql`, Composer, Node 20 e MySQL 8". A extensão `pdo_mysql` faltando era o tropeço
mais provável de quem fosse rodar o projeto, e ele deixou de existir.

Junto vêm **isolamento** (a máquina de desenvolvimento roda outros serviços nas portas
usuais) e **descarte limpo** (`docker compose down -v` e não sobra nada instalado).

Os dados do banco ficam em um volume nomeado (`mysql_data`), não em uma pasta do projeto:
evita problema de permissão de arquivo e não suja o repositório.

Rodar sem Docker continua funcionando, com os `.env` — os dois caminhos convivem.

#### Três detalhes que a containerização exigiu

**1. `NEXT_PUBLIC_API_URL` continua `http://localhost:8081`, não `http://api:8081`.**
Dentro da rede do compose, um container alcança o outro pelo nome do serviço — e é assim
que a API fala com o MySQL (`DB_HOST=mysql`, porta interna `3306`). Mas a chamada à API não
parte do container do frontend: parte do **navegador**, que roda na máquina de quem acessa e
não enxerga a rede do Docker. Trocar para `api:8081` quebraria a tela com erro de rede.
O nome do serviço vale para tráfego entre containers; a porta publicada vale para o
navegador.

**2. O servidor embutido do PHP passou a escutar em `0.0.0.0`.** O `composer start` usa
`php -S localhost:8081`, que só aceita conexão originada de dentro do próprio container —
a porta publicada não responderia. Por isso o `CMD` da imagem usa `0.0.0.0:8081`, enquanto o
script `start` do Composer segue em `localhost` para o uso fora do Docker.

**3. O `phpunit.xml` ganhou `force="true"` no `DB_DATABASE`.** O `<env>` do PHPUnit não
sobrescreve variável que já exista no ambiente. Fora do Docker isso nunca apareceu, porque o
`.env` é lido só pelo `public/index.php`, e não pelo bootstrap do PHPUnit — a variável estava
vazia e o PHPUnit definia `ultralims_teste`. Dentro do container ela existe de verdade,
valendo `ultralims`: sem o `force`, os testes de integração rodariam contra o banco de
desenvolvimento, e o `DELETE FROM amostras` do `setUp` apagaria os dados reais.

### `schema.sql` na mão, aplicado pelo próprio MySQL

Os `.sql` são montados em `/docker-entrypoint-initdb.d/`, diretório que a imagem oficial do
MySQL executa quando inicializa um volume novo. Isso elimina dois passos manuais do README
anterior (criar a tabela e criar o banco de teste) sem introduzir ferramenta nova.

O `banco-de-teste.sql` termina com `SOURCE /docker-entrypoint-initdb.d/01-schema.sql`, em vez
de repetir o `CREATE TABLE`: a estrutura da tabela continua declarada em um lugar só, e o
banco de teste não pode divergir do de desenvolvimento.

O preço é que os scripts rodam **apenas em volume novo** — em volume existente o MySQL os
ignora. Está avisado em [Como rodar](#como-rodar).

Não há ferramenta de migration porque é uma tabela só: Phinx (ou equivalente) custaria
instalação, configuração e mais uma peça para manter, sem benefício neste tamanho. O SQL é
auto-explicativo — dá para abrir e entender a estrutura em dez segundos.

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
- **Os containers são de desenvolvimento, não de produção.** A API roda no servidor embutido
  do PHP (`php -S`, single-thread) e o frontend em `next dev`. Em produção seriam
  PHP-FPM atrás de Nginx e `next build` + `next start`, em imagens multi-stage sem as
  dependências de desenvolvimento.
- **O campo de data segue o idioma do navegador.** O `<input type="date">` exibe `mm/dd/aaaa`
  em navegador configurado em inglês; o valor enviado é sempre ISO. Não é controlável pelo
  site.
