const express = require('express');
const path = require('path');
const cors = require('cors');
const { ProxyAgent, setGlobalDispatcher } = require('undici');

const app = express();
const PORT = process.env.PORT || 3000;

const PNCP_CONSULTA_BASE = 'https://pncp.gov.br/api/consulta/v1/';
const PNCP_CADASTRO_BASE = 'https://pncp.gov.br/api/pncp/v1/';
const FETCH_TIMEOUT = Number(process.env.PNCP_TIMEOUT_MS || 20000);

const proxyUrl = process.env.HTTPS_PROXY || process.env.HTTP_PROXY;
if (proxyUrl) {
  try {
    const agent = new ProxyAgent(proxyUrl);
    setGlobalDispatcher(agent);
  } catch (error) {
    console.error('Não foi possível configurar o proxy HTTP:', error);
  }
}

const DEFAULT_HEADERS = {
  accept: 'application/json, text/plain, */*',
  'user-agent':
    'Mozilla/5.0 (compatible; PNCPPriceResearch/1.0; +https://github.com/openai/chat-pro)'
};

app.use(cors());
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

function normalizeDateParam(value, label) {
  if (!value) {
    throw new Error(`Parâmetro obrigatório ausente: ${label}`);
  }

  // Accept YYYY-MM-DD, DD/MM/YYYY or YYYYMMDD
  const trimmed = String(value).trim();
  const isoMatch = trimmed.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (isoMatch) {
    return `${isoMatch[1]}${isoMatch[2]}${isoMatch[3]}`;
  }

  const brMatch = trimmed.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
  if (brMatch) {
    return `${brMatch[3]}${brMatch[2]}${brMatch[1]}`;
  }

  const digitsOnly = trimmed.replace(/\D/g, '');
  if (digitsOnly.length === 8) {
    return digitsOnly;
  }

  throw new Error(`Formato de data inválido para ${label}. Utilize AAAA-MM-DD.`);
}

function sanitizeCnpj(value) {
  if (!value) {
    return undefined;
  }
  const digits = String(value).replace(/\D/g, '');
  if (digits && digits.length !== 14) {
    throw new Error('CNPJ deve conter 14 dígitos.');
  }
  return digits || undefined;
}

function toPositiveInteger(value, label) {
  if (value === undefined || value === null || value === '') {
    return undefined;
  }
  const parsed = Number.parseInt(value, 10);
  if (Number.isNaN(parsed) || parsed < 1) {
    throw new Error(`${label} deve ser um inteiro positivo.`);
  }
  return parsed;
}

function buildPncpUrl(base, relativePath, params = {}) {
  const url = new URL(relativePath, base);
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      url.searchParams.set(key, String(value));
    }
  });
  return url;
}

async function fetchJson(url) {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), FETCH_TIMEOUT);
  try {
    const response = await fetch(url, {
      headers: DEFAULT_HEADERS,
      signal: controller.signal
    });

    if (!response.ok) {
      let details;
      try {
        details = await response.json();
      } catch (error) {
        details = { message: await response.text() };
      }
      const error = new Error(
        `Falha ao consultar o PNCP (${response.status} ${response.statusText}).`
      );
      error.status = response.status;
      error.details = details;
      throw error;
    }

    return response.json();
  } finally {
    clearTimeout(timeoutId);
  }
}

app.get('/api/modalidades', async (req, res, next) => {
  try {
    const url = buildPncpUrl(PNCP_CADASTRO_BASE, 'modalidades');
    const data = await fetchJson(url);
    const ativos = Array.isArray(data)
      ? data.filter((item) => item?.statusAtivo)
      : [];
    res.json(
      ativos.map((item) => ({ id: item.id, nome: item.nome, descricao: item.descricao || null }))
    );
  } catch (error) {
    next(error);
  }
});

app.get('/api/modos-disputa', async (req, res, next) => {
  try {
    const url = buildPncpUrl(PNCP_CADASTRO_BASE, 'modos-disputas');
    const data = await fetchJson(url);
    const ativos = Array.isArray(data)
      ? data.filter((item) => item?.statusAtivo)
      : [];
    res.json(ativos.map((item) => ({ id: item.id, nome: item.nome })));
  } catch (error) {
    next(error);
  }
});

app.get('/api/contratacoes/publicacao', async (req, res, next) => {
  try {
    const dataInicial = normalizeDateParam(req.query.dataInicial, 'dataInicial');
    const dataFinal = normalizeDateParam(req.query.dataFinal, 'dataFinal');
    const pagina = toPositiveInteger(req.query.pagina ?? 1, 'pagina');
    const tamanhoPagina = req.query.tamanhoPagina
      ? toPositiveInteger(req.query.tamanhoPagina, 'tamanhoPagina')
      : undefined;

    const modalidadeId = req.query.modalidadeId
      ? Number.parseInt(req.query.modalidadeId, 10)
      : undefined;
    if (!modalidadeId) {
      throw new Error('modalidadeId é obrigatório.');
    }

    const params = {
      dataInicial,
      dataFinal,
      codigoModalidadeContratacao: modalidadeId,
      pagina,
      tamanhoPagina,
      codigoModoDisputa: req.query.modoDisputaId ? Number(req.query.modoDisputaId) : undefined,
      uf: req.query.uf || undefined,
      codigoMunicipioIbge: req.query.codigoMunicipioIbge || undefined,
      cnpj: sanitizeCnpj(req.query.cnpj),
      codigoUnidadeAdministrativa: req.query.codigoUnidadeAdministrativa || undefined,
      idUsuario: req.query.idUsuario ? Number(req.query.idUsuario) : undefined
    };

    const url = buildPncpUrl(PNCP_CONSULTA_BASE, 'contratacoes/publicacao', params);
    const data = await fetchJson(url);
    res.json(data);
  } catch (error) {
    next(error);
  }
});

app.get('/api/contratos', async (req, res, next) => {
  try {
    const dataInicial = normalizeDateParam(req.query.dataInicial, 'dataInicial');
    const dataFinal = normalizeDateParam(req.query.dataFinal, 'dataFinal');
    const pagina = toPositiveInteger(req.query.pagina ?? 1, 'pagina');
    const tamanhoPagina = req.query.tamanhoPagina
      ? toPositiveInteger(req.query.tamanhoPagina, 'tamanhoPagina')
      : undefined;

    const params = {
      dataInicial,
      dataFinal,
      pagina,
      tamanhoPagina,
      cnpjOrgao: sanitizeCnpj(req.query.cnpjOrgao || req.query.cnpj),
      codigoUnidadeAdministrativa: req.query.codigoUnidadeAdministrativa || undefined,
      usuarioId: req.query.usuarioId ? Number(req.query.usuarioId) : undefined
    };

    const url = buildPncpUrl(PNCP_CONSULTA_BASE, 'contratos', params);
    const data = await fetchJson(url);
    res.json(data);
  } catch (error) {
    next(error);
  }
});

app.get('/api/compras/detalhe', async (req, res, next) => {
  try {
    const cnpj = sanitizeCnpj(req.query.cnpj);
    if (!cnpj) {
      throw new Error('cnpj é obrigatório para consulta de detalhes.');
    }
    const ano = toPositiveInteger(req.query.ano, 'ano');
    const sequencial = toPositiveInteger(req.query.sequencial, 'sequencial');

    const relativePath = `orgaos/${cnpj}/compras/${ano}/${sequencial}`;
    const url = buildPncpUrl(PNCP_CONSULTA_BASE, relativePath);
    const data = await fetchJson(url);
    res.json(data);
  } catch (error) {
    next(error);
  }
});

app.use((error, req, res, next) => {
  const status = error.status || 400;
  const payload = {
    message: error.message || 'Erro inesperado.',
    details: error.details || null
  };
  res.status(status).json(payload);
});

app.listen(PORT, () => {
  console.log(`Servidor iniciado em http://localhost:${PORT}`);
});
