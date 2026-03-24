<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-calendar3"></i> Agenda Médical</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#eventModal">
                    <i class="bi bi-plus"></i> Nouveau RDV
                </button>
            </div>

            <div id="calendar"></div>
        </main>
    </div>
</div>

<!-- Modal Événement -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Planifier un RDV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="eventForm">
                <div class="modal-body">
                    <input type="hidden" id="eventId" name="id">
                    
                    <div class="mb-3">
                        <label class="form-label">Type de RDV</label>
                        <select class="form-select" name="type_rdv" required>
                            <option value="consultation">Consultation</option>
                            <option value="intervention">Intervention</option>
                            <option value="suivi">Suivi</option>
                            <option value="urgence">Urgence</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Titre</label>
                        <input type="text" class="form-control" name="titre" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Date/Heure début</label>
                            <input type="datetime-local" class="form-control" name="date_debut" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date/Heure fin</label>
                            <input type="datetime-local" class="form-control" name="date_fin" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-3">
                        <label class="form-label">Salle</label>
                        <input type="text" class="form-control" name="salle">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/fr.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
    const eventForm = document.getElementById('eventForm');
    
    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'fr',
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: `${BASE_URL}agenda/events`,
        selectable: true,
        editable: true,
        
        select: function(info) {
            eventForm.reset();
            document.getElementById('eventId').value = '';
            document.querySelector('[name="date_debut"]').value = info.startStr.slice(0, 16);
            document.querySelector('[name="date_fin"]').value = info.endStr.slice(0, 16);
            eventModal.show();
        },
        
        eventClick: function(info) {
            const event = info.event;
            document.getElementById('eventId').value = event.id;
            document.querySelector('[name="titre"]').value = event.title;
            eventModal.show();
        }
    });
    
    calendar.render();
    
    eventForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(eventForm);
        
        fetch(`${BASE_URL}agenda/save`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                calendar.refetchEvents();
                eventModal.hide();
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>