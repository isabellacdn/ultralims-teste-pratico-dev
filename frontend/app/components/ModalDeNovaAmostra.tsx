"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useEffect, useRef, useState } from "react";
import type { FormEvent } from "react";
import { cadastrarAmostra } from "@/lib/api";
import {
  ROTULOS_DE_TIPO,
  TIPOS_DE_AMOSTRA,
  type TipoAmostra,
} from "@/lib/tipos";

type Props = {
  aberto: boolean;
  aoFechar: () => void;
};

function dataDeHoje() {
  const agora = new Date();
  const mes = String(agora.getMonth() + 1).padStart(2, "0");
  const dia = String(agora.getDate()).padStart(2, "0");

  return `${agora.getFullYear()}-${mes}-${dia}`;
}

export function ModalDeNovaAmostra({ aberto, aoFechar }: Props) {
  const clienteDeConsultas = useQueryClient();
  const dialogo = useRef<HTMLDialogElement>(null);

  const [tipo, setTipo] = useState<TipoAmostra | "">("");
  const [dataRecebimento, setDataRecebimento] = useState("");
  const [dataMaxima, setDataMaxima] = useState("");
  const [responsavelTecnico, setResponsavelTecnico] = useState("");

  const cadastro = useMutation({
    mutationFn: cadastrarAmostra,
    onSuccess: () => {
      clienteDeConsultas.invalidateQueries({ queryKey: ["amostras"] });
      aoFechar();
    },
  });

  const reiniciarCadastro = cadastro.reset;

  useEffect(() => {
    const elemento = dialogo.current;

    if (elemento === null) {
      return;
    }

    if (aberto && !elemento.open) {
      setTipo("");
      setDataRecebimento("");
      setResponsavelTecnico("");
      setDataMaxima(dataDeHoje());
      reiniciarCadastro();
      elemento.showModal();
    }

    if (!aberto && elemento.open) {
      elemento.close();
    }
  }, [aberto, reiniciarCadastro]);

  function enviar(evento: FormEvent<HTMLFormElement>) {
    evento.preventDefault();

    if (tipo === "") {
      return;
    }

    cadastro.mutate({
      tipo,
      data_recebimento: dataRecebimento,
      responsavel_tecnico: responsavelTecnico.trim() || undefined,
    });
  }

  return (
    <dialog
      ref={dialogo}
      onClose={aoFechar}
      onClick={(evento) => {
        if (evento.target === dialogo.current) {
          aoFechar();
        }
      }}
    >
      <div className="cabecalho-modal">
        <h2>Nova amostra</h2>
        <button
          type="button"
          className="fechar"
          onClick={aoFechar}
          aria-label="Fechar"
        >
          ×
        </button>
      </div>

      <form onSubmit={enviar} className="formulario">
        <div className="par-campos">
          <label>
            Tipo
            <select
              value={tipo}
              onChange={(evento) => setTipo(evento.target.value as TipoAmostra)}
              required
            >
              <option value="">Selecione</option>
              {TIPOS_DE_AMOSTRA.map((tipoDisponivel) => (
                <option key={tipoDisponivel} value={tipoDisponivel}>
                  {ROTULOS_DE_TIPO[tipoDisponivel]}
                </option>
              ))}
            </select>
          </label>

          <label>
            Data de recebimento
            <input
              type="date"
              value={dataRecebimento}
              max={dataMaxima || undefined}
              onChange={(evento) => setDataRecebimento(evento.target.value)}
              required
            />
          </label>
        </div>

        <label>
          Responsável técnico (opcional)
          <input
            type="text"
            value={responsavelTecnico}
            onChange={(evento) => setResponsavelTecnico(evento.target.value)}
            placeholder="Nome de quem vai analisar"
          />
        </label>

        <span className="obrigatorio">
          Tipo e data de recebimento são obrigatórios.
        </span>

        {cadastro.isError && (
          <p className="aviso erro">{cadastro.error.message}</p>
        )}

        <div className="acoes">
          <button type="submit" className="principal" disabled={cadastro.isPending}>
            {cadastro.isPending ? "Cadastrando..." : "Cadastrar amostra"}
          </button>
          <button type="button" onClick={aoFechar}>
            Cancelar
          </button>
        </div>
      </form>
    </dialog>
  );
}
