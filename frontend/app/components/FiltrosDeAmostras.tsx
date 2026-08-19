"use client";

import {
  STATUS_DE_AMOSTRA,
  TIPOS_DE_AMOSTRA,
  type FiltrosDeAmostra,
} from "@/lib/tipos";

type Props = {
  filtros: FiltrosDeAmostra;
  aoMudar: (filtros: FiltrosDeAmostra) => void;
};

export function FiltrosDeAmostras({ filtros, aoMudar }: Props) {
  const nenhumFiltroAtivo = !filtros.status && !filtros.tipo;

  return (
    <section className="filtros">
      <label>
        Status
        <select
          value={filtros.status ?? ""}
          onChange={(evento) =>
            aoMudar({
              ...filtros,
              status: evento.target.value as FiltrosDeAmostra["status"],
            })
          }
        >
          <option value="">Todos</option>
          {STATUS_DE_AMOSTRA.map((status) => (
            <option key={status} value={status}>
              {status}
            </option>
          ))}
        </select>
      </label>

      <label>
        Tipo
        <select
          value={filtros.tipo ?? ""}
          onChange={(evento) =>
            aoMudar({
              ...filtros,
              tipo: evento.target.value as FiltrosDeAmostra["tipo"],
            })
          }
        >
          <option value="">Todos</option>
          {TIPOS_DE_AMOSTRA.map((tipo) => (
            <option key={tipo} value={tipo}>
              {tipo}
            </option>
          ))}
        </select>
      </label>

      <button
        type="button"
        onClick={() => aoMudar({ status: "", tipo: "" })}
        disabled={nenhumFiltroAtivo}
      >
        Limpar filtros
      </button>
    </section>
  );
}
