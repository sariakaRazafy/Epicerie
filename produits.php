<?php
include("./includes/header.php");
include("./db/connexion.php");

// Récupère tous les produits depuis la base de données
$sql = "SELECT * FROM produits";
$result = $cnx->query($sql);
?>

<div class="container-fluid mt-4">
    <div class="row">

        <!-- Zone Produits -->
        <div class="col-12 col-md-9">
            <!-- Barre de titre, recherche et bouton d'ajout avec fond gris clair -->
            <div class="bg-light p-3 mb-3 border rounded" style="background-color: #f5f5f5;">
                <div class="d-flex align-items-center justify-content-between">
                    <h3 class="mb-0 me-3" id="produitdispo">Produits disponibles</h3>
                    <input id="searchInput" type="text" class="form-control me-2" placeholder="Rechercher un produit..." style="max-width: 400px;">
                    <button id="openAddBtn" class="btn btn-primary">Ajouter un produit</button>
                </div>
            </div>

            <?php
            // Si la requête SQL échoue, affiche l'erreur (utile pour le débogage)
            if (!$result) {
                echo '<div class="text-danger">Erreur SQL : ' . htmlspecialchars($cnx->error) . '</div>';
            }
            // Si aucun produit n'est trouvé, invite à utiliser le bouton d'ajout
            elseif ($result->num_rows == 0) { ?>

                <!-- Aucun produit trouvé -->
                <div class="alert alert-info">Aucun produit trouvé. Utilisez le bouton "Ajouter un produit" ci-dessus pour en créer.</div>

            <?php
                // Sinon, affiche la liste des produits comme avant
            } else {
            ?>
                <div class="row g-3" id="productList">
                    <?php while ($prod = $result->fetch_assoc()) { ?>
                        <!-- Boucle sur chaque produit récupéré depuis la base -->
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2 product-card"
                            data-product-name="<?php echo strtolower(htmlspecialchars($prod['nom'], ENT_QUOTES)); ?>">
                            <!-- Colonne responsive Bootstrap -->
                            <div class="card h-100 shadow"
                                data-id="<?php echo $prod['id']; ?>"
                                data-nom="<?php echo htmlspecialchars($prod['nom'], ENT_QUOTES); ?>"
                                data-prix="<?php echo $prod['prix']; ?>"
                                data-unite="<?php echo htmlspecialchars($prod['unite'] ?? 'unité', ENT_QUOTES); ?>"
                                data-stock="<?php echo $prod['stock']; ?>"
                                data-image="<?php echo htmlspecialchars($prod['image'], ENT_QUOTES); ?>">
                                <!-- Card Bootstrap avec ombre et hauteur 100% -->
                                <img src="uploads/<?php echo $prod['image']; ?>" class="card-img-top" style="height:120px; object-fit:cover;">
                                <!-- Image du produit -->
                                <div class="card-body text-center">
                                    <!-- Corps de la card, texte centré -->
                                    <h6 class="card-title"><?php echo $prod['nom']; ?></h6>
                                    <!-- Nom du produit -->
                                    <p class="card-text small"><?php echo $prod['prix']; ?> Ar / <small><?php echo $prod['unite'] ?? 'unité'; ?></small></p>
                                    <!-- Prix et unité -->
                                    <div class="mb-2">
                                        <!-- input quantité avec data-unite pour ajuster via JS -->
                                        <input id="qty-<?php echo $prod['id']; ?>" data-unite="<?php echo htmlspecialchars($prod['unite'] ?? 'unité', ENT_QUOTES); ?>" type="number" min="1" step="1" value="1" class="form-control form-control-sm">
                                    </div>
                                    <!-- Quantité pour le client -->
                                    <button class="btn btn-success btn-sm w-100" onclick="ajouterAuPanier(<?php echo $prod['id']; ?>, '<?php echo addslashes($prod['nom']); ?>', <?php echo $prod['prix']; ?>, document.getElementById('qty-<?php echo $prod['id']; ?>').value, '<?php echo addslashes($prod['unite'] ?? 'unité'); ?>')">Ajouter</button>
                                    <div class="mt-2 d-flex justify-content-between">
                                        <button class="btn btn-outline-secondary btn-sm" onclick="openEditModal(this.closest('.card'))">Modifier</button>
                                        <button class="btn btn-outline-danger btn-sm" onclick="deleteProduct(<?php echo $prod['id']; ?>)">Supprimer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

        <!-- Sidebar Calculatrice -->
        <div class="col-12 col-md-3 bg-light p-3 border-start shadow-sm mt-4 mt-md-0">
            <h4>Total Client</h4>
            <ul id="listeAchats" class="list-group mb-3"></ul>

            <h5>Total : <span id="total">0</span> Ar</h5>
            <div class="mb-2">
                <label class="form-label">Billet donné</label>
                <input id="billetDonne" type="number" class="form-control" min="0" step="1" placeholder="Montant reçu du client">
            </div>
            <div class="mb-3">
                <label class="form-label">Monnaie à rendre</label>
                <input id="monnaieRendre" type="text" class="form-control" readonly value="0">
            </div>
            <button class="btn btn-success w-100" onclick="enregistrerVente()">Enregistrer la vente</button>
        </div>

    </div>
</div>

<!-- Modal d'ajout / modification (masqué par défaut) -->
<div id="productModal" style="display:none; position:fixed; left:0; top:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:1050; align-items:center; justify-content:center;">
    <div style="background:#fff; max-width:600px; width:100%; margin:auto; padding:20px; border-radius:6px; position:relative;">
        <h5 id="modalTitle">Ajouter un produit</h5>
        <form id="modalProductForm" enctype="multipart/form-data">
            <input type="hidden" name="id" id="modal_id" />
            <div class="mb-2">
                <label class="form-label">Nom</label>
                <input id="modal_nom" name="nom" class="form-control" type="text" required />
            </div>
            <div class="mb-2">
                <label class="form-label">Prix</label>
                <input id="modal_prix" name="prix" class="form-control" type="number" step="0.01" required />
            </div>
            <div class="mb-2">
                <label class="form-label">Unité</label>
                <select id="modal_unite" name="unite" class="form-control">
                    <option value="unité">Unité</option>
                    <option value="kg">Kg</option>
                    <option value="l">Litre</option>
                    <option value="kapoka">Kapoaka</option>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label">Stock</label>
                <input id="modal_stock" name="stock" class="form-control" type="number" step="1" value="0" />
                <div id="stockHelp" class="form-text">Entrez la quantité en fonction de l'unité choisie.</div>
            </div>
            <div class="mb-2">
                <label class="form-label">Image (laisser vide pour garder)</label>
                <input id="modal_image" name="image" class="form-control" type="file" accept="image/*" />
            </div>
            <div class="d-flex justify-content-end">
                <button type="button" id="closeModalBtn" class="btn btn-secondary me-2">Annuler</button>
                <button type="submit" id="saveModalBtn" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Fonction de recherche : filtre les produits en temps réel
    document.getElementById('searchInput').addEventListener('keyup', function() {
        var searchValue = this.value.toLowerCase();
        var productCards = document.querySelectorAll('.product-card');

        productCards.forEach(function(card) {
            var productName = card.getAttribute('data-product-name');
            if (productName.includes(searchValue)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Gestion du panier côté client
    var cart = []; // {id, nom, prix, quantite, unite}

    // Formatte un nombre en currency locale (Ar)
    function formatCurrency(value) {
        return Number(value).toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    // Met à jour l'affichage du panier dans la sidebar
    function renderCart() {
        var list = document.getElementById('listeAchats');
        var totalEl = document.getElementById('total');
        list.innerHTML = '';
        var total = 0;

        cart.forEach(function(item, idx) {
            var lineTotal = item.prix * item.quantite;
            total += lineTotal;

            var li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-start';
            li.innerHTML = '<div><strong>' + item.nom + '</strong><br><small>' + item.quantite + ' ' + item.unite + ' x ' + formatCurrency(item.prix) + ' Ar</small></div>';

            var right = document.createElement('div');
            right.className = 'text-end';
            right.innerHTML = '<div>' + formatCurrency(lineTotal) + ' Ar</div>';

            var removeBtn = document.createElement('button');
            removeBtn.className = 'btn btn-sm btn-outline-danger mt-1';
            removeBtn.textContent = 'Supprimer';
            removeBtn.addEventListener('click', function() {
                removeFromCart(idx);
            });

            right.appendChild(removeBtn);
            li.appendChild(right);
            list.appendChild(li);
        });

        totalEl.innerText = formatCurrency(total);
    }

    // Ajoute ou met à jour un produit dans le panier (avec id)
    function ajouterAuPanier(id, nom, prix, quantite, unite) {
        // parse valeurs
        var q = parseFloat(quantite);
        if (isNaN(q) || q <= 0) {
            alert('Quantité invalide');
            return;
        }

        // Cherche si produit déjà présent (même id + unité)
        var found = null;
        for (var i = 0; i < cart.length; i++) {
            if (cart[i].id === id && cart[i].unite === unite && cart[i].prix == prix) {
                found = i;
                break;
            }
        }

        if (found !== null) {
            cart[found].quantite = parseFloat((cart[found].quantite + q).toFixed(3));
        } else {
            cart.push({
                id: id,
                nom: nom,
                prix: parseFloat(prix),
                quantite: parseFloat(q),
                unite: unite
            });
        }

        renderCart();
    }

    // Retire un élément du panier par index
    function removeFromCart(index) {
        cart.splice(index, 1);
        renderCart();
    }

    // Ouvre le modal en mode 'ajout'
    document.getElementById('openAddBtn').addEventListener('click', function() {
        openAddModal();
    });

    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Ajouter un produit';
        document.getElementById('modal_id').value = '';
        document.getElementById('modal_nom').value = '';
        document.getElementById('modal_prix').value = '';
        document.getElementById('modal_unite').value = 'unité';
        document.getElementById('modal_stock').value = 0;
        document.getElementById('modal_image').value = '';
        updateModalStockBehavior();
        document.getElementById('productModal').style.display = 'flex';
    }

    // Ouvre le modal en mode 'edit' et préremplit depuis la card
    function openEditModal(cardElement) {
        var id = cardElement.getAttribute('data-id');
        var nom = cardElement.getAttribute('data-nom');
        var prix = cardElement.getAttribute('data-prix');
        var unite = cardElement.getAttribute('data-unite');
        var stock = cardElement.getAttribute('data-stock');

        document.getElementById('modalTitle').innerText = 'Modifier le produit';
        document.getElementById('modal_id').value = id;
        document.getElementById('modal_nom').value = nom;
        document.getElementById('modal_prix').value = prix;
        document.getElementById('modal_unite').value = unite;
        document.getElementById('modal_stock').value = stock;
        document.getElementById('modal_image').value = '';
        updateModalStockBehavior();
        document.getElementById('productModal').style.display = 'flex';
    }

    // Écoute le changement d'unité dans le modal pour ajuster le champ stock
    document.getElementById('modal_unite').addEventListener('change', updateModalStockBehavior);

    // Fermer modal
    document.getElementById('closeModalBtn').addEventListener('click', function() {
        document.getElementById('productModal').style.display = 'none';
    });

    // Soumission du formulaire du modal (ajout ou modification)
    document.getElementById('modalProductForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = document.getElementById('modalProductForm');
        var formData = new FormData(form);
        var id = formData.get('id');
        var url = id ? 'modifier_produit.php' : 'ajouter_produit.php';

        fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Produit enregistré avec succès.');
                    window.location.reload();
                } else {
                    alert('Erreur : ' + (data.error || 'inconnue'));
                    console.error(data);
                }
            })
            .catch(err => {
                alert('Erreur réseau');
                console.error(err);
            });
    });

    // Supprime un produit par id
    function deleteProduct(id) {
        if (!confirm('Confirmer la suppression du produit #' + id + ' ?')) return;
        fetch('supprimer_produit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id=' + encodeURIComponent(id)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Produit supprimé.');
                    window.location.reload();
                } else {
                    alert('Erreur : ' + (data.error || 'inconnue'));
                }
            })
            .catch(err => {
                alert('Erreur réseau');
                console.error(err);
            });
    }

    // Ajuste le comportement du champ 'stock' du modal selon l'unité sélectionnée
    function updateModalStockBehavior() {
        var unite = document.getElementById('modal_unite').value;
        var stockInput = document.getElementById('modal_stock');
        var help = document.getElementById('stockHelp');

        if (unite === 'unité') {
            stockInput.step = 1;
            stockInput.min = 0;
            stockInput.value = parseInt(stockInput.value) || 0;
            stockInput.placeholder = 'Entier (ex: 3)';
            help.innerText = 'Pour "unité" la quantité doit être un entier (ex: 1, 2, 3).';
        } else {
            // kg ou litre autorisent des valeurs décimales
            stockInput.step = 0.01;
            stockInput.min = 0;
            stockInput.value = parseFloat(stockInput.value) || 0;
            stockInput.placeholder = 'Décimal autorisé (ex: 0.5, 1.25)';
            help.innerText = 'Pour "kg" ou "litre" vous pouvez utiliser des décimales (ex: 0.5, 1.25).';
        }
    }

    // Ajuste les inputs de quantité affichés sur les cards en fonction de leur data-unite
    function adjustCardQtyInputs() {
        var qtyInputs = document.querySelectorAll('input[id^="qty-"]');
        qtyInputs.forEach(function(inp) {
            var unite = inp.getAttribute('data-unite') || 'unité';
            if (unite === 'unité') {
                inp.step = 1;
                inp.min = 1;
                if (parseFloat(inp.value) < 1) inp.value = 1;
            } else {
                inp.step = 0.01;
                inp.min = 0.01;
                if (parseFloat(inp.value) < 0.01) inp.value = 0.01;
            }
        });
    }

    // Calcul du monnaie à rendre
    document.getElementById('billetDonne').addEventListener('input', function() {
        var billet = parseFloat(this.value) || 0;
        var total = parseFloat(document.getElementById('total').innerText.replace(/\s/g, '').replace(/,/g, '')) || 0;
        var monnaie = billet - total;
        document.getElementById('monnaieRendre').value = monnaie >= 0 ? monnaie.toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }) : '0';
    });

    // Ajuste les champs quantité des cards au chargement
    document.addEventListener('DOMContentLoaded', function() {
        adjustCardQtyInputs();
    });

    // Enregistre la vente et l'envoie au serveur via enregistrer_vente.php
    function enregistrerVente() {
        var billet = parseFloat(document.getElementById('billetDonne').value) || 0;
        var total = parseFloat(document.getElementById('total').innerText.replace(/\s/g, '').replace(/,/g, '')) || 0;
        var monnaie = billet - total;
        if (cart.length === 0) {
            alert('Aucun produit dans le panier.');
            return;
        }
        if (billet < total) {
            alert('Le billet donné est insuffisant.');
            return;
        }
        // Prépare les données à envoyer
        var vente = {
            produits: cart,
            total: total,
            payment_method: 'cash'
        };

        fetch('enregistrer_vente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(vente)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Vente enregistrée (ID: ' + data.order_id + ').\nMonnaie à rendre : ' + monnaie.toLocaleString(undefined, {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2
                    }) + ' Ar');
                    cart = [];
                    renderCart();
                    document.getElementById('billetDonne').value = '';
                    document.getElementById('monnaieRendre').value = '0';
                } else {
                    alert('Erreur enregistrement vente : ' + (data.error || 'inconnue'));
                }
            })
            .catch(err => {
                alert('Erreur réseau lors de l\'enregistrement');
                console.error(err);
            });
    }
</script>

<?php include("./includes/footer.php"); ?>