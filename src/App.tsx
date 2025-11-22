import Navigation from './components/Navigation';
import Hero from './components/Hero';
import ServiceGrid from './components/ServiceGrid';
import AIPanel from './components/AIPanel';
import FlowShowcase from './components/FlowShowcase';
import Highlights from './components/Highlights';
import ProgressStatus from './components/ProgressStatus';

function App() {
  return (
    <div>
      <Navigation />
      <main>
        <Hero />
        <Highlights />
        <ServiceGrid />
        <AIPanel />
        <FlowShowcase />
        <ProgressStatus />
      </main>
      <footer className="footer">
        <p style={{ margin: 0 }}>Juntaplay Next – visual, rápida e guiada por IA.</p>
      </footer>
    </div>
  );
}

export default App;
