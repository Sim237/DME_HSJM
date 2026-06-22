<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($titre ?? 'Formulaire') ?> — <?= htmlspecialchars(($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?></title>
<style>
/* ── RESET & BASE ──────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background: #e8e8e8; font-family: "Times New Roman", Times, serif; color: #000; }

/* ── FEUILLE A4 ────────────────────────────────────────── */
.paper-sheet {
    background: #fff;
    width: 21cm;
    min-height: 29.7cm;
    padding: 1.5cm 2cm 2cm;
    margin: 20px auto;
    box-shadow: 0 4px 18px rgba(0,0,0,.18);
    position: relative;
    display: flex;
    flex-direction: column;
    font-size: 11pt;
    line-height: 1.5;
}

/* ── EN-TÊTE HÔPITAL ───────────────────────────────────── */
.hosp-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}
.hosp-left { font-size: 9pt; font-weight: bold; line-height: 1.3; }
.hosp-right { text-align: right; font-size: 9pt; font-weight: bold; line-height: 1.3; }
.hosp-logo { height: 55px; }

/* ── TITRE DU DOCUMENT ─────────────────────────────────── */
.doc-title {
    text-align: center;
    font-size: 15pt;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: 2px solid #000;
    padding-bottom: 6px;
    margin: 14px 0 16px;
}
.doc-subtitle {
    text-align: center;
    font-size: 9pt;
    margin-bottom: 14px;
    color: #444;
}

/* ── CHAMPS IMPRIMÉS ───────────────────────────────────── */
.field-val {
    display: inline-block;
    border-bottom: 1px solid #000;
    min-width: 80px;
    padding: 0 4px;
    font-weight: bold;
    vertical-align: bottom;
}
.field-val.wide  { min-width: 200px; }
.field-val.xwide { min-width: 350px; }
.field-val.full  { width: 100%; display: block; }

/* ── SECTIONS ──────────────────────────────────────────── */
.section-header {
    font-weight: bold;
    text-transform: uppercase;
    font-size: 9.5pt;
    border-bottom: 1px solid #333;
    margin: 14px 0 8px;
    padding-bottom: 3px;
    letter-spacing: .5px;
}
.field-row {
    display: flex;
    align-items: flex-end;
    margin-bottom: 10px;
    gap: 4px;
}
.field-label { white-space: nowrap; font-weight: normal; }

/* ── GRILLES ───────────────────────────────────────────── */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }

/* ── OUI / NON / checkbox ──────────────────────────────── */
.yn { display: inline-flex; gap: 10px; align-items: center; }
.yn-box { display: inline-flex; align-items: center; gap: 4px; font-size: 10pt; }
.check-sq { width: 11px; height: 11px; border: 1px solid #000; display: inline-block; vertical-align: middle; }
.check-sq.checked::after { content: "✓"; font-size: 9pt; line-height: 1; }

/* ── SIGNATURE ─────────────────────────────────────────── */
.sig-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 30px; }
.sig-block { text-align: center; }
.sig-line { height: 60px; border-bottom: 1px solid #000; margin-bottom: 6px; }

/* ── PIED DE PAGE ──────────────────────────────────────── */
.footer-note {
    margin-top: auto;
    padding-top: 10px;
    border-top: .5px solid #000;
    font-size: 7pt;
    font-style: italic;
    text-align: center;
    color: #444;
}

/* ── IMPRESSION ────────────────────────────────────────── */
@page { size: A4; margin: 1cm; }
@media print {
    html, body { background: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .paper-sheet { margin: 0; box-shadow: none; width: 100%; min-height: unset; padding: 0; }
    .no-print { display: none !important; }
}
</style>
</head>
<body>
<div class="paper-sheet">
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
 require_once __DIR__ . '/../../partials/entete_hopital.php'; /* en-tête rendu par chaque formulaire via entete_hopital() */ ?>
<?php
/* Helper : lit formData ou retourne '' */
$fd = function(string $key, string $default = '') use ($formData): string {
    $v = $formData[$key] ?? $default;
    return htmlspecialchars(is_array($v) ? implode(', ', $v) : (string)$v);
};
/* Helper : yn box */
$yn = function(string $key, string $val = '1') use ($formData): string {
    $v = $formData[$key] ?? '';
    $checked = ($v === $val || $v === 'O' || $v === '1') ? ' checked' : '';
    return '<span class="check-sq'.$checked.'"></span>';
};
/* Helper : checked box for checkbox arrays */
$cb = function(string $key, string $val) use ($formData): string {
    $arr = $formData[$key] ?? [];
    if (!is_array($arr)) $arr = [$arr];
    return in_array($val, $arr) ? '<span class="check-sq checked"></span>' : '<span class="check-sq"></span>';
};
/* Helper : radio (O=Oui, N=Non) */
$radio = function(string $key, string $val) use ($formData): string {
    return ($formData[$key] ?? '') === $val ? '<span class="check-sq checked"></span>' : '<span class="check-sq"></span>';
};
$patNom    = htmlspecialchars(($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? ''));
$patAge    = $patient['date_naissance'] ? date_diff(date_create($patient['date_naissance']), date_create('now'))->y : '—';
$patSexe   = htmlspecialchars($patient['sexe'] ?? '');
$dossierNo = htmlspecialchars($patient['dossier_numero'] ?? '');
$today     = date('d/m/Y');
?>
