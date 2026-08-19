"use client";

import { Fragment, useState } from "react";
import { AcaoDeStatus } from "./AcaoDeStatus";
import type { Amostra } from "@/lib/tipos";

type Props = {
  amostras: Amostra[];
};

const STATUS_FINAIS = ["Concluida", "Rejeitada"];

export function TabelaDeAmostras({ amostras }: Props) {
  const [amostraEmEdicao, setAmostraEmEdicao] = useState<number | null>(null);

  return (
    <table>
      <thead>
        <tr>
          <th>Código</th>
          <th>Tipo</th>
          <th>Status</th>
          <th>Responsável técnico</th>
          <th>Recebimento</th>
          <th>Conclusão</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        {amostras.map((amostra) => (
          <Fragment key={amostra.id}>
            <tr>
              <td>{amostra.codigo}</td>
              <td>{amostra.tipo}</td>
              <td>
                <span
                  className={`status status-${amostra.status.toLowerCase()}`}
                >
                  {amostra.status}
                </span>
              </td>
              <td>{amostra.responsavel_tecnico ?? "—"}</td>
              <td>{formatarData(amostra.data_recebimento)}</td>
              <td>{formatarData(amostra.data_conclusao)}</td>
              <td>
                {STATUS_FINAIS.includes(amostra.status) ? (
                  <span className="sem-acao">Status final</span>
                ) : (
                  <button
                    type="button"
                    onClick={() =>
                      setAmostraEmEdicao(
                        amostraEmEdicao === amostra.id ? null : amostra.id,
                      )
                    }
                  >
                    Mudar status
                  </button>
                )}
              </td>
            </tr>

            {amostraEmEdicao === amostra.id && (
              <tr>
                <td colSpan={7}>
                  <AcaoDeStatus
                    amostra={amostra}
                    aoFechar={() => setAmostraEmEdicao(null)}
                  />
                </td>
              </tr>
            )}
          </Fragment>
        ))}
      </tbody>
    </table>
  );
}

function formatarData(data: string | null): string {
  if (data === null) {
    return "—";
  }

  const [ano, mes, dia] = data.split("-");

  return `${dia}/${mes}/${ano}`;
}
