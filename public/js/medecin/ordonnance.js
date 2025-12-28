// Liste de médicaments prédéfinie
const medicamentsList = [
    "Paracétamol 500mg",
    "Paracétamol 1000mg",
    "Ibuprofène 200mg",
    "Ibuprofène 400mg",
    "Amoxicilline 500mg",
    "Amoxicilline 1g",
    "Doliprane 500mg",
    "Doliprane 1000mg",
    "Oméprazole 20mg",
    "Oméprazole 40mg",
    "Azithromycine 250mg",
    "Azithromycine 500mg",
    "Dexaméthasone 4mg",
    "Prednisone 5mg",
    "Prednisone 20mg",
    "Loratadine 10mg",
    "Cétirizine 10mg",
    "Metformine 500mg",
    "Metformine 1000mg",
    "Atorvastatine 10mg",
    "Atorvastatine 20mg",
    "Amlodipine 5mg",
    "Amlodipine 10mg",
    "Autre (saisie libre)"
];

const posologieList = [
    "1/4 comprimé",
    "1/2 comprimé",
    "1 comprimé",
    "2 comprimés",
    "3 comprimés",
    "1 gélule",
    "2 gélules",
    "1 sachet",
    "2 sachets",
    "5 ml",
    "10 ml",
    "1 cuillère à café",
    "1 cuillère à soupe"
];

const frequenceList = [
    "1 fois par jour",
    "2 fois par jour",
    "3 fois par jour",
    "4 fois par jour",
    "Le matin",
    "Le soir",
    "Matin et soir",
    "Avant les repas",
    "Pendant les repas",
    "Après les repas",
    "Au coucher",
    "Si besoin (max 3x/jour)",
    "Si besoin (max 4x/jour)"
];

let medicationCount = 0;

// Initialiser avec un médicament au chargement
document.addEventListener('DOMContentLoaded', function() {
    addMedication();
});

function addMedication() {
    medicationCount++;
    const container = document.getElementById('medications-container');
    
    const medicationHtml = `
        <div class="medication-item" id="medication-${medicationCount}">
            <div class="medication-header">
                <span class="medication-number">Médicament ${medicationCount}</span>
                <button type="button" class="btn-remove-medication" onclick="removeMedication(${medicationCount})">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>
            
            <div class="medication-fields">
                <div class="field-row">
                    <div class="form-group">
                        <label>Médicament *</label>
                        <select class="form-control" name="medicament_${medicationCount}" id="medicament_${medicationCount}" required onchange="handleMedicamentChange(${medicationCount})">
                            <option value="">-- Sélectionner --</option>
                            ${medicamentsList.map(med => `<option value="${med}">${med}</option>`).join('')}
                        </select>
                    </div>
                    
                    <div class="form-group custom-input-group" id="custom_medicament_group_${medicationCount}" style="display: none;">
                        <label>Nom du médicament</label>
                        <input type="text" class="form-control" id="custom_medicament_${medicationCount}" placeholder="Saisir le nom">
                    </div>
                </div>
                
                <div class="field-row">
                    <div class="form-group">
                        <label>Posologie *</label>
                        <select class="form-control" name="posologie_${medicationCount}" required>
                            <option value="">-- Sélectionner --</option>
                            ${posologieList.map(pos => `<option value="${pos}">${pos}</option>`).join('')}
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Fréquence *</label>
                        <select class="form-control" name="frequence_${medicationCount}" required>
                            <option value="">-- Sélectionner --</option>
                            ${frequenceList.map(freq => `<option value="${freq}">${freq}</option>`).join('')}
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Durée (jours) *</label>
                        <input type="number" class="form-control" name="duree_${medicationCount}" min="1" max="365" required placeholder="7">
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', medicationHtml);
}

function handleMedicamentChange(id) {
    const selectEl = document.getElementById(`medicament_${id}`);
    const customGroup = document.getElementById(`custom_medicament_group_${id}`);
    
    if (selectEl.value === "Autre (saisie libre)") {
        customGroup.style.display = 'block';
        document.getElementById(`custom_medicament_${id}`).required = true;
    } else {
        customGroup.style.display = 'none';
        document.getElementById(`custom_medicament_${id}`).required = false;
    }
}

function removeMedication(id) {
    const element = document.getElementById(`medication-${id}`);
    if (element) {
        element.remove();
        // Renumber remaining medications
        renumberMedications();
    }
}

function renumberMedications() {
    const items = document.querySelectorAll('.medication-item');
    items.forEach((item, index) => {
        const number = item.querySelector('.medication-number');
        if (number) {
            number.textContent = `Médicament ${index + 1}`;
        }
    });
}

// Format and submit
document.getElementById('ordonnanceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const medications = document.querySelectorAll('.medication-item');
    if (medications.length === 0) {
        alert('Veuillez ajouter au moins un médicament');
        return;
    }
    
    let formattedContent = '';
    
    medications.forEach((item, index) => {
        const id = item.id.split('-')[1];
        
        let medicament = document.querySelector(`[name="medicament_${id}"]`).value;
        if (medicament === "Autre (saisie libre)") {
            medicament = document.getElementById(`custom_medicament_${id}`).value;
        }
        
        const posologie = document.querySelector(`[name="posologie_${id}"]`).value;
        const frequence = document.querySelector(`[name="frequence_${id}"]`).value;
        const duree = document.querySelector(`[name="duree_${id}"]`).value;
        
        if (!medicament || !posologie || !frequence || !duree) {
            alert('Veuillez remplir tous les champs obligatoires');
            throw new Error('Missing fields');
        }
        
        formattedContent += `${index + 1}. ${medicament}\n`;
        formattedContent += `   - Posologie: ${posologie}\n`;
        formattedContent += `   - Fréquence: ${frequence}\n`;
        formattedContent += `   - Durée: ${duree} jours\n\n`;
    });
    
    document.getElementById('contenu').value = formattedContent.trim();
    this.submit();
});
