import { useMemo, useState } from 'react';
import { motion } from 'framer-motion';
import { aiSuggestions } from '../data/services';

const badges = ['IA generativa', 'Autocomplete', 'Smart replies'];

const AIPanel = () => {
  const [input, setInput] = useState('');
  const filtered = useMemo(
    () =>
      aiSuggestions.filter((suggestion) => suggestion.toLowerCase().includes(input.toLowerCase())).slice(0, 4),
    [input],
  );

  return (
    <section className="section-shell" style={{ padding: '2rem 0 2.5rem' }}>
      <div className="grid two-col" style={{ alignItems: 'start' }}>
        <div>
          <p className="chip">Copiloto de interação</p>
          <h2 className="section-title">Sugestões inteligentes e respostas com um toque.</h2>
          <p className="section-subtitle">
            O campo inteligente combina autocomplete, respostas prontas e microinterações para acelerar tudo: convites, cobranças,
            ativações e follow-ups. Basta digitar a intenção para receber sugestões humanizadas.
          </p>
          <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap', marginTop: '1rem' }}>
            {badges.map((badge) => (
              <span key={badge} className="chip">
                {badge}
              </span>
            ))}
          </div>
        </div>
        <div className="ai-panel">
          <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
            <input
              className="ai-input"
              placeholder="Descreva o que deseja automatizar..."
              value={input}
              onChange={(event) => setInput(event.target.value)}
            />
            <button className="pill-button" style={{ paddingInline: '1.1rem' }}>
              Gerar
            </button>
          </div>
          <div className="suggestion-grid">
            {(filtered.length ? filtered : aiSuggestions.slice(0, 4)).map((suggestion) => (
              <motion.div
                key={suggestion}
                whileHover={{ scale: 1.01 }}
                className="suggestion-card"
                onClick={() => setInput(suggestion)}
              >
                <p style={{ margin: 0, color: '#f8fafc', fontWeight: 600 }}>{suggestion}</p>
                <p style={{ margin: '0.35rem 0 0', color: '#cbd5e1' }}>
                  IA adapta o tom, cria contexto e entrega o texto pronto com CTA claro.
                </p>
              </motion.div>
            ))}
          </div>
          <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            className="glass"
            style={{ padding: '1rem', borderRadius: '14px', border: '1px solid rgba(255,255,255,0.1)' }}
          >
            <p style={{ margin: 0, color: '#8be0ff', fontWeight: 700 }}>Resposta sugerida</p>
            <p style={{ margin: '0.4rem 0 0', color: '#e2e8f0', fontWeight: 600 }}>
              “Convite pronto! Enviamos para Camila com contexto de grupo, datas e links. Deseja adicionar um lembrete?”
            </p>
          </motion.div>
        </div>
      </div>
    </section>
  );
};

export default AIPanel;
