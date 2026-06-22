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
$patient = $patient ?? [];
$age     = $age     ?? '—';
$dernier = $dernier ?? null;
$v = function($k, $def='') use ($dernier) { return htmlspecialchars($dernier[$k] ?? $def); };
$sel = function($k, $opt) use ($dernier) { return (($dernier[$k] ?? '') === $opt) ? 'selected' : ''; };
?>
<style>
    .sidebar, nav.sidebar { display:none !important; }
    main, .col-md-10, .ms-sm-auto { margin-left:0 !important; width:100% !important; flex:0 0 100% !important; max-width:100% !important; padding:0 !important; }
    body { background:#f0f7f9; font-family:'Segoe UI',system-ui,sans-serif; }

    .neo-topbar { background:linear-gradient(135deg,#0e7490,#06b6d4); padding:14px 28px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; box-shadow:0 4px 18px rgba(6,182,212,.3); }
    .neo-topbar .t { font-size:1.05rem; font-weight:800; color:#fff; display:flex; align-items:center; gap:9px; }
    .neo-tb-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:10px; font-weight:700; font-size:.8rem; text-decoration:none; border:none; cursor:pointer; }
    .neo-tb-light { background:rgba(255,255,255,.2); color:#fff; border:1px solid rgba(255,255,255,.35); }
    .neo-tb-save  { background:#fff; color:#0e7490; }
    .neo-tb-save:hover { background:#ecfeff; color:#0e7490; }

    .neo-form { max-width:1000px; margin:0 auto; padding:24px 28px 60px; }

    .pat-card { background:linear-gradient(135deg,#06b6d4,#22d3ee); color:#fff; border-radius:16px; padding:16px 22px; margin-bottom:22px; display:flex; align-items:center; gap:14px; box-shadow:0 4px 18px rgba(6,182,212,.25); }
    .pat-av { width:52px; height:52px; border-radius:15px; background:rgba(255,255,255,.25); display:flex; align-items:center; justify-content:center; font-size:1.5rem; }
    .pat-name { font-size:1.15rem; font-weight:800; }
    .pat-meta { font-size:.8rem; opacity:.85; }

    .neo-section { background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(6,182,212,.06); border:1px solid #e0f0f3; margin-bottom:18px; overflow:hidden; }
    .neo-sec-head { padding:13px 20px; font-weight:800; font-size:.82rem; text-transform:uppercase; letter-spacing:.5px; color:#0e7490; background:#f0fdff; border-bottom:2px solid #cffafe; display:flex; align-items:center; gap:8px; }
    .neo-sec-body { padding:18px 20px; }

    .neo-label { font-size:.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px; display:block; }
    .neo-input, .neo-select, .neo-textarea { width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:.88rem; color:#1e293b; outline:none; background:#fff; transition:border .15s; }
    .neo-input:focus, .neo-select:focus, .neo-textarea:focus { border-color:#06b6d4; box-shadow:0 0 0 3px rgba(6,182,212,.12); }
    .neo-textarea { resize:vertical; min-height:64px; }
    .neo-grid { display:grid; gap:14px; }
    .g2 { grid-template-columns:repeat(2,1fr); } .g3 { grid-template-columns:repeat(3,1fr); } .g4 { grid-template-columns:repeat(4,1fr); }
    @media(max-width:720px){ .g2,.g3,.g4 { grid-template-columns:1fr 1fr; } }

    .neo-checks { display:flex; flex-wrap:wrap; gap:10px; }
    .neo-check { display:flex; align-items:center; gap:7px; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:10px; padding:8px 14px; cursor:pointer; font-size:.84rem; font-weight:600; color:#334155; transition:all .15s; }
    .neo-check input { width:17px; height:17px; accent-color:#06b6d4; }
    .neo-check:has(input:checked) { background:#ecfeff; border-color:#06b6d4; color:#0e7490; }

    .neo-suffix { position:relative; }
    .neo-suffix .u { position:absolute; right:12px; top:50%; transform:translateY(-50%); font-size:.72rem; color:#94a3b8; font-weight:700; pointer-events:none; }
    .neo-suffix .neo-input { padding-right:42px; }
</style>

<main>
    <form method="POST" action="<?= BASE_URL ?>neonatologie/sauvegarder" id="neoForm">
        <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>">

        <div class="neo-topbar">
            <div class="t"><i class="bi bi-clipboard2-heart"></i> Examen néonatal</div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>neonatologie/gavage/<?= (int)($patient['id'] ?? 0) ?>" class="neo-tb-btn neo-tb-light"><i class="bi bi-droplet-fill"></i> Gavage</a>
                <a href="<?= BASE_URL ?>neonatologie" class="neo-tb-btn neo-tb-light"><i class="bi bi-x-lg"></i> Annuler</a>
                <button type="submit" class="neo-tb-btn neo-tb-save"><i class="bi bi-check-circle-fill"></i> Enregistrer l'examen</button>
            </div>
        </div>

        <div class="neo-form">
            <!-- Patient -->
            <div class="pat-card">
                <div class="pat-av"><i class="bi bi-<?= strtoupper($patient['sexe']??'')==='F'?'gender-female':'gender-male' ?>"></i></div>
                <div style="flex:1;">
                    <div class="pat-name"><?= htmlspecialchars(strtoupper($patient['nom']??'').' '.($patient['prenom']??'')) ?></div>
                    <div class="pat-meta">
                        N° <?= htmlspecialchars($patient['dossier_numero'] ?? '—') ?>
                        &bull; Âge : <?= htmlspecialchars($age) ?>
                        &bull; <?= strtoupper($patient['sexe']??'')==='F'?'Fille':'Garçon' ?>
                    </div>
                </div>
            </div>

            <!-- À LA NAISSANCE -->
            <div class="neo-section">
                <div class="neo-sec-head"><i class="bi bi-balloon-heart"></i> Données de naissance</div>
                <div class="neo-sec-body">
                    <div class="neo-grid g3">
                        <div><label class="neo-label">Âge gestationnel</label><div class="neo-suffix"><input type="number" step="0.1" name="age_gestationnel_sa" class="neo-input" value="<?= $v('age_gestationnel_sa') ?>" placeholder="ex : 38"><span class="u">SA</span></div></div>
                        <div><label class="neo-label">Terme</label>
                            <select name="terme" class="neo-select">
                                <option value="">—</option>
                                <option value="PREMATURE" <?= $sel('terme','PREMATURE') ?>>Prématuré (&lt; 37 SA)</option>
                                <option value="A_TERME" <?= $sel('terme','A_TERME') ?>>À terme (37–42 SA)</option>
                                <option value="POST_TERME" <?= $sel('terme','POST_TERME') ?>>Post-terme (&gt; 42 SA)</option>
                            </select>
                        </div>
                        <div><label class="neo-label">Mode d'accouchement</label>
                            <select name="mode_accouchement" class="neo-select">
                                <option value="">—</option>
                                <option value="VOIE_BASSE" <?= $sel('mode_accouchement','VOIE_BASSE') ?>>Voie basse</option>
                                <option value="CESARIENNE" <?= $sel('mode_accouchement','CESARIENNE') ?>>Césarienne</option>
                                <option value="INSTRUMENTAL" <?= $sel('mode_accouchement','INSTRUMENTAL') ?>>Instrumental (forceps/ventouse)</option>
                            </select>
                        </div>
                    </div>
                    <div class="neo-grid g3 mt-3">
                        <div><label class="neo-label">Poids de naissance</label><div class="neo-suffix"><input type="number" name="poids_naissance" class="neo-input" value="<?= $v('poids_naissance') ?>" placeholder="ex : 3200"><span class="u">g</span></div></div>
                        <div><label class="neo-label">Taille</label><div class="neo-suffix"><input type="number" step="0.1" name="taille_naissance" class="neo-input" value="<?= $v('taille_naissance') ?>" placeholder="ex : 49"><span class="u">cm</span></div></div>
                        <div><label class="neo-label">Périmètre crânien</label><div class="neo-suffix"><input type="number" step="0.1" name="perimetre_cranien" class="neo-input" value="<?= $v('perimetre_cranien') ?>" placeholder="ex : 34"><span class="u">cm</span></div></div>
                    </div>
                    <div class="neo-grid g3 mt-3">
                        <div><label class="neo-label">Apgar 1 min</label><input type="number" min="0" max="10" name="apgar_1" class="neo-input" value="<?= $v('apgar_1') ?>" placeholder="/10"></div>
                        <div><label class="neo-label">Apgar 5 min</label><input type="number" min="0" max="10" name="apgar_5" class="neo-input" value="<?= $v('apgar_5') ?>" placeholder="/10"></div>
                        <div><label class="neo-label">Apgar 10 min</label><input type="number" min="0" max="10" name="apgar_10" class="neo-input" value="<?= $v('apgar_10') ?>" placeholder="/10"></div>
                    </div>
                </div>
            </div>

            <!-- CONSTANTES ACTUELLES -->
            <div class="neo-section">
                <div class="neo-sec-head"><i class="bi bi-activity"></i> Constantes actuelles</div>
                <div class="neo-sec-body">
                    <div class="neo-grid g4">
                        <div><label class="neo-label">Poids actuel</label><div class="neo-suffix"><input type="number" name="poids_actuel" id="neoPoids" class="neo-input" placeholder="g"><span class="u">g</span></div></div>
                        <div><label class="neo-label">Température <small class="text-muted">(N : 36.5–37.5)</small></label><div class="neo-suffix"><input type="number" step="0.1" name="temperature" class="neo-input neo-vital" data-min="36.5" data-max="37.5" placeholder="36.5–37.5"><span class="u">°C</span></div></div>
                        <div><label class="neo-label">Fréq. cardiaque <small class="text-muted">(N : 120–180)</small></label><div class="neo-suffix"><input type="number" name="freq_cardiaque" class="neo-input neo-vital" data-min="120" data-max="180" placeholder="120–180"><span class="u">bpm</span></div></div>
                        <div><label class="neo-label">Fréq. respiratoire <small class="text-muted">(N : 30–80)</small></label><div class="neo-suffix"><input type="number" name="freq_respiratoire" class="neo-input neo-vital" data-min="30" data-max="80" placeholder="30–80"><span class="u">/min</span></div></div>
                    </div>
                    <div class="neo-grid g4 mt-3">
                        <div><label class="neo-label">Saturation SpO2 <small class="text-muted">(N : &gt; 92)</small></label><div class="neo-suffix"><input type="number" name="spo2" class="neo-input neo-vital" data-min="92" placeholder="&gt; 92"><span class="u">%</span></div></div>
                        <div><label class="neo-label">Diurèse <small class="text-muted">(N : 0.5–1 cc/kg/h)</small></label><div class="neo-suffix"><input type="number" step="0.01" name="diurese" class="neo-input neo-vital" data-min="0.5" data-max="1" placeholder="0.5–1"><span class="u">cc/kg/h</span></div></div>
                        <div><label class="neo-label">État de conscience</label>
                            <select name="etat_conscience" class="neo-select">
                                <option value="">—</option>
                                <option value="EVEILLE">Éveillé / réactif</option>
                                <option value="SOMNOLENT">Somnolent</option>
                                <option value="HYPOTONIQUE">Hypotonique</option>
                                <option value="LETHARGIQUE">Léthargique</option>
                                <option value="IRRITABLE">Irritable / geignard</option>
                                <option value="COMATEUX">Comateux</option>
                            </select>
                        </div>
                        <div><label class="neo-label">Glycémie</label><div class="neo-suffix"><input type="number" step="0.01" name="glycemie" class="neo-input" placeholder="g/L"><span class="u">g/L</span></div></div>
                    </div>
                    <div class="neo-grid g4 mt-3">
                        <div><label class="neo-label">Fontanelle ant.</label>
                            <select name="fontanelle" class="neo-select">
                                <option value="">—</option>
                                <option value="NORMOTENDUE">Normotendue</option>
                                <option value="BOMBEE">Bombée</option>
                                <option value="DEPRIMEE">Déprimée</option>
                            </select>
                        </div>
                        <div></div><div></div><div></div>
                    </div>
                    <!-- Aide-calcul diurèse -->
                    <div class="mt-2" style="font-size:.74rem;color:#64748b;background:#f0fdfa;border:1px solid #ccfbf1;border-radius:9px;padding:8px 12px;">
                        <i class="bi bi-calculator me-1" style="color:#0d9488;"></i>
                        <strong>Diurèse (cc/kg/h)</strong> = volume urinaire (cc) ÷ poids (kg) ÷ durée (h).
                        <span id="aideDiurese"></span>
                    </div>
                </div>
            </div>

            <!-- ALIMENTATION -->
            <div class="neo-section">
                <div class="neo-sec-head"><i class="bi bi-cup-hot"></i> Alimentation</div>
                <div class="neo-sec-body">
                    <div class="neo-grid g2">
                        <div><label class="neo-label">Type d'alimentation</label>
                            <select name="alimentation" class="neo-select">
                                <option value="">—</option>
                                <option value="SEIN">Allaitement maternel</option>
                                <option value="ARTIFICIEL">Lait artificiel</option>
                                <option value="MIXTE">Mixte</option>
                                <option value="SONDE">Sonde gastrique</option>
                                <option value="PERFUSION">Perfusion / parentérale</option>
                            </select>
                        </div>
                        <div><label class="neo-label">Tolérance alimentaire</label><input type="text" name="tolerance_alimentaire" class="neo-input" placeholder="ex : bonne, régurgitations, ballonnement…"></div>
                    </div>
                </div>
            </div>

            <!-- EXAMEN CLINIQUE -->
            <div class="neo-section">
                <div class="neo-sec-head"><i class="bi bi-search-heart"></i> Examen clinique</div>
                <div class="neo-sec-body">
                    <label class="neo-label">Ictère & réflexes archaïques</label>
                    <div class="neo-checks mb-3">
                        <label class="neo-check"><input type="checkbox" name="ictere" value="1"> Ictère</label>
                        <label class="neo-check"><input type="checkbox" name="reflexe_succion" value="1"> Réflexe de succion</label>
                        <label class="neo-check"><input type="checkbox" name="reflexe_moro" value="1"> Réflexe de Moro</label>
                        <label class="neo-check"><input type="checkbox" name="reflexe_grasping" value="1"> Grasping</label>
                        <div style="min-width:160px;"><div class="neo-suffix"><input type="number" min="1" max="5" name="ictere_kramer" class="neo-input" placeholder="Zone de Kramer"><span class="u">1-5</span></div></div>
                    </div>
                    <div class="neo-grid g2">
                        <div><label class="neo-label">Cardio-vasculaire</label><textarea name="examen_cardio" class="neo-textarea" placeholder="Bruits du cœur, souffle, pouls fémoraux…"></textarea></div>
                        <div><label class="neo-label">Respiratoire</label><textarea name="examen_respiratoire" class="neo-textarea" placeholder="Murmure vésiculaire, signes de lutte, geignement…"></textarea></div>
                        <div><label class="neo-label">Abdomen / ombilic</label><textarea name="examen_abdomen" class="neo-textarea" placeholder="Souplesse, cordon ombilical, hépato-splénomégalie…"></textarea></div>
                        <div><label class="neo-label">Neurologique</label><textarea name="examen_neuro" class="neo-textarea" placeholder="Tonus, cri, vigilance, mouvements…"></textarea></div>
                        <div style="grid-column:1/-1;"><label class="neo-label">Peau & téguments</label><textarea name="examen_peau_ombilic" class="neo-textarea" placeholder="Coloration, lésions, érythème, cyanose…"></textarea></div>
                    </div>
                </div>
            </div>

            <!-- SYNTHÈSE -->
            <div class="neo-section">
                <div class="neo-sec-head"><i class="bi bi-clipboard2-check"></i> Synthèse & conduite</div>
                <div class="neo-sec-body">
                    <div class="neo-grid g2">
                        <div><label class="neo-label">Diagnostic</label><textarea name="diagnostic" class="neo-textarea" placeholder="Diagnostic principal et différentiels…"></textarea></div>
                        <div><label class="neo-label">Conduite à tenir</label><textarea name="conduite_tenir" class="neo-textarea" placeholder="Surveillance, examens complémentaires, transfert…"></textarea></div>
                        <div><label class="neo-label">Traitement</label><textarea name="traitement" class="neo-textarea" placeholder="Prescriptions, posologies…"></textarea></div>
                        <div><label class="neo-label">Observations</label><textarea name="observations" class="neo-textarea" placeholder="Remarques complémentaires…"></textarea></div>
                    </div>
                    <div class="text-end mt-3">
                        <button type="submit" class="neo-tb-btn" style="background:#06b6d4;color:#fff;padding:11px 26px;font-size:.9rem;">
                            <i class="bi bi-check-circle-fill me-1"></i> Enregistrer l'examen néonatal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</main>

<script>
/* ── Surlignage hors normes néonatales + aide diurèse ── */
(function(){
    document.querySelectorAll('.neo-vital').forEach(inp => {
        inp.addEventListener('input', () => {
            const v   = parseFloat(inp.value);
            const min = inp.dataset.min !== undefined ? parseFloat(inp.dataset.min) : null;
            const max = inp.dataset.max !== undefined ? parseFloat(inp.dataset.max) : null;
            const hors = !isNaN(v) && ((min !== null && v < min) || (max !== null && v > max));
            inp.style.borderColor = hors ? '#dc2626' : '';
            inp.style.background  = hors ? '#fef2f2' : '';
            inp.style.color       = hors ? '#b91c1c' : '';
            inp.style.fontWeight  = hors ? '700' : '';
        });
    });

    /* Aide-calcul : plage attendue en cc/h selon le poids saisi */
    const poids = document.getElementById('neoPoids');
    const aide  = document.getElementById('aideDiurese');
    if (poids && aide) {
        const maj = () => {
            const g = parseFloat(poids.value);
            if (!isNaN(g) && g > 0) {
                const kg = g / 1000;
                aide.innerHTML = ' Pour <strong>' + kg.toFixed(2) + ' kg</strong> : attendu entre <strong>'
                    + (0.5 * kg).toFixed(1) + '</strong> et <strong>' + (1 * kg).toFixed(1) + ' cc/h</strong>.';
            } else { aide.textContent = ''; }
        };
        poids.addEventListener('input', maj); maj();
    }
})();
</script>
