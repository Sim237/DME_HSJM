#!/bin/bash
# =================================================================
# Script d'exécution des migrations DME — à lancer sur le serveur
# Usage : bash /var/www/html/dme/migrations/run_migrations.sh
# =================================================================

set -e
source /var/www/html/dme/.env 2>/dev/null || true

DB_HOST=${DB_HOST:-localhost}
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS:-}
DB_NAME=${DB_NAME:-dme_hospital}

MYSQL="mysql -h$DB_HOST -u$DB_USER -p\"$DB_PASS\" $DB_NAME"

echo "=== Migration 002 : Contraintes de sécurité & index ==="

# UNIQUE dossier_numero
if ! eval "$MYSQL -e \"SHOW INDEX FROM patients WHERE Key_name='uk_dossier_numero'\"" 2>/dev/null | grep -q uk_dossier_numero; then
    eval "$MYSQL -e \"ALTER TABLE patients ADD UNIQUE KEY uk_dossier_numero (dossier_numero)\"" 2>/dev/null && echo "OK: UNIQUE dossier_numero" || echo "SKIP: UNIQUE dossier_numero"
else echo "EXISTS: uk_dossier_numero"; fi

# Index patients
for idx_col in "idx_statut_hosp:statut_hosp" "idx_statut_parcours:statut_parcours" "idx_type_client:type_client" "idx_circuit:circuit"; do
    idx=$(echo $idx_col | cut -d: -f1)
    col=$(echo $idx_col | cut -d: -f2)
    if ! eval "$MYSQL -e \"SHOW INDEX FROM patients WHERE Key_name='$idx'\"" 2>/dev/null | grep -q $idx; then
        eval "$MYSQL -e \"ALTER TABLE patients ADD INDEX $idx ($col)\"" 2>/dev/null && echo "OK: INDEX patients.$idx" || echo "SKIP: $idx"
    else echo "EXISTS: patients.$idx"; fi
done

# Index hospitalisations
for idx_col in "idx_hosp_statut:statut" "idx_hosp_patient:patient_id" "idx_hosp_service:service_id"; do
    idx=$(echo $idx_col | cut -d: -f1)
    col=$(echo $idx_col | cut -d: -f2)
    if ! eval "$MYSQL -e \"SHOW INDEX FROM hospitalisations WHERE Key_name='$idx'\"" 2>/dev/null | grep -q $idx; then
        eval "$MYSQL -e \"ALTER TABLE hospitalisations ADD INDEX $idx ($col)\"" 2>/dev/null && echo "OK: INDEX hospitalisations.$idx" || echo "SKIP: $idx"
    else echo "EXISTS: hospitalisations.$idx"; fi
done

# Index demandes_laboratoire
for idx_col in "idx_dl_statut:statut" "idx_dl_patient:patient_id"; do
    idx=$(echo $idx_col | cut -d: -f1)
    col=$(echo $idx_col | cut -d: -f2)
    if ! eval "$MYSQL -e \"SHOW INDEX FROM demandes_laboratoire WHERE Key_name='$idx'\"" 2>/dev/null | grep -q $idx; then
        eval "$MYSQL -e \"ALTER TABLE demandes_laboratoire ADD INDEX $idx ($col)\"" 2>/dev/null && echo "OK: INDEX demandes_laboratoire.$idx" || echo "SKIP: $idx"
    else echo "EXISTS: demandes_laboratoire.$idx"; fi
done

# Index lits
if ! eval "$MYSQL -e \"SHOW INDEX FROM lits WHERE Key_name='idx_lits_statut'\"" 2>/dev/null | grep -q idx_lits_statut; then
    eval "$MYSQL -e \"ALTER TABLE lits ADD INDEX idx_lits_statut (statut)\"" 2>/dev/null && echo "OK: INDEX lits.statut" || echo "SKIP"
else echo "EXISTS: lits.idx_lits_statut"; fi

echo ""
echo "=== Migration 003 : Colonnes applicatives ==="

# Colonnes patients
for col_ddl in "femme_enceinte:TINYINT(1) NOT NULL DEFAULT 0" "parametres_requis:TINYINT(1) NOT NULL DEFAULT 0"; do
    col=$(echo $col_ddl | cut -d: -f1)
    ddl=$(echo $col_ddl | cut -d: -f2-)
    if ! eval "$MYSQL -e \"SHOW COLUMNS FROM patients LIKE '$col'\"" 2>/dev/null | grep -q $col; then
        eval "$MYSQL -e \"ALTER TABLE patients ADD COLUMN $col $ddl\"" 2>/dev/null && echo "OK: patients.$col" || echo "SKIP: $col"
    else echo "EXISTS: patients.$col"; fi
done

# Tables chat & formulaires
for tbl in "chat_conversations" "chat_participants" "chat_messages" "user_presence" "formulaires_data" "formulaires_soumis" "patient_documents"; do
    if ! eval "$MYSQL -e \"SHOW TABLES LIKE '$tbl'\"" 2>/dev/null | grep -q $tbl; then
        echo "À créer manuellement : $tbl (voir 003_app_migrations.sql)"
    else echo "EXISTS: $tbl"; fi
done

echo ""
echo "=== Migrations terminées ==="
