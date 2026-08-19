"use client";

import { Fragment, useState } from "react";
import { AcaoDeStatus } from "./AcaoDeStatus";
import {
  ROTULOS_DE_STATUS,
  ROTULOS_DE_TIPO,
  type Amostra,
} from "@/lib/tipos";

type Props = {
  amostras: Amostra[];
};

const STATUS_FINAIS = ["Concluida", "Rejeitada"];

export function TabelaDeAmostras({ amostras }: Props) {
  const [amostraEmEdicao, setAmostraEmEdicao] = useState<number | null>(null);

  return (
    <div className="tabela-rolavel">
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
                <td className="codigo">{amostra.codigo}</td>
                <td>{ROTULOS_DE_TIPO[amostra.tipo]}</td>
                <td>
                  <span
                    className={`status status-${amostra.status.toLowerCase()}`}
                  >
                    {ROTULOS_DE_STATUS[amostra.status]}
                  </span>
                </td>
                <td>{amostra.responsavel_tecnico ?? "—"}</td>
                <td className="data">{formatarData(amostra.data_recebimento)}</td>
                <td className="data">{formatarData(amostra.data_conclusao)}</td>
                <td>
                  {STATUS_FINAIS.includes(amostra.status) ? (
                    <span className="sem-acao">Status final</span>
                  ) : (
                    <button
                      type="button"
                      className="compacto"
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
    </div>
  );
}

function formatarData(data: string | null): string {
  if (data === null) {
    return "—";
  }

  const [ano, mes, dia] = data.split("-");

  return `${dia}/${mes}/${ano}`;
}
