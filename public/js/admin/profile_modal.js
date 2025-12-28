/**
 * Admin Profile Modal Logic
 * Handles opening/closing of the profile edition modal.
 */

document.addEventListener('DOMContentLoaded', function () {
    const adminModal = document.getElementById('adminProfileModal');
    const openBtn = document.querySelector('.btn-edit-profile');

    if (openBtn) {
        openBtn.addEventListener('click', function (e) {
            e.preventDefault(); // Prevent default link behavior
            adminModal.style.display = 'flex';
        });
    }

    // Close on outside click
    window.onclick = function (event) {
        const adminModal = document.getElementById('adminProfileModal');
        const doctorModal = document.getElementById('doctorModal');

        // Handle Admin Modal Outside Click
        if (event.target == adminModal) {
            adminModal.style.display = 'none';
        }

        // Handle Doctor Modal Outside Click (if present on the page)
        if (doctorModal && event.target == doctorModal) {
            doctorModal.style.display = 'none';
        }
    };
});

function closeAdminProfile() {
    document.getElementById('adminProfileModal').style.display = 'none';
}
