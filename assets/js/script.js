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
