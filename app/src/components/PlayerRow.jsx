import React from 'react';

function PlayerRow({ player, onChange }) {
  const handleChange = (field) => (e) => {
    onChange(player.id, { ...player, [field]: e.target.value });
  };

  return (
    <tr className="border-b hover:bg-gray-50 dark:hover:bg-gray-700">
      <td className="p-2 text-center">{player.id}</td>
      <td className="p-2">
        <input
          className="w-full bg-transparent border rounded p-1 focus:outline-none focus:ring"
          aria-label="Nome do jogador"
          value={player.name}
          onChange={handleChange('name')}
        />
      </td>
      <td className="p-2">
        <select
          className="w-full bg-transparent border rounded p-1 focus:outline-none focus:ring"
          aria-label="Posição"
          value={player.position}
          onChange={handleChange('position')}
        >
          <option>Goleiro</option>
          <option>Fixo</option>
          <option>Lateral Direito</option>
          <option>Lateral Esquerdo</option>
          <option>Meia</option>
          <option>Piv\u00f4</option>
        </select>
      </td>
      <td className="p-2">
        <select
          className="w-full bg-transparent border rounded p-1 focus:outline-none focus:ring"
          aria-label="Pedra"
          value={player.stone}
          onChange={handleChange('stone')}
        >
          <option>Pedra 1</option>
          <option>Pedra 2</option>
          <option>Pedra 3</option>
        </select>
      </td>
    </tr>
  );
}

export default PlayerRow;
