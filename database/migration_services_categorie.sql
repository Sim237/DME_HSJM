-- ============================================================
-- MIGRATION : Catégorisation des services hospitaliers
-- Date  : 2026-04-16
-- ============================================================
USE dme_hospital;

ALTER TABLE services
  ADD COLUMN categorie ENUM('ADMINISTRATION','CLINIQUE','PARACLINIQUE') DEFAULT 'CLINIQUE' AFTER nom_service;

-- === ADMINISTRATION ===
-- Accueil (17), Administration (14), Accueil PHP (21)
UPDATE services SET categorie = 'ADMINISTRATION' WHERE id IN (14, 17, 21);

-- === PARACLINIQUE ===
-- Laboratoire (8), Imagerie Médicale (9), Pharmacie (10),
-- Bloc Opératoire (11), Banque de Sang (12), Kinésithérapie (13), Anesthésie (16)
UPDATE services SET categorie = 'PARACLINIQUE' WHERE id IN (8, 9, 10, 11, 12, 13, 16);

-- === CLINIQUE (par défaut, mais on précise) ===
-- Médecine Générale (1), Chirurgie (2), Urgences (3), Maternité (4),
-- Pédiatrie (5), Ophtalmologie (6), Odonto-Stomatologie (7),
-- Consultations Externes (15), Paramètres B1 (18), Paramètres B2 (19),
-- Consultation Ext. PHP (20), Paramètres PHP (22)
UPDATE services SET categorie = 'CLINIQUE' WHERE id IN (1, 2, 3, 4, 5, 6, 7, 15, 18, 19, 20, 22);

-- Vérification
SELECT id, nom_service, categorie FROM services ORDER BY categorie, id;
