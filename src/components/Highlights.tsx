import { motion } from 'framer-motion';

const items = [
  {
    title: 'Arquitetura modular',
    text: 'Separação clara de camadas, rotas prontas e componentes coesos.',
  },
  {
    title: 'Performance instantânea',
    text: 'Carregamento otimizado, assets leves e animações com 60fps.',
  },
  {
    title: 'Mobile first',
    text: 'Layout fluido com gestos e espaçamentos pensados para toque.',
  },
];

const Highlights = () => (
  <section className="section-shell" style={{ padding: '2.5rem 0' }}>
    <div className="bento-grid">
      {items.map((item, index) => (
        <motion.div key={item.title} className="bento-card" initial={{ opacity: 0, y: 8 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ delay: index * 0.05 }}>
          <p className="tagline" style={{ margin: 0 }}>
            {item.title}
          </p>
          <h3 style={{ margin: '0.4rem 0 0', color: '#f8fafc' }}>{item.text}</h3>
          <p style={{ margin: '0.4rem 0 0', color: '#cbd5e1' }}>
            Toques de cor, ícones nítidos e hierarquias visuais fortes reforçam foco e clareza.
          </p>
        </motion.div>
      ))}
    </div>
  </section>
);

export default Highlights;
