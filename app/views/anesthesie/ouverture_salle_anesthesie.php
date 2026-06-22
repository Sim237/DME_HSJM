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

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../services/CsrfService.php';
$salles = $salles ?? [];
$ok     = isset($_GET['ok']);
?>
<style>
body { background:#f0f4f8; font-family:'Inter','Segoe UI',sans-serif; color:#1e293b; }
.sidebar, nav.sidebar { display:none !important; }
main,.col-md-10,.ms-sm-auto { margin-left:0!important; width:100%!important; max-width:100%!important; flex:0 0 100%!important; padding:0!important; }

/* ── Top bar ── */
.os-top {
    background:#fff; padding:12px 26px;
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
    position:sticky; top:0; z-index:50;
    border-bottom:1px solid #e2e8f0; box-shadow:0 2px 14px rgba(0,0,0,.06);
}
.os-brand { display:flex; align-items:center; gap:11px; }
.os-brand .ico {
    width:40px; height:40px; border-radius:11px; flex-shrink:0;
    background:linear-gradient(135deg,#0891b2,#0e7490);
    display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.1rem;
}
.os-brand .title { font-size:.98rem; font-weight:800; color:#1e293b; line-height:1.2; }
.os-brand .sub   { font-size:.69rem; color:#64748b; font-weight:500; }
.os-clock {
    background:#f1f5f9; border-radius:10px; padding:5px 12px;
    font-family:'Courier New',monospace; font-size:.78rem; font-weight:800;
    color:#0891b2; letter-spacing:2px;
}
.os-btn {
    padding:7px 14px; border-radius:9px; font-weight:600; font-size:.78rem;
    text-decoration:none; border:1.5px solid #e2e8f0; cursor:pointer;
    background:#fff; color:#475569; transition:all .15s;
    display:inline-flex; align-items:center; gap:6px;
}
.os-btn:hover { background:#f1f5f9; color:#1e293b; }
.os-btn.accent {
    background:linear-gradient(135deg,#0891b2,#0e7490);
    border-color:transparent; color:#fff; font-weight:700;
    box-shadow:0 4px 14px rgba(8,145,178,.35);
}
.os-btn.accent:hover { opacity:.88; color:#fff; }

/* ── Layout ── */
.wrap { max-width:960px; margin:0 auto; padding:24px 20px 90px; }

/* ── Section cards ── */
.s-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; box-shadow:0 2px 10px rgba(0,0,0,.05); margin-bottom:18px; overflow:hidden; }
.s-card-head {
    padding:12px 18px; border-bottom:1px solid #f1f5f9;
    display:flex; align-items:center; gap:8px;
    font-size:.78rem; font-weight:800; color:#fff;
    text-transform:uppercase; letter-spacing:.5px;
}
.s-card-body { padding:0; }

/* ── Table ── */
.cl-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.cl-table th {
    background:#f8fafc; color:#64748b; font-size:.7rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.4px; padding:9px 14px;
    border-bottom:2px solid #e2e8f0; text-align:left;
}
.cl-table th.col-statut { text-align:center; width:160px; }
.cl-table th.col-obs    { width:200px; }
.cl-table td { border-bottom:1px solid #f1f5f9; padding:10px 14px; vertical-align:middle; }
.cl-table tr:last-child td { border-bottom:none; }
.cl-table tr:hover td { background:#fafcff; }
.cl-table .item-label { font-size:.82rem; color:#374151; }
.cl-table .item-detail { font-size:.72rem; color:#94a3b8; margin-top:2px; }

/* ── Statut radios ── */
.st-group { display:flex; flex-wrap:wrap; justify-content:center; gap:4px; }
.st-lbl {
    display:inline-flex; align-items:center; justify-content:center; gap:3px;
    padding:3px 9px; border-radius:20px; cursor:pointer;
    border:1.5px solid #e2e8f0; font-size:.68rem; font-weight:700;
    transition:all .15s; background:#fff; user-select:none; white-space:nowrap;
    color:#64748b;
}
.st-lbl input { position:absolute; opacity:0; width:0; height:0; }
.st-lbl:has(input:checked) { border-color:transparent; }
.st-lbl.ok:has(input:checked)  { background:#dcfce7; color:#16a34a; border-color:#86efac; }
.st-lbl.warn:has(input:checked){ background:#fef9c3; color:#b45309; border-color:#fde68a; }
.st-lbl.bad:has(input:checked) { background:#fee2e2; color:#dc2626; border-color:#fca5a5; }

/* ── Oui/Non ── */
.yn-group { display:flex; justify-content:center; gap:6px; }
.yn-lbl {
    display:inline-flex; align-items:center; justify-content:center;
    width:44px; height:30px; border-radius:8px; cursor:pointer;
    border:1.5px solid #e2e8f0; font-size:.72rem; font-weight:700;
    transition:all .15s; background:#fff; user-select:none;
}
.yn-lbl input { position:absolute; opacity:0; width:0; height:0; }
.yn-lbl.oui:has(input:checked) { background:#dcfce7; color:#16a34a; border-color:#86efac; }
.yn-lbl.non:has(input:checked) { background:#fee2e2; color:#dc2626; border-color:#fca5a5; }

/* ── Obs ── */
.obs-inp {
    width:100%; padding:5px 9px; border-radius:7px;
    border:1.5px solid #e2e8f0; font-size:.75rem; outline:none;
    transition:border-color .15s; color:#374151;
}
.obs-inp:focus { border-color:#0891b2; }

/* ── Submit bar ── */
.submit-bar {
    position:fixed; bottom:0; left:0; right:0;
    background:#fff; border-top:1px solid #e2e8f0;
    padding:12px 24px; display:flex; align-items:center; justify-content:space-between;
    box-shadow:0 -4px 20px rgba(0,0,0,.08); z-index:100;
}

/* Section colors */
.head-i    { background:linear-gradient(135deg,#4f46e5,#6366f1); }
.head-ii   { background:linear-gradient(135deg,#0891b2,#0e7490); }
.head-iii  { background:linear-gradient(135deg,#059669,#10b981); }
.head-iv   { background:linear-gradient(135deg,#7c3aed,#8b5cf6); }
.head-v    { background:linear-gradient(135deg,#dc2626,#ef4444); }
.head-vi   { background:linear-gradient(135deg,#b45309,#d97706); }
.head-vii  { background:linear-gradient(135deg,#64748b,#94a3b8); }
</style>

<!-- TOP BAR -->
<div class="os-top">
    <div class="os-brand">
        <div class="ico"><i class="bi bi-clipboard2-check-fill"></i></div>
        <div>
            <div class="title">Check-list d'ouverture de salle d'anesthésie</div>
            <div class="sub">Vérification pré-intervention · HSJM</div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="os-clock" id="osClock">--:--:--</div>
        <a href="<?= BASE_URL ?>dashboard" class="os-btn"><i class="bi bi-arrow-left"></i> Retour</a>
    </div>
</div>

<div class="wrap">

<?php if ($ok): ?>
<div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:14px;padding:14px 20px;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:#15803d;font-weight:700;">
    <i class="bi bi-check-circle-fill fs-5"></i> Check-list enregistrée avec succès.
    <a href="<?= BASE_URL ?>anesthesie/ouverture-salle" style="margin-left:auto;font-size:.78rem;color:#0891b2;text-decoration:none;font-weight:600;">Nouvelle fiche →</a>
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>anesthesie/ouverture-salle/sauvegarder" id="formOuverture">
<?= CsrfService::field() ?>

<!-- ── Informations générales ── -->
<div class="s-card">
    <div class="s-card-head head-i"><i class="bi bi-info-circle-fill"></i> Informations générales</div>
    <div class="s-card-body" style="padding:18px;">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Date</label>
                <input type="date" name="date_fiche" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Salle</label>
                <select name="nom_salle" class="form-select rounded-3">
                    <option value="">— Saisir manuellement —</option>
                    <?php foreach ($salles as $s): ?>
                    <option value="<?= htmlspecialchars($s['nom_salle']) ?>"><?= htmlspecialchars($s['nom_salle']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted">IBAA / IADE / Anesthésiste</label>
                <input type="text" name="personnel" class="form-control rounded-3"
                       value="<?= htmlspecialchars(($_SESSION['user_prenom']??'').' '.($_SESSION['user_nom']??'')) ?>"
                       placeholder="Nom du personnel">
            </div>
        </div>
    </div>
</div>

<?php
// ─────────────────────────────────────────────────────────────────────────────
// Helper : render a table row
// $key     = field name prefix
// $label   = libellé principal
// $detail  = sous-libellé optionnel
// $type    = 'statut' | 'ouinon' | 'statut_ouinon'
// $statuts = array of ['val','label','cls']
// ─────────────────────────────────────────────────────────────────────────────
function row(string $key, string $label, string $detail='', string $type='statut', array $statuts=[]): void {
    $defStatuts = $statuts ?: [
        ['val'=>'ok',  'label'=>'✓ OK',        'cls'=>'ok'],
        ['val'=>'nok', 'label'=>'✗ Non OK',     'cls'=>'bad'],
        ['val'=>'na',  'label'=>'N/A',           'cls'=>'warn'],
    ];
    echo '<tr>';
    echo '<td class="item-label">'.htmlspecialchars($label).($detail ? '<div class="item-detail">'.htmlspecialchars($detail).'</div>' : '').'</td>';
    echo '<td class="col-statut">';
    if ($type === 'ouinon' || $type === 'statut_ouinon') {
        echo '<div class="yn-group">';
        echo '<label class="yn-lbl oui"><input type="radio" name="'.$key.'_statut" value="oui"> Oui</label>';
        echo '<label class="yn-lbl non"><input type="radio" name="'.$key.'_statut" value="non"> Non</label>';
        echo '</div>';
    } else {
        echo '<div class="st-group">';
        foreach ($defStatuts as $s) {
            echo '<label class="st-lbl '.$s['cls'].'"><input type="radio" name="'.$key.'_statut" value="'.$s['val'].'"> '.$s['label'].'</label>';
        }
        echo '</div>';
    }
    echo '</td>';
    echo '<td><input type="text" name="'.$key.'_obs" class="obs-inp" placeholder="Observation…"></td>';
    echo '</tr>';
}

$DISPONIBLE  = [['val'=>'disponible','label'=>'Disponible','cls'=>'ok'],['val'=>'manquant','label'=>'Manquant','cls'=>'bad'],['val'=>'na','label'=>'N/A','cls'=>'warn']];
$CONFORME    = [['val'=>'conforme','label'=>'Conforme','cls'=>'ok'],['val'=>'non_conforme','label'=>'Non conforme','cls'=>'bad']];
$FONCTIONNEL = [['val'=>'fonctionnel','label'=>'Fonctionnel','cls'=>'ok'],['val'=>'defaillant','label'=>'Défaillant','cls'=>'bad'],['val'=>'na','label'=>'N/A','cls'=>'warn']];
$TESTE       = [['val'=>'teste','label'=>'Testé ✓','cls'=>'ok'],['val'=>'non_teste','label'=>'Non testé','cls'=>'bad']];
$OPERATIONNEL= [['val'=>'operationnel','label'=>'Opérationnel','cls'=>'ok'],['val'=>'non_op','label'=>'Non op.','cls'=>'bad'],['val'=>'verifie','label'=>'Vérifié','cls'=>'warn']];
?>

<!-- ══ I - OUVERTURE DE LA SALLE ══ -->
<div class="s-card">
    <div class="s-card-head head-i"><i class="bi bi-door-open-fill"></i> I — Ouverture de la salle</div>
    <div class="s-card-body">
        <table class="cl-table">
            <thead><tr><th>Éléments à vérifier / Matériel à préparer</th><th class="col-statut">Statut</th><th class="col-obs">Observations</th></tr></thead>
            <tbody>
            <?php
            row('fiche_anesthesie', 'Fiche d\'anesthésie', '', 'statut', $DISPONIBLE);
            row('hygiene_salle',    'Hygiène de la salle', '', 'statut', $CONFORME);
            row('table_operatoire', 'Table opératoire', '', 'statut', $CONFORME);
            row('scialytique',      'Scialytique', '', 'statut', $FONCTIONNEL);
            row('oxygene_mural',    'Oxygène mural / bouteille d\'oxygène', '', 'statut', $OPERATIONNEL);
            ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══ II - ACCUEIL + INSTALLATION ══ -->
<div class="s-card">
    <div class="s-card-head head-ii"><i class="bi bi-person-check-fill"></i> II — Accueil + Installation</div>
    <div class="s-card-body">
        <table class="cl-table">
            <thead><tr><th>Éléments à vérifier / Matériel à préparer</th><th class="col-statut">Statut</th><th class="col-obs">Observations</th></tr></thead>
            <tbody>
            <?php
            row('identite_patient',  'Vérification de l\'identité du patient', '', 'statut', $CONFORME);
            row('vpo',               'VPO (Visite Pré-Opératoire)', '', 'ouinon');
            row('jeun_preop',        'Vérification du jeûn pré-opératoire', '', 'ouinon');
            row('preparation_faite', 'Préparation faite', '', 'ouinon');
            row('dossier_medical',   'Dossier médical complet + bilan OK', '', 'statut', $CONFORME);
            row('prothese',          'Prothèse', 'Retirée si présente', 'ouinon');
            row('brassard_pa',       'PA : brassard adapté', '', 'statut', $FONCTIONNEL);
            ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══ III - MONITORAGE ══ -->
<div class="s-card">
    <div class="s-card-head head-iii"><i class="bi bi-heart-pulse-fill"></i> III — Monitorage</div>
    <div class="s-card-body">
        <table class="cl-table">
            <thead><tr><th>Éléments à vérifier / Matériel à préparer</th><th class="col-statut">Statut</th><th class="col-obs">Observations</th></tr></thead>
            <tbody>
            <?php
            row('ecg_electrodes', 'FC + ECG : électrodes + stéthoscope', '', 'statut', $FONCTIONNEL);
            row('spo2_capteur',   'FR + SpO₂ : capteur opérationnel', '', 'statut', $FONCTIONNEL);
            row('thermometre',    'T°C : thermomètre disponible', '', 'statut', $FONCTIONNEL);
            row('catheters',      'Cathéters', '', 'statut', $DISPONIBLE);
            ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══ IV - VOIE VEINEUSE PÉRIPHÉRIQUE ══ -->
<div class="s-card">
    <div class="s-card-head head-iv"><i class="bi bi-droplet-fill"></i> IV — Voie Veineuse Périphérique (VVP)</div>
    <div class="s-card-body">
        <table class="cl-table">
            <thead><tr><th>Éléments à vérifier / Matériel à préparer</th><th class="col-statut">Statut</th><th class="col-obs">Observations</th></tr></thead>
            <tbody>
            <?php
            row('perfuseurs_robinet',  'Perfuseurs + robinet à 3 voies', '', 'statut', $DISPONIBLE);
            row('transfuseur',         'Transfuseur', '', 'statut', $DISPONIBLE);
            row('solutes_remplissage', 'Solutés de remplissage',
                'NaCl 0.9% 500cc · Ringer Lactate · SG 5% · SG 10%', 'statut', $DISPONIBLE);
            ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══ V - SÉCURISATION VOIES AÉRIENNES ══ -->
<div class="s-card">
    <div class="s-card-head head-v"><i class="bi bi-wind"></i> V — Sécurisation des voies aériennes + Assistance</div>
    <div class="s-card-body">
        <table class="cl-table">
            <thead><tr><th>Éléments à vérifier / Matériel à préparer</th><th class="col-statut">Statut</th><th class="col-obs">Observations</th></tr></thead>
            <tbody>
            <?php
            row('ventilation_colonne', 'Ventilation',
                'Colonne d\'anesthésie · circuit étanche · filtre antibactérien · poumon artificiel',
                'statut', $TESTE);
            row('aspiration_aspirateur', 'Aspiration',
                'Aspirateur · bocal · tuyau · 02 sondes d\'aspiration · raccord',
                'statut', [['val'=>'disponible_pret','label'=>'Disponible et prêt','cls'=>'ok'],['val'=>'non_pret','label'=>'Non prêt','cls'=>'bad']]);
            row('sng', 'SNG : sonde nasogastrique', '', 'statut', $DISPONIBLE);
            row('intubation_kit', 'Intubation',
                '01 manche · 03 lames laryngoscope · 03 sondes d\'intubation · 02 masques faciaux · 02 canules de Guédel · pince de Magill · seringue vide · mandrin · BAVU · stéthoscope · sparadrap',
                'statut', $DISPONIBLE);
            ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══ VI - DROGUES ANESTHÉSIQUES ══ -->
<div class="s-card">
    <div class="s-card-head head-vi"><i class="bi bi-capsule-pill"></i> VI — Drogues anesthésiques</div>
    <div class="s-card-body">
        <table class="cl-table">
            <thead><tr><th>Éléments à vérifier / Matériel à préparer</th><th class="col-statut">Statut</th><th class="col-obs">Observations</th></tr></thead>
            <tbody>
            <?php
            row('premed_midazolam', 'Prémédication', 'Midazolam · Diazépam', 'statut', $DISPONIBLE);
            row('ag_hypnotiques',   'AG — Hypnotiques', 'Kétamine · Propofol · Thiopental · Étomidate', 'statut', $DISPONIBLE);
            row('ag_morphiniques',  'AG — Morphiniques', 'Fentanyl', 'statut', $DISPONIBLE);
            row('ag_curares',       'AG — Curares', 'Célocurine · Rocuronium · Vécuronium', 'statut', $DISPONIBLE);
            row('ag_halogenes',     'AG — Halogénés', 'Isoflurane', 'statut', $DISPONIBLE);
            row('alr_kit',          'ALR', 'Xylocaïne · Bupivacaïne · Gants stériles · Aiguille PL · Kit APD · Compresses', 'statut', $DISPONIBLE);
            row('antidotes',        'Antidotes', 'Naloxone · Néostigmine / Prostigmine', 'statut', $DISPONIBLE);
            row('reanimation_drogues','Réanimation', 'Atropine · Éphédrine · Adrénaline · Noradrénaline · Loxen · Dobutamine · Salbutamol', 'statut', $DISPONIBLE);
            row('atb',              'Antibiotiques', 'Ceftriaxone · Amoxiclav', 'statut', $DISPONIBLE);
            row('anti_hta',         'Anti-HTA', 'Loxen', 'statut', $DISPONIBLE);
            row('diuretique',       'Diurétique', 'Furosémide', 'statut', $DISPONIBLE);
            ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══ VII - AUTRES ══ -->
<div class="s-card">
    <div class="s-card-head head-vii"><i class="bi bi-plus-circle-fill"></i> VII — Autres</div>
    <div class="s-card-body">
        <table class="cl-table">
            <thead><tr><th>Éléments à vérifier / Matériel à préparer</th><th class="col-statut">Statut</th><th class="col-obs">Observations</th></tr></thead>
            <tbody>
            <?php
            row('anti_oedemateux', 'Anti-œdémateux', 'Solumédrol · Mannitol', 'statut', $DISPONIBLE);
            row('nvpo_dexa',       'NVPO', 'Dexaméthasone', 'statut', $DISPONIBLE);
            row('ulcere_stress',   'Ulcère de stress', 'Oméprazole', 'statut', $DISPONIBLE);
            ?>
            </tbody>
        </table>
    </div>
</div>

</form>
</div><!-- .wrap -->

<!-- BARRE DE VALIDATION FIXE -->
<div class="submit-bar">
    <div style="font-size:.82rem;color:#64748b;">
        <i class="bi bi-shield-fill-check text-success me-1"></i>
        Remplissez la check-list avant de démarrer l'intervention
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>dashboard" class="os-btn">Annuler</a>
        <button type="submit" form="formOuverture" class="os-btn accent" id="btnValider">
            <i class="bi bi-check2-circle"></i> Enregistrer la check-list
        </button>
    </div>
</div>

<script>
(function tick(){
    const d=new Date(),p=n=>String(n).padStart(2,'0');
    document.getElementById('osClock').textContent=p(d.getHours())+':'+p(d.getMinutes())+':'+p(d.getSeconds());
    setTimeout(tick,1000);
})();

document.getElementById('formOuverture').addEventListener('submit', function() {
    const btn=document.getElementById('btnValider');
    btn.disabled=true;
    btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement…';
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
