// Validation et gestion du formulaire d'inscription
class RegisterForm {
    constructor() {
        this.form = document.querySelector('.register-form');
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeValidation();
    }

    setupEventListeners() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Validation en temps réel
        const inputs = this.form.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearError(input));
        });

        // Validation pour le select spécialité
        const specialiteSelect = document.getElementById('specialite');
        if (specialiteSelect) {
            specialiteSelect.addEventListener('blur', () => this.validateField(specialiteSelect));
            specialiteSelect.addEventListener('change', () => this.clearError(specialiteSelect));
        }
    }

    setupRealTimeValidation() {
        // Validation du téléphone - seulement le formatage
        const phoneInput = document.getElementById('registration_form_telephone');
        if (phoneInput) {
            phoneInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/[^0-9+]/g, '');
            });
        }
    }

    validateField(input) {
        const value = input.value.trim();
        const fieldId = input.id;
        
        // Extraire le nom du champ de l'ID Symfony (ex: registration_form_nom -> nom)
        let fieldName = fieldId.replace('registration_form_', '');

        switch(fieldName) {
            case 'nom':
            case 'prenom':
                return this.validateName(input, value);
            case 'telephone':
                return this.validatePhone(input, value);
            case 'date_naissance':
                return this.validateBirthDate(input);
            case 'email':
                return this.validateEmail(input, value);
            case 'password':
                return this.validatePassword(input, value);
            case 'confirm_password':
                return this.validatePasswordConfirmation(input, value);
            case 'specialite':
                return this.validateSpecialite(input, value);
            default:
                return true;
        }
    }

    validateName(input, value) {
        if (!value) {
            this.showError(input, 'Ce champ est obligatoire');
            return false;
        }
        if (value.length < 2) {
            this.showError(input, 'Le nom doit contenir au moins 2 caractères');
            return false;
        }
        if (!/^[a-zA-ZÀ-ÿ\s\-']+$/.test(value)) {
            this.showError(input, 'Caractères spéciaux non autorisés');
            return false;
        }
        this.showSuccess(input);
        return true;
    }

    validatePhone(input, value) {
        if (!value) {
            this.showError(input, 'Le téléphone est obligatoire');
            return false;
        }
        
        // Nettoyer le numéro (supprimer les espaces)
        const cleanValue = value.replace(/\s/g, '');
        
        // Format Tunisie: +216 ou 00216 suivi de 8 chiffres
        // Le premier chiffre après l'indicatif ne peut pas être 0
        if (!/^(\+216|00216)[2-9][0-9]{7}$/.test(cleanValue)) {
            this.showError(input, 'Format de téléphone tunisien invalide. Exemple: +21620123456');
            return false;
        }
        
        this.showSuccess(input);
        return true;
    }

    validateBirthDate(input) {
        const value = input.value;
        if (!value) {
            this.showError(input, 'La date de naissance est obligatoire');
            return false;
        }

        const birthDate = new Date(value);
        const today = new Date();
        const minDate = new Date();
        minDate.setFullYear(today.getFullYear() - 120);
        const maxDate = new Date();
        maxDate.setFullYear(today.getFullYear() - 18);

        if (birthDate < minDate) {
            this.showError(input, 'Âge maximum dépassé (120 ans)');
            return false;
        }
        if (birthDate > maxDate) {
            this.showError(input, 'Vous devez avoir au moins 18 ans');
            return false;
        }

        this.showSuccess(input);
        return true;
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
        if (value.length < 8) {
            this.showError(input, 'Le mot de passe doit contenir au moins 8 caractères');
            return false;
        }
        if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
            this.showError(input, 'Doit contenir majuscule, minuscule et chiffre');
            return false;
        }
        this.showSuccess(input);
        return true;
    }

    validatePasswordConfirmation(input, value) {
        const password = document.getElementById('registration_form_password').value;
        if (!value) {
            this.showError(input, 'Veuillez confirmer votre mot de passe');
            return false;
        }
        if (value !== password) {
            this.showError(input, 'Les mots de passe ne correspondent pas');
            return false;
        }
        this.showSuccess(input);
        return true;
    }

    validateSpecialite(input, value) {
        if (!value) {
            this.showError(input, 'La spécialité est obligatoire');
            return false;
        }
        this.showSuccess(input);
        return true;
    }

    showError(input, message) {
        this.clearError(input);
        input.parentElement.classList.add('error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        
        input.parentElement.appendChild(errorDiv);
    }

    showSuccess(input) {
        this.clearError(input);
        input.parentElement.classList.remove('error');
        input.parentElement.classList.add('success');
    }

    clearError(input) {
        input.parentElement.classList.remove('error', 'success');
        const errorDiv = input.parentElement.querySelector('.error-message');
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

        // Validation spéciale pour les médecins
        const medecinRadio = document.getElementById('registration_form_role_1');
        if (medecinRadio && medecinRadio.checked) {
            const diplomeInput = document.getElementById('registration_form_diplome');
            if (diplomeInput && !diplomeInput.files[0]) {
                this.showError(diplomeInput, 'Le diplôme est obligatoire pour les médecins');
                isValid = false;
            }
            
            // Note: Le champ spécialité n'est pas encore dans le FormType Symfony
            const specialiteInput = document.getElementById('specialite');
            if (specialiteInput && !specialiteInput.value) {
                this.showError(specialiteInput, 'La spécialité est obligatoire pour les médecins');
                isValid = false;
            }
        }

        if (!isValid) {
            this.showNotification('Veuillez corriger les erreurs dans le formulaire', 'error');
            return;
        }

        // Si tout est valide, on soumet le formulaire au serveur
        this.setLoading(true);
        this.form.submit();
    }

    setLoading(loading) {
        const button = this.form.querySelector('.register-btn');
        if (loading) {
            button.textContent = 'Création du compte...';
            button.disabled = true;
            this.form.classList.add('loading');
        } else {
            button.textContent = 'Créer mon compte';
            button.disabled = false;
            this.form.classList.remove('loading');
        }
    }

    showNotification(message, type = 'info') {
        alert(message);
    }
}

// Fonctions globales
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.parentElement.querySelector('.toggle-password');
    
    if (input.type === 'password') {
        input.type = 'text';
        button.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
        `;
    } else {
        input.type = 'password';
        button.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        `;
    }
}

function toggleDiplomeField() {
    // IDs générés par Symfony : registration_form_role_0 (Patient), registration_form_role_1 (Médecin)
    const medecinRadio = document.getElementById('registration_form_role_1');
    const diplomeSection = document.getElementById('diplome-section');
    const diplomeInput = document.getElementById('registration_form_diplome');
    
    // Le champ spécialité n'est pas encore géré par Symfony dans ce snippet, on le garde conditionnel
    const specialiteSection = document.getElementById('specialite-section');
    const specialiteInput = document.getElementById('specialite');
    
    if (medecinRadio && medecinRadio.checked) {
        if(diplomeSection) diplomeSection.style.display = 'block';
        if(diplomeInput) diplomeInput.required = true;
        if(specialiteSection) specialiteSection.style.display = 'block';
        if(specialiteInput) specialiteInput.required = true;
    } else {
        if(diplomeSection) diplomeSection.style.display = 'none';
        if(diplomeInput) {
            diplomeInput.required = false;
            diplomeInput.value = '';
        }
        const fileNameDiv = document.getElementById('file-name');
        if(fileNameDiv) {
            fileNameDiv.classList.remove('active');
            fileNameDiv.innerHTML = '';
        }
        
        if(specialiteSection) specialiteSection.style.display = 'none';
        if(specialiteInput) {
            specialiteInput.required = false;
            specialiteInput.value = '';
        }
    }
}

function updateFileName(input) {
    const fileNameDiv = document.getElementById('file-name');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileName = file.name;
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        
        if (file.size > 5 * 1024 * 1024) {
            alert('Le fichier est trop volumineux. Taille maximale : 5 Mo');
            input.value = '';
            fileNameDiv.classList.remove('active');
            return;
        }
        
        fileNameDiv.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                <polyline points="13 2 13 9 20 9"/>
            </svg>
            ${fileName} (${fileSize} Mo)
        `;
        fileNameDiv.classList.add('active');
    } else {
        fileNameDiv.classList.remove('active');
        fileNameDiv.innerHTML = '';
    }
}

// Initialisation quand la page est chargée
document.addEventListener('DOMContentLoaded', () => {
    new RegisterForm();
});