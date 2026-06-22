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
 * ExcelService — Lecture/écriture de fichiers .xlsx en pure PHP
 *
 * Aucune dépendance externe : utilise ZipArchive + SimpleXML (extensions
 * standard PHP). Suffit largement pour des exports/imports tabulaires
 * (sans formules, sans formatage avancé).
 *
 * Capacités :
 *   - Génération d'un .xlsx valide (Excel 2007+, LibreOffice, Google Sheets)
 *   - Lecture de la première feuille d'un .xlsx
 *   - Lecture/écriture CSV en UTF-8 (avec BOM pour Excel)
 *
 * Limites connues :
 *   - Une seule feuille par export
 *   - Pas de styles avancés (juste les en-têtes en gras)
 *   - Les cellules numériques pures sont auto-détectées ; le reste est
 *     traité comme texte via une table de chaînes partagée (sharedStrings)
 */
class ExcelService
{
    // ──────────────────────────────────────────────────────────────
    //  ÉCRITURE XLSX
    // ──────────────────────────────────────────────────────────────

    /**
     * Génère un fichier .xlsx au chemin indiqué.
     *
     * @param string   $filepath    Chemin de sortie (.xlsx)
     * @param string[] $headers     Liste des en-têtes (1ère ligne, en gras)
     * @param array[]  $rows        Données : tableau de tableaux indexés
     * @param string   $sheetName   Nom de la feuille (défaut "Feuille1")
     * @throws RuntimeException si ZipArchive indisponible ou écriture impossible
     */
    public function writeXlsx(string $filepath, array $headers, array $rows, string $sheetName = 'Feuille1'): void
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException("L'extension ZipArchive PHP n'est pas disponible.");
        }

        // Construire la table des chaînes partagées (sharedStrings.xml)
        // → toutes les valeurs textuelles sont stockées dans cette table
        // → les cellules y font référence via leur index
        $stringTable = [];
        $stringIndex = [];
        $addString = function(string $s) use (&$stringTable, &$stringIndex): int {
            if (isset($stringIndex[$s])) return $stringIndex[$s];
            $idx = count($stringTable);
            $stringTable[] = $s;
            $stringIndex[$s] = $idx;
            return $idx;
        };

        // ── Construire sheet1.xml ──────────────────────────────────
        $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<sheetData>';

        // Ligne 1 : en-têtes (style gras = styleId 1)
        $xml .= '<row r="1">';
        foreach ($headers as $i => $h) {
            $cellRef = $this->columnLetter($i + 1) . '1';
            $idx = $addString((string)$h);
            $xml .= '<c r="' . $cellRef . '" s="1" t="s"><v>' . $idx . '</v></c>';
        }
        $xml .= '</row>';

        // Lignes de données
        $rowNum = 2;
        foreach ($rows as $row) {
            $xml .= '<row r="' . $rowNum . '">';
            // Si la ligne est associative, prendre les valeurs dans l'ordre des en-têtes
            if ($this->isAssoc($row)) {
                $orderedRow = [];
                foreach ($headers as $h) {
                    $orderedRow[] = $row[$h] ?? '';
                }
                $row = $orderedRow;
            }
            foreach ($row as $i => $v) {
                $cellRef = $this->columnLetter($i + 1) . $rowNum;
                if ($v === null || $v === '') {
                    // Cellule vide — omettre pour économiser de la place
                    continue;
                }
                if (is_int($v) || is_float($v) || (is_string($v) && preg_match('/^-?\d+(\.\d+)?$/', $v))) {
                    // Cellule numérique
                    $xml .= '<c r="' . $cellRef . '"><v>' . $v . '</v></c>';
                } else {
                    // Cellule texte (référence dans sharedStrings)
                    $idx = $addString((string)$v);
                    $xml .= '<c r="' . $cellRef . '" t="s"><v>' . $idx . '</v></c>';
                }
            }
            $xml .= '</row>';
            $rowNum++;
        }

        $xml .= '</sheetData></worksheet>';

        // ── Construire sharedStrings.xml ───────────────────────────
        $ssXml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $ssXml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"';
        $ssXml .= ' count="' . count($stringTable) . '" uniqueCount="' . count($stringTable) . '">';
        foreach ($stringTable as $s) {
            $ssXml .= '<si><t xml:space="preserve">' . $this->escapeXml($s) . '</t></si>';
        }
        $ssXml .= '</sst>';

        // ── Construire workbook.xml ────────────────────────────────
        $wbXml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $wbXml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"';
        $wbXml .= ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $wbXml .= '<sheets><sheet name="' . $this->escapeXml($sheetName) . '" sheetId="1" r:id="rId1"/></sheets>';
        $wbXml .= '</workbook>';

        // ── Construire styles.xml (gras pour les en-têtes) ─────────
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            .   '<font><sz val="11"/><name val="Calibri"/></font>'
            .   '<font><b/><sz val="11"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            .   '<fill><patternFill patternType="none"/></fill>'
            .   '<fill><patternFill patternType="gray125"/></fill>'
            .   '<fill><patternFill patternType="solid"><fgColor rgb="FF4F46E5"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="2">'
            .   '<xf/>'
            .   '<xf fontId="1" fillId="2" applyFont="1" applyFill="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';

        // ── Fichiers de relations ──────────────────────────────────
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        // ── Empaqueter le tout dans le ZIP .xlsx ───────────────────
        @unlink($filepath);
        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException("Impossible de créer le fichier xlsx : $filepath");
        }
        $zip->addFromString('[Content_Types].xml',          $contentTypes);
        $zip->addFromString('_rels/.rels',                  $rootRels);
        $zip->addFromString('xl/workbook.xml',              $wbXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels',   $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml',     $xml);
        $zip->addFromString('xl/sharedStrings.xml',         $ssXml);
        $zip->addFromString('xl/styles.xml',                $stylesXml);
        $zip->close();
    }

    /**
     * Génère un .xlsx et le télécharge directement (pour HTTP).
     */
    public function downloadXlsx(string $filename, array $headers, array $rows, string $sheetName = 'Feuille1'): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_') . '.xlsx';
        $this->writeXlsx($tmp, $headers, $rows, $sheetName);

        // Nettoyer tout output buffer pour éviter la corruption
        while (ob_get_level()) ob_end_clean();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: max-age=0');
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    // ──────────────────────────────────────────────────────────────
    //  LECTURE XLSX
    // ──────────────────────────────────────────────────────────────

    /**
     * Lit la première feuille d'un .xlsx et retourne les lignes.
     * La première ligne est utilisée comme en-têtes ; les suivantes
     * sont retournées comme tableaux associatifs [header => valeur].
     *
     * @param string $filepath
     * @return array{headers: string[], rows: array[]}
     * @throws RuntimeException
     */
    public function readXlsx(string $filepath): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException("L'extension ZipArchive PHP n'est pas disponible.");
        }
        if (!file_exists($filepath)) {
            throw new RuntimeException("Fichier introuvable : $filepath");
        }

        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            throw new RuntimeException("Fichier xlsx invalide ou corrompu.");
        }

        // Charger sharedStrings.xml (table des textes)
        $strings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $sst = @simplexml_load_string($ssXml);
            if ($sst !== false) {
                foreach ($sst->si as $si) {
                    // Une <si> peut contenir un <t> simple ou plusieurs <r><t>…</t></r>
                    if (isset($si->t)) {
                        $strings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $concat = '';
                        foreach ($si->r as $r) {
                            if (isset($r->t)) $concat .= (string)$r->t;
                        }
                        $strings[] = $concat;
                    } else {
                        $strings[] = '';
                    }
                }
            }
        }

        // Identifier la première feuille (sheet1.xml par défaut, sinon scanner)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            // Fallback : chercher la première feuille qui existe
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                    $sheetXml = $zip->getFromName($name);
                    break;
                }
            }
        }
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException("Aucune feuille trouvée dans le fichier xlsx.");
        }

        $sheet = @simplexml_load_string($sheetXml);
        if ($sheet === false) {
            throw new RuntimeException("Impossible de parser la feuille xlsx.");
        }

        // Parcourir les lignes
        $rawRows = [];
        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            $maxCol = 0;
            foreach ($row->c as $c) {
                // Référence cellule (ex: B3) → index colonne 1-based
                $ref = (string)$c['r'];
                $colLetters = preg_replace('/\d+/', '', $ref);
                $colIdx = $this->letterToColumn($colLetters); // 1-based

                $type = (string)($c['t'] ?? '');
                $val = isset($c->v) ? (string)$c->v : '';

                if ($type === 's') {
                    // Référence sharedStrings
                    $val = $strings[(int)$val] ?? '';
                } elseif ($type === 'inlineStr' && isset($c->is)) {
                    $val = (string)$c->is->t;
                } elseif ($type === 'b') {
                    $val = $val === '1' ? 'TRUE' : 'FALSE';
                }
                // Type vide ou 'n' (numeric) ou 'str' → val tel quel

                $rowData[$colIdx - 1] = $val;
                if ($colIdx > $maxCol) $maxCol = $colIdx;
            }
            // Combler les trous éventuels pour un tableau dense
            $dense = [];
            for ($i = 0; $i < $maxCol; $i++) {
                $dense[$i] = $rowData[$i] ?? '';
            }
            $rawRows[] = $dense;
        }

        if (empty($rawRows)) {
            return ['headers' => [], 'rows' => []];
        }

        // Première ligne = en-têtes
        $headers = array_map(fn($h) => trim((string)$h), $rawRows[0]);
        $dataRows = [];
        for ($r = 1; $r < count($rawRows); $r++) {
            $row = $rawRows[$r];
            // Skip lignes complètement vides
            $isEmpty = true;
            foreach ($row as $v) {
                if ($v !== '' && $v !== null) { $isEmpty = false; break; }
            }
            if ($isEmpty) continue;

            $assoc = [];
            foreach ($headers as $i => $h) {
                if ($h === '') continue;
                $assoc[$h] = $row[$i] ?? '';
            }
            $dataRows[] = $assoc;
        }

        return ['headers' => $headers, 'rows' => $dataRows];
    }

    // ──────────────────────────────────────────────────────────────
    //  CSV (en alternative)
    // ──────────────────────────────────────────────────────────────

    /**
     * Lit un CSV (UTF-8, BOM optionnel, séparateur , ou ; auto-détecté).
     * Retourne le même format que readXlsx().
     */
    public function readCsv(string $filepath): array
    {
        if (!file_exists($filepath)) {
            throw new RuntimeException("Fichier introuvable : $filepath");
        }
        $content = file_get_contents($filepath);
        // Retirer BOM UTF-8 éventuel
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        // Détecter séparateur : prendre celui le plus fréquent sur la première ligne
        $firstLine = strtok($content, "\n");
        $candidates = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];
        foreach ($candidates as $d => &$n) {
            $n = substr_count($firstLine ?: '', $d);
        }
        arsort($candidates);
        $delim = array_key_first($candidates);

        // Lire toutes les lignes
        $tmp = fopen('php://temp', 'r+');
        fwrite($tmp, $content);
        rewind($tmp);

        $allRows = [];
        while (($row = fgetcsv($tmp, 0, $delim, '"', '\\')) !== false) {
            $allRows[] = array_map(fn($v) => trim((string)$v), $row);
        }
        fclose($tmp);

        if (empty($allRows)) return ['headers' => [], 'rows' => []];

        $headers = $allRows[0];
        $dataRows = [];
        for ($r = 1; $r < count($allRows); $r++) {
            $row = $allRows[$r];
            $isEmpty = true;
            foreach ($row as $v) { if ($v !== '') { $isEmpty = false; break; } }
            if ($isEmpty) continue;
            $assoc = [];
            foreach ($headers as $i => $h) {
                if ($h === '') continue;
                $assoc[$h] = $row[$i] ?? '';
            }
            $dataRows[] = $assoc;
        }
        return ['headers' => $headers, 'rows' => $dataRows];
    }

    /**
     * Génère un CSV UTF-8 avec BOM (Excel le détecte natif).
     */
    public function downloadCsv(string $filename, array $headers, array $rows): void
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo "\xEF\xBB\xBF"; // BOM UTF-8
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers, ';');
        foreach ($rows as $row) {
            $line = [];
            if ($this->isAssoc($row)) {
                foreach ($headers as $h) $line[] = $row[$h] ?? '';
            } else {
                $line = $row;
            }
            fputcsv($out, $line, ';');
        }
        fclose($out);
        exit;
    }

    /**
     * Détecte le format d'un fichier uploadé et le lit.
     * @return array{headers: string[], rows: array[]}
     */
    public function readFile(string $filepath, string $originalName = ''): array
    {
        $ext = strtolower(pathinfo($originalName ?: $filepath, PATHINFO_EXTENSION));
        if ($ext === 'csv' || $ext === 'txt') return $this->readCsv($filepath);
        return $this->readXlsx($filepath);
    }

    // ──────────────────────────────────────────────────────────────
    //  HELPERS
    // ──────────────────────────────────────────────────────────────

    /** Convertit un index 1-based en lettres de colonne Excel (1→A, 27→AA…) */
    private function columnLetter(int $col): string
    {
        $letters = '';
        while ($col > 0) {
            $col--;
            $letters = chr(65 + ($col % 26)) . $letters;
            $col = (int)floor($col / 26);
        }
        return $letters;
    }

    /** Inverse : "A" → 1, "AA" → 27 */
    private function letterToColumn(string $letters): int
    {
        $col = 0;
        $letters = strtoupper($letters);
        for ($i = 0; $i < strlen($letters); $i++) {
            $col = $col * 26 + (ord($letters[$i]) - 64);
        }
        return $col;
    }

    /** Détecte si un tableau est associatif (clés string) ou indexé */
    private function isAssoc(array $arr): bool
    {
        if (empty($arr)) return false;
        foreach (array_keys($arr) as $k) {
            if (is_string($k)) return true;
        }
        return false;
    }

    /** Échappe les caractères spéciaux pour XML */
    private function escapeXml(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
