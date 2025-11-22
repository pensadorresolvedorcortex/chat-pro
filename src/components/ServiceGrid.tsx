import { motion } from 'framer-motion';
import { services } from '../data/services';

const ServiceGrid = () => (
  <section className="section-shell" style={{ padding: '2.5rem 0' }}>
    <div style={{ display: 'flex', justifyContent: 'space-between', gap: '1rem', alignItems: 'flex-end', flexWrap: 'wrap' }}>
      <div>
        <p className="chip">Biblioteca viva</p>
        <h2 className="section-title">Cards que respiram e respondem.</h2>
        <p className="section-subtitle">Selecione um serviço e veja animações, feedback imediato e prioridades visuais claras.</p>
      </div>
      <button className="pill-button secondary-button" style={{ whiteSpace: 'nowrap' }}>
        Ver catálogo completo
      </button>
    </div>
    <div className="grid two-col" style={{ marginTop: '1.4rem' }}>
      {services.map((service) => (
        <motion.article
          key={service.name}
          className="bento-card"
          whileHover={{ y: -4, scale: 1.01 }}
          transition={{ type: 'spring', stiffness: 260, damping: 18 }}
          style={{ background: service.gradient, position: 'relative', overflow: 'hidden' }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
            <div className="icon-circle" style={{ background: 'rgba(255,255,255,0.12)', borderColor: 'rgba(255,255,255,0.2)' }}>
              <img src={service.icon} alt={service.name} width={22} height={22} />
            </div>
            <div>
              <h3 style={{ margin: 0, color: '#0b1220', mixBlendMode: 'screen' }}>{service.name}</h3>
              <p style={{ margin: 0, color: '#0b1220cc', fontWeight: 600 }}>Harmonia visual + produtividade</p>
            </div>
          </div>
          <p style={{ margin: '0.9rem 0 0', color: '#0b1220de', fontWeight: 500 }}>{service.description}</p>
          <div style={{ display: 'flex', gap: '0.6rem', marginTop: '1rem', flexWrap: 'wrap' }}>
            {['Convites rápidos', 'Gestão de vagas', 'Alertas delicados'].map((pill) => (
              <span key={pill} className="chip" style={{ background: 'rgba(255,255,255,0.18)', color: '#0b1220', borderColor: 'rgba(0,0,0,0.08)' }}>
                {pill}
              </span>
            ))}
          </div>
          <motion.div
            initial={{ scale: 0.98, opacity: 0.4 }}
            animate={{ scale: 1, opacity: 1 }}
            transition={{ duration: 0.4 }}
            style={{ position: 'absolute', inset: 0, pointerEvents: 'none', background: `radial-gradient(circle at 20% 30%, ${service.accent}33, transparent 40%)` }}
          />
        </motion.article>
      ))}
    </div>
  </section>
);

export default ServiceGrid;
