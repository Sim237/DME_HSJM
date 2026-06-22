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
 require_once __DIR__ . '/../../layouts/header.php'; ?>

<?php
/* ── Formulaires gynéco liés ─────────────────────────────────────── */
$formsLies = [
    ['slug'=>'echo-pelvienne',          'titre'=>'Échographie pelvienne',        'icon'=>'bi-activity',          'color'=>'#9333ea'],
    ['slug'=>'echo-obstetricale',        'titre'=>'Écho obstétricale T1',         'icon'=>'bi-activity',          'color'=>'#7c3aed'],
    ['slug'=>'echo-obstetricale-t2-t3', 'titre'=>'Écho obstétricale T2/T3',      'icon'=>'bi-activity',          'color'=>'#7c3aed'],
    ['slug'=>'partogramme',              'titre'=>'Partogramme',                  'icon'=>'bi-graph-up-arrow',    'color'=>'#be123c'],
    ['slug'=>'fiche-suivi-cpn',          'titre'=>'Fiche suivi CPN',              'icon'=>'bi-table',             'color'=>'#0d9488'],
    ['slug'=>'info-mere-travail-enfant', 'titre'=>'Info mère / travail / enfant', 'icon'=>'bi-person-heart',      'color'=>'#f59e0b'],
    ['slug'=>'consentement-peridurale',  'titre'=>'Consentement péridurale',      'icon'=>'bi-file-earmark-check','color'=>'#8b5cf6'],
    ['slug'=>'certificat-grossesse',     'titre'=>'Certificat de grossesse',      'icon'=>'bi-heart-pulse',       'color'=>'#db2777'],
    ['slug'=>'certificat-accouchement',  'titre'=>'Certificat d\'accouchement',   'icon'=>'bi-hospital',          'color'=>'#059669'],
    ['slug'=>'ligature-trompes',         'titre'=>'Ligature des trompes',         'icon'=>'bi-clipboard2-pulse',  'color'=>'#be185d'],
    ['slug'=>'fiche-postpartum-immediat','titre'=>'Surveillance post partum',     'icon'=>'bi-clock-history',     'color'=>'#7c3aed'],
    ['slug'=>'consentement',             'titre'=>'Consentement éclairé',         'icon'=>'bi-pen',               'color'=>'#6366f1'],
];

/* ── Vérifier formulaires existants ──────────────────────────────── */
$formulairesExistants = [];
try {
    $db  = (new Database())->getConnection();
    $stmtEx = $db->prepare("SELECT type_formulaire, id, statut, date_creation
                             FROM formulaires_data
                             WHERE patient_id = ?
                             ORDER BY date_creation DESC");
    $stmtEx->execute([(int)$patient['id']]);
    foreach ($stmtEx->fetchAll(\PDO::FETCH_ASSOC) as $row) {
        if (!isset($formulairesExistants[$row['type_formulaire']])) {
            $formulairesExistants[$row['type_formulaire']] = $row;
        }
    }
} catch (\Exception $e) {}

/* ── Signature & Cachet du médecin connecté ──────────────────────── */
$signatureB64  = null;
$cachetB64     = null;
$medecinNomSig = 'Dr. ' . trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
$medecinSpec   = $_SESSION['specialite'] ?? '';
$medecinOrdre  = '';
try {
    require_once __DIR__ . '/../../../services/SignatureService.php';
    $sigSvc   = new SignatureService();
    $sigRaw   = $sigSvc->getSignature((int)($_SESSION['user_id'] ?? 0));
    $sigFull  = $sigSvc->getMedecinSignatureInfo((int)($_SESSION['user_id'] ?? 0));
    $signatureB64 = $sigRaw['signature_image'] ?? null;
    $cachetB64    = $sigRaw['cachet_image']    ?? null;
    $medecinSpec  = $sigFull['specialite']  ?? $medecinSpec;
    $medecinOrdre = $sigFull['numero_ordre'] ?? '';
    // Fallback URL → base64
    foreach ([['signature_src', &$signatureB64], ['cachet_src', &$cachetB64]] as [$key, &$b64]) {
        if (empty($b64) && !empty($sigFull[$key])) {
            $p = rtrim($_SERVER['DOCUMENT_ROOT'],'/\\') . parse_url($sigFull[$key], PHP_URL_PATH);
            $c = @file_get_contents($p);
            if ($c) $b64 = 'data:image/png;base64,' . base64_encode($c);
        }
    }
} catch (\Exception $e) {}
?>

<style>
/* ══ Design System Gynéco ══════════════════════════════════════════ */
:root {
    --gy-pink:    #db2777;
    --gy-pink-lt: #fdf2f8;
    --gy-dark:    #1e293b;
    --gy-muted:   #64748b;
    --border:     #e2e8f0;
}
body { background: #f0f4f8; }

/* ── Topbar ── */
.gy-topbar {
    position: sticky; top: 0; z-index: 300;
    background: white; border-bottom: 1px solid var(--border);
    padding: 10px 20px;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,.07);
}
.gy-topbar .spacer { flex: 1; }
.btn-gy-back  { display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;border:1.5px solid var(--border);background:white;color:#475569;font-weight:600;font-size:.85rem;text-decoration:none; }
.btn-gy-draft { display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;border:1.5px solid #fbcfe8;background:var(--gy-pink-lt);color:var(--gy-pink);font-weight:600;font-size:.85rem;cursor:pointer;transition:.2s; }
.btn-gy-print { display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--border);background:white;color:#475569;font-weight:600;font-size:.85rem;cursor:pointer; }
.topbar-title { font-weight:800;font-size:.97rem;color:var(--gy-dark);display:flex;align-items:center;gap:7px; }
.topbar-title i { color:var(--gy-pink); }

/* ── Bannière patient ── */
.gy-banner {
    background: linear-gradient(135deg, #db2777, #9d174d);
    border-radius:16px; padding:18px 24px; color:white;
    margin-bottom:24px; display:flex; align-items:center; gap:20px;
    flex-wrap:wrap; box-shadow: 0 4px 20px rgba(219,39,119,.25);
}
.gy-banner .avatar { width:54px;height:54px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0; }
.gy-banner .info h4 { margin:0 0 6px;font-size:1.15rem;font-weight:800; }
.gy-banner .badges { display:flex;flex-wrap:wrap;gap:8px; }
.gy-badge { background:rgba(255,255,255,.2);border-radius:20px;padding:3px 13px;font-size:.8rem;font-weight:600; }

/* ══ WIZARD ══════════════════════════════════════════════════════════ */
.wz-stepper-bar {
    background: white;
    border-radius: 16px;
    padding: 20px 28px 16px;
    margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    border: 1.5px solid var(--border);
}

/* Steps row */
.wz-steps {
    display: flex;
    align-items: flex-start;
    position: relative;
}
.wz-step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    cursor: pointer;
    position: relative;
}
.wz-step-item::after {
    content: '';
    position: absolute;
    top: 17px;
    left: 50%;
    width: 100%;
    height: 2px;
    background: var(--border);
    transition: background .3s;
    z-index: 0;
}
.wz-step-item:last-child::after { display: none; }
.wz-step-item.done::after { background: #86efac; }

.wz-circle {
    width: 36px; height: 36px;
    border-radius: 50%;
    border: 2px solid var(--border);
    background: white;
    color: var(--gy-muted);
    font-weight: 700; font-size: .88rem;
    display: flex; align-items: center; justify-content: center;
    position: relative; z-index: 1;
    transition: all .25s;
    flex-shrink: 0;
}
.wz-step-item.active .wz-circle {
    background: var(--gy-pink);
    border-color: var(--gy-pink);
    color: white;
    box-shadow: 0 0 0 5px rgba(219,39,119,.15);
}
.wz-step-item.done .wz-circle {
    background: #dcfce7;
    border-color: #16a34a;
    color: #16a34a;
}
.wz-step-label {
    font-size: .7rem;
    font-weight: 700;
    color: var(--gy-muted);
    margin-top: 6px;
    text-align: center;
    line-height: 1.3;
    max-width: 80px;
}
.wz-step-item.active .wz-step-label { color: var(--gy-pink); }
.wz-step-item.done   .wz-step-label { color: #16a34a; }

/* Progress bar */
.wz-progress { height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden; margin-top: 14px; }
.wz-progress-bar { height: 100%; background: linear-gradient(90deg, var(--gy-pink), #f472b6); border-radius:2px; transition: width .4s ease; }

/* ── Section card ── */
.gy-section {
    background:white;border-radius:14px;
    box-shadow:0 2px 10px rgba(0,0,0,.06);
    margin-bottom:16px;overflow:hidden;
    border: 1.5px solid var(--border);
}
.gy-section-head {
    background:var(--gy-pink-lt);padding:12px 20px;
    font-weight:700;font-size:.8rem;text-transform:uppercase;
    letter-spacing:.5px;display:flex;align-items:center;gap:8px;
    border-bottom:2px solid #fbcfe8;color:var(--gy-pink);
}
.gy-section-body { padding:20px; }

/* ── Champs ── */
.form-label { font-weight:600;font-size:.77rem;color:var(--gy-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px; }
.form-control,.form-select { border:1.5px solid var(--border);border-radius:8px;font-size:.9rem;padding:9px 12px;transition:.2s; }
.form-control:focus,.form-select:focus { border-color:var(--gy-pink);box-shadow:0 0 0 3px rgba(219,39,119,.12);outline:none; }

/* Suggestion hint */
.sug-hint { font-size:.69rem;color:#a78bfa;margin-top:3px;display:flex;align-items:center;gap:3px; }
.sug-hint i { font-size:.65rem; }

/* ── Radio pills ── */
.radio-pills { display:flex;flex-wrap:wrap;gap:8px; }
.radio-pill { position:relative; }
.radio-pill input { display:none; }
.radio-pill label {
    display:inline-flex;align-items:center;gap:5px;
    padding:6px 14px;border-radius:20px;
    border:1.5px solid var(--border);background:white;
    color:#475569;font-size:.82rem;font-weight:600;cursor:pointer;transition:.15s;
}
.radio-pill input:checked + label { border-color:var(--gy-pink);background:var(--gy-pink-lt);color:var(--gy-pink); }

/* ── Upload zone ── */
.upload-zone {
    border:2px dashed #fbcfe8;border-radius:12px;
    padding:28px 20px;text-align:center;cursor:pointer;
    background:var(--gy-pink-lt);transition:.2s;position:relative;
}
.upload-zone:hover,.upload-zone.drag-over { border-color:var(--gy-pink);background:#fce7f3; }
.upload-zone input[type=file] { position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%; }
.upload-icon { font-size:2rem;color:#fbcfe8;margin-bottom:8px; }
.upload-text { font-size:.9rem;font-weight:600;color:var(--gy-muted); }
.upload-hint { font-size:.73rem;color:#94a3b8;margin-top:4px; }
.upload-progress { height:4px;border-radius:2px;background:#e2e8f0;margin-top:10px;overflow:hidden;display:none; }
.upload-progress-bar { height:100%;background:var(--gy-pink);transition:width .3s; }

/* ── DICOM drop zone ── */
.dicom-drop-zone {
    border:2px dashed #c4b5fd;border-radius:12px;padding:22px 20px;
    text-align:center;cursor:pointer;background:#faf5ff;transition:.2s;position:relative;
}
.dicom-drop-zone:hover,.dicom-drop-zone.drag-over { border-color:#7c3aed;background:#ede9fe; }
.dicom-drop-zone input[type=file] {
    position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;
}

/* ── Doc list ── */
.doc-list { display:flex;flex-direction:column;gap:10px;margin-top:12px; }
.doc-item { display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:10px;border:1px solid var(--border);background:white;transition:.15s; }
.doc-item:hover { border-color:#fbcfe8;background:var(--gy-pink-lt); }
.doc-thumb { width:44px;height:44px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;overflow:hidden; }
.doc-thumb img { width:100%;height:100%;object-fit:cover; }
.doc-info { flex:1;min-width:0; }
.doc-name { font-weight:700;font-size:.85rem;color:var(--gy-dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.doc-meta { font-size:.73rem;color:var(--gy-muted);margin-top:2px; }
.doc-actions { display:flex;gap:6px; }
.btn-doc { padding:4px 10px;border-radius:6px;border:none;font-size:.75rem;font-weight:600;cursor:pointer; }
.btn-doc-view { background:#dbeafe;color:#1d4ed8; }

/* ── Formulaires liés ── */
.forms-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px; }
.form-card { border:1.5px solid var(--border);border-radius:12px;padding:14px 16px;background:white;transition:.2s; }
.form-card:hover { box-shadow:0 4px 12px rgba(0,0,0,.08);transform:translateY(-1px); }
.form-card-icon { font-size:1.4rem;margin-bottom:6px; }
.form-card-title { font-weight:700;font-size:.82rem;color:var(--gy-dark);margin-bottom:5px;line-height:1.3; }
.form-card-status { font-size:.72rem;margin-bottom:8px; }
.form-card-actions { display:flex;gap:6px; }
.btn-fc { display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border-radius:6px;border:none;font-size:.75rem;font-weight:700;cursor:pointer;text-decoration:none;transition:.15s; }
.btn-fc-new { background:var(--gy-pink);color:white; } .btn-fc-new:hover { background:#be185d;color:white; }
.btn-fc-voir { background:#e0f2fe;color:#0369a1; } .btn-fc-voir:hover { background:#bae6fd;color:#0369a1; }

/* ── Table prescriptions / bilans ── */
.tbl-presc { width:100%;border-collapse:collapse;font-size:.82rem; }
.tbl-presc th { background:#f8fafc;border:1px solid #e2e8f0;padding:8px 10px;font-weight:700;color:var(--gy-muted);text-transform:uppercase;font-size:.72rem; }
.tbl-presc td { border:1px solid #e2e8f0;padding:5px 8px;vertical-align:middle; }
.tbl-presc input,.tbl-presc select,.tbl-presc textarea { width:100%;border:none;outline:none;background:transparent;font-size:.82rem;padding:3px 4px; }
.tbl-presc input:focus,.tbl-presc textarea:focus { background:#f0fdfa;border-radius:3px; }
.btn-add-row { display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:8px;border:2px dashed #fbcfe8;background:var(--gy-pink-lt);color:var(--gy-pink);font-weight:700;font-size:.8rem;cursor:pointer;transition:.2s; }
.btn-add-row:hover { background:#fce7f3; }
.btn-del-row-sm { width:24px;height:24px;border:none;border-radius:5px;background:#fee2e2;color:#dc2626;cursor:pointer;font-size:.8rem; }

/* ── Wizard panels ── */
.wz-panel { display:none; animation: fadeIn .2s ease; }
.wz-panel.active { display:block; }
@keyframes fadeIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }

/* ── Step 5 sub-section toggles ── */
.s5-toggle {
    display:flex;align-items:center;justify-content:space-between;
    background:white;border-radius:12px;border:1.5px solid var(--border);
    padding:14px 20px;cursor:pointer;transition:.2s;margin-bottom:10px;
    font-weight:700;font-size:.9rem;color:var(--gy-dark);
}
.s5-toggle:hover { border-color:var(--gy-pink);background:var(--gy-pink-lt); }
.s5-toggle-body { display:none; }
.s5-toggle-body.open { display:block; }

/* ── Footer wizard navigation ── */
.wz-footer {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: white;
    border-top: 1px solid var(--border);
    padding: 11px 24px;
    display: flex; align-items: center; justify-content: space-between;
    z-index: 400;
    box-shadow: 0 -4px 16px rgba(0,0,0,.07);
}
.btn-wz-prev {
    display:inline-flex;align-items:center;gap:6px;padding:9px 20px;border-radius:8px;
    border:1.5px solid var(--border);background:white;color:#475569;font-weight:600;font-size:.88rem;cursor:pointer;transition:.2s;
}
.btn-wz-prev:hover:not(:disabled) { background:#f8fafc; }
.btn-wz-prev:disabled { opacity:.35;cursor:not-allowed; }
.btn-wz-next {
    display:inline-flex;align-items:center;gap:6px;padding:9px 26px;border-radius:8px;
    border:none;background:var(--gy-pink);color:white;font-weight:700;font-size:.88rem;cursor:pointer;transition:.2s;
}
.btn-wz-next:hover { background:#be185d; }
/* ── Récapitulatif ── */
.rcp-block { margin-bottom:14px; }
.rcp-block:empty { display:none; }
.rcp-titre { font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gy-muted);margin-bottom:4px; }
.rcp-val { background:#f8fafc;border-radius:8px;padding:8px 12px;font-size:.85rem;color:var(--gy-dark);border:1px solid var(--border); }
.rcp-val.empty { color:#94a3b8;font-style:italic; }
.rcp-table { width:100%;border-collapse:collapse;font-size:.8rem;background:white;border-radius:8px;overflow:hidden;border:1px solid var(--border); }
.rcp-table th { background:#f1f5f9;padding:6px 10px;text-align:left;font-weight:700;color:var(--gy-muted);font-size:.7rem;text-transform:uppercase; }
.rcp-table td { padding:6px 10px;border-top:1px solid var(--border); }

/* ── Print ── */
@media print {
    body * { visibility:hidden !important; }
    #gyPrintZone, #gyPrintZone * { visibility:visible !important; }
    #gyPrintZone { position:fixed;top:0;left:0;width:100%;z-index:99999; }
    .wz-footer, .gy-topbar, .wz-stepper-bar { display:none !important; }
}

.btn-wz-submit {
    display:inline-flex;align-items:center;gap:6px;padding:9px 26px;border-radius:8px;
    border:none;background:#059669;color:white;font-weight:700;font-size:.88rem;cursor:pointer;transition:.2s;
}
.btn-wz-submit:hover { background:#047857; }
.wz-counter { font-size:.82rem;color:var(--gy-muted);font-weight:600;text-align:center; }

.gy-wrap { max-width:980px;margin:0 auto;padding:20px 16px 90px; }

@media (max-width:640px) {
    .wz-step-label { display:none; }
    .wz-circle { width:30px;height:30px;font-size:.78rem; }
    .wz-step-item::after { top:15px; }
}

@media print {
    .gy-topbar,.wz-stepper-bar,.wz-footer { display:none !important; }
    body { background:white !important; }
    .wz-panel { display:block !important; }
}
</style>

<!-- ══════════════════════════════════════════════════════════
     DATALISTS — Suggestions de saisie
     ══════════════════════════════════════════════════════════ -->

<datalist id="dl-motif">
    <option value="Suivi de grossesse (CPN)">
    <option value="Douleurs pelviennes">
    <option value="Ménométrorragies">
    <option value="Aménorrhée primaire">
    <option value="Aménorrhée secondaire">
    <option value="Pertes vaginales anormales">
    <option value="Prurit vulvo-vaginal">
    <option value="Test de grossesse positif">
    <option value="Consultation prénuptiale">
    <option value="Consultation post-partum (6 semaines)">
    <option value="Planification familiale / Contraception">
    <option value="Frottis cervico-vaginal (FCV)">
    <option value="Stérilité primaire">
    <option value="Stérilité secondaire">
    <option value="Dysménorrhée">
    <option value="Syndrome prémenstruel">
    <option value="Prolapsus génital">
    <option value="Incontinence urinaire d'effort">
    <option value="Masse pelvienne — bilan">
    <option value="Suivi post-opératoire gynécologique">
    <option value="Dépistage cancer du col (colposcopie)">
    <option value="Ménopause — bilan">
    <option value="Puberté précoce">
    <option value="Saignements en début de grossesse">
    <option value="Contractions utérines prématurées">
</datalist>

<datalist id="dl-plaintes">
    <option value="Douleurs pelviennes basses bilatérales">
    <option value="Saignements en dehors des règles">
    <option value="Règles abondantes et douloureuses">
    <option value="Absence de règles depuis plusieurs mois">
    <option value="Pertes blanches malodorantes">
    <option value="Démangeaisons vulvo-vaginales intenses">
    <option value="Nausées et vomissements du 1er trimestre">
    <option value="Contractions utérines régulières">
    <option value="Perte de liquide amniotique">
    <option value="Diminution des mouvements actifs fœtaux">
    <option value="Brûlures mictionnelles">
    <option value="Dyspareunie (douleur pendant les rapports)">
    <option value="Tension et douleur mammaires">
    <option value="Gonflement abdominal progressif">
    <option value="Bouffées de chaleur, sueurs nocturnes">
    <option value="Saignements post-ménopausiques">
    <option value="Sensation de pesanteur pelvienne">
</datalist>

<datalist id="dl-cycle">
    <option value="Régulier, 28 jours, flux normal, durée 4–5 jours">
    <option value="Régulier, 30 jours, flux modéré">
    <option value="Irrégulier, cycles variables (21–45 jours)">
    <option value="Dysménorrhée sévère incapacitante">
    <option value="Ménorragie (flux abondant > 7 jours, caillots)">
    <option value="Aménorrhée (absence de règles)">
    <option value="Spanioménorrhée (règles rares < 4/an)">
    <option value="Oligoménorrhée (cycles longs > 35 jours)">
    <option value="Polyménorrhée (cycles courts < 21 jours)">
    <option value="Cycles sous contraception orale">
    <option value="Ménopause (dernières règles il y a > 12 mois)">
</datalist>

<datalist id="dl-menarche">
    <option value="9 ans">
    <option value="10 ans">
    <option value="11 ans">
    <option value="12 ans">
    <option value="13 ans">
    <option value="14 ans">
    <option value="15 ans">
    <option value="16 ans">
    <option value="Non précisé">
</datalist>

<datalist id="dl-atcd-med">
    <option value="Hypertension artérielle (HTA)">
    <option value="Diabète gestationnel">
    <option value="Diabète type 2">
    <option value="Anémie falciforme (drépanocytose)">
    <option value="Anémie ferriprive">
    <option value="Asthme bronchique">
    <option value="Épilepsie sous traitement">
    <option value="Cardiopathie rheumatismale">
    <option value="Tuberculose (ancienne, traitée)">
    <option value="Infection VIH sous ARV">
    <option value="Hépatite B chronique">
    <option value="Fibromes utérins (myomatose)">
    <option value="Endométriose">
    <option value="Kyste de l'ovaire">
    <option value="Prééclampsie antérieure">
    <option value="Hypothyroïdie sous lévothyroxine">
    <option value="Syndrome des ovaires polykystiques (SOPK)">
    <option value="Sans antécédents médicaux notables">
</datalist>

<datalist id="dl-atcd-chir">
    <option value="Césarienne (1 fois — cicatrice basse transverse)">
    <option value="Césarienne (2 fois)">
    <option value="Myomectomie par laparotomie">
    <option value="Myomectomie par cœlioscopie">
    <option value="Kystectomie ovarienne">
    <option value="Appendicectomie">
    <option value="Ligature des trompes">
    <option value="Curetage utérin (aspiration)">
    <option value="Conisation du col (LEEP)">
    <option value="Hystérectomie subtotale">
    <option value="Hystérectomie totale">
    <option value="Salpingectomie (GEU)">
    <option value="Laparotomie exploratrice pour GEU">
    <option value="Chirurgie pour prolapsus (promontofixation)">
    <option value="Sans antécédents chirurgicaux">
</datalist>

<datalist id="dl-atcd-fam">
    <option value="Cancer du sein (mère / sœur)">
    <option value="Cancer du col de l'utérus">
    <option value="Cancer de l'ovaire">
    <option value="Cancer de l'endomètre">
    <option value="Hypertension artérielle">
    <option value="Diabète type 2">
    <option value="Gémellité (grossesses multiples)">
    <option value="Maladies héréditaires (préciser)">
    <option value="Pas d'antécédents familiaux connus">
</datalist>

<datalist id="dl-groupe-sang">
    <option value="A+">
    <option value="A−">
    <option value="B+">
    <option value="B−">
    <option value="AB+">
    <option value="AB−">
    <option value="O+">
    <option value="O−">
    <option value="Non déterminé">
</datalist>

<datalist id="dl-ta">
    <option value="90/60">
    <option value="100/60">
    <option value="100/70">
    <option value="110/70">
    <option value="120/80">
    <option value="130/80">
    <option value="130/85">
    <option value="140/90">
    <option value="150/90">
    <option value="150/100">
    <option value="160/100">
    <option value="170/110">
</datalist>

<datalist id="dl-seins">
    <option value="Seins souples, symétriques, sans nodule ni écoulement mamelonnaire">
    <option value="Nodule du sein droit — ferme, mobile, non douloureux, < 2 cm">
    <option value="Nodule du sein gauche — ferme, mobile, peu douloureux">
    <option value="Galactorrhée bilatérale à l'expression">
    <option value="Écoulement mamelonnaire séro-sanglant unilatéral droit">
    <option value="Mastodynie bilatérale prépmenstruelle, sans nodule">
    <option value="Engorgement mammaire bilatéral (allaitement)">
    <option value="Mastite droite : placard chaud, rouge, douloureux">
    <option value="Seins ptosés, asymétrie volumétrique">
    <option value="Cicatrice de mastectomie gauche, plan de glissement libre">
    <option value="Gynécomastie (si patiente transgender)">
</datalist>

<datalist id="dl-speculum">
    <option value="Col sain, sécrétions claires physiologiques, pas de lésion visible">
    <option value="Cervicite avec sécrétions purulentes et col érythémateux">
    <option value="Ectropion cervical étendu">
    <option value="Leucorrhées blanches grumeleuses (candidose)">
    <option value="Pertes grisâtres malodorantes, homogènes (vaginose bactérienne)">
    <option value="Col violacé, signe de Jacquemier (grossesse)">
    <option value="Saignement au contact du col (lésion suspecte — FCV indiqué)">
    <option value="Polype cervical visible, pédiculé">
    <option value="Col effacé, dilaté, présentation visible à l'orifice">
    <option value="Cicatrice de conisation, muqueuse irrégulière">
</datalist>

<datalist id="dl-tv">
    <option value="Utérus de taille normale, antéversé, mobile, non sensible. Culs-de-sac libres. Douglas libre.">
    <option value="Utérus augmenté de volume (fibrome), régulier, mobile, non sensible.">
    <option value="Utérus gravide, taille concordante avec l'âge gestationnel. Segment inférieur bien formé.">
    <option value="Utérus rétroversé fixé, légèrement sensible. Nodules des ligaments utéro-sacrés (endométriose ?).">
    <option value="Douleur à la mobilisation utérine. Cul-de-sac de Douglas douloureux (pelvipéritonite ?).">
    <option value="Col long fermé postérieur, utérus tonique — pas de modification cervicale.">
    <option value="Col effacé à 50%, dilaté à 3 cm, poche des eaux intacte, présentation céphalique.">
    <option value="Col effacé, dilaté à 6 cm, poche des eaux bombante.">
    <option value="Dilatation complète, présentation bien appliquée au détroit supérieur.">
    <option value="Utérus augmenté, irrégulier (myomes multiples), mobile, indolore.">
</datalist>

<datalist id="dl-annexes">
    <option value="Annexes libres et indolores bilatéralement">
    <option value="Masse latéro-utérine droite, régulière, peu douloureuse, ~5 cm">
    <option value="Masse latéro-utérine gauche, régulière, mobile, ~4 cm">
    <option value="Empâtement latéro-utérin bilatéral douloureux (salpingite ?)">
    <option value="Trompes non palpables, ovaires non individualisés">
    <option value="Sensibilité annexielle droite à la palpation, sans masse palpable">
    <option value="Absence d'annexes (antécédent de salpingectomie bilatérale)">
</datalist>

<datalist id="dl-diagnostic">
    <option value="Grossesse intra-utérine évolutive de ___ SA">
    <option value="Menace d'accouchement prématuré (MAP) à ___ SA">
    <option value="Prééclampsie modérée">
    <option value="Prééclampsie sévère">
    <option value="Diabète gestationnel">
    <option value="Anémie ferriprive de la grossesse">
    <option value="Grossesse extra-utérine (GEU) — à confirmer par écho">
    <option value="Fausse couche spontanée en cours">
    <option value="Fausse couche spontanée complète">
    <option value="Fibromes utérins (myomatose)">
    <option value="Kyste de l'ovaire — surveillance/chirurgie">
    <option value="Endométriose pelvienne">
    <option value="Candidose vulvo-vaginale">
    <option value="Vaginose bactérienne">
    <option value="Cervicite à Neisseria gonorrhoeae">
    <option value="Infection génitale haute (salpingite)">
    <option value="Torsion d'annexe — urgence chirurgicale">
    <option value="Syndrome des ovaires polykystiques (SOPK)">
    <option value="Stérilité — bilan en cours">
    <option value="Ménopause confirmée">
    <option value="Cancer du col utérin — stadification en cours">
    <option value="Prolapsus génitourinaire de stade ___">
    <option value="Incontinence urinaire d'effort">
</datalist>

<datalist id="dl-conduite">
    <option value="Surveillance ambulatoire, consultation de contrôle dans 4 semaines">
    <option value="Surveillance ambulatoire, échographie de contrôle dans 15 jours">
    <option value="Hospitalisation pour surveillance et traitement IV">
    <option value="Traitement médical ambulatoire, réévaluation dans 7 jours">
    <option value="Orientation chirurgie gynécologique pour programmation">
    <option value="Déclenchement du travail — hospitalisation en maternité">
    <option value="Césarienne programmée à terme (39 SA)">
    <option value="Bilan étiologique complémentaire avant décision thérapeutique">
    <option value="Traitement antibiotique ambulatoire + contrôle à J7">
    <option value="FCV + colposcopie en consultation dédiée">
    <option value="Consultation planification familiale — pose DIU">
    <option value="Référence au service d'oncologie gynécologique">
</datalist>

<datalist id="dl-examen-labo">
    <option value="NFS + plaquettes">
    <option value="Glycémie à jeun">
    <option value="HGPO 75g (test O'Sullivan)">
    <option value="β-hCG qualitatif (test grossesse)">
    <option value="β-hCG quantitatif">
    <option value="Groupage sanguin ABO + Rhésus">
    <option value="RAI (Recherche Agglutinines Irrégulières)">
    <option value="Sérologie VIH (ELISA + Western Blot)">
    <option value="AgHBs (Hépatite B)">
    <option value="TPHA/VDRL (Syphilis)">
    <option value="Sérologie Toxoplasmose IgG/IgM">
    <option value="Sérologie Rubéole IgG/IgM">
    <option value="Protéinurie sur bandelette / 24h">
    <option value="ECBU (examen cytobactériologique des urines)">
    <option value="Prélèvement vaginal + antibiogramme">
    <option value="TSH ultrasensible">
    <option value="Ferritinémie / Fer sérique / Coefficient de saturation">
    <option value="FSH / LH / Estradiol (E2)">
    <option value="Progestérone sérique (J21)">
    <option value="Prolactinémie">
    <option value="Testostérone totale">
    <option value="CA 125 (marqueur ovarien)">
    <option value="Frottis cervico-vaginal (FCV)">
    <option value="Bilan de coagulation (TP/TCA/Fibrinogène)">
    <option value="Albuminémie / Protéines totales">
    <option value="Créatinémie / Urée">
    <option value="Bilan hépatique (ASAT/ALAT/Bili)">
</datalist>

<datalist id="dl-medoc">
    <option value="Acide folique 5 mg">
    <option value="Sulfate ferreux 200 mg">
    <option value="Fer + acide folique (Gynofar)">
    <option value="Calcium 1 g">
    <option value="Vitamine D3 1 000 UI">
    <option value="Amoxicilline 500 mg">
    <option value="Amoxicilline-Clavulanate 875/125 mg">
    <option value="Métronidazole 500 mg">
    <option value="Fluconazole 150 mg">
    <option value="Clotrimazole ovule 500 mg">
    <option value="Doxycycline 100 mg">
    <option value="Ceftriaxone 1 g (IM/IV)">
    <option value="Gentamicine 80 mg (IM)">
    <option value="Nifédipine LP 20 mg">
    <option value="Labétalol 100 mg">
    <option value="Méthyldopa 250 mg">
    <option value="Oxytocine 5 UI (IV)">
    <option value="Misoprostol 200 µg">
    <option value="Progestérone naturelle micronisée 200 mg">
    <option value="Utrogestan 200 mg (voie vaginale)">
    <option value="Ibuprofène 400 mg">
    <option value="Paracétamol 1 g">
    <option value="Aspirine faible dose 100 mg">
    <option value="Dexaméthasone 6 mg (IM)">
    <option value="Salbutamol 4 mg">
    <option value="Mébendazole 500 mg">
</datalist>

<datalist id="dl-dosage">
    <option value="100 mg">
    <option value="200 mg">
    <option value="250 mg">
    <option value="400 mg">
    <option value="500 mg">
    <option value="875 mg">
    <option value="1 g">
    <option value="6 mg">
    <option value="80 mg">
    <option value="5 UI">
    <option value="200 µg">
    <option value="1 000 UI">
</datalist>

<datalist id="dl-frequence">
    <option value="1 fois par jour (matin)">
    <option value="2 fois par jour (matin et soir)">
    <option value="3 fois par jour (après les repas)">
    <option value="4 fois par jour">
    <option value="En dose unique">
    <option value="1 fois par semaine">
    <option value="1 fois par mois">
    <option value="Au coucher">
    <option value="Selon douleur (max 3×/j, espacement 6h)">
    <option value="En perfusion continue">
</datalist>

<datalist id="dl-duree">
    <option value="5 jours">
    <option value="7 jours">
    <option value="10 jours">
    <option value="14 jours">
    <option value="21 jours">
    <option value="1 mois">
    <option value="3 mois">
    <option value="6 mois">
    <option value="Jusqu'à l'accouchement">
    <option value="Jusqu'à fin de grossesse">
    <option value="À poursuivre indéfiniment">
</datalist>

<!-- ══════════════════════════════════════════════════════════
     TOPBAR
     ══════════════════════════════════════════════════════════ -->
<div class="gy-topbar">
    <?php if ($from_suivi): ?>
    <a href="<?= BASE_URL ?>hospitalisation/suivi/<?= (int)$patient['id'] ?>" class="btn-gy-back">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
    <?php else: ?>
    <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$patient['id'] ?>" class="btn-gy-back">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
    <?php endif; ?>
    <div class="topbar-title">
        <i class="bi bi-heart-pulse-fill"></i>
        Consultation Gynécologique
    </div>
    <div class="spacer"></div>
    <button type="button" class="btn-gy-print" onclick="window.print()">
        <i class="bi bi-printer"></i> Imprimer
    </button>
    <button type="button" class="btn-gy-draft" onclick="sauvegarderBrouillon()">
        <i class="bi bi-save"></i> Brouillon
    </button>
</div>

<div class="gy-wrap">

<!-- BANNIÈRE PATIENT -->
<div class="gy-banner">
    <div class="avatar"><i class="bi bi-person-fill"></i></div>
    <div class="info">
        <h4><?= htmlspecialchars(($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?></h4>
        <div class="badges">
            <span class="gy-badge"><i class="bi bi-folder2"></i> <?= htmlspecialchars($patient['dossier_numero'] ?? '—') ?></span>
            <span class="gy-badge"><i class="bi bi-calendar3"></i> <?= $age ?> ans</span>
            <?php if (!empty($patient['date_naissance'])): ?>
            <span class="gy-badge"><i class="bi bi-cake"></i> <?= date('d/m/Y', strtotime($patient['date_naissance'])) ?></span>
            <?php endif; ?>
            <span class="gy-badge"><i class="bi bi-heart-pulse-fill"></i> Gynécologie-Obstétrique</span>
            <span class="gy-badge"><i class="bi bi-calendar-check"></i> <?= date('d/m/Y') ?></span>
        </div>
    </div>
</div>

<!-- ══ STEPPER ══════════════════════════════════════════════ -->
<div class="wz-stepper-bar">
    <div class="wz-steps" id="wzSteps">
        <div class="wz-step-item active" data-step="1" onclick="goStep(1)">
            <div class="wz-circle" id="wz-circle-1">1</div>
            <div class="wz-step-label">Motif &amp;<br>Anamnèse</div>
        </div>
        <div class="wz-step-item" data-step="2" onclick="goStep(2)">
            <div class="wz-circle" id="wz-circle-2">2</div>
            <div class="wz-step-label">Examen<br>Clinique</div>
        </div>
        <div class="wz-step-item" data-step="3" onclick="goStep(3)">
            <div class="wz-circle" id="wz-circle-3">3</div>
            <div class="wz-step-label">Bilans<br>Paracliniques</div>
        </div>
        <div class="wz-step-item" data-step="4" onclick="goStep(4)">
            <div class="wz-circle" id="wz-circle-4">4</div>
            <div class="wz-step-label">Traitement &amp;<br>Ordonnance</div>
        </div>
        <div class="wz-step-item" data-step="5" onclick="goStep(5)">
            <div class="wz-circle" id="wz-circle-5">5</div>
            <div class="wz-step-label">Diagnostic &amp;<br>Synthèse</div>
        </div>
        <div class="wz-step-item" data-step="6" onclick="goStep(6)">
            <div class="wz-circle" id="wz-circle-6">6</div>
            <div class="wz-step-label">Récap &amp;<br>Signature</div>
        </div>
    </div>
    <div class="wz-progress">
        <div class="wz-progress-bar" id="wzProgressBar" style="width:10%"></div>
    </div>
</div>

<!-- ══ FORM (steps 1–6) ═════════════════════════════════════ -->
<form id="formGyneco" method="POST" action="#" class="consultation-form">
<input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
<?php if ($hosp_id): ?>
<input type="hidden" name="hosp_id" value="<?= (int)$hosp_id ?>">
<?php endif; ?>
<!-- Champs signature (remplis au step 6) -->
<input type="hidden" name="signature_medecin"    id="fldSignatureMedecin">
<input type="hidden" name="cachet_medecin"        id="fldCachetMedecin">
<input type="hidden" name="date_signature"        id="fldDateSignature">
<input type="hidden" name="labo_data"  id="fldLaboData">
<input type="hidden" name="imag_data"  id="fldImagData">

<!-- ══════════════════════════════════════════════════════════
     ÉTAPE 1 — MOTIF & ANAMNÈSE
     ══════════════════════════════════════════════════════════ -->
<div class="wz-panel active" id="panel-step1">

    <!-- Motif -->
    <div class="gy-section">
        <div class="gy-section-head"><i class="bi bi-chat-text"></i> Motif de consultation</div>
        <div class="gy-section-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Motif principal</label>
                    <input type="text" name="motif" class="form-control" list="dl-motif"
                           value="<?= htmlspecialchars($formData['motif'] ?? '') ?>"
                           placeholder="Saisir ou choisir un motif…">
                    <div class="sug-hint"><i class="bi bi-lightbulb"></i> Suggestions disponibles — commencez à taper</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date de consultation</label>
                    <input type="date" name="date_consult" class="form-control"
                           value="<?= htmlspecialchars($formData['date_consult'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Plaintes et symptômes détaillés</label>
                    <textarea name="plaintes" class="form-control" rows="2"
                              list="dl-plaintes"
                              placeholder="Description des plaintes…"><?= htmlspecialchars($formData['plaintes'] ?? '') ?></textarea>
                    <div class="sug-hint"><i class="bi bi-lightbulb"></i> Suggestions courantes disponibles</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Antécédents gynéco-obstétricaux -->
    <div class="gy-section">
        <div class="gy-section-head"><i class="bi bi-journal-medical"></i> Antécédents gynéco-obstétricaux</div>
        <div class="gy-section-body">
            <div class="row g-3">
                <div class="col-md-2 col-4">
                    <label class="form-label">G (Gestité)</label>
                    <input type="number" name="gestite" class="form-control" min="0" max="20"
                           value="<?= htmlspecialchars($formData['gestite'] ?? '') ?>" placeholder="0">
                </div>
                <div class="col-md-2 col-4">
                    <label class="form-label">P (Parité)</label>
                    <input type="number" name="parite" class="form-control" min="0" max="20"
                           value="<?= htmlspecialchars($formData['parite'] ?? '') ?>" placeholder="0">
                </div>
                <div class="col-md-2 col-4">
                    <label class="form-label">A (Avortements)</label>
                    <input type="number" name="avortements" class="form-control" min="0" max="20"
                           value="<?= htmlspecialchars($formData['avortements'] ?? '') ?>" placeholder="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">DDR (Dernières règles)</label>
                    <input type="date" name="ddr" class="form-control"
                           value="<?= htmlspecialchars($formData['ddr'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Âge de grossesse (SA)</label>
                    <input type="text" name="age_grossesse_sa" class="form-control"
                           value="<?= htmlspecialchars($formData['age_grossesse_sa'] ?? '') ?>"
                           placeholder="ex: 28 SA" id="saField">
                    <div class="sug-hint" id="saAutoHint" style="display:none;color:#059669;">
                        <i class="bi bi-calculator"></i> <span id="saAutoText"></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contraception actuelle</label>
                    <select name="contraception" class="form-select">
                        <option value="">— Aucune / Non précisé —</option>
                        <?php foreach (['Pilule','DIU (stérilet)','Implant','Préservatif','Injection trimestrielle','Patch contraceptif','Ligature des trompes','Abstinence','Allaitement','Autre'] as $c): ?>
                        <option value="<?= $c ?>" <?= ($formData['contraception'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cycle menstruel</label>
                    <input type="text" name="cycle_menstruel" class="form-control" list="dl-cycle"
                           value="<?= htmlspecialchars($formData['cycle_menstruel'] ?? '') ?>"
                           placeholder="ex: régulier, 28j, flux normal">
                    <div class="sug-hint"><i class="bi bi-lightbulb"></i> Suggestions</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ménarche (âge des 1res règles)</label>
                    <input type="text" name="menarche" class="form-control" list="dl-menarche"
                           value="<?= htmlspecialchars($formData['menarche'] ?? '') ?>"
                           placeholder="ex: 13 ans">
                </div>

                <div class="col-md-6">
                    <label class="form-label">ATCD médicaux</label>
                    <textarea name="atcd_medicaux" class="form-control" rows="2"
                              list="dl-atcd-med"
                              placeholder="Pathologies connues, traitements chroniques…"><?= htmlspecialchars($formData['atcd_medicaux'] ?? '') ?></textarea>
                    <div class="sug-hint"><i class="bi bi-lightbulb"></i> Suggestions fréquentes</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ATCD chirurgicaux / obstétricaux</label>
                    <textarea name="atcd_chirurgicaux" class="form-control" rows="2"
                              list="dl-atcd-chir"
                              placeholder="Opérations, accouchements, césariennes…"><?= htmlspecialchars($formData['atcd_chirurgicaux'] ?? '') ?></textarea>
                    <div class="sug-hint"><i class="bi bi-lightbulb"></i> Suggestions fréquentes</div>
                </div>
                <div class="col-12">
                    <label class="form-label">ATCD familiaux pertinents</label>
                    <input type="text" name="atcd_familiaux" class="form-control" list="dl-atcd-fam"
                           value="<?= htmlspecialchars($formData['atcd_familiaux'] ?? '') ?>"
                           placeholder="Cancer, maladies héréditaires…">
                    <div class="sug-hint"><i class="bi bi-lightbulb"></i> Suggestions</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sérologies & Biologie de base -->
    <div class="gy-section">
        <div class="gy-section-head"><i class="bi bi-droplet-half"></i> Sérologies &amp; Biologie de base connues</div>
        <div class="gy-section-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Groupage sanguin</label>
                    <input type="text" name="groupe_sanguin" class="form-control" list="dl-groupe-sang"
                           value="<?= htmlspecialchars($formData['groupe_sanguin'] ?? '') ?>"
                           placeholder="ex: A+">
                </div>
                <div class="col-md-3">
                    <label class="form-label">VIH / LAV</label>
                    <div class="radio-pills mt-1">
                        <?php foreach (['Positif','Négatif','Non fait','ND'] as $v): ?>
                        <div class="radio-pill">
                            <input type="radio" id="lav_<?= $v ?>" name="lav" value="<?= $v ?>"
                                   <?= ($formData['lav'] ?? '') === $v ? 'checked' : '' ?>>
                            <label for="lav_<?= $v ?>"><?= $v ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">AgHBs (Hépatite B)</label>
                    <div class="radio-pills mt-1">
                        <?php foreach (['Positif','Négatif','Non fait'] as $v): ?>
                        <div class="radio-pill">
                            <input type="radio" id="aghbs_<?= $v ?>" name="aghbs" value="<?= $v ?>"
                                   <?= ($formData['aghbs'] ?? '') === $v ? 'checked' : '' ?>>
                            <label for="aghbs_<?= $v ?>"><?= $v ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Syphilis (TPHA/VDRL)</label>
                    <div class="radio-pills mt-1">
                        <?php foreach (['Positif','Négatif','Non fait'] as $v): ?>
                        <div class="radio-pill">
                            <input type="radio" id="syphilis_<?= $v ?>" name="syphilis" value="<?= $v ?>"
                                   <?= ($formData['syphilis'] ?? '') === $v ? 'checked' : '' ?>>
                            <label for="syphilis_<?= $v ?>"><?= $v ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /panel-step1 -->


<!-- ══════════════════════════════════════════════════════════
     ÉTAPE 2 — EXAMEN CLINIQUE
     ══════════════════════════════════════════════════════════ -->
<div class="wz-panel" id="panel-step2">

    <div class="gy-section">
        <div class="gy-section-head"><i class="bi bi-activity"></i> Constantes vitales</div>
        <div class="gy-section-body">
            <div class="row g-3">
                <div class="col-md-2 col-6">
                    <label class="form-label">TA (mmHg)</label>
                    <input type="text" name="ta" class="form-control" list="dl-ta"
                           value="<?= htmlspecialchars($formData['ta'] ?? '') ?>" placeholder="120/80">
                    <div class="sug-hint" id="taBadge" style="min-height:18px;"></div>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">Pouls (bpm)</label>
                    <input type="number" name="pouls" class="form-control"
                           value="<?= htmlspecialchars($formData['pouls'] ?? '') ?>" placeholder="72">
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">T° (°C)</label>
                    <input type="number" name="temperature" class="form-control" step="0.1"
                           value="<?= htmlspecialchars($formData['temperature'] ?? '') ?>"
                           placeholder="37.0" oninput="checkTempGy(this.value)">
                    <div id="tempBadgeGy" style="min-height:18px;margin-top:3px;font-size:.7rem;"></div>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">Poids (kg)</label>
                    <input type="number" name="poids" class="form-control" step="0.1"
                           value="<?= htmlspecialchars($formData['poids'] ?? '') ?>" placeholder="65">
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">Taille (cm)</label>
                    <input type="number" name="taille" class="form-control"
                           value="<?= htmlspecialchars($formData['taille'] ?? '') ?>" placeholder="165">
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">IMC</label>
                    <input type="text" name="imc" class="form-control" id="imcField"
                           value="<?= htmlspecialchars($formData['imc'] ?? '') ?>" placeholder="Auto" readonly>
                    <div id="imcBadge" style="min-height:18px;margin-top:3px;font-size:.7rem;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="gy-section">
        <div class="gy-section-head"><i class="bi bi-stethoscope"></i> Examen général</div>
        <div class="gy-section-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">État général</label>
                    <select name="etat_general" class="form-select">
                        <option value="">—</option>
                        <?php foreach (['Bon','Satisfaisant','Altéré','Critique'] as $eg): ?>
                        <option <?= ($formData['etat_general'] ?? '') === $eg ? 'selected' : '' ?>><?= $eg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">État des conjonctives</label>
                    <select name="conjonctives" class="form-select">
                        <option value="">—</option>
                        <?php foreach (['Colorées','Subictériques','Ictériques','Pâles','Très pâles'] as $c): ?>
                        <option <?= ($formData['conjonctives'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Œdèmes</label>
                    <div class="radio-pills mt-1">
                        <?php foreach (['Absents','Membres inf.','Généralisés'] as $v): ?>
                        <div class="radio-pill">
                            <input type="radio" id="oedemes_<?= md5($v) ?>" name="oedemes" value="<?= $v ?>"
                                   <?= ($formData['oedemes'] ?? '') === $v ? 'checked' : '' ?>>
                            <label for="oedemes_<?= md5($v) ?>"><?= $v ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="gy-section">
        <div class="gy-section-head"><i class="bi bi-person-heart"></i> Examen gynécologique</div>
        <div class="gy-section-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Examen des seins</label>
                    <textarea name="seins" class="form-control" rows="2"
                              list="dl-seins"
                              placeholder="Aspect, nodule, écoulement mamelonnaire…"><?= htmlspecialchars($formData['seins'] ?? '') ?></textarea>
                    <div class="sug-hint"><i class="bi bi-lightbulb"></i> Formulations types disponibles</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Examen au spéculum</label>
                    <textarea name="speculum" class="form-control" rows="2"
                              list="dl-speculum"
                              placeholder="Aspect du col, sécrétions, lésions…"><?= htmlspecialchars($formData['speculum'] ?? '') ?></textarea>
                    <div class="sug-hint"><i class="bi bi-lightbulb"></i> Formulations types disponibles</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Col utérin</label>
                    <select name="col_uterus" class="form-select">
                        <option value="">—</option>
                        <?php foreach (['Long, fermé, postérieur','Court, fermé','Entrouverte','Dilaté 2 cm','Dilaté 4 cm','Dilaté 6 cm','Effacé','Dilatation complète'] as $c): ?>
                        <option <?= ($formData['col_uterus'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Toucher vaginal (TV)</label>
                    <textarea name="tv" class="form-control" rows="3"
                              list="dl-tv"
                              placeholder="Utérus, culs-de-sac, Douglas…"><?= htmlspecialchars($formData['tv'] ?? '') ?></textarea>
                    <div class="sug-hint"><i class="bi bi-lightbulb"></i> Formulations types</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Annexes</label>
                    <textarea name="annexes" class="form-control" rows="3"
                              list="dl-annexes"
                              placeholder="Masse, sensibilité, empâtement…"><?= htmlspecialchars($formData['annexes'] ?? '') ?></textarea>
                    <div class="sug-hint"><i class="bi bi-lightbulb"></i> Formulations types</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Surveillance obstétricale (grossesse) -->
    <div class="gy-section">
        <div class="gy-section-head"><i class="bi bi-heart-pulse"></i> Surveillance obstétricale <small style="font-weight:400;text-transform:none;opacity:.7;">(si grossesse)</small></div>
        <div class="gy-section-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">BDCF (bpm)</label>
                    <input type="text" name="bdcf" class="form-control"
                           value="<?= htmlspecialchars($formData['bdcf'] ?? '') ?>"
                           placeholder="ex: 144" oninput="checkBDCF(this.value)">
                    <div id="bdcfBadge" style="min-height:18px;margin-top:3px;font-size:.7rem;"></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">HU (Hauteur utérine)</label>
                    <input type="text" name="hu" class="form-control"
                           value="<?= htmlspecialchars($formData['hu'] ?? '') ?>" placeholder="ex: 26 cm">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Présentation</label>
                    <select name="presentation" class="form-select">
                        <option value="">—</option>
                        <?php foreach (['Céphalique','Siège complet','Siège décomplété','Transverse','Non précisé'] as $p): ?>
                        <option <?= ($formData['presentation'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Liquide amniotique</label>
                    <select name="liquide_amniotique" class="form-select">
                        <option value="">—</option>
                        <?php foreach (['Clair','Teinté (grade I)','Méconial (grade II)','Méconial épais (grade III)','Absent','Non évalué'] as $la): ?>
                        <option <?= ($formData['liquide_amniotique'] ?? '') === $la ? 'selected' : '' ?>><?= $la ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contractions utérines</label>
                    <div class="radio-pills">
                        <?php foreach (['Absentes','Braxton-Hicks','CU régulières (travail)','CU hyper-toniques'] as $v): ?>
                        <div class="radio-pill">
                            <input type="radio" id="cu_<?= md5($v) ?>" name="contractions" value="<?= $v ?>"
                                   <?= ($formData['contractions'] ?? '') === $v ? 'checked' : '' ?>>
                            <label for="cu_<?= md5($v) ?>"><?= $v ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Poche des eaux</label>
                    <div class="radio-pills">
                        <?php foreach (['Intacte','Rompue','Non évaluée'] as $v): ?>
                        <div class="radio-pill">
                            <input type="radio" id="pde_<?= md5($v) ?>" name="poche_eaux" value="<?= $v ?>"
                                   <?= ($formData['poche_eaux'] ?? '') === $v ? 'checked' : '' ?>>
                            <label for="pde_<?= md5($v) ?>"><?= $v ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mouvements actifs fœtaux</label>
                    <div class="radio-pills">
                        <?php foreach (['Présents','Diminués','Absents'] as $v): ?>
                        <div class="radio-pill">
                            <input type="radio" id="maf_<?= md5($v) ?>" name="maf" value="<?= $v ?>"
                                   <?= ($formData['maf'] ?? '') === $v ? 'checked' : '' ?>>
                            <label for="maf_<?= md5($v) ?>"><?= $v ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /panel-step2 -->


<!-- ══════════════════════════════════════════════════════════
     ÉTAPE 3 — BILANS PARACLINIQUES
     ══════════════════════════════════════════════════════════ -->
<div class="wz-panel" id="panel-step3">

    <!-- ── 1. EXAMENS DE LABORATOIRE ─────────────────────────── -->
    <div class="gy-section">
        <div class="gy-section-head" style="background:linear-gradient(90deg,#eff6ff,#dbeafe);">
            <i class="bi bi-flask" style="color:#1d4ed8;"></i>
            <span style="color:#1e40af;">Examens de Laboratoire</span>
        </div>
        <div class="gy-section-body">
            <div class="mb-3">
                <label class="form-label small text-muted">Notes générales sur les bilans</label>
                <textarea name="notes_bilans" class="form-control" rows="2"
                          style="border-radius:10px;"
                          placeholder="Priorités, contexte, instructions globales…"><?= htmlspecialchars($formData['notes_bilans'] ?? '') ?></textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <span class="fw-semibold small text-muted">Liste des examens</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary btn-sm rounded-pill"
                            data-bs-toggle="modal" data-bs-target="#gyModalDemandeExamen">
                        <i class="bi bi-plus-lg me-1"></i> Ajouter un examen
                    </button>
                    <button type="button" id="gyBtnEnvoyerLabo"
                            onclick="gyEnvoyerAuLaboratoire()"
                            class="btn btn-success btn-sm rounded-pill fw-bold"
                            style="display:none;">
                        <i class="bi bi-send-fill me-1"></i> Envoyer au Laboratoire
                    </button>
                </div>
            </div>

            <div id="gyAlertesMasseLabo"></div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 bg-white" id="gyTableExamens" style="border-radius:10px;overflow:hidden;">
                    <thead style="background:#eff6ff;color:#1e40af;">
                        <tr>
                            <th>Examen</th>
                            <th>Catégorie</th>
                            <th>Prélèvement</th>
                            <th>Délai</th>
                            <th>Urgence</th>
                            <th class="text-center">×</th>
                        </tr>
                    </thead>
                    <tbody id="gyListeExamens"></tbody>
                </table>
                <div id="gyEmptyLabo" class="text-center text-muted py-4">
                    <i class="bi bi-flask fs-3 d-block mb-1" style="color:#bfdbfe;"></i>
                    <small>Aucun examen labo ajouté.<br>Cliquez sur <strong>Ajouter un examen</strong> pour prescrire.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 2. EXAMENS D'IMAGERIE ──────────────────────────────── -->
    <div class="gy-section">
        <div class="gy-section-head" style="background:linear-gradient(90deg,#f0fdf4,#dcfce7);">
            <i class="bi bi-activity" style="color:#15803d;"></i>
            <span style="color:#166534;">Examens d'Imagerie / Radiologie</span>
        </div>
        <div class="gy-section-body">

            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <span class="fw-semibold small text-muted">Demandes d'imagerie</span>
                <div class="d-flex gap-2 flex-wrap">
                    <!-- Bouton "Réaliser l'échographie moi-même" — spécifique gynéco -->
                    <button type="button" id="gyBtnEchoSoi"
                            onclick="gyToggleEchoSoi()"
                            class="btn btn-sm rounded-pill fw-semibold"
                            style="background:#fdf4ff;color:#7c3aed;border:1.5px solid #e9d5ff;">
                        <i class="bi bi-soundwave me-1"></i> Réaliser l'écho moi-même
                    </button>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill"
                            data-bs-toggle="modal" data-bs-target="#gyModalDemandeImagerie">
                        <i class="bi bi-plus-lg me-1"></i> Demande en radiologie
                    </button>
                    <button type="button" id="gyBtnEnvoyerRadio"
                            onclick="gyEnvoyerEnRadiologie()"
                            class="btn btn-danger btn-sm rounded-pill fw-bold"
                            style="display:none;">
                        <i class="bi bi-send-fill me-1"></i> Envoyer en Radiologie
                    </button>
                </div>
            </div>

            <div id="gyAlertesImagerie"></div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 bg-white" id="gyTableImagerie" style="border-radius:10px;overflow:hidden;">
                    <thead style="background:#f0fdf4;color:#166534;">
                        <tr>
                            <th>Modalité</th>
                            <th>Partie / Indication</th>
                            <th>Urgence</th>
                            <th>Instructions</th>
                            <th class="text-center">×</th>
                        </tr>
                    </thead>
                    <tbody id="gyListeImagerie"></tbody>
                </table>
                <div id="gyEmptyImagerie" class="text-center text-muted py-4">
                    <i class="bi bi-image fs-3 d-block mb-1" style="color:#bbf7d0;"></i>
                    <small>Aucune demande d'imagerie.<br>Cliquez sur <strong>Demande en radiologie</strong> pour prescrire.</small>
                </div>
            </div>

            <!-- ── ZONE ÉCHOGRAPHIE RÉALISÉE PAR LE GYNÉCOLOGUE ── -->
            <div id="gyEchoSoiPanel" style="display:none; margin-top:18px;">
                <div class="p-3 rounded-3 border"
                     style="background:linear-gradient(135deg,#fdf4ff,#f5f3ff);border-color:#e9d5ff !important;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="rounded-circle p-2" style="background:#e9d5ff;">
                            <i class="bi bi-soundwave" style="color:#7c3aed;font-size:1rem;"></i>
                        </span>
                        <h6 class="mb-0 fw-bold" style="color:#5b21b6;">
                            Compte-rendu d'échographie — Réalisée en salle par le gynécologue
                        </h6>
                        <button type="button" onclick="gyToggleEchoSoi()"
                                class="btn-close ms-auto" style="font-size:.75rem;" title="Fermer"></button>
                    </div>

                    <div class="row g-3">
                        <!-- Type d'échographie -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary">TYPE D'ÉCHOGRAPHIE</label>
                            <select name="echo_type" id="gyEchoType" class="form-select" style="border-radius:10px;"
                                    onchange="gyEchoTypeChange()">
                                <option value="">— Sélectionner —</option>
                                <option value="Pelvienne">Pelvienne</option>
                                <option value="Obstétricale T1">Obstétricale T1 (&lt; 14 SA)</option>
                                <option value="Obstétricale T2">Obstétricale T2 (14–28 SA)</option>
                                <option value="Obstétricale T3">Obstétricale T3 (&gt; 28 SA)</option>
                                <option value="Écho doppler obstétrical">Écho doppler obstétrical</option>
                                <option value="Écho col">Écho col utérin (longueur cervicale)</option>
                                <option value="Mammaire">Mammaire</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>

                        <!-- Appareil -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary">APPAREIL UTILISÉ</label>
                            <input type="text" name="echo_appareil" class="form-control" style="border-radius:10px;"
                                   placeholder="Marque, sonde…">
                        </div>

                        <!-- Date / Heure -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary">DATE DE L'EXAMEN</label>
                            <input type="date" name="echo_date" class="form-control" style="border-radius:10px;"
                                   value="<?= date('Y-m-d') ?>">
                        </div>

                        <!-- Compte-rendu textuel -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary">COMPTE-RENDU</label>
                            <textarea name="echo_cr" class="form-control" rows="3" style="border-radius:10px;"
                                      placeholder="Description des images observées, structure examinée, aspect général…"></textarea>
                        </div>

                        <!-- ── Biométrie fœtale (obstétrical uniquement) ── -->
                        <div class="col-12" id="gyEchoBiometrie" style="display:none;">
                            <div class="p-3 rounded-3" style="background:#fff;border:1.5px dashed #c4b5fd;">
                                <div class="fw-bold small mb-2" style="color:#5b21b6;">
                                    <i class="bi bi-rulers me-1"></i> Biométrie fœtale
                                </div>
                                <div class="row g-2">
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" style="font-size:.72rem;color:#64748b;">BIP (mm)</label>
                                        <input type="number" step="0.1" name="echo_bip" class="form-control form-control-sm"
                                               style="border-radius:8px;" placeholder="—">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" style="font-size:.72rem;color:#64748b;">LF (mm)</label>
                                        <input type="number" step="0.1" name="echo_lf" class="form-control form-control-sm"
                                               style="border-radius:8px;" placeholder="—">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" style="font-size:.72rem;color:#64748b;">PC (mm)</label>
                                        <input type="number" step="0.1" name="echo_pc" class="form-control form-control-sm"
                                               style="border-radius:8px;" placeholder="—">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" style="font-size:.72rem;color:#64748b;">PA (mm)</label>
                                        <input type="number" step="0.1" name="echo_pa" class="form-control form-control-sm"
                                               style="border-radius:8px;" placeholder="—">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" style="font-size:.72rem;color:#64748b;">LCC (mm)</label>
                                        <input type="number" step="0.1" name="echo_lcc" class="form-control form-control-sm"
                                               style="border-radius:8px;" placeholder="—">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" style="font-size:.72rem;color:#64748b;">Liquide amniotique</label>
                                        <select name="echo_la" class="form-select form-select-sm" style="border-radius:8px;">
                                            <option value="">—</option>
                                            <option>Normal</option>
                                            <option>Oligoamnios</option>
                                            <option>Hydramnios</option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" style="font-size:.72rem;color:#64748b;">Placenta</label>
                                        <input type="text" name="echo_placenta" class="form-control form-control-sm"
                                               style="border-radius:8px;" placeholder="Antérieur, postérieur…">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" style="font-size:.72rem;color:#64748b;">Présentation</label>
                                        <select name="echo_presentation" class="form-select form-select-sm" style="border-radius:8px;">
                                            <option value="">—</option>
                                            <option>Céphalique</option>
                                            <option>Siège</option>
                                            <option>Transversale</option>
                                            <option>Oblique</option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" style="font-size:.72rem;color:#64748b;">BDCF (bpm)</label>
                                        <input type="number" name="echo_bdcf" class="form-control form-control-sm"
                                               style="border-radius:8px;" placeholder="120–160">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" style="font-size:.72rem;color:#64748b;">Âge écho (SA)</label>
                                        <input type="text" name="echo_age_echo" class="form-control form-control-sm"
                                               style="border-radius:8px;" placeholder="Ex : 28+3">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Conclusion -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary">CONCLUSION</label>
                            <textarea name="echo_conclusion" class="form-control" rows="2" style="border-radius:10px;"
                                      placeholder="Grossesse évolutive, pas d'anomalie décelée…"></textarea>
                        </div>

                        <!-- Recommandations -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary">RECOMMANDATIONS / SUITES</label>
                            <textarea name="echo_recommandations" class="form-control" rows="2" style="border-radius:10px;"
                                      placeholder="Contrôle à 4 semaines, RDV MFM, hospitalisation…"></textarea>
                        </div>

                        <!-- ── Fichiers DICOM / Images ── -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary" style="color:#5b21b6 !important;">
                                <i class="bi bi-folder-symlink me-1"></i>
                                FICHIERS DICOM / IMAGES ÉCHOGRAPHIQUES
                            </label>
                            <div class="dicom-drop-zone" id="gyDicomZone"
                                 ondragover="gyDicomDragOver(event)"
                                 ondragleave="gyDicomDragLeave(event)"
                                 ondrop="gyDicomDrop(event)">
                                <input type="file" id="gyDicomInput"
                                       accept=".dcm,.jpg,.jpeg,.png,.pdf" multiple
                                       onchange="gyDicomFilesSelected(this.files)">
                                <div style="font-size:1.7rem;color:#c4b5fd;margin-bottom:6px;">
                                    <i class="bi bi-file-earmark-medical"></i>
                                </div>
                                <div style="font-weight:600;font-size:.88rem;color:#5b21b6;">
                                    Glisser-déposer ou <u>parcourir</u>
                                </div>
                                <div style="font-size:.72rem;color:#94a3b8;margin-top:4px;">
                                    Formats : DICOM (.dcm), JPEG, PNG, PDF &nbsp;·&nbsp; Max 100 Mo par fichier
                                </div>
                            </div>
                            <!-- Liste des fichiers uploadés -->
                            <div id="gyDicomFileList" class="doc-list" style="display:none;"></div>
                            <!-- Champ caché → JSON [{doc_id, nom, url, ext}] -->
                            <input type="hidden" name="echo_dicom_files" id="gyDicomFilesJson" value="[]">
                        </div>

                    </div>
                </div>
            </div><!-- /gyEchoSoiPanel -->

        </div>
    </div>

</div><!-- /panel-step3 -->

<script>
/* ══════════════════════════════════════════════════════════════
   BILANS GYNÉCO — Laboratoire + Imagerie + Écho en salle
   ══════════════════════════════════════════════════════════════ */

/* ── Variables globales ── */
let gyExamensLabo          = [];   // examens en attente d'envoi
let gyExamensLaboEnvoyes   = [];   // copie sauvegardée après envoi (pour impression)
let gyExamensDispo         = [];
let gyDemandesImagerie        = [];   // imagerie en attente
let gyDemandesImagerieEnvoyees= [];   // copie sauvegardée après envoi (pour impression)

const gyPartiesCorps = {
    radiographie: ['Thorax','Abdomen','Crâne','Rachis cervical','Rachis dorsal','Rachis lombaire','Bassin','Épaule','Bras','Avant-bras','Main','Cuisse','Jambe','Pied','Cheville','Genou'],
    echographie:  ['Abdomen total','Pelvis','Obstétricale','Thyroïde','Sein droit','Sein gauche','Rein droit','Rein gauche','Foie','Vésicule biliaire','Col utérin','Doppler utérin','Doppler ombilical','Paroi abdominale'],
    scanner:      ['Crâne','Thorax','Abdomen-Pelvis','Thorax-Abdomen-Pelvis','Rachis cervical','Rachis lombaire','Bassin'],
    irm:          ['Crâne','Rachis cervical','Rachis lombaire','Genou','Épaule','Hanche','Pelvis','Sein droit','Sein gauche','Abdomen'],
    mammographie: ['Sein droit','Sein gauche','Bilatéral'],
    autre:        ['À préciser dans les instructions']
};

const gyIconeModalite = { radiographie:'🩻', echographie:'🔊', scanner:'💻', irm:'🧲', mammographie:'🔬', autre:'📋' };

/* ── Chargement de la liste des examens labo au démarrage ── */
document.addEventListener('DOMContentLoaded', function() {
    fetch(BASE_URL + 'laboratoire/examens-disponibles')
        .then(r => r.json())
        .then(data => {
            gyExamensDispo = data;
            const sel = document.getElementById('gyCategorieExamen');
            const cats = [...new Set(data.map(e => e.categorie).filter(Boolean))].sort();
            cats.forEach(c => {
                const o = document.createElement('option');
                o.value = c; o.textContent = c;
                sel.appendChild(o);
            });
            gyChargerExamensCategorie();
        })
        .catch(() => {});
});

document.getElementById('gyModalDemandeExamen')?.addEventListener('shown.bs.modal', () => {
    /* Brancher la dictée sur les champs du modal */
    if (typeof window.dicteeAttach === 'function') {
        window.dicteeAttach(document.getElementById('gyModalDemandeExamen'));
    }
});
document.getElementById('gyModalDemandeImagerie')?.addEventListener('shown.bs.modal', () => {
    if (typeof window.dicteeAttach === 'function') {
        window.dicteeAttach(document.getElementById('gyModalDemandeImagerie'));
    }
});

document.getElementById('gyModalDemandeExamen')?.addEventListener('show.bs.modal', () => {
    document.getElementById('gySearchExamen').value = '';
    document.getElementById('gyCategorieExamen').value = '';
    gyChargerExamensCategorie();
});

function gyChargerExamensCategorie() {
    const cat    = document.getElementById('gyCategorieExamen').value;
    const search = (document.getElementById('gySearchExamen')?.value || '').toLowerCase().trim();
    const list   = document.getElementById('gyExamensList');
    let filtered = cat ? gyExamensDispo.filter(e => e.categorie === cat) : gyExamensDispo;
    if (search) filtered = filtered.filter(e => e.nom.toLowerCase().includes(search));

    const badge = document.getElementById('gyExamenCount');
    if (badge) badge.textContent = filtered.length + ' examen' + (filtered.length > 1 ? 's' : '');

    if (!filtered.length) {
        list.innerHTML = '<p class="text-muted small text-center py-4 mb-0"><i class="bi bi-search me-1"></i>Aucun examen trouvé</p>';
        gyMajCompteurSelection(); return;
    }

    list.innerHTML = filtered.map(ex => {
        const d = JSON.stringify(ex).replace(/"/g, '&quot;');
        const j = ex.a_jeun_requis ? '<span class="badge bg-warning text-dark ms-1" style="font-size:.58rem">⏰ À jeun</span>' : '';
        return `<div class="gy-exam-row d-flex align-items-start gap-2 px-2 py-2"
                     style="border-bottom:1px solid #f1f5f9;cursor:pointer;border-radius:6px;"
                     onclick="gyToggleRow(this)">
                    <input class="form-check-input flex-shrink-0 gy-exam-cb" type="checkbox"
                           id="gyEx_${ex.id}" value="${ex.id}" data-examen="${d}"
                           style="margin-top:3px;cursor:pointer;"
                           onclick="event.stopPropagation();gyRowHL(this.closest('.gy-exam-row'),this.checked);gyMajCompteurSelection()">
                    <label class="mb-0 w-100" for="gyEx_${ex.id}" style="cursor:pointer;pointer-events:none;">
                        <div class="fw-semibold" style="font-size:.83rem;">${ex.nom}</div>
                        <div class="text-muted d-flex flex-wrap gap-1" style="font-size:.7rem;">
                            <span class="badge bg-secondary" style="font-size:.6rem;">${ex.categorie||'—'}</span>
                            <span>${ex.type_prelevement||''}</span> · Délai <strong>${ex.delai_rendu_heures}h</strong>${j}
                        </div>
                    </label>
                </div>`;
    }).join('');
    gyMajCompteurSelection();
}

function gyToggleRow(row) {
    const cb = row.querySelector('.gy-exam-cb');
    if (!cb) return;
    cb.checked = !cb.checked;
    gyRowHL(row, cb.checked);
    gyMajCompteurSelection();
}
function gyRowHL(row, checked) { row.style.background = checked ? '#eff6ff' : ''; }
function gyMajCompteurSelection() {
    const n   = document.querySelectorAll('.gy-exam-cb:checked').length;
    const lbl = document.getElementById('gySelectedCount');
    if (lbl) lbl.textContent = n > 0 ? `✔ ${n} examen${n>1?'s':''} sélectionné${n>1?'s':''}` : '';
}
function gyDeselectAll() {
    document.querySelectorAll('.gy-exam-cb:checked').forEach(cb => {
        cb.checked = false; gyRowHL(cb.closest('.gy-exam-row'), false);
    });
    gyMajCompteurSelection();
}

function gyAjouterExamenToListe(e) {
    e.preventDefault();
    const form    = e.target;
    const checked = document.querySelectorAll('.gy-exam-cb:checked');
    if (!checked.length) {
        const list = document.getElementById('gyExamensList');
        list.style.border = '1.5px solid #dc2626';
        setTimeout(() => list.style.border = '1.5px solid #dee2e6', 1800);
        return;
    }
    const isUrgent = form.urgent.checked;
    const isAJeun  = form.a_jeun.checked;
    const instr    = form.instructions.value;
    checked.forEach(cb => {
        const ex = JSON.parse(cb.dataset.examen.replace(/&quot;/g, '"'));
        if (!gyExamensLabo.find(x => x.id === ex.id)) {
            gyExamensLabo.push({ id:ex.id, nom:ex.nom, categorie:ex.categorie,
                type_prelevement:ex.type_prelevement, delai_rendu_heures:ex.delai_rendu_heures,
                urgent:isUrgent, a_jeun:isAJeun, instructions:instr });
        }
    });
    gyAfficherListeExamens();
    bootstrap.Modal.getInstance(document.getElementById('gyModalDemandeExamen'))?.hide();
    form.reset();
}

function gyAfficherListeExamens() {
    const tbody = document.getElementById('gyListeExamens');
    const empty = document.getElementById('gyEmptyLabo');
    const btn   = document.getElementById('gyBtnEnvoyerLabo');
    if (!gyExamensLabo.length) {
        tbody.innerHTML = ''; empty.style.display = 'block'; btn.style.display = 'none'; return;
    }
    empty.style.display = 'none'; btn.style.display = 'inline-block';
    tbody.innerHTML = gyExamensLabo.map((ex, i) => `
        <tr>
            <td class="fw-semibold">${ex.nom}</td>
            <td><span class="badge bg-secondary">${ex.categorie}</span></td>
            <td style="font-size:.78rem;">${ex.type_prelevement||'—'}</td>
            <td style="font-size:.78rem;">${ex.delai_rendu_heures}h</td>
            <td>${ex.urgent?'<span class="badge bg-danger">URGENT</span>':'<span class="badge bg-success">Normal</span>'}
                ${ex.a_jeun?'<br><small class="text-warning fw-bold">À jeun</small>':''}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2"
                        onclick="gyRetirerExamen(${i})"><i class="bi bi-x-lg"></i></button>
            </td>
        </tr>`).join('');
}

function gyRetirerExamen(i) { gyExamensLabo.splice(i, 1); gyAfficherListeExamens(); }

function gyEnvoyerAuLaboratoire() {
    if (!gyExamensLabo.length) { alert('Ajoutez d\'abord au moins un examen.'); return; }
    const btn = document.getElementById('gyBtnEnvoyerLabo');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Envoi…';

    fetch(BASE_URL + 'laboratoire/creer-demande-consultation', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ patient_id: PATIENT_ID, examens: gyExamensLabo })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            gyToast('✅ Demande labo envoyée avec succès !', 'success');
            gyExamensLaboEnvoyes = [...gyExamensLabo]; // sauvegarde pour impression
            gyExamensLabo = []; gyAfficherListeExamens();
            btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Envoyé';
        } else {
            gyToast('❌ Erreur : ' + data.message, 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-1"></i>Envoyer au Laboratoire';
        }
    })
    .catch(() => {
        gyToast('❌ Erreur réseau.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i>Envoyer au Laboratoire';
    });
}

/* ── Imagerie ── */
function gyUpdatePartieCorps() {
    const mod = document.getElementById('gyModaliteImagerie').value;
    const sel = document.getElementById('gyPartieCorpsSelect');
    sel.innerHTML = '<option value="">— Choisir la partie —</option>';
    (gyPartiesCorps[mod] || []).forEach(p => {
        const o = document.createElement('option'); o.value = p; o.textContent = p; sel.appendChild(o);
    });
    const info = document.getElementById('gyInfoImagerie');
    if (mod === 'scanner' || mod === 'irm') {
        info.innerHTML = `<div class="alert alert-warning py-2 small mb-0"><i class="bi bi-info-circle me-1"></i>
            Pour le <strong>${mod.toUpperCase()}</strong>, vérifiez créatinine et allergies au produit de contraste.</div>`;
    } else { info.innerHTML = ''; }
}

function gyAjouterImagerieToListe(e) {
    e.preventDefault();
    const form    = e.target;
    const modalite = form.modalite.value;
    const partie   = form.partie_corps.value;
    const indication = form.indication.value.trim();
    if (!modalite || !partie || !indication) {
        alert('Renseignez la modalité, la partie du corps et l\'indication clinique.'); return;
    }
    gyDemandesImagerie.push({
        modalite, partie_corps: partie,
        urgence: form.urgence.value, cote: form.cote.value,
        avec_contraste: form.avec_contraste.checked,
        indication, instructions: form.instructions.value
    });
    gyAfficherListeImagerie();
    bootstrap.Modal.getInstance(document.getElementById('gyModalDemandeImagerie'))?.hide();
    form.reset();
    document.getElementById('gyInfoImagerie').innerHTML = '';
    document.getElementById('gyPartieCorpsSelect').innerHTML = '<option value="">— Sélectionner d\'abord la modalité —</option>';
}

function gyAfficherListeImagerie() {
    const tbody = document.getElementById('gyListeImagerie');
    const empty = document.getElementById('gyEmptyImagerie');
    const btn   = document.getElementById('gyBtnEnvoyerRadio');
    if (!gyDemandesImagerie.length) {
        tbody.innerHTML = ''; empty.style.display = 'block'; btn.style.display = 'none'; return;
    }
    empty.style.display = 'none'; btn.style.display = 'inline-block';
    const urg = { NORMAL:'bg-success', URGENT:'bg-danger', TRES_URGENT:'bg-dark' };
    tbody.innerHTML = gyDemandesImagerie.map((d, i) => `
        <tr>
            <td class="fw-semibold">${gyIconeModalite[d.modalite]||'📋'} ${d.modalite.charAt(0).toUpperCase()+d.modalite.slice(1)}</td>
            <td>${d.partie_corps}${d.cote?' <small class="text-muted">('+d.cote+')</small>':''}
                ${d.avec_contraste?'<span class="badge bg-info text-dark ms-1">+Contraste</span>':''}</td>
            <td><span class="badge ${urg[d.urgence]||'bg-secondary'}">${d.urgence}</span></td>
            <td class="small text-muted">${(d.indication||'').substring(0,50)}${(d.indication||'').length>50?'…':''}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2"
                        onclick="gyRetirerImagerie(${i})"><i class="bi bi-x-lg"></i></button>
            </td>
        </tr>`).join('');
}

function gyRetirerImagerie(i) { gyDemandesImagerie.splice(i, 1); gyAfficherListeImagerie(); }

function gyEnvoyerEnRadiologie() {
    if (!gyDemandesImagerie.length) { alert('Aucune demande d\'imagerie à envoyer.'); return; }
    const btn = document.getElementById('gyBtnEnvoyerRadio');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Envoi…';

    fetch(BASE_URL + 'imagerie/creer-demande-consultation', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
            patient_id: PATIENT_ID,
            medecin_id: '<?= $_SESSION['user_id'] ?? '' ?>',
            demandes: gyDemandesImagerie
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            gyToast(`✅ ${data.count || gyDemandesImagerie.length} demande(s) envoyée(s) en radiologie !`, 'success');
            gyDemandesImagerieEnvoyees = [...gyDemandesImagerie]; // sauvegarde pour impression
            gyDemandesImagerie = []; gyAfficherListeImagerie();
            btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Envoyé';
        } else {
            gyToast('❌ ' + (data.message || 'Erreur inconnue'), 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-1"></i>Envoyer en Radiologie';
        }
    })
    .catch(() => {
        gyToast('❌ Erreur réseau.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i>Envoyer en Radiologie';
    });
}

/* ── Échographie réalisée en salle ── */
function gyToggleEchoSoi() {
    const panel = document.getElementById('gyEchoSoiPanel');
    const btn   = document.getElementById('gyBtnEchoSoi');
    const open  = panel.style.display === 'none';
    panel.style.display = open ? 'block' : 'none';
    btn.style.background    = open ? '#ede9fe' : '#fdf4ff';
    btn.style.color         = '#7c3aed';
    btn.style.border        = '1.5px solid ' + (open ? '#c4b5fd' : '#e9d5ff');
    btn.innerHTML = open
        ? '<i class="bi bi-soundwave me-1"></i> Compte-rendu écho en cours…'
        : '<i class="bi bi-soundwave me-1"></i> Réaliser l\'écho moi-même';
    if (open) panel.scrollIntoView({ behavior:'smooth', block:'nearest' });
}

function gyEchoTypeChange() {
    const val = document.getElementById('gyEchoType').value;
    const obs = ['Obstétricale T1','Obstétricale T2','Obstétricale T3','Écho doppler obstétrical'];
    document.getElementById('gyEchoBiometrie').style.display =
        obs.includes(val) ? 'block' : 'none';
}

/* ══════════════════════════════════════════════════════════════
   UPLOAD DICOM / IMAGES ÉCHOGRAPHIQUES
   ══════════════════════════════════════════════════════════════ */
let gyDicomFiles = [];   // [{doc_id, nom, url, ext, item_id}]

function gyDicomDragOver(e) {
    e.preventDefault();
    document.getElementById('gyDicomZone').classList.add('drag-over');
}
function gyDicomDragLeave(e) {
    document.getElementById('gyDicomZone').classList.remove('drag-over');
}
function gyDicomDrop(e) {
    e.preventDefault();
    document.getElementById('gyDicomZone').classList.remove('drag-over');
    gyDicomFilesSelected(e.dataTransfer.files);
}
function gyDicomFilesSelected(fileList) {
    [...fileList].forEach(f => gyDicomUploadOne(f));
    // Réinitialiser l'input pour permettre de re-sélectionner le même fichier
    const inp = document.getElementById('gyDicomInput');
    if (inp) inp.value = '';
}

function gyDicomUploadOne(file) {
    const allowed = ['dcm','jpg','jpeg','png','pdf'];
    const ext = file.name.split('.').pop().toLowerCase();
    if (!allowed.includes(ext)) {
        gyToast('❌ Format non autorisé : ' + file.name, 'danger'); return;
    }
    const maxMo = ext === 'dcm' ? 100 : 20;
    if (file.size > maxMo * 1024 * 1024) {
        gyToast(`❌ Fichier trop volumineux (max ${maxMo} Mo) : ${file.name}`, 'danger'); return;
    }

    // Créer l'entrée dans la liste avec barre de progression
    const itemId = 'dicom_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
    const list = document.getElementById('gyDicomFileList');
    list.style.display = 'flex';
    list.insertAdjacentHTML('beforeend', `
        <div class="doc-item" id="${itemId}">
            <div class="doc-thumb" style="background:#ede9fe;" id="${itemId}_thumb">
                <i class="bi bi-file-earmark-medical" style="color:#7c3aed;font-size:1.2rem;"></i>
            </div>
            <div class="doc-info">
                <div class="doc-name">${file.name}</div>
                <div class="doc-meta">
                    <span>${(file.size/1024/1024).toFixed(2)} Mo</span>
                    &nbsp;·&nbsp;
                    <span id="${itemId}_status" style="color:#7c3aed;">⏳ Envoi en cours…</span>
                </div>
                <div style="height:4px;background:#e2e8f0;border-radius:2px;overflow:hidden;margin-top:6px;">
                    <div id="${itemId}_bar" style="height:100%;background:#7c3aed;width:0%;transition:width .3s;border-radius:2px;"></div>
                </div>
            </div>
            <div class="doc-actions" id="${itemId}_actions">
                <button type="button" class="btn-doc" style="background:#fee2e2;color:#dc2626;"
                        onclick="gyDicomRemove('${itemId}', null)" title="Annuler">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>`);

    const fd = new FormData();
    fd.append('document', file);
    fd.append('patient_id', PATIENT_ID);
    fd.append('categorie', 'Échographie DICOM');
    fd.append('description', 'Upload écho en salle — consultation gynéco');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', BASE_URL + 'formulaire/upload-document-ajax');

    xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
            const pct = Math.round(e.loaded / e.total * 100);
            const bar = document.getElementById(itemId + '_bar');
            if (bar) bar.style.width = pct + '%';
        }
    };

    xhr.onload = function() {
        const bar    = document.getElementById(itemId + '_bar');
        const status = document.getElementById(itemId + '_status');
        try {
            const data = JSON.parse(xhr.responseText);
            if (data.success) {
                if (bar)    { bar.style.width = '100%'; bar.style.background = '#059669'; }
                if (status) status.innerHTML = '<span style="color:#059669;">✅ Enregistré</span>';

                // Remplacer les boutons par Voir + Supprimer
                const actions = document.getElementById(itemId + '_actions');
                if (actions) {
                    actions.innerHTML = `
                        <a href="${data.url}" target="_blank" class="btn-doc btn-doc-view" title="Voir / Télécharger">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button type="button" class="btn-doc" style="background:#fee2e2;color:#dc2626;"
                                onclick="gyDicomRemove('${itemId}', ${data.doc_id})" title="Retirer">
                            <i class="bi bi-trash"></i>
                        </button>`;
                }
                // Aperçu miniature pour JPEG/PNG
                const thumb = document.getElementById(itemId + '_thumb');
                if (thumb && ['jpg','jpeg','png'].includes(data.ext)) {
                    thumb.innerHTML = `<img src="${data.url}" alt="aperçu">`;
                } else if (thumb && data.ext === 'pdf') {
                    thumb.innerHTML = `<i class="bi bi-file-earmark-pdf" style="color:#dc2626;font-size:1.2rem;"></i>`;
                }

                // Enregistrer dans le tableau + mettre à jour le champ caché
                gyDicomFiles.push({ doc_id: data.doc_id, nom: data.nom, url: data.url, ext: data.ext, item_id: itemId });
                gyDicomMajJson();
            } else {
                if (bar)    { bar.style.width = '100%'; bar.style.background = '#dc2626'; }
                if (status) status.innerHTML = `<span style="color:#dc2626;">❌ ${data.message || 'Erreur serveur'}</span>`;
            }
        } catch(err) {
            if (bar)    { bar.style.width = '100%'; bar.style.background = '#dc2626'; }
            if (status) status.innerHTML = '<span style="color:#dc2626;">❌ Réponse invalide</span>';
        }
    };

    xhr.onerror = function() {
        const status = document.getElementById(itemId + '_status');
        if (status) status.innerHTML = '<span style="color:#dc2626;">❌ Erreur réseau</span>';
    };

    xhr.send(fd);
}

function gyDicomRemove(itemId, docId) {
    const el = document.getElementById(itemId);
    if (el) el.remove();
    gyDicomFiles = gyDicomFiles.filter(f => f.item_id !== itemId);
    gyDicomMajJson();
    if (!gyDicomFiles.length) {
        const list = document.getElementById('gyDicomFileList');
        if (list) list.style.display = 'none';
    }
}

function gyDicomMajJson() {
    const val = gyDicomFiles.map(f => ({ doc_id: f.doc_id, nom: f.nom, url: f.url, ext: f.ext }));
    document.getElementById('gyDicomFilesJson').value = JSON.stringify(val);
}

/* ── Toast utilitaire ── */
function gyToast(msg, type) {
    let box = document.getElementById('gyToastCtn');
    if (!box) {
        box = document.createElement('div');
        box.id = 'gyToastCtn';
        box.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
        document.body.appendChild(box);
    }
    const el = document.createElement('div');
    el.style.cssText = `background:${type==='success'?'#059669':'#dc2626'};color:white;padding:12px 18px;border-radius:12px;font-weight:600;font-size:.85rem;box-shadow:0 4px 16px rgba(0,0,0,.15);animation:slideIn .25s ease;`;
    el.innerHTML = msg;
    box.appendChild(el);
    setTimeout(() => { el.style.opacity='0'; el.style.transition='opacity .3s'; setTimeout(()=>el.remove(),350); }, 4000);
}
</script>


<!-- ══════════════════════════════════════════════════════════
     ÉTAPE 4 — TRAITEMENT & ORDONNANCE
     ══════════════════════════════════════════════════════════ -->
<div class="wz-panel" id="panel-step4">

    <div class="gy-section">
        <div class="gy-section-head"><i class="bi bi-clipboard-pulse"></i> Plan de traitement</div>
        <div class="gy-section-body">
            <textarea name="plan_traitement" class="form-control" rows="3"
                      placeholder="Stratégie thérapeutique globale : chirurgie, médical, surveillance, rééducation…"><?= htmlspecialchars($formData['plan_traitement'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="gy-section">
        <div class="gy-section-head">
            <span><i class="bi bi-capsule"></i> Ordonnance médicale</span>
            <button type="button" class="btn-add-row ms-auto" onclick="ajouterMedicament()">
                <i class="bi bi-plus-circle-fill"></i> Ajouter un médicament
            </button>
        </div>
        <div class="gy-section-body">
            <div class="table-responsive">
                <table class="tbl-presc" id="tblOrdonnance">
                    <thead><tr>
                        <th style="width:26%;">Médicament</th>
                        <th style="width:14%;">Dosage</th>
                        <th style="width:16%;">Voie</th>
                        <th style="width:20%;">Fréquence</th>
                        <th style="width:14%;">Durée</th>
                        <th style="width:10%;"></th>
                    </tr></thead>
                    <tbody id="tblOrdoBody">
                    <?php
                    $ordonnance = $formData['ordonnance'] ?? [['medicament'=>'','dosage'=>'','voie'=>'Oral','frequence'=>'1 fois par jour (matin)','duree'=>'']];
                    if (!is_array($ordonnance)) $ordonnance = [];
                    foreach ($ordonnance as $oi => $med): ?>
                    <tr>
                        <td><input type="text" name="ordonnance[<?=$oi?>][medicament]" list="dl-medoc"
                                   value="<?= htmlspecialchars($med['medicament'] ?? '') ?>" placeholder="Médicament…"></td>
                        <td><input type="text" name="ordonnance[<?=$oi?>][dosage]" list="dl-dosage"
                                   value="<?= htmlspecialchars($med['dosage'] ?? '') ?>" placeholder="500mg"></td>
                        <td><select name="ordonnance[<?=$oi?>][voie]">
                            <?php foreach (['Oral','IV','IM','SC','Topique','Vaginal','Rectal','Sublingual','Inhalation'] as $v): ?>
                            <option <?= ($med['voie'] ?? '') === $v ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select></td>
                        <td><input type="text" name="ordonnance[<?=$oi?>][frequence]" list="dl-frequence"
                                   value="<?= htmlspecialchars($med['frequence'] ?? '') ?>" placeholder="2x/jour"></td>
                        <td><input type="text" name="ordonnance[<?=$oi?>][duree]" list="dl-duree"
                                   value="<?= htmlspecialchars($med['duree'] ?? '') ?>" placeholder="7 jours"></td>
                        <td style="text-align:center;"><button type="button" class="btn-del-row-sm" onclick="this.closest('tr').remove()"><i class="bi bi-x-lg"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <label class="form-label">Instructions &amp; conseils au patient</label>
                <textarea name="instructions_patient" class="form-control" rows="2"
                          placeholder="Hygiène de vie, alimentation, activité physique, surveillance à domicile, signaux d'alarme…"><?= htmlspecialchars($formData['instructions_patient'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

</div><!-- /panel-step4 -->


<!-- ══════════════════════════════════════════════════════════
     ÉTAPE 5 — DIAGNOSTIC & SYNTHÈSE
     ══════════════════════════════════════════════════════════ -->
<div class="wz-panel" id="panel-step5">

    <div class="gy-section">
        <div class="gy-section-head"><i class="bi bi-clipboard-check"></i> Diagnostic &amp; Conduite à tenir</div>
        <div class="gy-section-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Diagnostic principal / Impression clinique</label>
                    <textarea name="diagnostic" class="form-control" rows="3"
                              list="dl-diagnostic"
                              placeholder="Diagnostic principal et différentiels…"><?= htmlspecialchars($formData['diagnostic'] ?? '') ?></textarea>
                    <div class="sug-hint"><i class="bi bi-lightbulb"></i> Diagnostics fréquents disponibles</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Conduite à tenir / Plan de prise en charge</label>
                    <textarea name="conduite_tenir" class="form-control" rows="3"
                              list="dl-conduite"
                              placeholder="Hospitalisation, surveillance, chirurgie, suivi ambulatoire…"><?= htmlspecialchars($formData['conduite_tenir'] ?? '') ?></textarea>
                    <div class="sug-hint"><i class="bi bi-lightbulb"></i> Conduites types disponibles</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Observations / Notes complémentaires</label>
                    <textarea name="observations" class="form-control" rows="2"
                              placeholder="Remarques, conseils hygiéno-diététiques, prochain RDV, orientation…"><?= htmlspecialchars($formData['observations'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Gynécologue / Médecin responsable</label>
                    <?php
                      $__ms_field    = 'medecin_gyneco';
                      $__ms_label    = 'Gynécologue';
                      $__ms_value    = $formData['medecin_gyneco'] ?? $medecin_nom;
                      $__ms_id_field = 'medecin_id';
                      include __DIR__ . '/_medecin_select.php';
                    ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Prochain rendez-vous</label>
                    <input type="date" name="prochain_rdv" class="form-control"
                           value="<?= htmlspecialchars($formData['prochain_rdv'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

</div><!-- /panel-step5 -->


<!-- ══════════════════════════════════════════════════════════
     ÉTAPE 6 — RÉCAPITULATIF & SIGNATURE ÉLECTRONIQUE
     ══════════════════════════════════════════════════════════ -->
<div class="wz-panel" id="panel-step6">

    <!-- ── Confirmation avant enregistrement ── -->
    <div class="gy-section">
        <div class="gy-section-head" style="background:linear-gradient(90deg,#f0fdf4,#dcfce7);">
            <i class="bi bi-check2-circle" style="color:#15803d;"></i>
            <span style="color:#166534;">Confirmation &amp; Enregistrement</span>
        </div>
        <div class="gy-section-body">

            <!-- En-tête patient + médecin -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-3 rounded-3 mb-4"
                 style="background:#f8fafc;border:1px solid #e2e8f0;">
                <div>
                    <div class="fw-bold" style="font-size:.98rem;">
                        <?= htmlspecialchars($patient['prenom'].' '.$patient['nom']) ?>
                    </div>
                    <div class="text-muted small">
                        <?= htmlspecialchars($patient['dossier_numero'] ?? '') ?>
                        <?php if (!empty($patient['date_naissance'])): ?>
                         · <?= date_diff(date_create($patient['date_naissance']), date_create('now'))->y ?> ans
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-end">
                    <div class="fw-semibold small"><?= htmlspecialchars($medecinNomSig) ?></div>
                    <?php if ($medecinSpec): ?><div class="text-muted small"><?= htmlspecialchars($medecinSpec) ?></div><?php endif; ?>
                    <div class="text-muted small"><?= date('d/m/Y') ?></div>
                </div>
            </div>

            <?php if ($signatureB64 || $cachetB64): ?>
            <!-- Signature & cachet -->
            <div class="d-flex align-items-center gap-4 flex-wrap p-3 rounded-3 mb-3"
                 style="background:#f0fdf4;border:1px solid #bbf7d0;">
                <div class="text-center">
                    <div style="position:relative;display:inline-block;width:100px;height:100px;">
                        <?php if ($cachetB64): ?>
                        <img src="<?= htmlspecialchars($cachetB64) ?>"
                             style="width:100px;height:100px;object-fit:contain;opacity:0.85;">
                        <?php endif; ?>
                        <?php if ($signatureB64): ?>
                        <img src="<?= htmlspecialchars($signatureB64) ?>"
                             style="position:absolute;top:50%;left:50%;
                                    transform:translate(-50%,-50%);
                                    max-width:92px;max-height:75px;
                                    object-fit:contain;opacity:0.92;">
                        <?php endif; ?>
                    </div>
                    <div class="text-muted mt-1" style="font-size:.72rem;">Signature &amp; Cachet</div>
                </div>
                <div>
                    <div class="fw-semibold text-success"><i class="bi bi-check-circle-fill me-1"></i>Signature électronique configurée</div>
                    <div class="text-muted small mt-1">Sera apposée sur tous les documents générés.</div>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning mb-3" style="border-radius:10px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Aucune signature configurée —
                <a href="<?= BASE_URL ?>profil/signature" target="_blank" class="fw-semibold">Configurer maintenant</a>
            </div>
            <?php endif; ?>

            <!-- Note finale -->
            <div class="p-3 rounded-3" style="background:#f8fafc;border:1px dashed #e2e8f0;">
                <label class="form-label small fw-bold text-secondary">NOTE FINALE / OBSERVATIONS DE CLÔTURE</label>
                <textarea name="note_cloture" class="form-control" rows="2" style="border-radius:10px;"
                          placeholder="Commentaires de fin de consultation, instructions de suivi…"></textarea>
            </div>

            <div class="mt-3 p-3 rounded-3 d-flex align-items-center gap-2"
                 style="background:#eff6ff;border:1px solid #bfdbfe;font-size:.87rem;color:#1e40af;">
                <i class="bi bi-info-circle-fill"></i>
                En cliquant sur <strong>Terminer et Enregistrer</strong>, la consultation sera sauvegardée et vous serez redirigé vers la page récapitulative.
            </div>

        </div>
    </div>

</div><!-- /panel-step6 -->

</form><!-- /formGyneco -->

<!-- ══════════════════════════════════════════════════════════
     MODAL — AJOUT EXAMEN LABO (gynéco) — HORS DU FORM PRINCIPAL
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="gyModalDemandeExamen" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">
            <div class="modal-header border-0 pb-0"
                 style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);border-radius:18px 18px 0 0;">
                <h5 class="modal-title text-white fw-bold">
                    <i class="bi bi-flask me-2"></i>Ajouter un examen de laboratoire
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="gyFormAjoutExamen" onsubmit="gyAjouterExamenToListe(event)">
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-secondary">CATÉGORIE</label>
                        <select class="form-select" id="gyCategorieExamen" onchange="gyChargerExamensCategorie()" style="border-radius:10px;">
                            <option value="">Toutes les catégories</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small fw-bold text-secondary">RECHERCHE RAPIDE</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control" id="gySearchExamen"
                                   data-no-dictee="1"
                                   placeholder="Tapez pour filtrer…"
                                   oninput="gyChargerExamensCategorie()" autocomplete="off"
                                   style="border-left:none;">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small fw-bold text-secondary mb-0">
                                SÉLECTIONNEZ LES EXAMENS <span class="text-danger">*</span>
                            </label>
                            <span id="gyExamenCount" class="badge" style="background:#1d4ed8;font-size:.68rem;">0 examen(s)</span>
                        </div>
                        <div id="gyExamensList"
                             style="max-height:220px;overflow-y:auto;border:1.5px solid #dee2e6;border-radius:10px;padding:4px;">
                            <p class="text-muted small text-center py-4 mb-0">
                                <i class="bi bi-hourglass-split me-1"></i>Chargement…
                            </p>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small id="gySelectedCount" class="text-success fw-semibold"></small>
                            <button type="button" class="btn btn-link btn-sm p-0 text-secondary"
                                    onclick="gyDeselectAll()">Tout désélectionner</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="gyUrgent" name="urgent">
                            <label class="form-check-label text-danger fw-bold" for="gyUrgent">
                                <i class="bi bi-exclamation-triangle-fill"></i> URGENT
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="gyAJeun" name="a_jeun">
                            <label class="form-check-label" for="gyAJeun">
                                <i class="bi bi-clock"></i> À jeun requis
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-secondary">INSTRUCTIONS PARTICULIÈRES</label>
                        <textarea name="instructions" class="form-control" rows="2" style="border-radius:10px;"
                                  placeholder="Instructions spéciales pour le laboratoire…"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0" style="background:#f8fafc;">
                <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4">
                    <i class="bi bi-plus-lg me-1"></i>Ajouter les examens sélectionnés
                </button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     MODAL — DEMANDE IMAGERIE (gynéco) — HORS DU FORM PRINCIPAL
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="gyModalDemandeImagerie" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">
            <div class="modal-header border-0 pb-0"
                 style="background:linear-gradient(135deg,#15803d,#22c55e);border-radius:18px 18px 0 0;">
                <h5 class="modal-title text-white fw-bold">
                    <i class="bi bi-image me-2"></i>Demande d'Imagerie / Radiologie
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="gyFormAjoutImagerie" onsubmit="gyAjouterImagerieToListe(event)">
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">MODALITÉ <span class="text-danger">*</span></label>
                        <select class="form-select" id="gyModaliteImagerie" name="modalite" required
                                onchange="gyUpdatePartieCorps()" style="border-radius:10px;">
                            <option value="">— Choisir —</option>
                            <option value="radiographie">🩻 Radiographie</option>
                            <option value="echographie">🔊 Échographie</option>
                            <option value="scanner">💻 Scanner (TDM)</option>
                            <option value="irm">🧲 IRM</option>
                            <option value="mammographie">🔬 Mammographie</option>
                            <option value="autre">📋 Autre</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">PARTIE / RÉGION <span class="text-danger">*</span></label>
                        <select class="form-select" id="gyPartieCorpsSelect" name="partie_corps" required style="border-radius:10px;">
                            <option value="">— Sélectionner d'abord la modalité —</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-secondary">URGENCE</label>
                        <select class="form-select" name="urgence" style="border-radius:10px;">
                            <option value="NORMAL">Normal</option>
                            <option value="URGENT">🔴 URGENT</option>
                            <option value="TRES_URGENT">🚨 TRÈS URGENT</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-secondary">CÔTÉ</label>
                        <select class="form-select" name="cote" style="border-radius:10px;">
                            <option value="">Non applicable</option>
                            <option value="droit">Droit</option>
                            <option value="gauche">Gauche</option>
                            <option value="bilateral">Bilatéral</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end pb-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="gyAvecContraste" name="avec_contraste" value="1">
                            <label class="form-check-label fw-semibold" for="gyAvecContraste">
                                💉 Avec produit de contraste
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-secondary">INDICATION CLINIQUE <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="indication" id="gyIndicationImagerie" rows="2"
                                  required style="border-radius:10px;"
                                  placeholder="Ex: douleur pelvienne, suivi grossesse, masse ovarienne…"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-secondary">INSTRUCTIONS PARTICULIÈRES</label>
                        <textarea class="form-control" name="instructions" rows="2" style="border-radius:10px;"
                                  placeholder="Informations complémentaires pour le radiologue…"></textarea>
                    </div>
                    <div id="gyInfoImagerie" class="col-12 mt-1"></div>
                </div>
            </div>
            <div class="modal-footer border-0" style="background:#f8fafc;">
                <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn fw-bold rounded-pill px-4"
                        style="background:#15803d;color:white;">
                    <i class="bi bi-plus-lg me-1"></i>Ajouter à la liste
                </button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ Sections step 5 (hors form — AJAX) ══════════════════════════ -->
<div id="step5extras" style="display:none;">

    <!-- Documents -->
    <div class="s5-toggle" onclick="toggleS5('docBody')">
        <span><i class="bi bi-paperclip me-2" style="color:var(--gy-pink);"></i>
            Documents du dossier
            <span class="badge ms-2 rounded-pill" style="background:var(--gy-pink-lt);color:var(--gy-pink);font-size:.72rem;" id="docCountBadge2">0</span>
        </span>
        <i class="bi bi-chevron-down" style="color:var(--gy-muted);font-size:.8rem;"></i>
    </div>
    <div class="s5-toggle-body" id="docBody">
        <div class="gy-section" style="border-top:none;border-radius:0 0 14px 14px;">
            <div class="gy-section-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Catégorie</label>
                        <select class="form-select" id="uploadCategorie">
                            <?php foreach (['Gynécologie','Échographie','Résultats d\'analyses','Ordonnance','Compte rendu opératoire','Courrier médical','Autre'] as $cat): ?>
                            <option><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Description (facultatif)</label>
                        <input type="text" class="form-control" id="uploadDescription"
                               placeholder="Ex: Écho pelvienne du 15/05/2026…">
                    </div>
                </div>
                <div class="upload-zone" id="uploadZone">
                    <input type="file" id="uploadInput" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.mp4,.mov">
                    <div class="upload-icon"><i class="bi bi-cloud-upload-fill"></i></div>
                    <div class="upload-text">Glissez vos fichiers ici ou cliquez</div>
                    <div class="upload-hint">Images, PDF, Word, Excel — Max 20 Mo/fichier</div>
                </div>
                <div class="upload-progress" id="uploadProgress"><div class="upload-progress-bar" id="uploadProgressBar"></div></div>
                <div id="uploadMessage" style="margin-top:8px;font-size:.82rem;"></div>

                <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                    <span class="form-label mb-0">Fichiers existants</span>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="filterCategorie" onchange="chargerDocuments()" style="width:auto;font-size:.78rem;">
                            <option value="">Toutes catégories</option>
                            <?php foreach (['Gynécologie','Échographie','Résultats d\'analyses','Ordonnance','Compte rendu opératoire','Courrier médical','Autre'] as $cat): ?>
                            <option><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn-add-row" style="padding:5px 10px;font-size:.75rem;margin:0;" onclick="chargerDocuments()">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
                <div class="doc-list" id="docList">
                    <div style="text-align:center;color:#94a3b8;padding:20px;font-size:.85rem;">
                        <i class="bi bi-folder2 d-block mb-2 fs-3"></i>Chargement…
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaires liés -->
    <div class="s5-toggle mt-2" onclick="toggleS5('formesBody')">
        <span><i class="bi bi-grid-3x3-gap me-2" style="color:var(--gy-pink);"></i>Formulaires gynécologiques liés</span>
        <i class="bi bi-chevron-down" style="color:var(--gy-muted);font-size:.8rem;"></i>
    </div>
    <div class="s5-toggle-body" id="formesBody">
        <div class="gy-section" style="border-top:none;border-radius:0 0 14px 14px;">
            <div class="gy-section-body">
                <div class="forms-grid">
                <?php foreach ($formsLies as $fl):
                    $existe = $formulairesExistants[$fl['slug']] ?? null;
                    $statut = $existe['statut'] ?? null;
                    $formId = $existe['id'] ?? null;
                    $dateStr = $existe ? date('d/m/Y', strtotime($existe['date_creation'])) : null;
                ?>
                <div class="form-card">
                    <div class="form-card-icon" style="color:<?= $fl['color'] ?>;"><i class="bi <?= $fl['icon'] ?>"></i></div>
                    <div class="form-card-title"><?= htmlspecialchars($fl['titre']) ?></div>
                    <div class="form-card-status">
                        <?php if ($existe):
                            $stBg = ['BROUILLON'=>'#f1f5f9','SOUMIS'=>'#fef9c3','SIGNE'=>'#dcfce7'][$statut] ?? '#f1f5f9';
                            $stCl = ['BROUILLON'=>'#64748b','SOUMIS'=>'#92400e','SIGNE'=>'#16a34a'][$statut] ?? '#64748b';
                        ?>
                            <span style="background:<?=$stBg?>;color:<?=$stCl?>;padding:2px 10px;border-radius:10px;font-size:.72rem;font-weight:700;"><?= $statut ?></span>
                            <span style="color:#94a3b8;font-size:.72rem;margin-left:4px;"><?= $dateStr ?></span>
                        <?php else: ?>
                            <span style="color:#94a3b8;font-size:.72rem;">Non créé</span>
                        <?php endif; ?>
                    </div>
                    <div class="form-card-actions">
                        <a href="<?= BASE_URL ?>formulaire/creer/<?= $fl['slug'] ?>/<?= (int)$patient['id'] ?><?= $hosp_id ? '?hosp_id='.$hosp_id : '' ?>"
                           class="btn-fc btn-fc-new">
                            <i class="bi bi-plus-lg"></i> <?= $existe ? 'Nouveau' : 'Créer' ?>
                        </a>
                        <?php if ($existe && $formId): ?>
                        <a href="<?= BASE_URL ?>formulaire/imprimer/<?= $fl['slug'] ?>/<?= $formId ?>"
                           target="_blank" class="btn-fc btn-fc-voir">
                            <i class="bi bi-eye"></i> Voir
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div><!-- /step5extras -->

</div><!-- /gy-wrap -->

<!-- ══ WIZARD FOOTER ═══════════════════════════════════════════════ -->
<div class="wz-footer" id="wzFooter">
    <button type="button" class="btn-wz-prev" id="btnPrev" onclick="wzNav(-1)" disabled>
        <i class="bi bi-arrow-left"></i> Précédent
    </button>
    <div class="wz-counter" id="wzCounter">Étape 1 sur 6 — Motif &amp; Anamnèse</div>
    <div class="d-flex gap-2">
        <button type="button" class="btn-wz-next" id="btnNext" onclick="wzNav(1)">
            Suivant <i class="bi bi-arrow-right"></i>
        </button>
        <button type="submit" form="formGyneco" class="btn-wz-submit" id="btnSubmit" style="display:none;">
            <i class="bi bi-check2-circle"></i> Enregistrer
        </button>
    </div>
</div>


<script>
/* ══ Globaux ════════════════════════════════════════════════════════ */
const PATIENT_ID = <?= (int)$patient['id'] ?>;
// BASE_URL est déjà déclaré dans header.php — pas de redéclaration ici

const STEP_LABELS = [
    '', // index 0 inutilisé
    'Motif &amp; Anamnèse',
    'Examen Clinique',
    'Bilans Paracliniques',
    'Traitement &amp; Ordonnance',
    'Diagnostic &amp; Synthèse',
    'Récap &amp; Signature'
];

let currentStep = 1;
const TOTAL_STEPS = 6;

/* ══ Navigation Wizard ══════════════════════════════════════════════ */
function goStep(step) {
    if (step < 1 || step > TOTAL_STEPS) return;

    if (step > currentStep) {
        for (let i = currentStep; i < step; i++) {
            const si = document.querySelector(`.wz-step-item[data-step="${i}"]`);
            if (si) { si.classList.remove('active'); si.classList.add('done'); }
            const ci = document.getElementById('wz-circle-' + i);
            if (ci) ci.innerHTML = '<i class="bi bi-check-lg"></i>';
        }
    } else if (step < currentStep) {
        for (let i = step; i <= currentStep; i++) {
            const si = document.querySelector(`.wz-step-item[data-step="${i}"]`);
            if (si) si.classList.remove('done', 'active');
            const ci = document.getElementById('wz-circle-' + i);
            if (ci) ci.innerHTML = i;
        }
    }

    document.getElementById('panel-step' + currentStep).classList.remove('active');

    // Extras (documents) visibles aux étapes 5 et 6
    const extras = document.getElementById('step5extras');
    if (step === 5) {
        extras.style.display = '';
        chargerDocuments();
    } else if (step === 6) {
        extras.style.display = 'none';
        // Pré-remplir date signature
        const fldDate = document.getElementById('fldDateSignature');
        if (fldDate) fldDate.value = new Date().toISOString().substring(0, 10);
    } else {
        extras.style.display = 'none';
    }

    currentStep = step;
    document.getElementById('panel-step' + currentStep).classList.add('active');

    const sc = document.querySelector(`.wz-step-item[data-step="${step}"]`);
    if (sc) { sc.classList.remove('done'); sc.classList.add('active'); }

    document.getElementById('btnPrev').disabled = (step === 1);
    document.getElementById('btnNext').style.display    = (step === TOTAL_STEPS) ? 'none' : '';
    document.getElementById('btnSubmit').style.display  = (step === TOTAL_STEPS) ? '' : 'none';
    document.getElementById('wzCounter').innerHTML =
        `Étape ${step} sur ${TOTAL_STEPS} — ${STEP_LABELS[step]}`;
    document.getElementById('wzProgressBar').style.width = ((step / TOTAL_STEPS) * 100) + '%';

    window.scrollTo({ top: 0, behavior: 'smooth' });
    gyLocalSave();
}

/* ── Persistance entre étapes (localStorage) ── */
const GY_LS_KEY = 'gy_draft_' + PATIENT_ID;

function gyLocalSave() {
    try {
        const form = document.getElementById('formGyneco');
        const data = {};
        // Sauvegarder tous les champs du formulaire principal
        form.querySelectorAll('input:not([type=hidden]), textarea, select').forEach(el => {
            const key = el.name || el.id;
            if (!key) return;
            if (el.type === 'checkbox') { data[key] = el.checked; }
            else if (el.type === 'radio') { if (el.checked) data[key] = el.value; }
            else { data[key] = el.value; }
        });
        // Sauvegarder les tableaux d'examens
        data.__labo       = gyExamensLabo;
        data.__laboSent   = gyExamensLaboEnvoyes;
        data.__imag       = gyDemandesImagerie;
        data.__imagSent   = gyDemandesImagerieEnvoyees;
        data.__step       = currentStep;
        localStorage.setItem(GY_LS_KEY, JSON.stringify(data));
    } catch(e) {}
}

function gyLocalRestore() {
    try {
        const raw = localStorage.getItem(GY_LS_KEY);
        if (!raw) return;
        const data = JSON.parse(raw);
        const form = document.getElementById('formGyneco');
        // Restaurer les champs
        form.querySelectorAll('input:not([type=hidden]), textarea, select').forEach(el => {
            const key = el.name || el.id;
            if (!key || !(key in data)) return;
            if (el.type === 'checkbox') { el.checked = !!data[key]; }
            else if (el.type === 'radio') { el.checked = (el.value === data[key]); }
            else { el.value = data[key]; }
        });
        // Restaurer les tableaux
        if (Array.isArray(data.__labo))     gyExamensLabo            = data.__labo;
        if (Array.isArray(data.__laboSent)) gyExamensLaboEnvoyes     = data.__laboSent;
        if (Array.isArray(data.__imag))     gyDemandesImagerie       = data.__imag;
        if (Array.isArray(data.__imagSent)) gyDemandesImagerieEnvoyees = data.__imagSent;
        // Rafraîchir les listes
        gyAfficherListeExamens();
        gyAfficherListeImagerie();
        // Restaurer l'étape (sans appel récursif)
        const savedStep = parseInt(data.__step) || 1;
        if (savedStep > 1) goStep(savedStep);
    } catch(e) {}
}

function gyLocalClear() {
    localStorage.removeItem(GY_LS_KEY);
}

/* ── Validation par étape ── */
const GY_OBLIGATOIRES = {
    1: [
        { selector: 'input[name="motif"]', label: 'Motif principal de consultation' },
    ],
    2: [],
    3: [],
    4: [],
    5: [
        { selector: 'textarea[name="diagnostic"]', label: 'Diagnostic principal' },
    ],
    6: [],
};

function gyValiderEtape(step) {
    const champs = GY_OBLIGATOIRES[step] || [];
    let erreurs = [];
    champs.forEach(c => {
        const el = document.querySelector(c.selector);
        if (!el || !el.value.trim()) {
            erreurs.push(c.label);
            if (el) {
                el.classList.add('is-invalid');
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } else {
            if (el) el.classList.remove('is-invalid');
        }
    });
    if (erreurs.length) {
        gyToast('⚠️ Champ(s) obligatoire(s) : ' + erreurs.join(', '), 'danger');
        return false;
    }
    return true;
}

function wzNav(dir) {
    if (dir > 0 && !gyValiderEtape(currentStep)) return; // valider avant d'avancer
    if (dir > 0) {
        // Save current step data to server SESSION (like standard form)
        gySauvegarderEtapeSession(currentStep);
    }
    goStep(currentStep + dir);
}

async function gySauvegarderEtapeSession(step) {
    try {
        const data = gyDeepSerialize(document.getElementById('formGyneco'));
        await fetch(BASE_URL + 'consultation-gyneco/sauvegarder-etape', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ etape: step, data })
        });
    } catch(e) { /* silent – localStorage is fallback */ }
}

/* ══════════════════════════════════════════════════════════════════
   STEP 6 — RÉCAPITULATIF DYNAMIQUE
   ══════════════════════════════════════════════════════════════════ */
function gyGetVal(name) {
    const el = document.querySelector(`[name="${name}"]`);
    if (!el) return '';
    return el.value?.trim() || '';
}

function gyBuildRecap() {
    // ── Motif ──
    const motif = gyGetVal('motif_consultation');
    const plaintes = gyGetVal('plaintes');
    rcpBlock('rcpMotif','Motif de consultation',
        [motif, plaintes].filter(Boolean).join('\n') || null);

    // ── Examen clinique ──
    const ta = gyGetVal('ta'); const temp = gyGetVal('temperature');
    const poids = gyGetVal('poids'); const taille = gyGetVal('taille');
    const examen_general = gyGetVal('examen_general');
    let examLines = [];
    if (ta)      examLines.push('TA : ' + ta);
    if (temp)    examLines.push('Température : ' + temp + ' °C');
    if (poids)   examLines.push('Poids : ' + poids + ' kg');
    if (taille)  examLines.push('Taille : ' + taille + ' cm');
    if (examen_general) examLines.push(examen_general);
    rcpBlock('rcpExamen','Examen clinique', examLines.length ? examLines.join('  |  ') : null);

    // ── Diagnostic ──
    const diag = gyGetVal('diagnostic');
    const conduite = gyGetVal('conduite_tenir');
    rcpBlock('rcpDiagnostic','Diagnostic', diag || null);
    rcpBlock('rcpConduite','Conduite à tenir', conduite || null);

    // ── Ordonnance ──
    const rows = document.querySelectorAll('#tblOrdoBody tr');
    if (rows.length) {
        let html = '<table class="rcp-table"><thead><tr><th>Médicament</th><th>Dosage</th><th>Voie</th><th>Fréquence</th><th>Durée</th></tr></thead><tbody>';
        rows.forEach(tr => {
            const inputs = tr.querySelectorAll('input,select');
            if (inputs.length >= 5 && inputs[0].value.trim()) {
                html += `<tr><td>${inputs[0].value}</td><td>${inputs[1].value}</td><td>${inputs[2].value}</td><td>${inputs[3].value}</td><td>${inputs[4].value}</td></tr>`;
            }
        });
        html += '</tbody></table>';
        document.getElementById('rcpOrdonnance').innerHTML =
            '<div class="rcp-titre">Ordonnance (' + rows.length + ' médicament(s))</div>' + html;
    } else {
        document.getElementById('rcpOrdonnance').innerHTML = '';
    }

    // ── Bilans labo ──
    // Labo : préférer les examens en attente, sinon ceux déjà envoyés
    const rcpLabo = gyExamensLabo.length ? gyExamensLabo : gyExamensLaboEnvoyes;
    if (rcpLabo.length) {
        const label = gyExamensLaboEnvoyes.length && !gyExamensLabo.length ? ' ✅ envoyés' : '';
        let html = '<table class="rcp-table"><thead><tr><th>Examen</th><th>Catégorie</th><th>Urgence</th></tr></thead><tbody>';
        rcpLabo.forEach(ex => {
            html += `<tr><td>${ex.nom}</td><td>${ex.categorie}</td><td>${ex.urgent?'<span style="color:#dc2626;font-weight:700">URGENT</span>':'Normal'}</td></tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('rcpLabo').innerHTML =
            `<div class="rcp-titre">Examens de laboratoire (${rcpLabo.length}${label})</div>` + html;
    } else {
        document.getElementById('rcpLabo').innerHTML = '';
    }

    // ── Imagerie ──
    const rcpImag = gyDemandesImagerie.length ? gyDemandesImagerie : gyDemandesImagerieEnvoyees;
    if (rcpImag.length) {
        const labelI = gyDemandesImagerieEnvoyees.length && !gyDemandesImagerie.length ? ' ✅ envoyées' : '';
        let html = '<table class="rcp-table"><thead><tr><th>Modalité</th><th>Partie</th><th>Urgence</th><th>Indication</th></tr></thead><tbody>';
        rcpImag.forEach(d => {
            html += `<tr><td>${gyIconeModalite[d.modalite]||'📋'} ${d.modalite}</td><td>${d.partie_corps}</td><td>${d.urgence}</td><td>${(d.indication||'').substring(0,40)}</td></tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('rcpImagerie').innerHTML =
            `<div class="rcp-titre">Examens d'imagerie (${rcpImag.length}${labelI})</div>` + html;
    } else {
        document.getElementById('rcpImagerie').innerHTML = '';
    }

    // Pré-remplir les champs signature
    document.getElementById('fldSignatureMedecin').value = GY_SIG_B64  || '';
    document.getElementById('fldCachetMedecin').value    = GY_CACHET_B64 || '';
    document.getElementById('fldDateSignature').value    = new Date().toISOString().substring(0,10);
}

function rcpBlock(id, titre, val) {
    const el = document.getElementById(id);
    if (!el) return;
    if (!val) { el.innerHTML = ''; return; }
    el.innerHTML = `<div class="rcp-titre">${titre}</div>
        <div class="rcp-val">${val.replace(/\n/g,'<br>')}</div>`;
}

/* ── Signature confirm ── */
// Bouton submit toujours actif à l'étape 6
document.addEventListener('DOMContentLoaded', () => {
    // Pré-remplir la date de signature
    const fldDate = document.getElementById('fldDateSignature');
    if (fldDate) fldDate.value = new Date().toISOString().substring(0, 10);

    // Restaurer depuis SESSION serveur en priorité (comme le formulaire standard)
    // Les champs PHP sont déjà pré-remplis via $formData depuis la SESSION côté serveur.
    // On utilise le localStorage uniquement pour restaurer la navigation (étape courante).
    try {
        const raw = localStorage.getItem(GY_LS_KEY);
        if (raw) {
            const saved = JSON.parse(raw);
            // Restaurer seulement l'étape et les tableaux dynamiques (labo/imagerie)
            if (Array.isArray(saved.__labo))     gyExamensLabo              = saved.__labo;
            if (Array.isArray(saved.__laboSent)) gyExamensLaboEnvoyes       = saved.__laboSent;
            if (Array.isArray(saved.__imag))     gyDemandesImagerie         = saved.__imag;
            if (Array.isArray(saved.__imagSent)) gyDemandesImagerieEnvoyees = saved.__imagSent;
            gyAfficherListeExamens();
            gyAfficherListeImagerie();
            const savedStep = parseInt(saved.__step) || 1;
            if (savedStep > 1) goStep(savedStep);
        }
    } catch(e) {}
});

/* ── Sérialiseur profond : gère ordonnance[0][medicament] → objet imbriqué ── */
function gyDeepSerialize(form) {
    const out = {};
    new FormData(form).forEach((val, raw) => {
        // "ordonnance[0][medicament]" → parts ["ordonnance","0","medicament"]
        const parts = raw.replace(/\[([^\]]*)\]/g, '.$1').split('.');
        let node = out;
        const len = parts.length;
        for (let i = 0; i < len; i++) {
            const k     = parts[i];
            const isLast= (i === len - 1);
            if (isLast) {
                if (k === '') {
                    // trailing [] → push into array parent
                    if (Array.isArray(node)) node.push(val);
                } else {
                    node[k] = val;
                }
            } else {
                const nk     = parts[i + 1];
                const nIsNum = nk !== '' && /^\d+$/.test(nk);
                if (node[k] === undefined) node[k] = nIsNum ? [] : {};
                if (nIsNum) {
                    const idx = parseInt(nk);
                    while (node[k].length <= idx) node[k].push({});
                    node = node[k][idx];
                    i++; // idx déjà consommé
                } else {
                    node = node[k];
                }
            }
        }
    });
    return out;
}

/* ── Soumission finale → récap gynéco (logique identique au formulaire standard) ── */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formGyneco');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();

        const btn = document.getElementById('btnSubmit');
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…'; }

        // Enrichir les champs cachés labo/imagerie
        const fldLabo = document.getElementById('fldLaboData');
        const fldImag = document.getElementById('fldImagData');
        if (fldLabo) fldLabo.value = JSON.stringify(gyExamensLabo.length ? gyExamensLabo : gyExamensLaboEnvoyes);
        if (fldImag) fldImag.value = JSON.stringify(gyDemandesImagerie.length ? gyDemandesImagerie : gyDemandesImagerieEnvoyees);

        // Sérialisation profonde (gère ordonnance[0][medicament])
        const data = gyDeepSerialize(form);

        try {
            // Appel au contrôleur dédié (même logique que consultation/sauvegarder étape finale)
            const resp = await fetch(BASE_URL + 'consultation-gyneco/finaliser', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ patient_id: PATIENT_ID, data })
            });
            const r = await resp.json();
            if (r.success) {
                gyLocalClear(); // effacer le brouillon local
                window.location.href = BASE_URL + 'formulaire/recap-gyneco/' + r.formulaire_id;
            } else {
                gyToast('❌ Erreur : ' + (r.message || 'Erreur inconnue'), 'danger');
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Terminer et Enregistrer'; }
            }
        } catch(err) {
            gyToast('❌ Erreur réseau', 'danger');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Terminer et Enregistrer'; }
        }
    }, true); // capture phase = true → s'exécute avant le listener injecté par FormulaireController
});

/* ══════════════════════════════════════════════════════════════════
   IMPRESSION — Zone commune (injectée dans la page, puis window.print)
   ══════════════════════════════════════════════════════════════════ */
// Données médecin injectées depuis PHP
const GY_SIG_B64     = <?= json_encode($signatureB64  ?? null) ?>;
const GY_CACHET_B64  = <?= json_encode($cachetB64     ?? null) ?>;
const GY_MEDECIN_NOM = <?= json_encode($medecinNomSig ?? '') ?>;
const GY_MEDECIN_SPEC= <?= json_encode($medecinSpec   ?? '') ?>;
const GY_MEDECIN_ORD = <?= json_encode($medecinOrdre  ?? '') ?>;
const GY_PATIENT_NOM = <?= json_encode(($patient['nom']??'').' '.($patient['prenom']??'')) ?>;
const GY_PATIENT_DOS = <?= json_encode($patient['dossier_numero'] ?? '') ?>;
const GY_DATE_TODAY  = <?= json_encode(date('d/m/Y')) ?>;

function gyBlocSignature() {
    if (!GY_SIG_B64 && !GY_CACHET_B64) return '';

    // Bloc cachet + signature superposée
    let stampHtml = '';
    if (GY_CACHET_B64 || GY_SIG_B64) {
        stampHtml = `<div style="position:relative;display:inline-block;width:120px;height:120px;margin-top:8px;">`;
        if (GY_CACHET_B64) {
            stampHtml += `<img src="${GY_CACHET_B64}"
                style="width:120px;height:120px;display:block;object-fit:contain;opacity:0.85;">`;
        }
        if (GY_SIG_B64) {
            stampHtml += `<img src="${GY_SIG_B64}"
                style="position:absolute;top:50%;left:50%;
                       transform:translate(-50%,-50%);
                       max-width:110px;max-height:90px;
                       object-fit:contain;opacity:0.92;">`;
        }
        stampHtml += `</div>`;
    }

    return `<div style="margin-top:40px;display:flex;justify-content:flex-end;align-items:flex-end;">
        <div style="text-align:center;">
            <div style="font-size:11px;color:#64748b;margin-bottom:4px;">Fait le ${GY_DATE_TODAY}</div>
            <div style="font-size:11px;font-weight:700;">${GY_MEDECIN_NOM}</div>
            ${GY_MEDECIN_SPEC ? `<div style="font-size:10px;color:#64748b;">${GY_MEDECIN_SPEC}</div>` : ''}
            ${GY_MEDECIN_ORD  ? `<div style="font-size:10px;color:#64748b;">N° Ordre : ${GY_MEDECIN_ORD}</div>` : ''}
            <div style="display:flex;justify-content:center;">${stampHtml}</div>
        </div>
    </div>`;
}

function gyEntete(titre, couleur) {
    return `<div style="font-family:Arial,sans-serif;max-width:800px;margin:0 auto;padding:30px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid ${couleur};padding-bottom:12px;margin-bottom:20px;">
            <div>
                <div style="font-weight:900;font-size:16px;color:${couleur};">${titre}</div>
                <div style="font-size:11px;color:#64748b;margin-top:2px;">Hôpital Saint-Jean de Malte — Njombé</div>
            </div>
            <div style="text-align:right;font-size:11px;color:#64748b;">
                <div><strong>Patient :</strong> ${GY_PATIENT_NOM}</div>
                <div><strong>N° Dossier :</strong> ${GY_PATIENT_DOS}</div>
                <div><strong>Date :</strong> ${GY_DATE_TODAY}</div>
            </div>
        </div>`;
}

function gyImprimerOrdonnance() {
    const rows = document.querySelectorAll('#tblOrdoBody tr');
    let lignes = '';
    rows.forEach((tr, idx) => {
        const inputs = tr.querySelectorAll('input,select');
        if (inputs.length >= 5 && inputs[0].value.trim()) {
            lignes += `<div style="padding:8px 0;border-bottom:1px dashed #e2e8f0;">
                <div style="font-weight:700;font-size:13px;">${idx+1}. ${inputs[0].value}${inputs[1].value ? ' — ' + inputs[1].value : ''}</div>
                <div style="font-size:12px;color:#475569;margin-top:3px;">
                    Voie : ${inputs[2].value} &nbsp;·&nbsp; ${inputs[3].value} &nbsp;·&nbsp; Durée : ${inputs[4].value}
                </div>
            </div>`;
        }
    });
    const instructions = gyGetVal('instructions_patient');

    const html = gyEntete('ORDONNANCE MÉDICALE', '#db2777')
        + `<div style="font-size:11px;margin-bottom:16px;color:#64748b;">
            ${gyGetVal('diagnostic') ? '<strong>Diagnostic :</strong> ' + gyGetVal('diagnostic') + '<br>' : ''}
           </div>`
        + (lignes || '<p style="color:#94a3b8;font-style:italic;">Aucun médicament prescrit.</p>')
        + (instructions ? `<div style="margin-top:14px;font-size:11px;background:#f8fafc;padding:10px;border-radius:6px;"><strong>Instructions :</strong><br>${instructions}</div>` : '')
        + gyBlocSignature()
        + '</div>';
    gyOuvrirPrint(html);
}

function gyImprimerBilanLabo() {
    // Utiliser les examens encore en liste OU ceux déjà envoyés (sauvegardés)
    const examens = gyExamensLabo.length ? gyExamensLabo : gyExamensLaboEnvoyes;
    if (!examens.length) {
        gyToast('Aucun examen de laboratoire à imprimer.', 'danger'); return;
    }
    let lignes = `<table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead><tr style="background:#eff6ff;">
            <th style="padding:8px;border:1px solid #ddd;text-align:left;">Examen</th>
            <th style="padding:8px;border:1px solid #ddd;text-align:left;">Catégorie</th>
            <th style="padding:8px;border:1px solid #ddd;text-align:left;">Prélèvement</th>
            <th style="padding:8px;border:1px solid #ddd;text-align:left;">Instructions</th>
            <th style="padding:8px;border:1px solid #ddd;text-align:center;">Urgence</th>
        </tr></thead><tbody>`;
    examens.forEach(ex => {
        lignes += `<tr>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;font-weight:600;">${ex.nom}</td>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;">${ex.categorie||'—'}</td>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;">${ex.type_prelevement||'—'}</td>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;">${ex.instructions||''}${ex.a_jeun?'<br><strong style="color:#d97706">⏰ À jeun</strong>':''}</td>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;text-align:center;">${ex.urgent?'<strong style="color:#dc2626">URGENT</strong>':'Normal'}</td>
        </tr>`;
    });
    lignes += '</tbody></table>';

    const notes = gyGetVal('notes_bilans');
    const html = gyEntete('DEMANDE D\'EXAMENS — LABORATOIRE', '#1d4ed8')
        + (notes ? `<div style="margin-bottom:12px;font-size:11px;background:#f8fafc;padding:10px;border-radius:6px;"><strong>Notes :</strong> ${notes}</div>` : '')
        + lignes
        + gyBlocSignature()
        + '</div>';
    gyOuvrirPrint(html);
}

function gyImprimerBilanImagerie() {
    // Utiliser les demandes encore en liste OU celles déjà envoyées (sauvegardées)
    const demandes = gyDemandesImagerie.length ? gyDemandesImagerie : gyDemandesImagerieEnvoyees;
    if (!demandes.length) {
        gyToast('Aucune demande d\'imagerie à imprimer.', 'danger'); return;
    }
    let lignes = `<table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead><tr style="background:#f0fdf4;">
            <th style="padding:8px;border:1px solid #ddd;text-align:left;">Modalité</th>
            <th style="padding:8px;border:1px solid #ddd;text-align:left;">Région</th>
            <th style="padding:8px;border:1px solid #ddd;text-align:left;">Indication clinique</th>
            <th style="padding:8px;border:1px solid #ddd;text-align:center;">Urgence</th>
            <th style="padding:8px;border:1px solid #ddd;text-align:left;">Instructions</th>
        </tr></thead><tbody>`;
    demandes.forEach(d => {
        lignes += `<tr>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;font-weight:600;">${gyIconeModalite[d.modalite]||'📋'} ${d.modalite.charAt(0).toUpperCase()+d.modalite.slice(1)}</td>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;">${d.partie_corps}${d.cote?' ('+d.cote+')':''}</td>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;">${d.indication}</td>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;text-align:center;">${d.urgence==='URGENT'?'<strong style="color:#dc2626">URGENT</strong>':d.urgence==='TRES_URGENT'?'<strong style="color:#7c3aed">TRÈS URGENT</strong>':'Normal'}</td>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;">${d.instructions||'—'}${d.avec_contraste?'<br><em style="color:#0ea5e9">+ Produit de contraste</em>':''}</td>
        </tr>`;
    });
    lignes += '</tbody></table>';

    const html = gyEntete('DEMANDE D\'EXAMENS — IMAGERIE', '#15803d')
        + lignes
        + gyBlocSignature()
        + '</div>';
    gyOuvrirPrint(html);
}

function gyOuvrirPrint(html) {
    const w = window.open('', '_blank',
        'width=900,height=700,scrollbars=yes,menubar=no,toolbar=no');
    if (!w) { gyToast('Autorisez les fenêtres pop-up pour imprimer.', 'danger'); return; }
    w.document.write(`<!DOCTYPE html><html lang="fr"><head>
        <meta charset="UTF-8">
        <title>Impression — Gynécologie</title>
        <style>
            body { font-family: Arial, Helvetica, sans-serif; color: #1e293b; margin:0; padding:0; }
            @media print {
                @page { margin: 15mm 15mm; }
                body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            }
        </style>
    </head><body>${html}
    <script>window.onload=function(){window.print();}<\/script>
    </body></html>`);
    w.document.close();
}

/* ══ Sauvegarder en brouillon (submit silencieux) ═══════════════════ */
function sauvegarderBrouillon() {
    document.getElementById('formGyneco').submit();
}

/* ══ Calcul IMC automatique ════════════════════════════════════════ */
(function() {
    function majIMC() {
        const p = parseFloat(document.querySelector('[name=poids]')?.value);
        const t = parseFloat(document.querySelector('[name=taille]')?.value);
        const f = document.getElementById('imcField');
        const b = document.getElementById('imcBadge');
        if (!f) return;
        if (p > 0 && t > 0) {
            const imc = p / ((t / 100) ** 2);
            f.value = imc.toFixed(1);
            if (b) {
                if      (imc < 18.5) b.innerHTML = '<span style="color:#0ea5e9;font-weight:700;">⬇ Insuffisance pondérale</span>';
                else if (imc < 25)   b.innerHTML = '<span style="color:#16a34a;font-weight:700;">✓ IMC normal</span>';
                else if (imc < 30)   b.innerHTML = '<span style="color:#f59e0b;font-weight:700;">⚠ Surpoids</span>';
                else                 b.innerHTML = '<span style="color:#dc2626;font-weight:700;">⚠ Obésité</span>';
            }
        }
    }
    document.querySelector('[name=poids]')?.addEventListener('input', majIMC);
    document.querySelector('[name=taille]')?.addEventListener('input', majIMC);
    majIMC();
})();

/* ══ Badge température ══════════════════════════════════════════════ */
function checkTempGy(val) {
    const badge = document.getElementById('tempBadgeGy');
    if (!badge) return;
    const t = parseFloat(val);
    if (isNaN(t) || !val) { badge.innerHTML = ''; return; }
    if      (t >= 39.5) badge.innerHTML = '<span style="color:#dc2626;font-weight:700;">🚨 Hyperthermie sévère</span>';
    else if (t >= 38.5) badge.innerHTML = '<span style="color:#f59e0b;font-weight:700;">⚠ Fièvre élevée</span>';
    else if (t >= 37.5) badge.innerHTML = '<span style="color:#f97316;font-weight:700;">🌡 Fièvre</span>';
    else if (t < 36)    badge.innerHTML = '<span style="color:#0ea5e9;font-weight:700;">❄ Hypothermie</span>';
    else                badge.innerHTML = '<span style="color:#16a34a;font-weight:700;">✓ Normale</span>';
}

/* ══ Badge TA ════════════════════════════════════════════════════════ */
document.querySelector('[name=ta]')?.addEventListener('input', function() {
    const badge = document.getElementById('taBadge');
    if (!badge) return;
    const m = this.value.match(/^(\d+)\s*\/\s*(\d+)$/);
    if (!m) { badge.innerHTML = ''; return; }
    const sys = parseInt(m[1]), dia = parseInt(m[2]);
    if      (sys >= 180 || dia >= 120) badge.innerHTML = '<span style="color:#dc2626;font-weight:700;">🚨 HTA sévère</span>';
    else if (sys >= 160 || dia >= 100) badge.innerHTML = '<span style="color:#f59e0b;font-weight:700;">⚠ HTA modérée</span>';
    else if (sys >= 140 || dia >= 90)  badge.innerHTML = '<span style="color:#f97316;font-weight:700;">⚠ HTA légère</span>';
    else if (sys < 90  || dia < 60)   badge.innerHTML = '<span style="color:#0ea5e9;font-weight:700;">⬇ Hypotension</span>';
    else                               badge.innerHTML = '<span style="color:#16a34a;font-weight:700;">✓ Normale</span>';
});

/* ══ Badge BDCF ════════════════════════════════════════════════════ */
function checkBDCF(val) {
    const badge = document.getElementById('bdcfBadge');
    if (!badge) return;
    const v = parseInt(val);
    if (isNaN(v) || !val) { badge.innerHTML = ''; return; }
    if      (v < 100) badge.innerHTML = '<span style="color:#dc2626;font-weight:700;">🚨 Bradycardie fœtale</span>';
    else if (v < 120) badge.innerHTML = '<span style="color:#f59e0b;font-weight:700;">⚠ Limite basse</span>';
    else if (v > 180) badge.innerHTML = '<span style="color:#dc2626;font-weight:700;">🚨 Tachycardie fœtale</span>';
    else if (v > 160) badge.innerHTML = '<span style="color:#f59e0b;font-weight:700;">⚠ Limite haute</span>';
    else              badge.innerHTML = '<span style="color:#16a34a;font-weight:700;">✓ Normal (120–160)</span>';
}

/* ══ Calcul automatique âge gestationnel depuis DDR ════════════════ */
document.querySelector('[name=ddr]')?.addEventListener('change', function() {
    if (!this.value) return;
    const ddr  = new Date(this.value);
    const now  = new Date();
    const days = Math.floor((now - ddr) / 86400000);
    if (days < 0 || days > 300) return;
    const sa = Math.floor(days / 7);
    const j  = days % 7;
    const hint = document.getElementById('saAutoHint');
    const txt  = document.getElementById('saAutoText');
    const field = document.getElementById('saField');
    if (hint && txt) {
        txt.textContent = `Âge calculé : ${sa} SA + ${j} j`;
        hint.style.display = 'flex';
    }
    if (field && !field.value) field.value = `${sa} SA`;
});

/* ══ Ajout dynamique de lignes (ordonnance uniquement — bilans via modals) ═ */
let _rowCounts = {
    ordo: <?= count($ordonnance ?? []) ?>
};

/* ajouterLigne — remplacé par les modals dynamiques en step 3 */

function ajouterMedicament() {
    const idx   = _rowCounts.ordo++;
    const tbody = document.getElementById('tblOrdoBody');
    const tr    = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="ordonnance[${idx}][medicament]" list="dl-medoc" placeholder="Médicament…"></td>
        <td><input type="text" name="ordonnance[${idx}][dosage]" list="dl-dosage" placeholder="500mg"></td>
        <td><select name="ordonnance[${idx}][voie]">
            ${['Oral','IV','IM','SC','Topique','Vaginal','Rectal','Sublingual','Inhalation'].map(v=>`<option>${v}</option>`).join('')}
        </select></td>
        <td><input type="text" name="ordonnance[${idx}][frequence]" list="dl-frequence" placeholder="2x/jour"></td>
        <td><input type="text" name="ordonnance[${idx}][duree]" list="dl-duree" placeholder="7 jours"></td>
        <td style="text-align:center;"><button type="button" class="btn-del-row-sm" onclick="this.closest('tr').remove()"><i class="bi bi-x-lg"></i></button></td>`;
    tbody.appendChild(tr);
    tr.querySelector('input')?.focus();
}

/* ══ Toggle sections step 5 ════════════════════════════════════════ */
function toggleS5(id) {
    const el = document.getElementById(id);
    el.classList.toggle('open');
}

/* ══ Upload de documents ════════════════════════════════════════════ */
const uploadZone  = document.getElementById('uploadZone');
const uploadInput = document.getElementById('uploadInput');

['dragenter','dragover'].forEach(ev => uploadZone?.addEventListener(ev, e => {
    e.preventDefault(); uploadZone.classList.add('drag-over');
}));
['dragleave','drop'].forEach(ev => uploadZone?.addEventListener(ev, e => {
    e.preventDefault(); uploadZone?.classList.remove('drag-over');
    if (ev === 'drop') uploadFiles(e.dataTransfer.files);
}));
uploadInput?.addEventListener('change', () => uploadFiles(uploadInput.files));

function uploadFiles(files) {
    if (!files || !files.length) return;
    const msg  = document.getElementById('uploadMessage');
    const prog = document.getElementById('uploadProgress');
    const bar  = document.getElementById('uploadProgressBar');
    const cat  = document.getElementById('uploadCategorie').value;
    const desc = document.getElementById('uploadDescription').value;

    Array.from(files).forEach(file => {
        const fd = new FormData();
        fd.append('document',    file);
        fd.append('patient_id',  PATIENT_ID);
        fd.append('categorie',   cat);
        fd.append('description', desc);

        prog.style.display = 'block'; bar.style.width = '10%';
        msg.innerHTML = `<span style="color:#0d9488;"><i class="bi bi-upload"></i> Envoi de <strong>${file.name}</strong>…</span>`;

        const xhr = new XMLHttpRequest();
        xhr.upload.onprogress = e => { if (e.lengthComputable) bar.style.width = (e.loaded/e.total*90+10) + '%'; };
        xhr.onload = () => {
            bar.style.width = '100%';
            try {
                const r = JSON.parse(xhr.responseText);
                if (r.success) {
                    msg.innerHTML = `<span style="color:#059669;"><i class="bi bi-check-circle-fill"></i> <strong>${file.name}</strong> importé.</span>`;
                    chargerDocuments();
                } else {
                    msg.innerHTML = `<span style="color:#dc2626;"><i class="bi bi-x-circle-fill"></i> Erreur : ${r.message}</span>`;
                }
            } catch(e) { msg.innerHTML = `<span style="color:#dc2626;">Erreur de communication.</span>`; }
            setTimeout(() => { prog.style.display='none'; bar.style.width='0'; }, 1500);
            uploadInput.value = '';
        };
        xhr.onerror = () => { msg.innerHTML = `<span style="color:#dc2626;">Échec de l'envoi.</span>`; prog.style.display='none'; };
        xhr.open('POST', BASE_URL + 'formulaire/upload-document-ajax');
        xhr.send(fd);
    });
}

/* ══ Chargement liste documents ═════════════════════════════════════ */
function chargerDocuments() {
    const cat  = document.getElementById('filterCategorie')?.value || '';
    const url  = BASE_URL + 'formulaire/documents-patient/' + PATIENT_ID + (cat ? '?categorie=' + encodeURIComponent(cat) : '');
    const list = document.getElementById('docList');
    if (!list) return;

    fetch(url).then(r => r.json()).then(data => {
        if (!data.success || !data.documents.length) {
            list.innerHTML = `<div style="text-align:center;color:#94a3b8;padding:20px;font-size:.85rem;">
                <i class="bi bi-folder2 d-block mb-2 fs-3"></i>Aucun document.</div>`;
            updateDocBadge(0); return;
        }
        updateDocBadge(data.documents.length);
        list.innerHTML = data.documents.map(doc => `
            <div class="doc-item" id="docItem_${doc.id}">
                <div class="doc-thumb">${doc.est_image ? `<img src="${doc.url}" alt="${doc.nom_fichier}">` : iconForExt(doc.ext)}</div>
                <div class="doc-info">
                    <div class="doc-name">${doc.nom_fichier}</div>
                    <div class="doc-meta">
                        <span style="background:#f1f5f9;border-radius:8px;padding:1px 8px;font-size:.7rem;font-weight:700;">${doc.categorie}</span>
                        ${doc.description ? ' — ' + doc.description : ''} · ${doc.date_fmt}
                    </div>
                </div>
                <div class="doc-actions">
                    <a href="${doc.url}" target="_blank" class="btn-doc btn-doc-view"><i class="bi bi-eye"></i> Voir</a>
                    <a href="${doc.url}" download="${doc.nom_fichier}" class="btn-doc btn-doc-view"><i class="bi bi-download"></i></a>
                </div>
            </div>`).join('');
    }).catch(() => {
        list.innerHTML = `<div style="color:#dc2626;padding:12px;">Erreur chargement.</div>`;
    });
}

function updateDocBadge(n) {
    ['docCountBadge','docCountBadge2'].forEach(id => {
        const b = document.getElementById(id);
        if (b) { b.textContent = n; b.style.display = n > 0 ? 'inline' : 'none'; }
    });
}

function iconForExt(ext) {
    const icons = { pdf:'bi-file-pdf text-danger', doc:'bi-file-word text-primary', docx:'bi-file-word text-primary',
        xls:'bi-file-excel text-success', xlsx:'bi-file-excel text-success',
        mp4:'bi-file-play text-warning', mov:'bi-file-play text-warning' };
    return `<i class="bi ${icons[ext] || 'bi-file-earmark'}" style="font-size:1.4rem;"></i>`;
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
