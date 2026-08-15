/**
 * Medecin Actions - Gestion des actions sur les médecins (Voir, Supprimer)
 */

// ========== VIEW MODAL ==========

/**
 * Ouvre la modale de détails d'un médecin
 */
function openModal(name, specialty, email, phone) {
    document.getElementById('modalName').textContent = name;
    document.getElementById('modalSpecialty').textContent = specialty;
    document.getElementById('modalEmail').textContent = email;
    document.getElementById('modalPhone').textContent = phone;
    
    document.getElementById('doctorModal').style.display = 'flex';
}

/**
 * Ferme la modale de détails
 */
function closeModal() {
    document.getElementById('doctorModal').style.display = 'none';
}

// ========== DELETE MODAL ==========

/**
 * Ouvre la modale de confirmation de suppression
 * @param {number} medecinId - ID du médecin à supprimer
 * @param {string} medecinName - Nom du médecin
 * @param {HTMLElement} btn - Bouton déclencheur (contient data-csrf)
 */
function confirmDelete(medecinId, medecinName, btn) {
    // Mettre à jour le nom dans la modale
    document.getElementById('deleteMedecinName').textContent = medecinName;
    
    // Générer l'URL de suppression
    const deleteUrl = `/admin/medecin/${medecinId}/delete`;
    document.getElementById('deleteForm').action = deleteUrl;
    
    if (btn && btn.dataset.csrf) {
        document.getElementById('deleteCsrfToken').value = btn.dataset.csrf;
    }
    
    // Afficher la modale
    document.getElementById('deleteModal').style.display = 'flex';
}

/**
 * Ferme la modale de suppression
 */
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// ========== EVENT LISTENERS ==========

// Fermer les modales en cliquant à l'extérieur
window.onclick = function(event) {
    const doctorModal = document.getElementById('doctorModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target === doctorModal) {
        closeModal();
    }
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
};

// Gestion de la touche Échap pour fermer les modales
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' || event.key === 'Esc') {
        closeModal();
        closeDeleteModal();
    }
});
