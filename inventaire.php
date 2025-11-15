<?php
include('./includes/header.php');
if (function_exists('protegerPage')) protegerPage();
require_once __DIR__ . '/db/connexion.php';

$res = $cnx->query('SELECT id, nom, unite, prix, stock FROM produits ORDER BY nom ASC');
?>
<div class="container mt-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3>Inventaire des produits</h3>
        <div>
            <button id="refreshBtn" class="btn btn-light btn-sm">Recharger</button>
            <button id="exportBtn" class="btn btn-outline-secondary btn-sm">Exporter CSV</button>
        </div>
    </div>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table table-sm table-striped" id="inventoryTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Produit</th>
                        <th>Unité</th>
                        <th class="text-end">Prix (Ar)</th>
                        <th class="text-end">Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $res->fetch_assoc()): ?>
                        <tr data-id="<?php echo (int)$row['id']; ?>">
                            <td><?php echo (int)$row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['nom']); ?></td>
                            <td><?php echo htmlspecialchars($row['unite']); ?></td>
                            <td class="text-end"><?php echo number_format($row['prix'], 0, ',', ' '); ?></td>
                            <td class="text-end"><input type="number" step="0.01" min="0" value="<?php echo htmlspecialchars($row['stock']); ?>" class="form-control form-control-sm stock-input" style="width:120px; margin-left:auto;"></td>
                            <td>
                                <button class="btn btn-sm btn-primary save-stock">Enregistrer</button>
                                <button class="btn btn-sm btn-outline-secondary edit-product">Modifier</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // DataTable init (will pick French defaults from global script.js)
    $(document).ready(function() {
        $('#inventoryTable').DataTable({
            paging: true,
            pageLength: 25,
            lengthChange: false
        });
    });

    document.getElementById('refreshBtn').addEventListener('click', function() {
        location.reload();
    });

    document.getElementById('exportBtn').addEventListener('click', function() {
        const rows = Array.from(document.querySelectorAll('#inventoryTable tbody tr'));
        const csv = ['id,nom,unite,prix,stock'].concat(rows.map(r => {
            const id = r.getAttribute('data-id');
            const cols = r.querySelectorAll('td');
            const nom = '"' + cols[1].innerText.replace(/"/g, '""') + '"';
            const unite = cols[2].innerText;
            const prix = cols[3].innerText.replace(/\s/g, '').replace(/,/g, '');
            const stock = r.querySelector('.stock-input').value;
            return [id, nom, unite, prix, stock].join(',');
        })).join('\n');
        const blob = new Blob([csv], {
            type: 'text/csv;charset=utf-8;'
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'inventaire_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    });

    // Save single stock
    document.querySelectorAll('.save-stock').forEach(btn => {
        btn.addEventListener('click', async function() {
            const tr = this.closest('tr');
            const id = tr.getAttribute('data-id');
            const input = tr.querySelector('.stock-input');
            let value = parseFloat(input.value);
            if (isNaN(value) || value < 0) {
                alert('Stock invalide');
                return;
            }
            this.disabled = true;
            try {
                const res = await fetch('update_stock.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + encodeURIComponent(id) + '&stock=' + encodeURIComponent(value)
                });
                const json = await res.json();
                if (!json.success) alert('Erreur: ' + (json.error || 'inconnue'));
                else {
                    // Optionally show a small success flash
                    this.innerText = 'OK';
                    setTimeout(() => this.innerText = 'Enregistrer', 1000);
                }
            } catch (err) {
                alert('Erreur réseau');
            }
            this.disabled = false;
        });
    });

    // Open product modal (reuse existing modal on produits.php if available) — fallback to edit page
    document.querySelectorAll('.edit-product').forEach(btn => {
        btn.addEventListener('click', function() {
            const tr = this.closest('tr');
            const id = tr.getAttribute('data-id');
            // if produits.php modal exists, navigate there with query param to edit, otherwise open modifier_produit.php
            if (window.location.pathname.endsWith('inventaire.php')) {
                window.location.href = 'modifier_produit.php?id=' + encodeURIComponent(id);
            }
        });
    });
</script>

<?php include('./includes/footer.php'); ?>