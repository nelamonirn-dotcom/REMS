(() => {
  const searchForm = document.querySelector("#property-search");
  const locationInput = document.querySelector("#location");
  const typeSelect = document.querySelector("#property-type");
  const budgetSelect = document.querySelector("#budget");
  const cards = [...document.querySelectorAll(".property-card")];
  const resultCount = document.querySelector("#results-count");
  const emptyState = document.querySelector("#empty-state");
  const tabs = [...document.querySelectorAll(".search-tab")];
  const menuToggle = document.querySelector(".menu-toggle");
  const navLinks = document.querySelector("#nav-links");
  const budgetLabel = document.querySelector('label[for="budget"]');
  const contactForm = document.querySelector("#contact-form-element");
  let listingMode = "buy";

  if (searchForm && locationInput && typeSelect && budgetSelect && budgetLabel && resultCount && emptyState) {
    const updateBudgetOptions = () => {
      const options = listingMode === "rent"
        ? [
            ["", "Any monthly rent"],
            ["50000", "Up to ৳50,000 / month"],
            ["100000", "Up to ৳1 lakh / month"],
            ["150000", "Up to ৳1.5 lakh / month"]
          ]
        : [
            ["", "Any budget"],
            ["10000000", "Up to ৳1 crore"],
            ["20000000", "Up to ৳2 crore"],
            ["30000000", "Up to ৳3 crore"]
          ];
      budgetSelect.replaceChildren(...options.map(([value, label]) => {
        const option = document.createElement("option");
        option.value = value;
        option.textContent = label;
        return option;
      }));
      budgetLabel.textContent = listingMode === "rent" ? "Maximum monthly rent" : "Maximum budget";
    };

    const applyFilters = () => {
      const location = locationInput.value.trim().toLowerCase();
      const propertyType = typeSelect.value;
      const maxBudget = Number(budgetSelect.value) || Infinity;
      let visibleCount = 0;

      cards.forEach((card) => {
        const matchesLocation = !location || card.dataset.location.includes(location);
        const matchesType = !propertyType || card.dataset.type === propertyType;
        const matchesBudget = Number(card.dataset.price) <= maxBudget;
        const matchesMode = card.dataset.mode === listingMode;
        const visible = matchesLocation && matchesType && matchesBudget && matchesMode;

        card.hidden = !visible;
        if (visible) visibleCount += 1;
      });

      emptyState.style.display = visibleCount > 0 ? "none" : "block";
      resultCount.textContent = visibleCount === 1
        ? "Showing 1 handpicked home"
        : `Showing ${visibleCount} handpicked homes`;
    };

    searchForm.addEventListener("submit", (event) => {
      event.preventDefault();
      applyFilters();
      document.querySelector("#properties")?.scrollIntoView({ behavior: "smooth" });
    });

    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        listingMode = tab.dataset.mode;
        tabs.forEach((item) => item.setAttribute("aria-selected", String(item === tab)));
        updateBudgetOptions();
        applyFilters();
      });
    });

    applyFilters();
  }

  document.querySelectorAll(".favorite").forEach((button) => {
    button.addEventListener("click", () => {
      const saved = button.getAttribute("aria-pressed") !== "true";
      button.setAttribute("aria-pressed", String(saved));
      button.setAttribute(
        "aria-label",
        `${saved ? "Remove" : "Save"} ${button.getAttribute("aria-label").replace(/^(Save|Remove) /, "")}`
      );
    });
  });

  if (menuToggle && navLinks) {
    menuToggle.addEventListener("click", () => {
      const isOpen = menuToggle.getAttribute("aria-expanded") === "true";
      menuToggle.setAttribute("aria-expanded", String(!isOpen));
      menuToggle.setAttribute("aria-label", isOpen ? "Open navigation menu" : "Close navigation menu");
      navLinks.classList.toggle("is-open", !isOpen);
    });

    navLinks.addEventListener("click", (event) => {
      if (event.target.closest("a")) {
        menuToggle.setAttribute("aria-expanded", "false");
        menuToggle.setAttribute("aria-label", "Open navigation menu");
        navLinks.classList.remove("is-open");
      }
    });
  }

  if (contactForm) {
    contactForm.addEventListener("submit", (event) => {
      event.preventDefault();
      const status = document.querySelector("#contact-form-status");
      status.textContent = "Your message passed the form checks. This demo does not send or store inquiries; please call or email our team directly.";
    });
  }
})();
