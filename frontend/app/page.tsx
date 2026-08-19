"use client";

import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { FiltrosDeAmostras } from "./components/FiltrosDeAmostras";
import { ModalDeNovaAmostra } from "./components/ModalDeNovaAmostra";
import { ResumoDeAmostras } from "./components/ResumoDeAmostras";
import { TabelaDeAmostras } from "./components/TabelaDeAmostras";
import { listarAmostras } from "@/lib/api";
import type { FiltrosDeAmostra } from "@/lib/tipos";

export default function PaginaDeAmostras() {
  const [filtros, setFiltros] = useState<FiltrosDeAmostra>({
    status: "",
    tipo: "",
  });

  const [cadastroAberto, setCadastroAberto] = useState(false);

  const {
    data: amostras,
    isPending,
    isError,
    error,
  } = useQuery({
    queryKey: ["amostras", filtros],
    queryFn: () => listarAmostras(filtros),
  });

  return (
    <main>
      <div className="cabecalho-tela">
        <h1>Amostras</h1>
        <button
          type="button"
          className="principal"
          onClick={() => setCadastroAberto(true)}
        >
          + Nova amostra
        </button>
      </div>

      {amostras && amostras.length > 0 && (
        <ResumoDeAmostras amostras={amostras} />
      )}

      <FiltrosDeAmostras filtros={filtros} aoMudar={setFiltros} />

      {isPending && <p className="aviso">Carregando amostras...</p>}

      {isError && <p className="aviso erro">{error.message}</p>}

      {amostras && amostras.length === 0 && (
        <p className="aviso">Nenhuma amostra encontrada para estes filtros.</p>
      )}

      {amostras && amostras.length > 0 && (
        <TabelaDeAmostras amostras={amostras} />
      )}

      <ModalDeNovaAmostra
        aberto={cadastroAberto}
        aoFechar={() => setCadastroAberto(false)}
      />
    </main>
  );
}
