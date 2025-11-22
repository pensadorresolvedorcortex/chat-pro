import { motion } from 'framer-motion';

const Navigation = () => {
  return (
    <header className="section-shell" style={{ paddingTop: '2.2rem' }}>
      <div className="glass" style={{ borderRadius: '16px', padding: '0.85rem 1.2rem', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '1rem' }}>
        <motion.div initial={{ opacity: 0, y: -8 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.4 }} style={{ display: 'flex', alignItems: 'center', gap: '0.7rem' }}>
          <div className="icon-circle" style={{ background: 'linear-gradient(145deg, #8b5cf6, #22d3ee)', color: '#0b1220' }}>
            <span style={{ fontWeight: 800 }}>J</span>
          </div>
          <div>
            <strong style={{ fontSize: '1.05rem', letterSpacing: '-0.01em' }}>Juntaplay Next</strong>
            <p style={{ margin: 0, color: '#cbd5e1', fontSize: '0.9rem' }}>Imersão visual e experiência guiada</p>
          </div>
        </motion.div>
        <div style={{ display: 'flex', alignItems: 'center', gap: '0.8rem' }}>
          <button className="pill-button secondary-button">Explorar UI</button>
          <button className="pill-button">Começar agora</button>
        </div>
      </div>
    </header>
  );
};

export default Navigation;
