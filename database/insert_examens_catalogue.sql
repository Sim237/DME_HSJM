-- ============================================================
-- CATALOGUE COMPLET DES EXAMENS - HSJM BIOLOGIE CLINIQUE
-- Source : Bulletin d'examens du Laboratoire de Biologie Clinique
-- Prix   : 0 FCFA (à renseigner ultérieurement)
-- ============================================================

-- ── BIOCHIMIE SANGUINE (BIOS0010 → suite des 9 existants) ──
INSERT IGNORE INTO examens_laboratoire
  (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite)
VALUES
('BIOS0010', 'TRANSAMINASES ASAT',                'BIOCHIMIE SANGUINE', 'SANG', 24, 0, 0, 1,    5,    40, 'U/L'),
('BIOS0011', 'TRANSAMINASES ALAT',                'BIOCHIMIE SANGUINE', 'SANG', 24, 0, 0, 1,    5,    45, 'U/L'),
('BIOS0012', 'BILIRUBINE TOTALE',                 'BIOCHIMIE SANGUINE', 'SANG', 24, 0, 0, 1,  3.4,  17.1, 'µmol/L'),
('BIOS0013', 'BILIRUBINE DIRECTE',                'BIOCHIMIE SANGUINE', 'SANG', 24, 0, 0, 1, NULL,   5.1, 'µmol/L'),
('BIOS0014', 'GAMMA GT',                          'BIOCHIMIE SANGUINE', 'SANG', 24, 1, 0, 1,   10,    71, 'U/L'),
('BIOS0015', 'AMYLASEMIE',                        'BIOCHIMIE SANGUINE', 'SANG', 24, 0, 0, 1,   25,   125, 'U/L'),
('BIOS0016', 'PHOSPHATASE ALCALINE',              'BIOCHIMIE SANGUINE', 'SANG', 24, 1, 0, 1,   44,   147, 'U/L'),
('BIOS0017', 'CHOLESTEROL TOTAL',                 'BIOCHIMIE SANGUINE', 'SANG', 24, 1, 0, 1, NULL,   5.2, 'mmol/L'),
('BIOS0018', 'HDL CHOLESTEROL',                   'BIOCHIMIE SANGUINE', 'SANG', 24, 1, 0, 1,  1.0,  NULL, 'mmol/L'),
('BIOS0019', 'LDL CHOLESTEROL',                   'BIOCHIMIE SANGUINE', 'SANG', 24, 1, 0, 1, NULL,   3.4, 'mmol/L'),
('BIOS0020', 'TRIGLYCERIDES',                     'BIOCHIMIE SANGUINE', 'SANG', 24, 1, 0, 1, NULL,   1.7, 'mmol/L'),
('BIOS0021', 'CALCEMIE',                          'BIOCHIMIE SANGUINE', 'SANG', 24, 0, 0, 1, 2.15,  2.55, 'mmol/L'),
('BIOS0022', 'MAGNESEMIE',                        'BIOCHIMIE SANGUINE', 'SANG', 24, 0, 0, 1, 0.70,  1.05, 'mmol/L'),
('BIOS0023', 'IONOGRAMME SIMPLE NaKCl',           'BIOCHIMIE SANGUINE', 'SANG', 24, 0, 0, 1, NULL,  NULL, 'mmol/L'),
('BIOS0024', 'IONOGRAMME COMPLET Na/K/Cl/Mg/Ca',  'BIOCHIMIE SANGUINE', 'SANG', 24, 0, 0, 1, NULL,  NULL, 'mmol/L'),
('BIOS0025', 'HEMOGLOBINE GLYQUEE (HbA1c)',       'BIOCHIMIE SANGUINE', 'SANG', 24, 0, 0, 1,  4.0,   6.0, '%'),
('BIOS0026', 'CHOLINESTERASE',                    'BIOCHIMIE SANGUINE', 'SANG', 48, 0, 0, 1, 4000, 12000, 'U/L'),
('BIOS0027', 'G6PD',                              'BIOCHIMIE SANGUINE', 'SANG', 48, 0, 0, 1,  4.6,  13.5, 'U/g Hb'),
('BIOS0028', 'ALBUMINEMIE',                       'BIOCHIMIE SANGUINE', 'SANG', 24, 0, 0, 1,   35,    50, 'g/L'),
('BIOS0029', 'PHOSPHORE INORGANIQUE',             'BIOCHIMIE SANGUINE', 'SANG', 24, 1, 0, 1, 0.80,  1.50, 'mmol/L'),
('BIOS0030', 'CK MB',                             'BIOCHIMIE SANGUINE', 'SANG', 24, 0, 0, 1, NULL,    25, 'U/L'),
('BIOS0031', 'LIPASEMIE',                         'BIOCHIMIE SANGUINE', 'SANG', 24, 0, 0, 1,   10,   140, 'U/L'),
('BIOS0032', 'LDH',                               'BIOCHIMIE SANGUINE', 'SANG', 24, 0, 0, 1,  140,   280, 'U/L');

-- ── BIOCHIMIE URINAIRE ──────────────────────────────────────
INSERT IGNORE INTO examens_laboratoire
  (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite)
VALUES
('BIOU0001', 'IONOGRAMME URINAIRE NaKCl', 'BIOCHIMIE URINAIRE', 'URINE',  24, 0, 0, 1, NULL, NULL, 'mmol/24h'),
('BIOU0002', 'CALCIURIE',                 'BIOCHIMIE URINAIRE', 'URINE',  24, 0, 0, 1,  2.5,  7.5, 'mmol/24h'),
('BIOU0003', 'AMYLASURIE',               'BIOCHIMIE URINAIRE', 'URINE',  24, 0, 0, 1,    1,   17, 'U/h'),
('BIOU0004', 'URINES Alb/Sucre',         'BIOCHIMIE URINAIRE', 'URINE',   4, 0, 0, 1, NULL, NULL, ''),
('BIOU0005', 'PROTEINURIE DE 24H',       'BIOCHIMIE URINAIRE', 'URINE',  24, 0, 0, 1, NULL, 0.15, 'g/24h'),
('BIOU0006', 'URINE alb/s/Ph/sg',        'BIOCHIMIE URINAIRE', 'URINE',   2, 0, 0, 1, NULL, NULL, ''),
('BIOU0007', 'TEST GROSSESSE URINAIRE',  'BIOCHIMIE URINAIRE', 'URINE',   2, 0, 0, 1, NULL, NULL, '');

-- ── BIOCHIMIE LCR-LP ────────────────────────────────────────
INSERT IGNORE INTO examens_laboratoire
  (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite)
VALUES
('LCRL0001', 'LCR Glucose/Proteine/Cl', 'BIOCHIMIE LCR-LP', 'LCR', 24, 0, 0, 1, NULL, NULL, ''),
('LCRL0002', 'LP PROTEINES',            'BIOCHIMIE LCR-LP', 'LCR', 24, 0, 0, 1, 0.15, 0.45, 'g/L');

-- ── BIOCHIMIE (Marqueurs tumoraux & cardiaques) ─────────────
INSERT IGNORE INTO examens_laboratoire
  (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite)
VALUES
('BIOT0001', 'ALPHA FOETOPROTEINE (AFP)', 'BIOCHIMIE', 'SANG', 48, 0, 0, 1, NULL,   10, 'ng/mL'),
('BIOT0002', 'CEA',                       'BIOCHIMIE', 'SANG', 48, 0, 0, 1, NULL,    5, 'ng/mL'),
('BIOT0003', 'PSA TOTAL',                 'BIOCHIMIE', 'SANG', 48, 0, 0, 1, NULL,    4, 'ng/mL'),
('BIOT0004', 'PSA LIBRE',                 'BIOCHIMIE', 'SANG', 48, 0, 0, 1, NULL, NULL, 'ng/mL'),
('BIOT0005', 'TROPONINE I (cTnI)',        'BIOCHIMIE', 'SANG',  6, 0, 0, 1, NULL, 0.04, 'ng/mL'),
('BIOT0006', 'NT-pro BNP',               'BIOCHIMIE', 'SANG', 24, 0, 0, 1, NULL,  125, 'pg/mL');

-- ── HEMATOLOGIE ─────────────────────────────────────────────
INSERT IGNORE INTO examens_laboratoire
  (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite)
VALUES
('HEM0001', 'NFS',                        'HEMATOLOGIE', 'SANG',  24, 0, 0, 1, NULL, NULL, ''),
('HEM0002', 'TP (Taux de Prothrombine)',   'HEMATOLOGIE', 'SANG',  24, 0, 0, 1,   70,  100, '%'),
('HEM0003', 'TCK',                         'HEMATOLOGIE', 'SANG',  24, 0, 0, 1,   25,   38, 'secondes'),
('HEM0004', 'VS (Vitesse de Sedimentation)','HEMATOLOGIE','SANG',  24, 0, 0, 1, NULL,   20, 'mm/1h'),
('HEM0005', 'GROUPE SANGUIN',              'HEMATOLOGIE', 'SANG',  24, 0, 0, 1, NULL, NULL, ''),
('HEM0006', 'CROSS MATCH',                 'HEMATOLOGIE', 'SANG',  24, 0, 0, 1, NULL, NULL, ''),
('HEM0007', 'ELECTROPHORESE HEMOGLOBINE',  'HEMATOLOGIE', 'SANG',  72, 0, 0, 1, NULL, NULL, ''),
('HEM0008', 'TEMPS DE SAIGNEMENT',         'HEMATOLOGIE', 'SANG',   2, 0, 0, 1,    2,    8, 'minutes'),
('HEM0009', 'CD4',                         'HEMATOLOGIE', 'SANG',  48, 0, 0, 1,  500, 1500, 'cellules/µL'),
('HEM0010', 'PHENOTYPE RHESUS',            'HEMATOLOGIE', 'SANG',  24, 0, 0, 1, NULL, NULL, ''),
('HEM0011', 'TAUX D''HEMOGLOBINE',         'HEMATOLOGIE', 'SANG',  24, 0, 0, 1,   12,   17, 'g/dL'),
('HEM0012', 'GS RH + PHENOTYPE RHESUS',    'HEMATOLOGIE', 'SANG',  24, 0, 0, 1, NULL, NULL, ''),
('HEM0013', 'D-DIMERE',                    'HEMATOLOGIE', 'SANG',  24, 0, 0, 1, NULL,  500, 'µg/L');

-- ── HEMATOLOGIE PED ─────────────────────────────────────────
INSERT IGNORE INTO examens_laboratoire
  (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite)
VALUES
('HEMP0001', 'CRP PEDIATRIQUE',                  'HEMATOLOGIE PED', 'SANG', 24, 0, 0, 1, NULL,    6, 'mg/L'),
('HEMP0002', 'ELECTROPHORESE PEDIATRIQUE',        'HEMATOLOGIE PED', 'SANG', 72, 0, 0, 1, NULL, NULL, ''),
('HEMP0003', 'NFS PEDIATRIQUE',                   'HEMATOLOGIE PED', 'SANG', 24, 0, 0, 1, NULL, NULL, ''),
('HEMP0004', 'TAUX D''HEMOGLOBINE NOUVEAU-NE',    'HEMATOLOGIE PED', 'SANG', 24, 0, 0, 1,   14,   20, 'g/dL');

-- ── BACTERIOLOGIE PED ───────────────────────────────────────
INSERT IGNORE INTO examens_laboratoire
  (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite)
VALUES
('BACP0001', 'COPROCULTURE PEDIATRIQUE',         'BACTERIOLOGIE PED', 'SELLES', 72, 0, 0, 1, NULL, NULL, ''),
('BACP0002', 'HEMOCULTURE PEDIATRIQUE',           'BACTERIOLOGIE PED', 'SANG',   72, 0, 0, 1, NULL, NULL, ''),
('BACP0003', 'LCR ED/CULT/PROTEINE PEDIATRIQUE',  'BACTERIOLOGIE PED', 'LCR',    72, 0, 0, 1, NULL, NULL, ''),
('BACP0004', 'PUS/LP PEDIATRIQUE',                'BACTERIOLOGIE PED', 'AUTRE',  48, 0, 0, 1, NULL, NULL, '');

-- ── HORMONES ────────────────────────────────────────────────
INSERT IGNORE INTO examens_laboratoire
  (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite)
VALUES
('HOR0001', 'B HCG QUANTITATIF (serique)',  'HORMONES', 'SANG', 24, 0, 0, 1, NULL,    5, 'mUI/mL'),
('HOR0002', 'PROGESTERONE',                 'HORMONES', 'SANG', 48, 0, 0, 1,  0.6, 113.6, 'nmol/L'),
('HOR0003', 'TESTOSTERONE',                 'HORMONES', 'SANG', 48, 0, 0, 1,  0.3,  27.8, 'nmol/L'),
('HOR0004', 'TT3 (Tri-iodothyronine)',      'HORMONES', 'SANG', 48, 0, 0, 1, 1.07,  3.18, 'nmol/L'),
('HOR0005', 'TT4 (Thyroxine totale)',        'HORMONES', 'SANG', 48, 0, 0, 1,   58,   161, 'nmol/L'),
('HOR0006', 'PROLACTINE',                   'HORMONES', 'SANG', 48, 0, 0, 1,   86,   496, 'µUI/mL'),
('HOR0007', 'OESTRADIOL (E2)',              'HORMONES', 'SANG', 48, 0, 0, 1,   77,  1612, 'pmol/L'),
('HOR0008', 'FSH',                          'HORMONES', 'SANG', 48, 0, 0, 1,  1.5,  12.5, 'mUI/mL'),
('HOR0009', 'LH',                           'HORMONES', 'SANG', 48, 0, 0, 1,  1.7,  12.6, 'mUI/mL'),
('HOR0010', 'TSH',                          'HORMONES', 'SANG', 24, 0, 0, 1, 0.27,  4.20, 'µUI/mL');

-- ── BACTERIOLOGIE ───────────────────────────────────────────
INSERT IGNORE INTO examens_laboratoire
  (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite)
VALUES
('BAC0001', 'CRACHAT BAAR x2',         'BACTERIOLOGIE', 'AUTRE',  48, 0, 0, 1, NULL, NULL, ''),
('BAC0002', 'CRACHAT CULTURE/ATB',     'BACTERIOLOGIE', 'AUTRE',  72, 0, 0, 1, NULL, NULL, ''),
('BAC0003', 'PCV EXAMEN DIRECT',       'BACTERIOLOGIE', 'AUTRE',  24, 0, 0, 1, NULL, NULL, ''),
('BAC0004', 'PCV/ED/CULTURE/ATB',      'BACTERIOLOGIE', 'AUTRE',  72, 0, 0, 1, NULL, NULL, ''),
('BAC0005', 'PU EXAMEN DIRECT',        'BACTERIOLOGIE', 'AUTRE',  24, 0, 0, 1, NULL, NULL, ''),
('BAC0006', 'PU/ED/CULTURE/ATB',       'BACTERIOLOGIE', 'AUTRE',  72, 0, 0, 1, NULL, NULL, ''),
('BAC0007', 'LCR DIRECT',              'BACTERIOLOGIE', 'LCR',    24, 0, 0, 1, NULL, NULL, ''),
('BAC0008', 'LCR/ED/CULTURE/ATB',      'BACTERIOLOGIE', 'LCR',    72, 0, 0, 1, NULL, NULL, ''),
('BAC0009', 'ECBU',                    'BACTERIOLOGIE', 'URINE',  72, 0, 0, 1, NULL, NULL, ''),
('BAC0010', 'COPROCULTURE',            'BACTERIOLOGIE', 'SELLES', 72, 0, 0, 1, NULL, NULL, ''),
('BAC0011', 'SPERMOGRAMME',            'BACTERIOLOGIE', 'AUTRE',  24, 0, 0, 1, NULL, NULL, ''),
('BAC0012', 'CHAMPIGNON/ANTIFONGIQUE', 'BACTERIOLOGIE', 'AUTRE',  72, 0, 0, 1, NULL, NULL, ''),
('BAC0013', 'SPERMOCULTURE',           'BACTERIOLOGIE', 'AUTRE',  72, 0, 0, 1, NULL, NULL, ''),
('BAC0014', 'HEMOCULTURE',             'BACTERIOLOGIE', 'SANG',   72, 0, 0, 1, NULL, NULL, ''),
('BAC0015', 'LIQUIDE DE PONCTION',     'BACTERIOLOGIE', 'AUTRE',  48, 0, 0, 1, NULL, NULL, ''),
('BAC0016', 'MYCOPLASME',              'BACTERIOLOGIE', 'AUTRE',  72, 0, 0, 1, NULL, NULL, '');

-- ── PARASITOLOGIE ───────────────────────────────────────────
INSERT IGNORE INTO examens_laboratoire
  (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite)
VALUES
('PAR0001', 'CULOT URINAIRE',            'PARASITOLOGIE', 'URINE',  24, 0, 0, 1, NULL, NULL, ''),
('PAR0002', 'RECHERCHE HP (H. pylori)',  'PARASITOLOGIE', 'SELLES', 24, 0, 0, 1, NULL, NULL, ''),
('PAR0003', 'GOUTTE EPAISSE',            'PARASITOLOGIE', 'SANG',   24, 0, 0, 1, NULL, NULL, ''),
('PAR0004', 'GE QUALIFIEE',              'PARASITOLOGIE', 'SANG',   24, 0, 0, 1, NULL, NULL, ''),
('PAR0005', 'SCOTCH TEST ANAL',          'PARASITOLOGIE', 'AUTRE',  24, 0, 0, 1, NULL, NULL, ''),
('PAR0006', 'SKIN SNIP',                 'PARASITOLOGIE', 'AUTRE',  48, 0, 0, 1, NULL, NULL, ''),
('PAR0007', 'SELLES AKOP',              'PARASITOLOGIE', 'SELLES', 24, 0, 0, 1, NULL, NULL, ''),
('PAR0008', 'SELLES AKOP/CONCENTRE',    'PARASITOLOGIE', 'SELLES', 24, 0, 0, 1, NULL, NULL, '');

-- ── SEROLOGIE ───────────────────────────────────────────────
INSERT IGNORE INTO examens_laboratoire
  (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite)
VALUES
('SER0001', 'ASLO',                    'SEROLOGIE', 'SANG',   24, 0, 0, 1, NULL,  200, 'UI/mL'),
('SER0002', 'CRP',                     'SEROLOGIE', 'SANG',   24, 0, 0, 1, NULL,    6, 'mg/L'),
('SER0003', 'FACTEUR RHUMATOIDE',      'SEROLOGIE', 'SANG',   48, 0, 0, 1, NULL,   14, 'UI/mL'),
('SER0004', 'BW (TPHA/VDRL)',          'SEROLOGIE', 'SANG',   24, 0, 0, 1, NULL, NULL, ''),
('SER0005', 'TPHA',                    'SEROLOGIE', 'SANG',   24, 0, 0, 1, NULL, NULL, ''),
('SER0006', 'VDRL',                    'SEROLOGIE', 'SANG',   24, 0, 0, 1, NULL, NULL, ''),
('SER0007', 'SEROLOGIE WIDAL',         'SEROLOGIE', 'SANG',   48, 0, 0, 1, NULL, NULL, ''),
('SER0008', 'SEROLOGIE H PYLORI',      'SEROLOGIE', 'SANG',   24, 0, 0, 1, NULL, NULL, ''),
('SER0009', 'ANTIGENE H PYLORI',       'SEROLOGIE', 'SELLES', 24, 0, 0, 1, NULL, NULL, ''),
('SER0010', 'HIV (TDR)',               'SEROLOGIE', 'SANG',    2, 0, 0, 1, NULL, NULL, ''),
('SER0011', 'Ac anti-VHC TDR',        'SEROLOGIE', 'SANG',    2, 0, 0, 1, NULL, NULL, ''),
('SER0012', 'Ag HBs TDR',             'SEROLOGIE', 'SANG',    2, 0, 0, 1, NULL, NULL, '');

-- ── SEROLOGIE ELISA ─────────────────────────────────────────
INSERT IGNORE INTO examens_laboratoire
  (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite)
VALUES
('SERE0001', 'TOXOPLASMOSE IGG/IGM',         'SEROLOGIE ELISA', 'SANG', 48, 0, 0, 1, NULL, NULL, 'UI/mL'),
('SERE0002', 'TOXOPLASMOSE IGG',             'SEROLOGIE ELISA', 'SANG', 48, 0, 0, 1, NULL, NULL, 'UI/mL'),
('SERE0003', 'TOXOPLASMOSE IGM',             'SEROLOGIE ELISA', 'SANG', 48, 0, 0, 1, NULL, NULL, ''),
('SERE0004', 'CHLAMYDIA IGG/IGM',           'SEROLOGIE ELISA', 'SANG', 48, 0, 0, 1, NULL, NULL, ''),
('SERE0005', 'CHLAMYDIA IGM',               'SEROLOGIE ELISA', 'SANG', 48, 0, 0, 1, NULL, NULL, ''),
('SERE0006', 'RUBEOLE IGG/IGM',             'SEROLOGIE ELISA', 'SANG', 24, 0, 0, 1, NULL, NULL, 'UI/mL'),
('SERE0007', 'RUBEOLE IGG',                 'SEROLOGIE ELISA', 'SANG', 24, 0, 0, 1,   10, NULL, 'UI/mL'),
('SERE0008', 'RUBEOLE IGM',                 'SEROLOGIE ELISA', 'SANG', 24, 0, 0, 1, NULL, NULL, ''),
('SERE0009', 'B HCG QUANTITATIF ELISA',     'SEROLOGIE ELISA', 'SANG', 24, 0, 0, 1, NULL,    5, 'mUI/mL'),
('SERE0010', 'Ag HBs (ELISA)',              'SEROLOGIE ELISA', 'SANG', 24, 0, 0, 1, NULL, NULL, ''),
('SERE0011', 'HIV CONFIRMATION',             'SEROLOGIE ELISA', 'SANG', 72, 0, 0, 1, NULL, NULL, ''),
('SERE0012', 'Ag HBe',                      'SEROLOGIE ELISA', 'SANG', 48, 0, 0, 1, NULL, NULL, ''),
('SERE0013', 'Ac anti-HBc',                 'SEROLOGIE ELISA', 'SANG', 48, 0, 0, 1, NULL, NULL, ''),
('SERE0014', 'Ac anti-HBs',                 'SEROLOGIE ELISA', 'SANG', 48, 0, 0, 1,   10, NULL, 'mUI/mL'),
('SERE0015', 'Ac anti-HBc TOTAUX IgG/IgM',  'SEROLOGIE ELISA', 'SANG', 48, 0, 0, 1, NULL, NULL, '');

-- ── ANATOMO CYTO-PATH ────────────────────────────────────────
INSERT IGNORE INTO examens_laboratoire
  (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite)
VALUES
('ACP0001', 'FCV (Frottis Cervico-Vaginal)', 'ANATOMO CYTO-PATH', 'AUTRE', 168, 0, 0, 1, NULL, NULL, ''),
('ACP0002', 'APPENDICITE',                   'ANATOMO CYTO-PATH', 'AUTRE', 168, 0, 0, 1, NULL, NULL, ''),
('ACP0003', 'PIECE MOYENNE INF 15CM',        'ANATOMO CYTO-PATH', 'AUTRE', 168, 0, 0, 1, NULL, NULL, ''),
('ACP0004', 'GROSSE PIECE SUP 15CM',         'ANATOMO CYTO-PATH', 'AUTRE', 336, 0, 0, 1, NULL, NULL, ''),
('ACP0005', 'MICROBIOPSIE',                  'ANATOMO CYTO-PATH', 'AUTRE', 120, 0, 0, 1, NULL, NULL, ''),
('ACP0006', 'FROTTIS ECOULEMENT',            'ANATOMO CYTO-PATH', 'AUTRE',  72, 0, 0, 1, NULL, NULL, '');

-- ── CHARGE VIRALE ────────────────────────────────────────────
INSERT IGNORE INTO examens_laboratoire
  (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite)
VALUES
('CV0001', 'CHARGE VIRALE HEPATITE B', 'CHARGE VIRALE', 'SANG', 72, 0, 0, 1, NULL, NULL, 'UI/mL'),
('CV0002', 'CHARGE VIRALE HEPATITE C', 'CHARGE VIRALE', 'SANG', 72, 0, 0, 1, NULL, NULL, 'UI/mL');

-- ── VÉRIFICATION ─────────────────────────────────────────────
SELECT categorie, COUNT(*) AS nb_examens
FROM examens_laboratoire
GROUP BY categorie
ORDER BY categorie;
