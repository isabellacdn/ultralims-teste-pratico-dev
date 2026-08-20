# Como testar a aplicação

Guia prático para verificar o módulo de Gestão de Amostras do começo ao fim.

## O mapa: três programas separados

```
[Bruno]  ─┐
          ├─→  API PHP (:8081)  ─→  MySQL (:3307, Docker)
[Front]  ─┘         ↑
  (:3001)      onde moram as regras de negócio
```

Bruno e frontend são **dois clientes do mesmo servidor**. Nenhum dos dois guarda dado:
os dois pedem tudo para a API, que grava no mesmo banco.

Consequência prática: **o que é cadastrado pelo Bruno aparece no frontend** (e vice-versa).
A tela não descobre sozinha que surgiu dado novo — é preciso recarregar (F5) ou trocar um
filtro, porque a listagem fica em cache no cliente.

Cada peça sobe sozinha e pode estar no ar sem as outras. Frontend ligado com a API
desligada mostra o aviso "Não foi possível falar com a API" — não é defeito da tela, é a
API fora do ar.

## Preparação (uma única vez)

Os testes de integração usam um banco separado, `ultralims_teste`. Com o container do MySQL
no ar, criar o banco e aplicar o schema nele:

```bash
docker exec ultralims-mysql mysql -uroot -proot \
  -e "CREATE DATABASE IF NOT EXISTS ultralims_teste CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; \
      GRANT ALL PRIVILEGES ON ultralims_teste.* TO 'ultralims'@'%'; FLUSH PRIVILEGES;"

docker exec -i ultralims-mysql mysql -uultralims -pultralims ultralims_teste \
  < backend/database/schema.sql
```

## Parte A — Ligar tudo

Use uma aba de terminal para cada serviço. As abas dos serviços ficam ocupadas: é o
comportamento esperado, não feche.

### A1. Banco

```bash
cd ~/codes/desafio-dev && docker compose up -d
```

Esperado: linhas terminando em `Running` ou `Started`. Se o container já existir, a saída
avisa que ele está de pé — está correto.

### A2. API

```bash
cd ~/codes/desafio-dev/backend && php -S localhost:8081 -t public
```

Esperado: `Development Server (http://localhost:8081) started`. O prompt não volta.

Se aparecer `Failed to listen on localhost:8081 (reason: Address already in use)`, a API
já está rodando de uma sessão anterior. Confirme com o passo A4 e siga em frente; para
subir na sua própria aba, encerre a anterior antes:

```bash
pkill -f "php -S localhost:8081"
```

### A3. Frontend

```bash
cd ~/codes/desafio-dev/frontend && npm run dev
```

Esperado: `Local: http://localhost:3001`.

### A4. Confirmar que a API responde

Em uma quarta aba, que fica livre para os comandos avulsos:

```bash
curl -i http://localhost:8081/health
```

Esperado: `HTTP/1.1 200 OK` e `{"status":"ok"}`.
`Connection refused` significa que a API não está no ar — volte ao passo A2.

## Parte B — Testes automatizados

```bash
cd ~/codes/desafio-dev/backend && ./vendor/bin/phpunit --testdox
```

O `--testdox` imprime cada teste como uma frase legível, o que transforma a saída em uma
lista de requisitos verificados. No fim vem o resumo: `OK (N tests, N assertions)`.

- `OK` — tudo passou.
- `FAILURES!` — o relatório aponta o teste, o valor esperado e o obtido.

A suíte `Unit` cobre as regras de negócio e roda sem banco nem servidor. A suíte
`Integration` precisa do MySQL no ar e limpa a tabela `amostras` antes de cada teste, para
partir sempre de um estado conhecido — por isso ela aponta para o banco `ultralims_teste`,
definido em `phpunit.xml`, e nunca toca nos dados de desenvolvimento.

Para rodar apenas uma das suítes:

```bash
./vendor/bin/phpunit --testsuite Unit --testdox
./vendor/bin/phpunit --testsuite Integration --testdox
```

Rodar depois de qualquer alteração no backend.

## Parte C — Testar a API no Bruno

O Bruno envia requisições HTTP e mostra a resposta crua, sem o frontend no meio. Serve
para separar responsabilidade: se falha no Bruno, o problema é do backend; se funciona no
Bruno e falha na tela, o problema é do frontend.

### C1. Abrir a coleção

**Collection → Open Collection →** `~/codes/desafio-dev/docs/bruno`.
Aparecem as 13 requisições numeradas.

### C2. Selecionar o environment

No canto superior direito, trocar `No Environment` por **`local`**.

Esse passo define `base_url = http://localhost:8081`. Sem ele, todas as requisições falham.

### C3. Enviar e ler a resposta

Clicar na requisição e apertar `Ctrl + Enter`. No painel da direita, olhar sempre duas
coisas: o **código de status** (em cima) e o **corpo da resposta** (embaixo).

| Faixa | Significado |
| --- | --- |
| `2xx` | deu certo |
| `400` | payload malformado |
| `404` | recurso não existe |
| `422` | regra de negócio violada |
| `5xx` | a API quebrou — verificar o terminal do PHP |

### C4. Roteiro do caminho feliz

A lista lateral do Bruno exibe o nome definido dentro de cada arquivo, não o nome do
arquivo no disco. Os nomes abaixo são os que aparecem na tela.

| # | Requisição na lista do Bruno | Esperado | O que comprova |
| --- | --- | --- | --- |
| 01 | Health | `200` | API no ar |
| 02 | Cadastrar amostra | `201`, `"status":"Recebida"` | regra 1, e o código é gerado pelo backend |
| 04 | Listar amostras | `200` + array | a amostra criada está na lista |
| 07 | Transicionar para EmAnalise | `200`, `"status":"EmAnalise"` | regra 2 (o corpo envia `responsavel_tecnico`) |
| 08 | Transicionar para Concluida | `200`, `"status":"Concluida"` | regra 3 (data de conclusão válida) |
| 09 | Transicionar para Rejeitada | `200`, `"status":"Rejeitada"` | regra 4 |

A requisição "Cadastrar amostra" guarda o id da amostra recém-criada na variável
`{{amostra_id}}`, usada na URL das requisições 06 a 11. Por isso a ordem importa: **rodar
"Cadastrar amostra" antes delas**.

### C5. Roteiro dos erros

Aqui, resposta de erro é sinal de sucesso: significa que a regra bloqueou o que devia.

| # | Requisição na lista do Bruno | Esperado |
| --- | --- | --- |
| 10 | Erro 422 - analise sem responsavel | `422` |
| 11 | Erro 422 - conclusao anterior ao recebimento | `422` |
| 12 | Erro 400 - tipo invalido | `400` |
| 13 | Erro 404 - amostra inexistente | `404` |

`Concluida` e `Rejeitada` são estados finais (regra 5). Depois de "Transicionar para
Concluida", qualquer nova transição sobre a mesma amostra responde `422` — comportamento
correto. Para testar cada erro isoladamente, rodar "Cadastrar amostra" antes, obtendo uma
amostra nova em `Recebida`.

## Parte D — Testar a interface

Abrir `http://localhost:3001` **em um navegador real**, não no navegador embutido do
editor: é o ambiente do usuário final, e é onde as ferramentas de desenvolvedor funcionam
por completo.

Manter `F12 → aba Network` aberta durante os testes. Cada ação da tela vira uma linha ali,
com URL, status e corpo — é a forma de ver o que a tela realmente enviou, em vez de deduzir
pelo que ela mostra.

| # | Ação | Esperado |
| --- | --- | --- |
| D1 | Abrir a listagem | tabela com as amostras, rótulos acentuados ("Água", "Em análise") |
| D2 | F5 com o Network aberto | requisição `amostras` com resposta igual à do Bruno |
| D3 | Aplicar um filtro | tabela muda; a URL da requisição ganha `?status=` ou `?tipo=` |
| D4 | Cadastrar uma amostra | volta para a listagem já com a nova linha |
| D5 | Transicionar para "Em análise" sem responsável técnico | mensagem de erro na tela (o `422` da API exibido ao usuário) |
| D6 | Derrubar a API (`Ctrl + C` na aba A2) e recarregar | aviso "Não foi possível falar com a API", sem tela branca |

## Armadilhas conhecidas

**Campo de data em `mm/dd/aaaa`.** O `<input type="date">` exibe a data no formato do
idioma do navegador, e isso não é controlável pelo site. Com o navegador em inglês, o
primeiro campo é o mês — digitar o dia ali resulta em um valor cortado para 12. O valor
enviado é sempre ISO (`2026-08-19`). Testar com o navegador em português reproduz o que o
usuário brasileiro vê.

**Requisição falhando em tudo no Bruno.** Environment `local` não selecionado.

**`{{amostra_id}}` apontando para a amostra errada.** Rodar "Cadastrar amostra" novamente.

**Porta ocupada ao subir a API.** Uma instância anterior continua no ar; ver o passo A2.

**As amostras sumiram depois de rodar os testes.** A suíte `Integration` limpa a tabela
antes de cada teste. Ela só deve fazer isso em `ultralims_teste`: conferir se o bloco
`<php><env name="DB_DATABASE" .../></php>` continua no `phpunit.xml`.

**Tela não mostra o que foi cadastrado pelo Bruno.** A listagem está em cache no cliente:
recarregar a página ou trocar um filtro.

## Camadas de teste

| Camada | Ferramenta | Cobre | Automatizado |
| --- | --- | --- | --- |
| Regras de negócio | PHPUnit | as 5 regras, sem banco nem servidor | sim |
| Contrato HTTP | Bruno | status, formato do JSON, fluxo entre requisições | não |
| Uso real | navegador | tela, mensagens, navegação | não |

As camadas são complementares: o teste unitário não enxerga rota errada, CORS ou nome de
campo trocado no JSON; o teste manual não cobre com precisão cada ramo de regra de negócio.

## Onde cada regra é verificada

| Regra | PHPUnit | Bruno | Tela |
| --- | --- | --- | --- |
| 1 — nasce `Recebida` | `AmostraTest` | 02 | D4 |
| 2 — análise exige responsável técnico | `AmostraTest` | 07 e 10 | D5 |
| 3 — conclusão exige data válida | `AmostraTest` | 08 e 11 | — |
| 4 — rejeição só de `Recebida` ou `EmAnalise` | `AmostraTest` | 09 | — |
| 5 — `Concluida` e `Rejeitada` são finais | `AmostraTest` | 08 seguido de 09 | — |
