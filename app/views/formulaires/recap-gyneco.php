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

// Variables disponibles : $formulaire_id, $formData, $patient, $medecinNom, $medecinSpec, $medecinOrdre, $signatureB64, $cachetB64
$nom_patient  = trim(($patient['prenom'] ?? '') . ' ' . ($patient['nom'] ?? ''));
$age_patient  = '';
if (!empty($patient['date_naissance'])) {
    $age_patient = (new DateTime($patient['date_naissance']))->diff(new DateTime())->y . ' ans';
}
$dateConsult = date('d/m/Y');
?>
<style>
:root { --pk:#db2777;--pk-lt:#fdf2f8;--pk-dk:#9d174d;--border:#e2e8f0;--dark:#1e293b;--muted:#64748b; }
body { background:#f0f4f8; }

.rc-page { max-width:960px;margin:32px auto;padding:0 16px; }

/* Header */
.rc-hero { background:linear-gradient(135deg,#db2777,#9d174d);border-radius:20px;padding:28px 32px;color:white;margin-bottom:28px;display:flex;align-items:center;gap:20px;box-shadow:0 8px 32px rgba(219,39,119,.3); }
.rc-hero .avatar { width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2rem;flex-shrink:0; }
.rc-hero h2 { margin:0 0 4px;font-size:1.4rem;font-weight:900; }
.rc-hero p  { margin:0;opacity:.85;font-size:.9rem; }
.rc-success-badge { display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.2);border-radius:20px;padding:4px 14px;font-size:.82rem;font-weight:700;margin-top:8px; }

/* Cards de documents */
.rc-docs-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-bottom:28px; }

.rc-doc-card { background:white;border-radius:16px;border:1.5px solid var(--border);overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.06);transition:.2s; }
.rc-doc-card:hover { box-shadow:0 6px 24px rgba(0,0,0,.12);transform:translateY(-2px); }
.rc-doc-header { padding:16px 20px;display:flex;align-items:center;gap:12px; }
.rc-doc-icon { width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0; }
.rc-doc-title { font-weight:800;font-size:.95rem; }
.rc-doc-subtitle { font-size:.78rem;color:var(--muted);margin-top:2px; }
.rc-doc-body { padding:0 20px 16px; }
.rc-doc-list { list-style:none;padding:0;margin:0; }
.rc-doc-list li { padding:8px 0;border-bottom:1px dashed #f1f5f9;font-size:.87rem;display:flex;align-items:baseline;gap:8px; }
.rc-doc-list li:last-child { border-bottom:none; }
.rc-doc-list li .num { background:#f8fafc;border-radius:6px;padding:2px 7px;font-size:.72rem;font-weight:700;color:var(--muted);flex-shrink:0; }
.rc-doc-empty { color:var(--muted);font-style:italic;font-size:.85rem;padding:8px 0; }
.rc-doc-footer { padding:12px 20px;background:#f8fafc;border-top:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap; }

/* Boutons */
.btn-rc-print { display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:10px;font-weight:700;font-size:.85rem;cursor:pointer;border:none;transition:.18s; }
.btn-rc-print:hover { filter:brightness(1.07); }
.btn-rc-sign  { display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:10px;font-weight:700;font-size:.85rem;cursor:pointer;border:none;transition:.18s; }

/* Actions bas de page */
.rc-actions { display:flex;gap:14px;flex-wrap:wrap;justify-content:center;margin-top:8px;margin-bottom:40px; }
.btn-rc-dossier { display:inline-flex;align-items:center;gap:8px;padding:13px 28px;border-radius:12px;background:var(--pk);color:white;font-weight:700;font-size:.95rem;text-decoration:none;box-shadow:0 4px 16px rgba(219,39,119,.3);transition:.18s; }
.btn-rc-dossier:hover { background:var(--pk-dk);color:white;transform:translateY(-1px); }
.btn-rc-new { display:inline-flex;align-items:center;gap:8px;padding:13px 28px;border-radius:12px;background:white;color:var(--dark);font-weight:700;font-size:.95rem;text-decoration:none;border:1.5px solid var(--border);transition:.18s; }
.btn-rc-new:hover { background:#f8fafc;color:var(--dark); }

/* Synthèse */
.rc-synthese { background:white;border-radius:16px;border:1.5px solid var(--border);padding:20px 24px;margin-bottom:28px; }
.rc-synthese h4 { margin:0 0 16px;font-size:.95rem;font-weight:800;color:var(--dark);display:flex;align-items:center;gap:8px; }
.rc-grid2 { display:grid;grid-template-columns:1fr 1fr;gap:12px; }
.rc-field { background:#f8fafc;border-radius:10px;padding:10px 14px; }
.rc-field label { font-size:.7rem;font-weight:700;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:4px; }
.rc-field .val { font-size:.9rem;color:var(--dark);font-weight:600; }

/* Badge statut */
.rc-statut { display:inline-flex;align-items:center;gap:6px;background:#d1fae5;color:#065f46;border-radius:20px;padding:5px 14px;font-size:.8rem;font-weight:700; }

/* Sous-titres de section */
.rc-sub-title { font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);border-bottom:1px solid var(--border);padding-bottom:6px;margin:16px 0 10px; }
.rc-synthese { margin-bottom:20px; }
.rc-grid2 { display:grid;grid-template-columns:1fr 1fr;gap:10px; }
@media (max-width:600px) { .rc-grid2 { grid-template-columns:1fr; } }
</style>

<div class="rc-page">

    <!-- Hero -->
    <div class="rc-hero">
        <div class="avatar">👩</div>
        <div class="flex-grow-1">
            <h2>Consultation enregistrée !</h2>
            <p><?= htmlspecialchars($nom_patient) ?> — <?= $age_patient ?> — N° <?= htmlspecialchars($patient['dossier_numero'] ?? '') ?></p>
            <div>
                <span class="rc-success-badge"><i class="bi bi-check-circle-fill"></i> Consultation gynécologique enregistrée le <?= $dateConsult ?></span>
            </div>
        </div>
        <div class="text-end text-white opacity-75" style="font-size:.82rem;">
            <div class="fw-bold"><?= htmlspecialchars($medecinNom) ?></div>
            <?php if ($medecinSpec): ?><div><?= htmlspecialchars($medecinSpec) ?></div><?php endif; ?>
        </div>
    </div>

    <?php
    /* ── Helpers ── */
    function rcVal(array $d, string $k, string $def='—'): string {
        $v = $d[$k] ?? '';
        return htmlspecialchars(trim((string)$v) ?: $def);
    }
    function rcField(string $label, string $val, bool $full=false): string {
        $cls = $full ? 'style="grid-column:1/-1"' : '';
        return "<div class=\"rc-field\" {$cls}><label>{$label}</label><div class=\"val\">{$val}</div></div>";
    }
    function rcHasAny(array $d, array $keys): bool {
        foreach ($keys as $k) { if (!empty($d[$k])) return true; }
        return false;
    }
    ?>

    <!-- ══ 1. MOTIF & ANAMNÈSE ══ -->
    <?php if (rcHasAny($formData, ['motif','plaintes','gestite','parite','ddr','atcd_medicaux','atcd_chirurgicaux','atcd_familiaux','groupe_sanguin'])): ?>
    <div class="rc-synthese">
        <h4><i class="bi bi-journal-medical" style="color:#db2777;"></i> Motif &amp; Anamnèse</h4>
        <div class="rc-grid2">
            <?php
            if (!empty($formData['motif']))    echo rcField('Motif principal', rcVal($formData,'motif'), false);
            if (!empty($formData['date_consult'])) {
                $dc = $formData['date_consult'];
                echo rcField('Date', htmlspecialchars(date('d/m/Y', strtotime($dc))), false);
            }
            if (!empty($formData['plaintes'])) echo rcField('Plaintes &amp; Symptômes', rcVal($formData,'plaintes'), true);
            ?>
        </div>

        <?php if (rcHasAny($formData, ['gestite','parite','avortements','ddr','age_grossesse_sa','contraception','cycle_menstruel','menarche'])): ?>
        <div class="rc-sub-title">Antécédents gynéco-obstétricaux</div>
        <div class="rc-grid2">
            <?php
            $gpa = array_filter([
                $formData['gestite']    ?? null ? 'G'.$formData['gestite']    : null,
                $formData['parite']     ?? null ? 'P'.$formData['parite']     : null,
                $formData['avortements']?? null ? 'A'.$formData['avortements']: null,
            ]);
            if ($gpa) echo rcField('G/P/A', htmlspecialchars(implode(' — ', $gpa)));
            if (!empty($formData['ddr']))            echo rcField('DDR', htmlspecialchars(date('d/m/Y', strtotime($formData['ddr']))));
            if (!empty($formData['age_grossesse_sa']))echo rcField('Âge grossesse (SA)', rcVal($formData,'age_grossesse_sa'));
            if (!empty($formData['contraception']))  echo rcField('Contraception', rcVal($formData,'contraception'));
            if (!empty($formData['cycle_menstruel']))echo rcField('Cycle / Ménarche', rcVal($formData,'cycle_menstruel') . (!empty($formData['menarche']) ? ' / ' . htmlspecialchars($formData['menarche']) : ''));
            ?>
        </div>
        <?php endif; ?>

        <?php if (rcHasAny($formData, ['atcd_medicaux','atcd_chirurgicaux','atcd_familiaux'])): ?>
        <div class="rc-sub-title">Antécédents</div>
        <div class="rc-grid2">
            <?php
            if (!empty($formData['atcd_medicaux']))    echo rcField('Médicaux', rcVal($formData,'atcd_medicaux'), true);
            if (!empty($formData['atcd_chirurgicaux'])) echo rcField('Chirurgicaux / Obstétricaux', rcVal($formData,'atcd_chirurgicaux'), true);
            if (!empty($formData['atcd_familiaux']))    echo rcField('Familiaux', rcVal($formData,'atcd_familiaux'), true);
            ?>
        </div>
        <?php endif; ?>

        <?php if (rcHasAny($formData, ['groupe_sanguin','lav','aghbs','syphilis'])): ?>
        <div class="rc-sub-title">Sérologies</div>
        <div class="rc-grid2">
            <?php
            if (!empty($formData['groupe_sanguin'])) echo rcField('Groupe sanguin', rcVal($formData,'groupe_sanguin'));
            if (!empty($formData['lav']))             echo rcField('VIH/LAV', rcVal($formData,'lav'));
            if (!empty($formData['aghbs']))           echo rcField('Ag HBs', rcVal($formData,'aghbs'));
            if (!empty($formData['syphilis']))        echo rcField('Syphilis (TPHA)', rcVal($formData,'syphilis'));
            ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ══ 2. EXAMEN CLINIQUE ══ -->
    <?php if (rcHasAny($formData, ['ta','pouls','temperature','poids','taille','etat_general','seins','speculum','col_uterus','tv','bdcf'])): ?>
    <div class="rc-synthese">
        <h4><i class="bi bi-heart-pulse" style="color:#0f766e;"></i> Examen Clinique</h4>

        <?php if (rcHasAny($formData, ['ta','pouls','temperature','poids','taille','imc','etat_general','conjonctives','oedemes'])): ?>
        <div class="rc-sub-title">Constantes vitales &amp; État général</div>
        <div class="rc-grid2">
            <?php
            if (!empty($formData['ta']))          echo rcField('TA (mmHg)', rcVal($formData,'ta'));
            if (!empty($formData['pouls']))        echo rcField('Pouls (bpm)', rcVal($formData,'pouls'));
            if (!empty($formData['temperature'])) echo rcField('Température (°C)', rcVal($formData,'temperature'));
            if (!empty($formData['poids']))        echo rcField('Poids (kg)', rcVal($formData,'poids'));
            if (!empty($formData['taille']))       echo rcField('Taille (cm)', rcVal($formData,'taille'));
            if (!empty($formData['imc']))          echo rcField('IMC', rcVal($formData,'imc'));
            if (!empty($formData['etat_general'])) echo rcField('État général', rcVal($formData,'etat_general'));
            if (!empty($formData['conjonctives'])) echo rcField('Conjonctives', rcVal($formData,'conjonctives'));
            if (!empty($formData['oedemes']))      echo rcField('Œdèmes', rcVal($formData,'oedemes'));
            ?>
        </div>
        <?php endif; ?>

        <?php if (rcHasAny($formData, ['seins','speculum','col_uterus','tv','annexes'])): ?>
        <div class="rc-sub-title">Examen gynécologique</div>
        <div class="rc-grid2">
            <?php
            if (!empty($formData['seins']))      echo rcField('Examen des seins', rcVal($formData,'seins'), true);
            if (!empty($formData['speculum']))   echo rcField('Examen au spéculum', rcVal($formData,'speculum'), true);
            if (!empty($formData['col_uterus']))  echo rcField('Col utérin', rcVal($formData,'col_uterus'));
            if (!empty($formData['tv']))          echo rcField('Toucher vaginal (TV)', rcVal($formData,'tv'));
            if (!empty($formData['annexes']))     echo rcField('Annexes', rcVal($formData,'annexes'), true);
            ?>
        </div>
        <?php endif; ?>

        <?php if (rcHasAny($formData, ['bdcf','hu','presentation','liquide_amniotique','contractions','poche_eaux','maf'])): ?>
        <div class="rc-sub-title">Paramètres obstétricaux</div>
        <div class="rc-grid2">
            <?php
            if (!empty($formData['bdcf']))               echo rcField('BDCF (bpm)', rcVal($formData,'bdcf'));
            if (!empty($formData['hu']))                  echo rcField('HU (cm)', rcVal($formData,'hu'));
            if (!empty($formData['presentation']))        echo rcField('Présentation', rcVal($formData,'presentation'));
            if (!empty($formData['liquide_amniotique']))  echo rcField('Liquide amniotique', rcVal($formData,'liquide_amniotique'));
            if (!empty($formData['contractions']))        echo rcField('Contractions utérines', rcVal($formData,'contractions'));
            if (!empty($formData['poche_eaux']))          echo rcField('Poche des eaux', rcVal($formData,'poche_eaux'));
            if (!empty($formData['maf']))                 echo rcField('MAF', rcVal($formData,'maf'));
            ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ══ 3. DIAGNOSTIC & CONDUITE ══ -->
    <?php if (rcHasAny($formData, ['diagnostic','conduite_tenir','plan_traitement','observations','prochain_rdv','note_cloture'])): ?>
    <div class="rc-synthese">
        <h4><i class="bi bi-clipboard2-check" style="color:#7c3aed;"></i> Diagnostic &amp; Conduite</h4>
        <div class="rc-grid2">
            <?php
            if (!empty($formData['diagnostic']))      echo rcField('Diagnostic principal', rcVal($formData,'diagnostic'), true);
            if (!empty($formData['conduite_tenir']))  echo rcField('Conduite à tenir', rcVal($formData,'conduite_tenir'), true);
            if (!empty($formData['plan_traitement'])) echo rcField('Plan de traitement', rcVal($formData,'plan_traitement'), true);
            if (!empty($formData['observations']))    echo rcField('Observations', rcVal($formData,'observations'), true);
            if (!empty($formData['instructions_patient'])) echo rcField('Instructions au patient', rcVal($formData,'instructions_patient'), true);
            $rdv = $formData['prochain_rdv'] ?? '';
            if ($rdv) echo rcField('Prochain RDV', htmlspecialchars(date('d/m/Y', strtotime($rdv))));
            if (!empty($formData['note_cloture']))    echo rcField('Note de clôture', rcVal($formData,'note_cloture'), true);
            ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══ 4. ÉCHOGRAPHIE RÉALISÉE ══ -->
    <?php if (!empty($formData['echo_type']) || !empty($formData['echo_cr'])): ?>
    <div class="rc-synthese">
        <h4><i class="bi bi-activity" style="color:#0284c7;"></i> Échographie réalisée</h4>
        <div class="rc-grid2">
            <?php
            if (!empty($formData['echo_type']))        echo rcField('Type', rcVal($formData,'echo_type'));
            if (!empty($formData['echo_date']))        echo rcField('Date', htmlspecialchars(date('d/m/Y', strtotime($formData['echo_date']))));
            if (!empty($formData['echo_appareil']))    echo rcField('Appareil', rcVal($formData,'echo_appareil'));
            if (!empty($formData['echo_cr']))          echo rcField('Compte rendu', rcVal($formData,'echo_cr'), true);
            // Biométrie
            $bio = array_filter([
                !empty($formData['echo_bip']) ? 'BIP:' . $formData['echo_bip'] . 'mm' : null,
                !empty($formData['echo_lf'])  ? 'LF:'  . $formData['echo_lf']  . 'mm' : null,
                !empty($formData['echo_pc'])  ? 'PC:'  . $formData['echo_pc']  . 'mm' : null,
                !empty($formData['echo_pa'])  ? 'PA:'  . $formData['echo_pa']  . 'mm' : null,
                !empty($formData['echo_lcc']) ? 'LCC:' . $formData['echo_lcc'] . 'mm' : null,
            ]);
            if ($bio) echo rcField('Biométrie fœtale', htmlspecialchars(implode(' | ', $bio)), true);
            if (!empty($formData['echo_bdcf']))           echo rcField('BDCF (écho)', rcVal($formData,'echo_bdcf') . ' bpm');
            if (!empty($formData['echo_presentation']))   echo rcField('Présentation', rcVal($formData,'echo_presentation'));
            if (!empty($formData['echo_la']))             echo rcField('Liquide amniotique', rcVal($formData,'echo_la'));
            if (!empty($formData['echo_placenta']))       echo rcField('Placenta', rcVal($formData,'echo_placenta'));
            if (!empty($formData['echo_age_echo']))       echo rcField('Âge échographique', rcVal($formData,'echo_age_echo'));
            if (!empty($formData['echo_conclusion']))     echo rcField('Conclusion', rcVal($formData,'echo_conclusion'), true);
            if (!empty($formData['echo_recommandations']))echo rcField('Recommandations', rcVal($formData,'echo_recommandations'), true);
            ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Documents -->
    <div class="rc-docs-grid">

        <!-- Ordonnance -->
        <?php
        // Reconstruire les médicaments depuis formData (format ordonnance[0][medicament], etc.)
        $medicaments = [];
        if (!empty($formData['ordonnance']) && is_array($formData['ordonnance'])) {
            foreach ($formData['ordonnance'] as $m) {
                $nom = $m['medicament'] ?? $m['nom'] ?? '';
                if (trim($nom)) $medicaments[] = [
                    'nom'      => $nom,
                    'dosage'   => $m['dosage']    ?? '',
                    'voie'     => $m['voie']      ?? '',
                    'posologie'=> $m['frequence'] ?? $m['posologie'] ?? '',
                    'duree'    => $m['duree']      ?? '',
                ];
            }
        } else {
            // Format tableau : medoc_nom[], medoc_dosage[], etc.
            $noms     = (array)($formData['medoc_nom']     ?? $formData['medicament_nom']    ?? []);
            $dosages  = (array)($formData['medoc_dosage']  ?? $formData['medicament_dosage'] ?? []);
            $voies    = (array)($formData['medoc_voie']    ?? $formData['medicament_voie']   ?? []);
            $posol    = (array)($formData['medoc_posologie'] ?? $formData['posologie']        ?? []);
            $durees   = (array)($formData['medoc_duree']   ?? $formData['medicament_duree']  ?? []);
            foreach ($noms as $i => $nom) {
                if (trim($nom)) $medicaments[] = [
                    'nom'      => $nom,
                    'dosage'   => $dosages[$i]  ?? '',
                    'voie'     => $voies[$i]    ?? '',
                    'posologie'=> $posol[$i]    ?? '',
                    'duree'    => $durees[$i]   ?? '',
                ];
            }
        }
        ?>
        <div class="rc-doc-card">
            <div class="rc-doc-header">
                <div class="rc-doc-icon" style="background:#fdf2f8;color:#db2777;">💊</div>
                <div>
                    <div class="rc-doc-title">Ordonnance médicale</div>
                    <div class="rc-doc-subtitle"><?= count($medicaments) ?> médicament(s) prescrit(s)</div>
                </div>
            </div>
            <div class="rc-doc-body">
                <?php if ($medicaments): ?>
                <ul class="rc-doc-list">
                    <?php foreach ($medicaments as $i => $m): ?>
                    <li>
                        <span class="num"><?= $i+1 ?></span>
                        <span>
                            <strong><?= htmlspecialchars($m['nom']) ?><?= $m['dosage'] ? ' — ' . htmlspecialchars($m['dosage']) : '' ?></strong>
                            <?php if ($m['voie'] || $m['posologie']): ?>
                            <br><small class="text-muted">
                                <?= htmlspecialchars($m['voie']) ?><?= $m['posologie'] ? ' · ' . htmlspecialchars($m['posologie']) : '' ?><?= $m['duree'] ? ' · ' . htmlspecialchars($m['duree']) : '' ?>
                            </small>
                            <?php endif; ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="rc-doc-empty">Aucun médicament prescrit</div>
                <?php endif; ?>
            </div>
            <div class="rc-doc-footer">
                <button class="btn-rc-print" style="background:#fdf2f8;color:#db2777;"
                        onclick="rcImprimerOrdonnance()">
                    <i class="bi bi-printer"></i> Imprimer
                </button>
                <button class="btn-rc-sign" style="background:#db2777;color:white;"
                        onclick="rcSignerDocument('ordonnance')">
                    <i class="bi bi-pen"></i> Signer l'ordonnance
                </button>
            </div>
        </div>

        <!-- Bilan Labo -->
        <?php
        $laboData = json_decode($formData['labo_data'] ?? '[]', true) ?: [];
        ?>
        <div class="rc-doc-card">
            <div class="rc-doc-header">
                <div class="rc-doc-icon" style="background:#eff6ff;color:#1d4ed8;">🧪</div>
                <div>
                    <div class="rc-doc-title">Bilan Laboratoire</div>
                    <div class="rc-doc-subtitle"><?= count($laboData) ?> examen(s) demandé(s)</div>
                </div>
            </div>
            <div class="rc-doc-body">
                <?php if ($laboData): ?>
                <ul class="rc-doc-list">
                    <?php foreach ($laboData as $i => $ex): ?>
                    <li>
                        <span class="num"><?= $i+1 ?></span>
                        <span>
                            <strong><?= htmlspecialchars($ex['nom'] ?? $ex['examen_nom'] ?? '') ?></strong>
                            <?php if (!empty($ex['urgent'])): ?>
                            <span style="color:#dc2626;font-size:.72rem;font-weight:700;margin-left:4px;">URGENT</span>
                            <?php endif; ?>
                            <?php if (!empty($ex['categorie'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($ex['categorie']) ?></small>
                            <?php endif; ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="rc-doc-empty">Aucun examen de laboratoire demandé</div>
                <?php endif; ?>
            </div>
            <?php if ($laboData): ?>
            <div class="rc-doc-footer">
                <button class="btn-rc-print" style="background:#eff6ff;color:#1d4ed8;"
                        onclick="rcImprimerBilanLabo()">
                    <i class="bi bi-printer"></i> Imprimer
                </button>
                <button class="btn-rc-sign" style="background:#1d4ed8;color:white;"
                        onclick="rcSignerDocument('labo')">
                    <i class="bi bi-pen"></i> Signer le bulletin
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Bilan Imagerie -->
        <?php
        $imagData = json_decode($formData['imag_data'] ?? '[]', true) ?: [];
        ?>
        <div class="rc-doc-card">
            <div class="rc-doc-header">
                <div class="rc-doc-icon" style="background:#f0fdf4;color:#15803d;">📷</div>
                <div>
                    <div class="rc-doc-title">Bilan Imagerie</div>
                    <div class="rc-doc-subtitle"><?= count($imagData) ?> demande(s) d'imagerie</div>
                </div>
            </div>
            <div class="rc-doc-body">
                <?php if ($imagData): ?>
                <ul class="rc-doc-list">
                    <?php foreach ($imagData as $i => $d): ?>
                    <li>
                        <span class="num"><?= $i+1 ?></span>
                        <span>
                            <strong><?= ucfirst(htmlspecialchars($d['modalite'] ?? '')) ?></strong>
                            <?php if (!empty($d['partie_corps'])): ?> — <?= htmlspecialchars($d['partie_corps']) ?><?php endif; ?>
                            <?php if (($d['urgence'] ?? '') !== 'NORMAL' && !empty($d['urgence'])): ?>
                            <span style="color:#dc2626;font-size:.72rem;font-weight:700;margin-left:4px;"><?= htmlspecialchars($d['urgence']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($d['indication'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars(substr($d['indication'], 0, 60)) ?></small>
                            <?php endif; ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="rc-doc-empty">Aucune demande d'imagerie</div>
                <?php endif; ?>
            </div>
            <?php if ($imagData): ?>
            <div class="rc-doc-footer">
                <button class="btn-rc-print" style="background:#f0fdf4;color:#15803d;"
                        onclick="rcImprimerBilanImagerie()">
                    <i class="bi bi-printer"></i> Imprimer
                </button>
                <button class="btn-rc-sign" style="background:#15803d;color:white;"
                        onclick="rcSignerDocument('imagerie')">
                    <i class="bi bi-pen"></i> Signer le bulletin
                </button>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /rc-docs-grid -->

    <!-- Actions -->
    <div class="rc-actions">
        <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$patient['id'] ?>" class="btn-rc-dossier">
            <i class="bi bi-folder2-open"></i> Voir le dossier patient
        </a>
        <a href="<?= BASE_URL ?>formulaire/creer/consultation-gyneco/<?= (int)$patient['id'] ?>" class="btn-rc-new">
            <i class="bi bi-plus-circle"></i> Nouvelle consultation
        </a>
        <a href="<?= BASE_URL ?>dashboard" class="btn-rc-new">
            <i class="bi bi-house"></i> Tableau de bord
        </a>
    </div>

</div><!-- /rc-page -->

<!-- Modal signature supprimée — remplacée par rcOuvrirApercuSigne() -->

<script>
/* ── Données PHP → JS ── */
const RC_FORMULAIRE_ID = <?= (int)$formulaire_id ?>;
const RC_PATIENT_ID    = <?= (int)$patient['id'] ?>;
const RC_MEDECIN_NOM   = <?= json_encode($medecinNom) ?>;
const RC_MEDECIN_SPEC  = <?= json_encode($medecinSpec) ?>;
const RC_MEDECIN_ORD   = <?= json_encode($medecinOrdre) ?>;
const RC_SIG_B64       = <?= json_encode($signatureB64 ?? null) ?>;
const RC_CACHET_B64    = <?= json_encode($cachetB64 ?? null) ?>;
const RC_DATE_TODAY    = <?= json_encode(date('d/m/Y')) ?>;

const RC_MEDS          = <?= json_encode($medicaments) ?>;
const RC_LABO          = <?= json_encode($laboData) ?>;
const RC_IMAG          = <?= json_encode($imagData) ?>;
const RC_ORDONNANCE_ID = <?= (int)($formData['ordonnance_id'] ?? 0) ?>;

const RC_DIAGNOSTIC  = <?= json_encode($formData['diagnostic']  ?? '') ?>;
const RC_PATIENT_NOM = <?= json_encode($nom_patient) ?>;
const RC_DOSSIER     = <?= json_encode($patient['dossier_numero'] ?? '') ?>;
const RC_AGE         = <?= json_encode($age_patient) ?>;

/* ── Entête commune pour impression ── */
function rcEntete(titre, couleur) {
    return `<div style="font-family:Arial,sans-serif;max-width:780px;margin:0 auto;padding:28px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid ${couleur};padding-bottom:12px;margin-bottom:18px;">
            <div>
                <div style="font-weight:900;font-size:17px;color:${couleur};">${titre}</div>
                <div style="font-size:11px;color:#64748b;margin-top:2px;">Hôpital Saint-Jean de Malte — Njombé</div>
            </div>
            <div style="text-align:right;font-size:11px;color:#64748b;">
                <div><strong>Patient :</strong> ${RC_PATIENT_NOM}</div>
                <div><strong>N° Dossier :</strong> ${RC_DOSSIER}</div>
                <div><strong>Date :</strong> ${RC_DATE_TODAY}</div>
            </div>
        </div>`;
}

function rcBlocSignature() {
    if (!RC_SIG_B64 && !RC_CACHET_B64) return '';
    let stamp = '';
    if (RC_CACHET_B64 || RC_SIG_B64) {
        stamp = `<div style="position:relative;display:inline-block;width:110px;height:110px;margin-top:6px;">`;
        if (RC_CACHET_B64) stamp += `<img src="${RC_CACHET_B64}" style="width:110px;height:110px;object-fit:contain;opacity:0.85;">`;
        if (RC_SIG_B64)    stamp += `<img src="${RC_SIG_B64}" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);max-width:100px;max-height:80px;object-fit:contain;opacity:0.92;">`;
        stamp += `</div>`;
    }
    return `<div style="margin-top:36px;display:flex;justify-content:flex-end;">
        <div style="text-align:center;">
            <div style="font-size:11px;color:#64748b;">Fait le ${RC_DATE_TODAY}</div>
            <div style="font-size:11px;font-weight:700;">${RC_MEDECIN_NOM}</div>
            ${RC_MEDECIN_SPEC ? `<div style="font-size:10px;color:#64748b;">${RC_MEDECIN_SPEC}</div>` : ''}
            ${RC_MEDECIN_ORD  ? `<div style="font-size:10px;color:#64748b;">N° ${RC_MEDECIN_ORD}</div>` : ''}
            <div style="display:flex;justify-content:center;">${stamp}</div>
        </div>
    </div>`;
}

function rcOuvrirPrint(html) {
    const w = window.open('', '_blank', 'width=900,height=700');
    w.document.write('<!DOCTYPE html><html><head><title>Impression</title></head><body>' + html + '</body></html>');
    w.document.close();
    w.onload = () => { w.focus(); w.print(); };
}

/* ── Imprimer ordonnance ── */
function rcImprimerOrdonnance() {
    let lignes = '';
    RC_MEDS.forEach((m, i) => {
        lignes += `<div style="padding:9px 0;border-bottom:1px dashed #e2e8f0;">
            <div style="font-weight:700;font-size:13px;">${i+1}. ${m.nom}${m.dosage ? ' — ' + m.dosage : ''}</div>
            <div style="font-size:12px;color:#475569;margin-top:3px;">
                Voie : ${m.voie||'—'} &nbsp;·&nbsp; ${m.posologie||'—'} &nbsp;·&nbsp; Durée : ${m.duree||'—'}
            </div>
        </div>`;
    });
    const html = rcEntete('ORDONNANCE MÉDICALE', '#db2777')
        + (RC_DIAGNOSTIC ? `<div style="font-size:11px;margin-bottom:14px;color:#475569;"><strong>Diagnostic :</strong> ${RC_DIAGNOSTIC}</div>` : '')
        + (lignes || '<p style="color:#94a3b8;font-style:italic;">Aucun médicament prescrit.</p>')
        + rcBlocSignature()
        + '</div>';
    rcOuvrirPrint(html);
}

/* ── Imprimer bilan labo ── */
function rcImprimerBilanLabo() {
    if (!RC_LABO.length) { alert('Aucun examen de laboratoire.'); return; }
    let rows = RC_LABO.map(ex => `<tr>
        <td style="padding:7px 8px;border:1px solid #e2e8f0;font-weight:600;">${ex.nom||''}</td>
        <td style="padding:7px 8px;border:1px solid #e2e8f0;">${ex.categorie||'—'}</td>
        <td style="padding:7px 8px;border:1px solid #e2e8f0;">${ex.type_prelevement||'—'}</td>
        <td style="padding:7px 8px;border:1px solid #e2e8f0;text-align:center;">${ex.urgent?'<strong style="color:#dc2626">URGENT</strong>':'Normal'}</td>
    </tr>`).join('');
    const html = rcEntete('BULLETIN D\'EXAMENS — LABORATOIRE', '#1d4ed8')
        + `<table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead><tr style="background:#eff6ff;">
                <th style="padding:8px;border:1px solid #ddd;text-align:left;">Examen</th>
                <th style="padding:8px;border:1px solid #ddd;">Catégorie</th>
                <th style="padding:8px;border:1px solid #ddd;">Prélèvement</th>
                <th style="padding:8px;border:1px solid #ddd;text-align:center;">Urgence</th>
            </tr></thead><tbody>${rows}</tbody></table>`
        + rcBlocSignature()
        + '</div>';
    rcOuvrirPrint(html);
}

/* ── Imprimer bilan imagerie ── */
function rcImprimerBilanImagerie() {
    if (!RC_IMAG.length) { alert('Aucune demande d\'imagerie.'); return; }
    let rows = RC_IMAG.map(d => `<tr>
        <td style="padding:7px 8px;border:1px solid #e2e8f0;font-weight:600;">${ucfirst(d.modalite||'')}</td>
        <td style="padding:7px 8px;border:1px solid #e2e8f0;">${d.partie_corps||'—'}${d.cote?' ('+d.cote+')':''}</td>
        <td style="padding:7px 8px;border:1px solid #e2e8f0;">${d.indication||'—'}</td>
        <td style="padding:7px 8px;border:1px solid #e2e8f0;text-align:center;">${d.urgence==='URGENT'?'<strong style="color:#dc2626">URGENT</strong>':d.urgence==='TRES_URGENT'?'<strong style="color:#7c3aed">TRÈS URGENT</strong>':'Normal'}</td>
    </tr>`).join('');
    const html = rcEntete('BULLETIN D\'EXAMENS — IMAGERIE', '#15803d')
        + `<table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead><tr style="background:#f0fdf4;">
                <th style="padding:8px;border:1px solid #ddd;text-align:left;">Modalité</th>
                <th style="padding:8px;border:1px solid #ddd;">Région</th>
                <th style="padding:8px;border:1px solid #ddd;">Indication</th>
                <th style="padding:8px;border:1px solid #ddd;text-align:center;">Urgence</th>
            </tr></thead><tbody>${rows}</tbody></table>`
        + rcBlocSignature()
        + '</div>';
    rcOuvrirPrint(html);
}

function ucfirst(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

/* ══════════════════════════════════════════════════════════════════
   APERÇU + SIGNATURE — Ouvre une page de prévisualisation complète
   avec un bouton "Apposer la signature" qui :
   1. Affiche la signature/cachet sur le document
   2. Marque le document comme SIGNÉ en base
   ══════════════════════════════════════════════════════════════════ */
function rcSignerDocument(type) {
    let htmlContent = '';
    let docId       = 0;
    let couleur     = '#db2777';
    let titre       = '';

    if (type === 'ordonnance') {
        titre   = 'ORDONNANCE MÉDICALE';
        couleur = '#db2777';
        docId   = RC_ORDONNANCE_ID;
        let lignes = '';
        RC_MEDS.forEach((m, i) => {
            lignes += `<div style="padding:9px 0;border-bottom:1px dashed #e2e8f0;">
                <div style="font-weight:700;font-size:13px;">${i+1}. ${m.nom}${m.dosage ? ' — ' + m.dosage : ''}</div>
                <div style="font-size:12px;color:#475569;margin-top:3px;">
                    Voie : ${m.voie||'—'} &nbsp;·&nbsp; ${m.posologie||'—'} &nbsp;·&nbsp; Durée : ${m.duree||'—'}
                </div>
            </div>`;
        });
        htmlContent = (RC_DIAGNOSTIC ? `<div style="font-size:11px;margin-bottom:14px;padding:8px 12px;background:#f8fafc;border-radius:6px;color:#475569;"><strong>Diagnostic :</strong> ${RC_DIAGNOSTIC}</div>` : '')
            + (lignes || '<p style="color:#94a3b8;font-style:italic;">Aucun médicament prescrit.</p>');

    } else if (type === 'labo') {
        titre   = 'BULLETIN D\'EXAMENS DE LABORATOIRE';
        couleur = '#1d4ed8';
        const examens = RC_LABO;
        if (!examens.length) { alert('Aucun examen de laboratoire.'); return; }
        let rows = examens.map(ex => `<tr>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;font-weight:600;">${ex.nom||ex.name||''}</td>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;">${ex.categorie||'—'}</td>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;">${ex.type_prelevement||'—'}</td>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;text-align:center;">${ex.urgent?'<strong style="color:#dc2626">URGENT</strong>':'Normal'}</td>
        </tr>`).join('');
        htmlContent = `<table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead><tr style="background:#eff6ff;">
                <th style="padding:8px;border:1px solid #ddd;text-align:left;">Examen</th>
                <th style="padding:8px;border:1px solid #ddd;text-align:left;">Catégorie</th>
                <th style="padding:8px;border:1px solid #ddd;text-align:left;">Prélèvement</th>
                <th style="padding:8px;border:1px solid #ddd;text-align:center;">Urgence</th>
            </tr></thead><tbody>${rows}</tbody></table>`;

    } else if (type === 'imagerie') {
        titre   = 'DEMANDE D\'IMAGERIE / RADIOLOGIE';
        couleur = '#15803d';
        const demandes = RC_IMAG;
        if (!demandes.length) { alert('Aucune demande d\'imagerie.'); return; }
        let rows = demandes.map(d => `<tr>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;font-weight:600;">${d.modalite||''}</td>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;">${d.partie_corps||''}${d.cote?' ('+d.cote+')':''}</td>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;">${d.urgence||'Normal'}</td>
            <td style="padding:7px 8px;border:1px solid #e2e8f0;font-size:11px;">${(d.indication||'').substring(0,60)}</td>
        </tr>`).join('');
        htmlContent = `<table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead><tr style="background:#f0fdf4;">
                <th style="padding:8px;border:1px solid #ddd;text-align:left;">Modalité</th>
                <th style="padding:8px;border:1px solid #ddd;text-align:left;">Région</th>
                <th style="padding:8px;border:1px solid #ddd;text-align:center;">Urgence</th>
                <th style="padding:8px;border:1px solid #ddd;text-align:left;">Indication</th>
            </tr></thead><tbody>${rows}</tbody></table>`;
    }

    rcOuvrirApercuSigne(titre, couleur, htmlContent, type, docId);
}

/* ── Ouvre la page de prévisualisation avec toolbar de signature ── */
function rcOuvrirApercuSigne(titre, couleur, htmlContent, type, docId) {
    // Bloc signature/cachet (embarqué dans la fenêtre — pas de dépendance parent)
    let stampHtml = '';
    if (RC_CACHET_B64 || RC_SIG_B64) {
        stampHtml = `<div style="position:relative;display:inline-block;width:110px;height:110px;margin-top:6px;">`;
        if (RC_CACHET_B64) stampHtml += `<img src="${RC_CACHET_B64}" style="width:110px;height:110px;object-fit:contain;opacity:0.85;">`;
        if (RC_SIG_B64)    stampHtml += `<img src="${RC_SIG_B64}" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);max-width:100px;max-height:80px;object-fit:contain;opacity:0.92;">`;
        stampHtml += `</div>`;
    }

    const sigBlock = (RC_SIG_B64 || RC_CACHET_B64) ? `
        <div id="rcSigBlock" style="display:none;margin-top:36px;border-top:2px dashed ${couleur};padding-top:16px;">
            <div style="display:flex;justify-content:flex-end;">
                <div style="text-align:center;">
                    <div style="font-size:11px;color:#64748b;margin-bottom:4px;">Fait le ${RC_DATE_TODAY}</div>
                    <div style="font-size:12px;font-weight:700;">${RC_MEDECIN_NOM}</div>
                    ${RC_MEDECIN_SPEC ? `<div style="font-size:10px;color:#64748b;">${RC_MEDECIN_SPEC}</div>` : ''}
                    ${RC_MEDECIN_ORD  ? `<div style="font-size:10px;color:#64748b;">N° Ordre : ${RC_MEDECIN_ORD}</div>` : ''}
                    <div style="display:flex;justify-content:center;">${stampHtml}</div>
                </div>
            </div>
        </div>` : '';

    const baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : '';

    const win = window.open('', '_blank', 'width=950,height=820,resizable=yes');
    win.document.write(`<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>${titre}</title>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; font-family: Arial, sans-serif; background: #f0f4f8; }
  #rcBar {
      position: fixed; top: 0; left: 0; right: 0; z-index: 9999;
      background: #1e293b; color: white;
      padding: 10px 20px; display: flex; align-items: center; gap: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,.35);
  }
  #rcBar .bar-title { font-weight: 700; font-size: .88rem; flex: 1; }
  .tbtn { border: none; border-radius: 8px; padding: 8px 16px; cursor: pointer; font-weight: 700; font-size: .82rem; transition: .15s; }
  .tbtn:hover { filter: brightness(1.12); }
  .tbtn:disabled { opacity: .55; cursor: default; }
  #rcSpacer { height: 54px; }
  #rcSheet {
      max-width: 800px; margin: 24px auto 40px; background: white;
      border-radius: 12px; box-shadow: 0 2px 20px rgba(0,0,0,.1);
      padding: 32px 36px; font-family: Arial, sans-serif;
  }
  .doc-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid ${couleur}; padding-bottom: 12px; margin-bottom: 18px; }
  .doc-header .left .doc-title { font-weight: 900; font-size: 17px; color: ${couleur}; }
  .doc-header .left .doc-hopital { font-size: 11px; color: #64748b; margin-top: 2px; }
  .doc-header .right { text-align: right; font-size: 11px; color: #64748b; }
  #rcSignedBadge { display: none; background: #d1fae5; color: #065f46; border-radius: 20px; padding: 5px 14px; font-size: .78rem; font-weight: 700; align-items: center; gap: 6px; }
  #rcSignedBadge.show { display: inline-flex; }
  @media print {
      #rcBar, #rcSpacer { display: none !important; }
      #rcSheet { box-shadow: none; border-radius: 0; margin: 0; padding: 20px; }
      body { background: white; }
  }
</style>
</head>
<body>
  <div id="rcBar">
      <span class="bar-title">📄 ${titre}</span>
      <span id="rcSignedBadge">✓ Document signé</span>
      <button class="tbtn" style="background:#475569;color:white;" onclick="window.print()">🖨 Imprimer</button>
      <button class="tbtn" id="rcBtnSign" style="background:${couleur};color:white;" onclick="apposerSignature()">✍ Apposer la signature</button>
      <button class="tbtn" style="background:#374151;color:#9ca3af;" onclick="window.close()">✕ Fermer</button>
  </div>
  <div id="rcSpacer"></div>
  <div id="rcSheet">
      <div class="doc-header">
          <div class="left">
              <div class="doc-title">${titre}</div>
              <div class="doc-hopital">Hôpital Saint-Jean de Malte — Njombé</div>
          </div>
          <div class="right">
              <div><strong>Patient :</strong> ${RC_PATIENT_NOM}</div>
              <div><strong>Âge :</strong> ${RC_AGE}</div>
              <div><strong>N° Dossier :</strong> ${RC_DOSSIER}</div>
              <div><strong>Date :</strong> ${RC_DATE_TODAY}</div>
          </div>
      </div>
      ${htmlContent}
      ${sigBlock}
  </div>
  <script>
  const DOC_ID   = ${docId};
  const DOC_TYPE = ${JSON.stringify(type)};
  const BASE_URL = ${JSON.stringify(baseUrl)};

  async function apposerSignature() {
      const btn = document.getElementById('rcBtnSign');
      btn.disabled = true;
      btn.textContent = '⏳ Signature en cours…';
      try {
          // 1. Afficher la signature sur le document
          const sigBlock = document.getElementById('rcSigBlock');
          if (sigBlock) sigBlock.style.display = 'block';

          // 2. Marquer comme signé en base
          if (DOC_ID > 0) {
              const url = DOC_TYPE === 'ordonnance'
                  ? BASE_URL + 'pharmacie/signer-ordonnance/' + DOC_ID
                  : BASE_URL + 'formulaire/signer-bulletin/' + DOC_ID;
              await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' } });
          }

          // 3. Feedback visuel
          btn.style.display = 'none';
          const badge = document.getElementById('rcSignedBadge');
          if (badge) badge.classList.add('show');
      } catch(e) {
          btn.disabled = false;
          btn.textContent = '✍ Apposer la signature';
          alert('Erreur lors de la signature : ' + e.message);
      }
  }
  <\/script>
</body></html>`);
    win.document.close();
}
</script>
