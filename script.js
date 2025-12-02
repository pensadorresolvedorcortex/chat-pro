const brandLogos = [
  { name: 'Chevrolet', logo: 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/chevrolet.svg' },
  { name: 'Volkswagen', logo: 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/volkswagen.svg' },
  { name: 'Honda', logo: 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/honda.svg' },
  { name: 'Hyundai', logo: 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/hyundai.svg' },
  { name: 'Fiat', logo: 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/fiat.svg' },
  { name: 'Jeep', logo: 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/jeep.svg' },
  { name: 'Toyota', logo: 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/toyota.svg' },
  { name: 'Renault', logo: 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/renault.svg' },
  { name: 'Nissan', logo: 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/nissan.svg' },
  { name: 'Ford', logo: 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/ford.svg' },
  { name: 'Peugeot', logo: 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/peugeot.svg' },
  { name: 'Citroën', logo: 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/citroen.svg' },
  { name: 'BMW', logo: 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/bmw.svg' },
  { name: 'Kia', logo: 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/kia.svg' },
  { name: 'Motos', logo: 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/transportforlondon.svg' },
];

const vehicles = [
  { brand: 'Volkswagen', model: 'GOL - todos os modelos', years: '2000 a 2025' },
  { brand: 'Volkswagen', model: 'POLO - todos os modelos', years: '2003 a 2025' },
  { brand: 'Volkswagen', model: 'FOX - todos os modelos', years: '2003 a 2014' },
  { brand: 'Volkswagen', model: 'T-CROSS', years: '2019 a 2025' },
  { brand: 'Volkswagen', model: 'VOYAGE', years: '2000 a 2023' },
  { brand: 'Volkswagen', model: 'NIVUS', years: '2020 a 2025' },
  { brand: 'Chevrolet', model: 'ONIX - manual e automático', years: '2012 a 2025' },
  { brand: 'Chevrolet', model: 'PRISMA / ONIX PLUS', years: '2013 a 2025' },
  { brand: 'Chevrolet', model: 'CELTA - todos os modelos', years: '2001 a 2015' },
  { brand: 'Chevrolet', model: 'CRUZE', years: '2012 a 2025' },
  { brand: 'Chevrolet', model: 'TRACKER', years: '2013 a 2025' },
  { brand: 'Fiat', model: 'STRADA', years: '2000 a 2025' },
  { brand: 'Fiat', model: 'ARGO', years: '2017 a 2025' },
  { brand: 'Fiat', model: 'TORO', years: '2016 a 2025' },
  { brand: 'Fiat', model: 'UNO', years: '2000 a 2021' },
  { brand: 'Fiat', model: 'CRONOS', years: '2018 a 2025' },
  { brand: 'Fiat', model: 'PULSE', years: '2021 a 2025' },
  { brand: 'Hyundai', model: 'HB20', years: '2012 a 2025' },
  { brand: 'Hyundai', model: 'CRETA', years: '2017 a 2025' },
  { brand: 'Hyundai', model: 'HB20S', years: '2013 a 2025' },
  { brand: 'Hyundai', model: 'TUCSON / IX35', years: '2005 a 2025' },
  { brand: 'Renault', model: 'KWID', years: '2017 a 2025' },
  { brand: 'Renault', model: 'SANDERO', years: '2007 a 2025' },
  { brand: 'Ford', model: 'KA - todos os modelos', years: '2008 a 2021' },
  { brand: 'Ford', model: 'FIESTA', years: '2003 a 2014' },
  { brand: 'Ford', model: 'ECOSPORT', years: '2003 a 2021' },
  { brand: 'Jeep', model: 'RENEGADE', years: '2015 a 2025' },
  { brand: 'Jeep', model: 'COMPASS', years: '2016 a 2025' },
  { brand: 'Jeep', model: 'COMMANDER', years: '2022 a 2025' },
  { brand: 'Honda', model: 'CIVIC', years: '2000 a 2025' },
  { brand: 'Honda', model: 'FIT', years: '2003 a 2021' },
  { brand: 'Honda', model: 'HR-V', years: '2015 a 2025' },
  { brand: 'Toyota', model: 'COROLLA', years: '2000 a 2025' },
  { brand: 'Toyota', model: 'HILUX', years: '2001 a 2025' },
  { brand: 'Toyota', model: 'YARIS', years: '2018 a 2025' },
  { brand: 'Toyota', model: 'COROLLA CROSS', years: '2021 a 2025' },
  { brand: 'Nissan', model: 'KICKS', years: '2017 a 2025' },
  { brand: 'Nissan', model: 'FRONTIER', years: '2002 a 2025' },
  { brand: 'Peugeot', model: '208', years: '2013 a 2025' },
  { brand: 'Peugeot', model: '2008', years: '2015 a 2025' },
  { brand: 'Citroën', model: 'C3', years: '2003 a 2025' },
  { brand: 'Citroën', model: 'C4 CACTUS', years: '2018 a 2025' },
  { brand: 'BMW', model: 'X1', years: '2009 a 2025' },
  { brand: 'Kia', model: 'SPORTAGE', years: '2000 a 2025' },
  { brand: 'Kia', model: 'SORENTO', years: '2000 a 2025' },
  { brand: 'Motos', model: 'Bateria para motocicletas', years: 'Street, Trail e Scooter' },
];

const faqs = [
  {
    question: 'Quanto tempo leva para entregar e instalar?',
    answer: 'Nossa meta é chegar em até 50 minutos nas rotas prioritárias. Avisamos pelo WhatsApp quando estamos a caminho.',
  },
  {
    question: 'Quais formas de pagamento aceitam?',
    answer: 'Pagamento só na entrega: cartão, PIX ou dinheiro. Sem boletos, sem pré-pagamento.',
  },
  {
    question: 'A instalação e o teste são cobrados?',
    answer: 'Não. Testamos o sistema elétrico, instalamos a Tudor TFS e só cobramos a bateria na entrega.',
  },
  {
    question: 'E se meu CEP não for encontrado?',
    answer: 'Você pode preencher manualmente pelo botão “Preencher manualmente” ou revisar com “Reconsultar CEP”.',
  },
  {
    question: 'Posso alterar o veículo depois?',
    answer: 'Sim. Reabra a etapa de veículos ou use “Trocar veículo” no resumo para atualizar antes de enviar ao WhatsApp.',
  },
];

const popupOverlay = document.getElementById('popupOverlay');
const openPopupButtons = document.querySelectorAll('[data-open-popup]');
const closePopupButton = document.getElementById('closePopup');
const stepTabs = document.querySelectorAll('.step-tab');
const stepContents = document.querySelectorAll('.step-content');
const vehicleTab = document.getElementById('vehicleTab');
const addressForm = document.getElementById('addressForm');
const cepInput = document.getElementById('cep');
const cepStatus = document.getElementById('cepStatus');
const vehicleListEl = document.getElementById('vehicleList');
const vehicleFilter = document.getElementById('vehicleFilter');
const vehicleCount = document.getElementById('vehicleCount');
const clearFilterBtn = document.getElementById('clearFilter');
const brandChips = document.getElementById('brandChips');
const popularList = document.getElementById('popularList');
const addressPreview = document.getElementById('addressPreview');
const vehiclePreview = document.getElementById('vehiclePreview');
const waNameInput = document.getElementById('waName');
const notesInput = document.getElementById('notes');
const ctaWhatsApp = document.getElementById('ctaWhatsApp');
const ctaStateHint = document.getElementById('ctaStateHint');
const liveStatus = document.getElementById('liveStatus');
const messagePreview = document.getElementById('messagePreview');
const copySummaryBtn = document.getElementById('copySummary');
const scrollTopBtn = document.getElementById('scrollToTop');
const resetDataBtn = document.getElementById('resetData');
const addressStatusChip = document.getElementById('addressStatusChip');
const vehicleStatusChip = document.getElementById('vehicleStatusChip');
const progressBar = document.getElementById('progressBar');
const formAlert = document.getElementById('formAlert');
const addressChecklist = document.getElementById('addressChecklist');
const vehicleChecklist = document.getElementById('vehicleChecklist');
const scrollSummary = document.querySelector('[data-scroll-summary]');
const toast = document.getElementById('toast');
const readinessValue = document.getElementById('readinessValue');
const readinessStatus = document.getElementById('readinessStatus');
const readinessBar = document.getElementById('readinessBar');
const stickyBar = document.getElementById('stickyBar');
const stickyStatus = document.getElementById('stickyStatus');
const stickyAction = document.getElementById('stickyAction');
const stickySub = document.getElementById('stickySub');
const stickyPercent = document.getElementById('stickyPercent');
const primaryTriggers = document.querySelectorAll('[data-primary-trigger]');
const manualAddressBtn = document.getElementById('manualAddress');
const retryCepBtn = document.getElementById('retryCep');
const inlineReadinessValue = document.getElementById('inlineReadinessValue');
const inlineReadinessText = document.getElementById('inlineReadinessText');
const popupReadinessValue = document.getElementById('popupReadinessValue');
const faqList = document.getElementById('faqList');

let activeBrand = null;
let lastTrigger = null;
let focusTrapHandler = null;

const addressFields = {
  cep: cepInput,
  street: document.getElementById('street'),
  district: document.getElementById('district'),
  city: document.getElementById('city'),
  state: document.getElementById('state'),
  number: document.getElementById('number'),
  complement: document.getElementById('complement'),
};

const fieldErrors = {
  cep: document.querySelector('[data-error-for="cep"]'),
  street: document.querySelector('[data-error-for="street"]'),
  district: document.querySelector('[data-error-for="district"]'),
  city: document.querySelector('[data-error-for="city"]'),
  state: document.querySelector('[data-error-for="state"]'),
  number: document.querySelector('[data-error-for="number"]'),
};

const storage = {
  set(key, value) { sessionStorage.setItem(key, JSON.stringify(value)); },
  get(key) { const raw = sessionStorage.getItem(key); return raw ? JSON.parse(raw) : null; },
};

function showToast(message, type = 'info') {
  if (!toast) return;
  toast.textContent = message;
  toast.className = `toast show ${type}`;
  setTimeout(() => {
    toast.classList.remove('show');
  }, 2600);
}

function renderBrands() {
  const brandList = document.getElementById('brandList');
  brandList.innerHTML = brandLogos.map((item) => `
    <div class="brand-item" title="${item.name}">
      <img src="${item.logo}" alt="${item.name}">
    </div>
  `).join('');
}

function renderBrandChips() {
  if (!brandChips) return;
  const uniqueBrands = brandLogos.map((item) => item.name);
  brandChips.innerHTML = ['Todos', ...uniqueBrands]
    .map((brand) => {
      const isActive = (activeBrand === null && brand === 'Todos') || brand === activeBrand;
      return `<button type="button" class="chip brand-chip ${isActive ? 'active' : ''}" data-brand="${brand === 'Todos' ? '' : brand}">${brand}</button>`;
    })
    .join('');
}

function renderFaqs() {
  if (!faqList) return;
  faqList.innerHTML = faqs
    .map((item, index) => `
      <button class="faq-item" aria-expanded="${index === 0 ? 'true' : 'false'}" aria-controls="faq-panel-${index}" data-faq-index="${index}">
        <div class="faq-question">${item.question}</div>
        <div class="faq-icon" aria-hidden="true">${index === 0 ? '−' : '+'}</div>
        <div class="faq-answer" id="faq-panel-${index}" ${index === 0 ? '' : 'hidden'}>
          <p>${item.answer}</p>
        </div>
      </button>
    `)
    .join('');
}

function renderPopularVehicles() {
  if (!popularList) return;
  const suggestions = ['ONIX - manual e automático', 'GOL - todos os modelos', 'HB20', 'RENEGADE', 'COMPASS', 'Bateria para motocicletas'];
  popularList.innerHTML = suggestions
    .map((name) => {
      const vehicle = vehicles.find((item) => item.model === name);
      if (!vehicle) return '';
      const label = `${vehicle.brand} — ${vehicle.model}`;
      return `<button type="button" class="chip popular-chip" data-brand="${vehicle.brand}" data-model="${vehicle.model}">${label}</button>`;
    })
    .join('');
}

function renderVehicles(filterText = '') {
  const normalized = filterText.toLowerCase();
  const selected = storage.get('vehicle');
  const filtered = vehicles.filter(({ brand, model }) => {
    const matchesText = `${brand} ${model}`.toLowerCase().includes(normalized);
    const matchesBrand = !activeBrand || brand.toLowerCase() === activeBrand.toLowerCase();
    return matchesText && matchesBrand;
  });

  if (vehicleCount) {
    if (!filtered.length) {
      vehicleCount.textContent = activeBrand
        ? 'Nenhum modelo encontrado para esta montadora. Ajuste o termo ou limpe os filtros.'
        : 'Nenhum modelo encontrado. Tente outro termo ou limpe o filtro.';
    } else if (filterText) {
      vehicleCount.textContent = `${filtered.length} ${filtered.length === 1 ? 'modelo' : 'modelos'} encontrados`;
    } else if (activeBrand) {
      vehicleCount.textContent = `${filtered.length} modelos de ${activeBrand}`;
    } else {
      vehicleCount.textContent = 'Exibindo todos os modelos 2000 a 2025';
    }
  }

  if (!filtered.length) {
    vehicleListEl.innerHTML = '<div class="vehicle-empty">Nenhum veículo corresponde à busca.</div>';
    return;
  }

  vehicleListEl.innerHTML = filtered
    .map((item) => {
      const brandLogo = brandLogos.find((b) => b.name.toLowerCase() === item.brand.toLowerCase());
      const logoSrc = brandLogo ? brandLogo.logo : 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/car.svg';
      const isSelected = selected && selected.brand === item.brand && selected.model === item.model;
      return `
        <div class="vehicle-card ${isSelected ? 'selected' : ''}" data-brand="${item.brand}" data-model="${item.model}" data-years="${item.years}">
          <img src="${logoSrc}" alt="${item.brand}">
          <div>
            <div class="vehicle-name">${item.brand} — ${item.model}</div>
            <div class="vehicle-years">${item.years}</div>
          </div>
        </div>
      `;
    }).join('');
}

function setStep(step) {
  stepTabs.forEach((tab) => tab.classList.toggle('active', tab.dataset.step === String(step)));
  stepContents.forEach((content) => content.classList.toggle('active', content.dataset.stepContent === String(step)));
  if (step === 1) {
    cepInput.focus();
  } else if (step === 2 && vehicleFilter) {
    vehicleFilter.focus();
  }
}

function openPopup(targetStep) {
  lastTrigger = document.activeElement;
  popupOverlay.classList.add('active');
  const hasAddress = isAddressComplete();
  vehicleTab.disabled = !hasAddress;
  const stepToOpen = targetStep === 2 && !hasAddress ? 1 : targetStep || (hasAddress ? 2 : 1);
  if (targetStep === 2 && !hasAddress) {
    announce('Complete o endereço para escolher o veículo.');
    showToast('Finalize o endereço para liberar a etapa de veículos.', 'warning');
  }
  if (formAlert) formAlert.classList.remove('show');
  setStep(stepToOpen);
  trapFocus();
}

function closePopup() {
  popupOverlay.classList.remove('active');
  if (formAlert) formAlert.classList.remove('show');
  if (lastTrigger) {
    lastTrigger.focus({ preventScroll: true });
    lastTrigger = null;
  }
}

function trapFocus() {
  const focusables = popupOverlay.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
  const focusList = Array.from(focusables).filter((el) => !el.hasAttribute('disabled') && el.offsetParent !== null);
  const first = focusList[0];
  const last = focusList[focusList.length - 1];
  if (!first || !last) return;

  if (focusTrapHandler) {
    popupOverlay.removeEventListener('keydown', focusTrapHandler);
  }

  focusTrapHandler = (event) => {
    if (event.key !== 'Tab') return;
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  };

  popupOverlay.addEventListener('keydown', focusTrapHandler);
}

function maskCep(value) {
  const digits = value.replace(/\D/g, '').slice(0, 8);
  if (digits.length > 5) {
    return `${digits.slice(0, 5)}-${digits.slice(5)}`;
  }
  return digits;
}

async function fetchCep(cep) {
  const cleanCep = cep.replace(/\D/g, '');
  if (cleanCep.length !== 8) return;
  cepStatus.textContent = 'Buscando...';
  cepStatus.classList.add('loading');
  try {
    const response = await fetch(`https://viacep.com.br/ws/${cleanCep}/json/`);
    if (!response.ok) throw new Error('CEP não encontrado');
    const data = await response.json();
    if (data.erro) throw new Error('CEP inválido');
    addressFields.street.value = data.logradouro || '';
    addressFields.district.value = data.bairro || '';
    addressFields.city.value = data.localidade || '';
    addressFields.state.value = data.uf || '';
    Object.values(addressFields).forEach((input) => input.classList.remove('manual-edit'));
    cepStatus.textContent = 'Endereço encontrado';
    cepStatus.classList.remove('loading');
    if (formAlert) {
      formAlert.textContent = 'Endereço encontrado! Confirme o número e complemento.';
      formAlert.classList.add('show');
      formAlert.style.background = 'rgba(0, 230, 118, 0.12)';
      formAlert.style.color = '#0b5132';
    }
    showToast('Endereço encontrado. Confirme número e complemento.', 'success');
    addressFields.number.focus();
    announce('Endereço encontrado com ViaCEP. Confira os dados.');
  } catch (error) {
    cepStatus.textContent = 'CEP inválido';
    cepStatus.classList.remove('loading');
    addressFields.street.value = '';
    addressFields.district.value = '';
    addressFields.city.value = '';
    addressFields.state.value = '';
    if (formAlert) {
      formAlert.textContent = 'Não foi possível localizar o CEP. Preencha manualmente.';
      formAlert.classList.add('show');
      formAlert.style.background = 'rgba(255, 107, 107, 0.12)';
      formAlert.style.color = '#b42318';
    }
    showToast('Não encontramos o CEP. Preencha manualmente.', 'error');
    announce('Não foi possível localizar o CEP. Preencha manualmente.');
  }
}

function persistAddress() {
  const payload = Object.fromEntries(Object.entries(addressFields).map(([key, input]) => [key, input.value.trim()]));
  storage.set('address', payload);
  updatePreviews();
  toggleCtaState();
  if (formAlert && isAddressComplete()) {
    formAlert.textContent = 'Endereço salvo. Continue para escolher o veículo.';
    formAlert.classList.add('show');
    formAlert.style.background = 'rgba(0, 230, 118, 0.12)';
    formAlert.style.color = '#0b5132';
  }
  if (isAddressComplete()) {
    showToast('Endereço salvo. Agora escolha o veículo.', 'success');
  }
}

function persistVehicle(vehicle) {
  storage.set('vehicle', vehicle);
  updatePreviews();
  toggleCtaState();
  if (vehicle?.model) {
    showToast(`Veículo salvo: ${vehicle.brand} ${vehicle.model}`, 'success');
  }
}

function updatePreviews() {
  const address = storage.get('address');
  const vehicle = storage.get('vehicle');
  if (address && address.cep) {
    const complement = address.complement ? ` - ${address.complement}` : '';
    const line = `${address.cep} — ${address.street || 'Rua'}, ${address.number || 's/n'}${complement} — ${address.district || ''}, ${address.city || ''}/${address.state || ''}`;
    addressPreview.textContent = line;
  } else {
    addressPreview.textContent = 'Selecione o endereço para ver aqui.';
  }
  if (vehicle && vehicle.model) {
    vehiclePreview.textContent = `${vehicle.brand} — ${vehicle.model} (${vehicle.years})`;
  } else {
    vehiclePreview.textContent = 'Escolha o veículo para recomendarmos.';
  }
  updateMessagePreview();
}

function clearFieldErrors() {
  Object.values(fieldErrors).forEach((el) => {
    if (!el) return;
    el.textContent = '';
    el.classList.remove('show');
  });
}

function setFieldError(key, message) {
  const el = fieldErrors[key];
  if (!el) return;
  el.textContent = message;
  el.classList.add('show');
}

function validateAddressForm() {
  let valid = true;
  let firstInvalid = null;
  clearFieldErrors();
  const labels = {
    cep: 'o CEP',
    street: 'a rua',
    district: 'o bairro',
    city: 'a cidade',
    state: 'o estado',
    number: 'o número',
  };

  Object.entries(addressFields).forEach(([key, input]) => {
    const shouldValidate = input.hasAttribute('required');
    const value = input.value.trim();
    let errorMessage = '';

    if (shouldValidate && !value) {
      errorMessage = `Informe ${labels[key]}.`;
    }
    if (key === 'cep' && value && value.replace(/\D/g, '').length !== 8) {
      errorMessage = 'Digite um CEP completo (8 dígitos).';
    }
    if (key === 'state' && value && value.length !== 2) {
      errorMessage = 'Use a sigla do estado (ex: MG).';
      input.value = value.slice(0, 2).toUpperCase();
    }

    const invalid = Boolean(errorMessage);
    input.classList.toggle('is-invalid', invalid);
    input.setAttribute('aria-invalid', invalid ? 'true' : 'false');
    if (invalid) {
      valid = false;
      setFieldError(key, errorMessage);
      if (!firstInvalid) firstInvalid = input;
    }
  });

  if (!valid && firstInvalid) {
    firstInvalid.focus();
  }

  return valid;
}

function announce(message) {
  if (!liveStatus) return;
  liveStatus.textContent = message;
}

function isAddressComplete() {
  const address = storage.get('address');
  return Boolean(address && address.cep && address.city && address.number && address.street && address.state);
}

function isVehicleComplete() {
  const vehicle = storage.get('vehicle');
  return Boolean(vehicle && vehicle.model);
}

function updateStatusChips() {
  const addressReady = isAddressComplete();
  const vehicleReady = isVehicleComplete();
  if (addressStatusChip) {
    addressStatusChip.textContent = addressReady ? 'Endereço confirmado' : 'Endereço pendente';
    addressStatusChip.classList.toggle('ready', addressReady);
  }
  if (vehicleStatusChip) {
    vehicleStatusChip.textContent = vehicleReady ? 'Veículo selecionado' : 'Veículo pendente';
    vehicleStatusChip.classList.toggle('ready', vehicleReady);
  }
}

function updateChecklist() {
  const addressReady = isAddressComplete();
  const vehicleReady = isVehicleComplete();
  if (addressChecklist) {
    addressChecklist.textContent = addressReady ? 'Endereço confirmado para entrega' : 'Endereço pendente';
    addressChecklist.classList.toggle('ready', addressReady);
  }
  if (vehicleChecklist) {
    vehicleChecklist.textContent = vehicleReady ? 'Veículo definido para recomendação' : 'Veículo pendente';
    vehicleChecklist.classList.toggle('ready', vehicleReady);
  }
}

function updateProgress() {
  if (!progressBar) return;
  const stepsReady = Number(isAddressComplete()) + Number(isVehicleComplete());
  const width = stepsReady === 0 ? 30 : stepsReady === 1 ? 60 : 100;
  progressBar.style.width = `${width}%`;
}

function computeReadiness() {
  let score = 0;
  if (isAddressComplete()) score += 45;
  if (isVehicleComplete()) score += 45;
  if (waNameInput.value.trim()) score += 10;
  return Math.min(score, 100);
}

function updateReadiness() {
  if (!readinessBar || !readinessValue || !readinessStatus) return;
  const percent = computeReadiness();
  readinessBar.style.width = `${percent}%`;
  readinessValue.textContent = `${percent}%`;
  const addressReady = isAddressComplete();
  const vehicleReady = isVehicleComplete();
  const inlineHint = addressReady && vehicleReady
    ? 'Adicione seu nome (opcional) para chegar a 100%.'
    : 'Complete endereço e veículo para liberar o pedido.';
  readinessStatus.textContent = percent === 100
    ? 'Tudo pronto para enviar pelo WhatsApp.'
    : addressReady && vehicleReady
      ? 'Endereço e veículo completos. Adicione seu nome (opcional) para chegar a 100%.'
      : 'Complete endereço e veículo para chegar a 100%.';

  if (inlineReadinessValue && inlineReadinessText) {
    inlineReadinessValue.textContent = `${percent}%`;
    inlineReadinessText.textContent = percent === 100
      ? 'Tudo pronto para pedir pelo WhatsApp.'
      : inlineHint;
  }

  if (popupReadinessValue) {
    popupReadinessValue.textContent = `${percent}%`;
    const chip = document.getElementById('popupReadiness');
    if (chip) chip.classList.toggle('ready', percent === 100);
  }
}

function updateStickyBar() {
  if (!stickyBar || !stickyStatus || !stickyAction) return;
  const percent = computeReadiness();
  const ready = isAddressComplete() && isVehicleComplete();
  stickyBar.classList.toggle('ready', ready);
  stickyStatus.textContent = ready
    ? 'Pronto para enviar seu pedido agora.'
    : `Pronto ${percent}% — complete endereço e veículo.`;
  if (stickySub) {
    stickySub.textContent = ready
      ? 'Abra o WhatsApp para finalizar seu pedido.'
      : 'Revisamos tudo e abrimos seu WhatsApp automaticamente.';
  }
  if (stickyPercent) {
    stickyPercent.textContent = `${percent}%`;
    stickyPercent.classList.toggle('full', percent === 100);
  }
  stickyAction.textContent = ready ? 'Pedir pelo WhatsApp' : 'Preencher dados';
  stickyAction.dataset.action = ready ? 'send' : 'open';
}

function toggleCtaState() {
  const ready = isAddressComplete() && isVehicleComplete();
  ctaWhatsApp.disabled = !ready;
  ctaWhatsApp.classList.toggle('disabled', !ready);
  ctaStateHint.textContent = ready
    ? 'Pronto para abrir o WhatsApp com seus dados.'
    : 'Preencha endereço e veículo para liberar o pedido.';
  updateStatusChips();
  updateChecklist();
  updateProgress();
  updateReadiness();
  updateStickyBar();
}

function buildWhatsAppMessage() {
  const address = storage.get('address') || {};
  const vehicle = storage.get('vehicle') || {};
  const name = waNameInput.value.trim();
  const notes = notesInput ? notesInput.value.trim() : '';

  const addressText = address.cep
    ? `${address.cep}, ${address.street || ''}, ${address.district || ''}, ${address.city || ''} - ${address.state || ''}, Nº ${address.number || ''}${address.complement ? ', ' + address.complement : ''}`
    : 'Endereço não informado';

  const vehicleText = vehicle.model ? `${vehicle.brand} — ${vehicle.model} (${vehicle.years})` : 'Veículo não informado';
  const notesText = notes ? `\nObservações: ${notes}` : '';

  return `Nome: ${name || '(nome exibido no WhatsApp)'}\nEndereço: ${addressText}\nVeículo: ${vehicleText}${notesText}`;
}

function updateMessagePreview() {
  if (!messagePreview) return;
  const readyAddress = isAddressComplete();
  const readyVehicle = isVehicleComplete();
  if (!readyAddress || !readyVehicle) {
    messagePreview.textContent = 'Preencha endereço e veículo para gerar o resumo.';
    return;
  }
  messagePreview.textContent = buildWhatsAppMessage();
}

function focusSummary() {
  const summary = document.getElementById('summary');
  if (!summary) return;
  summary.scrollIntoView({ behavior: 'smooth', block: 'start' });
  const card = document.querySelector('.summary-card');
  if (card) {
    card.classList.add('pulse');
    setTimeout(() => card.classList.remove('pulse'), 1600);
  }
}

function ensureDataBeforeSend() {
  validateAddressForm();
  const missingAddress = !isAddressComplete();
  const missingVehicle = !isVehicleComplete();

  if (missingAddress || missingVehicle) {
    announce('Complete endereço e veículo para finalizar o pedido.');
    if (formAlert) {
      formAlert.textContent = missingAddress ? 'Preencha o endereço para liberar a escolha do veículo.' : 'Escolha um veículo para finalizar o pedido.';
      formAlert.classList.add('show');
    }
    showToast('Complete endereço e veículo para continuar.', 'warning');
    openPopup();
    setStep(missingAddress ? 1 : 2);
    return false;
  }
  return true;
}

function sendToWhatsApp() {
  if (!ensureDataBeforeSend()) return;
  storage.set('waName', { name: waNameInput.value.trim() });
  const message = buildWhatsAppMessage();
  const url = `https://wa.me/553291260925?text=${encodeURIComponent(message)}`;
  showToast('Abrindo seu WhatsApp com os dados preenchidos.', 'success');
  window.open(url, '_blank');
}

function handlePrimaryTrigger() {
  const ready = isAddressComplete() && isVehicleComplete();
  if (ready) {
    sendToWhatsApp();
  } else {
    openPopup(isAddressComplete() ? 2 : 1);
  }
}

function resetData() {
  sessionStorage.clear();
  Object.values(addressFields).forEach((input) => {
    input.value = '';
    input.classList.remove('is-invalid');
    input.classList.remove('manual-edit');
  });
  vehicleFilter.value = '';
  renderVehicles();
  activeBrand = null;
  renderBrandChips();
  waNameInput.value = '';
  if (notesInput) notesInput.value = '';
  vehicleTab.disabled = true;
  if (formAlert) {
    formAlert.textContent = 'Dados limpos. Preencha o endereço para continuar.';
    formAlert.classList.add('show');
    formAlert.style.background = 'rgba(255, 199, 0, 0.12)';
    formAlert.style.color = '#7a4d0b';
  }
  showToast('Dados limpos. Recomece pelo CEP.', 'warning');
  updatePreviews();
  toggleCtaState();
  announce('Dados do pedido foram limpos. Recomece pelo endereço.');
  cepInput.focus();
}

async function copySummaryToClipboard() {
  if (!copySummaryBtn) return;
  const message = buildWhatsAppMessage();
  try {
    await navigator.clipboard.writeText(message);
    copySummaryBtn.textContent = 'Copiado!';
    announce('Resumo copiado para a área de transferência.');
    showToast('Resumo copiado com sucesso.', 'success');
    setTimeout(() => { copySummaryBtn.textContent = 'Copiar resumo'; }, 1800);
  } catch (error) {
    copySummaryBtn.textContent = 'Falha ao copiar';
    announce('Não foi possível copiar. Selecione o texto manualmente.');
    showToast('Não foi possível copiar. Use Ctrl+C.', 'error');
    setTimeout(() => { copySummaryBtn.textContent = 'Copiar resumo'; }, 1800);
  }
}

// Event listeners
openPopupButtons.forEach((button) => button.addEventListener('click', () => {
  const targetStep = button.dataset.targetStep ? Number(button.dataset.targetStep) : undefined;
  openPopup(targetStep);
}));
primaryTriggers.forEach((button) => button.addEventListener('click', handlePrimaryTrigger));
closePopupButton.addEventListener('click', closePopup);
popupOverlay.addEventListener('click', (event) => {
  if (event.target === popupOverlay) closePopup();
});

stepTabs.forEach((tab) => tab.addEventListener('click', () => {
  if (tab.dataset.step === '2' && tab.disabled) return;
  setStep(Number(tab.dataset.step));
}));

cepInput.addEventListener('input', (event) => {
  event.target.value = maskCep(event.target.value);
  if (event.target.value.replace(/\D/g, '').length === 8) {
    fetchCep(event.target.value);
  }
});

addressForm.addEventListener('submit', (event) => {
  event.preventDefault();
  if (!validateAddressForm()) return;
  persistAddress();
  vehicleTab.disabled = false;
  announce('Endereço salvo. Escolha o veículo.');
  setStep(2);
});

vehicleFilter.addEventListener('input', (event) => {
  renderVehicles(event.target.value);
});

vehicleFilter.addEventListener('keydown', (event) => {
  if (event.key !== 'Enter') return;
  event.preventDefault();
  const firstCard = vehicleListEl.querySelector('.vehicle-card');
  if (!firstCard) return;
  const selected = {
    brand: firstCard.dataset.brand,
    model: firstCard.dataset.model,
    years: firstCard.dataset.years,
  };
  persistVehicle(selected);
  renderVehicles(vehicleFilter.value);
  announce(`Veículo aplicado: ${selected.brand} ${selected.model}.`);
  showToast(`Selecionado ${selected.brand} ${selected.model}.`, 'success');
  closePopup();
  focusSummary();
});

clearFilterBtn?.addEventListener('click', () => {
  vehicleFilter.value = '';
  activeBrand = null;
  renderVehicles();
  vehicleFilter.focus();
  renderBrandChips();
  storage.set('activeBrand', { brand: null });
  showToast('Filtros limpos. Veja todos os modelos.', 'info');
});

brandChips?.addEventListener('click', (event) => {
  const chip = event.target.closest('.brand-chip');
  if (!chip) return;
  const brand = chip.dataset.brand;
  activeBrand = brand || null;
  renderVehicles(vehicleFilter.value);
  renderBrandChips();
  storage.set('activeBrand', { brand: activeBrand });
  const brandLabel = activeBrand || 'todas as montadoras';
  showToast(`Filtrando ${brandLabel}.`, 'info');
});

manualAddressBtn?.addEventListener('click', () => {
  cepStatus.textContent = 'Preencher manualmente';
  cepStatus.classList.remove('loading');
  Object.values(addressFields).forEach((input) => input.classList.add('manual-edit'));
  addressFields.street.focus();
  if (formAlert) {
    formAlert.textContent = 'Preencha os campos manualmente e salve o endereço.';
    formAlert.classList.add('show');
  }
});

retryCepBtn?.addEventListener('click', () => {
  const value = cepInput.value.replace(/\D/g, '');
  if (value.length !== 8) {
    if (formAlert) {
      formAlert.textContent = 'Digite um CEP válido (8 dígitos) para consultar novamente.';
      formAlert.classList.add('show');
    }
    cepInput.focus();
    return;
  }
  fetchCep(cepInput.value);
});

popularList?.addEventListener('click', (event) => {
  const chip = event.target.closest('.popular-chip');
  if (!chip) return;
  const brand = chip.dataset.brand;
  const model = chip.dataset.model;
  const vehicle = vehicles.find((item) => item.brand === brand && item.model === model);
  if (!vehicle) return;
  persistVehicle(vehicle);
  activeBrand = brand;
  renderBrandChips();
  storage.set('activeBrand', { brand: activeBrand });
  renderVehicles(vehicleFilter.value);
  announce(`Veículo selecionado rapidamente: ${brand} ${model}.`);
  showToast(`Selecionado ${brand} ${model}.`, 'success');
  closePopup();
  focusSummary();
});

vehicleListEl.addEventListener('click', (event) => {
  const card = event.target.closest('.vehicle-card');
  if (!card) return;
  const selected = {
    brand: card.dataset.brand,
    model: card.dataset.model,
    years: card.dataset.years,
  };
  persistVehicle(selected);
  renderVehicles(vehicleFilter.value);
  announce(`Veículo selecionado: ${selected.brand} ${selected.model}.`);
  closePopup();
  focusSummary();
});

waNameInput.addEventListener('input', () => {
  storage.set('waName', { name: waNameInput.value.trim() });
  updateMessagePreview();
  updateReadiness();
});

notesInput?.addEventListener('input', () => {
  storage.set('notes', { text: notesInput.value.trim() });
  updateMessagePreview();
});
ctaWhatsApp.addEventListener('click', sendToWhatsApp);
copySummaryBtn?.addEventListener('click', copySummaryToClipboard);
scrollTopBtn?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
resetDataBtn?.addEventListener('click', resetData);

stickyAction?.addEventListener('click', () => {
  if (stickyAction.dataset.action === 'send') {
    sendToWhatsApp();
  } else {
    openPopup(isAddressComplete() ? 2 : 1);
  }
});

addressForm.addEventListener('input', () => {
  validateAddressForm();
  persistAddress();
  if (formAlert && addressForm.checkValidity()) {
    formAlert.classList.remove('show');
  }
});

addressForm.addEventListener('blur', (event) => {
  if (event.target && event.target.name) validateAddressForm();
}, true);

function hydrateFromStorage() {
  const savedAddress = storage.get('address');
  if (savedAddress) {
    Object.entries(addressFields).forEach(([key, input]) => {
      if (savedAddress[key]) input.value = savedAddress[key];
    });
    if (savedAddress.cep && savedAddress.city && savedAddress.number) {
      vehicleTab.disabled = false;
    }
  }
  const savedBrand = storage.get('activeBrand');
  if (savedBrand && Object.prototype.hasOwnProperty.call(savedBrand, 'brand')) {
    activeBrand = savedBrand.brand;
  }
  const savedVehicle = storage.get('vehicle');
  if (savedVehicle) {
    persistVehicle(savedVehicle);
    activeBrand = savedVehicle.brand;
  }
  const savedName = storage.get('waName');
  if (savedName && savedName.name) waNameInput.value = savedName.name;
  const savedNotes = storage.get('notes');
  if (savedNotes && savedNotes.text && notesInput) notesInput.value = savedNotes.text;
  updatePreviews();
  renderBrandChips();
  toggleCtaState();
  if (savedAddress || savedVehicle) {
    showToast('Recuperamos seus dados salvos.', 'info');
  }
}

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && popupOverlay.classList.contains('active')) {
    closePopup();
  }
});

addressFields.number.addEventListener('input', (event) => {
  event.target.value = event.target.value.replace(/[^\d]/g, '');
});

renderBrands();
renderBrandChips();
renderVehicles();
renderPopularVehicles();
renderFaqs();
hydrateFromStorage();
toggleCtaState();

if (scrollSummary) {
  scrollSummary.addEventListener('click', () => {
    document.getElementById('summary').scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
}

faqList?.addEventListener('click', (event) => {
  const item = event.target.closest('.faq-item');
  if (!item) return;
  const expanded = item.getAttribute('aria-expanded') === 'true';
  const panel = item.querySelector('.faq-answer');
  if (!panel) return;
  item.setAttribute('aria-expanded', String(!expanded));
  if (expanded) {
    panel.setAttribute('hidden', '');
    const icon = item.querySelector('.faq-icon');
    if (icon) icon.textContent = '+';
  } else {
    panel.removeAttribute('hidden');
    const icon = item.querySelector('.faq-icon');
    if (icon) icon.textContent = '−';
  }
});
