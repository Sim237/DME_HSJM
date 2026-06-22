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
FICHIER : app/views/laboratoire/saisie_resultats.php
============================================================================ */
require_once __DIR__ . '/../layouts/header.php';
$demande                = $demande ?? [];
$examens                = $examens ?? [];
$piecesJointes          = $piecesJointes ?? [];          // orphelins (examen_id = NULL)
$piecesJointesParExamen = $piecesJointesParExamen ?? []; // [examen_id => [pj, ...]]

if (empty($demande)) {
    echo '<div class="alert alert-danger m-4">Demande introuvable</div>';
    exit;
}

/** Helper : icône bootstrap selon le type MIME */
$iconePj = function($mime, $nom = '') {
    $mime = strtolower($mime ?? '');
    $ext = strtolower(pathinfo($nom, PATHINFO_EXTENSION));
    if (str_contains($mime, 'pdf') || $ext === 'pdf')                   return ['bi-file-earmark-pdf-fill', '#dc2626'];
    if (str_contains($mime, 'image/') || in_array($ext, ['png','jpg','jpeg','gif','webp','tif','tiff','bmp','heic']))
                                                                          return ['bi-file-earmark-image-fill', '#2563eb'];
    if (str_contains($mime, 'sheet') || str_contains($mime, 'excel') || in_array($ext, ['xlsx','xls','csv']))
                                                                          return ['bi-file-earmark-excel-fill', '#16a34a'];
    if (str_contains($mime, 'word') || in_array($ext, ['doc','docx']))   return ['bi-file-earmark-word-fill', '#1e40af'];
    return ['bi-file-earmark-fill', '#64748b'];
};
/** Helper : taille humaine */
$tailleHumaine = function($octets) {
    if ($octets === null) return '—';
    if ($octets < 1024) return "$octets o";
    if ($octets < 1048576) return round($octets/1024, 1) . ' Ko';
    return round($octets/1048576, 1) . ' Mo';
};

$age = '-';
if (!empty($demande['date_naissance'])) {
    try { $age = date_diff(date_create($demande['date_naissance']), date_create('now'))->y . ' ans'; } catch(Exception $e) {}
}
?>

<style>
/* ── Layout ────────────────────────────────────────────────────── */
body { background: #f0f4f8; }

/* ── Top bar ───────────────────────────────────────────────────── */
.labo-topbar {
    background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 60%, #388e3c 100%);
    color: #fff;
    padding: 0 2rem;
    height: 62px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 1000;
    box-shadow: 0 2px 12px rgba(27,94,32,.35);
}
.labo-topbar .brand {
    display: flex; align-items: center; gap: .75rem;
    font-size: 1.05rem; font-weight: 700;
}
.labo-topbar .brand-icon {
    width: 36px; height: 36px;
    background: rgba(255,255,255,.15);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.15rem;
}
.labo-topbar .page-title {
    font-size: .95rem; font-weight: 600; opacity: .9;
    padding: .35rem 1rem;
    background: rgba(255,255,255,.12);
    border-radius: 20px; border: 1px solid rgba(255,255,255,.2);
}
.labo-topbar .topbar-actions { display: flex; align-items: center; gap: .7rem; }
.btn-topbar {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
    color: #fff; border-radius: 8px;
    padding: .4rem 1rem; font-size: .875rem; font-weight: 600;
    text-decoration: none; cursor: pointer;
    display: inline-flex; align-items: center; gap: .4rem;
    transition: background .2s;
}
.btn-topbar:hover { background: rgba(255,255,255,.25); color: #fff; }
.btn-topbar.preview { background: rgba(255,255,255,.22); border-color: rgba(255,255,255,.4); }

/* ── Patient banner ────────────────────────────────────────────── */
.patient-banner {
    background: linear-gradient(120deg, #1b5e20 0%, #2e7d32 50%, #00695c 100%);
    color: #fff;
    padding: 1.4rem 2.5rem;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.pat-avatar {
    width: 52px; height: 52px;
    background: rgba(255,255,255,.2); border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; border: 2px solid rgba(255,255,255,.4); flex-shrink: 0;
}
.pat-name { font-size: 1.2rem; font-weight: 700; }
.pat-sub  { font-size: .82rem; opacity: .85; margin-top: .15rem; }
.banner-meta { display: flex; gap: 2.5rem; flex-wrap: wrap; }
.bm-item label {
    font-size: .72rem; text-transform: uppercase; letter-spacing: .8px;
    opacity: .75; display: block; margin-bottom: .1rem;
}
.bm-item .bm-val { font-weight: 600; font-size: .9rem; }

/* ── Main content ──────────────────────────────────────────────── */
.labo-content { max-width: 1300px; margin: 0 auto; padding: 2rem 2rem 5rem; }

/* ── Catégorie header ──────────────────────────────────────────── */
.cat-header {
    display: flex; align-items: center; gap: .7rem;
    padding: .9rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 16px 16px 0 0;
}
.cat-icon {
    width: 32px; height: 32px;
    background: linear-gradient(135deg, #2e7d32, #388e3c);
    border-radius: 8px; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem;
}
.cat-name { font-weight: 700; color: #1e293b; font-size: .95rem; }
.cat-count {
    margin-left: auto;
    background: #dcfce7; color: #15803d;
    font-size: .75rem; font-weight: 700;
    padding: .2rem .65rem; border-radius: 20px;
    border: 1px solid #bbf7d0;
}

/* ── Exam card ─────────────────────────────────────────────────── */
.exam-wrap {
    background: #fff; border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    overflow: hidden; margin-bottom: 1.5rem;
}
.exam-card {
    background: #fff;
    border-bottom: 1px solid #f1f5f9;
    padding: 1.4rem 1.8rem;
    transition: background .15s;
}
.exam-card:last-child { border-bottom: none; }
.exam-card:hover { background: #fafcff; }
.exam-card.exam-non-facture {
    background: linear-gradient(90deg, #fffbeb 0%, #fff 50%);
    border-left: 3px solid #f59e0b;
}

/* Badges Facturé / Non facturé pour le laborantin */
.badge-fact {
    display: inline-block;
    font-size: .65rem; font-weight: 800;
    padding: 3px 9px; border-radius: 12px;
    margin-left: 8px;
    vertical-align: middle;
    text-transform: uppercase; letter-spacing: .3px;
}
.badge-fact.ok { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
.badge-fact.ko { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.exam-title-row {
    display: flex; align-items: flex-start;
    justify-content: space-between; margin-bottom: 1.2rem;
}
.exam-name { font-size: 1rem; font-weight: 700; color: #1e293b; }
.exam-meta { font-size: .78rem; color: #94a3b8; margin-top: .2rem; }
.badge-urgent {
    background: #fef2f2; color: #dc2626; font-size: .75rem; font-weight: 800;
    padding: .3rem .8rem; border-radius: 20px; border: 1px solid #fecaca;
    animation: pulse-red 1.5s infinite; white-space: nowrap;
}
@keyframes pulse-red {
    0%,100% { box-shadow: 0 0 0 0 rgba(220,38,38,.3); }
    50%      { box-shadow: 0 0 0 5px rgba(220,38,38,.0); }
}

/* ── Fields ────────────────────────────────────────────────────── */
.field-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.2rem; align-items: start; }
@media (max-width: 900px) { .field-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 600px) { .field-grid { grid-template-columns: 1fr; } }

.field-label {
    font-size: .72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .7px; color: #64748b; margin-bottom: .4rem;
}
.result-input-group { display: flex; }
.result-input {
    flex: 1; font-size: 1rem; font-weight: 700; text-align: center;
    border: 2px solid #e2e8f0; border-radius: 10px 0 0 10px;
    padding: .6rem .9rem; transition: border-color .2s;
}
.result-input:focus { border-color: #2e7d32; outline: none; box-shadow: 0 0 0 3px rgba(46,125,50,.12); }
.result-input.is-normal   { border-color: #22c55e; background: #f0fdf4; }
.result-input.is-abnormal { border-color: #f59e0b; background: #fffbeb; }
.result-input.is-critical { border-color: #ef4444; background: #fff5f5; }
.result-unit {
    background: #f1f5f9; border: 2px solid #e2e8f0; border-left: none;
    border-radius: 0 10px 10px 0; padding: .6rem .85rem;
    font-size: .82rem; font-weight: 600; color: #64748b; white-space: nowrap;
}
.norm-hint { font-size: .72rem; color: #94a3b8; margin-top: .3rem; }

.field-input {
    width: 100%; border: 2px solid #e2e8f0; border-radius: 10px;
    padding: .6rem .9rem; font-size: .88rem;
    transition: border-color .2s;
}
.field-input:focus { border-color: #2e7d32; outline: none; box-shadow: 0 0 0 3px rgba(46,125,50,.12); }
.field-textarea {
    width: 100%; border: 2px solid #e2e8f0; border-radius: 10px;
    padding: .6rem .9rem; font-size: .88rem; resize: vertical;
    transition: border-color .2s;
}
.field-textarea:focus { border-color: #2e7d32; outline: none; box-shadow: 0 0 0 3px rgba(46,125,50,.12); }

.qc-check {
    display: inline-flex; align-items: center; gap: .5rem;
    background: #f0fdf4; border: 1px solid #bbf7d0;
    border-radius: 8px; padding: .5rem .9rem;
    font-size: .82rem; font-weight: 600; color: #15803d; cursor: pointer;
    margin-top: .8rem;
}
.qc-check input { width: 16px; height: 16px; accent-color: #22c55e; }

/* ── Notes générales ───────────────────────────────────────────── */
.notes-section {
    background: #fff; border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    padding: 1.4rem 1.8rem; margin-bottom: 1.5rem;
}
.notes-section .sec-title {
    font-size: .8rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .7px; color: #64748b; margin-bottom: .8rem;
    display: flex; align-items: center; gap: .4rem;
}

/* ── Actions footer ────────────────────────────────────────────── */
.actions-section {
    background: #fff; border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    padding: 1.4rem 1.8rem;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.certif-checks { display: flex; flex-direction: column; gap: .6rem; }
.certif-check {
    display: flex; align-items: center; gap: .6rem;
    font-size: .87rem; font-weight: 600; color: #374151; cursor: pointer;
}
.certif-check input { width: 17px; height: 17px; accent-color: #2e7d32; }
.certif-check.optional { font-weight: 400; color: #64748b; }

.btn-draft {
    background: #f1f5f9; color: #475569; border: 2px solid #e2e8f0;
    border-radius: 10px; padding: .65rem 1.4rem;
    font-size: .88rem; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: .4rem;
    transition: background .2s;
}
.btn-draft:hover { background: #e2e8f0; }
.btn-validate {
    background: linear-gradient(135deg, #2e7d32, #388e3c);
    color: #fff; border: none; border-radius: 12px;
    padding: .75rem 2rem; font-size: .95rem; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: .5rem;
    box-shadow: 0 4px 14px rgba(46,125,50,.35);
    transition: opacity .2s, transform .15s;
}
.btn-validate:hover { opacity: .9; transform: translateY(-1px); }

/* ── Toast autosave ────────────────────────────────────────────── */
.autosave-toast {
    position: fixed; bottom: 1.5rem; right: 1.5rem;
    background: #1e293b; color: #fff; border-radius: 12px;
    padding: .7rem 1.2rem; font-size: .82rem; font-weight: 600;
    display: flex; align-items: center; gap: .5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,.25);
    opacity: 0; transition: opacity .4s; pointer-events: none; z-index: 9999;
}
.autosave-toast.show { opacity: 1; }

/* ── Exam déjà renseigné ───────────────────────────────────────── */
.exam-card.exam-done {
    border-left: 4px solid #22c55e;
    background: linear-gradient(90deg, #f0fdf4 0%, #fff 60%);
}
.badge-done {
    display: inline-flex; align-items: center; gap: .3rem;
    background: #dcfce7; color: #15803d;
    font-size: .72rem; font-weight: 800;
    padding: .25rem .7rem; border-radius: 20px;
    border: 1px solid #86efac;
}
.badge-pending {
    display: inline-flex; align-items: center; gap: .3rem;
    background: #fef9c3; color: #854d0e;
    font-size: .72rem; font-weight: 800;
    padding: .25rem .7rem; border-radius: 20px;
    border: 1px solid #fde68a;
}
/* ── Barre de progression ──────────────────────────────────────── */
.progress-bar-wrap {
    background: #fff; border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    padding: 1rem 1.5rem; margin-bottom: 1.5rem;
    display: flex; align-items: center; gap: 1.2rem;
}
.progress-bar-wrap .pb-label {
    font-size: .85rem; font-weight: 700; color: #374151; white-space: nowrap;
}
.progress-bar-wrap .pb-track {
    flex: 1; height: 10px; background: #e2e8f0; border-radius: 10px; overflow: hidden;
}
.progress-bar-wrap .pb-fill {
    height: 100%; background: linear-gradient(90deg, #22c55e, #16a34a);
    border-radius: 10px; transition: width .4s;
}
.progress-bar-wrap .pb-count {
    font-size: .82rem; font-weight: 700; color: #15803d; white-space: nowrap;
}
/* ── Alerte flash ──────────────────────────────────────────────── */
.flash-success {
    background: #f0fdf4; border: 1px solid #86efac; color: #15803d;
    border-radius: 12px; padding: .85rem 1.3rem; margin-bottom: 1.2rem;
    font-weight: 600; font-size: .88rem;
    display: flex; align-items: center; gap: .6rem;
}
.flash-error {
    background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626;
    border-radius: 12px; padding: .85rem 1.3rem; margin-bottom: 1.2rem;
    font-weight: 600; font-size: .88rem;
    display: flex; align-items: center; gap: .6rem;
}

/* ── Pièces jointes (upload + liste) ────────────────────────────── */
.attach-section {
    background: #fff; border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    padding: 1.4rem 1.8rem; margin-bottom: 1.5rem;
}
.attach-dropzone {
    border: 2.5px dashed #cbd5e1; border-radius: 14px;
    padding: 2rem 1.5rem; text-align: center;
    background: #f8fafc; cursor: pointer;
    transition: all .18s;
}
.attach-dropzone:hover, .attach-dropzone.drag-over {
    border-color: #2e7d32; background: #f0fdf4;
}
.attach-dropzone .dz-icon {
    font-size: 2rem; color: #94a3b8; display: block; margin-bottom: .5rem;
}
.attach-dropzone:hover .dz-icon { color: #2e7d32; }
.attach-dropzone .dz-text { font-weight: 700; color: #475569; font-size: .95rem; }
.attach-dropzone .dz-hint { font-size: .75rem; color: #94a3b8; margin-top: .3rem; }

.attach-list { margin-top: 1rem; }
.attach-item {
    display: flex; align-items: center; gap: .9rem;
    padding: .75rem 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: .55rem;
    transition: border-color .15s;
}
.attach-item:hover { border-color: #c7d2dc; }
.attach-item.is-pending {
    background: #fffbeb; border-color: #fde68a;
}
.attach-icon {
    width: 38px; height: 38px;
    background: rgba(255,255,255,.9);
    border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    border: 1px solid #e2e8f0;
}
.attach-meta { flex: 1; min-width: 0; }
.attach-name {
    font-weight: 700; color: #1e293b; font-size: .9rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.attach-info {
    font-size: .73rem; color: #64748b; margin-top: .15rem;
}
.attach-actions { display: flex; gap: .35rem; }
.attach-btn {
    width: 32px; height: 32px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #64748b;
    transition: all .15s;
    text-decoration: none;
}
.attach-btn:hover { background: #f1f5f9; color: #1e293b; }
.attach-btn.danger:hover { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }
.attach-pending-badge {
    background: #fbbf24; color: #fff;
    font-size: .65rem; font-weight: 800;
    padding: .15rem .5rem; border-radius: 12px;
    text-transform: uppercase; letter-spacing: .5px;
}

/* ── Zone PJ par examen ────────────────────────────────────────── */
.exam-pj-zone {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f1f5f9;
}
.exam-pj-toggle {
    display: inline-flex; align-items: center; gap: .45rem;
    font-size: .79rem; font-weight: 700; color: #64748b;
    cursor: pointer; padding: .3rem .8rem;
    background: #f8fafc; border: 1.5px solid #e2e8f0;
    border-radius: 8px; transition: all .15s; user-select: none;
    text-decoration: none;
}
.exam-pj-toggle:hover { background: #f1f5f9; border-color: #cbd5e1; color: #475569; }
.exam-pj-toggle.has-files {
    background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8;
}
.pj-count-badge {
    background: #2563eb; color: #fff;
    font-size: .63rem; font-weight: 800;
    padding: .1rem .42rem; border-radius: 10px; line-height: 1.5;
}
.pj-chevron { font-size: .68rem; transition: transform .2s; }
.pj-chevron.open { transform: rotate(180deg); }
.exam-pj-body {
    margin-top: .6rem;
    padding: .85rem 1rem;
    background: #f8fafc;
    border: 1px solid #e8edf2;
    border-radius: 10px;
}
.pj-dropzone-mini {
    border: 2px dashed #cbd5e1; border-radius: 8px;
    padding: .7rem 1rem;
    background: #fff; cursor: pointer;
    transition: all .15s;
    display: flex; align-items: center; gap: .75rem;
}
.pj-dropzone-mini:hover,
.pj-dropzone-mini.drag-over { border-color: #2e7d32; background: #f0fdf4; }
.pj-dropzone-mini .pjdz-icon {
    font-size: 1.3rem; color: #94a3b8; flex-shrink: 0;
}
.pj-dropzone-mini:hover .pjdz-icon { color: #2e7d32; }
.pjdz-text { font-size: .82rem; font-weight: 700; color: #475569; }
.pjdz-hint { font-size: .7rem; color: #94a3b8; margin-top: .1rem; }
.pj-desc-input {
    margin-top: .55rem; width: 100%;
    border: 1.5px solid #e2e8f0; border-radius: 7px;
    padding: .4rem .7rem; font-size: .81rem;
    transition: border-color .15s; background: #fff;
}
.pj-desc-input:focus { border-color: #2e7d32; outline: none; }
.pj-preview-item {
    display: flex; align-items: center; gap: .5rem;
    padding: .38rem .65rem;
    background: #fffbeb; border: 1px solid #fde68a;
    border-radius: 6px; margin-top: .3rem;
    font-size: .78rem; font-weight: 600; color: #92400e;
}
.pj-preview-item-name {
    flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.pj-preview-item-size { font-size: .7rem; color: #a16207; white-space: nowrap; }
.pj-section-label {
    font-size: .71rem; color: #64748b; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px; margin-bottom: .15rem;
    display: flex; align-items: center; gap: .3rem;
}
.pj-existing-item {
    display: flex; align-items: center; gap: .55rem;
    padding: .42rem .7rem;
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 7px; margin-top: .3rem;
    font-size: .79rem; transition: border-color .15s;
}
.pj-existing-item:hover { border-color: #c7d2dc; }
.pj-item-name {
    flex: 1; font-weight: 700; color: #1e293b;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.pj-item-info { font-size: .69rem; color: #64748b; white-space: nowrap; }
.pj-item-btn {
    width: 26px; height: 26px;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 6px;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    color: #64748b; font-size: .75rem; text-decoration: none;
    transition: all .15s; flex-shrink: 0;
}
.pj-item-btn:hover { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }
</style>

<!-- ── Top bar ── -->
<div class="labo-topbar">
    <div class="brand">
        <div class="brand-icon"><i class="bi bi-pencil-square"></i></div>
        <span>Laboratoire</span>
    </div>
    <span class="page-title"><i class="bi bi-pencil-square me-1"></i>Saisie des Résultats</span>
    <div class="topbar-actions">
        <button type="button" class="btn-topbar preview" onclick="previsualiser()">
            <i class="bi bi-eye"></i> Prévisualiser
        </button>
        <a href="<?= BASE_URL ?>laboratoire/traitement/<?= $demande['id'] ?>" class="btn-topbar">
            <i class="bi bi-list-check"></i> Traitement
        </a>
        <a href="<?= BASE_URL ?>laboratoire" class="btn-topbar">
            <i class="bi bi-arrow-left"></i> Dashboard
        </a>
    </div>
</div>

<!-- ── Patient banner ── -->
<div class="patient-banner">
    <div class="d-flex align-items-center gap-3">
        <div class="pat-avatar"><i class="bi bi-person-fill"></i></div>
        <div>
            <div class="pat-name"><?= htmlspecialchars($demande['nom'] . ' ' . $demande['prenom']) ?></div>
            <div class="pat-sub">
                Dossier: <?= htmlspecialchars($demande['dossier_numero']) ?>
                &nbsp;|&nbsp; Âge: <?= $age ?>
                &nbsp;|&nbsp; Sexe: <?= htmlspecialchars($demande['sexe']) ?>
            </div>
        </div>
    </div>
    <div class="banner-meta">
        <div class="bm-item">
            <label>Médecin prescripteur</label>
            <div class="bm-val">Dr. <?= htmlspecialchars($demande['medecin_nom'] . ' ' . $demande['medecin_prenom']) ?></div>
        </div>
        <div class="bm-item">
            <label>Demande</label>
            <div class="bm-val">#<?= $demande['id'] ?> — <?= date('d/m/Y H:i', strtotime($demande['date_creation'])) ?></div>
        </div>
        <?php
        // Compteur de facturation (si la migration est exécutée)
        $hasFactCol = !empty($examens) && array_key_exists('facture', $examens[0]);
        if ($hasFactCol):
            $nbFact = count(array_filter($examens, fn($e) => (int)($e['facture'] ?? 0) === 1));
            $nbTot  = count($examens);
            $pctFact = $nbTot > 0 ? round($nbFact / $nbTot * 100) : 0;
        ?>
        <div class="bm-item">
            <label>Facturation</label>
            <div class="bm-val">
                <?php if ($nbFact === $nbTot && $nbTot > 0): ?>
                    <span style="background:rgba(34,197,94,.25);color:#fff;padding:3px 12px;border-radius:14px;font-size:.78rem;font-weight:800;">
                        <i class="bi bi-check-circle-fill me-1"></i><?= $nbFact ?>/<?= $nbTot ?> tous facturés
                    </span>
                <?php elseif ($nbFact > 0): ?>
                    <span style="background:rgba(251,146,60,.30);color:#fff;padding:3px 12px;border-radius:14px;font-size:.78rem;font-weight:800;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= $nbFact ?>/<?= $nbTot ?> partiellement facturés
                    </span>
                <?php else: ?>
                    <span style="background:rgba(239,68,68,.30);color:#fff;padding:3px 12px;border-radius:14px;font-size:.78rem;font-weight:800;">
                        <i class="bi bi-x-circle-fill me-1"></i>Aucun facturé
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Content ── -->
<div class="labo-content">

<?php
/* ── Flash messages ── */
if (!empty($_GET['success'])): ?>
<div class="flash-success">
    <i class="bi bi-check-circle-fill"></i>
    <?= htmlspecialchars(urldecode($_GET['success'])) ?>
</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
<div class="flash-error">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <?= htmlspecialchars(urldecode($_GET['error'])) ?>
</div>
<?php endif; ?>

<?php
/* ── Calcul de la progression ── */
$nbTotal   = count($examens);
$nbRemplis = count(array_filter($examens, fn($e) => !empty(trim((string)($e['resultat'] ?? '')))));
$pctDone   = $nbTotal > 0 ? round($nbRemplis / $nbTotal * 100) : 0;
?>

<?php if ($nbTotal > 0): ?>
<div class="progress-bar-wrap">
    <span class="pb-label"><i class="bi bi-clipboard2-pulse me-1"></i>Progression des résultats</span>
    <div class="pb-track"><div class="pb-fill" style="width:<?= $pctDone ?>%"></div></div>
    <span class="pb-count"><?= $nbRemplis ?>/<?= $nbTotal ?> examen<?= $nbTotal > 1 ? 's' : '' ?> renseigné<?= $nbTotal > 1 ? 's' : '' ?></span>
</div>
<?php endif; ?>

<form action="<?= BASE_URL ?>laboratoire/sauvegarder-resultats" method="POST" id="formResultats" enctype="multipart/form-data">
    <input type="hidden" name="demande_id" value="<?= $demande['id'] ?>">

    <?php
    $categories = [];
    foreach ($examens as $examen) {
        $categories[$examen['categorie']][] = $examen;
    }
    ?>

    <?php foreach ($categories as $categorie => $examensCategorie): ?>
    <div class="exam-wrap">
        <div class="cat-header">
            <div class="cat-icon"><i class="bi bi-folder2-open"></i></div>
            <span class="cat-name"><?= htmlspecialchars($categorie) ?></span>
            <span class="cat-count"><?= count($examensCategorie) ?> examen<?= count($examensCategorie) > 1 ? 's' : '' ?></span>
        </div>

        <?php foreach ($examensCategorie as $examen):
            $isFacture   = isset($examen['facture']) ? (int)$examen['facture'] === 1 : null;
            $existResult = trim((string)($examen['resultat'] ?? ''));
            $isDone      = $existResult !== '' || in_array($examen['statut'] ?? '', ['SOUMIS','VALIDE']);
            $isValide    = ($examen['statut'] ?? '') === 'VALIDE';

            /* Calculer la classe couleur du champ résultat (normal/anormal) */
            $resultColorClass = '';
            $valNum = $examen['valeur_numerique'] ?? null;
            if ($existResult !== '' && $examen['valeur_normale_min'] !== null && $examen['valeur_normale_max'] !== null && $valNum !== null) {
                $v = (float)$valNum;
                if ($v >= (float)$examen['valeur_normale_min'] && $v <= (float)$examen['valeur_normale_max']) {
                    $resultColorClass = 'is-normal';
                } elseif ($v < (float)$examen['valeur_normale_min'] * 0.5 || $v > (float)$examen['valeur_normale_max'] * 2) {
                    $resultColorClass = 'is-critical';
                } else {
                    $resultColorClass = 'is-abnormal';
                }
            }
        ?>
        <div class="exam-card <?= $isFacture === 0 ? 'exam-non-facture' : '' ?> <?= $isDone ? 'exam-done' : '' ?>" id="exam-<?= $examen['id'] ?>">
            <div class="exam-title-row">
                <div style="flex:1;">
                    <div class="exam-name">
                        <?= htmlspecialchars($examen['nom']) ?>
                        <?php if ($isValide): ?>
                            <span class="badge-done"><i class="bi bi-check-circle-fill"></i>Validé</span>
                        <?php elseif ($isDone): ?>
                            <span class="badge-done"><i class="bi bi-check-circle-fill"></i>Résultat saisi</span>
                        <?php else: ?>
                            <span class="badge-pending"><i class="bi bi-hourglass-split"></i>En attente</span>
                        <?php endif; ?>
                        <?php if ($isFacture === 1): ?>
                            <span class="badge-fact ok" title="Facturé"><i class="bi bi-check-circle-fill me-1"></i>Facturé</span>
                        <?php elseif ($isFacture === 0): ?>
                            <span class="badge-fact ko" title="Non facturé"><i class="bi bi-exclamation-triangle-fill me-1"></i>Non facturé</span>
                        <?php endif; ?>
                    </div>
                    <div class="exam-meta">
                        <i class="bi bi-droplet-fill text-danger me-1"></i><?= htmlspecialchars($examen['type_prelevement']) ?>
                        &nbsp;·&nbsp; Délai : <?= $examen['delai_rendu_heures'] ?>h
                        <?php if ($examen['a_jeun_requis']): ?>
                            &nbsp;·&nbsp; <i class="bi bi-clock-history text-warning"></i> À jeun requis
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($examen['urgent']): ?>
                    <span class="badge-urgent">⚡ URGENT</span>
                <?php endif; ?>
            </div>

            <div class="field-grid">
                <!-- Résultat -->
                <div>
                    <div class="field-label">Résultat</div>
                    <div class="result-input-group">
                        <input type="text"
                               class="result-input <?= $resultColorClass ?>"
                               name="resultats[<?= $examen['id'] ?>][resultat]"
                               id="resultat_<?= $examen['id'] ?>"
                               data-min="<?= $examen['valeur_normale_min'] ?? '' ?>"
                               data-max="<?= $examen['valeur_normale_max'] ?? '' ?>"
                               onchange="verifierNorme(this)"
                               value="<?= htmlspecialchars($examen['resultat'] ?? '') ?>"
                               <?= $isValide ? 'readonly' : '' ?>
                               placeholder="Saisir le résultat">
                        <?php if ($examen['unite']): ?>
                            <span class="result-unit"><?= htmlspecialchars($examen['unite']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($examen['valeur_normale_min'] && $examen['valeur_normale_max']): ?>
                        <div class="norm-hint">
                            Valeurs normales : <?= $examen['valeur_normale_min'] ?> – <?= $examen['valeur_normale_max'] ?> <?= $examen['unite'] ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Valeur numérique -->
                <div>
                    <div class="field-label">Valeur numérique <span style="font-weight:400;text-transform:none;">(si applicable)</span></div>
                    <input type="number"
                           step="0.001"
                           class="field-input"
                           name="resultats[<?= $examen['id'] ?>][valeur_numerique]"
                           id="valeur_<?= $examen['id'] ?>"
                           onchange="synchroniserValeur(<?= $examen['id'] ?>)"
                           value="<?= ($examen['valeur_numerique'] !== null && $examen['valeur_numerique'] !== '') ? htmlspecialchars($examen['valeur_numerique']) : '' ?>"
                           <?= $isValide ? 'readonly' : '' ?>
                           placeholder="0.00">
                </div>

                <!-- Interprétation -->
                <div>
                    <div class="field-label">Interprétation</div>
                    <textarea class="field-textarea"
                              name="resultats[<?= $examen['id'] ?>][interpretation]"
                              rows="2"
                              <?= $isValide ? 'readonly' : '' ?>
                              placeholder="Commentaire technique…"><?= htmlspecialchars($examen['interpretation'] ?? '') ?></textarea>
                </div>
            </div>

            <label class="qc-check">
                <input type="checkbox" name="resultats[<?= $examen['id'] ?>][controle_qualite]" id="qc_<?= $examen['id'] ?>"
                       <?= !empty($examen['controle_qualite']) ? 'checked' : '' ?>>
                <i class="bi bi-shield-check"></i> Contrôle qualité validé
            </label>

            <?php
            $pjsExamen    = $piecesJointesParExamen[$examen['id']] ?? [];
            $hasPjsExamen = !empty($pjsExamen);
            $nbPjsExamen  = count($pjsExamen);
            ?>
            <div class="exam-pj-zone">
                <button type="button"
                        class="exam-pj-toggle <?= $hasPjsExamen ? 'has-files' : '' ?>"
                        id="pjToggle_<?= $examen['id'] ?>"
                        onclick="togglePjExamen(<?= $examen['id'] ?>)">
                    <i class="bi bi-paperclip"></i>
                    <?php if ($hasPjsExamen): ?>
                        <span><?= $nbPjsExamen ?> fichier<?= $nbPjsExamen > 1 ? 's' : '' ?> joint<?= $nbPjsExamen > 1 ? 's' : '' ?></span>
                        <span class="pj-count-badge"><?= $nbPjsExamen ?></span>
                    <?php else: ?>
                        <span>Joindre des fichiers</span>
                    <?php endif; ?>
                    <i class="bi bi-chevron-down pj-chevron <?= $hasPjsExamen ? 'open' : '' ?>"
                       id="pjChevron_<?= $examen['id'] ?>"></i>
                </button>

                <div class="exam-pj-body"
                     id="pjBody_<?= $examen['id'] ?>"
                     <?= $hasPjsExamen ? '' : 'style="display:none;"' ?>>

                    <!-- Mini dropzone -->
                    <div class="pj-dropzone-mini"
                         id="pjDrop_<?= $examen['id'] ?>"
                         onclick="document.getElementById('pjInput_<?= $examen['id'] ?>').click()">
                        <i class="bi bi-cloud-arrow-up pjdz-icon"></i>
                        <div>
                            <div class="pjdz-text">Cliquez ou glissez vos fichiers ici</div>
                            <div class="pjdz-hint">PDF, images (JPG/PNG), Excel, Word — max 10 Mo</div>
                        </div>
                    </div>
                    <input type="file"
                           id="pjInput_<?= $examen['id'] ?>"
                           name="pieces_jointes[<?= $examen['id'] ?>][]"
                           accept=".pdf,.png,.jpg,.jpeg,.gif,.webp,.tif,.tiff,.bmp,.heic,.xlsx,.xls,.csv,.doc,.docx,.txt"
                           multiple style="display:none;"
                           onchange="previewPjExamen(<?= $examen['id'] ?>, this)">

                    <input type="text"
                           name="description_pieces[<?= $examen['id'] ?>]"
                           class="pj-desc-input"
                           placeholder="Description des fichiers (optionnel)">

                    <!-- Preview fichiers sélectionnés (avant upload) -->
                    <div id="pjPreview_<?= $examen['id'] ?>"></div>

                    <!-- Fichiers déjà uploadés pour cet examen -->
                    <?php if ($hasPjsExamen): ?>
                    <div style="margin-top:.65rem;">
                        <div class="pj-section-label">
                            <i class="bi bi-check-circle-fill" style="color:#22c55e;"></i>
                            Déjà uploadés :
                        </div>
                        <?php foreach ($pjsExamen as $pj):
                            [$icon, $iconColor] = $iconePj($pj['fichier_type'] ?? '', $pj['fichier_nom_original'] ?? '');
                        ?>
                        <div class="pj-existing-item" data-pj-id="<?= (int)$pj['id'] ?>">
                            <i class="bi <?= $icon ?>" style="color:<?= $iconColor ?>;font-size:1rem;flex-shrink:0;"></i>
                            <span class="pj-item-name" title="<?= htmlspecialchars($pj['fichier_nom_original']) ?>">
                                <?= htmlspecialchars($pj['fichier_nom_original']) ?>
                            </span>
                            <span class="pj-item-info"><?= $tailleHumaine($pj['fichier_taille']) ?></span>
                            <a href="<?= BASE_URL ?>laboratoire/piece-jointe/<?= (int)$pj['id'] ?>"
                               target="_blank" class="pj-item-btn" title="Ouvrir / télécharger">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button type="button" class="pj-item-btn"
                                    onclick="supprimerPj(<?= (int)$pj['id'] ?>)"
                                    title="Supprimer">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <!-- Fichiers généraux sans examen rattaché (rétro-compat données existantes) -->
    <?php if (!empty($piecesJointes)): ?>
    <div class="attach-section">
        <div class="sec-title">
            <i class="bi bi-paperclip"></i> Fichiers généraux
            <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#94a3b8;font-size:.78rem;margin-left:.4rem;">
                — fichiers rattachés à la demande sans examen spécifique
            </span>
        </div>
        <div class="attach-list">
            <?php foreach ($piecesJointes as $pj):
                [$icon, $iconColor] = $iconePj($pj['fichier_type'] ?? '', $pj['fichier_nom_original'] ?? '');
            ?>
            <div class="attach-item" data-pj-id="<?= (int)$pj['id'] ?>">
                <div class="attach-icon">
                    <i class="bi <?= $icon ?>" style="color:<?= $iconColor ?>;"></i>
                </div>
                <div class="attach-meta">
                    <div class="attach-name"><?= htmlspecialchars($pj['fichier_nom_original']) ?></div>
                    <div class="attach-info">
                        <?= $tailleHumaine($pj['fichier_taille']) ?>
                        &middot; <?= htmlspecialchars(date('d/m/Y H:i', strtotime($pj['uploade_at']))) ?>
                        <?php if (!empty($pj['uploader_nom'])): ?>
                            par <?= htmlspecialchars($pj['uploader_nom'] . ' ' . $pj['uploader_prenom']) ?>
                        <?php endif; ?>
                        <?php if (!empty($pj['description'])): ?>
                            <br><i class="bi bi-quote me-1"></i><?= htmlspecialchars($pj['description']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="attach-actions">
                    <a href="<?= BASE_URL ?>laboratoire/piece-jointe/<?= (int)$pj['id'] ?>"
                       target="_blank" class="attach-btn" title="Ouvrir / télécharger">
                        <i class="bi bi-eye"></i>
                    </a>
                    <button type="button" class="attach-btn danger"
                            onclick="supprimerPj(<?= (int)$pj['id'] ?>)" title="Supprimer">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Notes générales -->
    <div class="notes-section">
        <div class="sec-title"><i class="bi bi-journal-text"></i> Notes du technicien / Observations générales</div>
        <textarea class="field-textarea"
                  name="notes_technicien"
                  rows="3"
                  placeholder="Difficultés rencontrées, observations générales, recommandations…"></textarea>
    </div>

    <!-- Actions footer -->
    <div class="actions-section">
        <div class="certif-checks">
            <label class="certif-check">
                <input type="checkbox" id="validationTechnique" required>
                Je certifie avoir effectué tous les contrôles techniques requis
            </label>
            <label class="certif-check optional">
                <input type="checkbox" name="demander_validation_biologiste" id="validationBio">
                Demander validation du biologiste (résultats critiques)
            </label>
            <small class="text-muted" style="font-size:.75rem;">
                <i class="bi bi-info-circle me-1"></i>
                La validation est possible même si tous les examens ne sont pas remplis,
                à condition d'avoir saisi au moins un résultat OU joint une pièce.
            </small>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn-draft" onclick="sauvegarderBrouillon()">
                <i class="bi bi-save"></i> Brouillon
            </button>
            <button type="submit" class="btn-validate">
                <i class="bi bi-check-circle-fill"></i>
                Enregistrer les résultats
                <?php
                $nbRestants = $nbTotal - $nbRemplis;
                if ($nbRestants > 0): ?>
                    <span style="background:rgba(255,255,255,.25);border-radius:12px;padding:.1rem .6rem;font-size:.8rem;margin-left:.3rem;">
                        <?= $nbRestants ?> restant<?= $nbRestants > 1 ? 's' : '' ?>
                    </span>
                <?php endif; ?>
            </button>
        </div>
    </div>
</form>
</div>

<!-- Toast autosave -->
<div class="autosave-toast" id="autosaveToast">
    <i class="bi bi-cloud-check"></i> Brouillon sauvegardé automatiquement
</div>

<script>
// ── Normes & synchronisation ──────────────────────────────────────────────────
function verifierNorme(input) {
    const valeur = parseFloat(input.value);
    const min    = parseFloat(input.dataset.min);
    const max    = parseFloat(input.dataset.max);
    input.classList.remove('is-normal', 'is-abnormal', 'is-critical');
    if (!isNaN(valeur) && !isNaN(min) && !isNaN(max)) {
        if (valeur >= min && valeur <= max) {
            input.classList.add('is-normal');
        } else if (valeur < min * 0.5 || valeur > max * 2) {
            input.classList.add('is-critical');
            if (confirm('⚠️ Valeur critique détectée !\nVoulez-vous demander une validation du biologiste ?')) {
                document.getElementById('validationBio').checked = true;
            }
        } else {
            input.classList.add('is-abnormal');
        }
        const examenId = input.name.match(/\[(\d+)\]/)[1];
        const numField = document.getElementById('valeur_' + examenId);
        if (numField && !numField.value) numField.value = valeur;
    }
}

function synchroniserValeur(examenId) {
    const numField  = document.getElementById('valeur_' + examenId);
    const textField = document.getElementById('resultat_' + examenId);
    if (numField && textField && numField.value && !textField.value) {
        textField.value = numField.value;
        verifierNorme(textField);
    }
}

// ── Toast & brouillon ─────────────────────────────────────────────────────────
function showToast(msg) {
    const toast = document.getElementById('autosaveToast');
    toast.textContent = msg || '✓ Brouillon sauvegardé';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
}

function sauvegarderBrouillon() {
    const formData = new FormData(document.getElementById('formResultats'));
    formData.append('brouillon', '1');
    fetch('<?= BASE_URL ?>laboratoire/sauvegarder-resultats', { method: 'POST', body: formData })
        .then(r => { if (r.ok) showToast('✓ Brouillon sauvegardé'); });
}

// ── Prévisualisation ──────────────────────────────────────────────────────────
function previsualiser() {
    const inputs = document.querySelectorAll('.result-input');
    const hasResults = Array.from(inputs).some(i => i.value.trim());
    if (!hasResults) { alert('Aucun résultat saisi pour la prévisualisation'); return; }
    let rows = '';
    inputs.forEach(input => {
        if (!input.value.trim()) return;
        const card = input.closest('.exam-card');
        const name = card ? card.querySelector('.exam-name').textContent.trim() : '—';
        const unit = input.dataset.unite || '';
        rows += `<tr><td style="padding:.6rem 1rem;border-bottom:1px solid #eee;">${name}</td>
                     <td style="padding:.6rem 1rem;border-bottom:1px solid #eee;font-weight:700;">${input.value} ${unit}</td></tr>`;
    });
    const w = window.open('', 'preview', 'width=640,height=480,scrollbars=yes');
    w.document.write(`<!DOCTYPE html><html><head><title>Prévisualisation</title>
        <style>body{font-family:system-ui,sans-serif;padding:1.5rem;color:#1e293b}
        table{width:100%;border-collapse:collapse}th{background:#f1f5f9;padding:.7rem 1rem;text-align:left;font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b}</style>
        </head><body>
        <h2 style="margin-bottom:1rem;font-size:1.2rem">Prévisualisation des résultats</h2>
        <table><thead><tr><th>Examen</th><th>Résultat</th></tr></thead><tbody>${rows}</tbody></table>
        <br><button onclick="window.close()" style="padding:.5rem 1.2rem;border:none;background:#2e7d32;color:#fff;border-radius:8px;cursor:pointer;font-weight:600;">Fermer</button>
        </body></html>`);
    w.document.close();
}

// ── Pièces jointes par examen ─────────────────────────────────────────────────

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
}

/** Ouvre/ferme la zone PJ d'un examen */
function togglePjExamen(examenId) {
    const body    = document.getElementById('pjBody_'    + examenId);
    const chevron = document.getElementById('pjChevron_' + examenId);
    if (!body) return;
    const isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : '';
    chevron.classList.toggle('open', !isOpen);
}

/** Affiche le preview des fichiers sélectionnés pour un examen */
function previewPjExamen(examenId, input) {
    const preview = document.getElementById('pjPreview_' + examenId);
    const toggle  = document.getElementById('pjToggle_'  + examenId);
    const chevron = document.getElementById('pjChevron_' + examenId);
    const body    = document.getElementById('pjBody_'    + examenId);
    if (!preview) return;

    if (!input.files.length) { preview.innerHTML = ''; return; }

    let html = '';
    Array.from(input.files).forEach(f => {
        const sizeKb = (f.size / 1024).toFixed(0);
        const isPdf  = f.type.includes('pdf') || f.name.toLowerCase().endsWith('.pdf');
        const isImg  = f.type.startsWith('image/');
        const icon   = isPdf ? 'bi-file-earmark-pdf-fill' : (isImg ? 'bi-file-earmark-image-fill' : 'bi-file-earmark-fill');
        const color  = isPdf ? '#dc2626' : (isImg ? '#2563eb' : '#64748b');
        html += `<div class="pj-preview-item">
            <i class="bi ${icon}" style="color:${color};flex-shrink:0;"></i>
            <span class="pj-preview-item-name">${escapeHtml(f.name)}</span>
            <span class="pj-preview-item-size">${sizeKb} Ko</span>
        </div>`;
    });
    preview.innerHTML = html;

    // Mettre à jour le bouton toggle
    const nb = input.files.length;
    toggle.classList.add('has-files');
    toggle.querySelector('span:first-of-type').textContent =
        nb + ' fichier' + (nb > 1 ? 's' : '') + ' sélectionné' + (nb > 1 ? 's' : '');

    // S'assurer que la zone est ouverte
    body.style.display = '';
    chevron.classList.add('open');
}

/** Suppression d'une pièce jointe (works for both per-exam and general items) */
function supprimerPj(id) {
    if (!confirm('Supprimer définitivement cette pièce jointe ?')) return;
    fetch('<?= BASE_URL ?>laboratoire/piece-jointe-delete/' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            // Cherche dans les deux types d'items
            const item = document.querySelector(`.pj-existing-item[data-pj-id="${id}"]`)
                      || document.querySelector(`.attach-item[data-pj-id="${id}"]`);
            if (item) item.remove();
            showToast('✓ Pièce jointe supprimée');
        } else {
            alert(d.message || 'Erreur lors de la suppression');
        }
    });
}

// ── Drag & drop sur les mini dropzones ───────────────────────────────────────
(function setupPerExamDropzones() {
    document.querySelectorAll('.pj-dropzone-mini').forEach(dz => {
        const examenId = dz.id.replace('pjDrop_', '');
        ['dragenter','dragover'].forEach(ev => dz.addEventListener(ev, e => {
            e.preventDefault(); e.stopPropagation();
            dz.classList.add('drag-over');
        }));
        ['dragleave','drop'].forEach(ev => dz.addEventListener(ev, e => {
            e.preventDefault(); e.stopPropagation();
            dz.classList.remove('drag-over');
        }));
        dz.addEventListener('drop', e => {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const input = document.getElementById('pjInput_' + examenId);
                if (input) {
                    // DataTransfer ne permet pas d'assigner directement sur Firefox
                    // On utilise un DataTransfer intermédiaire si nécessaire
                    try {
                        const dt = new DataTransfer();
                        Array.from(files).forEach(f => dt.items.add(f));
                        input.files = dt.files;
                    } catch(err) {
                        input.files = files;
                    }
                    previewPjExamen(parseInt(examenId), input);
                }
            }
        });
    });
})();

// ── Validation formulaire ─────────────────────────────────────────────────────
document.getElementById('formResultats').addEventListener('submit', function(e) {
    const filled = Array.from(document.querySelectorAll('.result-input')).filter(i => i.value.trim()).length;

    // Compter les fichiers sélectionnés dans toutes les zones per-exam
    const hasNewFiles = Array.from(document.querySelectorAll('[id^="pjInput_"]'))
                             .some(inp => inp.files && inp.files.length > 0);
    // Compter les fichiers déjà uploadés
    const hasExistingFiles = document.querySelectorAll('.pj-existing-item, .attach-item:not(.is-pending)').length > 0;
    const hasAnyAttachment = hasNewFiles || hasExistingFiles;

    if (filled === 0 && !hasAnyAttachment) {
        e.preventDefault();
        alert('Aucun résultat saisi et aucune pièce jointe.\n\nPour valider, vous devez :\n• soit remplir au moins un examen,\n• soit joindre au moins un fichier à un examen.');
        return;
    }
    if (!document.getElementById('validationTechnique').checked) {
        e.preventDefault();
        alert('Veuillez certifier avoir effectué les contrôles techniques.');
        return;
    }

    // Alerte douce si peu de résultats remplis
    const totalExamens = document.querySelectorAll('.exam-card').length;
    if (totalExamens > 0 && filled < totalExamens && filled > 0) {
        const reste = totalExamens - filled;
        if (!confirm(`Vous avez rempli ${filled} examen(s) sur ${totalExamens}.\n${reste} examen(s) sans résultat textuel.\n\nLes fichiers joints contiennent-ils le reste des résultats ?\n\nContinuer la validation ?`)) {
            e.preventDefault();
            return;
        }
    }
});

// Auto-sauvegarde toutes les 2 min
setInterval(sauvegarderBrouillon, 120000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
