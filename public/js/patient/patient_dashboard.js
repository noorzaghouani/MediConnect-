// ============================
// Patient Dashboard JavaScript
// ============================

document.addEventListener('DOMContentLoaded', function () {

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

        resultsCount.textContent = `(${count} r\u00e9sultat${count > 1 ? 's' : ''})`;

        if (count === 0) {
            resultsList.innerHTML = '';
            const noRes = document.createElement('div');
            noRes.className = 'no-results';
            noRes.innerHTML = '<i class="fas fa-search"></i>';
            const p = document.createElement('p');
            p.textContent = 'Aucun m\u00e9decin trouv\u00e9 pour cette recherche';
            noRes.appendChild(p);
            resultsList.appendChild(noRes);
            return;
        }

        resultsList.innerHTML = '';
        doctors.forEach(doc => {
            const card = document.createElement('div');
            card.className = 'doctor-card';

            const infoDiv = document.createElement('div');
            infoDiv.className = 'doctor-card-info';

            const avatarDiv = document.createElement('div');
            avatarDiv.className = 'doctor-card-avatar';
            avatarDiv.innerHTML = '<i class="fas fa-user-md"></i>';

            const detailsDiv = document.createElement('div');
            detailsDiv.className = 'doctor-card-details';

            const h4 = document.createElement('h4');
            h4.textContent = `Dr. ${doc.prenom} ${doc.nom}`;

            const pSpec = document.createElement('p');
            pSpec.innerHTML = '<i class="fas fa-stethoscope"></i> ';
            const specSpan = document.createElement('span');
            specSpan.textContent = doc.specialite;
            pSpec.appendChild(specSpan);

            detailsDiv.appendChild(h4);
            detailsDiv.appendChild(pSpec);

            if (doc.telephone) {
                const pTel = document.createElement('p');
                pTel.innerHTML = '<i class="fas fa-phone"></i> ';
                const telSpan = document.createElement('span');
                telSpan.textContent = doc.telephone;
                pTel.appendChild(telSpan);
                detailsDiv.appendChild(pTel);
            }

            infoDiv.appendChild(avatarDiv);
            infoDiv.appendChild(detailsDiv);

            const btn = document.createElement('button');
            btn.className = 'btn-rdv';
            btn.dataset.medecinId = doc.id;
            btn.innerHTML = '<i class="fas fa-calendar-plus"></i> Prendre RDV';
            btn.addEventListener('click', function () {
                const doctorName = h4.textContent;
                document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
                const prendreRdvLink = document.querySelector('.nav-menu .nav-item:nth-child(2) .nav-link');
                if (prendreRdvLink) prendreRdvLink.classList.add('active');
                showAvailabilityModal(doc.id, doctorName);
            });

            card.appendChild(infoDiv);
            card.appendChild(btn);
            resultsList.appendChild(card);
        });
    }

    // ============================
    // Availability Modal
    // ============================
    // ============================
    // Availability Modal
    // ============================
    window.showAvailabilityModal = function (medecinId, doctorName) {
        // Create modal if not exists
        let modal = document.getElementById('availabilityModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'availabilityModal';
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-container">
                    <div class="modal-header-teal">
                        <div class="modal-header-content">
                            <h2 class="modal-title-white" id="availDoctorName">Disponibilités</h2>
                        </div>
                        <button class="modal-close-white" onclick="document.getElementById('availabilityModal').remove()">&times;</button>
                    </div>
                    <div class="modal-body-white">
                        <div id="availabilityList" class="availability-grid">
                            <p class="loading-text">Chargement des disponibilités...</p>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Add Styles for grid
            if (!document.getElementById('availabilityStyles')) {
                const style = document.createElement('style');
                style.id = 'availabilityStyles';
                style.textContent = `
                    .availability-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; padding: 20px; }
                    .slot-btn { background: #f0fdf9; border: 1px solid #ccfbf1; padding: 10px; border-radius: 6px; cursor: pointer; text-align: center; transition: all 0.2s; }
                    .slot-btn:hover { background: #0d9488; color: white; border-color: #0d9488; transform: translateY(-2px); }
                    .slot-date { font-size: 0.8rem; color: #64748b; margin-bottom: 4px; }
                    .slot-btn:hover .slot-date { color: #e2e8f0; }
                    .slot-time { font-weight: 600; font-size: 1rem; }
                    .loading-text { text-align: center; width: 100%; color: #64748b; grid-column: 1 / -1; }
                    .no-slots { text-align: center; width: 100%; color: #64748b; grid-column: 1 / -1; padding: 20px; }
                `;
                document.head.appendChild(style);
            }
        }

        modal.querySelector('#availDoctorName').textContent = `Disponibilités - ${doctorName}`;
        modal.classList.add('active');

        const list = modal.querySelector('#availabilityList');
        list.innerHTML = '<p class="loading-text"><i class="fas fa-spinner fa-spin"></i> Chargement...</p>';

        // Fetch availabilities
        fetch(`/api/medecin/${medecinId}/disponibilites`)
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                }
                return res.json();
            })
            .then(data => {

                if (!Array.isArray(data) || data.length === 0) {
                    list.innerHTML = '<p class="no-slots">Aucun créneau disponible pour le moment.</p>';
                    return;
                }

                list.innerHTML = '';
                data.forEach(slot => {
                    const slotBtn = document.createElement('div');
                    slotBtn.className = 'slot-btn';
                    slotBtn.onclick = () => window.bookAppointment(slot.id);

                    const dateDiv = document.createElement('div');
                    dateDiv.className = 'slot-date';
                    dateDiv.textContent = slot.display_date;

                    const timeDiv = document.createElement('div');
                    timeDiv.className = 'slot-time';
                    timeDiv.textContent = slot.display_time;

                    slotBtn.appendChild(dateDiv);
                    slotBtn.appendChild(timeDiv);
                    list.appendChild(slotBtn);
                });
            })
            .catch(err => {
                console.error('Erreur chargement disponibilités:', err);
                list.innerHTML = '';
                const errP = document.createElement('p');
                errP.className = 'no-slots';
                errP.style.color = 'red';
                errP.textContent = `Erreur: ${err.message}`;
                list.appendChild(errP);
            });
    };

    // Make it global so onclick works
    window.bookAppointment = function (slotId) {
        if (confirm('Confirmer ce rendez-vous ?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/patient/book/${slotId}`;

            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfMeta.getAttribute('content');
                form.appendChild(csrfInput);
            }

            document.body.appendChild(form);
            form.submit();
        }
    };

    function showError(message) {
        if (resultsList) {
            resultsList.innerHTML = '';
            const div = document.createElement('div');
            div.className = 'no-results';
            div.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>';
            const p = document.createElement('p');
            p.textContent = message;
            div.appendChild(p);
            resultsList.appendChild(div);
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
            const doctorName = card.querySelector('.doctor-info h3')?.textContent.trim() || 'ce m\u00e9decin';
            const rdvId = this.getAttribute('data-id');

            if (confirm(`Voulez-vous vraiment annuler le rendez-vous avec ${doctorName} ?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/patient/cancel/${rdvId}`;

                const csrfMeta = document.querySelector('meta[name="csrf-token-cancel"]');
                if (csrfMeta) {
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = csrfMeta.getAttribute('content');
                    form.appendChild(csrfInput);
                }

                document.body.appendChild(form);
                form.submit();
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
});
