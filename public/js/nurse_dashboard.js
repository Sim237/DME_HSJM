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

    // Libérer lit AJAX
    window.libererLit = function(lit_id) {
        if(confirm('Confirmer décharge du lit?')) {
            fetch(`${BASE_URL}dashboard/libererLit`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `lit_id=${lit_id}`
            }).then(() => location.reload());
        }
    };

    // Commencer hospitalisation (vitals modal → lit)
    window.commencerHospitalisation = function(admission_id) {
        // Open vitals modal
        document.getElementById('modalHospitaliser').dataset.admissionId = admission_id;
        new bootstrap.Modal(document.getElementById('modalHospitaliser')).show();
    };

    // Décharger patient
    window.dechargerPatient = function(hosp_id) {
        if(confirm('Décharger ce patient?')) {
            fetch(`${BASE_URL}hospitalisation/decharger`, {
                method: 'POST',
                body: `hosp_id=${hosp_id}`
            }).then(() => location.reload());
        }
    };
});
