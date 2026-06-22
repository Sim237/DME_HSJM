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

/* ============================================================================
   CONSULTATION POST-NATALE — Fiche de sortie du nouveau-né
   Check-up du pédiatre avant le retour à domicile (mère + bébé).
   Décision finale : SORTIE autorisée OU affectation en Néonatologie.
============================================================================ */
require_once __DIR__ . '/../layouts/header.php';
$patient        = $patient        ?? [];
$age            = $age            ?? '—';
$derniere_fiche = $derniere_fiche ?? null;

/* Items oui/non — examen clinique somatique */
$somatique = [
    'dysmorphie'             => 'Dysmorphie / anomalie physique',
    'souffle_cardiaque'      => 'Souffle cardiaque',
    'hepatomegalie'          => 'Hépatomégalie',
    'splenomegalie'          => 'Splénomégalie',
    'hernie_inguinale'       => 'Hernie inguinale',
    'ictere'                 => 'Ictère',
    'fosses_lombaires'       => 'Fosses lombaires libres',
    'femorales_percues'      => 'Fémorales perçues',
    'hanches_normales'       => 'Hanches normales',
    'membres_normaux'        => 'Membres normaux',
];
/* Items oui/non — examen clinique neurologique */
$neurologique = [
    'vigilance'              => 'Vigilance normale / Alerte',
    'stimuli_sonores'        => 'Réaction aux stimuli sonores',
    'motilite_spontanee'     => 'Motilité spontanée normale',
    'tonus_axial'            => 'Tonus axial normal',
    'tonus_peripherique'     => 'Tonus périphérique normal',
    'reflexe_succion'        => 'Réflexe de succion',
    'reflexe_points_card'    => 'Réflexe des points cardinaux',
    'reflexe_moro'           => 'Réflexe de Moro',
    'reflexe_marche'         => 'Réflexe de marche automatique',
    'reflexe_grasping'       => 'Réflexe de grasping',
    'reflexe_allongement'    => 'Réflexe d\'allongement croisé',
];
/* Consignes de sortie pré-imprimées */
$consignes = [
    '<strong>Gentamicine ou Floxsol collyre</strong> : 1 gtte/œil × 2/jour pendant 5 jours',
    '<strong>Uvestérol sirop</strong> : 1 ml/jour jusqu\'à 18 mois ou <strong>ZymaDuo gttes</strong> : 4 gttes/jour jusqu\'à 18 mois',
    '<strong>Lait maternel exclusif (LME)</strong> jusqu\'à 6 mois si possible',
    'Dormir sous une <strong>moustiquaire imprégnée (MILDA)</strong> tous les jours',
    '<strong>Vaccination</strong> selon le calendrier du PEV',
    '<strong>Huile de palmiste (« Magnanga »)</strong> pour les soins corporels du bébé et protection contre l\'érythème fessier (« fesses rouges »)',
    'Circoncision des bébés garçons avant l\'âge de 3 mois si possible',
];
?>
<style>
    .sidebar, nav.sidebar { display:none !important; }
    main, .col-md-10, .ms-sm-auto { margin-left:0 !important; width:100% !important; flex:0 0 100% !important; max-width:100% !important; padding:0 !important; }
    body { background:#f0f7f9; font-family:'Segoe UI',system-ui,sans-serif; }

    .pn-topbar { background:linear-gradient(135deg,#0e7490,#06b6d4); padding:14px 28px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; box-shadow:0 4px 18px rgba(6,182,212,.3); flex-wrap:wrap; gap:8px; }
    .pn-topbar .t { font-size:1.02rem; font-weight:800; color:#fff; display:flex; align-items:center; gap:9px; }
    .pn-tb-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:10px; font-weight:700; font-size:.8rem; text-decoration:none; border:1px solid rgba(255,255,255,.35); cursor:pointer; background:rgba(255,255,255,.2); color:#fff; }
    .pn-tb-btn:hover { background:rgba(255,255,255,.35); color:#fff; }

    .pn-wrap { max-width:1050px; margin:0 auto; padding:22px 26px 60px; }

    .pat-card { background:linear-gradient(135deg,#06b6d4,#22d3ee); color:#fff; border-radius:16px; padding:14px 20px; margin-bottom:18px; display:flex; align-items:center; gap:14px; box-shadow:0 4px 18px rgba(6,182,212,.25); }
    .pat-name { font-size:1.1rem; font-weight:800; }
    .pat-meta { font-size:.78rem; opacity:.88; }

    .pn-section { background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(6,182,212,.06); border:1px solid #e0f0f3; margin-bottom:16px; overflow:hidden; }
    .pn-sec-head { padding:12px 20px; font-weight:800; font-size:.8rem; text-transform:uppercase; letter-spacing:.5px; color:#0e7490; background:#f0fdff; border-bottom:2px solid #cffafe; display:flex; align-items:center; gap:8px; }
    .pn-sec-body { padding:16px 20px; }

    .pn-label { font-size:.68rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; display:block; }
    .pn-input, .pn-select, .pn-textarea { width:100%; padding:8px 11px; border:1.5px solid #e2e8f0; border-radius:9px; font-size:.85rem; color:#1e293b; outline:none; background:#fff; transition:border .15s; }
    .pn-input:focus, .pn-select:focus, .pn-textarea:focus { border-color:#06b6d4; box-shadow:0 0 0 3px rgba(6,182,212,.12); }
    .pn-textarea { resize:vertical; min-height:56px; }
    .pn-grid { display:grid; gap:12px; }
    .c2 { grid-template-columns:repeat(2,1fr); } .c3 { grid-template-columns:repeat(3,1fr); } .c4 { grid-template-columns:repeat(4,1fr); }
    @media(max-width:760px){ .c2,.c3,.c4 { grid-template-columns:1fr 1fr; } }

    /* Cases à cocher style fiche */
    .pn-checks { display:flex; flex-wrap:wrap; gap:8px; }
    .pn-check { display:flex; align-items:center; gap:7px; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:9px; padding:7px 12px; cursor:pointer; font-size:.8rem; font-weight:600; color:#334155; transition:all .15s; }
    .pn-check input { width:16px; height:16px; accent-color:#06b6d4; }
    .pn-check:has(input:checked) { background:#ecfeff; border-color:#06b6d4; color:#0e7490; }

    /* Lignes oui / non */
    .ouinon-grid { display:grid; grid-template-columns:1fr 1fr; gap:0 26px; }
    @media(max-width:760px){ .ouinon-grid { grid-template-columns:1fr; } }
    .ouinon-row { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:7px 0; border-bottom:1px dashed #f1f5f9; }
    .ouinon-row .q { font-size:.82rem; font-weight:600; color:#334155; }
    .ouinon-opts { display:flex; gap:6px; flex-shrink:0; }
    .ouinon-opt input { display:none; }
    .ouinon-opt span { display:inline-block; padding:4px 13px; border-radius:18px; border:1.5px solid #e2e8f0; font-size:.72rem; font-weight:800; color:#94a3b8; cursor:pointer; transition:all .12s; }
    .ouinon-opt input:checked + span.s-non { background:#f0fdf4; border-color:#86efac; color:#15803d; }
    .ouinon-opt input:checked + span.s-oui { background:#fef2f2; border-color:#fca5a5; color:#b91c1c; }
    /* Neuro : oui = normal (vert), non = anormal (rouge) */
    .neuro .ouinon-opt input:checked + span.s-non { background:#fef2f2; border-color:#fca5a5; color:#b91c1c; }
    .neuro .ouinon-opt input:checked + span.s-oui { background:#f0fdf4; border-color:#86efac; color:#15803d; }

    /* Consignes */
    .pn-consignes { margin:0; padding-left:20px; }
    .pn-consignes li { font-size:.82rem; color:#475569; padding:4px 0; }
    .pn-rdv { background:#f0fdff; border:1.5px solid #cffafe; border-radius:10px; padding:10px 14px; font-size:.8rem; color:#0e7490; font-weight:600; margin-top:12px; }

    /* Décision finale */
    .pn-decision { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    @media(max-width:680px){ .pn-decision { grid-template-columns:1fr; } }
    .dec-card { border:2px solid #e2e8f0; border-radius:14px; padding:16px 18px; cursor:pointer; transition:all .15s; display:flex; gap:12px; align-items:flex-start; background:#fff; }
    .dec-card input { display:none; }
    .dec-card .ic { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
    .dec-card .ttl { font-weight:800; font-size:.9rem; }
    .dec-card .sub { font-size:.74rem; color:#94a3b8; margin-top:2px; }
    .dec-sortie .ic { background:#f0fdf4; color:#16a34a; }
    .dec-neonat .ic { background:#fef2f2; color:#dc2626; }
    .dec-card.sel-sortie { border-color:#16a34a; background:#f0fdf4; box-shadow:0 4px 16px rgba(22,163,74,.15); }
    .dec-card.sel-neonat { border-color:#dc2626; background:#fef2f2; box-shadow:0 4px 16px rgba(220,38,38,.15); }

    .btn-pn-save { background:linear-gradient(135deg,#0e7490,#06b6d4); color:#fff; border:none; border-radius:12px; padding:13px 30px; font-weight:800; font-size:.92rem; cursor:pointer; display:inline-flex; align-items:center; gap:9px; box-shadow:0 4px 16px rgba(6,182,212,.35); }
    .btn-pn-save:hover { opacity:.92; transform:translateY(-1px); }
</style>

<main>
<div class="pn-topbar">
    <div class="t"><i class="bi bi-clipboard2-check-fill"></i> Consultation Post-Natale — Fiche de sortie du nouveau-né</div>
    <div style="display:flex;gap:8px;">
        <a href="<?= BASE_URL ?>neonatologie" class="pn-tb-btn"><i class="bi bi-arrow-left"></i> Néonatologie</a>
    </div>
</div>

<div class="pn-wrap">

    <?php if (($_GET['success'] ?? '') === 'sortie'): ?>
    <div class="alert alert-success rounded-4 border-0 shadow-sm">
        <i class="bi bi-house-heart-fill me-2"></i><strong>Sortie autorisée</strong> — la fiche est enregistrée, le bébé peut rentrer à la maison avec sa mère.
    </div>
    <?php elseif (($_GET['success'] ?? '') === 'neonatologie'): ?>
    <div class="alert alert-danger rounded-4 border-0 shadow-sm">
        <i class="bi bi-hospital-fill me-2"></i><strong>Affectation en Néonatologie</strong> — le nouveau-né apparaît dans la liste « À Hospitaliser » du service, les infirmiers ont été notifiés.
    </div>
    <?php elseif (isset($_GET['error'])): ?>
    <div class="alert alert-danger rounded-4 border-0 shadow-sm">
        <i class="bi bi-x-circle-fill me-2"></i>Erreur lors de l'enregistrement. Veuillez réessayer.
    </div>
    <?php endif; ?>

    <!-- Bandeau bébé -->
    <div class="pat-card">
        <div style="width:48px;height:48px;border-radius:14px;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
            <i class="bi bi-emoji-smile"></i>
        </div>
        <div>
            <div class="pat-name"><?= htmlspecialchars(strtoupper($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?></div>
            <div class="pat-meta">
                N° <?= htmlspecialchars($patient['dossier_numero'] ?? '—') ?>
                &bull; Âge : <?= htmlspecialchars($age) ?>
                &bull; Sexe : <?= strtoupper($patient['sexe'] ?? '') === 'F' ? 'Féminin' : 'Masculin' ?>
                &bull; Né(e) le : <?= !empty($patient['date_naissance']) ? date('d/m/Y', strtotime($patient['date_naissance'])) : '—' ?>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= BASE_URL ?>neonatologie/post-natale-sauvegarder" id="pnForm">
        <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>">

        <!-- ══ 1. NAISSANCE ══ -->
        <div class="pn-section">
            <div class="pn-sec-head"><i class="bi bi-balloon-heart"></i> Naissance</div>
            <div class="pn-sec-body">
                <div class="pn-grid c3">
                    <div><label class="pn-label">Lieu de naissance</label><input type="text" name="lieu_naissance" class="pn-input" placeholder="HSJM, domicile…"></div>
                    <div><label class="pn-label">Date & heure de naissance</label><input type="datetime-local" name="datetime_naissance" class="pn-input"
                         value="<?= !empty($patient['date_naissance']) ? date('Y-m-d\T08:00', strtotime($patient['date_naissance'])) : '' ?>"></div>
                    <div><label class="pn-label">Terme (SA)</label><input type="text" name="terme" class="pn-input" placeholder="Ex : 39 SA"></div>
                </div>
                <div class="pn-grid c4 mt-3">
                    <div><label class="pn-label">Présentation</label>
                        <select name="presentation" class="pn-select"><option value="">—</option><option>Céphalique</option><option>Siège</option><option>Autre</option></select>
                    </div>
                    <div><label class="pn-label">Mode d'accouchement</label>
                        <select name="mode_accouchement" class="pn-select" onchange="document.getElementById('pnCesar').style.display = this.value==='Césarienne' ? '' : 'none'">
                            <option value="">—</option><option>Voie basse</option><option>Césarienne</option><option>Instrumental</option>
                        </select>
                    </div>
                    <div><label class="pn-label">Cri immédiat</label>
                        <select name="cri_immediat" class="pn-select"><option value="">—</option><option>Oui</option><option>Non</option></select>
                    </div>
                    <div><label class="pn-label">Réanimation</label><input type="text" name="reanimation" class="pn-input" placeholder="Non / préciser"></div>
                </div>
                <div id="pnCesar" class="mt-3" style="display:none;">
                    <label class="pn-label">Indication de la césarienne</label>
                    <input type="text" name="cesarienne_indication" class="pn-input">
                </div>
                <div class="pn-grid c4 mt-3">
                    <div><label class="pn-label">Apgar 1'</label><input type="number" name="apgar_1" class="pn-input" min="0" max="10"></div>
                    <div><label class="pn-label">Apgar 5'</label><input type="number" name="apgar_5" class="pn-input" min="0" max="10"></div>
                    <div><label class="pn-label">Apgar 10'</label><input type="number" name="apgar_10" class="pn-input" min="0" max="10"></div>
                    <div></div>
                </div>
                <div class="pn-grid c4 mt-3">
                    <div><label class="pn-label">Poids de naissance (g)</label><input type="number" name="poids_naissance" class="pn-input" placeholder="3200"></div>
                    <div><label class="pn-label">PC (cm)</label><input type="number" step="0.1" name="pc" class="pn-input"></div>
                    <div><label class="pn-label">PB (cm)</label><input type="number" step="0.1" name="pb" class="pn-input"></div>
                    <div><label class="pn-label">Taille (cm)</label><input type="number" step="0.1" name="taille" class="pn-input"></div>
                </div>
                <div class="pn-grid c2 mt-3">
                    <div><label class="pn-label">1er méconium (date / heure)</label><input type="text" name="premier_meconium" class="pn-input"></div>
                    <div><label class="pn-label">1ère miction (date / heure)</label><input type="text" name="premiere_miction" class="pn-input"></div>
                </div>
                <div class="mt-3">
                    <label class="pn-label">Prophylaxie reçue à la naissance</label>
                    <div class="pn-checks">
                        <label class="pn-check"><input type="checkbox" name="vitamine_k1" value="1"> Vitamine K1</label>
                        <label class="pn-check"><input type="checkbox" name="gentamicine_collyre" value="1"> Gentamicine collyre</label>
                        <label class="pn-check"><input type="checkbox" name="vitamine_d" value="1"> Vitamine D (Uvestérol / ZymaDuo)</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ 2. MÈRE & GROSSESSE ══ -->
        <div class="pn-section">
            <div class="pn-sec-head"><i class="bi bi-person-heart"></i> Mère & grossesse</div>
            <div class="pn-sec-body">
                <div class="pn-grid c4">
                    <div><label class="pn-label">Âge de la mère (ans)</label><input type="number" name="mere_age" class="pn-input" min="10" max="60"></div>
                    <div><label class="pn-label">Gestité (G)</label><input type="number" name="gestite" class="pn-input" min="0"></div>
                    <div><label class="pn-label">Parité (P)</label><input type="number" name="parite" class="pn-input" min="0"></div>
                    <div><label class="pn-label">GSRh</label><input type="text" name="gsrh" class="pn-input" placeholder="O+, A−…"></div>
                </div>
                <div class="pn-grid c4 mt-3">
                    <div><label class="pn-label">Élect. Hb</label><input type="text" name="elect_hb" class="pn-input" placeholder="AA, AS…"></div>
                    <div><label class="pn-label">CPN (nbre et lieu)</label><input type="text" name="cpn" class="pn-input" placeholder="4 — HSJM"></div>
                    <div><label class="pn-label">ETME</label><input type="text" name="etme" class="pn-input"></div>
                    <div><label class="pn-label">BW</label><input type="text" name="bw" class="pn-input"></div>
                </div>
                <div class="pn-grid c4 mt-3">
                    <div><label class="pn-label">AgHBs</label><input type="text" name="aghbs" class="pn-input"></div>
                    <div><label class="pn-label">TOXO</label><input type="text" name="toxo" class="pn-input"></div>
                    <div><label class="pn-label">Rubéole</label><input type="text" name="rubeole" class="pn-input"></div>
                    <div><label class="pn-label">Écho obst. (nbre et terme)</label><input type="text" name="echo_obst" class="pn-input"></div>
                </div>
                <div class="mt-3">
                    <label class="pn-label">Prophylaxie maternelle</label>
                    <div class="pn-checks">
                        <label class="pn-check"><input type="checkbox" name="milda" value="1"> MILDA</label>
                        <label class="pn-check"><input type="checkbox" name="tpi" value="1"> TPI <input type="text" name="tpi_doses" class="pn-input" style="width:70px;padding:2px 8px;margin-left:4px;" placeholder="doses"></label>
                        <label class="pn-check"><input type="checkbox" name="fer_acfol" value="1"> Fer + Acfol</label>
                        <label class="pn-check"><input type="checkbox" name="vat" value="1"> VAT <input type="text" name="vat_doses" class="pn-input" style="width:70px;padding:2px 8px;margin-left:4px;" placeholder="doses"></label>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="pn-label">Facteurs de risque périnatals</label>
                    <div class="pn-checks">
                        <label class="pn-check"><input type="checkbox" name="infection_urogenitale" value="1"> Infection uro-génitale (dernier mois)</label>
                        <label class="pn-check"><input type="checkbox" name="rupture_prematuree" value="1"> Rupture prématurée PDE</label>
                        <label class="pn-check"><input type="checkbox" name="rupture_prolongee" value="1"> Rupture prolongée PDE <input type="text" name="rupture_duree" class="pn-input" style="width:80px;padding:2px 8px;margin-left:4px;" placeholder="durée"></label>
                        <label class="pn-check"><input type="checkbox" name="la_teinte" value="1"> LA teinté / méconial</label>
                        <label class="pn-check"><input type="checkbox" name="la_fetide" value="1"> LA fétide</label>
                        <label class="pn-check"><input type="checkbox" name="fievre_maternelle" value="1"> Fièvre maternelle péripartale <input type="text" name="fievre_temp" class="pn-input" style="width:70px;padding:2px 8px;margin-left:4px;" placeholder="T° ?"></label>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ 3. EXAMEN CLINIQUE SOMATIQUE ══ -->
        <div class="pn-section">
            <div class="pn-sec-head"><i class="bi bi-clipboard2-pulse"></i> Examen clinique somatique</div>
            <div class="pn-sec-body">
                <div class="pn-grid c4" style="margin-bottom:12px;">
                    <div><label class="pn-label">Température (°C)</label><input type="number" step="0.1" name="temperature" class="pn-input" placeholder="36.5–37.5"></div>
                </div>
                <div class="ouinon-grid">
                    <?php foreach ($somatique as $key => $lbl): ?>
                    <div class="ouinon-row">
                        <span class="q"><?= $lbl ?></span>
                        <span class="ouinon-opts">
                            <label class="ouinon-opt"><input type="radio" name="som_<?= $key ?>" value="NON"><span class="s-non">non</span></label>
                            <label class="ouinon-opt"><input type="radio" name="som_<?= $key ?>" value="OUI"><span class="s-oui">oui</span></label>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="pn-grid c2 mt-3">
                    <div><label class="pn-label">Organes génitaux</label><input type="text" name="organes_genitaux" class="pn-input" placeholder="Normaux / préciser"></div>
                    <div><label class="pn-label">Anus</label><input type="text" name="anus" class="pn-input" placeholder="Perméable / préciser"></div>
                </div>
            </div>
        </div>

        <!-- ══ 4. EXAMEN CLINIQUE NEUROLOGIQUE ══ -->
        <div class="pn-section">
            <div class="pn-sec-head"><i class="bi bi-lightning-charge"></i> Examen clinique neurologique</div>
            <div class="pn-sec-body neuro">
                <div class="ouinon-grid">
                    <?php foreach ($neurologique as $key => $lbl): ?>
                    <div class="ouinon-row">
                        <span class="q"><?= $lbl ?></span>
                        <span class="ouinon-opts">
                            <label class="ouinon-opt"><input type="radio" name="neuro_<?= $key ?>" value="NON"><span class="s-non">non</span></label>
                            <label class="ouinon-opt"><input type="radio" name="neuro_<?= $key ?>" value="OUI"><span class="s-oui">oui</span></label>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ══ 5. SORTIE & CONCLUSION ══ -->
        <div class="pn-section">
            <div class="pn-sec-head"><i class="bi bi-journal-check"></i> Conclusion & sortie</div>
            <div class="pn-sec-body">
                <div class="pn-grid c3">
                    <div><label class="pn-label">Poids de sortie (g)</label><input type="number" name="poids_sortie" class="pn-input" placeholder="3150"></div>
                    <div><label class="pn-label">Date de l'examen</label><input type="date" name="date_examen" class="pn-input" value="<?= date('Y-m-d') ?>"></div>
                    <div><label class="pn-label">Date du 1er rendez-vous</label><input type="date" name="date_rdv1" class="pn-input"></div>
                </div>
                <div class="mt-3">
                    <label class="pn-label">Conclusion</label>
                    <textarea name="conclusion" class="pn-textarea" placeholder="Synthèse de l'examen, anomalies relevées…"></textarea>
                </div>

                <div class="mt-3">
                    <label class="pn-label">Consignes de sortie (remises à la mère)</label>
                    <ol class="pn-consignes">
                        <?php foreach ($consignes as $c): ?><li><?= $c ?></li><?php endforeach; ?>
                    </ol>
                    <div class="pn-rdv">
                        <i class="bi bi-calendar-week me-1"></i>
                        <strong>Rendez-vous de routine :</strong> 1 mois, 3 mois, 4 mois, 6 mois, 9 mois, 12 mois, 15 mois, 18 mois, 2 ans, 3 ans, 5 ans
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ 6. DÉCISION DU PÉDIATRE ══ -->
        <div class="pn-section" style="border:2px solid #cffafe;">
            <div class="pn-sec-head"><i class="bi bi-signpost-split-fill"></i> Décision du pédiatre <span style="color:#dc2626;">*</span></div>
            <div class="pn-sec-body">
                <div class="pn-decision">
                    <label class="dec-card dec-sortie" id="cardSortie">
                        <input type="radio" name="decision" value="SORTIE" required onchange="pnMajDecision()">
                        <span class="ic"><i class="bi bi-house-heart-fill"></i></span>
                        <span>
                            <span class="ttl">Autoriser la sortie</span>
                            <span class="sub d-block">Examen normal — le bébé rentre à la maison avec sa mère. Le dossier passe en « Sorti ».</span>
                        </span>
                    </label>
                    <label class="dec-card dec-neonat" id="cardNeonat">
                        <input type="radio" name="decision" value="NEONATOLOGIE" required onchange="pnMajDecision()">
                        <span class="ic"><i class="bi bi-hospital-fill"></i></span>
                        <span>
                            <span class="ttl">Affecter en Néonatologie</span>
                            <span class="sub d-block">Problème détecté — le nouveau-né est orienté vers le service de néonatologie (berceau / couveuse à attribuer).</span>
                        </span>
                    </label>
                </div>
                <div id="pnMotifNeonat" class="mt-3" style="display:none;">
                    <label class="pn-label" style="color:#b91c1c;">Motif de l'affectation en néonatologie <span style="color:#dc2626;">*</span></label>
                    <textarea name="motif_neonatologie" id="motifNeonatInput" class="pn-textarea" style="border-color:#fca5a5;background:#fff7f7;"
                              placeholder="Ex : détresse respiratoire, ictère précoce, hypotonie, prématurité, suspicion d'infection néonatale…"></textarea>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn-pn-save">
                        <i class="bi bi-check-circle-fill"></i> Enregistrer la consultation post-natale
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
</main>

<script>
function pnMajDecision() {
    const val = document.querySelector('input[name="decision"]:checked')?.value;
    document.getElementById('cardSortie').classList.toggle('sel-sortie', val === 'SORTIE');
    document.getElementById('cardNeonat').classList.toggle('sel-neonat', val === 'NEONATOLOGIE');
    document.getElementById('pnMotifNeonat').style.display = (val === 'NEONATOLOGIE') ? '' : 'none';
}

/* Validation : motif obligatoire si affectation en néonatologie */
document.getElementById('pnForm').addEventListener('submit', function(e) {
    const val = document.querySelector('input[name="decision"]:checked')?.value;
    if (val === 'NEONATOLOGIE') {
        const motif = document.getElementById('motifNeonatInput');
        if (!motif.value.trim()) {
            e.preventDefault();
            motif.focus();
            alert("Veuillez préciser le motif de l'affectation en néonatologie.");
        }
    }
});

/* Raccourci : "Tout normal" — pré-coche les réponses attendues normales */
(function ajouterBoutonToutNormal() {
    const headSom = document.querySelectorAll('.pn-sec-head')[2];   // Examen somatique
    const headNeu = document.querySelectorAll('.pn-sec-head')[3];   // Examen neurologique
    const mk = (head, fn) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.textContent = '✓ Tout normal';
        b.style.cssText = 'margin-left:auto;border:1.5px solid #86efac;background:#f0fdf4;color:#15803d;font-size:.68rem;font-weight:800;border-radius:18px;padding:3px 12px;cursor:pointer;';
        b.onclick = fn;
        head.appendChild(b);
    };
    /* Somatique : "non" = absence d'anomalie SAUF les 4 items "normaux" où oui = normal */
    const somOuiNormal = ['som_fosses_lombaires','som_femorales_percues','som_hanches_normales','som_membres_normaux'];
    mk(headSom, () => {
        document.querySelectorAll('[name^="som_"]').forEach(r => {
            const attendu = somOuiNormal.includes(r.name) ? 'OUI' : 'NON';
            if (r.value === attendu) r.checked = true;
        });
    });
    /* Neurologique : "oui" = réponse normale partout */
    mk(headNeu, () => {
        document.querySelectorAll('[name^="neuro_"][value="OUI"]').forEach(r => r.checked = true);
    });
})();
</script>
