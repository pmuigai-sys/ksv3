const KVS = (() => {
    // Get base path from meta tag or default to empty string
    const getBasePath = () => {
        const basePathMeta = document.querySelector('meta[name="base-path"]');
        return basePathMeta ? basePathMeta.getAttribute('content') || '' : '';
    };
    
    const basePath = getBasePath();
    
    const endpoints = {
        auth: basePath + '/backend/api/auth.php',
        elections: basePath + '/backend/api/election.php',
        vote: basePath + '/backend/api/vote.php',
        blockchain: basePath + '/backend/api/blockchain.php',
    };
    
    const getIndexPath = () => getBasePath() + '/index.php';

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

        let data = {};
        let text = await response.text();
        if (text) {
            // Remove any PHP warnings or HTML that might precede the JSON
            // Look for JSON starting with { or [
            const jsonMatch = text.match(/(\{|\[).*$/s);
            if (jsonMatch) {
                text = jsonMatch[0];
            }
            
            try {
                data = JSON.parse(text);
            } catch (e) {
                // If JSON parsing fails, try to extract JSON from the response
                // Look for the actual JSON in the response
                const jsonPattern = /(\{[\s\S]*\}|\[[\s\S]*\])/;
                const jsonFound = text.match(jsonPattern);
                if (jsonFound) {
                    try {
                        data = JSON.parse(jsonFound[1]);
                    } catch (e2) {
                        data = { message: text || 'Request failed' };
                    }
                } else {
                    data = { message: text || 'Request failed' };
                }
            }
        }

        if (!response.ok) {
            // Prioritize payload message, then fallback to status text
            const errorMessage = data.message || data.error || response.statusText || 'Request failed';
            const error = new Error(errorMessage);
            error.payload = data;
            error.status = response.status;
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

    return { endpoints, postJson, getJson, serializeForm, getIndexPath, getBasePath };
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
            window.location.href = KVS.getIndexPath();
        } catch (error) {
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
                window.location.href = KVS.getIndexPath();
            } catch (error) {
                // Extract error message from various possible locations
                const errorMessage = error.payload?.message || error.message || 'Request failed';
                feedback.textContent = errorMessage;
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

