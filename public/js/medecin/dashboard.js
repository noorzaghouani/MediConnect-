document.addEventListener('DOMContentLoaded', function () {
    console.log('Dashboard JS initialized');

    // Profile Modal Elements
    const profileModal = document.getElementById('editProfileModal');
    const openProfileBtn = document.getElementById('editProfileBtn');
    const closeProfileBtn = document.getElementById('closeModalBtn');
    const cancelProfileBtn = document.getElementById('cancelModalBtn');

    // Availability Modal Elements
    const availabilityModal = document.getElementById('availabilityModal');
    const openAvailabilityBtn = document.getElementById('availabilityMenuBtn');
    const closeAvailabilityBtn = document.getElementById('closeAvailabilityBtn');
    const cancelAvailabilityBtn = document.getElementById('cancelAvailabilityBtn');
    const availabilityForm = document.getElementById('availabilityForm');

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

            // Validation
            if (!data.date) {
                alert('Veuillez sélectionner une date');
                return;
            }

            // Date validation (No past dates)
            const selectedDate = new Date(data.date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                alert('❌ Vous ne pouvez pas sélectionner une date passée');
                return;
            }

            const debut = new Date('2000-01-01 ' + data.heure_debut);
            const fin = new Date('2000-01-01 ' + data.heure_fin);

            if (debut >= fin) {
                alert('L\'heure de fin doit être après l\'heure de début');
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
                        alert('✅ ' + result.message);
                        availabilityForm.reset();
                        loadAvailabilities(); // Reload the list
                    } else {
                        alert('❌ ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Erreur lors de l\'enregistrement');
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
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette disponibilité ?')) {
            return;
        }

        fetch(`/medecin/availability/delete/${id}`, {
            method: 'DELETE'
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('✅ ' + result.message);
                    loadAvailabilities();
                } else {
                    alert('❌ ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Erreur lors de la suppression');
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