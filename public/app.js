const form = document.getElementById('search-form');
const searchTypeInputs = form.querySelectorAll('input[name="searchType"]');
const modalidadeField = form.querySelector('[data-field="modalidade"]');
const modalidadeSelect = document.getElementById('modalidadeId');
const modoDisputaField = form.querySelector('[data-field="modo-disputa"]');
const modoDisputaSelect = document.getElementById('modoDisputaId');
const ufSelect = document.getElementById('uf');
const summaryEl = document.getElementById('summary');
const errorEl = document.getElementById('error');
const loadingEl = document.getElementById('loading');
const resultsTableBody = document.querySelector('#results-table tbody');
const prevButton = document.getElementById('prev-page');
const nextButton = document.getElementById('next-page');
const pageIndicator = document.getElementById('page-indicator');
const detailsDialog = document.getElementById('details-dialog');
const detailsContent = document.getElementById('details-content');
const closeDialogButton = document.getElementById('close-dialog');
const resetButton = document.getElementById('reset-button');

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL'
});

const dateFormatter = new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium' });

const state = {
  searchType: 'publicacao',
  page: 1,
  totalPages: 0,
  totalRecords: 0,
  term: '',
  baseQuery: {},
  entries: []
};

let activeController;

function setLoading(isLoading) {
  loadingEl.hidden = !isLoading;
  form.querySelectorAll('button, input, select').forEach((element) => {
    if (element.type === 'submit' || element.tagName === 'BUTTON') {
      element.disabled = isLoading;
    }
  });
}

function resetResults() {
  resultsTableBody.innerHTML = '';
  summaryEl.textContent = 'Nenhum resultado para exibir.';
  pageIndicator.textContent = '';
  state.page = 1;
  state.totalPages = 0;
  state.totalRecords = 0;
  prevButton.disabled = true;
  nextButton.disabled = true;
}

function populateUfOptions() {
  const ufs = [
    ['AC', 'Acre'],
    ['AL', 'Alagoas'],
    ['AP', 'Amapá'],
    ['AM', 'Amazonas'],
    ['BA', 'Bahia'],
    ['CE', 'Ceará'],
    ['DF', 'Distrito Federal'],
    ['ES', 'Espírito Santo'],
    ['GO', 'Goiás'],
    ['MA', 'Maranhão'],
    ['MT', 'Mato Grosso'],
    ['MS', 'Mato Grosso do Sul'],
    ['MG', 'Minas Gerais'],
    ['PA', 'Pará'],
    ['PB', 'Paraíba'],
    ['PR', 'Paraná'],
    ['PE', 'Pernambuco'],
    ['PI', 'Piauí'],
    ['RJ', 'Rio de Janeiro'],
    ['RN', 'Rio Grande do Norte'],
    ['RS', 'Rio Grande do Sul'],
    ['RO', 'Rondônia'],
    ['RR', 'Roraima'],
    ['SC', 'Santa Catarina'],
    ['SP', 'São Paulo'],
    ['SE', 'Sergipe'],
    ['TO', 'Tocantins']
  ];
  ufSelect.innerHTML = '<option value="">Todas</option>' +
    ufs.map(([sigla, nome]) => `<option value="${sigla}">${sigla} - ${nome}</option>`).join('');
}

function setDefaultDates() {
  const dataInicial = document.getElementById('dataInicial');
  const dataFinal = document.getElementById('dataFinal');
  const hoje = new Date();
  const inicioMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
  dataInicial.value = inicioMes.toISOString().split('T')[0];
  dataFinal.value = hoje.toISOString().split('T')[0];
}

async function fetchJson(url, options = {}) {
  if (activeController) {
    activeController.abort();
  }
  activeController = new AbortController();
  const config = { ...options, signal: activeController.signal };
  let response;
  try {
    response = await fetch(url, config);
  } catch (error) {
    if (error.name === 'AbortError') {
      throw error;
    }
    throw new Error('Não foi possível comunicar com o backend.');
  }

  if (!response.ok) {
    const errorBody = await response.json().catch(() => ({ message: response.statusText }));
    const error = new Error(errorBody.message || 'Falha ao consultar o backend.');
    error.details = errorBody.details;
    throw error;
  }
  return response.json();
}

async function loadModalidades() {
  try {
    const data = await fetch('/api/modalidades').then((res) => res.json());
    modalidadeSelect.innerHTML =
      '<option value="" disabled selected>Selecione</option>' +
      data.map((item) => `<option value="${item.id}">${item.nome}</option>`).join('');
  } catch (error) {
    console.error('Erro ao carregar modalidades', error);
    modalidadeSelect.innerHTML = '<option value="" disabled>Não foi possível carregar</option>';
  }
}

async function loadModosDisputa() {
  try {
    const data = await fetch('/api/modos-disputa').then((res) => res.json());
    modoDisputaSelect.innerHTML =
      '<option value="">Todos</option>' +
      data.map((item) => `<option value="${item.id}">${item.nome}</option>`).join('');
  } catch (error) {
    console.error('Erro ao carregar modos de disputa', error);
    modoDisputaSelect.innerHTML = '<option value="" disabled>Não foi possível carregar</option>';
  }
}

function toggleFieldsForSearchType(type) {
  const isPublicacao = type === 'publicacao';
  modalidadeField.hidden = !isPublicacao;
  modoDisputaField.hidden = !isPublicacao;
  modalidadeSelect.required = isPublicacao;
}

function formatCurrency(value) {
  if (typeof value !== 'number') {
    return '—';
  }
  return currencyFormatter.format(value);
}

function formatDate(value) {
  if (!value) {
    return '—';
  }
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }
  return dateFormatter.format(date);
}

function buildSummary(totalOnPage, filteredCount) {
  if (!state.totalRecords) {
    return 'Nenhum resultado encontrado para os parâmetros informados.';
  }
  const base = `Mostrando ${filteredCount} de ${totalOnPage} registros nesta página.`;
  const total = ` Total geral informado pelo PNCP: ${state.totalRecords}.`;
  if (state.term) {
    return `${base} ${filteredCount !== totalOnPage ? 'Aplicado filtro pelo termo "' + state.term + '".' : ''}${total}`;
  }
  return base + total;
}

function renderResults(data) {
  const entries = Array.isArray(data?.data) ? data.data : [];
  state.totalPages = data?.totalPaginas ?? 0;
  state.totalRecords = data?.totalRegistros ?? entries.length;
  const filtered = state.term
    ? entries.filter((entry) => filterByTerm(entry, state.term, state.searchType))
    : entries;

  state.entries = filtered;
  resultsTableBody.innerHTML = '';

  if (!filtered.length) {
    summaryEl.textContent = 'Nenhum registro encontrado com o termo aplicado.';
    pageIndicator.textContent = `Página ${state.page} de ${state.totalPages || 1}`;
    prevButton.disabled = state.page <= 1;
    nextButton.disabled = state.page >= (state.totalPages || 1);
    return;
  }

  const fragment = document.createDocumentFragment();
  filtered.forEach((entry, index) => {
    const tr = document.createElement('tr');
    tr.innerHTML = renderRow(entry, index, state.searchType);
    fragment.appendChild(tr);
  });

  resultsTableBody.appendChild(fragment);
  summaryEl.textContent = buildSummary(entries.length, filtered.length);
  pageIndicator.textContent = `Página ${state.page} de ${state.totalPages || 1}`;
  prevButton.disabled = state.page <= 1;
  nextButton.disabled = state.page >= (state.totalPages || 1);
}

function filterByTerm(entry, term, type) {
  const normalizedTerm = term.toLowerCase();
  if (type === 'contratos') {
    const alvo = `${entry.objetoContrato || ''} ${entry.nomeRazaoSocialFornecedor || ''} ${
      entry.orgaoEntidade?.razaoSocial || ''
    } ${entry.unidadeOrgao?.municipioNome || ''}`;
    return alvo.toLowerCase().includes(normalizedTerm);
  }
  const alvo = `${entry.objetoCompra || ''} ${entry.orgaoEntidade?.razaoSocial || ''} ${
    entry.unidadeOrgao?.municipioNome || ''
  }`;
  return alvo.toLowerCase().includes(normalizedTerm);
}

function renderRow(entry, index, type) {
  if (type === 'contratos') {
    const fornecedor = entry.nomeRazaoSocialFornecedor || '—';
    const objeto = entry.objetoContrato || '—';
    const valores = `Inicial: <span class="value-highlight">${formatCurrency(
      entry.valorInicial
    )}</span><br />Global: <span class="value-highlight">${formatCurrency(entry.valorGlobal)}</span>`;
    return `
      <td>
        <strong>${formatDate(entry.dataPublicacaoPncp)}</strong><br />
        Vigência: ${formatDate(entry.dataVigenciaInicio)} – ${formatDate(entry.dataVigenciaFim)}
      </td>
      <td>
        <strong>${entry.orgaoEntidade?.razaoSocial || '—'}</strong><br />
        ${entry.unidadeOrgao?.ufSigla || '—'} · ${entry.unidadeOrgao?.municipioNome || '—'}
      </td>
      <td>
        <strong>${fornecedor}</strong><br />
        ${objeto}
      </td>
      <td>${valores}</td>
      <td>
        <button type="button" class="secondary" data-action="detalhes" data-index="${index}">
          Ver detalhes
        </button>
      </td>
    `;
  }

  const valores = `Estimado: <span class="value-highlight">${formatCurrency(
    entry.valorTotalEstimado
  )}</span><br />Homologado: <span class="value-highlight">${formatCurrency(
    entry.valorTotalHomologado
  )}</span>`;

  return `
    <td>
      <strong>${formatDate(entry.dataPublicacaoPncp)}</strong><br />
      Abertura: ${formatDate(entry.dataAberturaProposta)}
    </td>
    <td>
      <strong>${entry.orgaoEntidade?.razaoSocial || '—'}</strong><br />
      ${entry.unidadeOrgao?.ufSigla || '—'} · ${entry.unidadeOrgao?.municipioNome || '—'}
    </td>
    <td>
      <strong>${entry.numeroCompra || '—'}</strong><br />
      ${entry.objetoCompra || '—'}
    </td>
    <td>${valores}</td>
    <td>
      <button type="button" class="secondary" data-action="detalhes" data-index="${index}">
        Ver detalhes
      </button>
    </td>
  `;
}

function buildQueryFromForm() {
  const data = new FormData(form);
  const searchType = data.get('searchType');
  const baseQuery = {
    dataInicial: data.get('dataInicial'),
    dataFinal: data.get('dataFinal'),
    pagina: 1,
    tamanhoPagina: data.get('tamanhoPagina') || 20
  };

  const cnpj = data.get('cnpj');
  const cleanedCnpj = cnpj ? cnpj.replace(/\D/g, '') : '';
  if (cleanedCnpj) {
    baseQuery.cnpj = cleanedCnpj;
  }

  if (searchType === 'publicacao') {
    baseQuery.modalidadeId = data.get('modalidadeId');
    baseQuery.modoDisputaId = data.get('modoDisputaId') || undefined;
    baseQuery.uf = data.get('uf') || undefined;
    baseQuery.codigoMunicipioIbge = data.get('codigoMunicipioIbge') || undefined;
    baseQuery.codigoUnidadeAdministrativa = data.get('codigoUnidadeAdministrativa') || undefined;
  } else {
    baseQuery.cnpjOrgao = cleanedCnpj || undefined;
  }

  state.term = (data.get('termo') || '').trim();
  state.searchType = searchType;
  state.baseQuery = baseQuery;
  state.page = 1;
  return baseQuery;
}

async function fetchAndRender(queryOverrides = {}) {
  const query = { ...state.baseQuery, ...queryOverrides, pagina: queryOverrides.pagina || state.page };
  const endpoint = state.searchType === 'contratos' ? '/api/contratos' : '/api/contratacoes/publicacao';
  const params = new URLSearchParams();
  Object.entries(query).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      params.append(key, value);
    }
  });

  const url = `${endpoint}?${params.toString()}`;
  errorEl.hidden = true;
  errorEl.textContent = '';
  setLoading(true);
  try {
    const data = await fetchJson(url);
    state.page = Number(query.pagina || 1);
    renderResults(data);
  } catch (error) {
    if (error.name === 'AbortError') {
      return;
    }
    console.error(error);
    errorEl.hidden = false;
    errorEl.textContent = error.message || 'Não foi possível concluir a consulta.';
    resetResults();
  } finally {
    setLoading(false);
  }
}

function extractControleData(controle) {
  if (!controle) {
    return null;
  }
  const regex = /(\d{14})-\d-0*(\d+)\/(\d{4})/;
  const match = controle.match(regex);
  if (!match) {
    return null;
  }
  return {
    cnpj: match[1],
    sequencial: Number(match[2]),
    ano: Number(match[3])
  };
}

async function openDetails(entry) {
  detailsContent.innerHTML = '<p>Carregando detalhes…</p>';
  if (typeof detailsDialog.showModal === 'function' && !detailsDialog.open) {
    detailsDialog.showModal();
  } else {
    detailsDialog.setAttribute('open', 'open');
  }

  const listItems = [];
  const detailPairs = [];

  if (state.searchType === 'contratos') {
    detailPairs.push(['Órgão', entry.orgaoEntidade?.razaoSocial || '—']);
    detailPairs.push(['Unidade', `${entry.unidadeOrgao?.nomeUnidade || '—'} (${entry.unidadeOrgao?.codigoUnidade || '—'})`]);
    detailPairs.push(['Fornecedor', entry.nomeRazaoSocialFornecedor || '—']);
    detailPairs.push(['CNPJ fornecedor', entry.niFornecedor || '—']);
    detailPairs.push(['Objeto', entry.objetoContrato || '—']);
    detailPairs.push(['Valor global', formatCurrency(entry.valorGlobal)]);
    detailPairs.push(['Data de assinatura', formatDate(entry.dataAssinatura)]);
    detailPairs.push(['Vigência', `${formatDate(entry.dataVigenciaInicio)} – ${formatDate(entry.dataVigenciaFim)}`]);
  } else {
    detailPairs.push(['Órgão', entry.orgaoEntidade?.razaoSocial || '—']);
    detailPairs.push(['Unidade', `${entry.unidadeOrgao?.nomeUnidade || '—'} (${entry.unidadeOrgao?.codigoUnidade || '—'})`]);
    detailPairs.push(['Objeto da compra', entry.objetoCompra || '—']);
    detailPairs.push(['Modalidade', entry.modalidadeNome || '—']);
    detailPairs.push(['Modo de disputa', entry.modoDisputaNome || '—']);
    detailPairs.push(['Processo', entry.processo || '—']);
    detailPairs.push(['Valor estimado', formatCurrency(entry.valorTotalEstimado)]);
    detailPairs.push(['Valor homologado', formatCurrency(entry.valorTotalHomologado)]);
  }

  const controleInfo = state.searchType === 'contratos'
    ? extractControleData(entry.numeroControlePncpCompra)
    : {
        cnpj: entry.orgaoEntidade?.cnpj,
        sequencial: entry.sequencialCompra,
        ano: entry.anoCompra
      };

  if (controleInfo?.cnpj && controleInfo?.sequencial && controleInfo?.ano) {
  try {
      const params = new URLSearchParams({
        cnpj: controleInfo.cnpj,
        sequencial: String(controleInfo.sequencial),
        ano: String(controleInfo.ano)
      });
      const response = await fetch(`/api/compras/detalhe?${params.toString()}`);
      if (!response.ok) {
        throw new Error('Erro ao recuperar detalhes da compra.');
      }
      const detail = await response.json();
      detailPairs.push(['Publicação no PNCP', formatDate(detail.dataPublicacaoPncp)]);
      detailPairs.push(['Número da compra', detail.numeroCompra || '—']);
      detailPairs.push(['Informações complementares', detail.informacaoComplementar || '—']);
      detailPairs.push([
        'Situação da compra',
        `${detail.situacaoCompraNome || '—'}${detail.existeResultado ? ' · Possui resultado publicado' : ''}`
      ]);
      if (detail.valorTotalEstimado) {
        detailPairs.push(['Valor total estimado (detalhe)', formatCurrency(detail.valorTotalEstimado)]);
      }
      if (detail.valorTotalHomologado) {
        detailPairs.push(['Valor total homologado (detalhe)', formatCurrency(detail.valorTotalHomologado)]);
      }
      if (Array.isArray(detail.fontesOrcamentarias) && detail.fontesOrcamentarias.length) {
        listItems.push(
          `<li><strong>Fontes orçamentárias:</strong> ${detail.fontesOrcamentarias
            .map((fonte) => `${fonte.codigo} - ${fonte.descricao}`)
            .join('; ')}</li>`
        );
      }
      if (detail.linkSistemaOrigem) {
        listItems.push(
          `<li><a href="${detail.linkSistemaOrigem}" target="_blank" rel="noopener">Sistema de origem</a></li>`
        );
      }
      if (detail.linkProcessoEletronico) {
        listItems.push(
          `<li><a href="${detail.linkProcessoEletronico}" target="_blank" rel="noopener">Processo eletrônico</a></li>`
        );
      }
    } catch (error) {
      detailPairs.push(['Detalhes adicionais', 'Não foi possível recuperar informações complementares.']);
    }
  }

  const detailsHtml = `
    <dl class="detail-list">
      ${detailPairs
        .map(
          ([label, value]) => `
            <div>
              <dt>${label}</dt>
              <dd>${value || '—'}</dd>
            </div>
          `
        )
        .join('')}
    </dl>
    ${listItems.length ? `<ul class="detail-extra">${listItems.join('')}</ul>` : ''}
  `;

  detailsContent.innerHTML = detailsHtml;
}

function handleTableClick(event) {
  const button = event.target.closest('button[data-action="detalhes"]');
  if (!button) {
    return;
  }
  const index = Number(button.dataset.index);
  const entry = state.entries[index];
  if (!entry) {
    return;
  }
  openDetails(entry);
}

function closeDetailsDialog() {
  if (typeof detailsDialog.close === 'function') {
    detailsDialog.close();
  } else {
    detailsDialog.removeAttribute('open');
  }
}

function handleReset() {
  state.term = '';
  summaryEl.textContent = '';
  errorEl.hidden = true;
  errorEl.textContent = '';
  resetResults();
}

function initEventListeners() {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const baseQuery = buildQueryFromForm();
    if (state.searchType === 'publicacao' && !baseQuery.modalidadeId) {
      errorEl.hidden = false;
      errorEl.textContent = 'Selecione uma modalidade para consultar as publicações.';
      return;
    }
    await fetchAndRender({ pagina: 1 });
  });

  resetButton.addEventListener('click', () => {
    setDefaultDates();
    handleReset();
  });

  searchTypeInputs.forEach((input) => {
    input.addEventListener('change', (event) => {
      const value = event.target.value;
      toggleFieldsForSearchType(value);
      state.searchType = value;
      handleReset();
    });
  });

  prevButton.addEventListener('click', () => {
    if (state.page > 1) {
      const pagina = state.page - 1;
      fetchAndRender({ pagina });
    }
  });

  nextButton.addEventListener('click', () => {
    if (state.page < state.totalPages) {
      const pagina = state.page + 1;
      fetchAndRender({ pagina });
    }
  });

  document.querySelector('#results-table').addEventListener('click', handleTableClick);

  closeDialogButton.addEventListener('click', closeDetailsDialog);
  detailsDialog.addEventListener('cancel', (event) => {
    event.preventDefault();
    closeDetailsDialog();
  });
}

async function bootstrap() {
  populateUfOptions();
  setDefaultDates();
  toggleFieldsForSearchType(state.searchType);
  await Promise.all([loadModalidades(), loadModosDisputa()]);
  initEventListeners();
}

bootstrap().catch((error) => {
  console.error('Falha ao iniciar aplicação', error);
  errorEl.hidden = false;
  errorEl.textContent = 'Não foi possível inicializar a aplicação.';
});
