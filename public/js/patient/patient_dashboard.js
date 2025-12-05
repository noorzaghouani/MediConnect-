// ============================
// Patient Dashboard JavaScript
// ============================

document.addEventListener('DOMContentLoaded', function () {
    console.log('Patient Dashboard loaded successfully');

    // ============================
    // Modal: Edit Profile
    // ============================
    const profileModal = document.getElementById('editProfileModal');
    const openProfileBtn = document.querySelector('.btn-primary');
    const closeProfileBtn = document.getElementById('closeModalBtn');
    const cancelProfileBtn = document.getElementById('cancelModalBtn');

    function openProfileModal() {
        if (profileModal) {
            profileModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeProfileModal() {
        if (profileModal) {
            profileModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (openProfileBtn) {
        openProfileBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openProfileModal();
        });
    }

    if (closeProfileBtn) {
        closeProfileBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeProfileModal();
        });
    }

    if (cancelProfileBtn) {
        cancelProfileBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeProfileModal();
        });
    }

    window.addEventListener('click', function (event) {
        if (event.target === profileModal) {
            closeProfileModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeProfileModal();
        }
    });

    // ============================
    // Doctor Search Functionality
    // ============================
    const searchBtn = document.getElementById('btnSearchMedecin');
    const searchNom = document.getElementById('searchNom');
    const searchPrenom = document.getElementById('searchPrenom');
    const searchSpecialite = document.getElementById('searchSpecialite');
    const resultsContainer = document.getElementById('searchResultsContainer');
    const resultsList = document.getElementById('searchResultsList');
    const resultsCount = document.getElementById('resultsCount');

    if (searchBtn) {
        searchBtn.addEventListener('click', function (e) {
            e.preventDefault();
            performSearch();
        });
    }

    // Search on Enter key
    [searchNom, searchPrenom, searchSpecialite].forEach(input => {
        if (input) {
            input.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                }
            });
        }
    });

    function performSearch() {
        const nom = searchNom ? searchNom.value.trim() : '';
        const prenom = searchPrenom ? searchPrenom.value.trim() : '';
        const specialite = searchSpecialite ? searchSpecialite.value : '';

        // Show loading
        if (resultsContainer) {
            resultsContainer.style.display = 'block';
            resultsList.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Recherche en cours...</p>
                </div>
            `;
        }

        // Build query string
        const params = new URLSearchParams();
        if (nom) params.append('nom', nom);
        if (prenom) params.append('prenom', prenom);
        if (specialite) params.append('specialite', specialite);

        // Fetch results
        fetch('/patient/search-medecin?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResults(data.data, data.count);
                } else {
                    showError('Erreur lors de la recherche');
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                showError('Erreur de connexion au serveur');
            });
    }

    function displayResults(doctors, count) {
        if (!resultsList || !resultsCount) return;

        resultsCount.textContent = `(${count} résultat${count > 1 ? 's' : ''})`;

        if (count === 0) {
            resultsList.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p>Aucun médecin trouvé pour cette recherche</p>
                </div>
            `;
            return;
        }

        resultsList.innerHTML = doctors.map(doc => `
            <div class="doctor-card">
                <div class="doctor-card-info">
                    <div class="doctor-card-avatar">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="doctor-card-details">
                        <h4>Dr. ${doc.prenom} ${doc.nom}</h4>
                        <p><i class="fas fa-stethoscope"></i> ${doc.specialite}</p>
                        ${doc.telephone ? `<p><i class="fas fa-phone"></i> ${doc.telephone}</p>` : ''}
                    </div>
                </div>
                <button class="btn-rdv" data-medecin-id="${doc.id}">
                    <i class="fas fa-calendar-plus"></i>
                    Prendre RDV
                </button>
            </div>
        `).join('');

        // Add click handlers for RDV buttons
        resultsList.querySelectorAll('.btn-rdv').forEach(btn => {
            btn.addEventListener('click', function () {
                const medecinId = this.getAttribute('data-medecin-id');
                const doctorName = this.closest('.doctor-card').querySelector('h4').textContent;
                alert(`Fonctionnalité "Prendre RDV" avec ${doctorName} sera disponible prochainement.`);
            });
        });
    }

    function showError(message) {
        if (resultsList) {
            resultsList.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                    <p>${message}</p>
                </div>
            `;
        }
    }

    // ============================
    // Navigation Menu Interactions
    // ============================
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // ============================
    // Appointment Buttons
    // ============================
    const detailsBtns = document.querySelectorAll('.btn-details');
    detailsBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const card = this.closest('.appointment-card');
            const doctorName = card.querySelector('h3')?.textContent || '';
            alert(`Détails du rendez-vous avec ${doctorName}\n\nCette fonctionnalité sera disponible prochainement.`);
        });
    });

    const cancelBtns = document.querySelectorAll('.btn-cancel');
    cancelBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const card = this.closest('.appointment-card');
            const doctorName = card.querySelector('h3')?.textContent || '';

            if (confirm(`Voulez-vous vraiment annuler le rendez-vous avec ${doctorName} ?`)) {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '0';
                card.style.transform = 'translateX(-20px)';

                setTimeout(() => {
                    card.remove();
                    alert('Rendez-vous annulé avec succès');
                }, 500);
            }
        });
    });

    // ============================
    // Update current date in header
    // ============================
    const updateHeaderDate = () => {
        const dateElement = document.querySelector('.header-date');
        if (dateElement) {
            const now = new Date();
            const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
            dateElement.textContent = now.toLocaleDateString('fr-FR', options);
        }
    };

    updateHeaderDate();

    console.log('%c🏥 MediConnect Patient Dashboard', 'color: #0d9488; font-size: 16px; font-weight: bold;');
    console.log('%cDashboard chargé avec succès!', 'color: #10b981; font-size: 12px;');
});
