(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.jpChatData === 'undefined') {
      return;
    }

    const chatBox = document.getElementById('jp-chat-messages');
    const input = document.getElementById('jp-chat-text');
    const sendButton = document.getElementById('jp-chat-send-btn');
    const uploadBtn = document.getElementById('jp-chat-upload-btn');
    const previewBox = document.getElementById('jp-chat-preview');

    const groupId = parseInt(window.jpChatData.group_id, 10) || 0;
    const ajaxUrl = window.jpChatData.ajax_url;
    const nonce = window.jpChatData.nonce;

    let isLoading = false;
    let uploading = false;

    function renderMessages(messages) {
      if (!chatBox) return;
      chatBox.innerHTML = '';

      if (!Array.isArray(messages) || messages.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'jp-chat-empty';
        empty.textContent = 'Nenhuma mensagem ainda.';
        chatBox.appendChild(empty);
        return;
      }

      messages.forEach(function (msg) {
        const bubble = document.createElement('div');
        const sender = msg && msg.sender === 'admin' ? 'admin' : 'user';
        bubble.className = 'jp-chat-bubble ' + (sender === 'admin' ? 'jp-chat-bubble-admin' : 'jp-chat-bubble-user');

        if (msg && msg.message) {
          const textNode = document.createElement('div');
          textNode.textContent = msg.message;
          bubble.appendChild(textNode);
        }

        if (msg && msg.image_url) {
          const img = document.createElement('img');
          img.src = msg.image_url;
          img.alt = 'Imagem da conversa';
          img.className = 'jp-chat-image';
          bubble.appendChild(img);
        }

        chatBox.appendChild(bubble);
      });

      chatBox.scrollTop = chatBox.scrollHeight;
    }

    function showPreview(text) {
      if (!previewBox) return;
      previewBox.textContent = text;
    }

    async function loadMessages() {
      if (isLoading || !groupId) return;
      isLoading = true;
      try {
        const res = await fetch(
          ajaxUrl + '?action=juntaplay_chat_list&group_id=' + encodeURIComponent(groupId) + '&nonce=' + encodeURIComponent(nonce),
          { credentials: 'same-origin' }
        );
        const data = await res.json();
        if (data && data.success) {
          renderMessages(data.messages || []);
        }
      } catch (e) {
        // silencioso
      }
      isLoading = false;
    }

    async function sendMessage(payload) {
      if (!groupId) return;
      const form = new FormData();
      form.append('action', 'juntaplay_chat_send');
      form.append('nonce', nonce);
      form.append('group_id', groupId);

      if (payload.message) {
        form.append('message', payload.message);
      }
      if (payload.image_url) {
        form.append('image_url', payload.image_url);
      }

      try {
        const res = await fetch(ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          body: form,
        });
        const data = await res.json();
        if (data && data.success) {
          if (input) {
            input.value = '';
          }
          showPreview('Pré-visualização da imagem (aguardando upload)');
          await loadMessages();
        }
      } catch (e) {
        // silencioso
      }
    }

    async function handleUpload() {
      if (uploading || !groupId) return;
      const fileInput = document.createElement('input');
      fileInput.type = 'file';
      fileInput.accept = 'image/png,image/jpeg,image/webp';

      fileInput.addEventListener('change', async function () {
        if (!fileInput.files || !fileInput.files.length) return;
        const file = fileInput.files[0];

        const form = new FormData();
        form.append('action', 'juntaplay_chat_upload');
        form.append('nonce', nonce);
        form.append('file', file);

        uploading = true;
        showPreview('Enviando imagem...');
        try {
          const res = await fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: form,
          });
          const data = await res.json();
          if (data && data.success && data.url) {
            await sendMessage({ image_url: data.url });
          } else {
            showPreview('Falha ao enviar imagem.');
          }
        } catch (e) {
          showPreview('Falha ao enviar imagem.');
        }
        uploading = false;
      });

      fileInput.click();
    }

    function bindEvents() {
      if (sendButton) {
        sendButton.addEventListener('click', function (event) {
          event.preventDefault();
          if (input && input.value.trim()) {
            sendMessage({ message: input.value.trim() });
          }
        });
      }

      if (input) {
        input.addEventListener('keypress', function (event) {
          if (event.key === 'Enter') {
            event.preventDefault();
            if (input.value.trim()) {
              sendMessage({ message: input.value.trim() });
            }
          }
        });
      }

      if (uploadBtn) {
        uploadBtn.addEventListener('click', function (event) {
          event.preventDefault();
          handleUpload();
        });
      }
    }

    bindEvents();
    loadMessages();
    setInterval(loadMessages, 5000);
  });
})();
