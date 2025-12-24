document.addEventListener('DOMContentLoaded', function () {
    console.log('Dashboard JS initialized');

    // ============================
    // TOAST NOTIFICATION SYSTEM
    // ============================
    function showToast(message, type = 'info') {
        // Create container if doesn't exist
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        // Define icons and titles for each type
        const config = {
            success: { icon: 'fa-circle-check', title: 'Succès' },
            error: { icon: 'fa-circle-xmark', title: 'Erreur' },
            info: { icon: 'fa-circle-info', title: 'Information' },
            warning: { icon: 'fa-triangle-exclamation', title: 'Attention' }
        };

        const { icon, title } = config[type] || config.info;

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas ${icon}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
        `;

        container.appendChild(toast);

        // Auto remove after animation
        setTimeout(() => {
            toast.remove();
            // Remove container if empty
            if (container.children.length === 0) {
                container.remove();
            }
        }, 3000);
    }

    // Make showToast global
    window.showToast = showToast;

    // Profile Modal Elements
    const profileModal = document.getElementById('editProfileModal');
    const openProfileBtn = document.getElementById('editProfileBtn');
    const closeProfileBtn = document.getElementById('closeModalBtn');
    const cancelProfileBtn = document.getElementById('cancelModalBtn');

    // Availability Modal Elements
    const availabilityModal = document.getElementById('availabilityModal');
    const openAvailabilityBtn = document.getElementById('nav-availability'); // ID updated
    const closeAvailabilityBtn = document.getElementById('closeAvailabilityBtn');
    const cancelAvailabilityBtn = document.getElementById('cancelAvailabilityBtn');
    const availabilityForm = document.getElementById('availabilityForm');

    // Navigation Elements
    const navItems = document.querySelectorAll('.nav-link');
    const navAppointments = document.getElementById('nav-appointments');
    const navPatients = document.getElementById('nav-patients');
    const navPrescriptions = document.getElementById('nav-prescriptions');

    // Helper: Set Active Nav
    function setActiveNav(id) {
        navItems.forEach(item => item.classList.remove('active'));
        const activeItem = document.getElementById(id);
        if (activeItem) activeItem.classList.add('active');
    }

    // Profile Modal Functions
    function openProfileModal() {
        console.log('Opening profile modal...');
        if (profileModal) {
            profileModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeProfileModal() {
        console.log('Closing profile modal...');
        if (profileModal) {
            profileModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Availability Modal Functions
    function openAvailabilityModal() {
        console.log('Opening availability modal...');
        if (availabilityModal) {
            availabilityModal.classList.add('active');
            document.body.style.overflow = 'hidden';
            setActiveNav('nav-availability'); // Highlight menu
            loadAvailabilities(); // Load existing availabilities

            // Set min date to today
            const dateInput = document.getElementById('availability_date');
            if (dateInput) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.setAttribute('min', today);
            }
        }
    }

    function closeAvailabilityModal() {
        console.log('Closing availability modal...');
        if (availabilityModal) {
            availabilityModal.classList.remove('active');
            document.body.style.overflow = '';
            setActiveNav('nav-appointments'); // Revert to Appointments
        }
    }

    // Event Listeners for Profile Modal
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

    // Event Listeners for Availability Modal
    if (openAvailabilityBtn) {
        openAvailabilityBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openAvailabilityModal();
        });
    }

    if (closeAvailabilityBtn) {
        closeAvailabilityBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeAvailabilityModal();
        });
    }

    if (cancelAvailabilityBtn) {
        cancelAvailabilityBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeAvailabilityModal();
        });
    }

    // Nav Item Listeners
    if (navAppointments) {
        navAppointments.addEventListener('click', function (e) {
            e.preventDefault();
            closeAvailabilityModal(); // Ensure modals are closed
            setActiveNav('nav-appointments');
        });
    }

    if (navPatients) {
        navPatients.addEventListener('click', function (e) {
            // e.preventDefault() handled by onclick in HTML but strict separation preferred
            setActiveNav('nav-patients');
        });
    }

    if (navPrescriptions) {
        navPrescriptions.addEventListener('click', function (e) {
            setActiveNav('nav-prescriptions');
        });
    }

    // Availability Form Submission
    if (availabilityForm) {
        availabilityForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(availabilityForm);
            const data = {
                date: formData.get('date'),
                heure_debut: formData.get('heure_debut'),
                heure_fin: formData.get('heure_fin')
            };

            // Si tous les champs sont vides, fermer la modal sans erreur
            if (!data.date && !data.heure_debut && !data.heure_fin) {
                closeAvailabilityModal();
                return;
            }

            // Si au moins un champ est rempli, valider que TOUS sont remplis
            if (!data.date || !data.heure_debut || !data.heure_fin) {
                showToast('Veuillez remplir tous les champs pour ajouter une disponibilité', 'warning');
                return;
            }

            // Date validation (No past dates)
            const selectedDate = new Date(data.date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                showToast('Vous ne pouvez pas sélectionner une date passée', 'error');
                return;
            }

            const debut = new Date('2000-01-01 ' + data.heure_debut);
            const fin = new Date('2000-01-01 ' + data.heure_fin);

            if (debut >= fin) {
                showToast('L\'heure de fin doit être après l\'heure de début', 'error');
                return;
            }

            // Send AJAX request
            fetch('/medecin/availability/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showToast(result.message, 'success');
                        availabilityForm.reset();
                        loadAvailabilities(); // Reload the list
                        closeAvailabilityModal(); // Fermer la modal
                    } else {
                        showToast(result.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Erreur lors de l\'enregistrement', 'error');
                });
        });
    }

    // Load Availabilities
    function loadAvailabilities() {
        fetch('/medecin/availability/list')
            .then(response => response.json())
            .then(result => {
                const container = document.getElementById('availabilityItems');
                if (!container) return;

                if (result.data.length === 0) {
                    container.innerHTML = '<p style="color: #6c757d; font-size: 14px;">Aucune disponibilité enregistrée.</p>';
                    return;
                }

                container.innerHTML = result.data.map(item => `
                    <div class="availability-item">
                        <div class="availability-info">
                            <strong>${formatDate(item.date)}</strong>
                            <span>${item.heure_debut} - ${item.heure_fin}</span>
                        </div>
                        <button class="btn-delete-availability" data-id="${item.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `).join('');

                // Add delete event listeners
                container.querySelectorAll('.btn-delete-availability').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const id = this.getAttribute('data-id');
                        deleteAvailability(id);
                    });
                });
            })
            .catch(error => {
                console.error('Error loading availabilities:', error);
            });
    }

    // Delete Availability
    function deleteAvailability(id) {
        // Suppression directe sans confirmation
        fetch(`/medecin/availability/delete/${id}`, {
            method: 'DELETE'
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Suppression silencieuse, juste recharger la liste
                    loadAvailabilities();
                } else {
                    showToast(result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Erreur lors de la suppression', 'error');
            });
    }

    // Helper function to format date
    function formatDate(dateStr) {
        const date = new Date(dateStr);
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('fr-FR', options);
    }

    // Close modals on outside click
    window.addEventListener('click', function (event) {
        if (event.target === profileModal) {
            closeProfileModal();
        }
        if (event.target === availabilityModal) {
            closeAvailabilityModal();
        }
    });

    // Close modals on Escape key
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeProfileModal();
            closeAvailabilityModal();
        }
    });

    // Flash messages auto-hide
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(() => {
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    }
});