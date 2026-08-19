import type { Metadata } from "next";
import { Barlow, JetBrains_Mono } from "next/font/google";
import { Cabecalho } from "./components/Cabecalho";
import { Providers } from "./providers";
import "./globals.css";

const interface_ = Barlow({
  subsets: ["latin"],
  weight: ["400", "500", "600"],
  variable: "--fonte-interface",
});

const monoespacada = JetBrains_Mono({
  subsets: ["latin"],
  weight: ["400", "500"],
  variable: "--fonte-monoespacada",
});

export const metadata: Metadata = {
  title: "Gestão de Amostras",
  description: "Cadastro e acompanhamento de amostras de laboratório",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="pt-BR" className={`${interface_.variable} ${monoespacada.variable}`}>
      <body>
        <Cabecalho />
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
