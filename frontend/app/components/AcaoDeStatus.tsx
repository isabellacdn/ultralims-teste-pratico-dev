"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { transicionarStatus } from "@/lib/api";
import {
  STATUS_DE_AMOSTRA,
  type Amostra,
  type StatusAmostra,
} from "@/lib/tipos";

type Props = {
  amostra: Amostra;
  aoFechar: () => void;
};

export function AcaoDeStatus({ amostra, aoFechar }: Props) {
  const clienteDeConsultas = useQueryClient();

  const [destino, setDestino] = useState<StatusAmostra | "">("");
  const [responsavelTecnico, setResponsavelTecnico] = useState("");
  const [dataConclusao, setDataConclusao] = useState("");

  const transicao = useMutation({
    mutationFn: (novoStatus: StatusAmostra) =>
      transicionarStatus(amostra.id, {
        status: novoStatus,
        responsavel_tecnico: responsavelTecnico.trim() || undefined,
        data_conclusao: dataConclusao || undefined,
      }),
    onSuccess: () => {
      clienteDeConsultas.invalidateQueries({ queryKey: ["amostras"] });
      aoFechar();
    },
  });

  const pedeResponsavelTecnico =
    destino === "EmAnalise" && amostra.responsavel_tecnico === null;
  const pedeDataDeConclusao = destino === "Concluida";

  return (
    <div className="acao-de-status">
      <label>
        Novo status
        <select
          value={destino}
          onChange={(evento) =>
            setDestino(evento.target.value as StatusAmostra)
          }
        >
          <option value="">Selecione</option>
          {STATUS_DE_AMOSTRA.filter((status) => status !== amostra.status).map(
            (status) => (
              <option key={status} value={status}>
                {status}
              </option>
            ),
          )}
        </select>
      </label>

      {pedeResponsavelTecnico && (
        <label>
          Responsável técnico
          <input
            type="text"
            value={responsavelTecnico}
            onChange={(evento) => setResponsavelTecnico(evento.target.value)}
          />
        </label>
      )}

      {pedeDataDeConclusao && (
        <label>
          Data de conclusão
          <input
            type="date"
            value={dataConclusao}
            onChange={(evento) => setDataConclusao(evento.target.value)}
          />
        </label>
      )}

      <div className="acoes">
        <button
          type="button"
          onClick={() => destino !== "" && transicao.mutate(destino)}
          disabled={destino === "" || transicao.isPending}
        >
          {transicao.isPending ? "Aplicando..." : "Aplicar"}
        </button>
        <button type="button" onClick={aoFechar}>
          Cancelar
        </button>
      </div>

      {transicao.isError && (
        <p className="aviso erro">{transicao.error.message}</p>
      )}
    </div>
  );
}
