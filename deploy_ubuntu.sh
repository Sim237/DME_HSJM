#!/bin/bash
# ============================================================
#  SCRIPT DE DÉPLOIEMENT — SimCare+ HSJM
#  Serveur : Ubuntu 24.04 | Apache 2.4 | PHP 8.3 | MySQL 8.0
#
#  Usage :
#    chmod +x deploy_ubuntu.sh
#    sudo bash deploy_ubuntu.sh
#
#  Ce script effectue une INSTALLATION COMPLÈTE ou une MISE À JOUR
#  idempotente : peut être relancé sans risque sur un serveur existant.
# ============================================================

set -e  # Arrêt immédiat en cas d'erreur

# ── Couleurs ────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'

ok()   { echo -e "${GREEN}  ✔ $1${NC}"; }
info() { echo -e "${CYAN}  ➜ $1${NC}"; }
warn() { echo -e "${YELLOW}  ⚠ $1${NC}"; }
fail() { echo -e "${RED}  ✘ $1${NC}"; exit 1; }
h1()   { echo -e "\n${BOLD}${CYAN}══════════════════════════════════════${NC}"; \
         echo -e "${BOLD}  $1${NC}"; \
         echo -e "${BOLD}${CYAN}══════════════════════════════════════${NC}"; }

# ── Variables (à adapter si nécessaire) ─────────────────────
APP_DIR="/var/www/html/dme"
DB_NAME="dme_hospital"
DB_USER="dme"
DB_PASS="dme123"
DB_ROOT_PASS=""            # laisser vide si auth socket root
SERVER_IP="192.168.10.79"
SSL_CERT="/etc/ssl/certs/dme_hospital.crt"
SSL_KEY="/etc/ssl/private/dme_hospital.key"
APACHE_HTTP_CONF="/etc/apache2/sites-available/dme.conf"
APACHE_SSL_CONF="/etc/apache2/sites-available/dme_hospital-ssl.conf"

echo -e "\n${BOLD}  SimCare+ HSJM — Script de déploiement Ubuntu${NC}"
echo -e "  $(date '+%d/%m/%Y %H:%M:%S')\n"

# ════════════════════════════════════════════════════════════
h1 "1. PRÉREQUIS SYSTÈME"
# ════════════════════════════════════════════════════════════
info "Mise à jour des paquets..."
apt-get update -qq

info "Installation Apache, PHP 8.3, MySQL, outils..."
apt-get install -y -qq \
    apache2 \
    php8.3 php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
    php8.3-gd php8.3-zip php8.3-intl php8.3-bcmath \
    mysql-server \
    openssl \
    unzip curl git 2>/dev/null || true

ok "Paquets installés"

# ════════════════════════════════════════════════════════════
h1 "2. BASE DE DONNÉES"
# ════════════════════════════════════════════════════════════
info "Démarrage MySQL..."
systemctl start mysql 2>/dev/null || true
systemctl enable mysql 2>/dev/null || true

info "Création utilisateur et base de données..."
mysql -u root ${DB_ROOT_PASS:+-p"$DB_ROOT_PASS"} <<SQL 2>/dev/null || true
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
ok "Base de données et utilisateur prêts"

# ── Schéma principal ────────────────────────────────────────
if [ -f "${APP_DIR}/dme_hospital.sql" ]; then
    # SÉCURITÉ CRITIQUE : vérifier si la base contient déjà des données
    PATIENT_COUNT=$(mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
        -se "SELECT COUNT(*) FROM information_schema.tables \
             WHERE table_schema='${DB_NAME}' AND table_name='patients';" 2>/dev/null)
    if [ "$PATIENT_COUNT" = "1" ]; then
        DATA_COUNT=$(mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
            -se "SELECT COUNT(*) FROM patients;" 2>/dev/null || echo "0")
    else
        DATA_COUNT="0"
    fi

    if [ "$DATA_COUNT" -gt "0" ] 2>/dev/null; then
        warn "⛔ SCHÉMA NON APPLIQUÉ — La base contient ${DATA_COUNT} patient(s)."
        warn "   dme_hospital.sql contient des DROP TABLE qui EFFACERAIENT toutes les données."
        warn "   Pour forcer une réinstallation complète : supprimez manuellement la base d'abord."
    else
        info "Application du schéma principal (dme_hospital.sql)..."
        mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
            < "${APP_DIR}/dme_hospital.sql" 2>/dev/null && ok "Schéma principal appliqué" \
            || warn "Schéma principal : des erreurs ignorées (normal si déjà existant)"
    fi
fi

# ── Migrations dans l'ordre recommandé ──────────────────────
info "Application des migrations..."
MIGRATIONS=(
    "hospitalisation_tables.sql"
    "hospitalisation_module.sql"
    "migration_reevaluation.sql"
    "migration_reevaluation_v2.sql"
    "migration_suppression_soins.sql"
    "migration_surveillance_intensive_obs.sql"
    "migration_evaluations_douleur.sql"
    "migration_transferts_patients.sql"
    "migration_consultation_parametres_vitaux.sql"
    "migration_notifications_v2.sql"
    "migration_services_categorie.sql"
    "migration_comptes_rendus_hosp.sql"
    "migration_labo_pieces_jointes.sql"
    "migration_patient_resultats_labo.sql"
    "migration_imagerie_fichiers.sql"
    "migration_pharmacie_delivre.sql"
    "migration_formulaires_infirmier.sql"
    "migration_facturation_par_examen.sql"
    "corrections_finales.sql"
)

for migration in "${MIGRATIONS[@]}"; do
    filepath="${APP_DIR}/database/${migration}"
    if [ -f "$filepath" ]; then
        mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
            < "$filepath" 2>/dev/null \
            && ok "Migration : ${migration}" \
            || warn "Migration ignorée (déjà appliquée) : ${migration}"
    else
        warn "Fichier manquant : ${migration}"
    fi
done

# ── Vérification des tables critiques ───────────────────────
info "Vérification des tables critiques..."
CRITICAL_TABLES=(
    "patients" "users" "consultations" "hospitalisations"
    "soins_hospitalisation" "reevaluations_hospitalisees"
    "evaluations_douleur" "patient_parametres" "lits" "chambres"
    "services" "transferts_patients" "notifications"
    "demandes_laboratoire" "demande_examens"
)
ALL_OK=true
for table in "${CRITICAL_TABLES[@]}"; do
    exists=$(mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
        -se "SELECT COUNT(*) FROM information_schema.tables \
             WHERE table_schema='${DB_NAME}' AND table_name='${table}';" 2>/dev/null)
    if [ "$exists" = "1" ]; then
        ok "Table : ${table}"
    else
        warn "Table MANQUANTE : ${table}"
        ALL_OK=false
    fi
done

# ════════════════════════════════════════════════════════════
h1 "3. CERTIFICAT SSL AUTOSIGNÉ"
# ════════════════════════════════════════════════════════════
if [ ! -f "$SSL_CERT" ] || [ ! -f "$SSL_KEY" ]; then
    info "Génération du certificat SSL autosigné (10 ans)..."
    openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
        -keyout "$SSL_KEY" \
        -out "$SSL_CERT" \
        -subj "/CN=${SERVER_IP}/O=HSJM/C=CM" 2>/dev/null
    chmod 600 "$SSL_KEY"
    ok "Certificat SSL créé : ${SSL_CERT}"
else
    ok "Certificat SSL existant conservé"
    # Afficher la date d'expiration
    expiry=$(openssl x509 -in "$SSL_CERT" -noout -enddate 2>/dev/null | cut -d= -f2)
    info "Expire le : ${expiry}"
fi

# ════════════════════════════════════════════════════════════
h1 "4. CONFIGURATION APACHE"
# ════════════════════════════════════════════════════════════
info "Activation des modules Apache..."
a2enmod rewrite ssl headers 2>/dev/null && ok "Modules Apache activés" || true

info "Écriture de la config VirtualHost HTTP (port 80)..."
cat > "$APACHE_HTTP_CONF" <<APACHE
<VirtualHost *:80>
    ServerAdmin webmaster@hsjm
    DocumentRoot ${APP_DIR}
    ServerName ${SERVER_IP}

    <Directory ${APP_DIR}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Redirection HTTP → HTTPS
    RewriteEngine On
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    ErrorLog \${APACHE_LOG_DIR}/dme_error.log
    CustomLog \${APACHE_LOG_DIR}/dme_access.log combined
</VirtualHost>
APACHE
ok "VirtualHost HTTP écrit"

info "Écriture de la config VirtualHost HTTPS (port 443)..."
cat > "$APACHE_SSL_CONF" <<APACHE
<VirtualHost *:443>
    ServerName ${SERVER_IP}
    DocumentRoot ${APP_DIR}

    SSLEngine on
    SSLCertificateFile    ${SSL_CERT}
    SSLCertificateKeyFile ${SSL_KEY}

    <Directory ${APP_DIR}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Sécurité HTTPS
    Header always set Strict-Transport-Security "max-age=31536000"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN

    ErrorLog \${APACHE_LOG_DIR}/dme_ssl_error.log
    CustomLog \${APACHE_LOG_DIR}/dme_ssl_access.log combined
</VirtualHost>
APACHE
ok "VirtualHost HTTPS écrit"

info "Activation des sites..."
a2ensite dme.conf dme_hospital-ssl.conf 2>/dev/null || true
a2dissite 000-default.conf 2>/dev/null || true

info "Test de la configuration Apache..."
apache2ctl configtest 2>&1 | grep -E "Syntax|Error" || true

info "Redémarrage Apache..."
systemctl restart apache2
ok "Apache redémarré"

# ════════════════════════════════════════════════════════════
h1 "5. PERMISSIONS FICHIERS"
# ════════════════════════════════════════════════════════════
info "Application des permissions..."
chown -R www-data:www-data "${APP_DIR}" 2>/dev/null || true
chmod -R 755 "${APP_DIR}" 2>/dev/null || true
# Uploads et assets : écriture
[ -d "${APP_DIR}/assets/uploads" ]   && chmod -R 775 "${APP_DIR}/assets/uploads"   || mkdir -p "${APP_DIR}/assets/uploads"   && chmod 775 "${APP_DIR}/assets/uploads"
[ -d "${APP_DIR}/assets/dicom" ]     && chmod -R 775 "${APP_DIR}/assets/dicom"     || true
[ -d "${APP_DIR}/backups" ]          && chmod -R 775 "${APP_DIR}/backups"           || mkdir -p "${APP_DIR}/backups" && chmod 775 "${APP_DIR}/backups"
# .env : lecture seule
[ -f "${APP_DIR}/.env" ] && chmod 640 "${APP_DIR}/.env" && chown www-data:www-data "${APP_DIR}/.env"
ok "Permissions appliquées"

# ════════════════════════════════════════════════════════════
h1 "6. FICHIER .ENV"
# ════════════════════════════════════════════════════════════
ENV_FILE="${APP_DIR}/.env"
if [ ! -f "$ENV_FILE" ]; then
    info "Création du fichier .env depuis .env.example..."
    if [ -f "${APP_DIR}/.env.example" ]; then
        cp "${APP_DIR}/.env.example" "$ENV_FILE"
        # Remplacer les valeurs par défaut
        sed -i "s|DB_HOST=.*|DB_HOST=localhost|" "$ENV_FILE"
        sed -i "s|DB_NAME=.*|DB_NAME=${DB_NAME}|" "$ENV_FILE"
        sed -i "s|DB_USER=.*|DB_USER=${DB_USER}|" "$ENV_FILE"
        sed -i "s|DB_PASS=.*|DB_PASS=${DB_PASS}|" "$ENV_FILE"
        sed -i "s|BASE_URL=.*|BASE_URL=https://${SERVER_IP}/|" "$ENV_FILE"
        sed -i "s|APP_ENV=.*|APP_ENV=production|" "$ENV_FILE"
        sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" "$ENV_FILE"
        ok ".env créé depuis .env.example"
    else
        # Créer un .env minimal
        cat > "$ENV_FILE" <<ENV
# CONFIGURATION LOCALE — NE PAS COMMITTER
DB_HOST=localhost
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
BASE_URL=https://${SERVER_IP}/
APP_ENV=production
APP_DEBUG=false
ENV
        ok ".env minimal créé"
    fi
    chown www-data:www-data "$ENV_FILE"
    chmod 640 "$ENV_FILE"
else
    ok ".env existant conservé"
    # S'assurer que BASE_URL est bien en HTTPS
    current_url=$(grep "^BASE_URL" "$ENV_FILE" | cut -d= -f2)
    if [[ "$current_url" != https* ]]; then
        sed -i "s|BASE_URL=.*|BASE_URL=https://${SERVER_IP}/|" "$ENV_FILE"
        warn "BASE_URL mise à jour en HTTPS : https://${SERVER_IP}/"
    fi
fi

# ════════════════════════════════════════════════════════════
h1 "7. VÉRIFICATION FINALE"
# ════════════════════════════════════════════════════════════
info "Services actifs..."
systemctl is-active apache2  && ok "Apache2 : actif" || warn "Apache2 : inactif"
systemctl is-active mysql    && ok "MySQL   : actif" || warn "MySQL   : inactif"

info "Test de réponse HTTPS..."
HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" "https://${SERVER_IP}/" 2>/dev/null || echo "000")
if [[ "$HTTP_CODE" =~ ^(200|302|301) ]]; then
    ok "HTTPS répond : HTTP $HTTP_CODE"
else
    warn "HTTPS ne répond pas correctement (code: $HTTP_CODE)"
fi

info "Nombre de tables en base..."
NB_TABLES=$(mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
    -se "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}';" 2>/dev/null || echo "0")
ok "Tables en base : ${NB_TABLES}"

# ── Résumé final ────────────────────────────────────────────
echo ""
echo -e "${BOLD}${GREEN}  ════════════════════════════════════════${NC}"
echo -e "${BOLD}${GREEN}   Déploiement terminé avec succès !${NC}"
echo -e "${BOLD}${GREEN}  ════════════════════════════════════════${NC}"
echo ""
echo -e "  URL de l'application : ${BOLD}https://${SERVER_IP}/${NC}"
echo -e "  Dossier de l'app     : ${BOLD}${APP_DIR}${NC}"
echo -e "  Base de données      : ${BOLD}${DB_NAME}${NC}"
echo -e "  Certificat SSL       : ${BOLD}${SSL_CERT}${NC}"
echo ""
echo -e "  ${YELLOW}⚠ Première connexion : accepter le certificat autosigné dans le navigateur${NC}"
echo -e "  ${YELLOW}  (cliquer Avancé → Continuer vers ${SERVER_IP})${NC}"
echo ""
