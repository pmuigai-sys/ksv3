const KVS = (() => {
    const endpoints = {
        auth: '/backend/api/auth.php',
        elections: '/backend/api/election.php',
        vote: '/backend/api/vote.php',
        blockchain: '/backend/api/blockchain.php',
    };

    const jsonRequest = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
                ...options.headers,
            },
            ...options,
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(data.message || 'Request failed');
            error.payload = data;
            throw error;
        }

        return data;
    };

    const postJson = (url, payload) => jsonRequest(url, {
        method: 'POST',
        body: JSON.stringify(payload),
    });

    const getJson = (url, params = {}) => {
        const query = new URLSearchParams(params).toString();
        const finalUrl = query ? `${url}?${query}` : url;
        return jsonRequest(finalUrl);
    };

    const serializeForm = (form) => {
        const formData = new FormData(form);
        return Object.fromEntries(formData.entries());
    };

    return { endpoints, postJson, getJson, serializeForm };
})();

const highlightActiveNav = () => {
    const navLinks = document.querySelectorAll('.app-header__nav .nav-link');
    const currentUrl = window.location.href;
    navLinks.forEach((link) => {
        if (currentUrl.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
};

const setupLogout = () => {
    const logoutButton = document.getElementById('logoutButton');
    if (!logoutButton) return;

    logoutButton.addEventListener('click', async () => {
        logoutButton.disabled = true;
        try {
            await KVS.postJson(`${KVS.endpoints.auth}?action=logout`, {});
            window.location.href = '/index.php';
        } catch (error) {
            console.error(error);
            logoutButton.disabled = false;
        }
    });
};

const setupAuthForms = () => {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    if (!loginForm || !registerForm) return;

    const switchLinks = document.querySelectorAll('[data-switch]');
    switchLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const target = link.getAttribute('data-switch');
            if (target === 'register') {
                loginForm.classList.remove('active');
                registerForm.classList.add('active');
            } else {
                registerForm.classList.remove('active');
                loginForm.classList.add('active');
            }
        });
    });

    const handleSubmit = (form, action) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const feedback = form.querySelector('.form-feedback');
            feedback.textContent = 'Processing...';
            feedback.classList.remove('error', 'success');

            try {
                const payload = KVS.serializeForm(form);
                const response = await KVS.postJson(`${KVS.endpoints.auth}?action=${action}`, payload);
                feedback.textContent = response.message;
                feedback.classList.add('success');
                window.location.href = '/index.php';
            } catch (error) {
                feedback.textContent = error.payload?.message || error.message;
                feedback.classList.add('error');
            }
        });
    };

    handleSubmit(loginForm, 'login');
    handleSubmit(registerForm, 'register');
};

document.addEventListener('DOMContentLoaded', () => {
    highlightActiveNav();
    setupLogout();
    setupAuthForms();
});

window.KVS = KVS;

