const ready = (callback) => {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", callback);
  } else {
    callback();
  }
};

ready(() => {
  const yearHolder = document.getElementById("current-year");
  if (yearHolder) {
    yearHolder.textContent = new Date().getFullYear();
  }

  const storageKey = "oe-cookie-consent";
  const cookieBanner = document.querySelector("[data-cookie-banner]");
  const acceptButton = document.querySelector("[data-cookie-accept]");
  const manageButton = document.querySelector("[data-cookie-preferences]");
  const reopenButton = document.querySelector("[data-open-cookies]");

  const setVisibility = (visible) => {
    if (!cookieBanner) return;
    cookieBanner.style.display = visible ? "grid" : "none";
    cookieBanner.setAttribute("aria-hidden", visible ? "false" : "true");
    if (visible) {
      cookieBanner.focus({ preventScroll: true });
    }
  };

  const storedConsent = (() => {
    try {
      const raw = localStorage.getItem(storageKey);
      return raw ? JSON.parse(raw) : null;
    } catch (error) {
      console.warn("Não foi possível ler o consentimento salvo", error);
      return null;
    }
  })();

  if (!storedConsent) {
    setTimeout(() => setVisibility(true), 800);
  }

  const persistConsent = (status) => {
    try {
      localStorage.setItem(
        storageKey,
        JSON.stringify({ status, updatedAt: new Date().toISOString() }),
      );
    } catch (error) {
      console.warn("Não foi possível salvar o consentimento", error);
    }
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ event: "cookie_consent", consent_state: status });
  };

  acceptButton?.addEventListener("click", () => {
    persistConsent("all");
    setVisibility(false);
  });

  manageButton?.addEventListener("click", () => {
    persistConsent("customize");
    const target = manageButton.getAttribute("data-target") || "/cookies";
    const win = window.open(target, "_blank");
    if (win) {
      win.opener = null;
    }
  });

  reopenButton?.addEventListener("click", () => {
    setVisibility(true);
  });

  const anchors = document.querySelectorAll('a[href^="#"]');
  anchors.forEach((anchor) => {
    anchor.addEventListener("click", (event) => {
      const hash = anchor.getAttribute("href");
      if (!hash || hash === "#") return;
      const target = document.querySelector(hash);
      if (!target) return;
      event.preventDefault();
      target.scrollIntoView({ behavior: "smooth", block: "start" });
      target.setAttribute("tabindex", "-1");
      target.focus({ preventScroll: true });
      target.removeAttribute("tabindex");
    });
  });

  const progressBar = document.querySelector("[data-progress-bar]");
  if (progressBar) {
    const updateProgress = () => {
      const doc = document.documentElement;
      const body = document.body;
      const scrollTop = window.scrollY || doc.scrollTop || body.scrollTop || 0;
      const scrollHeight = Math.max(doc.scrollHeight, body.scrollHeight);
      const viewport = window.innerHeight || doc.clientHeight || 1;
      const max = scrollHeight - viewport;
      const progress = max > 0 ? Math.min(Math.max(scrollTop / max, 0), 1) : 0;
      progressBar.style.transform = `scaleX(${progress})`;
    };

    let ticking = false;
    const requestTick = () => {
      if (!ticking) {
        window.requestAnimationFrame(() => {
          updateProgress();
          ticking = false;
        });
        ticking = true;
      }
    };

    window.addEventListener("scroll", requestTick, { passive: true });
    window.addEventListener("resize", requestTick);
    updateProgress();
  }

  const ctaBar = document.querySelector("[data-cta-bar]");
  const heroSection = document.querySelector(".hero");
  if (ctaBar && heroSection) {
    const mobileQuery = window.matchMedia("(max-width: 768px)");
    let heroOutOfView = false;

    const applyCtaState = () => {
      if (!mobileQuery.matches) {
        ctaBar.classList.remove("is-visible");
        return;
      }
      ctaBar.classList.toggle("is-visible", heroOutOfView);
    };

    if ("IntersectionObserver" in window) {
      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.target === heroSection) {
              heroOutOfView = !entry.isIntersecting;
              applyCtaState();
            }
          });
        },
        { threshold: 0.15 },
      );
      observer.observe(heroSection);
    } else {
      const updateFallback = () => {
        const rect = heroSection.getBoundingClientRect();
        heroOutOfView = rect.bottom < 0;
        applyCtaState();
      };
      window.addEventListener("scroll", updateFallback, { passive: true });
      updateFallback();
    }

    const onQueryChange = () => applyCtaState();
    if (typeof mobileQuery.addEventListener === "function") {
      mobileQuery.addEventListener("change", onQueryChange);
    } else if (typeof mobileQuery.addListener === "function") {
      mobileQuery.addListener(onQueryChange);
    }

    applyCtaState();
  }
});
