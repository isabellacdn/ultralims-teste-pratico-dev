"use client";

import {
  ROTULOS_DE_STATUS,
  STATUS_DE_AMOSTRA,
  type Amostra,
} from "@/lib/tipos";

type Props = {
  amostras: Amostra[];
};

export function ResumoDeAmostras({ amostras }: Props) {
  return (
    <div className="resumo">
      <span className="metrica">
        Todos
        <span className="valor">{amostras.length}</span>
      </span>

      {STATUS_DE_AMOSTRA.map((status) => (
        <span key={status} className="metrica">
          <span className={`quadro quadro-${status.toLowerCase()}`} />
          {ROTULOS_DE_STATUS[status]}
          <span className="valor">
            {amostras.filter((amostra) => amostra.status === status).length}
          </span>
        </span>
      ))}
    </div>
  );
}
