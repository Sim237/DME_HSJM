// Nurse Dashboard JS - Dynamic/Interactive
document.addEventListener('DOMContentLoaded', function() {
    // Flash à hospitaliser
    const flashingCards = document.querySelectorAll('.animate__pulse');
    flashingCards.forEach(card => {
        card.style.animationDuration = '1.5s';
        card.addEventListener('click', function() {
            this.classList.remove('animate__pulse');
            this.classList.add('animate__tada');
        });
    });

    // Libérer lit AJAX (avec protection double-clic)
    window.libererLit = function(lit_id, btnEl) {
        if (!confirm('Confirmer la libération du lit ?')) return;
        if (btnEl) { btnEl.disabled = true; btnEl.textContent = 'En cours…'; }

        fetch(`${BASE_URL}dashboard/libererLit`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `lit_id=${encodeURIComponent(lit_id)}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success === false) {
                alert(data.message || 'Erreur lors de la libération du lit.');
                if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Libérer'; }
            } else {
                location.reload();
            }
        })
        .catch(err => {
            console.error('[libererLit]', err);
            alert('Erreur réseau. Veuillez réessayer.');
            if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Libérer'; }
        });
    };

    // Commencer hospitalisation
    window.commencerHospitalisation = function(admission_id) {
        document.getElementById('modalHospitaliser').dataset.admissionId = admission_id;
        new bootstrap.Modal(document.getElementById('modalHospitaliser')).show();
    };

    // Décharger patient (avec protection double-clic)
    window.dechargerPatient = function(hosp_id, btnEl) {
        if (!confirm('Décharger ce patient ? Cette action est irréversible.')) return;
        if (btnEl) { btnEl.disabled = true; btnEl.textContent = 'En cours…'; }

        fetch(`${BASE_URL}hospitalisation/decharger`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `hosp_id=${encodeURIComponent(hosp_id)}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success === false) {
                alert(data.message || 'Erreur lors de la décharge.');
                if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Décharger'; }
            } else {
                location.reload();
            }
        })
        .catch(err => {
            console.error('[dechargerPatient]', err);
            alert('Erreur réseau. Veuillez réessayer.');
            if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Décharger'; }
        });
    };
});
