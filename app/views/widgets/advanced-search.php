<!-- Recherche globale avancée -->
<div class="search-advanced-container">
    <div class="search-box">
        <input type="text" id="globalSearch" placeholder="Rechercher patients, consultations..." class="form-control">
        <button class="btn btn-outline-secondary" type="button" id="toggleFilters">
            <i class="bi bi-funnel"></i>
        </button>
    </div>
    
    <div class="search-filters" id="searchFilters" style="display: none;">
        <div class="row g-2">
            <div class="col-md-3">
                <select class="form-select form-select-sm" id="filterType">
                    <option value="">Tous types</option>
                    <option value="patient">Patients</option>
                    <option value="consultation">Consultations</option>
                    <option value="hospitalisation">Hospitalisations</option>
                    <option value="examen">Examens</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" class="form-control form-control-sm" id="filterDateFrom" placeholder="Du">
            </div>
            <div class="col-md-3">
                <input type="date" class="form-control form-control-sm" id="filterDateTo" placeholder="Au">
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" id="filterStatus">
                    <option value="">Tous statuts</option>
                    <option value="actif">Actif</option>
                    <option value="termine">Terminé</option>
                    <option value="urgent">Urgent</option>
                    <option value="annule">Annulé</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="search-results" id="searchResults"></div>
</div>

<style>
.search-advanced-container {
    position: relative;
    margin-bottom: 2rem;
}

.search-box {
    display: flex;
    gap: 0.5rem;
}

.search-filters {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 0.5rem;
    border: 1px solid #dee2e6;
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 1000;
    max-height: 400px;
    overflow-y: auto;
    display: none;
}

.search-item {
    padding: 0.75rem;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background 0.2s;
}

.search-item:hover {
    background: #f8f9fa;
}

.search-item-type {
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
}

.search-item-title {
    font-weight: 500;
    color: #212529;
}

.search-item-meta {
    font-size: 0.85rem;
    color: #6c757d;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('globalSearch');
    const toggleFilters = document.getElementById('toggleFilters');
    const searchFilters = document.getElementById('searchFilters');
    const searchResults = document.getElementById('searchResults');
    
    // Toggle filtres
    toggleFilters.addEventListener('click', function() {
        searchFilters.style.display = searchFilters.style.display === 'none' ? 'block' : 'none';
    });
    
    // Recherche avec délai
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch();
        }, 300);
    });
    
    // Recherche avec filtres
    document.querySelectorAll('#searchFilters select, #searchFilters input').forEach(filter => {
        filter.addEventListener('change', performSearch);
    });
    
    function performSearch() {
        const query = searchInput.value.trim();
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        const filters = {
            q: query,
            type: document.getElementById('filterType').value,
            dateFrom: document.getElementById('filterDateFrom').value,
            dateTo: document.getElementById('filterDateTo').value,
            status: document.getElementById('filterStatus').value
        };
        
        fetch(`${BASE_URL}dashboard/advanced-search?${new URLSearchParams(filters)}`)
            .then(response => response.json())
            .then(data => {
                displayResults(data);
            })
            .catch(error => console.error('Erreur recherche:', error));
    }
    
    function displayResults(results) {
        if (results.length === 0) {
            searchResults.innerHTML = '<div class="p-3 text-muted">Aucun résultat trouvé</div>';
        } else {
            searchResults.innerHTML = results.map(item => `
                <div class="search-item" onclick="window.location.href='${item.url}'">
                    <div class="search-item-type">${item.type}</div>
                    <div class="search-item-title">${item.title}</div>
                    <div class="search-item-meta">${item.meta}</div>
                </div>
            `).join('');
        }
        searchResults.style.display = 'block';
    }
    
    // Fermer résultats en cliquant ailleurs
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-advanced-container')) {
            searchResults.style.display = 'none';
        }
    });
});
</script>