function validateForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return true;

  const inputs = form.querySelectorAll("input[required]");
  let isValid = true;

  inputs.forEach((input) => {
    if (!input.value.trim()) {
      input.style.borderColor = "red";
      isValid = false;
    } else {
      input.style.borderColor = "#ccc";
    }
  });

  return isValid;
}

document.addEventListener("DOMContentLoaded", function () {
  const forms = document.querySelectorAll("form");
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      if (!validateForm(form.id)) {
        e.preventDefault();
        alert("Please fill in all required fields");
      }
    });
  });

  const navLinks = document.querySelectorAll('.nav-links a[href^="#"]');
  navLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        target.scrollIntoView({ behavior: "smooth" });
      }
    });
  });

  const modal = document.getElementById("loginModal");
  const modalClose = document.querySelector(".modal-close");
  const isLoggedIn = document.body.dataset.isLoggedIn === "true";

  document.addEventListener("click", function (e) {
    const button = e.target.closest(
      '[data-action="apply"], [data-action="save"]',
    );
    if (!button) return;

    e.preventDefault();
    const action = button.dataset.action;
    const jobId = button.dataset.jobId;

    if (!isLoggedIn) {
      modal.style.display = "flex";
      return;
    }

    if (action === "apply") {
      const applied = button.dataset.applied === "1";
      if (applied) return;

      button.disabled = true;
      button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';

      fetch("apply_job.php?id=" + jobId, {
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            button.classList.add("applied");
            button.dataset.applied = "1";
            button.querySelector(".apply-label").textContent = "Applied";
            button.querySelector("i").classList.remove("fa-regular");
            button.querySelector("i").classList.add("fa-solid");
            alert(data.message || "Successfully applied!");
          } else {
            button.disabled = false;
            button.innerHTML =
              '<i class="fa-regular fa-check"></i><span class="apply-label">Apply Now</span>';
            alert(data.message || "Error applying for job");
          }
        })
        .catch((error) => {
          button.disabled = false;
          button.innerHTML =
            '<i class="fa-regular fa-check"></i><span class="apply-label">Apply Now</span>';
          console.error("Apply error:", error);
          alert("Error applying for job");
        });
    }

    if (action === "save") {
      window.location.href = "save_job.php?id=" + jobId;
    }
  });

  if (modalClose) {
    modalClose.addEventListener("click", function () {
      modal.style.display = "none";
    });
  }

  modal.addEventListener("click", function (e) {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  });

  console.log("JavaScript loaded successfully");

  const searchInput = document.getElementById("search");
  const citySelect = document.getElementById("city");
  const specialtySelect = document.getElementById("specialty");
  const experienceSelect = document.getElementById("experience");
  const filterForm = document.getElementById("homeFilterForm");
  const jobGrid = document.getElementById("jobResultsGrid");

  if (
    searchInput &&
    citySelect &&
    specialtySelect &&
    experienceSelect &&
    filterForm &&
    jobGrid
  ) {
    filterForm.addEventListener("submit", function (e) {
      e.preventDefault();
    });

    async function performLiveSearch() {
      const query = searchInput.value.trim();
      const city = citySelect.value;
      const specialty = specialtySelect.value;
      const experience = experienceSelect.value;

      try {
        const params = new URLSearchParams();
        if (query) params.append("q", query);
        if (city) params.append("city", city);
        if (specialty) params.append("specialty", specialty);
        if (experience) params.append("experience", experience);

        const response = await fetch("search_api.php?" + params.toString());
        const data = await response.json();

        if (data.success) {
          jobGrid.innerHTML = "";

          if (data.count === 0) {
            jobGrid.innerHTML =
              '<div class="card no-results-message"><p class="small-note">No jobs found</p></div>';
          } else {
            data.jobs.forEach((job) => {
              const jobCard = createJobCard(job);
              jobGrid.appendChild(jobCard);
            });
          }
        } else {
          jobGrid.innerHTML =
            '<div class="card"><p class="small-note">Error loading jobs</p></div>';
        }
      } catch (error) {
        console.error("Search error:", error);
        jobGrid.innerHTML =
          '<div class="card"><p class="small-note">Error loading jobs</p></div>';
      }
    }

    function createJobCard(job) {
      const card = document.createElement("div");
      card.className = "job-card compact-card";
      const isLoggedIn = document.body.dataset.isLoggedIn === "true";

      let saveButtonHtml = "";
      if (isLoggedIn) {
        saveButtonHtml = `<a href="save_job.php?id=${job.id}" class="icon-button save-job" aria-label="Save job">
                    <i class="fa-regular fa-bookmark"></i>
                </a>`;
      } else {
        saveButtonHtml = `<button class="icon-button save-job" type="button" data-action="save" data-job-id="${job.id}" aria-label="Save job">
                    <i class="fa-regular fa-bookmark"></i>
                </button>`;
      }

      let applyButtonHtml = "";
      if (isLoggedIn) {
        applyButtonHtml = `<a class="btn primary" href="job.php?id=${job.id}">Apply</a>`;
      } else {
        applyButtonHtml = `<button class="btn primary" type="button" data-action="apply" data-job-id="${job.id}">Apply</button>`;
      }

      card.innerHTML = `
                <div class="job-card-top">
                    <div class="job-logo">
                        ${job.logo_url ? `<img src="${job.logo_url}" alt="${job.company_name || "Company"} logo">` : '<span class="no-logo">No Logo</span>'}
                    </div>
                    ${saveButtonHtml}
                </div>
                <div class="job-card-body">
                    <h4>${job.title}</h4>
                    <p class="company-name">${job.company_name || "Company"}</p>
                    <div class="job-meta">
                        <span><i class="fa-solid fa-location-dot"></i> ${job.city}</span>
                    </div>
                    <div class="job-extra">
                        <span><i class="fa-solid fa-dollar-sign"></i> ${job.salary}</span>
                        <span>Posted ${job.posted_date || "recently"}</span>
                    </div>
                </div>
                <div class="job-card-footer">
                    ${applyButtonHtml}
                </div>
            `;

      return card;
    }

    let searchTimeout;
    const triggerSearch = () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(performLiveSearch, 300);
    };

    searchInput.addEventListener("input", triggerSearch);
    citySelect.addEventListener("change", performLiveSearch);
    specialtySelect.addEventListener("change", performLiveSearch);
    experienceSelect.addEventListener("change", performLiveSearch);
  }
});
