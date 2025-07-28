import { useState, useEffect } from 'react';


function balanceTeams(players) {
  const positions = [
    'Goleiro',
    'Fixo',
    'Lateral Direito',
    'Lateral Esquerdo',
    'Meia',
    'Piv\u00f4',
  ];

  const groups = Object.fromEntries(positions.map((p) => [p, []]));
  players.forEach((pl) => {
    if (groups[pl.position]) groups[pl.position].push(pl);
  });

  const value = (stone) => parseInt(stone.replace(/\D/g, ''), 10) || 0;
  positions.forEach((pos) => {
    groups[pos].sort((a, b) => value(a.stone) - value(b.stone));
  });

  const teams = [[], [], []];
  groups['Goleiro'].forEach((p, i) => {
    teams[i % 2].push(p);
  });

  positions.slice(1).forEach((pos) => {
    groups[pos].forEach((p, idx) => {
      teams[idx % 3].push(p);
    });
  });

  teams.forEach((t) => t.sort(() => Math.random() - 0.5));
  teams.forEach((t, i) => {
    teams[i] = t.slice(0, 5);
  });
  return teams;
}

export default function useTeams(initialPlayers) {
  const safeParse = (key, fallback) => {
    try {
      const value = localStorage.getItem(key);
      return value ? JSON.parse(value) : fallback;
    } catch {
      localStorage.removeItem(key);
      return fallback;
    }
  };

  const [players, setPlayers] = useState(() => safeParse('players', initialPlayers));
  const [teams, setTeams] = useState(() => safeParse('teams', []));

  useEffect(() => {
    localStorage.setItem('players', JSON.stringify(players));
  }, [players]);

  useEffect(() => {
    localStorage.setItem('teams', JSON.stringify(teams));
  }, [teams]);

  const updatePlayer = (id, newPlayer) => {
    setPlayers((prev) => prev.map((p) => (p.id === id ? newPlayer : p)));
  };

  const reset = () => {
    setPlayers(initialPlayers);
    setTeams([]);
    localStorage.removeItem('players');
    localStorage.removeItem('teams');
  };

  const clearTeams = () => {
    setTeams([]);
    localStorage.removeItem('teams');
  };

  const sortTeams = () => {
    const balanced = balanceTeams(players);
    setTeams(balanced);
  };

  return { players, updatePlayer, reset, sortTeams, clearTeams, teams };
}
