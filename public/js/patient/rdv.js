/**
 * RDV Booking - JavaScript pour la réservation de rendez-vous
 */

/**
 * Fonction pour réserver un créneau
 * @param {number} disponibiliteId - ID de la disponibilité
 * @param {string} medecinNom - Nom du médecin
 * @param {string} dateHeure - Date et heure du RDV
 */
function reserverCreneau(disponibiliteId, medecinNom, dateHeure) {
    // Confirmer la réservation
    if (confirm(`Voulez-vous réserver ce rendez-vous avec ${medecinNom} le ${dateHeure} ?`)) {
        fetch(`/patient/rdv/reserver/${disponibiliteId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Rendez-vous confirmé !');
                    window.location.reload();
                } else {
                    alert('Erreur lors de la réservation: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la réservation.');
            });
    }
}

// Définir la date minimale (aujourd'hui) pour le champ date
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('date');
    if (dateInput) {
        // Définir la date minimale à aujourd'hui
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }
});
