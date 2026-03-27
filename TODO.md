# Task: Make "Envoyer au laboratoire" from consultation etape4 appear in "Suivi des bilans demandés" dashboard

## Approved Plan Steps

- [x] **Step 1**: Fix broken JOIN query in `app/controllers/DashboardController.php::loadDoctorWardData()` for `$suivi_bilans` Labo subquery (use demande_examens)
- [x] **Step 2**: Fix similar query in `app/controllers/PatientController.php::dossier()` for patient dossier consistency
- [ ] **Step 3**: Update `app/views/dashboard/dashboard_medecin.php` to handle aggregated labels (e.g., "3 examens: NFS, CRP, ...")
- [ ] **Step 4**: Test - Create lab request from consultation etape4 → verify appears in doctor dashboard → attempt_completion

**Current Progress**: Steps 1-2 complete, Step 3 view update, Step 4 test
