// Script pour le graphique d'évolution des résultats
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si on est sur l'onglet examens et qu'il y a des résultats
    const canvas = document.getElementById('evolutionChart');
    if (!canvas) return;
    
    // Récupérer les données d'évolution
    fetch(`<?= BASE_URL ?>patients/evolution-resultats/<?= $patient['id'] ?>`)
        .then(r => r.json())
        .then(data => {
            if (data.length === 0) {
                canvas.style.display = 'none';
                return;
            }
            
            const ctx = canvas.getContext('2d');
            
            // Grouper par type d'examen
            const examensGroupes = {};
            data.forEach(resultat => {
                if (!examensGroupes[resultat.nom_examen]) {
                    examensGroupes[resultat.nom_examen] = [];
                }
                examensGroupes[resultat.nom_examen].push({
                    date: resultat.date_resultat,
                    valeur: parseFloat(resultat.valeur_numerique),
                    unite: resultat.unite
                });
            });
            
            // Créer le graphique avec Chart.js (si disponible)
            if (typeof Chart !== 'undefined') {
                const datasets = [];
                const colors = ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#6f42c1'];
                let colorIndex = 0;
                
                Object.keys(examensGroupes).forEach(nomExamen => {
                    const donnees = examensGroupes[nomExamen];
                    if (donnees.length > 1) { // Seulement si plusieurs points
                        datasets.push({
                            label: nomExamen,
                            data: donnees.map(d => ({
                                x: d.date,
                                y: d.valeur
                            })),
                            borderColor: colors[colorIndex % colors.length],
                            backgroundColor: colors[colorIndex % colors.length] + '20',
                            tension: 0.1
                        });
                        colorIndex++;
                    }
                });
                
                new Chart(ctx, {
                    type: 'line',
                    data: { datasets },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'day'
                                }
                            },
                            y: {
                                beginAtZero: false
                            }
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: 'Évolution des paramètres biologiques'
                            }
                        }
                    }
                });
            } else {
                // Fallback simple sans Chart.js
                canvas.style.display = 'none';
            }
        })
        .catch(() => {
            canvas.style.display = 'none';
        });
});

// Gestion des onglets avec URL
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si un onglet est spécifié dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    
    if (tab) {
        const tabElement = document.querySelector(`#${tab}-tab`);
        if (tabElement) {
            const tabInstance = new bootstrap.Tab(tabElement);
            tabInstance.show();
        }
    }
});