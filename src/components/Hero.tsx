import { motion } from 'framer-motion';

const highlights = [
  'Fluxos guiados e contextuais',
  'Microinterações em tempo real',
  'Sugestões inteligentes para cada etapa',
];

const Hero = () => {
  return (
    <section className="section-shell hero-grid">
      <div>
        <div className="chip">Experiência reformulada</div>
        <h1 className="section-title" style={{ marginTop: '0.8rem', marginBottom: '0.4rem' }}>
          Interface cinematográfica para organizar assinaturas e pessoas.
        </h1>
        <p className="section-subtitle">
          A nova versão da Juntaplay combina estética minimalista com IA generativa para acelerar convites, organizar grupos e
          criar confiança. Fluxos são autoexplicativos, com transições suaves e feedback visual imediato.
        </p>
        <div style={{ display: 'flex', gap: '0.8rem', flexWrap: 'wrap', marginTop: '1.4rem' }}>
          <button className="pill-button">Criar grupo</button>
          <button className="pill-button secondary-button">Testar IA copiloto</button>
        </div>
        <div className="metric-grid" style={{ marginTop: '1.8rem' }}>
          {[{ label: 'Tempo médio de ação', value: '12s' }, { label: 'Sugestões geradas', value: '120K' }, { label: 'Satisfação', value: '98%' }].map((metric) => (
            <div key={metric.label} className="metric-card">
              <p className="metric-label">{metric.label}</p>
              <p className="metric-value">{metric.value}</p>
            </div>
          ))}
        </div>
      </div>
      <motion.div
        className="glass card"
        initial={{ opacity: 0, y: 12 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5 }}
        style={{ padding: '1.4rem', borderRadius: '20px', border: '1px solid rgba(255,255,255,0.08)', position: 'relative' }}
      >
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '1rem' }}>
          <div>
            <p className="tagline" style={{ margin: 0 }}>
              Copiloto visual
            </p>
            <h3 style={{ margin: '0.2rem 0 0', color: '#f8fafc' }}>Ações inteligentes sugeridas</h3>
          </div>
          <div className="icon-circle" style={{ background: 'rgba(59, 130, 246, 0.15)' }}>
            <span role="img" aria-label="spark">
              ✨
            </span>
          </div>
        </div>
        <div className="timeline">
          {highlights.map((item, index) => (
            <div key={item} className="timeline-step">
              <div className="icon-circle" style={{ width: 36, height: 36, background: 'rgba(124,58,237,0.14)' }}>
                <strong>{index + 1}</strong>
              </div>
              <div>
                <strong>{item}</strong>
                <p style={{ margin: '0.15rem 0 0', color: '#cbd5e1' }}>
                  IA sugere o melhor próximo passo considerando contexto, membros e metas.
                </p>
              </div>
            </div>
          ))}
        </div>
        <motion.div
          initial={{ opacity: 0, scale: 0.96 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 0.35, delay: 0.2 }}
          className="gradient-border"
          style={{ marginTop: '1.2rem', borderRadius: '16px' }}
        >
          <div className="glass" style={{ padding: '1rem 1.1rem', borderRadius: '16px', border: '1px solid rgba(255,255,255,0.12)' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '1rem' }}>
              <div>
                <p style={{ margin: 0, color: '#8be0ff', fontWeight: 600 }}>Resposta inteligente</p>
                <h4 style={{ margin: '0.2rem 0', color: '#f8fafc' }}>Mensagem pronta para enviar</h4>
              </div>
              <span className="chip" style={{ background: 'rgba(59,130,246,0.16)' }}>
                Personalizada
              </span>
            </div>
            <p style={{ margin: '0.6rem 0 0', color: '#cbd5e1' }}>
              “Oi, Ana! Preparei seu acesso Disney+ na família Costa. Entre com este código e aproveite playlists e watch-parties
              já configuradas para você.”
            </p>
          </div>
        </motion.div>
      </motion.div>
    </section>
  );
};

export default Hero;
