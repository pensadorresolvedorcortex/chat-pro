import { motion } from 'framer-motion';

const personas = [
  {
    title: 'Família & streaming',
    desc: 'Netflix + Spotify com convites automáticos e divisão de cotas.',
  },
  {
    title: 'Squad de estudos',
    desc: 'Calendly + Notion + Figma com acesso guiado para cada membro.',
  },
  {
    title: 'Time gamer',
    desc: 'Game Pass + Discord Nitro com alertas de renovação e status.',
  },
];

const CreateGroup = () => {
  return (
    <section className="section-shell create-grid">
      <div>
        <div className="chip">Criar um grupo</div>
        <h2 className="section-title" style={{ marginTop: '0.9rem' }}>
          Glassmorphism orientado por IA para configurar grupos em minutos.
        </h2>
        <p className="section-subtitle">
          Estrutura clara, cartões translúcidos e microinterações que guiam cada escolha. O copiloto sugere serviços, define
          cotas e já envia convites elegantes para todos.
        </p>
        <div className="persona-grid">
          {personas.map((persona, index) => (
            <motion.div
              key={persona.title}
              className="glass persona-card"
              initial={{ opacity: 0, y: 12 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, amount: 0.5 }}
              transition={{ duration: 0.35, delay: index * 0.05 }}
            >
              <div className="icon-circle" style={{ background: 'rgba(255,255,255,0.08)' }}>
                <span role="img" aria-label="sparkles">
                  ✨
                </span>
              </div>
              <div>
                <h4 style={{ margin: '0 0 0.2rem', color: '#f8fafc' }}>{persona.title}</h4>
                <p style={{ margin: 0, color: '#cbd5e1' }}>{persona.desc}</p>
              </div>
            </motion.div>
          ))}
        </div>
      </div>
      <motion.div
        className="glass create-panel"
        initial={{ opacity: 0, scale: 0.98 }}
        whileInView={{ opacity: 1, scale: 1 }}
        viewport={{ once: true, amount: 0.45 }}
        transition={{ duration: 0.4 }}
      >
        <div className="create-header">
          <div>
            <p className="tagline" style={{ margin: 0 }}>
              Fluxo guiado
            </p>
            <h3 style={{ margin: '0.25rem 0 0', color: '#f8fafc' }}>Defina o grupo com feedback imediato</h3>
          </div>
          <span className="chip" style={{ background: 'rgba(255,255,255,0.08)' }}>
            Glass ready
          </span>
        </div>
        <div className="create-form">
          <label className="form-field">
            <span>Nome do grupo</span>
            <input placeholder="Ex: Squad Filmes & Séries" className="glass-input" />
          </label>
          <label className="form-field">
            <span>Serviços</span>
            <div className="pill-select">
              {['Netflix', 'Spotify', 'Disney+', 'Notion'].map((service) => (
                <button key={service} type="button" className="pill-option">
                  {service}
                </button>
              ))}
              <button type="button" className="pill-option ghost">
                + adicionar
              </button>
            </div>
          </label>
          <div className="dual-fields">
            <label className="form-field">
              <span>Qtd. de membros</span>
              <input placeholder="4 pessoas" className="glass-input" />
            </label>
            <label className="form-field">
              <span>Cota por pessoa</span>
              <input placeholder="R$ 19,90" className="glass-input" />
            </label>
          </div>
          <label className="form-field">
            <span>Mensagem de convite</span>
            <textarea
              placeholder="IA sugere textos acolhedores, com links e lembretes automáticos."
              rows={3}
              className="glass-input"
            />
          </label>
          <div className="create-actions">
            <div className="status-bubble">
              <strong>Pronto para lançar</strong>
              <p>Resumo em tempo real, IA ativa e convites preparados.</p>
            </div>
            <div style={{ display: 'flex', gap: '0.6rem', flexWrap: 'wrap' }}>
              <button className="pill-button">Criar grupo</button>
              <button className="pill-button secondary-button">Pré-visualizar convite</button>
            </div>
          </div>
        </div>
      </motion.div>
    </section>
  );
};

export default CreateGroup;
