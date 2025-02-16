document.addEventListener("DOMContentLoaded", () => {
    // Données simulées pour le bétail en vedette
    const featuredLivestock = [
      { id: 1, name: "Vache Angus", price: 1200, image: "https://via.placeholder.com/300x200.png?text=Vache+Angus" },
      { id: 2, name: "Mouton Mérinos", price: 300, image: "https://via.placeholder.com/300x200.png?text=Mouton+Merinos" },
      {
        id: 3,
        name: "Cochon Berkshire",
        price: 250,
        image: "https://via.placeholder.com/300x200.png?text=Cochon+Berkshire",
      },
    ]
  
    // Afficher le bétail en vedette
    const featuredContainer = document.getElementById("featured-livestock")
    featuredLivestock.forEach((animal) => {
      const animalCard = createAnimalCard(animal)
      featuredContainer.appendChild(animalCard)
    })
  
    // Gérer la soumission du formulaire de recherche
    const searchForm = document.getElementById("search-form")
    searchForm.addEventListener("submit", function (e) {
      e.preventDefault()
      const searchTerm = this.querySelector("input").value.toLowerCase()
  
      // Simuler une recherche (dans un vrai scénario, cela serait une requête à une API)
      const searchResults = featuredLivestock.filter((animal) => animal.name.toLowerCase().includes(searchTerm))
  
      displaySearchResults(searchResults)
    })
  })
  
  function createAnimalCard(animal) {
    const col = document.createElement("div")
    col.className = "col-md-4 mb-4"
    col.innerHTML = `
          <div class="card">
              <img src="${animal.image}" class="card-img-top" alt="${animal.name}">
              <div class="card-body">
                  <h5 class="card-title">${animal.name}</h5>
                  <p class="card-text">Prix: ${animal.price} €</p>
                  <button class="btn btn-primary">Ajouter au panier</button>
              </div>
          </div>
      `
    return col
  }
  
  function displaySearchResults(results) {
    const resultsContainer = document.getElementById("search-results")
    resultsContainer.innerHTML = ""
  
    if (results.length === 0) {
      resultsContainer.innerHTML = "<p>Aucun résultat trouvé.</p>"
      return
    }
  
    results.forEach((animal) => {
      const resultCard = document.createElement("div")
      resultCard.className = "card mb-3"
      resultCard.innerHTML = `
              <div class="row g-0">
                  <div class="col-md-4">
                      <img src="${animal.image}" class="img-fluid rounded-start card-img" alt="${animal.name}">
                  </div>
                  <div class="col-md-8">
                      <div class="card-body">
                          <h5 class="card-title">${animal.name}</h5>
                          <p class="card-text">Prix: ${animal.price} €</p>
                          <button class="btn btn-primary">Ajouter au panier</button>
                      </div>
                  </div>
              </div>
          `
      resultsContainer.appendChild(resultCard)
    })
  }
  
  