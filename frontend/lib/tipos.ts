export const TIPOS_DE_AMOSTRA = ["Agua", "Solo", "Ar", "Efluente"] as const;

export type TipoAmostra = (typeof TIPOS_DE_AMOSTRA)[number];

export const STATUS_DE_AMOSTRA = [
  "Recebida",
  "EmAnalise",
  "Concluida",
  "Rejeitada",
] as const;

export type StatusAmostra = (typeof STATUS_DE_AMOSTRA)[number];

export const ROTULOS_DE_TIPO: Record<TipoAmostra, string> = {
  Agua: "Água",
  Solo: "Solo",
  Ar: "Ar",
  Efluente: "Efluente",
};

export const ROTULOS_DE_STATUS: Record<StatusAmostra, string> = {
  Recebida: "Recebida",
  EmAnalise: "Em análise",
  Concluida: "Concluída",
  Rejeitada: "Rejeitada",
};

export type Amostra = {
  id: number;
  codigo: string;
  tipo: TipoAmostra;
  status: StatusAmostra;
  responsavel_tecnico: string | null;
  data_recebimento: string;
  data_conclusao: string | null;
};

export type FiltrosDeAmostra = {
  status?: StatusAmostra | "";
  tipo?: TipoAmostra | "";
};

export type NovaAmostra = {
  tipo: TipoAmostra;
  data_recebimento: string;
  responsavel_tecnico?: string;
};

export type MudancaDeStatus = {
  status: StatusAmostra;
  data_conclusao?: string;
  responsavel_tecnico?: string;
};
