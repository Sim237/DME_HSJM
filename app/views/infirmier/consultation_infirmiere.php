<?php
/**
 * SimCare+ — Dossier Médical Électronique (DME)
 * Copyright (c) 2024-2026 Franck Simeni. Tous droits réservés.
 * Développé pour la gestion hospitalière, et le bien être numérique des patients.
 *
 * Toute reproduction, modification ou distribution de ce logiciel,
 * en tout ou en partie, sans autorisation écrite préalable de l'auteur
 * est strictement interdite et constitue une contrefaçon.
 *
 * Protected under OAPI Agreement — Annexe VII · Berne Convention
 */

/**
 * Formulaire de consultation infirmière
 * Variables disponibles :
 *   $patient, $atcd, $dernieres_constantes, $medicaments, $examens_labo, $lits_disponibles, $services
 */
$age = !empty($patient['date_naissance'])
    ? date_diff(date_create($patient['date_naissance']), date_create('now'))->y
    : '?';
$ini = strtoupper(mb_substr($patient['nom'],0,1).mb_substr($patient['prenom']??'',0,1));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consultation Infirmière — <?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.min.css">
<style>
:root{
    --teal:#0d9488; --teal-lt:#f0fdfa;
    --blue:#1e40af; --blue-lt:#eff6ff;
    --slate:#64748b; --bg:#f1f5f9;
    --radius:14px; --shadow:0 2px 12px rgba(0,0,0,.07);
}
*,*::before,*::after{box-sizing:border-box}
body{background:var(--bg);font-family:'Segoe UI',system-ui,sans-serif;color:#1e293b;margin:0;font-size:.9rem;}

/* ── Topbar ── */
.ci-topbar{
    position:sticky;top:0;z-index:1000;background:#fff;
    border-bottom:2px solid #e2e8f0;
    padding:0 24px;height:64px;
    display:flex;align-items:center;justify-content:space-between;
    box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.ci-topbar-left{display:flex;align-items:center;gap:14px;}
.ci-patient-avatar{
    width:44px;height:44px;border-radius:12px;
    background:linear-gradient(135deg,#0d9488,#14b8a6);
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-weight:800;font-size:.9rem;flex-shrink:0;
}
.ci-patient-name{font-size:1rem;font-weight:700;color:#0f172a;line-height:1.2;}
.ci-patient-meta{font-size:.75rem;color:#94a3b8;}
.ci-badge{
    display:inline-flex;align-items:center;gap:.35rem;
    border-radius:20px;padding:.25rem .7rem;
    font-size:.72rem;font-weight:700;white-space:nowrap;
}

/* ── Layout ── */
.ci-layout{max-width:1200px;margin:0 auto;padding:24px;}
.ci-cols{display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start;}
@media(max-width:900px){.ci-cols{grid-template-columns:1fr;}}

/* ── Section cards ── */
.ci-section{
    background:#fff;border-radius:var(--radius);
    border:1px solid #e2e8f0;box-shadow:var(--shadow);
    margin-bottom:16px;overflow:hidden;
}
.ci-section-header{
    display:flex;align-items:center;gap:10px;
    padding:14px 20px;border-bottom:1px solid #f1f5f9;
    background:#fafafa;cursor:pointer;user-select:none;
}
.ci-section-header:hover{background:#f1f5f9;}
.ci-section-icon{
    width:34px;height:34px;border-radius:10px;
    display:flex;align-items:center;justify-content:center;
    font-size:.95rem;flex-shrink:0;
}
.ci-section-title{font-weight:700;font-size:.9rem;color:#1e293b;flex:1;}
.ci-section-body{padding:20px;}

/* ── Constantes grid ── */
.constantes-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;}
.constante-field label{font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:4px;}
.constante-field input{font-size:.9rem;font-weight:600;}

/* ── Ordonnance ── */
.ordo-ligne{
    display:grid;grid-template-columns:2fr 2fr 1fr 60px 36px;gap:8px;align-items:start;
    padding:10px;border-radius:10px;background:#f8fafc;border:1px solid #f1f5f9;margin-bottom:8px;
}
.ordo-ligne.hors-stock-active{border-color:#fbbf24;background:#fffbeb;}
.hors-stock-toggle{display:flex;align-items:center;gap:6px;margin-top:4px;font-size:.75rem;color:#92400e;font-weight:600;}
.hors-stock-badge{
    display:none;background:#fef3c7;color:#92400e;
    border-radius:6px;padding:1px 6px;font-size:.68rem;font-weight:700;
}
.ordo-ligne.hors-stock-active .hors-stock-badge{display:inline-block;}

/* ── Examens labo ── */
.examens-categorie{margin-bottom:14px;}
.examens-categorie-title{
    font-size:.72rem;font-weight:700;color:#64748b;
    text-transform:uppercase;letter-spacing:.5px;
    padding:6px 0;border-bottom:1px solid #f1f5f9;margin-bottom:8px;
}
.examen-checkbox-item{
    display:flex;align-items:center;gap:8px;
    padding:6px 10px;border-radius:8px;cursor:pointer;
    transition:background .12s;font-size:.83rem;
}
.examen-checkbox-item:hover{background:#f8fafc;}
.examen-checkbox-item input{width:16px;height:16px;cursor:pointer;accent-color:var(--teal);}

/* ── Devenir du malade ── */
.devenir-options{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;}
.devenir-card{
    border:2px solid #e2e8f0;border-radius:12px;padding:16px 12px;
    text-align:center;cursor:pointer;transition:all .15s;
    background:#fff;
}
.devenir-card:hover{border-color:#0d9488;background:var(--teal-lt);}
.devenir-card.selected{border-color:#0d9488;background:var(--teal-lt);box-shadow:0 0 0 3px rgba(13,148,136,.15);}
.devenir-card.selected-blue{border-color:#1e40af;background:var(--blue-lt);box-shadow:0 0 0 3px rgba(30,64,175,.15);}
.devenir-card.selected-amber{border-color:#d97706;background:#fffbeb;box-shadow:0 0 0 3px rgba(217,119,6,.15);}
.devenir-card.selected-red{border-color:#dc2626;background:#fef2f2;box-shadow:0 0 0 3px rgba(220,38,38,.15);}
.devenir-icon{font-size:2rem;margin-bottom:8px;}
.devenir-label{font-weight:700;font-size:.85rem;}
.devenir-sub{font-size:.72rem;color:#94a3b8;margin-top:2px;}
.devenir-detail{display:none;margin-top:16px;animation:fadeIn .2s ease;}
.devenir-detail.visible{display:block;}
@keyframes fadeIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}

/* ── Sidebar sticky ── */
.ci-sidebar{position:sticky;top:84px;}
.ci-submit-card{
    background:linear-gradient(135deg,#0d9488,#0f766e);
    border-radius:var(--radius);padding:20px;color:#fff;margin-bottom:16px;
}
.ci-submit-btn{
    width:100%;padding:14px;border-radius:10px;border:none;
    background:#fff;color:#0d9488;font-weight:800;font-size:1rem;
    cursor:pointer;transition:all .15s;
}
.ci-submit-btn:hover{background:#f0fdfa;transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.15);}
.ci-submit-btn:disabled{opacity:.6;cursor:not-allowed;transform:none;}

/* ── Dossier résumé ── */
.ci-info-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:.82rem;}
.ci-info-row:last-child{border-bottom:none;}
.ci-info-label{color:#94a3b8;font-weight:600;}
.ci-info-value{font-weight:600;color:#1e293b;text-align:right;}

/* Observation countdown */
.obs-countdown{
    background:#fff3cd;border:1px solid #ffc107;border-radius:10px;
    padding:12px;text-align:center;font-weight:700;color:#856404;margin-top:12px;
}
</style>
</head>
<body>

<!-- ══════════════════════════════ TOPBAR ══════════════════════════════ -->
<nav class="ci-topbar no-print">
    <div class="ci-topbar-left">
        <a href="<?= BASE_URL ?>dashboard" class="btn btn-sm btn-outline-secondary rounded-pill me-2"
           style="font-size:.8rem;">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>
        <div class="ci-patient-avatar"><?= $ini ?></div>
        <div>
            <div class="ci-patient-name">
                <?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?>
            </div>
            <div class="ci-patient-meta">
                <?= $patient['dossier_numero'] ?> · <?= $age ?> ans ·
                <?= $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?>
                <?php if(!empty($patient['nom_service'])): ?>
                 · <span style="color:#0d9488;"><?= htmlspecialchars($patient['nom_service']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2">
        <span class="ci-badge" style="background:#f0fdfa;color:#0d9488;border:1px solid #99f6e4;">
            <i class="bi bi-clipboard2-pulse"></i> Consultation Infirmière
        </span>
        <span class="ci-badge" style="background:#f1f5f9;color:#64748b;">
            <i class="bi bi-person-badge"></i>
            <?= htmlspecialchars(($_SESSION['user_prenom']??'').' '.($_SESSION['user_nom']??'')) ?>
        </span>
    </div>
</nav>

<!-- ══════════════════════════════ CONTENU ══════════════════════════════ -->
<div class="ci-layout">
<form id="formConsultInf" autocomplete="off">
<input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

<div class="ci-cols">

<!-- ══ COLONNE PRINCIPALE ══════════════════════════════════════════════ -->
<div>

    <!-- ①  MOTIF & ANAMNÈSE -->
    <div class="ci-section">
        <div class="ci-section-header" onclick="toggleSection(this)">
            <div class="ci-section-icon" style="background:#eff6ff;color:#1e40af;">
                <i class="bi bi-chat-text-fill"></i>
            </div>
            <span class="ci-section-title">① Motif &amp; Anamnèse</span>
            <i class="bi bi-chevron-up toggle-icon"></i>
        </div>
        <div class="ci-section-body">
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Motif de consultation <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" name="motif_consultation"
                       placeholder="Ex : Fièvre, douleurs abdominales, pansement…" required>
            </div>
            <div>
                <label class="form-label fw-semibold">Histoire de la maladie</label>
                <textarea class="form-control" name="histoire_maladie" rows="4"
                          placeholder="Décrivez l'évolution des symptômes, la chronologie, les traitements déjà pris…"></textarea>
            </div>
        </div>
    </div>

    <!-- ② ANTÉCÉDENTS -->
    <div class="ci-section">
        <div class="ci-section-header" onclick="toggleSection(this)">
            <div class="ci-section-icon" style="background:#fdf4ff;color:#9333ea;">
                <i class="bi bi-clock-history"></i>
            </div>
            <span class="ci-section-title">② Antécédents</span>
            <i class="bi bi-chevron-up toggle-icon"></i>
        </div>
        <div class="ci-section-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Antécédents médicaux</label>
                    <textarea class="form-control" name="atcd_medicaux" rows="3"
                              placeholder="HTA, diabète, asthme…"><?= htmlspecialchars($atcd['atcd_medicaux']??'') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Antécédents chirurgicaux</label>
                    <textarea class="form-control" name="atcd_chirurgicaux" rows="3"
                              placeholder="Appendicite, césarienne…"><?= htmlspecialchars($atcd['atcd_chirurgicaux']??'') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Antécédents familiaux</label>
                    <textarea class="form-control" name="atcd_familiaux" rows="2"
                              placeholder="Cardiopathies familiales, cancer…"><?= htmlspecialchars($atcd['atcd_familiaux']??'') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Allergies <span class="badge bg-danger-subtle text-danger ms-1" style="font-size:.68rem;">Important</span>
                    </label>
                    <textarea class="form-control border-danger" name="atcd_allergies" rows="2"
                              placeholder="Pénicilline, aspirine, latex…"><?= htmlspecialchars($atcd['atcd_allergies']??'') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- ③ EXAMENS PHYSIQUES & CONSTANTES -->
    <div class="ci-section">
        <div class="ci-section-header" onclick="toggleSection(this)">
            <div class="ci-section-icon" style="background:#f0fdf4;color:#16a34a;">
                <i class="bi bi-activity"></i>
            </div>
            <span class="ci-section-title">③ Examens Physiques &amp; Constantes</span>
            <i class="bi bi-chevron-up toggle-icon"></i>
        </div>
        <div class="ci-section-body">
            <!-- Constantes vitales -->
            <h6 class="fw-bold text-muted mb-3" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.5px;">
                <i class="bi bi-heart-pulse me-1"></i>Constantes vitales
            </h6>
            <div class="constantes-grid mb-4">
                <div class="constante-field">
                    <label>Température (°C)</label>
                    <input type="number" step="0.1" min="34" max="43" class="form-control"
                           name="temperature" placeholder="37.0"
                           value="<?= $dernieres_constantes['temperature']??'' ?>">
                </div>
                <div class="constante-field">
                    <label>FC (bpm)</label>
                    <input type="number" step="1" min="20" max="300" class="form-control"
                           name="frequence_cardiaque" placeholder="80"
                           value="<?= $dernieres_constantes['frequence_cardiaque']??'' ?>">
                </div>
                <div class="constante-field">
                    <label>FR (/min)</label>
                    <input type="number" step="1" min="5" max="60" class="form-control"
                           name="frequence_respiratoire" placeholder="18"
                           value="<?= $dernieres_constantes['frequence_respiratoire']??'' ?>">
                </div>
                <div class="constante-field">
                    <label>TA syst. (mmHg)</label>
                    <input type="number" step="1" min="50" max="300" class="form-control"
                           name="tension_systolique" placeholder="120"
                           value="<?= $dernieres_constantes['tension_systolique']??'' ?>">
                </div>
                <div class="constante-field">
                    <label>TA diast. (mmHg)</label>
                    <input type="number" step="1" min="30" max="200" class="form-control"
                           name="tension_diastolique" placeholder="80"
                           value="<?= $dernieres_constantes['tension_diastolique']??'' ?>">
                </div>
                <div class="constante-field">
                    <label>SpO₂ (%)</label>
                    <input type="number" step="1" min="50" max="100" class="form-control"
                           name="spo2" placeholder="98"
                           value="<?= $dernieres_constantes['spo2']??'' ?>">
                </div>
                <div class="constante-field">
                    <label>Poids (kg)</label>
                    <input type="number" step="0.1" min="0" max="300" class="form-control"
                           name="poids" placeholder="70"
                           value="<?= $dernieres_constantes['poids']??'' ?>">
                </div>
                <div class="constante-field">
                    <label>Taille (cm)</label>
                    <input type="number" step="1" min="20" max="250" class="form-control"
                           name="taille" placeholder="170"
                           value="<?= $dernieres_constantes['taille']??'' ?>">
                </div>
            </div>

            <!-- Examen clinique -->
            <h6 class="fw-bold text-muted mb-2" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.5px;">
                <i class="bi bi-stethoscope me-1"></i>Examen clinique
            </h6>
            <textarea class="form-control" name="examen_physique" rows="5"
                      placeholder="Apparence générale, état de conscience, examen par appareils (cardiovasculaire, respiratoire, abdomen, neurologique, cutané…)"></textarea>
        </div>
    </div>

    <!-- ④ DEMANDES DE BILANS -->
    <div class="ci-section">
        <div class="ci-section-header" onclick="toggleSection(this)">
            <div class="ci-section-icon" style="background:#f0fdf4;color:#16a34a;">
                <i class="bi bi-flask-fill"></i>
            </div>
            <span class="ci-section-title">④ Demandes de Bilans Laboratoire</span>
            <i class="bi bi-chevron-down toggle-icon"></i>
        </div>
        <div class="ci-section-body" style="display:none;">
            <div class="d-flex gap-3 mb-3 flex-wrap">
                <div class="flex-grow-1">
                    <input type="text" id="rechercheExamen" class="form-control form-control-sm"
                           placeholder="🔍 Filtrer les examens…">
                </div>
                <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                    <input class="form-check-input" type="checkbox" id="bilansUrgence" name="bilans_urgence">
                    <label class="form-check-label fw-semibold text-danger" for="bilansUrgence" style="font-size:.83rem;">
                        <i class="bi bi-lightning-fill"></i> URGENT
                    </label>
                </div>
            </div>
            <div class="mb-3">
                <textarea class="form-control form-control-sm" name="bilans_observations" rows="2"
                          placeholder="Indications cliniques, renseignements au biologiste…"></textarea>
            </div>

            <!-- Examens groupés par catégorie -->
            <div id="examensListeContainer">
            <?php
            $groupes = [];
            foreach ($examens_labo as $ex) {
                $cat = $ex['categorie'] ?: 'Autres';
                $groupes[$cat][] = $ex;
            }
            ksort($groupes);
            foreach ($groupes as $cat => $exams): ?>
            <div class="examens-categorie" data-cat="<?= htmlspecialchars($cat) ?>">
                <div class="examens-categorie-title"><?= htmlspecialchars($cat) ?></div>
                <?php foreach ($exams as $ex): ?>
                <label class="examen-checkbox-item" data-nom="<?= strtolower(htmlspecialchars($ex['nom_examen'])) ?>">
                    <input type="checkbox" name="examens[]" value="<?= $ex['id'] ?>">
                    <?= htmlspecialchars($ex['nom_examen']) ?>
                </label>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
            <?php if (empty($examens_labo)): ?>
            <p class="text-muted text-center py-3">Aucun examen configuré dans le système.</p>
            <?php endif; ?>
            </div>

            <div class="mt-3 p-2 rounded-3" style="background:#f8fafc;font-size:.8rem;color:#64748b;">
                <span id="countExamensSelec">0</span> examen(s) sélectionné(s)
                <button type="button" class="btn btn-sm btn-outline-secondary ms-2 py-0" onclick="toutDecocher()">
                    Tout décocher
                </button>
            </div>
        </div>
    </div>

    <!-- ⑤ ORDONNANCES -->
    <div class="ci-section">
        <div class="ci-section-header" onclick="toggleSection(this)">
            <div class="ci-section-icon" style="background:#fff7ed;color:#ea580c;">
                <i class="bi bi-capsule-pill"></i>
            </div>
            <span class="ci-section-title">⑤ Ordonnances Médicamenteuses</span>
            <i class="bi bi-chevron-down toggle-icon"></i>
        </div>
        <div class="ci-section-body" style="display:none;">
            <div class="alert alert-info border-0 rounded-3 py-2 mb-3" style="font-size:.8rem;">
                <i class="bi bi-info-circle me-1"></i>
                Vous pouvez prescrire des médicaments du stock pharmacie <strong>ou hors stock</strong>
                (médicaments non référencés — cochez <strong>Hors stock</strong>).
            </div>

            <div id="ordo-lignes"></div>

            <button type="button" class="btn btn-outline-teal rounded-pill mt-2"
                    style="font-size:.82rem;color:#0d9488;border-color:#0d9488;"
                    onclick="ajouterLigneOrdo()">
                <i class="bi bi-plus-circle me-1"></i>Ajouter un médicament
            </button>

            <!-- Datalist pour l'autocomplétion -->
            <datalist id="listeMedicaments">
                <?php foreach ($medicaments as $m): ?>
                <option value="<?= htmlspecialchars($m['nom_commercial']) ?>"
                        data-id="<?= $m['id'] ?>"
                        data-dci="<?= htmlspecialchars($m['dci']) ?>">
                    <?= htmlspecialchars($m['forme'].' '.$m['dosage']) ?>
                </option>
                <?php endforeach; ?>
            </datalist>
        </div>
    </div>

    <!-- ⑥ DEVENIR DU MALADE -->
    <div class="ci-section">
        <div class="ci-section-header" onclick="toggleSection(this)">
            <div class="ci-section-icon" style="background:#fef2f2;color:#dc2626;">
                <i class="bi bi-signpost-split-fill"></i>
            </div>
            <span class="ci-section-title">⑥ Devenir du Malade <span class="text-danger">*</span></span>
            <i class="bi bi-chevron-up toggle-icon"></i>
        </div>
        <div class="ci-section-body">
            <input type="hidden" name="devenir" id="devenirValue" value="">

            <div class="devenir-options">
                <!-- SORTIE -->
                <div class="devenir-card" data-devenir="SORTIE" onclick="selectionnerDevenir(this,'SORTIE')">
                    <div class="devenir-icon text-success"><i class="bi bi-box-arrow-right"></i></div>
                    <div class="devenir-label text-success">Sortie</div>
                    <div class="devenir-sub">Patient peut rentrer</div>
                </div>
                <!-- HOSPITALISATION -->
                <div class="devenir-card" data-devenir="HOSPITALISATION" onclick="selectionnerDevenir(this,'HOSPITALISATION')">
                    <div class="devenir-icon" style="color:#1e40af;"><i class="bi bi-hospital-fill"></i></div>
                    <div class="devenir-label" style="color:#1e40af;">Hospitalisation</div>
                    <div class="devenir-sub">Admettre sur un lit</div>
                </div>
                <!-- OBSERVATION -->
                <div class="devenir-card" data-devenir="OBSERVATION" onclick="selectionnerDevenir(this,'OBSERVATION')">
                    <div class="devenir-icon" style="color:#d97706;"><i class="bi bi-eye-fill"></i></div>
                    <div class="devenir-label" style="color:#d97706;">Mise en observation</div>
                    <div class="devenir-sub">Max 24h — décision ensuite</div>
                </div>
            </div>

            <!-- Détails SORTIE -->
            <div class="devenir-detail" id="detail-SORTIE">
                <div class="alert alert-success border-0 rounded-3 py-2" style="font-size:.85rem;">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Le patient sera marqué <strong>Sorti</strong> après enregistrement.
                    Pensez à remettre les ordonnances et les résultats d'examens.
                </div>
            </div>

            <!-- Détails HOSPITALISATION -->
            <div class="devenir-detail" id="detail-HOSPITALISATION">
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Service de destination</label>
                        <select class="form-select" name="devenir_service_id" id="devenirServiceId"
                                onchange="chargerLitsService(this.value)">
                            <option value="">— Choisir un service —</option>
                            <?php foreach ($services as $svc): ?>
                            <option value="<?= $svc['id'] ?>"><?= htmlspecialchars($svc['nom_service']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Lit disponible
                            <span id="nbLitsDispo" class="badge bg-success-subtle text-success ms-1"
                                  style="font-size:.68rem;display:none;"></span>
                        </label>
                        <select class="form-select" name="devenir_lit_id" id="devenirLitId">
                            <option value="">— Choisir un lit —</option>
                            <?php foreach ($lits_disponibles as $lit): ?>
                            <option value="<?= $lit['id'] ?>"
                                    data-service="<?= $lit['service_id'] ?>">
                                <?= htmlspecialchars($lit['nom_chambre'].' · '.$lit['nom_lit'].' ('.$lit['nom_service'].')') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <div id="litFiltre" class="alert alert-info border-0 py-2" style="font-size:.8rem;display:none;">
                            <i class="bi bi-info-circle me-1"></i>
                            <span id="litFiltreMsg">Sélectionnez un service pour filtrer les lits.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détails OBSERVATION -->
            <div class="devenir-detail" id="detail-OBSERVATION">
                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Durée d'observation
                            <span class="text-muted" style="font-size:.75rem;">(1–24h)</span>
                        </label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="observation_duree"
                                   id="obsHeure" min="1" max="24" value="6" placeholder="6">
                            <span class="input-group-text">heure(s)</span>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Motif de mise en observation</label>
                        <input type="text" class="form-control" name="observation_motif"
                               placeholder="Ex : Douleur thoracique à surveiller, fièvre à réévaluer…">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            Éléments à surveiller / observer
                        </label>
                        <textarea class="form-control" name="observation_elements" rows="3"
                                  placeholder="FC, TA toutes les 2h · Saturation · Réponse au traitement antalgique · État de conscience…"></textarea>
                    </div>
                    <div class="col-12">
                        <div class="obs-countdown" id="obsPreview" style="display:none;">
                            <i class="bi bi-hourglass-split me-2"></i>
                            Fin d'observation prévue : <strong id="obsFinDate">—</strong>
                            <br><small style="font-weight:400;">Vous serez notifié pour prendre une décision (sortie ou hospitalisation).</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div><!-- /colonne principale -->

<!-- ══ SIDEBAR ════════════════════════════════════════════════════════ -->
<div class="ci-sidebar">

    <!-- Bouton principal -->
    <div class="ci-submit-card">
        <div class="fw-bold mb-1" style="font-size:1rem;">
            <i class="bi bi-clipboard2-check me-2"></i>Enregistrer la consultation
        </div>
        <div style="font-size:.78rem;opacity:.8;margin-bottom:14px;">
            Toutes les informations seront visibles par le médecin du service.
        </div>
        <button type="button" id="btnSauvegarder" class="ci-submit-btn" onclick="sauvegarderConsultation()">
            <i class="bi bi-save2-fill me-2"></i>Enregistrer &amp; Valider
        </button>
    </div>

    <!-- Résumé patient -->
    <div class="ci-section">
        <div class="ci-section-header" style="cursor:default;">
            <div class="ci-section-icon" style="background:#eff6ff;color:#1e40af;">
                <i class="bi bi-person-vcard-fill"></i>
            </div>
            <span class="ci-section-title">Dossier patient</span>
        </div>
        <div class="ci-section-body">
            <div class="ci-info-row">
                <span class="ci-info-label">N° Dossier</span>
                <span class="ci-info-value"><?= htmlspecialchars($patient['dossier_numero']) ?></span>
            </div>
            <div class="ci-info-row">
                <span class="ci-info-label">Nom complet</span>
                <span class="ci-info-value"><?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?></span>
            </div>
            <div class="ci-info-row">
                <span class="ci-info-label">Âge / Sexe</span>
                <span class="ci-info-value"><?= $age ?> ans / <?= $patient['sexe'] === 'M' ? 'M' : 'F' ?></span>
            </div>
            <?php if (!empty($patient['telephone'])): ?>
            <div class="ci-info-row">
                <span class="ci-info-label">Téléphone</span>
                <span class="ci-info-value"><?= htmlspecialchars($patient['telephone']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($atcd['atcd_allergies'])): ?>
            <div class="ci-info-row">
                <span class="ci-info-label text-danger fw-bold">⚠ Allergies</span>
                <span class="ci-info-value text-danger"><?= htmlspecialchars(substr($atcd['atcd_allergies'],0,40)) ?></span>
            </div>
            <?php endif; ?>
            <div class="mt-3 d-grid">
                <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>"
                   class="btn btn-outline-secondary btn-sm rounded-pill" target="_blank">
                    <i class="bi bi-folder2-open me-1"></i>Ouvrir le dossier complet
                </a>
            </div>
        </div>
    </div>

    <!-- Récapitulatif sélection -->
    <div class="ci-section">
        <div class="ci-section-header" style="cursor:default;">
            <div class="ci-section-icon" style="background:#f0fdf4;color:#16a34a;">
                <i class="bi bi-list-check"></i>
            </div>
            <span class="ci-section-title">Récapitulatif</span>
        </div>
        <div class="ci-section-body" style="font-size:.82rem;color:#475569;">
            <div class="d-flex justify-content-between mb-1">
                <span>Bilans sélectionnés</span>
                <strong id="recapBilans">0</strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span>Médicaments prescrits</span>
                <strong id="recapMeds">0</strong>
            </div>
            <div class="d-flex justify-content-between">
                <span>Devenir</span>
                <strong id="recapDevenir" class="text-muted">Non défini</strong>
            </div>
        </div>
    </div>

</div><!-- /sidebar -->

</div><!-- /ci-cols -->
</form>
</div><!-- /ci-layout -->

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
/* ══════════════════════════════════════════════════════
   CONSULTATION INFIRMIÈRE — JS
══════════════════════════════════════════════════════ */

// ── Toggle sections ──────────────────────────────────
function toggleSection(header) {
    const body = header.nextElementSibling;
    const icon = header.querySelector('.toggle-icon');
    const isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : 'block';
    icon.className = 'bi ' + (isOpen ? 'bi-chevron-down' : 'bi-chevron-up') + ' toggle-icon';
}

// ── Devenir du malade ────────────────────────────────
function selectionnerDevenir(card, type) {
    // Retirer toutes les sélections
    document.querySelectorAll('.devenir-card').forEach(c => {
        c.classList.remove('selected','selected-blue','selected-amber','selected-red');
    });
    // Masquer tous les détails
    document.querySelectorAll('.devenir-detail').forEach(d => d.classList.remove('visible'));

    // Appliquer la classe
    const classes = {
        SORTIE: 'selected',
        HOSPITALISATION: 'selected-blue',
        OBSERVATION: 'selected-amber',
    };
    card.classList.add(classes[type] || 'selected');

    // Mettre à jour le champ caché
    document.getElementById('devenirValue').value = type;

    // Afficher le détail correspondant
    const detail = document.getElementById('detail-' + type);
    if (detail) detail.classList.add('visible');

    // Mettre à jour le récap
    const labels = { SORTIE: '🟢 Sortie', HOSPITALISATION: '🔵 Hospitalisation', OBSERVATION: '🟡 Observation' };
    document.getElementById('recapDevenir').textContent = labels[type] || type;
    document.getElementById('recapDevenir').style.color = '#1e293b';
}

// ── Filtre lits par service ──────────────────────────
function chargerLitsService(serviceId) {
    const select  = document.getElementById('devenirLitId');
    const badge   = document.getElementById('nbLitsDispo');
    const options = Array.from(select.querySelectorAll('option'));
    let visible = 0;

    options.forEach(opt => {
        if (!opt.value) return;
        const show = !serviceId || opt.dataset.service == serviceId;
        opt.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    if (serviceId) {
        badge.textContent = visible + ' lit(s) dispo';
        badge.style.display = '';
    } else {
        badge.style.display = 'none';
    }

    // Réinitialiser la sélection
    select.value = '';
}

// ── Preview fin d'observation ─────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const heureInput = document.getElementById('obsHeure');
    if (heureInput) {
        heureInput.addEventListener('input', mettreAJourPreviewObs);
    }
});

function mettreAJourPreviewObs() {
    const h = Math.min(24, Math.max(1, parseInt(document.getElementById('obsHeure').value) || 6));
    const fin = new Date(Date.now() + h * 3600 * 1000);
    const opts = { weekday:'long', day:'numeric', month:'long', hour:'2-digit', minute:'2-digit' };
    document.getElementById('obsFinDate').textContent = fin.toLocaleDateString('fr-FR', opts);
    document.getElementById('obsPreview').style.display = 'block';
}

// ── Ordonnances ──────────────────────────────────────
let ordoCount = 0;
function ajouterLigneOrdo() {
    const idx = ordoCount++;
    const div = document.createElement('div');
    div.className = 'ordo-ligne';
    div.id = 'ordo-' + idx;
    div.innerHTML = `
        <div>
            <label style="font-size:.72rem;color:#64748b;font-weight:600;text-transform:uppercase;">Médicament</label>
            <div class="d-flex gap-1 align-items-center">
                <input type="text" class="form-control form-control-sm ordo-nom"
                       name="medicaments[${idx}][nom]" list="listeMedicaments"
                       placeholder="Nom du médicament"
                       oninput="onNomMedChange(this, ${idx})">
                <input type="hidden" name="medicaments[${idx}][id]" id="ordo-id-${idx}">
                <input type="hidden" name="medicaments[${idx}][hors_stock]" id="ordo-hs-${idx}" value="0">
                <span class="hors-stock-badge">HORS STOCK</span>
            </div>
            <div class="hors-stock-toggle">
                <input type="checkbox" id="hs-chk-${idx}"
                       onchange="toggleHorsStock(${idx}, this.checked)"
                       style="width:14px;height:14px;accent-color:#d97706;">
                <label for="hs-chk-${idx}" style="cursor:pointer;">Hors stock (prescription libre)</label>
            </div>
        </div>
        <div>
            <label style="font-size:.72rem;color:#64748b;font-weight:600;text-transform:uppercase;">Posologie</label>
            <input type="text" class="form-control form-control-sm"
                   name="medicaments[${idx}][posologie]" placeholder="Ex : 1cp matin et soir pendant">
        </div>
        <div>
            <label style="font-size:.72rem;color:#64748b;font-weight:600;text-transform:uppercase;">Durée</label>
            <input type="text" class="form-control form-control-sm"
                   name="medicaments[${idx}][duree]" placeholder="7 jours">
        </div>
        <div>
            <label style="font-size:.72rem;color:#64748b;font-weight:600;text-transform:uppercase;">Qté</label>
            <input type="number" class="form-control form-control-sm"
                   name="medicaments[${idx}][quantite]" value="1" min="1" max="999">
        </div>
        <div style="display:flex;align-items:flex-end;padding-bottom:2px;">
            <button type="button" class="btn btn-sm btn-outline-danger rounded-2"
                    style="width:34px;height:34px;padding:0;font-size:.9rem;"
                    onclick="document.getElementById('ordo-${idx}').remove(); mettreAJourRecap();">
                <i class="bi bi-trash3"></i>
            </button>
        </div>
    `;
    document.getElementById('ordo-lignes').appendChild(div);
    mettreAJourRecap();
}

function onNomMedChange(input, idx) {
    // Trouver l'option correspondante dans le datalist
    const opt = document.querySelector(`#listeMedicaments option[value="${input.value}"]`);
    document.getElementById('ordo-id-' + idx).value = opt ? (opt.dataset.id || '') : '';
}

function toggleHorsStock(idx, checked) {
    const ligne = document.getElementById('ordo-' + idx);
    document.getElementById('ordo-hs-' + idx).value = checked ? '1' : '0';
    ligne.classList.toggle('hors-stock-active', checked);
}

// ── Filtrage examens ─────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('rechercheExamen');
    if (input) {
        input.addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.examen-checkbox-item').forEach(item => {
                const visible = !q || item.dataset.nom.includes(q);
                item.style.display = visible ? '' : 'none';
            });
            // Masquer les catégories vides
            document.querySelectorAll('.examens-categorie').forEach(cat => {
                const hasVisible = [...cat.querySelectorAll('.examen-checkbox-item')]
                    .some(i => i.style.display !== 'none');
                cat.style.display = hasVisible ? '' : 'none';
            });
        });
    }

    // Compteur examens
    document.querySelectorAll('input[name="examens[]"]').forEach(cb => {
        cb.addEventListener('change', function() {
            const n = document.querySelectorAll('input[name="examens[]"]:checked').length;
            document.getElementById('countExamensSelec').textContent = n;
            document.getElementById('recapBilans').textContent = n;
        });
    });

    // Preview observation à l'ouverture
    mettreAJourPreviewObs();
});

function toutDecocher() {
    document.querySelectorAll('input[name="examens[]"]').forEach(cb => cb.checked = false);
    document.getElementById('countExamensSelec').textContent = '0';
    document.getElementById('recapBilans').textContent = '0';
}

// ── Récapitulatif ────────────────────────────────────
function mettreAJourRecap() {
    const meds = document.querySelectorAll('.ordo-nom').length;
    document.getElementById('recapMeds').textContent = meds;
}

// ── Sauvegarde ───────────────────────────────────────
function sauvegarderConsultation() {
    // Validation
    const motif   = document.querySelector('[name="motif_consultation"]').value.trim();
    const devenir = document.getElementById('devenirValue').value;

    if (!motif) {
        showToast('error', 'Le motif de consultation est obligatoire.');
        document.querySelector('[name="motif_consultation"]').focus();
        return;
    }
    if (!devenir) {
        showToast('error', 'Veuillez sélectionner le devenir du malade.');
        document.getElementById('detail-SORTIE').scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    const btn = document.getElementById('btnSauvegarder');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement…';

    const formData = new FormData(document.getElementById('formConsultInf'));

    fetch('<?= BASE_URL ?>infirmier/consultation/sauvegarder', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message || 'Consultation enregistrée !');
            setTimeout(() => { window.location.href = data.redirect; }, 1200);
        } else {
            showToast('error', data.message || 'Erreur lors de la sauvegarde.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save2-fill me-2"></i>Enregistrer & Valider';
        }
    })
    .catch(() => {
        showToast('error', 'Erreur réseau.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save2-fill me-2"></i>Enregistrer & Valider';
    });
}

// ── Toast ────────────────────────────────────────────
function showToast(type, msg) {
    const t = document.createElement('div');
    const isOk = type === 'success';
    t.style.cssText = `position:fixed;top:80px;right:20px;z-index:9999;
        background:${isOk?'#16a34a':'#dc2626'};color:#fff;
        border-radius:12px;padding:12px 18px;font-size:.85rem;font-weight:600;
        display:flex;align-items:center;gap:8px;
        box-shadow:0 4px 20px rgba(0,0,0,.2);animation:fadeIn .2s ease;max-width:360px;`;
    t.innerHTML = `<i class="bi ${isOk?'bi-check-circle-fill':'bi-x-circle-fill'}"></i>${msg}`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 4000);
}
</script>
</body>
</html>
