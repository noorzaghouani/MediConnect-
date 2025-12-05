// Gestion du formulaire de connexion
class LoginForm {
    constructor() {
        this.form = document.querySelector('.login-form');
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeValidation();
        this.checkForUrlParameters();
    }

    setupEventListeners() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Validation en temps réel
        const inputs = this.form.querySelectorAll('input[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearError(input));
        });

        // Gestion du mot de passe oublié
        const forgotPasswordLink = document.querySelector('.forgot-password');
        if (forgotPasswordLink) {
            forgotPasswordLink.addEventListener('click', (e) => this.handleForgotPassword(e));
        }

        // Gestion de l'inscription
        const signupLink = document.querySelector('.signup-link');
        if (signupLink) {
            signupLink.addEventListener('click', (e) => this.handleSignup(e));
        }
    }

    setupRealTimeValidation() {
        // Validation de l'email en temps réel
        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.addEventListener('blur', () => this.validateEmail(emailInput));
        }

        // Validation du mot de passe en temps réel
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('blur', () => this.validatePassword(passwordInput));
        }
    }

    checkForUrlParameters() {
        const urlParams = new URLSearchParams(window.location.search);

        // Vérifier si l'utilisateur vient de s'inscrire
        if (urlParams.get('registered') === 'true') {
            this.showNotification('Compte créé avec succès ! Vous pouvez maintenant vous connecter.', 'success');
        }

        // Vérifier si l'utilisateur a été déconnecté
        if (urlParams.get('logout') === 'true') {
            this.showNotification('Vous avez été déconnecté avec succès.', 'info');
        }
    }

    validateField(input) {
        const value = input.value.trim();
        const fieldName = input.name || input.id;

        switch (fieldName) {
            case '_username':
            case 'email':
                return this.validateEmail(input, value);
            case '_password':
            case 'password':
                return this.validatePassword(input, value);
        }
    }

    validateEmail(input, value) {
        if (!value) {
            this.showError(input, 'L\'email est obligatoire');
            return false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            this.showError(input, 'Format d\'email invalide');
            return false;
        }
        this.showSuccess(input);
        return true;
    }

    validatePassword(input, value) {
        if (!value) {
            this.showError(input, 'Le mot de passe est obligatoire');
            return false;
        }
        if (value.length < 6) {
            this.showError(input, 'Le mot de passe doit contenir au moins 6 caractères');
            return false;
        }
        this.showSuccess(input);
        return true;
    }

    showError(input, message) {
        this.clearError(input);

        const formGroup = input.closest('.form-group');
        formGroup.classList.add('error');

        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';

        formGroup.appendChild(errorDiv);
    }

    showSuccess(input) {
        this.clearError(input);
        const formGroup = input.closest('.form-group');
        formGroup.classList.remove('error');
        formGroup.classList.add('success');
    }

    clearError(input) {
        const formGroup = input.closest('.form-group');
        formGroup.classList.remove('error', 'success');
        const errorDiv = formGroup.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    async handleSubmit(e) {
        e.preventDefault();

        // Valider tous les champs
        const inputs = this.form.querySelectorAll('input[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        if (!isValid) {
            this.showNotification('Veuillez corriger les erreurs dans le formulaire', 'error');
            return;
        }

        // Soumettre le formulaire
        this.submitForm();
    }

    submitForm() {
        this.setLoading(true);
        // Soumettre le formulaire au serveur Symfony
        // Symfony gérera l'authentification et la redirection
        this.form.submit();
    }

    handleForgotPassword(e) {
        e.preventDefault();
        // Redirection vers la page mot de passe oublié
        window.location.href = '/forgot-password';
    }

    handleSignup(e) {
        e.preventDefault();
        // Redirection vers la page d'inscription
        window.location.href = '/register';
    }

    setLoading(loading) {
        const button = this.form.querySelector('.login-btn');
        if (loading) {
            button.innerHTML = '<span class="spinner"></span>Connexion...';
            button.disabled = true;
            this.form.classList.add('loading');
        } else {
            button.innerHTML = 'Se connecter';
            button.disabled = false;
            this.form.classList.remove('loading');
        }
    }

    showNotification(message, type = 'info') {
        // Créer une notification temporaire
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : 'success'}`;
        notification.textContent = message;

        const welcomeSection = document.querySelector('.welcome-section');
        welcomeSection.parentNode.insertBefore(notification, welcomeSection.nextSibling);

        // Supprimer après 5 secondes
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

// Fonctions globales
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const button = document.querySelector('.toggle-password');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        button.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
        `;
    } else {
        passwordInput.type = 'password';
        button.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        `;
    }
}

// Initialisation quand la page est chargée
document.addEventListener('DOMContentLoaded', () => {
    new LoginForm();

    // Configurer le toggle du mot de passe
    const toggleButton = document.querySelector('.toggle-password');
    if (toggleButton) {
        toggleButton.addEventListener('click', togglePassword);
    }
});