"use client";

import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { FiltrosDeAmostras } from "./components/FiltrosDeAmostras";
import { TabelaDeAmostras } from "./components/TabelaDeAmostras";
import { listarAmostras } from "@/lib/api";
import type { FiltrosDeAmostra } from "@/lib/tipos";

export default function PaginaDeAmostras() {
  const [filtros, setFiltros] = useState<FiltrosDeAmostra>({
    status: "",
    tipo: "",
  });

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
      <h1>Gestão de Amostras</h1>

      <FiltrosDeAmostras filtros={filtros} aoMudar={setFiltros} />

      {isPending && <p className="aviso">Carregando amostras...</p>}

      {isError && <p className="aviso erro">{error.message}</p>}

      {amostras && amostras.length === 0 && (
        <p className="aviso">Nenhuma amostra encontrada para estes filtros.</p>
      )}

      {amostras && amostras.length > 0 && (
        <TabelaDeAmostras amostras={amostras} />
      )}
    </main>
  );
}
