import { motion } from 'framer-motion';

const flows = [
  {
    title: 'Descoberta guiada',
    text: 'CTA e microcópias claras conduzem novos membros em minutos.',
  },
  {
    title: 'Pagamentos transparentes',
    text: 'Linhas do tempo com estados visuais para cada etapa da cobrança.',
  },
  {
    title: 'Confiança instantânea',
    text: 'Avatares, cores e feedback imediato criam proximidade e segurança.',
  },
];

const FlowShowcase = () => (
  <section className="section-shell" style={{ padding: '2.5rem 0' }}>
    <div className="hero-grid" style={{ padding: 0 }}>
      <div>
        <p className="chip">Fluxos a 3 toques</p>
        <h2 className="section-title">Hierarquias fortes e animações leves.</h2>
        <p className="section-subtitle">
          Interfaces minimalistas com contrastes elegantes e estados bem definidos. Cada clique recebe um feedback visual, criando
          confiança instantânea e rapidez de entendimento.
        </p>
        <div className="timeline">
          {flows.map((flow, index) => (
            <div key={flow.title} className="timeline-step">
              <div className="icon-circle" style={{ background: 'rgba(34,211,238,0.16)' }}>
                <strong>{index + 1}</strong>
              </div>
              <div>
                <strong>{flow.title}</strong>
                <p style={{ margin: '0.15rem 0 0', color: '#cbd5e1' }}>{flow.text}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
      <motion.div
        className="glass"
        initial={{ opacity: 0, y: 12 }}
        whileInView={{ opacity: 1, y: 0 }}
        viewport={{ once: true, amount: 0.4 }}
        transition={{ duration: 0.45 }}
        style={{ padding: '1.4rem', borderRadius: '18px', border: '1px solid rgba(255,255,255,0.08)' }}
      >
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '0.8rem' }}>
          {['Onboarding', 'Compartilhamento', 'Renovação', 'Suporte imediato'].map((item) => (
            <motion.div
              key={item}
              className="bento-card"
              whileHover={{ y: -2, scale: 1.005 }}
              style={{ padding: '1rem', minHeight: '110px' }}
            >
              <p className="tagline" style={{ margin: 0 }}>
                {item}
              </p>
              <h4 style={{ margin: '0.35rem 0 0', color: '#f8fafc' }}>Design vivo + IA</h4>
              <p style={{ margin: '0.35rem 0 0', color: '#cbd5e1', fontSize: '0.95rem' }}>
                Combina transições suaves, microinterações e estados claros.
              </p>
            </motion.div>
          ))}
        </div>
      </motion.div>
    </div>
  </section>
);

export default FlowShowcase;
