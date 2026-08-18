import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Gestao de Amostras",
  description: "Cadastro e acompanhamento de amostras de laboratorio",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="pt-BR">
      <body>{children}</body>
    </html>
  );
}
