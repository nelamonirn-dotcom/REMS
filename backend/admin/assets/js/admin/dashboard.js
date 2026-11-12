(() => {
  const layout = document.querySelector("#admin-layout");
  const sidebar = document.querySelector("#admin-sidebar");
  const collapseButton = document.querySelector("#sidebar-collapse");
  const mobileMenuButton = document.querySelector("#mobile-menu-toggle");
  const sidebarBackdrop = document.querySelector("#sidebar-backdrop");
  const propertyDialog = document.querySelector("#property-dialog");
  const propertyForm = document.querySelector("#property-form");
  const formNotice = document.querySelector("#property-form-notice");
  const bookingSearch = document.querySelector("#booking-search");
  const bookingStatus = document.querySelector("#booking-status");
  const bookingRows = [...document.querySelectorAll("#booking-rows tr")];
  const emptyBookings = document.querySelector("#empty-bookings");
  const bookingCount = document.querySelector("#booking-count");
  const revenuePeriod = document.querySelector("#revenue-period");

  const updateDateAndGreeting = () => {
    const now = new Date();
    const dateNode = document.querySelector("#current-date");
    const greetingNode = document.querySelector("#greeting");

    dateNode.textContent = new Intl.DateTimeFormat("en", {
      weekday: "short",
      month: "short",
      day: "numeric"
    }).format(now);

    const hour = now.getHours();
    greetingNode.textContent = hour < 12 ? "Good morning" : hour < 18 ? "Good afternoon" : "Good evening";
  };

  const setMobileMenuOpen = (isOpen) => {
    sidebar.classList.toggle("is-open", isOpen);
    sidebarBackdrop.hidden = !isOpen;
    mobileMenuButton.setAttribute("aria-expanded", String(isOpen));
    mobileMenuButton.setAttribute("aria-label", isOpen ? "Close navigation" : "Open navigation");
    mobileMenuButton.innerHTML = `<i class="fa-solid ${isOpen ? "fa-xmark" : "fa-bars"}" aria-hidden="true"></i>`;
    document.body.classList.toggle("menu-is-open", isOpen);
  };

  collapseButton.addEventListener("click", () => {
    const isCollapsed = layout.classList.toggle("is-collapsed");
    collapseButton.setAttribute("aria-expanded", String(!isCollapsed));
    collapseButton.setAttribute("aria-label", isCollapsed ? "Expand sidebar" : "Collapse sidebar");
    collapseButton.title = isCollapsed ? "Expand sidebar" : "Collapse sidebar";
    collapseButton.innerHTML = `<i class="fa-solid ${isCollapsed ? "fa-angles-right" : "fa-angles-left"}" aria-hidden="true"></i>`;
  });

  mobileMenuButton.addEventListener("click", () => {
    setMobileMenuOpen(mobileMenuButton.getAttribute("aria-expanded") !== "true");
  });
  sidebarBackdrop.addEventListener("click", () => setMobileMenuOpen(false));
  sidebar.addEventListener("click", (event) => {
    if (event.target.closest('a[href^="#"]')) setMobileMenuOpen(false);
  });
  window.addEventListener("resize", () => {
    if (window.innerWidth > 700) setMobileMenuOpen(false);
  });

  document.querySelector("#open-property-modal").addEventListener("click", () => {
    formNotice.hidden = true;
    propertyDialog.showModal();
  });

  const closePropertyDialog = () => {
    propertyDialog.close();
  };

  propertyDialog.addEventListener("close", () => {
    propertyForm.reset();
    formNotice.hidden = true;
  });

  document.querySelector("#close-property-modal").addEventListener("click", closePropertyDialog);
  document.querySelector("#cancel-property-modal").addEventListener("click", closePropertyDialog);
  propertyDialog.addEventListener("click", (event) => {
    if (event.target === propertyDialog) closePropertyDialog();
  });

  propertyForm.addEventListener("submit", (event) => {
    event.preventDefault();
    formNotice.textContent = "Preview only — property details have not been saved.";
    formNotice.hidden = false;
  });

  const filterBookings = () => {
    const query = bookingSearch.value.trim().toLowerCase();
    const selectedStatus = bookingStatus.value;
    let visibleRows = 0;

    bookingRows.forEach((row) => {
      const matchesQuery = row.textContent.toLowerCase().includes(query);
      const matchesStatus = selectedStatus === "all" || row.dataset.status === selectedStatus;
      const isVisible = matchesQuery && matchesStatus;
      row.hidden = !isVisible;
      if (isVisible) visibleRows += 1;
    });

    emptyBookings.hidden = visibleRows > 0;
    bookingCount.textContent = `Showing ${visibleRows} sample booking${visibleRows === 1 ? "" : "s"}`;
  };

  bookingSearch.addEventListener("input", filterBookings);
  bookingStatus.addEventListener("change", filterBookings);

  const chartColors = {
    navy: "#4b80c2",
    green: "#61b99a",
    amber: "#edbd68",
    gray: "#a6afbf",
    grid: "#edf0f5",
    label: "#98a2b1"
  };

  const revenueChartNode = document.querySelector("#revenue-chart");
  const inventoryChartNode = document.querySelector("#inventory-chart");
  let revenueChart;

  const showChartFallback = (chartNode, fallbackNode) => {
    chartNode.hidden = true;
    fallbackNode.hidden = false;
  };

  if (typeof Chart === "undefined") {
    showChartFallback(revenueChartNode, document.querySelector("#revenue-fallback"));
    showChartFallback(inventoryChartNode, document.querySelector("#inventory-fallback"));
  } else {
    Chart.defaults.font.family = '"Inter", "Segoe UI", Arial, sans-serif';
    Chart.defaults.color = chartColors.label;

    revenueChart = new Chart(revenueChartNode, {
      type: "line",
      data: {
        labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
        datasets: [{
          label: "Revenue",
          data: [1.4, 2.1, 1.8, 2.9, 2.2, 3.5, 2.7],
          borderColor: chartColors.navy,
          backgroundColor: (context) => {
            const { ctx, chartArea } = context.chart;
            if (!chartArea) return "rgb(75 128 194 / 10%)";
            const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            gradient.addColorStop(0, "rgb(75 128 194 / 22%)");
            gradient.addColorStop(1, "rgb(75 128 194 / 0%)");
            return gradient;
          },
          borderWidth: 2,
          fill: true,
          tension: 0.4,
          pointRadius: 0,
          pointHoverRadius: 4,
          pointHoverBackgroundColor: chartColors.navy
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: "index" },
        plugins: {
          legend: { display: false },
          tooltip: {
            displayColors: false,
            backgroundColor: "#172744",
            padding: 9,
            titleFont: { size: 9, weight: "600" },
            bodyFont: { size: 9 },
            callbacks: { label: (context) => ` ৳${context.parsed.y.toFixed(1)}L` }
          }
        },
        scales: {
          x: {
            grid: { display: false, drawTicks: false },
            border: { display: false },
            ticks: { color: chartColors.label, font: { size: 8 }, padding: 8 }
          },
          y: {
            beginAtZero: true,
            suggestedMax: 4,
            grid: { color: chartColors.grid, drawTicks: false },
            border: { display: false, dash: [3, 4] },
            ticks: {
              stepSize: 1,
              color: chartColors.label,
              font: { size: 8 },
              padding: 9,
              callback: (value) => `৳${value}L`
            }
          }
        }
      }
    });

    new Chart(inventoryChartNode, {
      type: "doughnut",
      data: {
        labels: ["Available", "Rented", "Sold", "In review"],
        datasets: [{
          data: [142, 68, 24, 14],
          backgroundColor: [chartColors.navy, chartColors.green, chartColors.amber, chartColors.gray],
          borderWidth: 0,
          hoverOffset: 3
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "76%",
        plugins: {
          legend: { display: false },
          tooltip: {
            displayColors: false,
            backgroundColor: "#172744",
            padding: 8,
            titleFont: { size: 9, weight: "600" },
            bodyFont: { size: 9 }
          }
        }
      }
    });
  }

  revenuePeriod.addEventListener("change", () => {
    if (!revenueChart) return;

    const useMonth = revenuePeriod.value === "30";
    revenueChart.data.labels = useMonth
      ? ["Week 1", "Week 2", "Week 3", "Week 4"]
      : ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
    revenueChart.data.datasets[0].data = useMonth
      ? [9.4, 11.2, 10.1, 12.6]
      : [1.4, 2.1, 1.8, 2.9, 2.2, 3.5, 2.7];
    revenueChart.update();
  });

  updateDateAndGreeting();
})();
