// Print prescription functionality using event delegation
document.addEventListener('DOMContentLoaded', function () {
    // Add click handlers to all print buttons
    document.querySelectorAll('.btn-print').forEach(button => {
        button.addEventListener('click', function () {
            const contenu = this.dataset.contenu;
            const date = this.dataset.date;
            const medecin = this.dataset.medecin;
            const patient = this.dataset.patient;

            printOrdonnance(contenu, date, medecin, patient);
        });
    });
});

// Print prescription function
function printOrdonnance(contenu, date, medecin, patient) {
    // Fill in the data
    document.getElementById('printDate').textContent = date;
    document.getElementById('printMedecin').textContent = medecin;
    document.getElementById('printPatient').textContent = patient;
    document.getElementById('printContenu').textContent = contenu;

    // Make visible for printing
    const printArea = document.getElementById('printArea');
    printArea.style.display = 'block';

    // Print
    window.print();

    // Hide after printing
    setTimeout(function () {
        printArea.style.display = 'none';
    }, 100);
}
