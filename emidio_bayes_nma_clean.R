# ============================================================
# Emidio - Bayes NMA (multinma): execução limpa e organizada
# Alinhado ao multinma; SIDE robusto; priors definidos por YAML
# Versão desta revisão: 2025-08-31
# ============================================================

# ----------------------------
# 0) Repositório CRAN & helpers
# ----------------------------
options(repos = c(CRAN = "https://cloud.r-project.org"), warn = 1L)

ensure_package <- function(pkg,
                           min_version = NULL,
                           prefer_github = FALSE,
                           github = NULL) {
  have_pkg <- requireNamespace(pkg, quietly = TRUE)
  if (!have_pkg) {
    message("Instalando pacote: ", pkg)
    install.packages(pkg, dependencies = TRUE)
    have_pkg <- requireNamespace(pkg, quietly = TRUE)
  }
  if (!is.null(min_version) && have_pkg) {
    cur <- utils::packageVersion(pkg)
    if (utils::compareVersion(as.character(cur), min_version) < 0) {
      have_pkg <- FALSE
    }
  }
  if ((prefer_github || !have_pkg) && !is.null(github)) {
    if (!requireNamespace("remotes", quietly = TRUE)) {
      install.packages("remotes", dependencies = TRUE)
    }
    message("Instalando/atualizando ", pkg, " via GitHub: ", github)
    remotes::install_github(github, dependencies = TRUE, upgrade = "always")
  }
  if (!requireNamespace(pkg, quietly = TRUE)) {
    stop("Falha ao carregar o pacote '", pkg, "'.")
  }
  suppressPackageStartupMessages(library(pkg, character.only = TRUE))
}

core_packages <- c(
  "tidyverse",
  "readxl",
  "janitor",
  "stringr",
  "yaml",
  "writexl",
  "rlang",
  "posterior",
  "bayesplot",
  "loo",
  "igraph",
  "ggraph",
  "rstan",
  "pkgbuild",
  "scales",
  "png",
  "ggdist",
  "ggrepel"
)

for (pkg in core_packages) {
  ensure_package(pkg)
}

if (Sys.info()[["sysname"]] == "Windows") {
  ensure_package("installr")
}

USE_GH <- identical(tolower(Sys.getenv("EMIDIO_USE_MULTINMA_GITHUB", "0")), "1")
ensure_package(
  "multinma",
  min_version = "0.8.0",
  prefer_github = USE_GH,
  github = "dmphillippo/multinma"
)

suppressPackageStartupMessages(library(grid))

# ============================================================
# [ETAPA 1] Configurações globais e diretórios
# ============================================================
`%||%` <- function(x, y) {
  if (is.null(x)) y else x
}

options(mc.cores = max(1L, parallel::detectCores() - 1L))
set.seed(20250816)

ROOT <- getwd()
DATA_DIR <- file.path(ROOT, "data_raw")
FIG_DIR <- file.path(ROOT, "figures")
DOCS_DIR <- file.path(ROOT, "docs")

for (dir in c(DATA_DIR, FIG_DIR, DOCS_DIR)) {
  dir.create(dir, showWarnings = FALSE, recursive = TRUE)
}

FOREST_DIR <- file.path(FIG_DIR, "forests")
dir.create(FOREST_DIR, showWarnings = FALSE, recursive = TRUE)
options(FOREST_DIR = FOREST_DIR)

DATA_XLSX <- "/Users/MAC/Desktop/Doutorado/Planos Estatísticos/Network Meta Analysis/Emidio-Bayes-NMA_data.xlsx"
CFG_DIR <- "/Users/MAC/Desktop/Doutorado/Planos Estatísticos/Network Meta Analysis/Yaml"

if (!file.exists(DATA_XLSX)) {
  stop("Arquivo de dados não encontrado em:\n  ", DATA_XLSX)
}

TRT_LEVELS <- c(
  "ketamine",
  "esmolol",
  "dexmedetomidine",
  "clonidine",
  "lidocaine",
  "placebo"
)

# ============================================================
# [ETAPA 2] Leitura de configurações (YAML)
# ============================================================
read_yaml_or <- function(path, default = list()) {
  if (file.exists(path)) {
    yaml::read_yaml(path)
  } else {
    default
  }
}

settings <- read_yaml_or(
  file.path(CFG_DIR, "settings.yml"),
  default = list(
    mcmc = list(
      chains = 4,
      iter_warmup = 1200,
      iter_sampling = 2500,
      adapt_delta = 0.98,
      max_treedepth = 12,
      seed = 20250816
    ),
    qa = list(
      rhat_threshold = 1.01,
      allow_divergences = 0,
      min_ess_bulk = 400,
      min_ess_tail = 200
    )
  )
)

priors_default <- list(
  priors = list(
    mme_24h = list(
      effect = list(dist = "student_t", df = 7, location = 0, scale = 10),
      tau = list(dist = "half_normal", scale = 5)
    ),
    pain_vas_6h = list(
      effect = list(dist = "student_t", df = 7, location = 0, scale = 5),
      tau = list(dist = "half_normal", scale = 2)
    ),
    opioid_free_pacu = list(
      effect = list(dist = "student_t", df = 7, location = 0, scale = 2.5),
      tau = list(dist = "half_normal", scale = 0.7)
    )
  ),
  intercepts = list(
    binomial_logit = list(dist = "normal", location = 0, scale = 10),
    normal_identity = list(dist = "normal", location = 0, scale = 1000)
  )
)

priors_yaml <- read_yaml_or(file.path(CFG_DIR, "priors.yml"), default = priors_default)

get_prior <- function(key) {
  if (!is.null(priors_yaml$priors[[key]])) {
    return(priors_yaml$priors[[key]])
  }
  if (!is.null(priors_yaml$intercepts[[key]])) {
    return(priors_yaml$intercepts[[key]])
  }
  stop("Prior não encontrado para a chave: ", key)
}

INTERCEPT_KEY_BIN <- "binomial_logit"
INTERCEPT_KEY_CONT <- "normal_identity"

# ============================================================
# [ETAPA 3] Importação e saneamento dos dados
# ============================================================
load_sheet <- function(sheet) {
  readxl::read_excel(DATA_XLSX, sheet = sheet) %>%
    janitor::clean_names()
}

standardize_treatments <- function(tbl) {
  tbl %>%
    mutate(treatment = tolower(trimws(treatment))) %>%
    mutate(
      treatment = case_when(
        treatment %in% c("dex", "dexmed", "dexmedetomidine") ~ "dexmedetomidine",
        treatment %in% c("lido", "lidocaina", "lidocaine") ~ "lidocaine",
        TRUE ~ treatment
      )
    ) %>%
    mutate(treatment = factor(treatment, levels = TRT_LEVELS))
}

studies <- load_sheet("studies")
arms_raw <- load_sheet("arms")
outcomes_raw <- load_sheet("outcomes")
covars <- load_sheet("covariates")

arms <- standardize_treatments(arms_raw)

unknown_trt <- setdiff(unique(as.character(arms$treatment)), TRT_LEVELS)
if (length(unknown_trt) > 0) {
  stop("Tratamentos não mapeados em TRT_LEVELS: ", paste(sort(unknown_trt), collapse = ", "))
}

outcomes <- outcomes_raw %>%
  mutate(
    timepoint = tolower(trimws(timepoint)),
    outcome = tolower(trimws(outcome))
  )

# ============================================================
# [ETAPA 4] Priors helpers
# ============================================================
.is_scalar_numeric <- function(x) {
  is.numeric(x) && length(x) == 1L && is.finite(x)
}

.as_num_chr <- function(x) {
  if (is.null(x)) {
    return(NA_real_)
  }
  if (is.data.frame(x)) {
    candidates <- intersect(c("p_value", "p.value", "p", "pval", "pval_std", "pval-std"), names(x))
    if (length(candidates) > 0) {
      return(suppressWarnings(as.numeric(x[[candidates[1]]])))
    }
    return(suppressWarnings(as.numeric(unlist(x, use.names = FALSE))))
  }
  if (is.list(x)) {
    return(vapply(x, function(el) {
      if (is.null(el)) {
        return(NA_real_)
      }
      if (is.data.frame(el)) {
        candidates <- intersect(c("p_value", "p.value", "p", "pval", "pval_std", "pval-std"), names(el))
        if (length(candidates) > 0) {
          val <- suppressWarnings(as.numeric(el[[candidates[1]]][1]))
          return(if (length(val)) val else NA_real_)
        }
        return(NA_real_)
      }
      if (length(el) == 0) {
        return(NA_real_)
      }
      if (is.list(el)) {
        flat <- unlist(el, recursive = TRUE, use.names = FALSE)
        if (!length(flat)) {
          return(NA_real_)
        }
        val <- suppressWarnings(as.numeric(flat[1]))
        return(if (length(val)) val else NA_real_)
      }
      val <- suppressWarnings(as.numeric(el[1]))
      if (length(val)) val else NA_real_
    }, numeric(1)))
  }
  suppressWarnings(as.numeric(as.character(x)))
}

.assert_pos <- function(x, label, ctx = "") {
  if (!.is_scalar_numeric(x) || x <= 0) {
    stop(sprintf("'%s' deve ser numérico escalar > 0 %s (valor atual: %s)",
                 label,
                 if (nzchar(ctx)) paste0(" em ", ctx) else "",
                 deparse(x)))
  }
}

.build_prior <- function(pr, ctx = "") {
  if (is.null(pr$dist)) {
    return(list(
      effect = .build_prior(pr$effect, paste0(ctx, "/effect")),
      tau = .build_prior(pr$tau, paste0(ctx, "/tau"))
    ))
  }
  pr$dist <- gsub("-", "_", tolower(pr$dist))
  if (pr$dist == "normal") {
    m <- .as_num_chr(pr$location %||% pr$mean %||% 0)
    s <- .as_num_chr(pr$scale %||% pr$sd)
    .assert_pos(s, "normal$scale", ctx)
    return(multinma::normal(location = m, scale = s))
  }
  if (pr$dist == "student_t") {
    lc <- .as_num_chr(pr$location %||% pr$loc %||% 0)
    sc <- .as_num_chr(pr$scale %||% pr$sd)
    df <- .as_num_chr(pr$df)
    .assert_pos(sc, "student_t$scale", ctx)
    .assert_pos(df, "student_t$df", ctx)
    return(multinma::student_t(location = lc, scale = sc, df = df))
  }
  if (pr$dist == "half_normal") {
    sc <- .as_num_chr(pr$scale %||% pr$sd)
    .assert_pos(sc, "half_normal$scale", ctx)
    return(multinma::half_normal(scale = sc))
  }
  if (pr$dist == "half_student_t") {
    sc <- .as_num_chr(pr$scale %||% pr$sd)
    df <- .as_num_chr(pr$df)
    .assert_pos(sc, "half_student_t$scale", ctx)
    .assert_pos(df, "half_student_t$df", ctx)
    return(multinma::half_student_t(scale = sc, df = df))
  }
  stop("Distribuição não suportada: ", pr$dist, if (nzchar(ctx)) paste0(" em ", ctx))
}

.build_intercept_prior <- function(is_binary) {
  if (is_binary) {
    .build_prior(get_prior(INTERCEPT_KEY_BIN), "intercept_binomial")
  } else if (!is.null(priors_yaml$intercepts[[INTERCEPT_KEY_CONT]])) {
    .build_prior(priors_yaml$intercepts[[INTERCEPT_KEY_CONT]], "intercept_normal")
  } else {
    multinma::normal(location = 0, scale = 1000)
  }
}

# ============================================================
# [ETAPA 5] Rede AGD-arm e conectividade
# ============================================================
.edges_from_dat <- function(dat) {
  required_cols <- c("study_id", "treatment")
  if (!all(required_cols %in% names(dat))) {
    return(tibble::tibble())
  }
  dat %>%
    select(study_id, treatment) %>%
    distinct() %>%
    split(.$study_id) %>%
    lapply(function(x) {
      trts <- unique(as.character(x$treatment))
      if (length(trts) < 2L) {
        return(NULL)
      }
      combos <- utils::combn(trts, 2L)
      tibble::tibble(
        study_id = unique(x$study_id),
        treat1 = combos[1, ],
        treat2 = combos[2, ]
      )
    }) %>%
    bind_rows()
}

network_is_disconnected <- function(dat) {
  edges <- .edges_from_dat(dat)
  if (!nrow(edges)) {
    return(TRUE)
  }
  graph <- igraph::graph_from_data_frame(edges[, c("treat1", "treat2")], directed = FALSE)
  igraph::components(graph)$no > 1L
}

make_agd_arm <- function(outcome_id, tp = NULL) {
  dat <- outcomes %>%
    filter(.data$outcome == outcome_id) %>%
    { if (!is.null(tp)) filter(., .data$timepoint == tolower(tp)) else . } %>%
    left_join(arms, by = c("study_id", "arm_id"), suffix = c("", ".arm")) %>%
    left_join(studies, by = "study_id") %>%
    mutate(
      n = coalesce(.data$total, .data$n),
      se = .data$sd / sqrt(.data$n)
    )
  
  required_cols <- c("study_id", "arm_id", "treatment", "n")
  if (!all(required_cols %in% names(dat))) {
    stop("Colunas ausentes em outcomes/arms para ", outcome_id, " @", tp)
  }
  
  is_binary <- all(c("events", "n") %in% names(dat)) && any(!is.na(dat$events))
  
  if (is_binary) {
    n_before <- nrow(dat)
    dat <- dat %>%
      filter(!is.na(events), !is.na(n)) %>%
      mutate(
        events = as.integer(events),
        n = as.integer(n)
      )
    if (any(dat$events < 0 | dat$events > dat$n)) {
      stop("Eventos inválidos em desfecho binário (fora de [0, n]).")
    }
    removed <- n_before - nrow(dat)
    if (removed > 0) {
      message("Braços binários removidos (dados ausentes): ", removed)
    }
    agd <- multinma::set_agd_arm(
      data = dat,
      study = study_id,
      trt = treatment,
      r = events,
      n = n,
      trt_ref = "placebo"
    )
    return(list(agd = agd, family = "binomial", link = "logit", dat = dat))
  }
  
  required_cont <- c("mean", "sd", "n")
  if (!all(required_cont %in% names(dat))) {
    stop("Para contínuos são necessários mean, sd, n em ", outcome_id, " @", tp)
  }
  n_before <- nrow(dat)
  dat <- dat %>%
    filter(is.finite(mean), is.finite(se), is.finite(n), se > 0)
  removed <- n_before - nrow(dat)
  if (removed > 0) {
    message("Braços contínuos removidos (dados inválidos): ", removed)
  }
  agd <- multinma::set_agd_arm(
    data = dat,
    study = study_id,
    trt = treatment,
    y = mean,
    se = se,
    sample_size = n,
    trt_ref = "placebo"
  )
  list(agd = agd, family = "normal", link = "identity", dat = dat)
}

make_network <- function(agd_obj) {
  multinma::combine_network(agd_obj)
}

# ============================================================
# [ETAPA 6] Resumos + SUCRA
# ============================================================
exp_quantiles_if_binary <- function(df, is_binary) {
  if (!is_binary) {
    return(df)
  }
  qcols <- intersect(c("2.5%", "50%", "97.5%", "mean", "Median", "median"), names(df))
  for (nm in qcols) {
    df[[nm]] <- exp(df[[nm]])
  }
  df
}

summarise_nma <- function(fit,
                          ref = "placebo",
                          is_binary = FALSE,
                          outcome_id = NULL) {
  relative_ref <- multinma::relative_effects(fit, trt_ref = ref)
  pairwise_tbl <- tibble::as_tibble(relative_ref)
  pairwise_tbl <- exp_quantiles_if_binary(pairwise_tbl, is_binary)

  relative_all <- multinma::relative_effects(fit, all_contrasts = TRUE)
  all_contrasts_tbl <- tibble::as_tibble(relative_all)
  all_contrasts_tbl <- exp_quantiles_if_binary(all_contrasts_tbl, is_binary)

  lower_better <- !(tolower(outcome_id %||% "") %in% c("opioid_free", "opioid_free_pacu"))
  ranks_summary <- multinma::posterior_ranks(
    fit,
    lower_better = lower_better,
    summary = TRUE,
    sucra = TRUE
  )
  ranks_tbl <- tryCatch(
    tibble::as_tibble(ranks_summary),
    error = function(e) tibble::as_tibble(as.data.frame(ranks_summary))
  )

  list(
    pairwise = pairwise_tbl,
    all_contrasts = all_contrasts_tbl,
    ranks = ranks_tbl
  )
}

# ============================================================
# [ETAPA 7] Forest pairwise — estilo “RevMan”
# ============================================================
save_forest_pairwise <- function(fit, is_binary, outcome_id, timepoint, ref) {
  rel <- try(multinma::relative_effects(fit, trt_ref = ref), silent = TRUE)
  if (inherits(rel, "try-error")) {
    message("[pairwise forest] Falha ao obter efeitos relativos — pulando.")
    return(invisible(NULL))
  }

  p <- try(plot(rel, ref_line = if (is_binary) 1 else 0), silent = TRUE)
  if (inherits(p, "try-error")) {
    message("[pairwise forest] plot() indisponível para o objeto retornado — pulando.")
    return(invisible(NULL))
  }

  p <- p + ggplot2::labs(
    title = sprintf("Contrastes vs %s — %s @ %s", ref, outcome_id, timepoint %||% "NA"),
    x = if (is_binary) "OR (vs ref)" else "MD (vs ref)"
  )

  out_path <- file.path(
    FOREST_DIR,
    sprintf("forest_%s_%s_vsref_%s.png", outcome_id, timepoint %||% "NA", ref)
  )
  ggplot2::ggsave(out_path, p, width = 7.8, height = 5.2, dpi = 300, bg = "white")
  invisible(p)
}

# ============================================================
# [ETAPA 8] Ajuste do modelo, QC e node-splitting (SIDE)
# ============================================================
.get_stanfit <- function(fit) {
  sf <- try(as.stanfit(fit), silent = TRUE)
  if (inherits(sf, "try-error")) {
    return(NULL)
  }
  sf
}

qc_nma <- function(fit, qa = settings$qa, outcome_key = "<na>") {
  sf <- .get_stanfit(fit)
  if (is.null(sf)) {
    message("[QC] Objeto stanfit não acessível via as.stanfit() para ", outcome_key)
    return(list(fail = FALSE, rhat = NA_real_, ess_min = NA_real_, divergences = NA_integer_))
  }
  sm <- rstan::summary(sf)$summary
  rhat_max <- suppressWarnings(max(sm[, "Rhat"], na.rm = TRUE))
  ess_min <- suppressWarnings(min(sm[, "n_eff"], na.rm = TRUE))
  sampler <- try(rstan::get_sampler_params(sf, inc_warmup = FALSE), silent = TRUE)
  n_div <- if (inherits(sampler, "try-error")) {
    NA_integer_
  } else {
    sum(vapply(sampler, function(x) sum(x[, "divergent__"]), numeric(1)))
  }
  fail <- (is.finite(rhat_max) && rhat_max > qa$rhat_threshold) ||
    (is.finite(ess_min) && ess_min < qa$min_ess_bulk) ||
    (is.finite(n_div) && n_div > qa$allow_divergences)
  if (fail) {
    stop(sprintf(
      "Falha QA [%s]: Rhat=%.3f, n_eff_min=%.1f, divergences=%s",
      outcome_key,
      rhat_max,
      ess_min,
      as.character(n_div)
    ))
  }
  list(fail = FALSE, rhat = rhat_max, ess_min = ess_min, divergences = n_div)
}

nodesplit_check <- function(net,
                            fit_consistent,
                            outcome_key,
                            is_binary,
                            mcmc = settings$mcmc) {
  plan <- try(multinma::get_nodesplits(net), silent = TRUE)
  if (inherits(plan, "try-error") || is.null(plan)) {
    message("[Node-splitting] Plano indisponível — pulando ajuste.")
    return(list(object = NULL, summary = NULL, table = tibble::tibble(), plan = NULL))
  }

  n_plan <- NROW(plan)
  message("[Node-splitting] Total de splits: ", n_plan)
  if (n_plan == 0L) {
    return(list(object = NULL, summary = NULL, table = tibble::tibble(), plan = plan))
  }

  pr <- get_prior(outcome_key)
  pr_int <- .build_intercept_prior(is_binary)
  iter_total <- mcmc$iter_warmup + mcmc$iter_sampling
  ctrl <- list()
  if (!is.null(mcmc$max_treedepth)) {
    ctrl$max_treedepth <- mcmc$max_treedepth
  }

  fit_ns <- multinma::nma(
    net,
    trt_effects = "random",
    consistency = "nodesplit",
    nodesplit = plan,
    prior_intercept = pr_int,
    prior_trt = .build_prior(pr$effect, paste0(outcome_key, " (effect)")),
    prior_het = .build_prior(pr$tau, paste0(outcome_key, " (tau)")),
    adapt_delta = mcmc$adapt_delta,
    chains = mcmc$chains,
    iter = iter_total,
    warmup = mcmc$iter_warmup,
    seed = mcmc$seed,
    control = ctrl
  )

  ns_summary <- try(summary(fit_ns, consistency = fit_consistent), silent = TRUE)
  if (inherits(ns_summary, "try-error")) {
    cond <- attr(ns_summary, "condition")
    msg <- if (inherits(cond, "condition")) conditionMessage(cond) else "erro desconhecido"
    message("[Node-splitting] summary() falhou: ", msg)
    ns_summary <- NULL
  }

  ns_table <- tibble::tibble()
  if (!is.null(ns_summary)) {
    ns_table <- try(tibble::as_tibble(ns_summary), silent = TRUE)
    if (inherits(ns_table, "try-error")) {
      ns_table <- tibble::tibble()
    }
  }

  list(object = fit_ns, summary = ns_summary, table = ns_table, plan = plan)
}


fit_nma <- function(net, outcome_key, is_binary, mcmc = settings$mcmc) {
  message(">> Ajustando NMA para ", outcome_key)
  pr <- get_prior(outcome_key)
  pr_tau <- .build_prior(pr$tau, paste0(outcome_key, " (tau)"))
  pr_eff <- .build_prior(pr$effect, paste0(outcome_key, " (effect)"))
  pr_int <- .build_intercept_prior(is_binary)
  iter_total <- mcmc$iter_warmup + mcmc$iter_sampling
  ctrl <- list()
  if (!is.null(mcmc$max_treedepth)) {
    ctrl$max_treedepth <- mcmc$max_treedepth
  }
  multinma::nma(
    net,
    trt_effects = "random",
    prior_intercept = pr_int,
    prior_trt = pr_eff,
    prior_het = pr_tau,
    adapt_delta = mcmc$adapt_delta,
    chains = mcmc$chains,
    iter = iter_total,
    warmup = mcmc$iter_warmup,
    seed = mcmc$seed,
    control = ctrl
  )
}

run_one <- function(outcome_id, timepoint = NULL, ref = "placebo") {
  key <- tolower(paste0(outcome_id, if (!is.null(timepoint)) paste0("_", timepoint) else ""))
  message(">> Executando rotina para ", key)
  agd_info <- make_agd_arm(outcome_id, timepoint)
  if (network_is_disconnected(agd_info$dat)) {
    stop("Rede desconectada para ", key, " — verifique tratamentos/estudos.")
  }
  net <- make_network(agd_info$agd)
  network_plot_path <- file.path(FIG_DIR, paste0("network_", outcome_id, "_", timepoint %||% "NA", ".png"))
  ggplot2::ggsave(
    filename = network_plot_path,
    plot = plot(net),
    width = 7,
    height = 5,
    dpi = 300,
    bg = "white"
  )
  fit <- fit_nma(net, outcome_key = key, is_binary = (agd_info$family == "binomial"))
  qc_nma(fit, outcome_key = key)
  sm <- summarise_nma(
    fit,
    ref = ref,
    is_binary = (agd_info$family == "binomial"),
    outcome_id = outcome_id
  )
  ns <- nodesplit_check(
    net,
    fit_consistent = fit,
    outcome_key = key,
    is_binary = (agd_info$family == "binomial")
  )
  save_forest_pairwise(
    fit,
    is_binary = (agd_info$family == "binomial"),
    outcome_id = outcome_id,
    timepoint = timepoint,
    ref = ref
  )
  list(
    net = net,
    fit = fit,
    pairwise = sm$pairwise,
    all_contrasts = sm$all_contrasts,
    ranks = sm$ranks,
    nodesplit = ns
  )
}

writetbl <- function(x, path) {
  readr::write_csv(dplyr::as_tibble(x), path)
}


# ============================================================
# [ETAPA 9] Execução principal + exportação
# ============================================================
res_mme_24h <- run_one(outcome_id = "mme", timepoint = "24h", ref = "placebo")
res_pain_vas_6h <- run_one(outcome_id = "pain_vas", timepoint = "6h", ref = "placebo")
res_opioid_free <- run_one(outcome_id = "opioid_free", timepoint = "pacu", ref = "placebo")

writetbl(res_mme_24h$pairwise, file.path(DOCS_DIR, "re_vs_ref_mme_24h.csv"))
writetbl(res_mme_24h$all_contrasts, file.path(DOCS_DIR, "re_allcontrasts_mme_24h.csv"))
writetbl(res_mme_24h$ranks, file.path(DOCS_DIR, "ranks_mme_24h.csv"))
writetbl(res_pain_vas_6h$pairwise, file.path(DOCS_DIR, "re_vs_ref_pain_vas_6h.csv"))
writetbl(res_pain_vas_6h$all_contrasts, file.path(DOCS_DIR, "re_allcontrasts_pain_vas_6h.csv"))
writetbl(res_opioid_free$pairwise, file.path(DOCS_DIR, "re_vs_ref_opioid_free_pacu.csv"))
writetbl(res_opioid_free$all_contrasts, file.path(DOCS_DIR, "re_allcontrasts_opioid_free_pacu.csv"))

# -----------------------------
# Node-splitting — TXT + CSV achatado
# -----------------------------
if (!is.null(res_mme_24h$nodesplit$summary)) {
  capture.output(
    print(res_mme_24h$nodesplit$summary),
    file = file.path(DOCS_DIR, "nodesplit_mme_24h.txt")
  )
}
if (!is.null(res_mme_24h$nodesplit$table) && nrow(res_mme_24h$nodesplit$table)) {
  readr::write_csv(res_mme_24h$nodesplit$table, file.path(DOCS_DIR, "nodesplit_mme_24h.csv"))
}
if (!is.null(res_pain_vas_6h$nodesplit$summary)) {
  capture.output(
    print(res_pain_vas_6h$nodesplit$summary),
    file = file.path(DOCS_DIR, "nodesplit_pain_vas_6h.txt")
  )
}
if (!is.null(res_pain_vas_6h$nodesplit$table) && nrow(res_pain_vas_6h$nodesplit$table)) {
  readr::write_csv(res_pain_vas_6h$nodesplit$table, file.path(DOCS_DIR, "nodesplit_pain_vas_6h.csv"))
}
if (!is.null(res_opioid_free$nodesplit$summary)) {
  capture.output(
    print(res_opioid_free$nodesplit$summary),
    file = file.path(DOCS_DIR, "nodesplit_opioid_free_pacu.txt")
  )
}
if (!is.null(res_opioid_free$nodesplit$table) && nrow(res_opioid_free$nodesplit$table)) {
  readr::write_csv(res_opioid_free$nodesplit$table, file.path(DOCS_DIR, "nodesplit_opioid_free_pacu.csv"))
}


# ============================================================
# [ETAPA 10] Node-splitting — plots básicos
# ============================================================
NODE_DIR <- file.path(FIG_DIR, "nodesplit")
dir.create(NODE_DIR, showWarnings = FALSE, recursive = TRUE)

save_nodesplit_plots <- function(ns_summary, outcome_id, timepoint = NULL) {
  if (is.null(ns_summary)) {
    message("[nodesplit] Resumo inexistente — gráficos não gerados.")
    return(invisible(NULL))
  }

  base_tag <- tolower(paste0(outcome_id, "_", timepoint %||% "NA"))
  plots_obj <- try(plot(ns_summary), silent = TRUE)
  if (inherits(plots_obj, "try-error")) {
    cond <- attr(plots_obj, "condition")
    msg <- if (inherits(cond, "condition")) conditionMessage(cond) else "erro desconhecido"
    message("[nodesplit] plot() falhou: ", msg)
    return(invisible(NULL))
  }

  store <- list()
  if (inherits(plots_obj, c("ggplot", "patchwork"))) {
    store[["overall"]] <- plots_obj
  } else if (is.list(plots_obj)) {
    store <- plots_obj
  } else {
    store[["nodesplit"]] <- plots_obj
  }

  nms <- names(store)
  if (is.null(nms)) {
    nms <- rep("", length(store))
  }
  for (idx in seq_along(store)) {
    nm <- nms[idx]
    obj <- store[[idx]]
    if (!inherits(obj, c("ggplot", "patchwork"))) {
      next
    }
    suffix <- if (!is.null(nm) && nzchar(nm)) nm else sprintf("plot%02d", idx)
    out_path <- file.path(NODE_DIR, paste0("nodesplit_", base_tag, "_", suffix, ".png"))
    ggplot2::ggsave(out_path, obj, width = 8, height = 6, dpi = 300, bg = "white")
    message("[nodesplit] Figura salva em: ", out_path)
  }

  invisible(NULL)
}


save_nodesplit_plots(res_mme_24h$nodesplit$summary, "mme", "24h")
save_nodesplit_plots(res_pain_vas_6h$nodesplit$summary, "pain_vas", "6h")
save_nodesplit_plots(res_opioid_free$nodesplit$summary, "opioid_free", "pacu")



# ============================================================
# [MÓDULO DE NI] — Esmolol vs Outros (MME 24h)
# ============================================================
suppressPackageStartupMessages({
  if (!requireNamespace("ggplot2", quietly = TRUE)) stop("Falta ggplot2.")
  if (!requireNamespace("dplyr", quietly = TRUE)) stop("Falta dplyr.")
  if (!requireNamespace("tibble", quietly = TRUE)) stop("Falta tibble.")
  if (!requireNamespace("readr", quietly = TRUE)) stop("Falta readr.")
})

if (!exists("FIG_DIR", inherits = TRUE) || is.null(FIG_DIR) || !nzchar(FIG_DIR)) {
  FIG_DIR <- file.path(getwd(), "figures")
}
if (!exists("DOCS_DIR", inherits = TRUE) || is.null(DOCS_DIR) || !nzchar(DOCS_DIR)) {
  DOCS_DIR <- file.path(getwd(), "docs")
}
dir.create(FIG_DIR, showWarnings = FALSE, recursive = TRUE)
dir.create(DOCS_DIR, showWarnings = FALSE, recursive = TRUE)

if (exists("settings", inherits = TRUE) && !is.null(settings$ni)) {
  NI_DELTA <- settings$ni$delta_mg %||% 4
  NI_THRESH <- settings$ni$prob_threshold %||% 0.90
} else {
  NI_DELTA <- 4
  NI_THRESH <- 0.90
}

.get_draws_vs_ref <- function(fit, trt_ref, trt_target) {
  re <- try(multinma::relative_effects(fit, trt_ref = trt_ref, summary = FALSE), silent = TRUE)
  if (inherits(re, "try-error") || is.null(re) || is.null(re$sims)) {
    return(NULL)
  }
  arr <- re$sims
  pnames <- dimnames(arr)[[3]]
  if (is.null(pnames)) {
    return(NULL)
  }
  target <- sprintf("d[%s]", trt_target)
  norm <- function(x) gsub("\\s+", " ", tolower(trimws(x)))
  pos <- which(norm(pnames) == norm(target))
  if (length(pos) != 1L) {
    return(NULL)
  }
  as.numeric(c(arr[, , pos]))
}

compute_ni_esmolol <- function(fit,
                               delta_mg = 4,
                               prob_threshold = 0.90,
                               trt_test = "esmolol",
                               comparators = NULL) {
  stopifnot(is.numeric(delta_mg), length(delta_mg) == 1L,
            is.numeric(prob_threshold), length(prob_threshold) == 1L)
  if (is.null(comparators)) {
    comparators <- if (exists("TRT_LEVELS", inherits = TRUE)) {
      setdiff(tolower(TRT_LEVELS), tolower(trt_test))
    } else {
      setdiff(c("ketamine", "esmolol", "dexmedetomidine", "clonidine", "lidocaine", "placebo"), tolower(trt_test))
    }
  }
  out <- list()
  k <- 0L
  for (comp in comparators) {
    draws <- .get_draws_vs_ref(fit, trt_ref = comp, trt_target = trt_test)
    if (is.null(draws)) {
      message("[NI] Sem draws para '", trt_test, "' vs '", comp, "' — pulando.")
      next
    }
    draws <- draws[is.finite(draws)]
    if (!length(draws)) {
      next
    }
    p_NI <- mean(draws <= delta_mg)
    qs <- stats::quantile(draws, c(0.025, 0.5, 0.975), names = FALSE, type = 7, na.rm = TRUE)
    k <- k + 1L
    out[[k]] <- tibble::tibble(
      trt_test = trt_test,
      comparator = comp,
      delta_mg = delta_mg,
      prob_threshold = prob_threshold,
      p_NI = p_NI,
      dec_NI = p_NI >= prob_threshold,
      mean_MD = mean(draws),
      sd_MD = stats::sd(draws),
      q2.5_MD = qs[1],
      q50_MD = qs[2],
      q97.5_MD = qs[3],
      NI_CrI = (qs[3] <= delta_mg)
    )
  }
  if (k == 0L) {
    tibble::tibble(
      trt_test = character(),
      comparator = character(),
      delta_mg = double(),
      prob_threshold = double(),
      p_NI = double(),
      dec_NI = logical(),
      mean_MD = double(),
      sd_MD = double(),
      q2.5_MD = double(),
      q50_MD = double(),
      q97.5_MD = double(),
      NI_CrI = logical()
    )
  } else {
    dplyr::bind_rows(out)
  }
}

plot_forest_ni_esmolol <- function(ni_tbl, delta_mg = 4, out_path = NULL) {
  if (is.null(ni_tbl) || !nrow(ni_tbl)) {
    message("[Forest NI] Tabela vazia — nada a plotar.")
    return(invisible(NULL))
  }
  lab_up <- function(x) toupper(gsub("_", " ", x))
  trt_header <- lab_up(unique(ni_tbl$trt_test)[1])
  fmt_num <- function(x) formatC(x, digits = 2, format = "f", drop0trailing = FALSE)
  prec <- 1 / (ni_tbl$sd_MD^2)
  prec[!is.finite(prec)] <- NA_real_
  prec <- scales::rescale(prec, to = c(3.2, 6.2), from = range(prec, na.rm = TRUE))
  df <- ni_tbl %>%
    mutate(
      comp_label = factor(lab_up(comparator), levels = lab_up(comparator)[order(mean_MD)]),
      eff_txt = sprintf("%s  [%s; %s]", fmt_num(mean_MD), fmt_num(q2.5_MD), fmt_num(q97.5_MD)),
      pni_txt = sprintf("P(NI)=%.2f", p_NI),
      sq_size = prec
    ) %>%
    arrange(mean_MD)
  df$y <- as.numeric(df$comp_label)
  stripes <- df %>%
    mutate(ymin = y - 0.5, ymax = y + 0.5) %>%
    filter((y %% 2) == 0) %>%
    select(ymin, ymax)
  xmin <- min(c(df$q2.5_MD, 0, delta_mg), na.rm = TRUE)
  xmax <- max(c(df$q97.5_MD, 0, delta_mg), na.rm = TRUE)
  span <- xmax - xmin + 1e-9
  pad <- 0.10 * span
  x_txt1 <- xmax + 0.35 * span
  x_txt2 <- x_txt1 + 0.65 * span
  p <- ggplot2::ggplot(df, ggplot2::aes(y = comp_label)) +
    ggplot2::geom_rect(
      data = stripes,
      ggplot2::aes(ymin = ymin, ymax = ymax),
      xmin = -Inf,
      xmax = Inf,
      inherit.aes = FALSE,
      fill = "grey97",
      colour = NA
    ) +
    ggplot2::geom_vline(xintercept = 0, linetype = "solid", linewidth = 0.8, alpha = 0.95) +
    ggplot2::geom_vline(xintercept = delta_mg, linetype = "dashed", linewidth = 0.9, alpha = 0.95) +
    ggplot2::geom_segment(ggplot2::aes(x = q2.5_MD, xend = q97.5_MD), linewidth = 0.9) +
    ggplot2::geom_point(
      ggplot2::aes(x = mean_MD, size = sq_size),
      shape = 22,
      stroke = 0.7,
      colour = "black",
      fill = "grey70",
      show.legend = FALSE
    ) +
    ggplot2::geom_point(
      ggplot2::aes(x = mean_MD),
      shape = 3,
      size = 2.3,
      stroke = 0.7,
      colour = "black"
    ) +
    ggplot2::geom_text(ggplot2::aes(x = x_txt1, label = eff_txt), hjust = 0, size = 3.6) +
    ggplot2::geom_text(ggplot2::aes(x = x_txt2, label = pni_txt), hjust = 0, size = 3.6) +
    ggplot2::coord_cartesian(xlim = c(xmin - pad, x_txt2 + 0.18 * span), clip = "off") +
    ggplot2::theme_minimal(base_size = 12) +
    ggplot2::theme(
      panel.grid.major.y = ggplot2::element_blank(),
      panel.grid.minor = ggplot2::element_blank(),
      panel.grid.major.x = ggplot2::element_line(colour = "grey85", linewidth = 0.4),
      axis.title.y = ggplot2::element_blank(),
      axis.ticks.y = ggplot2::element_blank(),
      axis.text.y = ggplot2::element_text(hjust = 0),
      plot.margin = ggplot2::margin(10, 80, 35, 10)
    ) +
    ggplot2::labs(
      title = sprintf("'%s' vs other", trt_header),
      x = "MD (Esmolol − Comparador) em MME (mg)"
    )
  if (!is.null(out_path)) {
    ggplot2::ggsave(filename = out_path, plot = p, width = 8.4, height = 5.4, dpi = 300, bg = "white")
  }
  invisible(p)
}

stopifnot(!is.null(res_mme_24h$fit))
ni_mme_esmolol <- compute_ni_esmolol(
  fit = res_mme_24h$fit,
  delta_mg = NI_DELTA,
  prob_threshold = NI_THRESH,
  trt_test = "esmolol"
)
readr::write_csv(ni_mme_esmolol, file.path(DOCS_DIR, "NI_esmolol_vs_others_MME_24h.csv"))
if (nrow(ni_mme_esmolol)) {
  plot_forest_ni_esmolol(
    ni_mme_esmolol,
    delta_mg = NI_DELTA,
    out_path = file.path(FIG_DIR, "forest_NI_esmolol_MME_24h.png")
  )
  message("[NI] Tabela:  ", file.path(DOCS_DIR, "NI_esmolol_vs_others_MME_24h.csv"))
  message("[NI] Figura:   ", file.path(FIG_DIR, "forest_NI_esmolol_MME_24h.png"))
} else {
  message("[NI] Sem comparadores válidos para esmolol em MME 24h.")
}


# ============================================================
# [Subsessão] Forests por referência (X vs others)
# ============================================================
ensure_package("ggridges")
ensure_package("glue")

VERBOSE <- TRUE
say <- function(fmt, ...) {
  if (isTRUE(VERBOSE)) {
    cat(">> ", sprintf(fmt, ...), "\n", sep = "")
  }
}

WHICH_RES <- res_mme_24h
OUTCOME_ID <- "mme"
TP_LABEL <- "24h"
OUTCOME_IS_BINARY <- FALSE
NI_VALUE <- if (exists("NI_DELTA", inherits = TRUE)) NI_DELTA else 4
ALPHA_RIDGES <- 0.35

.sanitize_trt <- function(x) {
  gsub("\\s+", " ", trimws(x))
}

.escape_regex <- function(x) {
  gsub("([][()^$.|?*+{}\\\\])", "\\\\\\1", x)
}

.get_all_contrasts_draws <- function(fit) {
  say("Extraindo draws: all_contrasts=TRUE, summary=FALSE")
  re_all <- multinma::relative_effects(fit, all_contrasts = TRUE, summary = FALSE)
  arr <- re_all$sims
  stopifnot(is.array(arr) && length(dim(arr)) == 3L)
  p <- dimnames(arr)[[3]]
  if (is.null(p)) {
    stop("Não há nomes de parâmetros nos draws (all_contrasts).")
  }
  labs <- .sanitize_trt(gsub("^d\\[|^delta_new\\[|\\]$", "", p))
  list(arr = arr, labs = labs)
}

.long_for_ref <- function(arr, labs, REF) {
  stopifnot(is.array(arr), length(dim(arr)) == 3L)
  pat_ref <- paste0("\\s+vs\\.?\\s*", .escape_regex(REF), "$")
  keep <- grepl(pat_ref, labs, ignore.case = TRUE)
  if (!any(keep)) {
    return(NULL)
  }
  arrK <- arr[, , keep, drop = FALSE]
  labsK <- labs[keep]
  if (length(labsK) != dim(arrK)[3]) {
    dn <- dimnames(arrK)[[3]]
    if (!is.null(dn)) {
      labsK <- .sanitize_trt(gsub("^d\\[|^delta_new\\[|\\]$", "", dn))
    }
  }
  labsK <- make.unique(labsK, sep = "..dup")
  dims <- dim(arrK)
  mat <- matrix(arrK, nrow = dims[1] * dims[2], ncol = dims[3], byrow = FALSE)
  dfw <- as.data.frame(mat)
  if (ncol(dfw) != length(labsK)) {
    stop(sprintf(
      "Inconsistência nos rótulos: ncol(dfw)=%d, length(labsK)=%d.",
      ncol(dfw),
      length(labsK)
    ))
  }
  names(dfw) <- labsK
  dfw$.draw <- seq_len(nrow(dfw))
  tidyr::pivot_longer(dfw, cols = - .draw, names_to = "Author", values_to = "b_Intercept") %>%
    mutate(Author = .sanitize_trt(Author))
}

.pure_density_or_null <- function(x, n = 512, bw = "nrd0") {
  x <- as.numeric(x)
  x <- x[is.finite(x)]
  if (length(unique(x)) < 2L) {
    return(NULL)
  }
  d <- stats::density(x, n = n, bw = bw)
  if (!all(is.finite(d$y))) {
    return(NULL)
  }
  d
}

.make_ridge_df <- function(forest.data, q_lo = 0.025, q_hi = 0.975, n = 512) {
  by_author <- split(forest.data$b_Intercept, forest.data$Author, drop = TRUE)
  out <- vector("list", length(by_author))
  k <- 0L
  for (nm in names(by_author)) {
    v <- by_author[[nm]]
    d <- .pure_density_or_null(v, n = n)
    if (is.null(d)) {
      say("   - [ridge: %s] sem densidade (variância insuficiente)", nm)
      next
    }
    qs <- stats::quantile(v[is.finite(v)], c(q_lo, q_hi))
    seg <- ifelse(d$x < qs[1], "L", ifelse(d$x > qs[2], "R", "M"))
    zone <- ifelse(seg == "M", "body", "tail")
    k <- k + 1L
    out[[k]] <- tibble::tibble(
      Author = nm,
      x = d$x,
      height = d$y,
      seg = seg,
      zone = zone,
      seg_id = paste(nm, seg, sep = "::")
    )
  }
  if (k == 0L) {
    tibble::tibble(Author = character(), x = double(), height = double(), seg = character(), zone = character(), seg_id = character())
  } else {
    dplyr::bind_rows(out)
  }
}

.plot_forest_for_ref <- function(forest.data,
                                 REF,
                                 outcome_id,
                                 tp_label,
                                 outcome_is_binary,
                                 ni_value,
                                 fig_dir,
                                 file_tag = NULL) {
  if (outcome_is_binary) {
    forest.data$b_Intercept <- exp(forest.data$b_Intercept)
  }
  forest.data.summary <- forest.data %>%
    dplyr::group_by(Author) %>%
    dplyr::summarise(
      b_Intercept = mean(b_Intercept, na.rm = TRUE),
      .lower = stats::quantile(b_Intercept, 0.025, na.rm = TRUE),
      .upper = stats::quantile(b_Intercept, 0.975, na.rm = TRUE),
      .groups = "drop"
    )
  x0 <- if (outcome_is_binary) 1 else 0
  xlab <- if (outcome_is_binary) sprintf("Odds Ratio (vs %s)", REF) else sprintf("Mean Difference (vs %s)", REF)
  ni_vals <- if (is.finite(ni_value)) ni_value else numeric(0)
  rng <- range(c(forest.data.summary$.lower, forest.data.summary$.upper, x0, ni_vals), na.rm = TRUE)
  xmin <- rng[1]
  xmax <- rng[2]
  span <- xmax - xmin + 1e-9
  pad <- 0.10 * span
  x_text <- xmax + 0.40 * span
  lbl_df <- forest.data.summary %>%
    dplyr::mutate(
      b_Intercept = round(b_Intercept, 2),
      .lower = round(.lower, 2),
      .upper = round(.upper, 2),
      lbl = glue::glue("{b_Intercept} [{.lower}, {.upper}]")
    )
  ridge_df <- .make_ridge_df(forest.data, q_lo = 0.025, q_hi = 0.975, n = 512)
  layer_NI <- if (is.finite(ni_value)) {
    ggplot2::geom_vline(xintercept = ni_value, linetype = "dashed", linewidth = 0.9, alpha = 0.95)
  } else {
    NULL
  }
  p <- ggplot2::ggplot() +
    ggplot2::geom_vline(xintercept = x0, color = "black", linewidth = 1) +
    layer_NI +
    ggridges::geom_ridgeline(
      data = ridge_df[ridge_df$zone == "body", , drop = FALSE],
      ggplot2::aes(x = x, y = forcats::fct_inorder(Author), height = height, group = seg_id),
      fill = "grey70",
      alpha = ALPHA_RIDGES,
      color = NA,
      scale = 1
    ) +
    ggridges::geom_ridgeline(
      data = ridge_df[ridge_df$zone == "tail", , drop = FALSE],
      ggplot2::aes(x = x, y = forcats::fct_inorder(Author), height = height, group = seg_id),
      fill = "red3",
      alpha = ALPHA_RIDGES,
      color = NA,
      scale = 1
    ) +
    ggplot2::geom_segment(
      data = forest.data.summary,
      ggplot2::aes(
        y = forcats::fct_inorder(Author),
        yend = forcats::fct_inorder(Author),
        x = .lower,
        xend = .upper
      ),
      linewidth = 0.9
    ) +
    ggplot2::geom_point(
      data = forest.data.summary,
      ggplot2::aes(y = forcats::fct_inorder(Author), x = b_Intercept),
      shape = 21,
      size = 2.8,
      stroke = 0.6,
      fill = "white"
    ) +
    ggplot2::geom_text(
      data = lbl_df,
      ggplot2::aes(x = x_text, y = forcats::fct_inorder(Author), label = lbl),
      hjust = 0,
      size = 3.3
    ) +
    ggplot2::labs(
      title = sprintf("Forest (posterior) — %s @ %s — %s vs others", outcome_id, tp_label, toupper(REF)),
      x = xlab,
      y = NULL
    ) +
    ggplot2::coord_cartesian(xlim = c(xmin - pad, x_text + 0.2 * span), clip = "off") +
    ggplot2::theme_minimal() +
    ggplot2::theme(plot.margin = ggplot2::margin(10, 80, 10, 10))
  forest_dir <- getOption("FOREST_DIR", default = file.path(fig_dir, "forests"))
  dir.create(forest_dir, showWarnings = FALSE, recursive = TRUE)
  tag <- if (length(file_tag) && nzchar(file_tag)) paste0("__", file_tag) else ""
  ref_safe <- gsub("[^a-z0-9]+", "_", tolower(REF))
  out_path <- file.path(
    forest_dir,
    sprintf("forest_like_%s_%s%s__ref_%s.png", outcome_id, tp_label, tag, ref_safe)
  )
  ggplot2::ggsave(out_path, p, width = 8, height = 6, dpi = 300, bg = "white")
  say("[REF=%s] %d contrastes; salvo em: %s", REF, nrow(forest.data.summary), out_path)
  list(plot = p, out_path = out_path, n = nrow(forest.data.summary))
}

say("=== Gerando forests: %s @ %s (binário=%s, NI=%s) ===", OUTCOME_ID, TP_LABEL, OUTCOME_IS_BINARY, as.character(NI_VALUE))
ac <- .get_all_contrasts_draws(WHICH_RES$fit)
arr_all <- ac$arr
labs_all <- ac$labs
refs_from_draws <- unique(sub(".*\\s+vs\\.?\\s*", "", labs_all))
say("Referências detectadas: %s", if (length(refs_from_draws)) paste(refs_from_draws, collapse = ", ") else "<nenhuma>")
REFS <- if (exists("TRT_LEVELS", inherits = TRUE) && length(TRT_LEVELS)) {
  unique(c(intersect(TRT_LEVELS, refs_from_draws), setdiff(refs_from_draws, TRT_LEVELS)))
} else {
  sort(refs_from_draws)
}
say("REFS alvo: %s", if (length(REFS)) paste(REFS, collapse = ", ") else "<vazio>")
FIG_DIR <- if (exists("FIG_DIR", inherits = TRUE) && nzchar(FIG_DIR)) FIG_DIR else file.path(getwd(), "figures")
dir.create(FIG_DIR, showWarnings = FALSE, recursive = TRUE)
plots_by_ref <- list()
out_paths <- character(0)
if (!length(REFS)) {
  say("ATENÇÃO: nenhum REF detectado. Verifique rótulos 'X vs Y'.")
} else {
  for (REF in REFS) {
    df_ref <- .long_for_ref(arr_all, labs_all, REF)
    if (is.null(df_ref) || !nrow(df_ref)) {
      say("[REF=%s] Sem contrastes -> pulando.", REF)
      next
    }
    res <- .plot_forest_for_ref(df_ref, REF, OUTCOME_ID, TP_LABEL, OUTCOME_IS_BINARY, NI_VALUE, FIG_DIR, NULL)
    plots_by_ref[[REF]] <- res$plot
    out_paths[REF] <- res$out_path
  }
}
say("=== Concluído. Total de gráficos gerados: %d ===", length(out_paths))

.selfcheck_ref <- function(fit, REF, is_binary = FALSE, tol = 1e-6) {
  ac <- .get_all_contrasts_draws(fit)
  d1 <- .long_for_ref(ac$arr, ac$labs, REF)
  if (is.null(d1) || !nrow(d1)) {
    return(list(pass = FALSE, reason = sprintf("Sem contrastes p/ REF=%s", REF)))
  }
  if (is_binary) {
    d1$b_Intercept <- exp(d1$b_Intercept)
  }
  s1 <- d1 %>%
    dplyr::group_by(Author) %>%
    dplyr::summarise(
      mean_f = mean(b_Intercept),
      lcl_f = stats::quantile(b_Intercept, 0.025),
      ucl_f = stats::quantile(b_Intercept, 0.975),
      .groups = "drop"
    )
  rb <- multinma::relative_effects(fit, trt_ref = REF, summary = FALSE)
  arr <- if (is.list(rb) && !is.null(rb$sims)) rb$sims else rb
  p2 <- dimnames(arr)[[3]]
  if (is.null(p2)) {
    return(list(pass = FALSE, reason = "Sem nomes de parâmetros"))
  }
  labs_raw <- gsub("^d\\[|^delta_new\\[|\\]$", "", p2)
  labs <- ifelse(grepl(" vs ", labs_raw), labs_raw, paste0(labs_raw, " vs ", REF))
  dims <- dim(arr)
  mat <- matrix(arr, nrow = dims[1] * dims[2], ncol = dims[3])
  dfw <- as.data.frame(mat)
  names(dfw) <- labs
  dfw$.draw <- seq_len(nrow(dfw))
  d2 <- tidyr::pivot_longer(dfw, cols = - .draw, names_to = "Author", values_to = "val")
  if (is_binary) {
    d2$val <- exp(d2$val)
  }
  s2 <- d2 %>%
    dplyr::group_by(Author) %>%
    dplyr::summarise(
      mean_m = mean(val),
      lcl_m = stats::quantile(val, 0.025),
      ucl_m = stats::quantile(val, 0.975),
      .groups = "drop"
    )
  cmp <- dplyr::full_join(s1, s2, by = "Author") %>%
    dplyr::mutate(
      d_mean = abs(mean_f - mean_m),
      d_lcl = abs(lcl_f - lcl_m),
      d_ucl = abs(ucl_f - ucl_m)
    )
  bad <- dplyr::filter(cmp, d_mean > tol | d_lcl > tol | d_ucl > tol)
  list(pass = nrow(bad) == 0, diffs = bad, cmp = cmp)
}

tol <- 1e-6
pass_all <- TRUE
for (REF in names(plots_by_ref)) {
  chk <- .selfcheck_ref(WHICH_RES$fit, REF, OUTCOME_IS_BINARY, tol = tol)
  if (!isTRUE(chk$pass)) {
    pass_all <- FALSE
    cat(sprintf("CHECK FAILED [REF=%s]\n", REF))
    if (!is.null(chk$diffs) && nrow(chk$diffs)) {
      print(chk$diffs, n = 50)
    }
    if (!is.null(chk$reason)) {
      cat("  Motivo: ", chk$reason, "\n", sep = "")
    }
  }
}
if (pass_all) {
  cat("OK\n")
  flush.console()
} else {
  cat("CHECK FAILED\n")
  flush.console()
}

# ============================================================
# [Revisão final]
# ============================================================
stopifnot(
  exists("res_mme_24h"),
  exists("res_pain_vas_6h"),
  exists("res_opioid_free"),
  is.list(res_mme_24h),
  is.list(res_pain_vas_6h),
  is.list(res_opioid_free)
)
