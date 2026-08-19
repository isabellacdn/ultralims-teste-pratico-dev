import type { Amostra } from "@/lib/tipos";

type Props = {
  amostras: Amostra[];
};

export function TabelaDeAmostras({ amostras }: Props) {
  return (
    <table>
      <thead>
        <tr>
          <th>Codigo</th>
          <th>Tipo</th>
          <th>Status</th>
          <th>Responsavel tecnico</th>
          <th>Recebimento</th>
          <th>Conclusao</th>
        </tr>
      </thead>
      <tbody>
        {amostras.map((amostra) => (
          <tr key={amostra.id}>
            <td>{amostra.codigo}</td>
            <td>{amostra.tipo}</td>
            <td>
              <span className={`status status-${amostra.status.toLowerCase()}`}>
                {amostra.status}
              </span>
            </td>
            <td>{amostra.responsavel_tecnico ?? "—"}</td>
            <td>{formatarData(amostra.data_recebimento)}</td>
            <td>{formatarData(amostra.data_conclusao)}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}

function formatarData(data: string | null): string {
  if (data === null) {
    return "—";
  }

  const [ano, mes, dia] = data.split("-");

  return `${dia}/${mes}/${ano}`;
}
