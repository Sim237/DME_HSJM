<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
/* ══════════════════════════════════════════════════════
   BULLETIN D'EXAMENS — styles écran
   ══════════════════════════════════════════════════════ */
.paper-sheet {
    background: white;
    width: 21cm;
    padding: .8cm 1cm;
    margin: 16px auto;
    box-shadow: 0 4px 15px rgba(0,0,0,.15);
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: .82rem;
    color: #000;
}
.form-dotted {
    border: none;
    border-bottom: 1px dotted #555;
    background: transparent;
    padding: 0 3px;
    outline: none;
    font-weight: 600;
    color: #1d4ed8;
    font-size: .82rem;
}
/* En-tête */
.hospital-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #333;
    padding-bottom: 8px;
    margin-bottom: 6px;
}
.logo-area { display: flex; align-items: center; gap: 10px; }
.logo-area img { height: 58px; }
.hospital-name strong { font-size: .95rem; letter-spacing: .5px; }
.dept-checkboxes { border: 1.5px solid #000; padding: 6px 10px; border-radius: 3px; font-size: .78rem; }
.dept-checkboxes .form-check { margin-bottom: 2px; }

/* Titre */
.form-title { text-align: center; margin: 6px 0 8px; }
.form-title h2 { font-weight: 900; text-decoration: underline; text-underline-offset: 5px; font-size: 1.5rem; margin: 0; }
.form-title p  { font-size: .78rem; color: #555; margin: 2px 0 0; }

/* Infos patient */
.patient-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3px 20px;
    margin-bottom: 8px;
    line-height: 1.7;
    font-size: .8rem;
}

/* Grille principale */
.main-content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border: 2px solid #000;
}
.grid-col { padding: 8px 10px; display: flex; flex-direction: column; }
.grid-col-left { border-right: 2px solid #000; }
.col-header {
    text-align: center; font-weight: 700; text-decoration: underline;
    text-transform: uppercase; font-size: .78rem; margin-bottom: 6px;
}
.exam-item { display: flex; align-items: baseline; margin-bottom: 4px; font-size: .78rem; }
.exam-item .num { min-width: 18px; color: #888; }

/* Zone résultats */
.result-area { flex: 1; font-size: .78rem; }
.cat-title {
    font-weight: 700; font-size: .72rem; text-transform: uppercase;
    border-bottom: 1px solid #333; margin: 4px 0 2px; letter-spacing: .4px;
}
.res-table { width: 100%; border-collapse: collapse; font-size: .75rem; }
.res-table th { text-align: left; color: #555; font-weight: 600; padding: 1px 3px; }
.res-table td { padding: 2px 3px; border-bottom: 1px solid #eee; }
.res-anormal { background: #fff3cd; }
.res-val { text-align: center; }
.res-ref { text-align: center; color: #666; font-size: .68rem; }

/* Zone signature */
.sig-box {
    margin-top: 8px; padding-top: 6px;
    border-top: 1px solid #ccc;
    display: flex; justify-content: space-between; align-items: flex-end;
    font-size: .75rem;
}
.sig-zone { text-align: center; }
.sig-zone img { max-height: 55px; max-width: 100px; display: block; margin: 0 auto 2px; }
.sig-cachet img { max-height: 55px; max-width: 55px; }
.sig-name { font-size: .68rem; color: #333; font-weight: 600; margin-top: 2px; }

/* Renseignements */
.clinical-note {
    font-size: .78rem; border: 1px solid #ccc; border-radius: 3px;
    padding: 4px; resize: none; width: 100%; margin-top: 4px;
}
.waiting-msg { color: #888; font-style: italic; font-size: .78rem; padding: 10px 0; }

/* Barre d'action */
.action-bar {
    position: sticky; top: 0; z-index: 1000;
    background: rgba(255,255,255,.95);
    backdrop-filter: blur(4px);
    border-bottom: 1px solid #e2e8f0;
}

/* Modal signature */
.sig-modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.5); z-index: 9999;
    align-items: center; justify-content: center;
}
.sig-modal-overlay.active { display: flex; }
.sig-modal {
    background: white; border-radius: 12px;
    padding: 24px; width: 420px;
    box-shadow: 0 20px 60px rgba(0,0,0,.3);
}
.sig-preview { display: flex; gap: 16px; margin-top: 12px; }
.sig-preview-box {
    flex: 1; border: 2px dashed #cbd5e1; border-radius: 8px;
    padding: 8px; text-align: center; font-size: .78rem; color: #64748b;
}
.sig-preview-box img { max-height: 70px; max-width: 100%; margin-bottom: 4px; display: block; }
.sig-preview-box.has-img { border-color: #22c55e; }

/* ══════════════════════════════════════════════════════
   IMPRESSION — s'adapte au papier choisi dans la boîte
   de dialogue d'impression (A4, A5, continu, etc.)
   ══════════════════════════════════════════════════════ */
@media print {
    /* Laisser le pilote d'imprimante définir le format du papier */
    @page {
        size: auto;
        margin: 10mm 12mm;
    }

    /* Masquer les éléments de l'interface */
    .no-print,
    .action-bar,
    .sig-modal-overlay { display: none !important; }

    body { background: white !important; margin: 0; padding: 0; }

    /* La feuille occupe toute la largeur disponible du papier choisi */
    .paper-sheet {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;   /* les marges @page prennent le relais */
        box-shadow: none !important;
        font-size: 9pt !important;
    }

    /* Texte des champs en noir pur */
    .form-dotted { color: #000 !important; }

    /* Supprimer les fonds colorés sur les lignes anormales */
    .res-anormal { background: none !important; font-weight: bold; }

    /* Éviter les coupures à l'intérieur des blocs importants */
    .exam-item,
    .res-table tr,
    .sig-box { page-break-inside: avoid; break-inside: avoid; }

    /* Grille : basculer en colonne unique sur les petits papiers
       (le navigateur choisit automatiquement via auto-fit) */
    .main-content-grid {
        grid-template-columns: 1fr 1fr;
    }
    .patient-info-grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<div class="container-fluid bg-light pb-4 no-print">
    <!-- BARRE D'ACTIONS -->
    <div class="action-bar py-2 px-4">
        <div class="d-flex justify-content-between align-items-center">
            <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <div class="d-flex gap-2 align-items-center">
                <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Imprimer
                </button>
                <?php if (empty($bulletin['signe'])): ?>
                <button class="btn btn-success btn-sm px-3" onclick="ouvrirModalSignature(<?= $bulletin['id'] ?>)">
                    <i class="bi bi-pen-fill me-1"></i> Signer le Bulletin
                </button>
                <?php else: ?>
                <span class="btn btn-outline-success btn-sm disabled">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Signé le <?= date('d/m/Y', strtotime($bulletin['date_signature'])) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- FEUILLE PAPIER -->
<div class="paper-sheet">

    <!-- EN-TÊTE -->
    <div class="hospital-header">
        <div class="logo-area">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" alt="Logo">
            <div class="hospital-name">
                <strong>ORDRE DE MALTE</strong><br>
                <span style="font-size:.78rem;font-weight:600;">HÔPITAL SAINT-JEAN DE MALTE</span><br>
                <span style="font-size:.7rem;color:#555;">B.P.: 56 NJOMBE — Tél.: (237) 697 09 29 92</span>
            </div>
        </div>
        <div class="dept-checkboxes">
            <div class="form-check"><input type="checkbox" class="form-check-input" <?= $bulletin['radio'] ? 'checked' : '' ?> disabled><label class="form-check-label fw-bold">RADIOLOGIE</label></div>
            <div class="form-check"><input type="checkbox" class="form-check-input" <?= $bulletin['echo']  ? 'checked' : '' ?> disabled><label class="form-check-label fw-bold">ECHOGRAPHIE</label></div>
            <div class="form-check"><input type="checkbox" class="form-check-input" <?= $bulletin['labo']  ? 'checked' : '' ?> disabled><label class="form-check-label fw-bold">LABORATOIRE</label></div>
        </div>
    </div>

    <!-- TITRE -->
    <div class="form-title">
        <h2>BULLETIN D'EXAMENS</h2>
        <p>Résultats d'Analyses — <?= date('d/m/Y', strtotime($bulletin['date_creation'])) ?></p>
    </div>

    <!-- INFOS PATIENT -->
    <div class="patient-info-grid">
        <div>Nom et Prénom :
            <input class="form-dotted" style="width:65%;" value="<?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?>" readonly>
        </div>
        <div>Profession :
            <input class="form-dotted" style="width:65%;" value="<?= htmlspecialchars($bulletin['profession'] ?? '') ?>" readonly>
        </div>
        <div>
            Âge : <input class="form-dotted" style="width:30px;" value="<?= $age ?>" readonly> ans
            &nbsp; Sexe : <input class="form-dotted" style="width:70px;" value="<?= $patient['sexe']=='M'?'Masculin':'Féminin' ?>" readonly>
        </div>
        <div>
            Service : <input class="form-dotted" style="width:38%;" value="<?= htmlspecialchars($bulletin['service'] ?? 'Consultation Extr.') ?>" readonly>
            &nbsp;Ch. : <input class="form-dotted" style="width:35px;" value="<?= htmlspecialchars($bulletin['chambre'] ?? '') ?>" readonly>
            &nbsp;Lit : <input class="form-dotted" style="width:30px;" value="<?= htmlspecialchars($bulletin['lit'] ?? '') ?>" readonly>
        </div>
    </div>

    <!-- GRILLE PRINCIPALE -->
    <div class="main-content-grid">

        <!-- COLONNE GAUCHE : Examens demandés -->
        <div class="grid-col grid-col-left">
            <div class="col-header">Type d'examens demandés</div>

            <?php
            $nbLines = max(count($examens) + 2, 8);
            for ($i = 0; $i < $nbLines; $i++):
                $val = $examens[$i] ?? '';
            ?>
            <div class="exam-item">
                <span class="num"><?= $i+1 ?>.</span>
                <input class="form-dotted w-100" value="<?= htmlspecialchars($val) ?>" readonly>
            </div>
            <?php endfor; ?>

            <!-- Renseignements cliniques -->
            <div style="margin-top:6px;">
                <div style="font-weight:600;font-size:.75rem;margin-bottom:2px;">Renseignements cliniques :</div>
                <textarea class="clinical-note" rows="2" readonly><?= htmlspecialchars($bulletin['renseignements'] ?? '') ?></textarea>
            </div>

            <!-- Signature médecin -->
            <div class="sig-box">
                <div>
                    <div>Date : <input class="form-dotted" style="width:80px;" value="<?= date('d/m/Y', strtotime($bulletin['date_creation'])) ?>" readonly></div>
                </div>
                <div class="sig-zone">
                    <?php if (!empty($bulletin['signe']) && !empty($bulletin['signature_data'])): ?>
                        <img src="<?= htmlspecialchars($bulletin['signature_data']) ?>" alt="Signature">
                    <?php elseif (!empty($bulletin['signature_path'])): ?>
                        <img src="<?= BASE_URL . htmlspecialchars($bulletin['signature_path']) ?>" alt="Signature">
                    <?php else: ?>
                        <div style="height:40px;"></div>
                    <?php endif; ?>
                    <?php if (!empty($bulletin['cachet_path'])): ?>
                    <div class="sig-cachet" style="display:inline-block;vertical-align:middle;margin-left:8px;">
                        <img src="<?= BASE_URL . htmlspecialchars($bulletin['cachet_path']) ?>" alt="Cachet">
                    </div>
                    <?php endif; ?>
                    <div class="sig-name">Dr. <?= htmlspecialchars(($bulletin['medecin_prenom']??'').' '.($bulletin['medecin_nom']??'')) ?></div>
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE : Résultats -->
        <div class="grid-col grid-col-right">
            <div class="col-header">Résultats / Intervention</div>

            <?php if (!empty($resultats_live)): ?>
            <div class="result-area">
                <?php
                $parCategorie = [];
                foreach ($resultats_live as $r) $parCategorie[$r['categorie']][] = $r;
                foreach ($parCategorie as $cat => $lignes):
                ?>
                <div class="cat-title"><?= htmlspecialchars($cat) ?></div>
                <table class="res-table">
                    <thead><tr>
                        <th>Examen</th><th class="res-val">Résultat</th><th class="res-ref">Réf.</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($lignes as $l):
                        $anormal = $l['anormal'] || ($l['valeur_numerique'] !== null && $l['valeur_normale_min'] !== null
                            && ($l['valeur_numerique'] < $l['valeur_normale_min'] || $l['valeur_numerique'] > $l['valeur_normale_max']));
                    ?>
                    <tr class="<?= $anormal ? 'res-anormal' : '' ?>">
                        <td><?= htmlspecialchars($l['nom_examen']) ?></td>
                        <td class="res-val" style="font-weight:<?= $anormal?'700':'400' ?>">
                            <?= htmlspecialchars($l['resultat'] !== '' ? $l['resultat'] : ($l['valeur_numerique'] !== null ? $l['valeur_numerique'] : '—')) ?>
                            <?= $l['unite'] ? '<span style="color:#666;font-size:.68rem;"> '.htmlspecialchars($l['unite']).'</span>' : '' ?>
                            <?= $anormal ? ' <span style="color:#c00;font-size:.7rem;">▲</span>' : '' ?>
                        </td>
                        <td class="res-ref">
                            <?= $l['valeur_normale_min'] !== null ? htmlspecialchars($l['valeur_normale_min'].' – '.$l['valeur_normale_max']) : '' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endforeach; ?>
            </div>
            <?php elseif (!empty($bulletin['resultats'])): ?>
            <div class="result-area" style="white-space:pre-line;"><?= htmlspecialchars($bulletin['resultats']) ?></div>
            <?php else: ?>
            <div class="result-area waiting-msg">En attente des résultats du laboratoire</div>
            <?php endif; ?>

            <!-- Signature labo -->
            <div class="sig-box">
                <div>Date : <input class="form-dotted" style="width:80px;" placeholder="../../...." readonly></div>
                <div class="sig-zone">
                    <div style="height:40px;border-bottom:1px dotted #999;width:90px;"></div>
                    <div class="sig-name">Technicien / Biologiste</div>
                </div>
            </div>
        </div>

    </div>

    <div style="text-align:center;color:#999;font-size:.65rem;margin-top:6px;">
        SimCare+ — Hôpital Saint-Jean de Malte &nbsp;|&nbsp; Document généré le <?= date('d/m/Y à H:i') ?>
    </div>
</div>


<!-- MODAL SIGNATURE ÉLECTRONIQUE -->
<div class="sig-modal-overlay" id="sigModalOverlay">
    <div class="sig-modal">
        <h6 class="fw-bold mb-1"><i class="bi bi-pen-fill me-2 text-success"></i>Signature électronique</h6>
        <p class="text-muted small mb-3">La signature et le cachet enregistrés dans votre profil seront appliqués au bulletin.</p>

        <div class="sig-preview">
            <div class="sig-preview-box <?= !empty($bulletin['signature_path']) ? 'has-img' : '' ?>" id="previewSig">
                <?php if (!empty($bulletin['signature_path'])): ?>
                    <img src="<?= BASE_URL . htmlspecialchars($bulletin['signature_path']) ?>" alt="Signature">
                    <span>Signature</span>
                <?php else: ?>
                    <i class="bi bi-exclamation-circle text-warning d-block mb-1" style="font-size:1.4rem;"></i>
                    Aucune signature enregistrée dans votre profil
                <?php endif; ?>
            </div>
            <div class="sig-preview-box <?= !empty($bulletin['cachet_path']) ? 'has-img' : '' ?>" id="previewCachet">
                <?php if (!empty($bulletin['cachet_path'])): ?>
                    <img src="<?= BASE_URL . htmlspecialchars($bulletin['cachet_path']) ?>" alt="Cachet">
                    <span>Cachet</span>
                <?php else: ?>
                    <i class="bi bi-info-circle text-secondary d-block mb-1" style="font-size:1.4rem;"></i>
                    Aucun cachet enregistré
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($bulletin['signature_path'])): ?>
        <div class="alert alert-warning py-2 mt-3 small">
            Vous n'avez pas encore enregistré votre signature.
            <a href="<?= BASE_URL ?>profil" target="_blank">Aller dans Mon Profil</a> pour l'ajouter.
        </div>
        <?php endif; ?>

        <div class="d-flex gap-2 mt-3 justify-content-end">
            <button class="btn btn-outline-secondary btn-sm" onclick="fermerModalSignature()">Annuler</button>
            <button class="btn btn-success btn-sm px-4" id="btnConfirmerSignature"
                    onclick="confirmerSignature()"
                    <?= empty($bulletin['signature_path']) ? 'disabled' : '' ?>>
                <i class="bi bi-check2-circle me-1"></i> Confirmer et Signer
            </button>
        </div>
    </div>
</div>

<script>
var currentBulletinId = null;

function ouvrirModalSignature(id) {
    currentBulletinId = id;
    document.getElementById('sigModalOverlay').classList.add('active');
}
function fermerModalSignature() {
    document.getElementById('sigModalOverlay').classList.remove('active');
}

function confirmerSignature() {
    var btn = document.getElementById('btnConfirmerSignature');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Signature...';

    fetch('<?= BASE_URL ?>formulaire/signer-bulletin/' + currentBulletinId, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            fermerModalSignature();
            location.reload();
        } else {
            alert('Erreur : ' + (data.message || 'Impossible de signer'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Confirmer et Signer';
        }
    })
    .catch(() => {
        alert('Erreur réseau.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Confirmer et Signer';
    });
}

document.getElementById('sigModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) fermerModalSignature();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
