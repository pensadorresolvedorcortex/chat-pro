import React, { useState, useEffect } from 'react';
import {
  IonApp,
  IonContent,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonButton,
  IonList,
  IonItem,
  IonInput,
  IonSelect,
  IonSelectOption,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent
} from '@ionic/react';

const PORTELA = [
  'Dheniell','Dario','Papel','Wallace','Matheus','Kloh','Bebê',
  'Costela','Diego','Matheus MP','Gabriel','Bolo','refém','Caputo',
  'Fred','Darlan','Baiano'
];

const RESENHA = [
  'Dheniell','Felipe','Gabriel','Lucas','Bahia','Bilico','Quelotti',
  'Ricardo','Pepe','Pedron','Rezende','Schittini','Tai','Dani',
  'Nicolato','Rodolfo','Juninho'
];

function initPlayers(list) {
  return list.map(name => ({ name, pos: 'Linha', pedra: '4' }));
}

function shuffle(arr) {
  for (let i = arr.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
}

export default function App() {
  const [players, setPlayers] = useState([]);
  const [teams, setTeams] = useState([]);
  const [shareText, setShareText] = useState('');

  useEffect(() => {
    setPlayers(initPlayers(PORTELA));
  }, []);

  const loadRoster = (list) => {
    setPlayers(initPlayers(list));
    setTeams([]);
    setShareText('');
  };

  const updateField = (idx, field, value) => {
    setPlayers(prev => {
      const next = [...prev];
      next[idx] = { ...next[idx], [field]: value };
      return next;
    });
  };

  const saveRoster = () => {
    localStorage.setItem('jogadores', JSON.stringify(players));
    alert('Jogadores salvos');
  };

  const loadSaved = () => {
    const data = localStorage.getItem('jogadores');
    if (data) {
      setPlayers(JSON.parse(data));
      setTeams([]);
      setShareText('');
    }
  };

  const sortTeams = () => {
    const valid = players.filter(p => p.name.trim() !== '').map(p => ({
      name: p.name,
      pos: p.pos || 'Linha',
      pedra: parseInt(p.pedra || '4', 10)
    }));
    const goleiros = valid.filter(p => p.pos === 'Goleiro');
    const linha = valid.filter(p => p.pos !== 'Goleiro');
    const teams = [[], [], []];
    const capacity = [6,6,5];

    shuffle(goleiros);
    if (goleiros[0]) teams[0].push(goleiros[0]);
    if (goleiros[1]) teams[1].push(goleiros[1]);
    for (let i=2; i<goleiros.length; i++) {
      goleiros[i].pos = 'Linha';
      linha.push(goleiros[i]);
    }

    const porPedra = {1:[],2:[],3:[],4:[]};
    linha.forEach(j => porPedra[j.pedra].push(j));
    let t = 0;
    for (let pedra=1; pedra<=4; pedra++) {
      shuffle(porPedra[pedra]);
      for (const j of porPedra[pedra]) {
        let attempts = 0;
        while (attempts < 3 && teams[t].length >= capacity[t]) {
          t = (t + 1) % 3;
          attempts++;
        }
        if (attempts === 3) break;
        teams[t].push(j);
        t = (t + 1) % 3;
      }
    }
    setTeams(teams);
    let txt = '';
    teams.forEach((time, idx) => {
      txt += `Time ${idx+1}:\n` + time.map(p => `${p.name} - ${p.pos} - Pedra ${p.pedra}`).join('\n') + '\n\n';
    });
    setShareText(txt.trim());
  };

  return (
    <IonApp>
      <IonHeader>
        <IonToolbar>
          <IonTitle>Sorteio de Times</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent className="ion-padding">
        <div className="btn-bar">
          <IonButton onClick={() => loadRoster(PORTELA)}>Portela</IonButton>
          <IonButton onClick={() => loadRoster(RESENHA)}>Resenha Sagrada</IonButton>
          <IonButton onClick={sortTeams}>Sortear Times</IonButton>
          <IonButton onClick={saveRoster}>Salvar Times</IonButton>
          <IonButton onClick={loadSaved}>Jogadores Semana Passada</IonButton>
        </div>
        <IonList>
          {players.map((p, idx) => (
            <IonItem key={idx}>
              <IonInput value={p.name} onIonChange={e => updateField(idx, 'name', e.detail.value)} />
              <IonSelect value={p.pos} placeholder="Posição" onIonChange={e => updateField(idx, 'pos', e.detail.value)}>
                <IonSelectOption value="Goleiro">Goleiro</IonSelectOption>
                <IonSelectOption value="Linha">Linha</IonSelectOption>
              </IonSelect>
              <IonSelect value={p.pedra} placeholder="Pedra" onIonChange={e => updateField(idx, 'pedra', e.detail.value)}>
                <IonSelectOption value="1">Pedra 1</IonSelectOption>
                <IonSelectOption value="2">Pedra 2</IonSelectOption>
                <IonSelectOption value="3">Pedra 3</IonSelectOption>
                <IonSelectOption value="4">Pedra 4</IonSelectOption>
              </IonSelect>
            </IonItem>
          ))}
        </IonList>
        {teams.length > 0 && (
          <div className="result">
            {teams.map((time, idx) => (
              <IonCard key={idx}>
                <IonCardHeader>
                  <IonCardTitle>Time {idx + 1}</IonCardTitle>
                </IonCardHeader>
                <IonCardContent>
                  <ul>
                    {time.map((j, i) => (
                      <li key={i}>{j.name} - {j.pos} - Pedra {j.pedra}</li>
                    ))}
                  </ul>
                </IonCardContent>
              </IonCard>
            ))}
            {shareText && (
              <IonButton expand="block" href={`https://wa.me/?text=${encodeURIComponent(shareText)}`} target="_blank">
                Compartilhar no WhatsApp
              </IonButton>
            )}
          </div>
        )}
      </IonContent>
    </IonApp>
  );
}

