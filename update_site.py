from bs4 import BeautifulSoup, NavigableString
from pathlib import Path
import subprocess
import json

html_path = Path('site/Guell Almeida – Agência de Marketing Digital em São Paulo – SP.htm')
assets_dir = Path('Guell Almeida – Agência de Marketing Digital em São Paulo – SP_files')


def normalize_asset_filename(name: str) -> str:
    if name.endswith('.transferir'):
        name = name[: -len('.transferir')]
    return {
        'css': 'css.css',
        'css(1)': 'css-1.css',
        'css(2)': 'css-2.css',
        'css(3)': 'css-3.css',
        'css(4)': 'css-4.css',
        'css2': 'css2.css',
    }.get(name, name)


def normalize_asset_files(directory: Path) -> None:
    if not directory.exists():
        return
    for path in sorted(directory.iterdir(), key=lambda p: p.name):
        new_name = normalize_asset_filename(path.name)
        if new_name != path.name:
            target = directory / new_name
            if not target.exists():
                path.rename(target)


normalize_asset_files(assets_dir)

html = html_path.read_text(encoding='utf-8')
soup = BeautifulSoup(html, 'html.parser')

# Mapping of filenames to absolute URLs from live site
live_html = subprocess.check_output(['curl', '-s', 'https://agenciadigitalsaopaulo.com.br/guell/']).decode('utf-8')
live_soup = BeautifulSoup(live_html, 'html.parser')
img_map = {}
for img in live_soup.find_all('img'):
    src = img.get('data-src') or img.get('data-lazy-src') or img.get('src')
    if not src:
        continue
    filename = src.split('/')[-1]
    img_map[filename] = src

# Fallbacks for duplicated filenames in the exported theme assets
if 'logo.png' in img_map and 'logo(1).png' not in img_map:
    img_map['logo(1).png'] = img_map['logo.png']

# Text replacements
replacements = {
    'Launch Bold Ideas. Build Brighter Futures.': 'Guell Almeida — Designer & Social Media',
    'Empowering startups with boldstrategy, creative design, & smarttechnology to launch, grow, lead inthe digital world.': 'Guiando marcas com estratégia criativa e tecnologia para lançar e escalar no mundo digital.',
    'Empowering startups withbold strategy, creative': 'Guiando marcas com',
    'bold strategy, creative': 'Estratégia criativa e',
    'technology to launch, grow, lead in': 'tecnologia para lançar e escalar',
    'the digital world.': 'o mundo digital.',
    'Empowering startups with': 'Guiando marcas com',
    'Collaborating with visionary clients andparners for startup excellence.': 'Identidade Visual & Logotipos',
    'Collaborating with visionary clients and': 'Identidade Visual & ',
    'parners for startup excellence.': 'Logotipos',
    'Flexible pricing plans tailored forgrowing startups.': 'Gestão de Mídias Sociais',
    'growing startups.': '',
    'Our Case Studies and': 'Portfólio de',
    'Success Stories': 'Projetos',
    'Frequently Asked Questions': 'Consultoria Online Grátis',
    'Streamlined workflow for startup project success.': 'Sites Profissionais & Landing Pages',
    'Empowering startups withcreative solutions andproven growth strategies.': 'Edição de Vídeos & Motion Design',
    'We empower startups through innovative solutions and data-driven growth strategies tailored for long-term success.': 'Atendo marcas com soluções criativas e inteligência de dados para crescimento contínuo.',
    'At our core, we empower bold thinkers and ambitious startups to bring their ideas to life. From branding to digital solutions, we craft experiences that connect, inspire, and convert. Our team blends strategy, design, and technology to deliver results that matter.': 'No coração do estúdio, impulsiono mentes ousadas a tirar ideias do papel. Do branding às soluções digitais, crio experiências que conectam, inspiram e convertem. Estratégia, design e tecnologia se unem para gerar resultado real.',
    'We don’t just build projects — we build momentum. Driven by curiosity and innovation, we thrive on solving real-world problems. Whether you’re just starting or scaling fast, we’re here to guide the way.': 'Não entrego só projetos — construo impulso contínuo. Com curiosidade e inovação, resolvo desafios reais. Seja no início ou em expansão, guio cada etapa com clareza.',
    'What our customer says.': 'Depoimentos de Clientes',
    'Learn something more fromour latest blogs.': 'Consultoria Online Grátis',
    'Let’s work': 'Fale no',
    'TGether': 'Whats',
    'Gether': 'App',
    'Subscribe our': 'Assine nosso',
    'newsletter': 'boletim',
    'Subscribe our \nnewsletter': 'Assine nosso boletim',
    '24/7 Customer support': 'Atendimento 24/7',
    '24/7 business support': 'Suporte 24/7',
    'About Us': 'Sobre',
    'About Us Coreriver': 'Sobre Guell',
    'Access to premium content': 'Acesso a conteúdo premium',
    'Advanced features for growing teams': 'Recursos avançados para equipes',
    'Advanced features for scaling business': 'Recursos avançados para escalar',
    'Advanced page optimization': 'Otimização avançada web',
    'Agile development ensures a scalable, secure product with continuous testing for quality.': 'Desenvolvimento ágil garante produto escalável e seguro com testes contínuos.',
    'All Rights Reserved.': 'Direitos reservados.',
    'Amy Adams': 'Ana Magal',
    'Annually': 'Anual',
    'Awards Winning': 'Prêmios ganhos',
    'Basic Plan': 'Plano Base',
    'Basic on-page optimization': 'Otimização básica on-page',
    'Best Services Coreriver': 'Identidade Visual',
    'Blog & News': 'Blog & dicas',
    'Blog Carousel': 'Blog slides',
    'Blog Details': 'Detalhe blog',
    'Blog Grids': 'Blog grade',
    'Blog Standard': 'Blog padrão',
    'Brand Strategy': 'Ident. visual',
    'Brand Strategy for AI Startup': 'Identidade para startup IA',
    'Business Development': 'Gestão comercial',
    'Business Development Planning': 'Planejamento comercial',
    'Career': 'Vagas',
    'Career Details': 'Detalhes vagas',
    'Cart': 'Loja',
    'Case Studies': 'Cases',
    'Checkout': 'Pagar',
    'Chief Product Officer, SolarGen Inc.': 'Diretora de Produto, SolarGen',
    'Company Insights': 'Insights Guell',
    'Comprehensive portfolio tracking': 'Gestão completa do portfólio',
    'Contact Us': 'Contato',
    'Coreriver': 'Guell',
    'Dedicated expert team': 'Equipe especialista',
    'Design & Prototyping': 'Design e protótipos',
    'Development & Testing': 'Dev. e testes',
    'Discover More': 'Saiba mais',
    'Discovery & Strategy': 'Exploração e tática',
    'Do you offer MVP development for startups?': 'Você desenvolve MVP para startups?',
    'Do you offer post-launch support and maintenance?': 'Oferece suporte e manutenção pós-lançamento?',
    'E-Commerce': 'E-commerce',
    'Emma Markson': 'Marina Lopes',
    'Error 404': 'Erro 404',
    'Essential tools to launch your startup': 'Ferramentas para lançar sua startup',
    'Expert Teams': 'Time expert',
    'FAQs': 'FAQ',
    'First working process': 'Primeiro fluxo',
    'Flexible pricing plans tailored for': 'Gestão de Mídias Sociais',
    'Free Consulting': 'Consulta grátis',
    'Frequently Asked Questions': 'Consultoria Online Grátis',
    'Get Started': 'Fale no WhatsApp',
    'Get in Touch:': 'Fale Comigo',
    'Gether': 'App',
    'Conheça mais': 'Saiba mais',
    'Happy Customers': 'Clientes fiéis',
    'Have questions about how we work or what to expect? Our FAQs cover everything from project timelines to pricing and collaboration.': 'Tem dúvidas sobre meu método ou expectativas? Resumo cronogramas, investimentos e co-criação para clareza total.',
    'Health Tech': 'Saúde tech',
    'Home': 'Lar',
    'Home 01': 'Tema 01',
    'Home 02': 'Tema 02',
    'Home 03': 'Tema 03',
    'Home 04': 'Tema 04',
    'How do we get started with your agency?': 'Como iniciamos o projeto juntos?',
    'Getting started is simple—just reach out through our contact form or schedule a free consultation. We’ll discuss your goals, challenges, and how we can help. From there, we’ll outline a tailored plan and kick off your project.': 'Começar é simples: fale pelo formulário ou agende uma consultoria gratuita. Conversamos sobre metas, desafios e como posso apoiar. Depois, envio plano sob medida e iniciamos o projeto.',
    'Innovatiove Digital Solutions for Startup agency': 'Soluções digitais estratégicas para startups.',
    'Instagram': 'Instagram',
    'Know About Us': 'Saiba mais',
    'Landing Page for Product Launch': 'Landing page para lançamentos',
    'Launch & Growth': 'Lançar & crescer',
    'Learn different ways to secure funding and build investor confidence.': 'Aprenda caminhos para atrair capital e ganhar confiança de investidores.',
    'Learn something more from': 'Aprofunde aprendizados com',
    'our latest blogs.': ' meu blog atual.',
    'Left Sidebar': 'Barra esquerda',
    'Left sidebar': 'Barra esquerda',
    'Limited keyword analysis': 'Análise limitada de palavras-chave',
    'LinkedIn': 'LinkedIn',
    'Menu': 'Menu',
    'Mistakes to Avoid When Building Your First App': 'Erros a evitar ao criar seu primeiro app',
    'Mobile App for Food Delivery Startup': 'App móvel para delivery',
    'Mon – Fri 8:30 – 17:30': 'Seg – Sex 8:30 – 17:30',
    'Monthly': 'Mensal',
    'More then 5.2k  Clients Reviews': 'Mais de 5,2k avaliações de clientes',
    'Motion Graphics design': 'Motion design avançado',
    'Multi Page': 'Multi página',
    'MultiPage': 'MultiPágina',
    'New Trends In SEO & Analytics': 'Novas tendências em SEO e analytics',
    'New Trends In Social': 'Novas tendências em social',
    'One Page': 'Uma página',
    'OnePage': 'UmaPágina',
    'Our Case Studies and': 'Portfólio de',
    'Our Clients': 'Clientes',
    'Our Shop': 'Minha loja',
    'Our Team': 'Meu time',
    'Our Team 01': 'Time 01',
    'Our Team 02': 'Time 02',
    'Pages': 'Páginas',
    'Popular Questions': 'Perguntas comuns',
    'Premium Plan': 'Plano Premium',
    'Pricing Plan': 'Planos',
    'Project Details': 'Detalhes do projeto',
    'Projects': 'Projetos',
    'Projects 01': 'Projetos 01',
    'Projects 02': 'Projetos 02',
    'Projects 03': 'Projetos 03',
    'Projects 04': 'Projetos 04',
    'Projects Completed': 'Projetos concluídos',
    'Raised More Then': 'Captação superior a',
    'Read More': 'Ver mais',
    'Right Sidebar': 'Barra direita',
    'Right sidebar': 'Barra direita',
    'SEO Optimization for Marketplace': 'SEO para marketplace',
    'SEO, social media marketing, content creation, and paid cam paigns to drive…': 'SEO, gestão social, conteúdo e mídia paga para gerar resultados.',
    'SaaS Platform': 'Plataforma SaaS',
    'Sat – Sun off': 'Sáb – Dom não',
    'Services': 'Serviços',
    'Services 01': 'Serviços 01',
    'Services 02': 'Serviços 02',
    'Services 03': 'Serviços 03',
    'Services 04': 'Serviços 04',
    'Shop': 'Loja',
    'Single Services': 'Serviço único',
    'Standard Plan': 'Plano Standard',
    'Startup': 'Startup',
    'Startup Growth Hacks You Should Try Today': 'Growth hacks de startup para aplicar hoje',
    'Startup Marketing on a Budget: What Works': 'Marketing de startup com orçamento reduzido',
    'Startup Website Essentials: What You Really Need': 'Essenciais para site de startup que geram valor',
    'Startup focused expertise': 'Expertise focada em startups',
    'Startups have raised $120M+': 'Projetos somam $120M+',
    'Streamlined workflow for startup project success.': 'Sites Profissionais & Landing Pages',
    'Subscribe our newsletter': 'Assine nosso boletim',
    'Success Stories': 'Projetos',
    'Team Single': 'Perfil do time',
    'Testimonial': 'Depoimento',
    'The Role of Agile Development  in Startups': 'O papel do ágil nas startups',
    'Twitter': 'Twitter',
    'UI/UX Design': 'Design UI/UX',
    'UI/UX Redesign for SaaS Dashboard': 'Redesign UI/UX para SaaS',
    'Useful Links:': 'Links úteis:',
    'User-centric design and interactive prototypes bring your product idea to life.': 'Design centrado no usuário e protótipos interativos dão vida ao produto.',
    'View All Clients': 'Ver portfólio',
    'View All Projects': 'Ver portfólio',
    'View All Services': 'Pedir orçamento',
    'We begin by understanding your vision, goals, and market to craft a tailored strategy.': 'Começo entendendo visão, objetivos e mercado para construir estratégia sob medida.',
    'We build high-performance mobile apps tailored to your users’ needs and business…': 'Desenvolvo apps de alta performance alinhados às demandas do negócio.',
    'We collaborate with visionary clients partners to drive innovation & achieve startup success. Together, we build bold ideas into impactful ventures.': 'Colaboro com clientes visionários para transformar ideias ousadas em resultados tangíveis.',
    'We collaborate with visionary clients partners to drive innovation achieve.': 'Colaboro com clientes visionários para impulsionar inovação.',
    'We craft unique brand strategies that align with your startup vision, mission,…': 'Crio estratégias de marca alinhadas à visão e missão da startup.',
    'We deploy, optimize, and support  your startup as it grows and scales': 'Implanto, otimizo e acompanho sua marca enquanto escala',
    'We deploy, optimize, and support  your startup as it grows and scales \nin the market.': 'Implanto, otimizo e acompanho sua marca enquanto escala \nno mercado.',
    'in the market.': 'no mercado.',
    'We empower startups through innovative solutions and data-driven growth strategies tailored for long-term success.': 'Impulsiono marcas com soluções inovadoras e crescimento guiado por dados para sucesso duradouro.',
    'Working with this agency transformed our startup journey — from branding to launch, they delivered beyond expecta tions. Their creative insights and strategic approach truly set them apart.': 'Trabalhar com Guell transformou nossa jornada — do branding ao lançamento com visão estratégica e criatividade.',
    'We turn bold ideas into powerful brands. From strategy to launch, we craft digital experiences that captivate and convert. Partner with us to build what’s next.': 'Transformo ideias em marcas poderosas com estratégia, lançamento e experiências digitais que convertem.',
    'Welcome to Coreriver': 'Bem-vindo(a) à Guell',
    'What is your communication process during projects?': 'Como funciona a comunicação durante o projeto?',
    'What our customer says.': 'Depoimentos de Clientes',
    'What platforms and technologies do you use?': 'Quais plataformas e tecnologias você utiliza?',
    'What sets your agency apart from others?': 'O que diferencia seu estúdio?',
    'Why Choose Us': 'Gestão de Mídias Sociais',
    'Why Every Startup Needs a Strong Brand Identity': 'Por que toda startup precisa de identidade forte',
    'William Turner': 'Paulo Vieira',
    'Without Sidebar': 'Sem barra lateral',
    'Work Process': 'Processo de trabalho',
    'Working with this agency transformed our startup journey — from branding to launch, they delivered beyond expectations. Their creative insights and strategic approach truly set them apart.': 'Trabalhar com Guell transformou nossa jornada — branding a lançamento com visão estratégica e criatividade.',
    'Years of work experience': 'Anos de experiência',
    'Gether': 'App',
    'a': 'a',
    'c': 'c',
    'g': 'g',
    'l': 'l',
    'n': 'n',
    'r': 'r',
    'strategy, creative design, & smart': 'estratégia criativa e smart',
    'technology to launch, grow, lead in': 'tecnologia para lançar e escalar',
    'the digital world.': 'o mundo digital.',
    'bold strategy, creative': 'Estratégia criativa e',
    'creative solutions and': 'soluções criativas e',
    'proven growth strategies.': 'estratégias de crescimento comprovadas.',
    'growing startups.': 'startups em expansão.',
    'Startups have raised $120M+': 'Projetos somam $120M+',
    'Gether': 'App',
}

# Replace text nodes
for string in soup.find_all(string=True):
    if not isinstance(string, NavigableString):
        continue
    stripped = string.strip()
    if not stripped:
        continue
    if stripped in replacements:
        new_text = replacements[stripped]
        text = str(string)
        leading = text[:len(text) - len(text.lstrip())]
        trailing = text[len(text.rstrip()):]
        string.replace_with(leading + new_text + trailing)

# Replace data-text attributes
for tag in soup.find_all(True):
    for attr in ['data-text', 'data-hover']:
        val = tag.get(attr)
        if val and val in replacements:
            tag[attr] = replacements[val]

# Fine-tune split headings to ensure required texts
for div in soup.find_all('div', string=lambda s: s and 'Gestão de Mídias Sociais' in s):
    sibling = div.find_next_sibling('div')
    if sibling and sibling.name == 'div' and 'split-line' in sibling.get('class', []):
        sibling.string = ''

for div in soup.find_all('div', string=lambda s: s and 'soluções criativas e' in s):
    prev = div.find_previous_sibling('div')
    if prev and 'split-line' in prev.get('class', []):
        prev.string = 'Edição de Vídeos & '
    div.string = 'Motion Design'
    nxt = div.find_next_sibling('div')
    if nxt and 'split-line' in nxt.get('class', []):
        nxt.string = ''

for div in soup.find_all('div', string=lambda s: s and 'Portfólio de' in s):
    div.string = 'Portfólio de '
    nxt = div.find_next_sibling('div')
    if nxt and 'split-line' in nxt.get('class', []):
        nxt.string = 'Projetos'

for div in soup.find_all('div', string=lambda s: s and 'Identidade Visual &' in s):
    div.string = 'Identidade Visual & '
    nxt = div.find_next_sibling('div')
    if nxt and 'split-line' in nxt.get('class', []):
        nxt.string = 'Logotipos'

hero_lines = [
    'Impulsiono startups com visão',
    'Estratégia criativa e tecnologia',
    'para lançar, crescer e escalar',
    'no cenário digital.'
]
for span in soup.find_all('span', class_='pxl-heading--text'):
    combined = ''.join(span.stripped_strings)
    if 'Empowering startups with bold' in combined:
        split_divs = span.find_all('div', class_='split-line')
        for idx, text in enumerate(hero_lines):
            if idx < len(split_divs):
                split_divs[idx].string = text
        for extra in split_divs[len(hero_lines):]:
            extra.string = ''

for btn in soup.select('a[data-target=".pxl-page-popup-template-0"]'):
    btn['href'] = '#'
    if btn.get('data-hover'):
        btn['data-hover'] = 'Saiba mais'
    text_span = btn.find('span', class_='pxl--btn-text')
    if text_span:
        text_span['data-text'] = 'Saiba mais'
        text_span.string = 'Saiba mais'

for span in soup.find_all('span', class_='pxl--btn-text'):
    if span.get_text(strip=True) == 'Conheça mais':
        span['data-text'] = 'Saiba mais'
        span.string = 'Saiba mais'
        parent_link = span.find_parent('a')
        if parent_link:
            parent_link['href'] = '#'
            parent_link['data-hover'] = 'Saiba mais'

for a in soup.find_all('a', href='https://agenciadigitalsaopaulo.com.br/guell/about-us/'):
    a['href'] = '#'
    a['data-hover'] = 'Saiba mais'
    text_span = a.find('span', class_='pxl--btn-text')
    if text_span:
        text_span['data-text'] = 'Saiba mais'
        text_span.string = 'Saiba mais'

# Update image sources
for img in soup.find_all('img'):
    src = img.get('src')
    if not src:
        continue
    filename = src.split('/')[-1]
    if filename in img_map:
        img['src'] = img_map[filename]
    # srcset update
    srcset = img.get('srcset')
    if srcset:
        parts = []
        for part in srcset.split(','):
            url = part.strip().split(' ')[0]
            name = url.split('/')[-1]
            mapped = img_map.get(name)
            if mapped:
                pieces = part.strip().split(' ')
                pieces[0] = mapped
                parts.append(' '.join(pieces))
            else:
                parts.append(part.strip())
        img['srcset'] = ', '.join(parts)

# Update WhatsApp and CTA links
for a in soup.find_all('a'):
    href = a.get('href', '')
    if 'wa.me' in href or 'whatsapp' in href.lower():
        a['href'] = 'https://wa.me/5511985830211?text=Ol%C3%A1%2C+vim+do+site+e+quero+saber+mais'
    if a.get_text(strip=True) == 'Ver portfólio':
        a['href'] = '#portfolio'
    if a.get_text(strip=True) == 'Pedir orçamento':
        a['href'] = '#contato'
    if a.get_text(strip=True) == 'Saiba mais':
        a['href'] = '#'
    if a.get_text(strip=True) == 'Fale no WhatsApp':
        a['href'] = 'https://wa.me/5511985830211?text=Ol%C3%A1%2C+vim+do+site+e+quero+saber+mais'
    if href.startswith('mailto:'):
        a['href'] = 'mailto:contato@guell.com'
    if href.startswith('tel:'):
        a['href'] = 'tel:+5511985830211'

# Update phone numbers
for tag in soup.find_all(string='+44 20 8980 9731'):
    tag.replace_with('+55 11 98583-0211')

# Update email
for tag in soup.find_all(string='info@coreriver.co.uk'):
    tag.replace_with('contato@guell.com')

# Update address
for tag in soup.find_all(string='31 St Martin’s Ln, London WC2N 4DD, United Kingdom'):
    tag.replace_with('Av. Paulista, 1106, Bela Vista, São Paulo, SP')

# Update office hours weekend
for tag in soup.find_all(string='Sat – Sun off'):
    tag.replace_with('Sáb – Dom não')

# Update metadata
html_tag = soup.find('html')
if html_tag:
    html_tag['lang'] = 'pt-BR'

head = soup.head
if head:
    # Title
    title_tag = head.find('title')
    if title_tag:
        title_tag.string = 'Guell Almeida — Designer & Social Media | Identidade Visual, Redes Sociais, Sites e Vídeos'
    # Remove existing meta description
    for meta in head.find_all('meta', attrs={'name': 'description'}):
        meta.decompose()
    # Add meta description
    desc_tag = soup.new_tag('meta', attrs={'name': 'description', 'content': 'Designer e social media em São Paulo criando identidades, conteúdo, sites e vídeos estratégicos que conectam marcas e resultados.'})
    head.append(desc_tag)
    # Canonical
    canon = head.find('link', rel='canonical')
    if canon:
        canon['href'] = 'https://agenciadigitalsaopaulo.com.br/guell/'
    else:
        canon_tag = soup.new_tag('link', rel='canonical', href='https://agenciadigitalsaopaulo.com.br/guell/')
        head.append(canon_tag)
    # Open Graph
    og_tags = {
        'og:type': 'website',
        'og:title': 'Guell Almeida — Designer & Social Media | Identidade Visual, Redes Sociais, Sites e Vídeos',
        'og:description': 'Designer e social media em São Paulo criando identidades, conteúdo, sites e vídeos estratégicos que conectam marcas e resultados.',
        'og:url': 'https://agenciadigitalsaopaulo.com.br/guell/',
        'og:image': 'https://agenciadigitalsaopaulo.com.br/guell/wp-content/uploads/2025/10/home-1.webp'
    }
    for prop, val in og_tags.items():
        meta = head.find('meta', property=prop)
        if meta:
            meta['content'] = val
        else:
            head.append(soup.new_tag('meta', property=prop, content=val))
    # Twitter
    twitter_tags = {
        'twitter:card': 'summary_large_image',
        'twitter:title': 'Guell Almeida — Designer & Social Media | Identidade Visual, Redes Sociais, Sites e Vídeos',
        'twitter:description': 'Designer e social media em São Paulo criando identidades, conteúdo, sites e vídeos estratégicos que conectam marcas e resultados.',
        'twitter:image': 'https://agenciadigitalsaopaulo.com.br/guell/wp-content/uploads/2025/10/home-1.webp'
    }
    for name, val in twitter_tags.items():
        meta = head.find('meta', attrs={'name': name})
        if meta:
            meta['content'] = val
        else:
            head.append(soup.new_tag('meta', attrs={'name': name, 'content': val}))
    # JSON-LD Person
    for script in head.find_all('script', type='application/ld+json'):
        script.decompose()
    person = {
        '@context': 'https://schema.org',
        '@type': 'Person',
        'name': 'Guell Almeida',
        'jobTitle': 'Designer e Social Media',
        'url': 'https://agenciadigitalsaopaulo.com.br/guell/',
        'image': 'https://agenciadigitalsaopaulo.com.br/guell/wp-content/uploads/2025/10/home-1.webp',
        'sameAs': [
            'https://www.instagram.com/',
            'https://www.linkedin.com/'
        ],
        'telephone': '+55 11 98583-0211',
        'address': {
            '@type': 'PostalAddress',
            'addressLocality': 'São Paulo',
            'addressRegion': 'SP',
            'addressCountry': 'BR'
        }
    }
    script_tag = soup.new_tag('script', type='application/ld+json')
    script_tag.string = json.dumps(person, ensure_ascii=False)
    head.append(script_tag)

asset_segment = 'Guell Almeida – Agência de Marketing Digital em São Paulo – SP_files'
for link in soup.find_all('link'):
    href = link.get('href')
    if href and asset_segment in href:
        parts = href.split('/')
        parts[-1] = normalize_asset_filename(parts[-1])
        link['href'] = '/'.join(parts)

for script in soup.find_all('script'):
    src = script.get('src')
    if src and asset_segment in src:
        parts = src.split('/')
        parts[-1] = normalize_asset_filename(parts[-1])
        script['src'] = '/'.join(parts)

output_path = Path('site_tema_preenchido_guell.html')
output_html = str(soup).replace('Conheça mais', 'Saiba mais')
output_path.write_text(output_html, encoding='utf-8')
