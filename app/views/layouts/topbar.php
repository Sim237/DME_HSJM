<header class="topbar">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <!-- Bouton Menu Mobile/Tablette (visible sous 992px) -->
            <button class="btn btn-link d-lg-none p-0 me-3" type="button" id="sidebarToggleBtn" aria-label="Ouvrir le menu">
                <i class="bi bi-list fs-4 text-dark"></i>
            </button>
            
            <!-- Barre de Recherche Globale -->
            <div class="position-relative flex-grow-1 me-4" style="max-width: 500px;">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input class="form-control border-start-0 bg-transparent" 
                           id="globalSearchInput" 
                           type="text" 
                           placeholder="Rechercher patient, dossier, médicament..." 
                           aria-label="Search"
                           autocomplete="off"
                           style="box-shadow: none;">
                </div>
                <!-- Résultats de recherche -->
                <div id="globalSearchResults" class="search-global-results"></div>
            </div>

            <!-- Actions rapides et Profil -->
            <div class="d-flex align-items-center gap-3">
                <!-- Notifications -->
                <div class="dropdown">
                    <button class="btn btn-link p-0 position-relative" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5 text-muted"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6em;">3</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width: 300px;">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li><a class="dropdown-item" href="#">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                <div>
                                    <div class="fw-bold">Stock faible</div>
                                    <small class="text-muted">3 médicaments en rupture</small>
                                </div>
                            </div>
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="#">Voir toutes les notifications</a></li>
                    </ul>
                </div>

                <!-- Profil Utilisateur -->
                <div class="dropdown">
                    <button class="btn btn-link p-0 d-flex align-items-center text-decoration-none" 
                            type="button" 
                            data-bs-toggle="dropdown">
                        <div class="d-none d-lg-block text-end me-2">
                            <div class="fw-bold text-dark" style="font-size: 0.9em;">
                                <?= $_SESSION['user_nom'] ?? 'Utilisateur' ?>
                            </div>
                            <small class="text-muted">
                                <?= $_SESSION['user_role'] ?? 'Médecin' ?>
                            </small>
                        </div>
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 40px; height: 40px;">
                            <i class="bi bi-person-fill text-white"></i>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>profil">
                            <i class="bi bi-person me-2"></i>Mon Profil
                        </a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>parametres">
                            <i class="bi bi-gear me-2"></i>Paramètres
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout">
                            <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
// ===== TOGGLE SIDEBAR MOBILE/TABLETTE =====
(function() {
    // Créer l'overlay au premier chargement
    if (!document.getElementById('sidebarOverlay')) {
        const overlay = document.createElement('div');
        overlay.id = 'sidebarOverlay';
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);

        overlay.addEventListener('click', function() {
            closeSidebar();
        });
    }

    const toggleBtn = document.getElementById('sidebarToggleBtn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const sidebar  = document.querySelector('.sidebar');
            const overlay  = document.getElementById('sidebarOverlay');
            if (sidebar && sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    function openSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar) sidebar.classList.add('show');
        if (overlay) overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar) sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    // Fermer la sidebar si on passe en desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            closeSidebar();
        }
    });
})();

// ===== RECHERCHE GLOBALE =====
// Logique JS de la recherche globale
const searchInput = document.getElementById('globalSearchInput');
const searchResults = document.getElementById('globalSearchResults');
let searchTimeout;

searchInput.addEventListener('input', function(e) {
    const query = e.target.value.trim();
    
    // Délai pour ne pas spammer le serveur (Debounce)
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        searchResults.style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch('<?= BASE_URL ?>dashboard/global-search?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                searchResults.innerHTML = '';
                
                if (data.length === 0) {
                    searchResults.innerHTML = '<div class="p-3 text-muted text-center">Aucun résultat trouvé</div>';
                } else {
                    // Grouper par catégorie
                    const categories = {};
                    data.forEach(item => {
                        if (!categories[item.type]) categories[item.type] = [];
                        categories[item.type].push(item);
                    });

                    for (const [type, items] of Object.entries(categories)) {
                        searchResults.innerHTML += `<div class="search-category">${type}</div>`;
                        items.forEach(item => {
                            let icon = 'bi-question';
                            let link = '#';
                            let text = item.label;
                            let subtext = item.subtext || '';

                            if (type === 'PATIENT') {
                                icon = 'bi-person';
                                link = '<?= BASE_URL ?>consultation/dossier-patient/' + item.id;
                            } else if (type === 'MEDICAMENT') {
                                icon = 'bi-capsule';
                                link = '<?= BASE_URL ?>pharmacie';
                            } else if (type === 'LIT') {
                                icon = 'bi-hospital';
                                link = '<?= BASE_URL ?>lits';
                            }

                            searchResults.innerHTML += `
                                <a href="${link}" class="search-item">
                                    <div class="search-icon"><i class="bi ${icon}"></i></div>
                                    <div>
                                        <div class="fw-bold">${text}</div>
                                        <div class="small text-muted">${subtext}</div>
                                    </div>
                                </a>
                            `;
                        });
                    }
                }
                searchResults.style.display = 'block';
            })
            .catch(error => {
                console.error('Erreur de recherche:', error);
                searchResults.innerHTML = '<div class="p-3 text-danger text-center">Erreur de recherche</div>';
                searchResults.style.display = 'block';
            });
    }, 300);
});

// Fermer les résultats si on clique ailleurs
document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.style.display = 'none';
    }
});
</script>