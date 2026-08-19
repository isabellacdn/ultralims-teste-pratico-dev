import type { Metadata } from "next";
import Link from "next/link";
import { Providers } from "./providers";
import "./globals.css";

export const metadata: Metadata = {
  title: "Gestao de Amostras",
  description: "Cadastro e acompanhamento de amostras de laboratorio",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="pt-BR">
      <body>
        <header>
          <nav>
            <Link href="/">Amostras</Link>
            <Link href="/amostras/nova">Nova amostra</Link>
          </nav>
        </header>
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
