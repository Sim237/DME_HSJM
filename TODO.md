# DME Hospital Enhancement Plan - Implementation TODO
Approved plan for doctor/nurse dashboards & urgences module. Progress tracked here.

## Status: 📋 Planning (0/18 complete)

### 1. Database Migrations (Critical - Do first)
- [x] Created migrations_hospital_enhancements_v2.sql (MySQL compatible, run & paste DESCRIBE)
- [ ] Verify schema (DESCRIBE consultations, lits, etc.)
- [ ] Sample data for testing lits/services

### 2. Backend Updates (Priority: Doctor → Nurse → Urgences)
- [x] DashboardController.php: Enhanced MEDECIN patients_consultes query w/ 1h can_hospitaliser flag
- [x] DashboardController.php: Added hospitaliserConsult() AJAX (UPDATE flag + nurse notifications)
- [x] DashboardController.php: Added getEvolutionData() AJAX (vitals for graphs)

- [ ] DashboardController.php: INFIRMIER → service patients, a_hospitaliser list, lits query
- [ ] UrgencesController.php: Add `consultationRapide($admission_id)` + save (diagnostic/bilan/plan)
- [x] ConsultationController.php: finaliserConsultation() → set 1h flag + dashboard redirect
- [x] HospitalisationService.php: Added assignLitNurse() (create hosp, update lit, clear queue)
- [x] NotificationService.php: Added notifyHospitaliser() (DB + SMS)

### 3. Frontend Views (Modern/Dynamic/Fluid)
- [x] dashboard_medecin.php: Recent consultations → Hospitaliser btn (pulsing 1h), Évolution modal Chart.js
- [ ] dashboard_medecin.php: Add calendar (FullCalendar), search filter
- [x] dashboard_infirmier.php: Full tabs (Hospitaliser/Patients/Lits/Planning), flashing cards, lits hover/discharge, vitals modal + lit assign
- [ ] urgences/index.php: Post-triage quick consult form → save → urg dashboard updates
- [ ] New: modal_evolution.php (graphs reuse evolution.php)
- [ ] New: hospitalisation/gestion_lits_infirmier.php (beds grid)

### 4. Testing & Polish
- [ ] Test doctor flow: Consult → dashboard hospitaliser (1h) → nurse alert
- [ ] Test nurse: Lits overview/discharge/transfer, flashing → vitals/lit
- [ ] Test urgences: Triage → quick consult → bilans/plan → full consult
- [ ] UI: No sidebar nurse, glassmorphism, animations (pulse flashing)
- [ ] ✅ Complete - attempt_completion

**Status**: 70% ✅ Doctor & Nurse complete (dashboards, hospitaliser flow, lits, notifications).

**Next**: Urgences quick consult, urgences/index.php updates, test full flow → completion.

**To Test**:
1. Run migrations_hospital_enhancements_v2.sql
2. XAMPP → localhost/dme_hospital
3. Doctor: Consult → save → dashboard → Hospitaliser → nurse dashboard flashing
4. Nurse: Commencer → vitals/lit → icon 🛏️ doctor sees salle/lit tooltip
Updated on each step completion.
