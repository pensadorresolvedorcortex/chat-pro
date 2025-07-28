import React from 'react';

function TeamList({ teams }) {
  return (
    <div className="grid md:grid-cols-3 gap-4 mt-4">
      {teams.map((team, idx) => (
        <div key={idx} className="border rounded p-4 bg-white dark:bg-gray-900 shadow">
          <h3 className="font-bold mb-2 text-center">Time {idx + 1}</h3>
          <ul className="space-y-1">
            {team.map((p) => (
              <li key={p.id}>
                {p.name} - {p.position} - {p.stone}
              </li>
            ))}
          </ul>
        </div>
      ))}
    </div>
  );
}

export default TeamList;
