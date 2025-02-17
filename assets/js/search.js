document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const categorieFilter = document.getElementById('categorie-filter');
    const prixFilter = document.getElementById('prix-filter');
    const searchResults = document.getElementById('search-results');
    
    let searchTimeout = null;
    
    // Fonction pour mettre à jour les résultats
    function updateResults() {
        clearTimeout(searchTimeout);
        
        searchTimeout = setTimeout(() => {
            const searchTerm = searchInput.value.trim();
            const categorie = categorieFilter.value;
            const prix = prixFilter.value;
            
            // Afficher l'indicateur de chargement
            searchResults.innerHTML = '<div class="loading">Recherche en cours...</div>';
            
            // Préparer les paramètres de recherche
            const params = new URLSearchParams({
                q: searchTerm,
                categorie: categorie,
                prix: prix
            });
            
            // Faire la requête AJAX
            fetch(`api/search.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        searchResults.innerHTML = `<div class="error">${data.error}</div>`;
                        return;
                    }
                    
                    if (data.length === 0) {
                        searchResults.innerHTML = `
                            <div class="no-results">
                                <p>Aucun résultat trouvé</p>
                                <p>Essayez d'autres termes de recherche</p>
                            </div>
                        `;
                        return;
                    }
                    
                    // Afficher les résultats
                    const resultsHTML = data.map(betail => `
                        <div class="betail-card" data-aos="fade-up">
                            <div class="betail-image">
                                <img src="${betail.photo}" alt="${betail.nom_betail}">
                                ${betail.note_vendeur ? `
                                    <div class="vendor-rating">
                                        ⭐ ${parseFloat(betail.note_vendeur).toFixed(1)}
                                    </div>
                                ` : ''}
                            </div>
                            <div class="betail-info">
                                <h3>${betail.nom_betail}</h3>
                                <p class="category">${betail.categorie.charAt(0).toUpperCase() + betail.categorie.slice(1)}</p>
                                <p class="price">${new Intl.NumberFormat('fr-FR').format(betail.prix)} FCFA</p>
                                <p class="vendor">Vendeur: ${betail.vendeur_nom}</p>
                                <div class="betail-actions">
                                    <a href="detail_betail.php?id=${betail.id}" class="btn btn-primary">Voir détails</a>
                                    ${betail.can_add_to_cart ? `
                                        <button onclick="ajouterAuPanier(${betail.id})" class="btn btn-secondary">
                                            Ajouter au panier
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `).join('');
                    
                    searchResults.innerHTML = `
                        <div class="results-count">${data.length} résultat(s) trouvé(s)</div>
                        <div class="results-grid">
                            ${resultsHTML}
                        </div>
                    `;
                })
                .catch(error => {
                    searchResults.innerHTML = `
                        <div class="error">
                            Une erreur est survenue lors de la recherche.
                            Veuillez réessayer plus tard.
                        </div>
                    `;
                    console.error('Erreur de recherche:', error);
                });
        }, 300); // Délai de 300ms pour éviter trop de requêtes
    }
    
    // Écouter les événements de saisie et de changement
    searchInput.addEventListener('input', updateResults);
    categorieFilter.addEventListener('change', updateResults);
    prixFilter.addEventListener('change', updateResults);
    
    // Lancer une première recherche au chargement
    updateResults();
}); 