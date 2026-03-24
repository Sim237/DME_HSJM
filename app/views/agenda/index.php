<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- Google Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    :root {
        --med-primary: #1a4a8e;
        --med-bg: #f8fafc;
        --fc-border-color: #f1f5f9;
        --fc-today-bg-color: #fefce8;
    }

    /* 1. SUPPRESSION SIDEBAR & MISE EN PAGE CENTRÉE */
    .sidebar { display: none !important; }
    main, .col-md-10, .ms-sm-auto {
        margin-left: 0 !important;
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        background-color: var(--med-bg);
    }

    .agenda-focus-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    /* 2. HEADER ÉPURÉ */
    .agenda-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .header-title h2 {
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -1px;
    }

    /* 3. LÉGENDE SOFT */
    .calendar-legend {
        display: flex;
        gap: 25px;
        margin-bottom: 25px;
        padding: 15px 25px;
        background: white;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    }

    .legend-item { display: flex; align-items: center; gap: 10px; font-size: 0.85rem; font-weight: 700; color: #64748b; }
    .dot { width: 10px; height: 10px; border-radius: 3px; }

    /* 4. CARTE DU CALENDRIER */
    .calendar-card {
        background: white;
        border-radius: 24px;
        padding: 30px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
        border: 1px solid #f1f5f9;
    }

    /* Personnalisation FullCalendar 6 */
    .fc .fc-toolbar-title { font-weight: 800; color: #1e293b; font-size: 1.6rem !important; }
    .fc .fc-button-primary {
        background-color: white; border: 1px solid #e2e8f0; color: #64748b;
        font-weight: 700; text-transform: capitalize; padding: 8px 16px; border-radius: 10px;
    }
    .fc .fc-button-primary:hover { background-color: #f8fafc; color: var(--med-primary); border-color: var(--med-primary); }
    .fc .fc-button-active { background-color: var(--med-primary) !important; color: white !important; border-color: var(--med-primary) !important; }

    .fc th { background: #f8fafc; padding: 12px 0 !important; color: #64748b; font-size: 0.75rem; text-transform: uppercase; border: none !important; }
    .fc td { border-color: var(--fc-border-color) !important; }
    .fc-event { border-radius: 6px !important; padding: 4px 8px !important; border: none !important; font-weight: 600; font-size: 0.8rem; margin: 2px 4px !important; }

    /* 5. BOUTON ACTIONS */
    .btn-new-rdv {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: white; padding: 12px 25px; border-radius: 14px; font-weight: 700;
        border: none; box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.2);
        transition: all 0.3s ease;
    }
    .btn-new-rdv:hover { transform: translateY(-2px); box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.3); color: white; }
</style>

<main>
    <div class="agenda-focus-container">

        <!-- HEADER -->
        <div class="agenda-header">
            <div class="header-title">
                <div class="d-flex align-items-center gap-3 mb-1">
                    <a href="<?= BASE_URL ?>dashboard" class="btn btn-light rounded-circle shadow-sm p-2" title="Retour au Dashboard">
                        <i class="bi bi-arrow-left text-primary fs-5"></i>
                    </a>
                    <h2 class="mb-0">Agenda Médical</h2>
                </div>
                <p class="text-muted mb-0 ms-5">Vue panoramique de vos consultations et interventions</p>
            </div>

            <button class="btn-new-rdv" onclick="openAddModal()">
                <i class="bi bi-plus-lg me-2"></i> NOUVEAU RENDEZ-VOUS
            </button>
        </div>

        <!-- LÉGENDE -->
        <div class="calendar-legend">
            <div class="legend-item"><span class="dot" style="background: #3b82f6;"></span> Consultation</div>
            <div class="legend-item"><span class="dot" style="background: #10b981;"></span> Suivi</div>
            <div class="legend-item"><span class="dot" style="background: #ef4444;"></span> Urgence</div>
            <div class="legend-item"><span class="dot" style="background: #8b5cf6;"></span> Intervention</div>
        </div>

        <!-- CALENDRIER -->
        <div class="calendar-card">
            <div id="calendar"></div>
        </div>
    </div>
</main>

<!-- MODALE DE SAISIE MODERNE -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold text-dark">Planifier un RDV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="eventForm">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Titre de la visite</label>
                        <input type="text" name="titre" class="form-control form-control-lg bg-light border-0" placeholder="Nom du patient ou type d'acte" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Heure Début</label>
                            <input type="datetime-local" name="date_debut" id="date_debut" class="form-control bg-light border-0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Heure Fin</label>
                            <input type="datetime-local" name="date_fin" id="date_fin" class="form-control bg-light border-0" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold text-muted text-uppercase">Type de Rendez-vous</label>
                        <select name="type_rdv" class="form-select bg-light border-0">
                            <option value="consultation">Consultation Standard</option>
                            <option value="suivi">Suivi Médical</option>
                            <option value="urgence">Urgence / Prioritaire</option>
                            <option value="intervention">Intervention Chirurgicale</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 shadow fw-bold">ENREGISTRER</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        firstDay: 1, // Semaine commence lundi
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        buttonText: {
            today: 'Aujourd\'hui',
            month: 'Mois',
            week: 'Semaine',
            list: 'Liste'
        },
        events: '<?= BASE_URL ?>agenda/events',
        editable: true,
        selectable: true,

        select: function(info) {
            document.getElementById('eventForm').reset();
            document.getElementById('date_debut').value = info.startStr.slice(0, 16);
            document.getElementById('date_fin').value = info.endStr.slice(0, 16);
            new bootstrap.Modal(document.getElementById('eventModal')).show();
        },

        eventClick: function(info) {
            Swal.fire({
                title: info.event.title,
                text: 'Type: ' + info.event.extendedProps.type,
                icon: 'info',
                confirmButtonText: 'Fermer'
            });
        }
    });
    calendar.render();
});

function openAddModal() {
    new bootstrap.Modal(document.getElementById('eventModal')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>