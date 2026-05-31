const toast = document.createElement('div');
toast.className = 'toast';
document.body.appendChild(toast);

function showToast(message) {
    toast.textContent = message;
    toast.classList.add('show');
    clearTimeout(toast.hideTimeout);
    toast.hideTimeout = setTimeout(() => toast.classList.remove('show'), 3200);
}

function showModal(html, title = 'Notice') {
    let backdrop = document.querySelector('.modal-backdrop');
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop';
        backdrop.innerHTML = `
            <div class="modal" role="dialog" aria-modal="true">
                <header>
                    <h3>${title}</h3>
                    <button class="close-btn" type="button" aria-label="Close">×</button>
                </header>
                <div class="modal-body"></div>
            </div>
        `;
        document.body.appendChild(backdrop);
        backdrop.querySelector('.close-btn').addEventListener('click', closeModal);
        backdrop.addEventListener('click', (event) => {
            if (event.target === backdrop) closeModal();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeModal();
        });
    }
    backdrop.querySelector('header h3').textContent = title;
    backdrop.querySelector('.modal-body').innerHTML = html;
    backdrop.classList.add('show');
}

function closeModal() {
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) backdrop.classList.remove('show');
}

function showConfirmModal(onConfirm, title = 'Are you sure?', message = 'Please confirm your choice.', confirmText = 'Confirm', cancelText = 'Cancel') {
    showModal(`
        <p class="modal-message">${message}</p>
        <div class="modal-actions">
            <button type="button" class="btn secondary modal-cancel">${cancelText}</button>
            <button type="button" class="btn primary modal-confirm">${confirmText}</button>
        </div>
    `, title);
    const backdrop = document.querySelector('.modal-backdrop');
    if (!backdrop) return;
    const confirmButton = backdrop.querySelector('.modal-confirm');
    const cancelButton = backdrop.querySelector('.modal-cancel');
    confirmButton?.addEventListener('click', () => {
        closeModal();
        onConfirm();
    });
    cancelButton?.addEventListener('click', closeModal);
}

function showLogoutConfirmation(href) {
    showConfirmModal(
        () => {
            window.location.href = href;
        },
        'Logout confirmation',
        'Are you sure you want to logout?',
        'Logout',
        'Cancel'
    );
}

function showAuthModal() {
    showModal(`
        <p class="modal-message">Please login or create an account to continue.</p>
        <div class="modal-actions">
            <button type="button" class="btn secondary modal-cancel">Cancel</button>
            <a href="login.php" class="btn secondary">Login</a>
            <a href="register.php" class="btn primary">Register</a>
        </div>
    `, 'Authentication required');
    const backdrop = document.querySelector('.modal-backdrop');
    if (!backdrop) return;
    const cancelButton = backdrop.querySelector('.modal-cancel');
    cancelButton?.addEventListener('click', closeModal);
}



function isLoggedInOnPage() {
    return Boolean(
        document.querySelector('[data-confirm-logout]') ||
        document.getElementById('profile-menu-button')
    );
}

function getJobIdFromActionButton(button) {
    if (!button) return null;
    return button.dataset.jobId || button.value || null;
}

function updateSaveButtonState(button, saved) {
    if (!button) return;
    button.dataset.saved = saved ? '1' : '0';
    button.classList.toggle('saved', saved);
    const icon = button.querySelector('i');
    const label = button.querySelector('.save-label');
    if (icon) {
        icon.classList.toggle('fa-solid', saved);
        icon.classList.toggle('fa-regular', !saved);
    }
    if (label) {
        label.textContent = saved ? 'Saved' : 'Save Job';
    }
}

function updateApplyButtonState(button, status) {
    if (!button) return;
    const applied = status !== 'not_applied';
    button.dataset.applied = applied ? '1' : '0';
    const icon = button.querySelector('i');
    const label = button.querySelector('.apply-label');
    if (icon) {
        icon.classList.toggle('fa-solid', applied);
        icon.classList.toggle('fa-regular', !applied);
    }
    if (label) {
        if (status === 'pending') label.textContent = 'Pending';
        else if (status === 'accepted') label.textContent = 'Accepted';
        else if (status === 'rejected') label.textContent = 'Rejected';
        else label.textContent = 'Apply';
    }
    button.disabled = applied;
}

async function sendAction(url, params, button, type) {
    if (!button) return null;
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams(params),
        });
        const data = await response.json();
        if (!data.success) {
            showToast(data.error || 'Unable to complete the request.');
            return null;
        }
        if (type === 'save') {
            updateSaveButtonState(button, !!data.saved);
            showToast(data.saved ? 'Job saved.' : 'Removed from saved jobs.');
        } else if (type === 'apply') {
            updateApplyButtonState(button, data.status || 'pending');
            showToast(data.message || 'Application updated.');
        }
        return data;
    } catch (error) {
        console.error(error);
        showToast('Request failed. Please try again.');
        return null;
    }
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview || !input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = (event) => {
        preview.src = event.target.result;
    };
    reader.readAsDataURL(input.files[0]);
}

function fillSuggestion(value) {
    const input = document.getElementById('searchQuery');
    if (input) input.value = value;
    const box = document.getElementById('suggestions');
    if (box) box.style.display = 'none';
}

function createSuggestionItem(text) {
    const item = document.createElement('div');
    item.className = 'suggestion-item';
    item.textContent = text;
    item.addEventListener('click', () => fillSuggestion(text));
    return item;
}

function toggleTheme() {
    const activeTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = activeTheme;
    localStorage.setItem('jobdz_theme', activeTheme);
    const icon = document.querySelector('.theme-toggle i');
    if (icon) icon.className = activeTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
}

function restoreTheme() {
    const stored = localStorage.getItem('jobdz_theme');
    if (stored) {
        document.documentElement.dataset.theme = stored;
        const icon = document.querySelector('.theme-toggle i');
        if (icon) icon.className = stored === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
}

function initializeSuggestionBox(inputId, suggestionId, apiPath) {
    const input = document.getElementById(inputId);
    const box = document.getElementById(suggestionId);
    if (!input || !box) return;
    let timeout;
    input.addEventListener('input', () => {
        const query = input.value.trim();
        if (timeout) clearTimeout(timeout);
        if (!query || query.length < 2) {
            box.style.display = 'none';
            box.innerHTML = '';
            return;
        }
        timeout = setTimeout(async () => {
            try {
                const res = await fetch(`${apiPath}?q=${encodeURIComponent(query)}`);
                const data = await res.json();
                box.innerHTML = '';
                if (!Array.isArray(data) || data.length === 0) {
                    box.style.display = 'none';
                    return;
                }
                data.forEach(item => box.appendChild(createSuggestionItem(item)));
                box.style.display = 'block';
            } catch (error) {
                box.style.display = 'none';
            }
        }, 200);
    });
    document.addEventListener('click', (event) => {
        if (!box.contains(event.target) && event.target !== input) {
            box.style.display = 'none';
        }
    });
}

function wireActionButtons() {
    const profileButton = document.getElementById('profile-menu-button');
    const profilePanel = document.getElementById('profile-menu-panel');
    const logoutLinks = document.querySelectorAll('[data-confirm-logout]');

    const setProfileMenuOpen = (open) => {
        if (!profilePanel) return;
        if (open) {
            profilePanel.classList.remove('opacity-0', 'translate-y-2', 'invisible');
            profilePanel.classList.add('opacity-100', 'translate-y-0', 'visible');
            profileButton?.setAttribute('aria-expanded', 'true');
        } else {
            profilePanel.classList.remove('opacity-100', 'translate-y-0', 'visible');
            profilePanel.classList.add('opacity-0', 'translate-y-2', 'invisible');
            profileButton?.setAttribute('aria-expanded', 'false');
        }
    };

    const isProfileMenuOpen = () => profilePanel && profilePanel.classList.contains('opacity-100');

    if (profileButton) {
        profileButton.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            setProfileMenuOpen(!isProfileMenuOpen());
        });
    }

    logoutLinks.forEach(link => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const href = link.getAttribute('href');
            if (href) {
                showLogoutConfirmation(href);
            }
        });
    });

    document.body.addEventListener('click', async (event) => {
        const logoutLink = event.target.closest('[data-confirm-logout]');
        const confirmDelete = event.target.closest('[data-confirm-delete]');
        const saveButton = event.target.closest('[data-action="save"]');
        const applyButton = event.target.closest('[data-action="apply"]');
        const viewButton = event.target.closest('[data-action="viewjob"]');
        const authGuardLink = event.target.closest('[data-requires-auth]');
        const cvDownloadLink = event.target.closest('a[href*="cv_download.php"]');
        const authGuardForm = event.target.closest('form[data-requires-auth]');
        const themeToggle = event.target.closest('.theme-toggle');
        const navToggle = event.target.closest('.nav-toggle');

        if (profilePanel && !event.target.closest('#profile-menu-panel') && !event.target.closest('#profile-menu-button')) {
            setProfileMenuOpen(false);
        }
        if (confirmDelete) {
            event.preventDefault();
            const form = confirmDelete.closest('form');
            if (form) {
                showConfirmModal(() => form.submit(), 'Delete account', 'This will permanently remove your account and all associated data.', 'Delete', 'Cancel');
            }
            return;
        }
        if (themeToggle) {
            event.preventDefault();
            toggleTheme();
            return;
        }
        if (navToggle) {
            const nav = document.querySelector('.site-navbar');
            nav?.classList.toggle('open');
            return;
        }
        if (logoutLink) {
            const href = logoutLink.getAttribute('href');
            if (href) {
                event.preventDefault();
                showLogoutConfirmation(href);
            }
            return;
        }
        if (!isLoggedInOnPage() && (authGuardLink || cvDownloadLink || authGuardForm)) {
            event.preventDefault();
            showAuthModal();
            return;
        }
        if (saveButton) {
            const jobId = getJobIdFromActionButton(saveButton);
            if (!jobId) return;
            event.preventDefault();
            event.stopPropagation();
            if (!isLoggedInOnPage()) {
                showAuthModal();
                return;
            }
            await sendAction('save_job.php', { job_id: jobId }, saveButton, 'save');
            return;
        }
        if (applyButton) {
            const jobId = getJobIdFromActionButton(applyButton);
            if (!jobId) return;
            event.preventDefault();
            event.stopPropagation();
            if (!isLoggedInOnPage()) {
                showAuthModal();
                return;
            }
            await sendAction('apply_job.php', { job_id: jobId }, applyButton, 'apply');
            return;
        }
        if (viewButton) {
            const jobId = getJobIdFromActionButton(viewButton);
            if (!jobId) return;
            event.preventDefault();
            event.stopPropagation();
            if (!isLoggedInOnPage()) {
                showAuthModal();
                return;
            }
            window.location.href = `job.php?id=${jobId}`;
            return;
        }
    });
}

function showLoginModal() {
    showAuthModal();
}

function showRegisterModal() {
    window.location.href = 'register.php';
}

function initStarRating() {
    const starRating = document.getElementById('star-rating');
    if (!starRating) return;
    const stars = starRating.querySelectorAll('label');
    const radios = starRating.querySelectorAll('input[type="radio"]');
    let selectedRating = 0;

    // Set initial rating from checked radio
    radios.forEach(radio => {
        if (radio.checked) {
            selectedRating = parseInt(radio.value);
        }
    });
    updateStars(selectedRating);

    stars.forEach((star, index) => {
        star.addEventListener('click', () => {
            selectedRating = parseInt(star.dataset.rating);
            updateStars(selectedRating);
            // Update the radio button
            const radio = document.getElementById(`star${selectedRating}`);
            if (radio) radio.checked = true;
        });
        star.addEventListener('mouseenter', () => {
            updateStars(parseInt(star.dataset.rating));
        });
        star.addEventListener('mouseleave', () => {
            updateStars(selectedRating);
        });
    });

    function updateStars(rating) {
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('text-slate-300');
                star.classList.add('text-yellow-400');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-slate-300');
            }
        });
    }
}

function initPage() {
    restoreTheme();
    wireActionButtons();
    initStarRating();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPage);
} else {
    initPage();
}

