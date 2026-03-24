// ===== RECHERCHE DE PATIENTS =====
let searchTimeout;
const searchPatient = document.getElementById('searchPatient');

if (searchPatient) {
    searchPatient.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const term = e.target.value.trim();
        
        if (term.length < 2) {
            hideSearchResults();
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performSearch(term);
        }, 300);
    });
}

function performSearch(term) {
    showLoader();
    
    fetch(`${BASE_URL}consultation/search-patients?term=${encodeURIComponent(term)}`)
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data);
            hideLoader();
        })
        .catch(error => {
            console.error('Erreur de recherche:', error);
            showNotification('Erreur lors de la recherche', 'error');
            hideLoader();
        });
}

function displaySearchResults(patients) {
    const resultsDiv = document.getElementById('searchResults');
    
    if (!patients || patients.length === 0) {
        resultsDiv.innerHTML = '<div class="p-3 text-muted">Aucun patient trouvé</div>';
        resultsDiv.classList.add('show');
        return;
    }
    
    let html = '';
    patients.forEach(patient => {
        const age = calculateAge(patient.date_naissance);
        html += `
            <div class="search-result-item" onclick="selectPatient(${patient.id})">
                <span class="patient-name">
                    ${patient.prenom} ${patient.nom}
                </span>
                <div class="patient-details">
                    <span class="badge bg-secondary">${patient.numero_dossier}</span>
                    <span class="ms-2">${patient.sexe === 'M' ? 'Homme' : 'Femme'}</span>
                    <span class="ms-2">${age} ans</span>
                    ${patient.telephone ? `<span class="ms-2"><i class="fas fa-phone"></i> ${patient.telephone}</span>` : ''}
                </div>
            </div>
        `;
    });
    
    resultsDiv.innerHTML = html;
    resultsDiv.classList.add('show');
}

function hideSearchResults() {
    const resultsDiv = document.getElementById('searchResults');
    if (resultsDiv) {
        resultsDiv.classList.remove('show');
        resultsDiv.innerHTML = '';
    }
}

function selectPatient(patientId) {
    hideSearchResults();
    window.location.href = `${BASE_URL}consultation/dossier-patient/${patientId}`;
}

function calculateAge(birthDate) {
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    
    return age;
}

// Fermer les résultats si on clique en dehors
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-box')) {
        hideSearchResults();
    }
});

// ===== GESTION DES EXAMENS PARACLINIQUES =====
const formDemandeExamen = document.getElementById('formDemandeExamen');

if (formDemandeExamen) {
    formDemandeExamen.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('consultation_id', document.querySelector('[name="consultation_id"]')?.value);
        
        showLoader();
        
        fetch(`${BASE_URL}consultation/demander-examen`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                showNotification('Examen demandé avec succès', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalDemandeExamen')).hide();
                clearForm('formDemandeExamen');
                refreshExamensList();
            } else {
                showNotification(data.message || 'Erreur lors de la demande', 'error');
            }
        })
        .catch(error => {
            hideLoader();
            showNotification('Erreur lors de la demande d\'examen', 'error');
        });
    });
}

function supprimerExamen(examenId) {
    confirmAction('Êtes-vous sûr de vouloir supprimer cette demande d\'examen ?', () => {
        showLoader();
        
        fetch(`${BASE_URL}consultation/supprimer-examen/${examenId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                showNotification('Examen supprimé', 'success');
                refreshExamensList();
            } else {
                showNotification('Erreur lors de la suppression', 'error');
            }
        });
    });
}

function refreshExamensList() {
    const consultationId = document.querySelector('[name="consultation_id"]')?.value;
    if (!consultationId) return;
    
    fetch(`${BASE_URL}consultation/liste-examens/${consultationId}`)
        .then(response => response.json())
        .then(data => {
            // Mettre à jour la liste des examens
            const listExamens = document.getElementById('listExamens');
            if (listExamens && data.html) {
                listExamens.innerHTML = data.html;
            }
        });
}

// ===== GESTION DES MÉDICAMENTS (ORDONNANCE) =====
let selectedMedicamentId = null;

// Recherche de médicaments
const searchMedicament = document.getElementById('searchMedicament');

if (searchMedicament) {
    let medicamentTimeout;
    
    searchMedicament.addEventListener('input', function(e) {
        clearTimeout(medicamentTimeout);
        const term = e.target.value.trim();
        
        if (term.length < 2) {
            document.getElementById('medicamentResults').innerHTML = '';
            return;
        }
        
        medicamentTimeout = setTimeout(() => {
            searchMedicaments(term);
        }, 300);
    });
}

function searchMedicaments(term) {
    fetch(`${BASE_URL}pharmacie/search-medicaments?term=${encodeURIComponent(term)}`)
        .then(response => response.json())
        .then(data => {
            displayMedicamentResults(data);
        })
        .catch(error => {
            console.error('Erreur recherche médicament:', error);
        });
}

function displayMedicamentResults(medicaments) {
    const resultsDiv = document.getElementById('medicamentResults');
    
    if (!medicaments || medicaments.length === 0) {
        resultsDiv.innerHTML = '<div class="p-2 text-muted small">Aucun médicament trouvé</div>';
        return;
    }
    
    let html = '';
    medicaments.forEach(med => {
        const stockClass = med.quantite_disponible > 10 ? 'success' : 
                          med.quantite_disponible > 0 ? 'warning' : 'danger';
        html += `
            <div class="search-result-item" onclick="selectMedicament(${med.id}, '${med.nom_commercial}', '${med.dci}', '${med.dosage}', ${med.quantite_disponible})">
                <strong>${med.nom_commercial}</strong>
                <br>
                <small class="text-muted">${med.dci} - ${med.dosage}</small>
                <span class="badge bg-${stockClass} float-end">Stock: ${med.quantite_disponible}</span>
            </div>
        `;
    });
    
    resultsDiv.innerHTML = html;
}

function selectMedicament(id, nom, dci, dosage, stock) {
    selectedMedicamentId = id;
    document.getElementById('medicament_id').value = id;
    document.getElementById('searchMedicament').value = nom;
    document.getElementById('medicamentResults').innerHTML = '';
    
    const stockClass = stock > 10 ? 'success' : stock > 0 ? 'warning' : 'danger';
    const stockText = stock > 0 ? `Stock disponible: ${stock}` : 'Rupture de stock!';
    
    document.getElementById('medicamentInfo').innerHTML = `
        <strong>${nom}</strong><br>
        <small>${dci} - ${dosage}</small><br>
        <span class="badge bg-${stockClass}">${stockText}</span>
    `;
    document.getElementById('medicamentInfo').classList.remove('d-none');
}

// Ajouter un médicament à l'ordonnance
const formAjouterMedicament = document.getElementById('formAjouterMedicament');

if (formAjouterMedicament) {
    formAjouterMedicament.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!selectedMedicamentId) {
            showNotification('Veuillez sélectionner un médicament', 'warning');
            return;
        }
        
        const formData = new FormData(this);
        formData.append('consultation_id', document.querySelector('[name="consultation_id"]')?.value);
        
        showLoader();
        
        fetch(`${BASE_URL}consultation/ajouter-medicament`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                showNotification('Médicament ajouté à l\'ordonnance', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalAjouterMedicament')).hide();
                clearForm('formAjouterMedicament');
                selectedMedicamentId = null;
                document.getElementById('medicamentInfo').classList.add('d-none');
                refreshPrescriptionList();
            } else {
                showNotification(data.message || 'Erreur lors de l\'ajout', 'error');
            }
        });
    });
}

function supprimerMedicament(ligneId) {
    confirmAction('Retirer ce médicament de l\'ordonnance ?', () => {
        showLoader();
        
        fetch(`${BASE_URL}consultation/supprimer-medicament/${ligneId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                showNotification('Médicament retiré', 'success');
                refreshPrescriptionList();
            }
        });
    });
}

function refreshPrescriptionList() {
    const consultationId = document.querySelector('[name="consultation_id"]')?.value;
    if (!consultationId) return;
    
    fetch(`${BASE_URL}consultation/liste-prescription/${consultationId}`)
        .then(response => response.json())
        .then(data => {
            const listPrescription = document.getElementById('prescriptionList');
            if (listPrescription && data.html) {
                listPrescription.innerHTML = data.html;
            }
        });
}

// ===== GESTION DES SOINS =====
const formAppelSoin = document.getElementById('formAppelSoin');

if (formAppelSoin) {
    formAppelSoin.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('consultation_id', document.querySelector('[name="consultation_id"]')?.value);
        
        showLoader();
        
        fetch(`${BASE_URL}consultation/appeler-soin`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                showNotification('Soin planifié avec succès', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalAppelSoin')).hide();
                clearForm('formAppelSoin');
                refreshSoinsList();
            } else {
                showNotification(data.message || 'Erreur', 'error');
            }
        });
    });
}

function supprimerSoin(soinId) {
    confirmAction('Annuler ce soin planifié ?', () => {
        fetch(`${BASE_URL}consultation/supprimer-soin/${soinId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Soin annulé', 'success');
                refreshSoinsList();
            }
        });
    });
}

function refreshSoinsList() {
    const consultationId = document.querySelector('[name="consultation_id"]')?.value;
    if (!consultationId) return;
    
    fetch(`${BASE_URL}consultation/liste-soins/${consultationId}`)
        .then(response => response.json())
        .then(data => {
            const listeSoins = document.getElementById('listeSoins');
            if (listeSoins && data.html) {
                listeSoins.innerHTML = data.html;
            }
        });
}

// ===== SAUVEGARDE BROUILLON =====
function saveDraft() {
    const forms = document.querySelectorAll('form');
    if (forms.length === 0) return;
    
    const formData = new FormData(forms[0]);
    
    fetch(`${BASE_URL}consultation/sauvegarder-brouillon`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Brouillon sauvegardé', 'success', 2000);
        }
    })
    .catch(error => {
        console.error('Erreur sauvegarde brouillon:', error);
    });
}

// Auto-save toutes les 2 minutes
setInterval(saveDraft, 120000);
