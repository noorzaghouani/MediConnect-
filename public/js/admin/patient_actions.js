/**
 * Patient Actions - Gestion des actions sur les patients (Voir, Supprimer)
 */

// ========== VIEW MODAL ==========

/**
 * Ouvre la modale de détails d'un patient
 */
function openPatientModal(name, email, phone) {
    document.getElementById('modalPatientName').textContent = name;
    document.getElementById('modalPatientEmail').textContent = email;
    document.getElementById('modalPatientPhone').textContent = phone;

    document.getElementById('patientModal').style.display = 'flex';
}

/**
 * Ferme la modale de détails
 */
function closePatientModal() {
    document.getElementById('patientModal').style.display = 'none';
}

// ========== DELETE MODAL ==========

/**
 * Ouvre la modale de confirmation de suppression
 * @param {number} patientId - ID du patient à supprimer
 * @param {string} patientName - Nom du patient
 */
function confirmDeletePatient(patientId, patientName) {
    // Mettre à jour le nom dans la modale
    document.getElementById('deletePatientName').textContent = patientName;

    // Générer l'URL de suppression
    const deleteUrl = `/admin/patient/${patientId}/delete`;
    document.getElementById('deletePatientForm').action = deleteUrl;

    // Afficher la modale
    document.getElementById('deletePatientModal').style.display = 'flex';
}

/**
 * Ferme la modale de suppression
 */
function closeDeletePatientModal() {
    document.getElementById('deletePatientModal').style.display = 'none';
}

// ========== EVENT LISTENERS ==========

// Fermer les modales en cliquant à l'extérieur
window.onclick = function (event) {
    const patientModal = document.getElementById('patientModal');
    const deleteModal = document.getElementById('deletePatientModal');
    const adminModal = document.getElementById('adminProfileModal');

    if (event.target === patientModal) {
        closePatientModal();
    }
    if (event.target === deleteModal) {
        closeDeletePatientModal();
    }
    if (event.target === adminModal) {
        adminModal.style.display = 'none';
    }
};

// Gestion de la touche Échap pour fermer les modales
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' || event.key === 'Esc') {
        closePatientModal();
        closeDeletePatientModal();
    }
});
