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

// Initialisation des variables
$patient = $patient ?? [];
$consultation = $consultation_data ?? [];

include __DIR__ . '/../../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">


        <main class="col-12 px-md-4 consultation-form" style="margin-left: 0 !important;">

            <!-- Barre de progression (Étape 2) -->
            <?php
                $numero = 2;
                $type_consultation = $_GET['type'] ?? $consultation['type'] ?? 'EXTERNE';
                include __DIR__ . '/progress_bar.php';
            ?>

            <!-- FORMULAIRE -->
            <form action="<?php echo BASE_URL; ?>consultation/sauvegarder" method="POST">

                <!-- === CHAMPS CACHÉS INDISPENSABLES (À COPIER PARTOUT) === -->
                <input type="hidden" name="etape_actuelle" value="2"> <!-- Notez que c'est l'étape 2 -->
                <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_consultation); ?>">
                <!-- ======================================================= -->

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-stethoscope me-2"></i> EXAMEN PHYSIQUE</h5>
                    </div>
                    <div class="card-body">

                        <h6 class="text-success border-bottom pb-2 mb-3">Paramètres Vitaux (récupérés du tri)</h6>

<?php
// Helper : reconstruire "SYS/DIA" depuis 2 colonnes BDD ou prendre ce qu'a saisi le user
$buildTA = function($consultation, $colSys, $colDia, $fallback = '') {
    if (!empty($consultation[$colSys]) || !empty($consultation[$colDia])) {
        return ($consultation[$colSys] ?? '') . '/' . ($consultation[$colDia] ?? '');
    }
    return $fallback;
};
$ta_gauche = $buildTA($consultation, 'ta_bras_gauche_systolique', 'ta_bras_gauche_diastolique',
                     $consultation['ta_bras_gauche'] ?? '');
$ta_droit  = $buildTA($consultation, 'ta_bras_droit_systolique',  'ta_bras_droit_diastolique',
                     $consultation['ta_bras_droit']  ?? '');
$taNonPrenable = !empty($consultation['tension_non_prenable']);
?>

<!-- Ligne 1 : Température, Pression artérielle, Fréquence cardiaque, Poids -->
<div class="row g-3 mb-3">
    <!-- Température -->
    <div class="col-md-3">
        <label class="form-label fw-bold">TEMPÉRATURE (°C)</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-thermometer-half"></i></span>
            <input type="number" step="0.1" class="form-control" name="temperature"
                   value="<?= $consultation['temperature'] ?? $last_vitals['temperature'] ?? '' ?>">
        </div>
    </div>

    <!-- Tension Artérielle générale -->
    <div class="col-md-3">
        <label class="form-label fw-bold">PRESSION ARTÉRIELLE</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-heart-pulse"></i></span>
            <?php
                $ta_value = "";
                if(isset($last_vitals['pression_arterielle_systolique'])) {
                    $ta_value = $last_vitals['pression_arterielle_systolique'] . '/' . $last_vitals['pression_arterielle_diastolique'];
                }
            ?>
            <input type="text" class="form-control ta-classique" name="tension_arterielle"
                   value="<?= $consultation['tension_arterielle'] ?? $ta_value ?>"
                   <?= $taNonPrenable ? 'disabled' : '' ?>>
        </div>
        <small class="text-muted">mmHg (Ex: 120/80)</small>
    </div>

    <!-- Fréquence Cardiaque -->
    <div class="col-md-3">
        <label class="form-label fw-bold">FRÉQUENCE CARDIAQUE</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-activity"></i></span>
            <input type="number" class="form-control" name="frequence_cardiaque"
                   value="<?= $consultation['frequence_cardiaque'] ?? $last_vitals['frequence_cardiaque'] ?? '' ?>">
        </div>
        <small class="text-muted">Bpm</small>
    </div>

    <!-- Poids -->
    <div class="col-md-3">
        <label class="form-label fw-bold">POIDS (KG)</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-speedometer2"></i></span>
            <input type="number" step="0.1" class="form-control" name="poids"
                   value="<?= $consultation['poids'] ?? $last_vitals['poids'] ?? '' ?>">
        </div>
    </div>
</div>

<!-- Ligne 2 : Fréquence respiratoire, TA bras gauche/droit, Pouls (tous optionnels) -->
<div class="row g-3 mb-3">
    <!-- Fréquence respiratoire -->
    <div class="col-md-3">
        <label class="form-label fw-bold">FRÉQUENCE RESPIRATOIRE
            <small class="fw-normal text-muted">(opt.)</small>
        </label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lungs"></i></span>
            <input type="number" min="0" max="200" class="form-control" name="frequence_respiratoire"
                   value="<?= htmlspecialchars($consultation['frequence_respiratoire'] ?? '') ?>">
        </div>
        <small class="text-muted">Cycles/min</small>
    </div>

    <!-- TA bras gauche -->
    <div class="col-md-3">
        <label class="form-label fw-bold">TA BRAS GAUCHE
            <small class="fw-normal text-muted">(opt.)</small>
        </label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-hand-index" style="transform:scaleX(-1);"></i></span>
            <input type="text" class="form-control ta-bras" name="ta_bras_gauche"
                   placeholder="120/80" pattern="\d{2,3}/\d{2,3}"
                   value="<?= htmlspecialchars($ta_gauche) ?>"
                   <?= $taNonPrenable ? 'disabled' : '' ?>>
        </div>
        <small class="text-muted">mmHg (Ex: 120/80)</small>
    </div>

    <!-- TA bras droit -->
    <div class="col-md-3">
        <label class="form-label fw-bold">TA BRAS DROIT
            <small class="fw-normal text-muted">(opt.)</small>
        </label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-hand-index"></i></span>
            <input type="text" class="form-control ta-bras" name="ta_bras_droit"
                   placeholder="120/80" pattern="\d{2,3}/\d{2,3}"
                   value="<?= htmlspecialchars($ta_droit) ?>"
                   <?= $taNonPrenable ? 'disabled' : '' ?>>
        </div>
        <small class="text-muted">mmHg (Ex: 120/80)</small>
    </div>

    <!-- Pouls -->
    <div class="col-md-3">
        <label class="form-label fw-bold">POULS
            <small class="fw-normal text-muted">(opt.)</small>
        </label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-heart"></i></span>
            <input type="number" min="0" max="300" class="form-control" name="pouls"
                   value="<?= htmlspecialchars($consultation['pouls'] ?? '') ?>">
        </div>
        <small class="text-muted">Bpm (palpé)</small>
    </div>
</div>

<!-- Ligne 3 : Saturation et Glycémie Capillaire -->
<?php $spo2NonPrenable = !empty($consultation['spo2_non_prenable']); ?>
<div class="row g-3 mb-3">
    <!-- Saturation en oxygène (SpO2) -->
    <div class="col-md-3">
        <label class="form-label fw-bold">SATURATION (SpO₂)
            <small class="fw-normal text-muted">(opt.)</small>
        </label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-droplet-half"></i></span>
            <input type="number" step="0.1" min="0" max="100"
                   class="form-control vital-check" id="inputSaturation" name="saturation"
                   data-vital="spo2"
                   value="<?= htmlspecialchars($consultation['saturation'] ?? $last_vitals['saturation_oxygene'] ?? '') ?>"
                   <?= $spo2NonPrenable ? 'disabled' : '' ?>
                   oninput="checkVital(this)">
            <span class="input-group-text">%</span>
        </div>
        <small class="text-muted">SpO₂ (Ex: 98)</small>
        <div id="alert-spo2" class="vital-alert mt-1" style="display:none;"></div>
    </div>

    <!-- Glycémie Capillaire -->
    <div class="col-md-3">
        <label class="form-label fw-bold">GLYCÉMIE CAPILLAIRE
            <small class="fw-normal text-muted">(opt.)</small>
        </label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-droplet"></i></span>
            <input type="number" step="0.01" min="0" max="50"
                   class="form-control vital-check" name="glycemie_capillaire"
                   id="glycemie_capillaire"
                   data-vital="glycemie"
                   value="<?= htmlspecialchars($consultation['glycemie_capillaire'] ?? $last_vitals['glycemie'] ?? '') ?>"
                   oninput="checkVital(this)">
            <span class="input-group-text">g/L</span>
        </div>
        <div class="btn-group btn-group-sm w-100 mt-1" role="group" aria-label="Type de glycémie">
            <input type="radio" class="btn-check" name="glycemie_type" id="glyc_a_jeun"
                   value="a_jeun" autocomplete="off" onchange="onGlycemieTypeChange()"
                   <?= (($consultation['glycemie_type'] ?? '') === 'a_jeun') ? 'checked' : '' ?>>
            <label class="btn btn-outline-primary" for="glyc_a_jeun" title="Mesure prise après au moins 8h de jeûne">
                <i class="bi bi-moon-stars-fill me-1" style="font-size:.7rem;"></i>À jeun
            </label>
            <input type="radio" class="btn-check" name="glycemie_type" id="glyc_aleatoire"
                   value="aleatoire" autocomplete="off" onchange="onGlycemieTypeChange()"
                   <?= (($consultation['glycemie_type'] ?? '') === 'aleatoire') ? 'checked' : '' ?>>
            <label class="btn btn-outline-secondary" for="glyc_aleatoire" title="Mesure prise à n'importe quel moment">
                <i class="bi bi-shuffle me-1" style="font-size:.7rem;"></i>Aléatoire
            </label>
        </div>
        <div id="alert-glycemie" class="vital-alert mt-1" style="display:none;"></div>
    </div>
</div>

<!-- Ligne 4 : Indicateurs "non prenable" (tension + saturation) -->
<div class="row g-3 mb-4">

    <!-- Tension non prenable -->
    <div class="col-md-6">
        <div class="p-3 rounded-3 border h-100" style="background:#fffbeb; border-color:#fde68a !important;">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="tensionNonPrenable" name="tension_non_prenable"
                       value="1" <?= $taNonPrenable ? 'checked' : '' ?>
                       onchange="toggleTensionNonPrenable(this)">
                <label class="form-check-label fw-bold" for="tensionNonPrenable" style="color:#92400e;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Tension artérielle non prenable
                </label>
                <small class="d-block text-muted mt-1" style="margin-left:1.5rem;">
                    Cochez si la tension n'a pas pu être prise (patient agité, matériel indisponible, brûlure, etc.).
                    Les champs de tension seront alors désactivés.
                </small>
            </div>
            <div class="mt-2" id="motifTensionWrap" style="<?= $taNonPrenable ? '' : 'display:none;' ?> margin-left:1.5rem;">
                <label class="form-label small fw-semibold mb-1">Motif (optionnel)</label>
                <input type="text" name="motif_tension_non_prenable" class="form-control form-control-sm"
                       placeholder="Ex : matériel indisponible, patient en agitation…"
                       value="<?= htmlspecialchars($consultation['motif_tension_non_prenable'] ?? '') ?>">
            </div>
        </div>
    </div>

    <!-- Saturation non prenable -->
    <div class="col-md-6">
        <div class="p-3 rounded-3 border h-100" style="background:#eff6ff; border-color:#bfdbfe !important;">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="spo2NonPrenable" name="spo2_non_prenable"
                       value="1" <?= $spo2NonPrenable ? 'checked' : '' ?>
                       onchange="toggleSpo2NonPrenable(this)">
                <label class="form-check-label fw-bold" for="spo2NonPrenable" style="color:#1e40af;">
                    <i class="bi bi-droplet-half me-1"></i>
                    Saturation (SpO₂) non prenable
                </label>
                <small class="d-block text-muted mt-1" style="margin-left:1.5rem;">
                    Cochez si la saturation n'a pas pu être mesurée (ongle vernis, mauvaise perfusion, sonde non disponible, etc.).
                    Le champ SpO₂ sera désactivé.
                </small>
            </div>
            <div class="mt-2" id="motifSpo2Wrap" style="<?= $spo2NonPrenable ? '' : 'display:none;' ?> margin-left:1.5rem;">
                <label class="form-label small fw-semibold mb-1">Motif (optionnel)</label>
                <input type="text" name="motif_spo2_non_prenable" class="form-control form-control-sm"
                       placeholder="Ex : ongle vernis, sonde absente, mauvaise perfusion…"
                       value="<?= htmlspecialchars($consultation['motif_spo2_non_prenable'] ?? '') ?>">
            </div>
        </div>
    </div>
</div>

<!-- Commentaires sur les paramètres vitaux -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="p-3 rounded-3 border" style="background:#f8fafc; border-color:#e2e8f0 !important;">
            <label class="form-label fw-semibold mb-1" style="color:#374151;">
                <i class="bi bi-chat-left-text me-1" style="color:#6366f1;"></i>
                Commentaires sur les paramètres vitaux
                <small class="fw-normal text-muted">(opt.)</small>
            </label>
            <textarea name="commentaires_parametres" class="form-control form-control-sm"
                      rows="2" maxlength="500"
                      placeholder="Observations, contexte de mesure, discordances entre bras, évolution par rapport à la dernière visite…"
                      style="resize:vertical; font-size:.93rem;"><?= htmlspecialchars($consultation['commentaires_parametres'] ?? '') ?></textarea>
            <div class="d-flex justify-content-end mt-1">
                <small class="text-muted" id="cpCount">0 / 500</small>
            </div>
        </div>
    </div>
</div>
<script>
(function(){
    var ta = document.querySelector('textarea[name="commentaires_parametres"]');
    var ct = document.getElementById('cpCount');
    if(!ta||!ct) return;
    function upd(){ ct.textContent = ta.value.length + ' / 500'; }
    ta.addEventListener('input', upd);
    upd();
})();
</script>

<!-- Bandeau d'alertes vitaux critiques (affiché si ≥1 valeur anormale) -->
<div id="alerteVitauxBandeau" class="alert alert-danger border-0 rounded-3 mb-3 d-none" role="alert"
     style="background:linear-gradient(135deg,#fee2e2,#fecaca);border-left:4px solid #dc2626 !important;">
    <div class="d-flex align-items-center gap-2 mb-1">
        <i class="bi bi-exclamation-octagon-fill text-danger fs-5"></i>
        <strong class="text-danger">Paramètres vitaux anormaux détectés</strong>
    </div>
    <ul id="alerteVitauxListe" class="mb-0 ps-3 small text-danger"></ul>
</div>

<script>
/* ───────── Seuils normaux des paramètres vitaux ───────── */
const VITAUX_SEUILS = {
    temperature: {
        critique_bas:  35.0,  warn_bas:   36.0,
        warn_haut:     37.5,  critique_haut: 39.0,
        labels: ['Hypothermie critique', 'Hypothermie', 'Fièvre', 'Hyperthermie critique'],
        unite: '°C'
    },
    fc: {
        critique_bas:  40,    warn_bas:    60,
        warn_haut:    100,    critique_haut: 130,
        labels: ['Bradycardie sévère', 'Bradycardie', 'Tachycardie', 'Tachycardie sévère'],
        unite: 'bpm'
    },
    fr: {
        critique_bas:   8,    warn_bas:    12,
        warn_haut:     20,    critique_haut: 30,
        labels: ['Bradypnée sévère', 'Bradypnée', 'Tachypnée', 'Tachypnée sévère'],
        unite: 'cycles/min'
    },
    spo2: {
        critique_bas:  85,    warn_bas:    94,
        warn_haut:    100,    critique_haut: 101, // pas de limite haute
        labels: ['Hypoxémie critique', 'Hypoxémie', '', ''],
        unite: '%'
    },
    glycemie: {
        critique_bas: 0.50,   warn_bas:   0.70,
        warn_haut:    1.40,   critique_haut: 2.00,
        labels: ['Hypoglycémie critique', 'Hypoglycémie', 'Hyperglycémie', 'Hyperglycémie sévère'],
        unite: 'g/L'
    }
};

/* Map champ → clé seuil */
const CHAMP_MAP = {
    temperature:        'temperature',
    frequence_cardiaque:'fc',
    frequence_respiratoire:'fr',
    saturation:         'spo2',
    glycemie_capillaire:'glycemie'
};

/* Vérifie un champ et affiche l'alerte inline + met à jour le bandeau */
function checkVital(input) {
    const nomChamp = input.name;
    const cle      = CHAMP_MAP[nomChamp];
    if (!cle) return;

    const seuils  = VITAUX_SEUILS[cle];
    const val     = parseFloat(input.value);
    const alertId = 'alert-' + cle;
    const alertEl = document.getElementById(alertId);

    if (!alertEl) return;

    if (isNaN(val) || input.value === '') {
        alertEl.style.display = 'none';
        alertEl.innerHTML = '';
        input.classList.remove('is-invalid-vital','is-warn-vital');
        majBandeau();
        return;
    }

    let niveau = 0; // 0=OK 1=warn 2=critique
    let libelle = '';

    if (val < seuils.critique_bas) {
        niveau = 2; libelle = seuils.labels[0];
    } else if (val < seuils.warn_bas) {
        niveau = 1; libelle = seuils.labels[1];
    } else if (val > seuils.critique_haut) {
        niveau = 2; libelle = seuils.labels[3];
    } else if (val > seuils.warn_haut) {
        niveau = 1; libelle = seuils.labels[2];
    }

    input.classList.toggle('is-invalid-vital', niveau === 2);
    input.classList.toggle('is-warn-vital',    niveau === 1);

    if (niveau === 0) {
        alertEl.style.display = 'none';
        alertEl.innerHTML = '';
    } else {
        const bg    = niveau === 2 ? '#fee2e2' : '#fffbeb';
        const color = niveau === 2 ? '#dc2626' : '#92400e';
        const icon  = niveau === 2 ? 'bi-exclamation-octagon-fill' : 'bi-exclamation-triangle-fill';
        alertEl.style.display = '';
        alertEl.innerHTML = `
            <span class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2 fw-semibold"
                  style="background:${bg};color:${color};font-size:.78rem;border:1px solid ${color}33;">
                <i class="bi ${icon}"></i>
                ${libelle} — ${val} ${seuils.unite}
            </span>`;
    }
    majBandeau();
}

/* Adapte les seuils glycémie selon le type (à jeun / aléatoire) */
function onGlycemieTypeChange() {
    const type = document.querySelector('input[name="glycemie_type"]:checked')?.value;
    const seuils = VITAUX_SEUILS.glycemie;
    if (type === 'a_jeun') {
        // À jeun : normal < 1.10 g/L, diabète ≥ 1.26 g/L
        seuils.warn_haut     = 1.10;
        seuils.critique_haut = 1.26;
    } else {
        // Aléatoire ou non défini : normal < 1.40 g/L, diabète ≥ 2.00 g/L
        seuils.warn_haut     = 1.40;
        seuils.critique_haut = 2.00;
    }
    const input = document.getElementById('glycemie_capillaire');
    if (input && input.value !== '') checkVital(input);
}

/* Met à jour le bandeau résumé en haut */
function majBandeau() {
    const liste   = document.getElementById('alerteVitauxListe');
    const bandeau = document.getElementById('alerteVitauxBandeau');
    if (!liste || !bandeau) return;

    const items = [];
    document.querySelectorAll('.vital-check').forEach(inp => {
        if (inp.disabled || inp.value === '') return;
        const cle = CHAMP_MAP[inp.name];
        if (!cle) return;
        const s   = VITAUX_SEUILS[cle];
        const val = parseFloat(inp.value);
        if (isNaN(val)) return;

        let label = '';
        if      (val < s.critique_bas)  label = s.labels[0] + ' (' + val + ' ' + s.unite + ')';
        else if (val < s.warn_bas)       label = s.labels[1] + ' (' + val + ' ' + s.unite + ')';
        else if (val > s.critique_haut)  label = s.labels[3] + ' (' + val + ' ' + s.unite + ')';
        else if (val > s.warn_haut)      label = s.labels[2] + ' (' + val + ' ' + s.unite + ')';

        if (label) items.push('<li>' + label + '</li>');
    });

    /* TA : extraire systolique */
    const taInput = document.querySelector('.ta-classique:not(:disabled)');
    if (taInput && taInput.value) {
        const parts = taInput.value.split('/');
        const sys   = parseInt(parts[0], 10);
        const dia   = parseInt(parts[1], 10);
        if (!isNaN(sys)) {
            if (sys < 90)       items.push('<li>Hypotension sévère — TA ' + taInput.value + ' mmHg</li>');
            else if (sys < 100) items.push('<li>Hypotension — TA ' + taInput.value + ' mmHg</li>');
            else if (sys >= 180)items.push('<li>Urgence hypertensive — TA ' + taInput.value + ' mmHg</li>');
            else if (sys >= 160)items.push('<li>HTA sévère — TA ' + taInput.value + ' mmHg</li>');
            else if (sys >= 140)items.push('<li>HTA — TA ' + taInput.value + ' mmHg</li>');
        }
    }

    if (items.length > 0) {
        liste.innerHTML   = items.join('');
        bandeau.classList.remove('d-none');
    } else {
        bandeau.classList.add('d-none');
    }
}

/* Toggle TA non prenable */
function toggleTensionNonPrenable(cb) {
    const disabled = cb.checked;
    document.querySelectorAll('.ta-classique, .ta-bras').forEach(input => {
        input.disabled = disabled;
        if (disabled) input.value = '';
    });
    document.getElementById('motifTensionWrap').style.display = disabled ? '' : 'none';
    majBandeau();
}

/* Toggle SpO2 non prenable */
function toggleSpo2NonPrenable(cb) {
    const disabled = cb.checked;
    const inp = document.getElementById('inputSaturation');
    if (inp) {
        inp.disabled = disabled;
        if (disabled) { inp.value = ''; }
        const alertEl = document.getElementById('alert-spo2');
        if (alertEl) { alertEl.style.display = 'none'; alertEl.innerHTML = ''; }
        inp.classList.remove('is-invalid-vital','is-warn-vital');
    }
    document.getElementById('motifSpo2Wrap').style.display = disabled ? '' : 'none';
    majBandeau();
}

/* Alertes TA en saisie directe */
document.addEventListener('DOMContentLoaded', function() {
    /* Vérifier les champs déjà pré-remplis */
    document.querySelectorAll('.vital-check').forEach(function(inp) {
        if (inp.value && !inp.disabled) checkVital(inp);
    });
    majBandeau();

    /* Surveiller la TA manuelle */
    document.querySelectorAll('.ta-classique').forEach(function(inp) {
        inp.addEventListener('input', function() { majBandeau(); });
    });
});
</script>

<style>
.is-invalid-vital {
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 3px rgba(220,38,38,.15) !important;
}
.is-warn-vital {
    border-color: #d97706 !important;
    box-shadow: 0 0 0 3px rgba(217,119,6,.15) !important;
}
</style>

                        <h6 class="text-success border-bottom pb-2 mb-3">Examen Clinique Détaillé</h6>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Résultats de l'Examen Physique</label>
                            <textarea class="form-control" name="examen_physique" rows="6"
                                      placeholder="Décrivez l'examen tête aux pieds : État général, ORL, Cardio-pulmonaire, Abdominal, etc."><?php echo htmlspecialchars($consultation['examen_physique'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Résumé Syndromique</label>
                            <textarea class="form-control" name="resume_syndromique" rows="3"
                                      placeholder="Regroupement des symptômes en syndromes (ex: Syndrome grippal, Syndrome méningé...)"><?php echo htmlspecialchars($consultation['resume_syndromique'] ?? ''); ?></textarea>
                        </div>

                    </div>
                </div>

                <!-- Boutons de navigation -->
                <div class="card shadow-sm mb-5">
                    <div class="card-body d-flex justify-content-between">
                        <a href="<?php echo BASE_URL; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i> Annuler
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            Suivant <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </form>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>