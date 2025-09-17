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

core_handled_packages <- unique(c(
  core_packages,
  "ggplot2",
  "dplyr",
  "tidyr",
  "readr",
  "purrr",
  "tibble",
  "forcats"
))

for (pkg in core_packages) {
  ensure_package(pkg)
}

ensure_additional_packages <- function(pkgs) {
  pkgs <- setdiff(pkgs, core_handled_packages)
  if (!length(pkgs)) {
    return(invisible(NULL))
  }
  for (pkg in pkgs) {
    if (!requireNamespace(pkg, quietly = TRUE)) {
      ensure_package(pkg)
    } else if (!paste0("package:", pkg) %in% search()) {
      suppressPackageStartupMessages(library(pkg, character.only = TRUE))
    }
  }
  invisible(NULL)
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

compute_sucra_tbl <- function(fit, lower_better = TRUE) {
  rk <- multinma::posterior_ranks(fit, lower_better = lower_better, summary = FALSE)
  arr <- rk$sims
  stopifnot(is.array(arr) && length(dim(arr)) == 3L)
  pnames <- dimnames(arr)[[3]]
  if (is.null(pnames)) {
    stop("Sem nomes de parâmetros nos ranks.")
  }
  mat <- matrix(arr, nrow = dim(arr)[1] * dim(arr)[2], ncol = dim(arr)[3])
  colnames(mat) <- pnames
  df <- as.data.frame(mat)
  rank_cols <- grep("^rank\\[|^rank_", names(df), value = TRUE)
  if (!length(rank_cols)) {
    stop("Não encontrei colunas de ranks nos draws.")
  }
  means <- vapply(df[rank_cols], function(v) mean(as.numeric(v), na.rm = TRUE), numeric(1))
  trt <- gsub("^rank\\[|\\]$|^rank_", "", rank_cols)
  K <- length(means)
  sucra <- (K - means) / (K - 1)
  tibble::tibble(
    treatment = trt,
    mean_rank = as.numeric(means),
    SUCRA = as.numeric(sucra)
  )
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
  ranks_summary <- multinma::posterior_ranks(fit, lower_better = lower_better)
  ranks_tbl <- tryCatch(
    tibble::as_tibble(ranks_summary),
    error = function(e) tibble::as_tibble(as.data.frame(ranks_summary))
  )
  sucra_tbl <- compute_sucra_tbl(fit, lower_better = lower_better)
  
  list(
    pairwise = pairwise_tbl,
    all_contrasts = all_contrasts_tbl,
    ranks = ranks_tbl,
    sucra = sucra_tbl
  )
}

# ============================================================
# [ETAPA 7] Forest pairwise — estilo “RevMan”
# ============================================================
.revman_theme <- function(base_size = 12) {
  ggplot2::theme_minimal(base_size = base_size) +
    ggplot2::theme(
      panel.background = ggplot2::element_rect(fill = "white", colour = NA),
      plot.background = ggplot2::element_rect(fill = "white", colour = NA),
      panel.grid.major.y = ggplot2::element_blank(),
      panel.grid.minor = ggplot2::element_blank(),
      panel.grid.major.x = ggplot2::element_line(colour = "grey85", linewidth = 0.4),
      axis.title.y = ggplot2::element_blank(),
      axis.ticks.y = ggplot2::element_blank(),
      axis.text.y = ggplot2::element_text(hjust = 0),
      plot.margin = ggplot2::margin(10, 80, 35, 10)
    )
}

.fmt_ <- function(x, k = 2) {
  formatC(x, digits = k, format = "f", drop0trailing = FALSE)
}

save_forest_pairwise <- function(tbl, is_binary, outcome_id, timepoint, ref) {
  if (is.null(tbl) || !nrow(tbl)) {
    message("[pairwise forest] Tabela vazia — nada a plotar.")
    return(invisible(NULL))
  }
  
  cn <- names(tbl)
  pick <- function(opts) {
    nm <- intersect(opts, cn)
    if (length(nm) == 0L) {
      return(NULL)
    }
    tbl[[nm[1]]]
  }
  
  mean_v <- pick(c("mean", "Mean", "estimate", "Estimate", "est"))
  lcl_v <- pick(c("2.5%", "lower", "lcl", "ci_lower", "Lower", "lower.95"))
  ucl_v <- pick(c("97.5%", "upper", "ucl", "ci_upper", "Upper", "upper.95"))
  
  if (is.null(mean_v) || is.null(lcl_v) || is.null(ucl_v)) {
    message("[pairwise forest] Não encontrei colunas de média/IC — pulando.")
    return(invisible(NULL))
  }
  
  has_trtb <- ".trtb" %in% cn
  trtb_chr <- if (has_trtb) as.character(tbl$.trtb) else NULL
  label <- if (has_trtb) {
    paste0(trtb_chr, " vs ", ref)
  } else if ("contrast" %in% cn) {
    as.character(tbl$contrast)
  } else if ("treatment" %in% cn) {
    paste0(as.character(tbl$treatment), " vs ", ref)
  } else if ("trt" %in% cn) {
    paste0(as.character(tbl$trt), " vs ", ref)
  } else {
    paste0("trt_", seq_len(nrow(tbl)), " vs ", ref)
  }
  
  df <- tibble::tibble(
    label = label,
    mean = as.numeric(mean_v),
    lcl = as.numeric(lcl_v),
    ucl = as.numeric(ucl_v)
  ) %>%
    filter(is.finite(mean), is.finite(lcl), is.finite(ucl)) %>%
    mutate(
      trt_name = sub("\\s+vs\\s+.*$", "", label),
      ord = if (exists("TRT_LEVELS", inherits = TRUE)) match(tolower(trt_name), tolower(TRT_LEVELS)) else NA_real_
    ) %>%
    arrange(coalesce(ord, Inf), label) %>%
    mutate(label = factor(label, levels = unique(label)))
  
  if (!nrow(df)) {
    message("[pairwise forest] Tabela vazia após limpeza — nada a plotar.")
    return(invisible(NULL))
  }
  
  df <- df %>%
    mutate(
      txt_effect = sprintf("%s  [%s; %s]", .fmt_(mean, 2), .fmt_(lcl, 2), .fmt_(ucl, 2)),
      y = as.numeric(label)
    )
  
  stripes <- df %>%
    mutate(ymin = y - 0.5, ymax = y + 0.5) %>%
    filter((y %% 2) == 0) %>%
    select(ymin, ymax)
  
  x0 <- if (is_binary) 1 else 0
  xmin <- min(c(df$lcl, x0), na.rm = TRUE)
  xmax <- max(c(df$ucl, x0), na.rm = TRUE)
  span <- xmax - xmin + 1e-9
  pad <- 0.10 * span
  x_txt <- xmax + 0.45 * span
  
  p <- ggplot2::ggplot(df, ggplot2::aes(y = label)) +
    ggplot2::geom_rect(
      data = stripes,
      ggplot2::aes(ymin = ymin, ymax = ymax),
      xmin = -Inf,
      xmax = Inf,
      inherit.aes = FALSE,
      fill = "grey97",
      colour = NA
    ) +
    ggplot2::geom_vline(xintercept = x0, linetype = "solid", linewidth = 0.7, alpha = 0.9) +
    ggplot2::geom_segment(ggplot2::aes(x = lcl, xend = ucl, yend = label), linewidth = 0.8) +
    ggplot2::geom_point(
      ggplot2::aes(x = mean),
      shape = 22,
      size = 3.6,
      stroke = 0.6,
      colour = "black",
      fill = "grey70"
    ) +
    ggplot2::geom_point(
      ggplot2::aes(x = mean),
      shape = 3,
      size = 2.2,
      stroke = 0.7,
      colour = "black"
    ) +
    ggplot2::geom_text(ggplot2::aes(x = x_txt, label = txt_effect), hjust = 0, size = 3.4) +
    ggplot2::coord_cartesian(xlim = c(xmin - pad, x_txt + 0.25 * span), clip = "off") +
    .revman_theme(base_size = 12) +
    ggplot2::labs(
      title = sprintf("Contrastes vs %s — %s @ %s", ref, outcome_id, timepoint %||% "NA"),
      x = if (is_binary) "OR (vs ref)" else "MD (vs ref)"
    )
  
  print(p)
  
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

.ns_pick1 <- function(df, cand) {
  nm <- cand[cand %in% names(df)]
  if (length(nm)) nm[1] else ""
}


.ns_to_wide <- function(ns_input) {
  if (is.null(ns_input)) {
    return(tibble::tibble())
  }
  
  tb <- NULL
  if (inherits(ns_input, "nodesplit_summary")) {
    tb <- suppressWarnings(try(tibble::as_tibble(ns_input, nest = FALSE), silent = TRUE))
    if (inherits(tb, "try-error")) {
      tb <- suppressWarnings(try(tibble::as_tibble(ns_input), silent = TRUE))
    }
  } else if (is.data.frame(ns_input)) {
    tb <- tibble::as_tibble(ns_input)
  } else {
    tb <- suppressWarnings(try(tibble::as_tibble(ns_input, nest = FALSE), silent = TRUE))
    if (inherits(tb, "try-error")) {
      tb <- suppressWarnings(try(tibble::as_tibble(ns_input), silent = TRUE))
    }
  }
  if (inherits(tb, "try-error") || is.null(tb)) {
    return(tibble::tibble())
  }
  
  if (!"comparison" %in% names(tb)) {
    if (all(c("trt1", "trt2") %in% names(tb))) {
      tb$comparison <- paste(tb$trt2, "vs", tb$trt1)
    } else {
      tb$comparison <- paste0("cmp_", seq_len(nrow(tb)))
    }
  }
  
  get_trts <- function(df) {
    tmp <- df
    if (!"trt1" %in% names(tmp)) tmp$trt1 <- NA_character_
    if (!"trt2" %in% names(tmp)) tmp$trt2 <- NA_character_
    dplyr::distinct(tmp, comparison, trt1, trt2)
  }
  
  if ("parameter" %in% names(tb)) {
    par_raw <- as.character(tb$parameter)
    par_low <- tolower(par_raw)
    src_col <- .ns_pick1(tb, c("source", "component", "group", "subset", "type", "evidence", "d_source"))
    
    if (any(par_low == "d") && nzchar(src_col)) {
      mean_col <- .ns_pick1(tb, c("mean", "Mean", "estimate", "Estimate", "est"))
      lcl_col <- .ns_pick1(tb, c("2.5%", "lower", "Lower", "lcl", "ci_lower", "Lower..95.", "lower.95"))
      ucl_col <- .ns_pick1(tb, c("97.5%", "upper", "Upper", "ucl", "ci_upper", "Upper..95.", "upper.95"))
      
      tbd <- tb[par_low == "d", , drop = FALSE]
      src_vals <- tolower(as.character(tbd[[src_col]]))
      src_code <- ifelse(grepl("^dir", src_vals), "d_dir",
                         ifelse(grepl("^ind", src_vals), "d_ind",
                                ifelse(grepl("net|overall|comb|network", src_vals), "d_net", NA_character_)))
      tbd$src_code <- src_code
      tbd$mean_std <- .as_num_chr(tbd[[if (nzchar(mean_col)) mean_col else "mean"]])
      tbd$lcl_std <- if (nzchar(lcl_col)) .as_num_chr(tbd[[lcl_col]]) else NA_real_
      tbd$ucl_std <- if (nzchar(ucl_col)) .as_num_chr(tbd[[ucl_col]]) else NA_real_
      
      keep <- tbd[!is.na(tbd$src_code), c("comparison", "src_code", "mean_std", "lcl_std", "ucl_std"), drop = FALSE]
      wide <- tidyr::pivot_wider(
        tibble::as_tibble(keep),
        names_from = src_code,
        values_from = c(mean_std, lcl_std, ucl_std),
        names_glue = "{src_code}_{.value}"
      )
      
      pv_col <- .ns_pick1(tb, c("p_value", "p.value", "p", "pval", "pval_std", "pval-std"))
      if (any(par_low == "omega")) {
        tpv <- tb[par_low == "omega", , drop = FALSE]
        pv <- tibble::tibble(
          comparison = paste(tpv$trt2, "vs", tpv$trt1),
          p_value = .as_num_chr(tpv[[if (nzchar(pv_col)) pv_col else "p_value"]])
        )
        wide <- dplyr::left_join(wide, pv, by = "comparison")
      }
      
      wide <- dplyr::left_join(wide, get_trts(tb), by = "comparison")
      names(wide) <- gsub("mean_std", "mean", names(wide))
      names(wide) <- gsub("lcl_std", "lcl", names(wide))
      names(wide) <- gsub("ucl_std", "ucl", names(wide))
      return(as.data.frame(wide))
    }
    
    is_bracketed <- grepl("^(d_(dir|ind|net)|omega)\\[", par_low)
    if (any(is_bracketed)) {
      mean_col <- .ns_pick1(tb, c("mean", "Mean", "estimate", "Estimate", "est"))
      lcl_col <- .ns_pick1(tb, c("2.5%", "lower", "Lower", "lcl", "ci_lower", "Lower..95.", "lower.95"))
      ucl_col <- .ns_pick1(tb, c("97.5%", "upper", "Upper", "ucl", "ci_upper", "Upper..95.", "upper.95"))
      pv_col <- .ns_pick1(tb, c("p_value", "p.value", "p", "pval", "pval_std", "pval-std"))
      
      tbb <- tb[is_bracketed, , drop = FALSE]
      kind <- ifelse(grepl("^d_dir\\[", par_low[is_bracketed]), "d_dir",
                     ifelse(grepl("^d_ind\\[", par_low[is_bracketed]), "d_ind",
                            ifelse(grepl("^d_net\\[", par_low[is_bracketed]), "d_net",
                                   ifelse(grepl("^omega\\[", par_low[is_bracketed]), "omega", NA_character_))))
      comp <- sub("^.*\\[|\\]$", "", par_raw[is_bracketed])
      sp <- strsplit(comp, "\\s+vs\\.?\\s+", perl = TRUE)
      trt2 <- vapply(sp, function(v) if (length(v) >= 1) v[1] else NA_character_, "")
      trt1 <- vapply(sp, function(v) if (length(v) >= 2) v[2] else NA_character_, "")
      
      tbb$kind <- kind
      tbb$comparison <- comp
      tbb$trt1 <- trt1
      tbb$trt2 <- trt2
      tbb$mean_std <- .as_num_chr(tbb[[if (nzchar(mean_col)) mean_col else "mean"]])
      tbb$lcl_std <- if (nzchar(lcl_col)) .as_num_chr(tbb[[lcl_col]]) else NA_real_
      tbb$ucl_std <- if (nzchar(ucl_col)) .as_num_chr(tbb[[ucl_col]]) else NA_real_
      tbb$pval_std <- if (nzchar(pv_col)) .as_num_chr(tbb[[pv_col]]) else NA_real_
      
      keep <- tbb[, c("comparison", "trt1", "trt2", "kind", "mean_std", "lcl_std", "ucl_std", "pval_std"), drop = FALSE]
      wide <- tidyr::pivot_wider(
        tibble::as_tibble(keep),
        names_from = kind,
        values_from = c(mean_std, lcl_std, ucl_std),
        names_glue = "{kind}_{.value}"
      )
      pv <- keep[keep$kind == "omega", c("comparison", "pval_std"), drop = FALSE]
      if (nrow(pv)) {
        names(pv)[names(pv) == "pval_std"] <- "p_value"
        wide <- dplyr::left_join(wide, pv, by = "comparison")
      }
      if (!"trt1" %in% names(wide)) {
        wide$trt1 <- keep$trt1[match(wide$comparison, keep$comparison)]
      }
      if (!"trt2" %in% names(wide)) {
        wide$trt2 <- keep$trt2[match(wide$comparison, keep$comparison)]
      }
      
      names(wide) <- gsub("mean_std", "mean", names(wide))
      names(wide) <- gsub("lcl_std", "lcl", names(wide))
      names(wide) <- gsub("ucl_std", "ucl", names(wide))
      return(as.data.frame(wide))
    }
    
    if (any(par_low %in% c("d_dir", "d_ind", "d_net", "omega"))) {
      mean_col <- .ns_pick1(tb, c("mean", "Mean", "estimate", "Estimate", "est"))
      lcl_col <- .ns_pick1(tb, c("2.5%", "lower", "Lower", "lcl", "ci_lower", "Lower..95.", "lower.95"))
      ucl_col <- .ns_pick1(tb, c("97.5%", "upper", "Upper", "ucl", "ci_upper", "Upper..95.", "upper.95"))
      
      tb$mean_std <- .as_num_chr(tb[[if (nzchar(mean_col)) mean_col else "mean"]])
      tb$lcl_std <- if (nzchar(lcl_col)) .as_num_chr(tb[[lcl_col]]) else NA_real_
      tb$ucl_std <- if (nzchar(ucl_col)) .as_num_chr(tb[[ucl_col]]) else NA_real_
      
      keep <- tb %>%
        filter(par_low %in% c("d_dir", "d_ind", "d_net")) %>%
        select(comparison, parameter, mean = mean_std, lcl = lcl_std, ucl = ucl_std)
      wide <- tidyr::pivot_wider(
        keep,
        names_from = parameter,
        values_from = c(mean, lcl, ucl),
        names_glue = "{parameter}_{.value}"
      )
      
      pv_col <- .ns_pick1(tb, c("p_value", "p.value", "p", "pval", "pval_std", "pval-std"))
      pv <- tb %>%
        filter(par_low == "omega") %>%
        transmute(
          comparison,
          p_value = .as_num_chr(.data[[if (nzchar(pv_col)) pv_col else "p_value"]])
        ) %>%
        distinct()
      
      wide <- dplyr::left_join(wide, pv, by = "comparison")
      wide <- dplyr::left_join(wide, get_trts(tb), by = "comparison")
      return(as.data.frame(wide))
    }
  }
  
  wide <- as.data.frame(tb)
  mapping <- list(
    d_dir_mean = c("d_dir_mean", "mean_d_dir", "d.dir.mean", "d_dir", "d.dir"),
    d_dir_lcl = c("d_dir_lcl", "d_dir_2.5%", "lcl_d_dir", "d.dir.2.5.", "d_dir_lower", "lower_d_dir"),
    d_dir_ucl = c("d_dir_ucl", "d_dir_97.5%", "ucl_d_dir", "d.dir.97.5.", "d_dir_upper", "upper_d_dir"),
    d_ind_mean = c("d_ind_mean", "mean_d_ind", "d.ind.mean", "d_ind", "d.ind"),
    d_ind_lcl = c("d_ind_lcl", "d_ind_2.5%", "lcl_d_ind", "d.ind.2.5.", "d_ind_lower", "lower_d_ind"),
    d_ind_ucl = c("d_ind_ucl", "d_ind_97.5%", "ucl_d_ind", "d.ind.97.5.", "d_ind_upper", "upper_d_ind"),
    d_net_mean = c("d_net_mean", "mean_d_net", "d.net.mean", "d_net", "d.net"),
    d_net_lcl = c("d_net_lcl", "d_net_2.5%", "lcl_d_net", "d.net.2.5.", "d_net_lower", "lower_d_net"),
    d_net_ucl = c("d_net_ucl", "d_net_97.5%", "ucl_d_net", "d.net.97.5.", "d_net_upper", "upper_d_net"),
    omega_mean = c("omega_mean", "mean_omega", "omega.mean", "omega"),
    omega_lcl = c("omega_lcl", "omega_2.5%", "omega.lower", "omega.2.5."),
    omega_ucl = c("omega_ucl", "omega_97.5%", "omega.upper", "omega.97.5."),
    p_value = c("p_value", "p.value", "p", "pval", "pval_std", "pval-std")
  )
  for (k in names(mapping)) {
    if (!k %in% names(wide)) {
      src <- mapping[[k]][mapping[[k]] %in% names(wide)]
      if (length(src)) {
        wide[[k]] <- .as_num_chr(wide[[src[1]]])
      } else {
        wide[[k]] <- NA_real_
      }
    } else {
      wide[[k]] <- .as_num_chr(wide[[k]])
    }
  }
  if (!"comparison" %in% names(wide) || all(is.na(wide$comparison))) {
    if (all(c("trt1", "trt2") %in% names(wide))) {
      wide$comparison <- paste(wide$trt2, "vs", wide$trt1)
    } else if ("contrast" %in% names(wide)) {
      wide$comparison <- as.character(wide$contrast)
    } else {
      wide$comparison <- paste0("cmp_", seq_len(nrow(wide)))
    }
  } else {
    wide$comparison <- as.character(wide$comparison)
  }
  
  if (!("trt1" %in% names(wide) && "trt2" %in% names(wide))) {
    sp <- strsplit(as.character(wide$comparison), "\\s+vs\\.?\\s+", perl = TRUE)
    trt2 <- vapply(sp, function(v) if (length(v) >= 1) v[1] else NA_character_, "")
    trt1 <- vapply(sp, function(v) if (length(v) >= 2) v[2] else NA_character_, "")
    if (!"trt1" %in% names(wide)) wide$trt1 <- trt1
    if (!"trt2" %in% names(wide)) wide$trt2 <- trt2
  }
  wide
}


.ns_build_long <- function(wide_tbl, is_binary) {
  if (is.null(wide_tbl) || !nrow(wide_tbl)) {
    return(list(base = tibble::tibble(), long = tibble::tibble()))
  }
  n <- nrow(wide_tbl)
  sc <- function(nm) {
    if (nm %in% names(wide_tbl)) {
      .as_num_chr(wide_tbl[[nm]])
    } else {
      rep(NA_real_, n)
    }
  }
  
  base <- tibble::tibble(
    comparison = as.character(wide_tbl$comparison),
    d_dir_mean = sc("d_dir_mean"),
    d_dir_lcl = sc("d_dir_lcl"),
    d_dir_ucl = sc("d_dir_ucl"),
    d_ind_mean = sc("d_ind_mean"),
    d_ind_lcl = sc("d_ind_lcl"),
    d_ind_ucl = sc("d_ind_ucl"),
    d_net_mean = sc("d_net_mean"),
    d_net_lcl = sc("d_net_lcl"),
    d_net_ucl = sc("d_net_ucl"),
    omega_mean = sc("omega_mean"),
    omega_lcl = sc("omega_lcl"),
    omega_ucl = sc("omega_ucl"),
    p_value = sc("p_value")
  )
  
  if (isTRUE(is_binary)) {
    for (nm in c(
      "d_dir_mean", "d_dir_lcl", "d_dir_ucl",
      "d_ind_mean", "d_ind_lcl", "d_ind_ucl",
      "d_net_mean", "d_net_lcl", "d_net_ucl"
    )) {
      base[[nm]] <- exp(base[[nm]])
    }
  }
  
  mk_long <- function(lbl, m, l, u) {
    keep <- rowSums(is.finite(cbind(base[[m]], base[[l]], base[[u]]))) >= 2
    if (!any(keep)) {
      return(NULL)
    }
    tibble::tibble(
      comparison = base$comparison[keep],
      type = lbl,
      estimate = base[[m]][keep],
      lower = base[[l]][keep],
      upper = base[[u]][keep]
    )
  }
  
  long <- dplyr::bind_rows(
    mk_long("Direto", "d_dir_mean", "d_dir_lcl", "d_dir_ucl"),
    mk_long("Indireto", "d_ind_mean", "d_ind_lcl", "d_ind_ucl"),
    mk_long("Network", "d_net_mean", "d_net_lcl", "d_net_ucl")
  )
  if (is.null(long)) {
    long <- tibble::tibble()
  }
  if (nrow(long)) {
    long$type <- factor(long$type, levels = c("Direto", "Indireto", "Network"))
  }
  
  list(base = base, long = long)
}

nodesplit_check <- function(net,
                            outcome_key,
                            is_binary,
                            mcmc = settings$mcmc) {
  plan <- try(multinma::get_nodesplits(net), silent = TRUE)
  if (!inherits(plan, "try-error")) {
    message("[Node-splitting] Total de splits: ", nrow(plan))
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
  
  sm <- summary(fit_ns)
  top_p <- NULL
  if (all(c("trt1", "trt2", "parameter") %in% names(sm))) {
    pcol_top <- intersect(c("p_value", "p.value", "p", "pval", "pval_std", "pval-std"), names(sm))[1]
    if (length(pcol_top) == 1L && nzchar(pcol_top)) {
      par_low <- tolower(as.character(sm$parameter))
      tmp <- tibble::tibble(
        comparison = paste(sm$trt2, "vs", sm$trt1),
        trt1 = as.character(sm$trt1),
        trt2 = as.character(sm$trt2),
        p_value_top = .as_num_chr(sm[[pcol_top]]),
        is_omega = par_low == "omega"
      ) %>%
        filter(is_omega, is.finite(p_value_top)) %>%
        distinct(comparison, .keep_all = TRUE)
      if (nrow(tmp)) {
        top_p <- tmp
      }
    }
  }
  
  tbl <- try(.ns_to_wide(sm), silent = TRUE)
  if (inherits(tbl, "try-error")) {
    tbl <- NULL
  }
  
  if (!is.null(top_p)) {
    if (is.null(tbl) || !nrow(tbl)) {
      tbl <- top_p %>%
        transmute(comparison, trt1, trt2, p_value = p_value_top)
    } else {
      if (!"p_value" %in% names(tbl)) {
        tbl$p_value <- NA_real_
      }
      tbl <- tbl %>%
        dplyr::left_join(
          dplyr::select(top_p, comparison, p_value_top),
          by = "comparison"
        ) %>%
        mutate(p_value = dplyr::coalesce(.as_num_chr(p_value), .as_num_chr(p_value_top))) %>%
        select(-p_value_top)
    }
  }
  
  if (!is.null(tbl) && "p_value" %in% names(tbl)) {
    tbl$p_value <- .as_num_chr(tbl$p_value)
  }
  
  list(object = fit_ns, summary = sm, table = tbl, plan = plan)
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
    outcome_key = key,
    is_binary = (agd_info$family == "binomial")
  )
  save_forest_pairwise(
    sm$pairwise,
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
    sucra = sm$sucra,
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
writetbl(res_mme_24h$sucra, file.path(DOCS_DIR, "sucra_mme_24h.csv"))
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
# [ETAPA 10] Padrão de plots para node-splitting
# ============================================================
ensure_additional_packages(c(
  "ggplot2",
  "dplyr",
  "tidyr",
  "stringr",
  "forcats",
  "scales",
  "ggrepel",
  "ggdist"
))

NODE_DIR <- file.path(FIG_DIR, "nodesplit")
dir.create(NODE_DIR, showWarnings = FALSE, recursive = TRUE)

.save_plot <- function(p, file, w = 8, h = 5.2) {
  ggplot2::ggsave(filename = file, plot = p, width = w, height = h, dpi = 300, bg = "white")
  message("[nodesplit] Figura salva em: ", file)
}

plot_nodesplit_builtin <- function(ns_summary, is_binary, outcome_key, label_tag) {
  if (missing(ns_summary) || is.null(ns_summary)) {
    return(invisible(NULL))
  }
  p_dens <- plot(ns_summary, pars = "d", stat = "dens_overlay", orientation = "vertical", ref_line = 0) +
    ggplot2::labs(
      title = paste0("Node-splitting — d (", label_tag, ")"),
      x = if (is_binary) "log(OR)" else "Efeito"
    )
  .save_plot(p_dens, file.path(NODE_DIR, paste0("nodesplit_dens_", outcome_key, ".png")), w = 8.4, h = 6.2)
  
  p_omega <- plot(ns_summary, pars = "omega", stat = "halfeye", orientation = "vertical", ref_line = 0) +
    ggplot2::labs(
      title = paste0("Node-splitting — ω = d_dir − d_ind (", label_tag, ")"),
      x = expression(omega)
    )
  .save_plot(p_omega, file.path(NODE_DIR, paste0("nodesplit_omega_", outcome_key, ".png")), w = 8.4, h = 6.2)
  
  p_tau <- try(plot(ns_summary, pars = "tau", stat = "pointinterval"), silent = TRUE)
  if (!inherits(p_tau, "try-error")) {
    p_tau <- p_tau + ggplot2::labs(title = paste0("Heterogeneidade (τ) — ", label_tag))
    .save_plot(p_tau, file.path(NODE_DIR, paste0("nodesplit_tau_", outcome_key, ".png")), w = 6.8, h = 4.8)
  }
  invisible(list(dens = p_dens, omega = p_omega, tau = p_tau))
}

plot_nodesplit_dumbbell <- function(ns_input, is_binary, outcome_key, label_tag) {
  wide <- .ns_to_wide(ns_input)
  ex <- .ns_build_long(wide, is_binary)
  base <- ex$base
  long <- ex$long
  if (!nrow(long)) {
    message("[nodesplit] Sem estimativas diretas/indiretas — gráfico dumbbell não gerado.")
    return(invisible(NULL))
  }
  
  x0 <- if (is_binary) 1 else 0
  cores <- c("Direto" = "#2c7fb8", "Indireto" = "#f03b20", "Network" = "black")
  
  base <- base %>%
    mutate(
      se_dir = ifelse(is.finite(d_dir_ucl - d_dir_lcl), abs(d_dir_ucl - d_dir_lcl) / (2 * 1.96), NA_real_),
      se_ind = ifelse(is.finite(d_ind_ucl - d_ind_lcl), abs(d_ind_ucl - d_ind_lcl) / (2 * 1.96), NA_real_),
      prec = 1 / (se_dir^2 + se_ind^2)
    )
  rng <- range(base$prec, na.rm = TRUE)
  size_map <- if (diff(rng) <= 0 || !all(is.finite(rng))) {
    rep(4, nrow(base))
  } else {
    scales::rescale(base$prec, to = c(2.2, 6.2), from = rng)
  }
  size_df <- tibble::tibble(comparison = base$comparison, size_pt = size_map, p_value = base$p_value)
  long <- dplyr::left_join(long, size_df, by = "comparison")
  
  net_pts <- dplyr::filter(long, type == "Network")
  segs <- tidyr::pivot_wider(
    dplyr::filter(long, type %in% c("Direto", "Indireto")),
    id_cols = "comparison",
    names_from = "type",
    values_from = c("estimate", "lower", "upper")
  )
  segs <- segs[stats::complete.cases(segs[c("estimate_Direto", "estimate_Indireto")]), , drop = FALSE]
  
  p <- ggplot2::ggplot() +
    ggplot2::geom_vline(xintercept = x0, linewidth = 0.8) +
    ggplot2::geom_segment(
      data = segs,
      ggplot2::aes(
        y = comparison,
        yend = comparison,
        x = estimate_Direto,
        xend = estimate_Indireto
      ),
      linewidth = 1,
      colour = "grey50"
    ) +
    ggplot2::geom_point(
      data = dplyr::filter(long, type %in% c("Direto", "Indireto")),
      ggplot2::aes(x = estimate, y = comparison, colour = type, shape = type),
      size = 3
    ) +
    { if (nrow(net_pts)) ggplot2::geom_point(
      data = net_pts,
      ggplot2::aes(x = estimate, y = comparison),
      shape = 18,
      size = pmin(net_pts$size_pt, 6.5),
      colour = "black"
    ) } +
    ggplot2::scale_colour_manual(values = cores, drop = TRUE) +
    ggplot2::scale_shape_manual(values = c("Direto" = 16, "Indireto" = 17, "Network" = 18), drop = TRUE) +
    ggplot2::labs(
      title = paste0("Node-splitting — Dumbbell (", label_tag, ")"),
      x = if (is_binary) "OR" else "Efeito (escala original)",
      y = NULL,
      caption = "Linha: efeito nulo (0=contínuo; 1=binário). Diamante: rede (se disponível)."
    ) +
    ggplot2::theme_minimal()
  .save_plot(p, file.path(NODE_DIR, paste0("nodesplit_dumbbell_", outcome_key, ".png")), w = 8.6, h = 6.4)
  invisible(p)
}

plot_nodesplit_scatter <- function(ns_input, is_binary, outcome_key, label_tag) {
  wide <- .ns_to_wide(ns_input)
  ex <- .ns_build_long(wide, is_binary)
  base <- ex$base
  
  req <- c("d_dir_mean", "d_ind_mean")
  if (!all(req %in% names(base))) {
    message("[nodesplit] Colunas ausentes para scatter — gráfico não gerado.")
    return(invisible(NULL))
  }
  
  have <- is.finite(base$d_dir_mean) & is.finite(base$d_ind_mean)
  if (!any(have)) {
    message("[nodesplit] Sem pares direto/indireto finitos — scatter não gerado.")
    return(invisible(NULL))
  }
  
  df <- dplyr::transmute(
    base[have, , drop = FALSE],
    comparison,
    x = d_ind_mean,
    y = d_dir_mean,
    p_value = p_value,
    se = sqrt(((d_dir_ucl - d_dir_lcl) / (2 * 1.96))^2 + ((d_ind_ucl - d_ind_lcl) / (2 * 1.96))^2),
    prec = 1 / se^2
  )
  rng <- range(df$prec, na.rm = TRUE)
  df$size_pt <- if (diff(rng) <= 0 || !all(is.finite(rng))) {
    4
  } else {
    scales::rescale(df$prec, to = c(2.2, 6.0), from = rng)
  }
  
  ref <- if (is_binary) 1 else 0
  p <- ggplot2::ggplot(df, ggplot2::aes(x = x, y = y)) +
    ggplot2::geom_abline(slope = 1, intercept = 0, linetype = "dashed", linewidth = 0.8, alpha = 0.8) +
    ggplot2::geom_vline(xintercept = ref, linewidth = 0.5, alpha = 0.6) +
    ggplot2::geom_hline(yintercept = ref, linewidth = 0.5, alpha = 0.6) +
    ggplot2::geom_point(ggplot2::aes(size = size_pt, colour = p_value), alpha = 0.9) +
    ggplot2::scale_size_identity() +
    ggplot2::scale_colour_gradient2(
      low = "#2166ac",
      mid = "grey60",
      high = "#b2182b",
      midpoint = 0.10,
      name = "p (Bayes)"
    ) +
    ggrepel::geom_text_repel(
      data = dplyr::filter(df, is.finite(p_value) & p_value < 0.10),
      ggplot2::aes(label = comparison),
      size = 3.1,
      max.overlaps = 15
    ) +
    ggplot2::labs(
      title = paste0("Direto vs Indireto — ", label_tag),
      x = if (is_binary) "OR (indireto)" else "Efeito indireto",
      y = if (is_binary) "OR (direto)" else "Efeito direto"
    ) +
    ggplot2::theme_minimal()
  .save_plot(p, file.path(NODE_DIR, paste0("nodesplit_scatter_", outcome_key, ".png")), w = 7.8, h = 6.0)
  invisible(p)
}

plot_nodesplit_heatmap <- function(ns_input, outcome_key, label_tag) {
  wide <- .ns_to_wide(ns_input)
  if (is.null(wide) || !nrow(wide) || !("p_value" %in% names(wide))) {
    message("[nodesplit] Sem p_value — heatmap não gerado.")
    return(invisible(NULL))
  }
  
  pv_num <- .as_num_chr(wide$p_value)
  df <- tibble::tibble(
    comparison = as.character(wide$comparison),
    trt1 = if ("trt1" %in% names(wide)) as.character(wide$trt1) else NA_character_,
    trt2 = if ("trt2" %in% names(wide)) as.character(wide$trt2) else NA_character_,
    p_value = pv_num
  )
  if (all(is.na(df$trt1) | is.na(df$trt2))) {
    sp <- stringr::str_split_fixed(df$comparison, "\\s+vs\\s+", 2)
    df$trt2 <- sp[, 1]
    df$trt1 <- sp[, 2]
  }
  df <- df %>%
    filter(is.finite(p_value)) %>%
    mutate(
      t_low = pmin(trt1, trt2, na.rm = TRUE),
      t_high = pmax(trt1, trt2, na.rm = TRUE)
    ) %>%
    distinct(t_low, t_high, .keep_all = TRUE)
  if (!nrow(df)) {
    message("[nodesplit] p_value ausente — heatmap não gerado.")
    return(invisible(NULL))
  }
  p <- ggplot2::ggplot(df, ggplot2::aes(x = t_low, y = t_high, fill = p_value)) +
    ggplot2::geom_tile(color = "white") +
    ggplot2::scale_fill_gradientn(
      colours = c("#2166ac", "#67a9cf", "#fddbc7", "#ef8a62", "#b2182b"),
      values = scales::rescale(c(0, 0.05, 0.10, 0.25, 1.0)),
      limits = c(0, 1),
      name = "p (Bayes)"
    ) +
    ggplot2::coord_equal() +
    ggplot2::theme_minimal() +
    ggplot2::theme(axis.text.x = ggplot2::element_text(angle = 45, hjust = 1)) +
    ggplot2::labs(
      title = paste0("Heatmap de p (Bayes) — ", label_tag),
      x = NULL,
      y = NULL
    )
  .save_plot(p, file.path(NODE_DIR, paste0("nodesplit_heatmap_", outcome_key, ".png")), w = 7.6, h = 6.8)
  invisible(p)
}

nodesplit_plot_suite <- function(res, outcome_id, timepoint, is_binary) {
  key <- tolower(paste0(outcome_id, "_", timepoint %||% "NA"))
  tag <- paste0(outcome_id, " @ ", timepoint %||% "NA")
  if (!is.null(res$nodesplit$summary)) {
    plot_nodesplit_builtin(res$nodesplit$summary, is_binary, key, tag)
  } else {
    message("[nodesplit] Sem summary — plots built-in pulados para ", key)
  }
  ns_input <- if (!is.null(res$nodesplit$table) && nrow(res$nodesplit$table)) {
    res$nodesplit$table
  } else {
    res$nodesplit$summary
  }
  plot_nodesplit_dumbbell(ns_input, is_binary, key, tag)
  plot_nodesplit_scatter(ns_input, is_binary, key, tag)
  plot_nodesplit_heatmap(ns_input, key, tag)
  invisible(TRUE)
}

nodesplit_plot_suite(res_mme_24h, "mme", "24h", is_binary = FALSE)
nodesplit_plot_suite(res_pain_vas_6h, "pain_vas", "6h", is_binary = FALSE)
nodesplit_plot_suite(res_opioid_free, "opioid_free", "pacu", is_binary = TRUE)


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
  prec <- 1 / (ni_tbl$sd_MD^2)
  prec[!is.finite(prec)] <- NA_real_
  prec <- scales::rescale(prec, to = c(3.2, 6.2), from = range(prec, na.rm = TRUE))
  df <- ni_tbl %>%
    mutate(
      comp_label = factor(lab_up(comparator), levels = lab_up(comparator)[order(mean_MD)]),
      eff_txt = sprintf("%s  [%s; %s]", .fmt_(mean_MD, 2), .fmt_(q2.5_MD, 2), .fmt_(q97.5_MD, 2)),
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
    .revman_theme(base_size = 12) +
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
ensure_additional_packages(c(
  "posterior",
  "ggridges",
  "glue",
  "forcats"
))

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