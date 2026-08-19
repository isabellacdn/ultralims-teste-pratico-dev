import type {
  Amostra,
  FiltrosDeAmostra,
  MudancaDeStatus,
  NovaAmostra,
} from "./tipos";

const URL_DA_API = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8081";

export class ErroDaApi extends Error {
  constructor(
    mensagem: string,
    readonly status: number,
  ) {
    super(mensagem);
    this.name = "ErroDaApi";
  }
}

export function listarAmostras(
  filtros: FiltrosDeAmostra = {},
): Promise<Amostra[]> {
  return requisitar<Amostra[]>(`/amostras${consultaDe(filtros)}`);
}

export function consultarAmostra(id: number): Promise<Amostra> {
  return requisitar<Amostra>(`/amostras/${id}`);
}

export function cadastrarAmostra(dados: NovaAmostra): Promise<Amostra> {
  return requisitar<Amostra>("/amostras", { method: "POST", corpo: dados });
}

export function transicionarStatus(
  id: number,
  dados: MudancaDeStatus,
): Promise<Amostra> {
  return requisitar<Amostra>(`/amostras/${id}/status`, {
    method: "PATCH",
    corpo: dados,
  });
}

type Requisicao = {
  method?: string;
  corpo?: unknown;
};

async function requisitar<T>(
  caminho: string,
  requisicao: Requisicao = {},
): Promise<T> {
  const resposta = await enviar(caminho, requisicao);
  const corpo = await lerCorpo(resposta);

  if (!resposta.ok) {
    throw new ErroDaApi(mensagemDe(corpo, resposta.status), resposta.status);
  }

  return corpo as T;
}

async function enviar(
  caminho: string,
  requisicao: Requisicao,
): Promise<Response> {
  try {
    return await fetch(`${URL_DA_API}${caminho}`, {
      method: requisicao.method ?? "GET",
      headers:
        requisicao.corpo === undefined
          ? undefined
          : { "Content-Type": "application/json" },
      body:
        requisicao.corpo === undefined
          ? undefined
          : JSON.stringify(requisicao.corpo),
    });
  } catch {
    throw new ErroDaApi(
      `Não foi possível falar com a API em ${URL_DA_API}. Verifique se ela está rodando.`,
      0,
    );
  }
}

async function lerCorpo(resposta: Response): Promise<unknown> {
  const texto = await resposta.text();

  if (texto === "") {
    return null;
  }

  try {
    return JSON.parse(texto);
  } catch {
    return null;
  }
}

function mensagemDe(corpo: unknown, status: number): string {
  if (
    typeof corpo === "object" &&
    corpo !== null &&
    "erro" in corpo &&
    typeof corpo.erro === "string"
  ) {
    return corpo.erro;
  }

  return `A API respondeu com o código ${status}.`;
}

function consultaDe(filtros: FiltrosDeAmostra): string {
  const parametros = new URLSearchParams();

  if (filtros.status) {
    parametros.set("status", filtros.status);
  }

  if (filtros.tipo) {
    parametros.set("tipo", filtros.tipo);
  }

  const consulta = parametros.toString();

  return consulta === "" ? "" : `?${consulta}`;
}
