"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import type { FormEvent } from "react";
import { cadastrarAmostra } from "@/lib/api";
import {
  ROTULOS_DE_TIPO,
  TIPOS_DE_AMOSTRA,
  type TipoAmostra,
} from "@/lib/tipos";

export default function PaginaDeCadastro() {
  const router = useRouter();
  const clienteDeConsultas = useQueryClient();

  const [tipo, setTipo] = useState<TipoAmostra | "">("");
  const [dataRecebimento, setDataRecebimento] = useState("");
  const [responsavelTecnico, setResponsavelTecnico] = useState("");

  const cadastro = useMutation({
    mutationFn: cadastrarAmostra,
    onSuccess: () => {
      clienteDeConsultas.invalidateQueries({ queryKey: ["amostras"] });
      router.push("/");
    },
  });

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
    <main>
      <h1>Nova amostra</h1>

      <form onSubmit={enviar} className="formulario">
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
            onChange={(evento) => setDataRecebimento(evento.target.value)}
            required
          />
        </label>

        <label>
          Responsável técnico (opcional)
          <input
            type="text"
            value={responsavelTecnico}
            onChange={(evento) => setResponsavelTecnico(evento.target.value)}
            placeholder="Nome de quem vai analisar"
          />
        </label>

        {cadastro.isError && (
          <p className="aviso erro">{cadastro.error.message}</p>
        )}

        <div className="acoes">
          <button type="submit" disabled={cadastro.isPending}>
            {cadastro.isPending ? "Cadastrando..." : "Cadastrar amostra"}
          </button>
          <Link href="/">Cancelar</Link>
        </div>
      </form>
    </main>
  );
}
