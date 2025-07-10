const button = document.getElementById('trigger');
const statusDiv = document.getElementById('status');
const imagesDiv = document.getElementById('images');

button.addEventListener('click', async () => {
    statusDiv.textContent = 'Gerando...';
    imagesDiv.innerHTML = '';
    try {
        const response = await fetch('https://webhook.domingoscreativetest.tech/webhook/gerador', {
            method: 'POST'
        });
        if (!response.ok) throw new Error('Erro ao disparar o webhook');
        const data = await response.json();
        // espera que o webhook retorne { images: [url1, url2] }
        if (Array.isArray(data.images)) {
            data.images.forEach(src => {
                const img = document.createElement('img');
                img.src = src;
                imagesDiv.appendChild(img);
            });
            statusDiv.textContent = 'Concluído';
        } else {
            statusDiv.textContent = 'Resposta inesperada';
        }
    } catch (err) {
        statusDiv.textContent = 'Erro: ' + err.message;
    }
});
