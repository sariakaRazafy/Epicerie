let total = 0;

function ajouter(nom, prix) {
    total += prix;
    document.getElementById("listeAchats").innerHTML += `
        <li class="list-group-item d-flex justify-content-between">
            ${nom}
            <span>${prix} Ar</span>
        </li>
    `;
    document.getElementById("total").innerHTML = total;
}

function payer() {
    alert("Montant à payer : " + total + " Ar");
    total = 0;
    document.getElementById("listeAchats").innerHTML = "";
    document.getElementById("total").innerHTML = "0";
}

// Configuration par défaut DataTables (français)
// Si DataTables est chargé, applique une traduction française globale
if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
    // tente d'utiliser le pack i18n CDN; si bloqué, DataTables tombera sur ses textes par défaut
    jQuery.extend(true, jQuery.fn.dataTable.defaults, {
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
        }
    });
}
