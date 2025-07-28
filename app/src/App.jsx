import React from 'react';
import PlayerTable from './components/PlayerTable.jsx';
import TeamList from './components/TeamList.jsx';
import useTeams from './hooks/useTeams.js';
import { initialPlayers } from './data/players.js';
import './App.css';

function App() {
  const { players, updatePlayer, reset, sortTeams, clearTeams, teams } = useTeams(initialPlayers);

  const saveTeams = () => {
    if (teams.length === 0) return;
    const text = teams
      .map((team, idx) => `Time ${idx + 1}\n` + team.map((p) => `- ${p.name} (${p.position}) ${p.stone}`).join('\n'))
      .join('\n\n');
    navigator.clipboard.writeText(text);
    alert('Times copiados para a área de transferência!');
  };

  return (
    <div className="container mx-auto p-4">
      <h1 className="text-2xl font-bold mb-4 text-center">Sorteio de Times</h1>
      <PlayerTable players={players} onChange={updatePlayer} />
      <div className="flex flex-wrap gap-2 mt-4 justify-center">
        <button className="bg-blue-500 hover:bg-blue-600 transition-colors text-white px-3 py-1 rounded" onClick={sortTeams}>
          Sortear Times
        </button>
        <button className="bg-gray-500 hover:bg-gray-600 transition-colors text-white px-3 py-1 rounded" onClick={reset}>
          Resetar Tabela
        </button>
        <button
          className="bg-red-500 hover:bg-red-600 transition-colors text-white px-3 py-1 rounded"
          onClick={clearTeams}
          disabled={teams.length === 0}
        >
          Limpar Times
        </button>
        <button
          className="bg-green-500 hover:bg-green-600 transition-colors text-white px-3 py-1 rounded"
          onClick={saveTeams}
          disabled={teams.length === 0}
        >
          Salvar Times
        </button>
      </div>
      {teams.length > 0 && <TeamList teams={teams} />}
    </div>
  );
}

export default App;
