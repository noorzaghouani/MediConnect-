document.addEventListener('DOMContentLoaded', function () {

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
                <div class="toast-message"></div>
            </div>`;
        toast.querySelector('.toast-message').textContent = message;

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

    // ============================
    // PROFILE MODAL
    // ============================
    const profileModal = document.getElementById('editProfileModal');
    const openProfileBtn = document.getElementById('editProfileBtn');
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

    // Close modal on outside click
    window.addEventListener('click', function (event) {
        if (event.target === profileModal) {
            closeProfileModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeProfileModal();
        }
    });

    // ============================
    // FLASH MESSAGES AUTO-HIDE
    // ============================
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
