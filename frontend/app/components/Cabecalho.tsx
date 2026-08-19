"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

const NOMES_DE_TELA: Record<string, string> = {
  "/": "Listagem",
};

export function Cabecalho() {
  const caminho = usePathname();

  return (
    <header>
      <Link href="/" className="marca">
        Ultralims · Amostras
      </Link>
      <span className="tela-atual">{NOMES_DE_TELA[caminho] ?? "Amostras"}</span>
    </header>
  );
}
