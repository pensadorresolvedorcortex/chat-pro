import React from 'react';
import PlayerRow from './PlayerRow.jsx';

function PlayerTable({ players, onChange }) {
  return (
    <table className="w-full text-sm border-collapse rounded shadow bg-white dark:bg-gray-900">
      <thead>
        <tr className="bg-gray-200 dark:bg-gray-800">
          <th className="p-2">N\u00ba</th>
          <th className="p-2">Nome do Jogador</th>
          <th className="p-2">Posi\u00e7\u00e3o</th>
          <th className="p-2">Pedra</th>
        </tr>
      </thead>
      <tbody>
        {players.map((p) => (
          <PlayerRow key={p.id} player={p} onChange={onChange} />
        ))}
      </tbody>
    </table>
  );
}

export default PlayerTable;
