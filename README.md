# Landing Page da Dra. Greice Kronbauer no Elementor Pro

Este repositório contém um modelo de página para ser usado no [Elementor Pro](https://elementor.com/). O arquivo `elementor-template.json` traz a estrutura das seções e widgets e o arquivo `styles.css` contém o CSS responsável pelo visual responsivo.

## Como usar
1. No painel do WordPress, acesse **Modelos → Importar Modelo** e faça o upload do arquivo `elementor-template.json`.
2. Crie ou edite uma página com o Elementor e, dentro do editor, escolha **Modelos → Meus Modelos** e insira o template "Dra. Greice Landing Page".
3. Vá em **Site Settings → Custom CSS** (ou **Aparência → Personalizar → CSS adicional**) e cole o conteúdo de `styles.css`.
4. Publique a página. As classes necessárias já estão definidas no template, permitindo que o CSS seja aplicado automaticamente.

### Personalizações
- Substitua o logotipo no **Image Widget** do banner hero utilizando a URL `https://greicekronbauer.com.br/wp-content/uploads/2018/09/logo-site.png` ou outra de sua preferência.
- Troque textos dos widgets de acordo com sua necessidade (sobre, especialidades, depoimentos, etc.).
- Nas áreas de "Depoimentos" e "Antes e Depois", substitua o texto de placeholder por widgets de imagem ou carrossel com as imagens reais.
- Para alterar as cores ou ajustar as curvas entre as seções, edite as variáveis no início de `styles.css`.
- O layout usa a fonte **Poppins** com pesos mais espessos e botões arredondados para um visual mais arrojado.

## Estrutura de arquivos
- `elementor-template.json` – modelo de página para importar no Elementor.
- `styles.css` – CSS responsivo a ser colado em Custom CSS do Elementor ou do tema.

## Requisitos
- WordPress com plugin Elementor Pro instalado.

## Observações
Este modelo é um ponto de partida. Utilize os recursos do Elementor Pro para aprimorar animações, responsividade e demais ajustes conforme desejar.
