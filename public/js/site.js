document.addEventListener("DOMContentLoaded", () => {
  initLoader();
  initNavToggle();
  initRevealOnScroll();
  initBackToTop();
  initPortfolioFilter();
  initTestimonialSlider();
  initStatCounters();
});

function initLoader() {
  const loader = document.querySelector(".loader");
  if (!loader) return;
  window.addEventListener("load", () => loader.classList.add("is-hidden"));
}

function initNavToggle() {
  const toggle = document.querySelector(".nav-toggle");
  const nav = document.querySelector(".site-nav");
  if (!toggle || !nav) return;

  toggle.addEventListener("click", () => {
    const isOpen = nav.classList.toggle("is-open");
    toggle.setAttribute("aria-expanded", String(isOpen));
  });

  nav.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
      nav.classList.remove("is-open");
      toggle.setAttribute("aria-expanded", "false");
    });
  });
}

function initRevealOnScroll() {
  const items = document.querySelectorAll(".reveal");
  if (!items.length) return;

  if (!("IntersectionObserver" in window)) {
    items.forEach((el) => el.classList.add("is-active"));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-active");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15 }
  );

  items.forEach((el) => observer.observe(el));
}

function initBackToTop() {
  const button = document.querySelector(".back-to-top");
  if (!button) return;

  window.addEventListener("scroll", () => {
    button.classList.toggle("is-visible", window.scrollY > 400);
  });

  button.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
}

function initPortfolioFilter() {
  const buttons = document.querySelectorAll(".filter-btn");
  const cards = document.querySelectorAll(".portfolio-card");
  if (!buttons.length || !cards.length) return;

  buttons.forEach((button) => {
    button.addEventListener("click", () => {
      buttons.forEach((b) => b.classList.remove("active"));
      button.classList.add("active");

      const filter = button.dataset.filter;
      cards.forEach((card) => {
        const matches = filter === "all" || card.dataset.category === filter;
        card.hidden = !matches;
      });
    });
  });
}

function initTestimonialSlider() {
  const slider = document.querySelector(".testimonial-slider");
  const cards = document.querySelectorAll(".testimonial-card");
  const dotsWrap = document.querySelector(".dots");
  if (!slider || !cards.length || !dotsWrap) return;

  let current = Math.max(0, [...cards].findIndex((c) => c.classList.contains("active")));

  cards.forEach((_, index) => {
    const dot = document.createElement("button");
    dot.type = "button";
    dot.setAttribute("aria-label", `Show testimonial ${index + 1}`);
    if (index === current) dot.classList.add("active");
    dot.addEventListener("click", () => goTo(index));
    dotsWrap.appendChild(dot);
  });

  function goTo(index) {
    cards[current].classList.remove("active");
    dotsWrap.children[current].classList.remove("active");
    current = index;
    cards[current].classList.add("active");
    dotsWrap.children[current].classList.add("active");
  }

  setInterval(() => {
    goTo((current + 1) % cards.length);
  }, 6000);
}

function initStatCounters() {
  const stats = document.querySelectorAll(".stat strong[data-count]");
  if (!stats.length) return;

  const animate = (el) => {
    const target = Number(el.dataset.count);
    const duration = 1200;
    const start = performance.now();

    const step = (now) => {
      const progress = Math.min((now - start) / duration, 1);
      el.textContent = Math.round(target * progress);
      if (progress < 1) requestAnimationFrame(step);
    };

    requestAnimationFrame(step);
  };

  if (!("IntersectionObserver" in window)) {
    stats.forEach(animate);
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animate(entry.target);
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.5 }
  );

  stats.forEach((el) => observer.observe(el));
}
