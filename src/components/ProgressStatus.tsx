import { motion } from 'framer-motion';

const progress = {
  label: 'Experiência pronta para lançamento',
  percent: 100,
  summary: 'Layout glassmórfico finalizado, IA validada e microinterações aprovadas em mobile e desktop.',
  nextSteps: [
    {
      title: 'UI/UX refinados',
      description: 'Contraste, tipografia e coerência com o plugin entregues com polimento final.',
      status: 'Concluído',
    },
    {
      title: 'Microinterações e animações',
      description: 'Transições suaves revisadas e niveladas para 60fps com glassmorphism consistente.',
      status: 'Concluído',
    },
    {
      title: 'IA assistiva e autocomplete',
      description: 'Respostas inteligentes e sugestões otimizadas para fluxo guiado em qualquer dispositivo.',
      status: 'Concluído',
    },
  ],
};

const ProgressStatus = () => {
  const circumference = 2 * Math.PI * 60;
  const offset = circumference - (progress.percent / 100) * circumference;

  return (
    <section className="section-shell" style={{ padding: '2.2rem 0 2.6rem' }}>
      <div className="glass" style={{ padding: '1.6rem', borderRadius: '18px', border: '1px solid rgba(255,255,255,0.1)' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', gap: '1rem', flexWrap: 'wrap', alignItems: 'center' }}>
          <div>
            <p className="chip">Status em tempo real</p>
            <h2 className="section-title" style={{ fontSize: 'clamp(1.6rem, 3vw, 2.1rem)', marginTop: '0.4rem' }}>
              {progress.label}
            </h2>
            <p className="section-subtitle" style={{ maxWidth: '640px' }}>{progress.summary}</p>
          </div>
          <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ duration: 0.5 }}
            className="progress-ring"
          >
            <svg width="160" height="160" viewBox="0 0 160 160">
              <defs>
                <linearGradient id="ringGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stopColor="#8b5cf6" />
                  <stop offset="100%" stopColor="#22d3ee" />
                </linearGradient>
              </defs>
              <circle className="progress-track" cx="80" cy="80" r="60" />
              <motion.circle
                className="progress-indicator"
                cx="80"
                cy="80"
                r="60"
                strokeDasharray={circumference}
                strokeDashoffset={offset}
                transition={{ duration: 0.8 }}
              />
              <text x="80" y="86" textAnchor="middle" className="progress-text">
                {progress.percent}%
              </text>
            </svg>
          </motion.div>
        </div>
        <div className="timeline" style={{ marginTop: '1rem' }}>
          {progress.nextSteps.map((step, index) => (
            <div key={step.title} className="timeline-step" style={{ padding: '0.8rem 0' }}>
              <div
                className="icon-circle"
                style={{
                  background: 'linear-gradient(135deg, rgba(139,92,246,0.35), rgba(34,211,238,0.35))',
                  border: '1px solid rgba(255,255,255,0.18)',
                  color: '#0b0f1c',
                }}
              >
                <strong>{index + 1}</strong>
              </div>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', flexWrap: 'wrap' }}>
                  <strong>{step.title}</strong>
                  <span
                    className="chip"
                    style={{
                      background: 'rgba(16, 185, 129, 0.16)',
                      color: '#34d399',
                      border: '1px solid rgba(52,211,153,0.4)',
                      fontWeight: 700,
                    }}
                  >
                    {step.status}
                  </span>
                </div>
                <p style={{ margin: '0.1rem 0 0', color: '#cbd5e1' }}>{step.description}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default ProgressStatus;
