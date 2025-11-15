<?php
include('./includes/header.php');
// Protéger la page (redirige vers index si pas connecté)
if (function_exists('protegerPage')) protegerPage();
?>
<div class="container mt-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3>Statistiques des ventes</h3>
        <div>
            <button id="exportCsv" class="btn btn-outline-secondary btn-sm">Exporter CSV</button>
        </div>
    </div>

    <div class="card p-3 mb-3">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="form-label small mb-1">Période</label>
                <input type="date" id="startDate" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">à</label>
                <input type="date" id="endDate" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Top N</label>
                <input type="number" id="limit" class="form-control form-control-sm" value="10" min="1" max="100">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Actions</label>
                <div>
                    <button id="applyBtn" class="btn btn-primary btn-sm">Appliquer</button>
                    <button id="last7Btn" class="btn btn-light btn-sm">7 jours</button>
                    <button id="last30Btn" class="btn btn-light btn-sm">30 jours</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 mb-3">
            <div class="card p-3">
                <h5>Tableau détaillé</h5>
                <div class="table-responsive" style="max-height:360px; overflow:auto;">
                    <table class="table table-sm" id="statsTable">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Quantité</th>
                                <th>CA (Ar)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6 mb-3">
            <div class="card p-3">
                <h5>Top produits par chiffre d'affaires</h5>
                <div class="chart-area">
                    <canvas id="topChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card p-3">
                <h5>Ventes par jour (série temporelle)</h5>
                <div class="chart-area small">
                    <canvas id="timeSeriesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js + plugins -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<!-- Styles fixes pour les charts: hauteur fixe et éviter l'agrandissement dû au wrapping des labels -->
<style>
    /* conteneur qui force la hauteur du canvas */
    .chart-area {
        height: 320px;
        overflow: hidden;
    }

    .chart-area.small {
        height: 200px;
    }

    /* s'assure que le canvas occupe tout le conteneur et respecte la hauteur */
    #topChart,
    #timeSeriesChart {
        width: 100% !important;
        height: 100% !important;
    }

    /* limite l'espace disponible pour les labels x afin d'éviter qu'ils poussent le canvas */
    .card .chart-area {
        display: block;
    }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
    // Register Chart.js plugins if available
    if (window.Chart && window.ChartDataLabels) {
        try {
            Chart.register(ChartDataLabels);
        } catch (e) {
            /* ignore if already registered */
        }
    }
    // Utilitaires
    function formatNumber(n) {
        return Number(n).toLocaleString();
    }

    // Définit les dates par défaut (30 derniers jours)
    function setDefaultDates() {
        const end = new Date();
        const start = new Date();
        start.setDate(end.getDate() - 30);
        document.getElementById('endDate').value = end.toISOString().slice(0, 10);
        document.getElementById('startDate').value = start.toISOString().slice(0, 10);
    }

    setDefaultDates();

    let chart = null;

    async function loadTopSold() {
        const start = document.getElementById('startDate').value;
        const end = document.getElementById('endDate').value;
        const limit = document.getElementById('limit').value || 10;

        const url = `api/stats/top_sold.php?start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}&limit=${encodeURIComponent(limit)}`;
        const res = await fetch(url);
        const data = await res.json();
        if (!data.success) {
            alert('Erreur: ' + (data.error || 'Erreur inconnue'));
            return;
        }

        const rows = data.data || [];

        // Préparer données pour le chart
        const labels = rows.map(r => r.product_name);
        const revenues = rows.map(r => parseFloat(r.revenue));
        const qtys = rows.map(r => parseFloat(r.total_qty || 0));

        // Met à jour le chart
        const ctx = document.getElementById('topChart').getContext('2d');
        if (chart) chart.destroy();

        // Calcul de l'épaisseur de barre pour ~0.5 cm en pixels (approx. 96 DPI)
        const pxPerCm = 96 / 2.54; // ≈37.8
        const barPx = Math.max(6, Math.round(0.5 * pxPerCm)); // ≈19px, minimum 6

        // Génère une couleur par barre (HSL réparti)
        function generateColors(n) {
            const colors = [];
            for (let i = 0; i < n; i++) {
                const hue = Math.round((i * 360) / Math.max(1, n));
                colors.push(`hsl(${hue} 70% 55% / 0.85)`);
            }
            return colors;
        }

        const bgColors = generateColors(labels.length);

        // Plugin léger pour un effet pseudo-3D (ombre portée)
        const pseudo3D = {
            id: 'pseudo3D',
            beforeDraw: function(chart, args, options) {
                const ctx = chart.ctx;
                ctx.save();
                // applique une ombre douce sous le donut
                ctx.shadowColor = options.shadowColor || 'rgba(0,0,0,0.25)';
                ctx.shadowBlur = options.shadowBlur || 18;
                ctx.shadowOffsetX = options.shadowOffsetX || 0;
                ctx.shadowOffsetY = options.shadowOffsetY || 8;
            },
            afterDraw: function(chart) {
                chart.ctx.restore();
            }
        };

        // centerPercent plugin removed — aucun pourcentage central ne sera dessiné

        try {
            chart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Chiffre d'affaires",
                        data: revenues,
                        backgroundColor: bgColors,
                        borderColor: bgColors.map(c => c.replace('/ 0.85', '/ 1')),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '38%',
                    rotation: -90,
                    plugins: {
                        pseudo3D: {
                            shadowColor: 'rgba(0,0,0,0.28)',
                            shadowBlur: 18,
                            shadowOffsetY: 8
                        },
                        legend: {
                            display: true,
                            position: 'right',
                            labels: {
                                // Affiche uniquement le nom du produit (sans pourcentage)
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (!data || !data.datasets || !data.datasets.length) return [];
                                    const ds = data.datasets[0];
                                    return data.labels.map((label, i) => ({
                                        text: label,
                                        fillStyle: Array.isArray(ds.backgroundColor) ? ds.backgroundColor[i] : ds.backgroundColor,
                                        strokeStyle: Array.isArray(ds.borderColor) ? ds.borderColor[i] : ds.borderColor,
                                        hidden: chart.getDataVisibility ? !chart.getDataVisibility(i) : false,
                                        index: i
                                    }));
                                }
                            }
                        },
                        tooltip: {
                            mode: 'nearest'
                        },
                        // aucun label chiffré sur les segments
                        datalabels: {
                            display: false
                        }
                    }
                },
                plugins: [pseudo3D]
            });
        } catch (err) {
            console.error('Erreur création chart topChart:', err);
            const container = document.querySelector('#topChart').closest('.chart-area');
            if (container) container.innerHTML = '<div class="p-3 text-danger">Impossible d\'afficher le graphique. Ouvrez la console pour voir l\'erreur.</div>';
            chart = null;
        }

        // Remplit le tableau
        const tbody = document.querySelector('#statsTable tbody');
        tbody.innerHTML = '';
        rows.forEach(r => {
            const tr = document.createElement('tr');
            const nameTd = document.createElement('td');
            nameTd.textContent = r.product_name;
            const qtyTd = document.createElement('td');
            qtyTd.textContent = r.total_qty ? Number(r.total_qty).toLocaleString() : '-';
            const revTd = document.createElement('td');
            revTd.textContent = r.revenue ? formatNumber(r.revenue) + ' Ar' : '0';
            tr.appendChild(nameTd);
            tr.appendChild(qtyTd);
            tr.appendChild(revTd);
            tbody.appendChild(tr);
        });

        // Initialiser DataTable (ou re-draw si déjà initialisé)
        if ($.fn.dataTable.isDataTable('#statsTable')) {
            $('#statsTable').DataTable().destroy();
        }
        $('#statsTable').DataTable({
            paging: true,
            pageLength: 10,
            lengthChange: false,
            searching: true,
            ordering: true,
            order: [
                [2, 'desc']
            ],
            columnDefs: [{
                targets: [1, 2],
                className: 'text-end'
            }],
            // assurance : utiliser la traduction française (si la configuration globale n'est pas chargée)
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
            }
        });

        // stocke les données pour export
        window._lastStats = rows;
    }

    // Charge et affiche la série temporelle (ventes par jour)
    async function loadTimeSeries() {
        const start = document.getElementById('startDate').value;
        const end = document.getElementById('endDate').value;
        const url = `api/stats/time_series.php?start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`;
        const res = await fetch(url);
        const data = await res.json();
        if (!data.success) {
            console.error('Erreur timeseries', data);
            return;
        }
        const rows = data.data || [];
        const labels = rows.map(r => r.day);
        const revenues = rows.map(r => parseFloat(r.revenue));
        const qtys = rows.map(r => parseFloat(r.qty_sold));

        const tsArea = document.querySelector('#timeSeriesChart').closest('.chart-area');
        // si pas de données, affiche un message clair et supprime le canvas existant
        if (!rows.length) {
            if (window._tsChart) {
                window._tsChart.destroy();
                window._tsChart = null;
            }
            if (tsArea) tsArea.innerHTML = '<div class="p-3 text-muted">Aucune donnée disponible pour la période sélectionnée.</div>';
            return;
        }
        // s'assurer que le canvas existe (au cas où on ait affiché un message précédemment)
        if (tsArea && !tsArea.querySelector('#timeSeriesChart')) {
            tsArea.innerHTML = '<canvas id="timeSeriesChart"></canvas>';
        }

        const ctx = document.getElementById('timeSeriesChart').getContext('2d');
        if (window._tsChart) window._tsChart.destroy();
        try {
            window._tsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'CA (Ar)',
                            data: revenues,
                            backgroundColor: 'rgba(54,162,235,0.85)',
                            borderColor: 'rgba(54,162,235,1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                            categoryPercentage: 0.7,
                            barPercentage: 0.9
                        },
                        {
                            label: 'Quantité',
                            data: qtys,
                            backgroundColor: 'rgba(255,99,132,0.85)',
                            borderColor: 'rgba(255,99,132,1)',
                            borderWidth: 1,
                            yAxisID: 'y1',
                            categoryPercentage: 0.7,
                            barPercentage: 0.9
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        x: {
                            stacked: false
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Chiffre d\'affaires (Ar)'
                            },
                            beginAtZero: true
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'Quantité'
                            },
                            beginAtZero: true
                        }
                    }
                }
            });
        } catch (err) {
            console.error('Erreur création timeSeriesChart:', err);
            const container = document.querySelector('#timeSeriesChart').closest('.chart-area');
            if (container) container.innerHTML = '<div class="p-3 text-danger">Impossible d\'afficher la série temporelle. Ouvrez la console pour voir l\'erreur.</div>';
            window._tsChart = null;
        }
    }

    document.getElementById('applyBtn').addEventListener('click', function() {
        loadTopSold();
        loadTimeSeries();
    });
    document.getElementById('last7Btn').addEventListener('click', function() {
        const end = new Date();
        const start = new Date();
        start.setDate(end.getDate() - 7);
        document.getElementById('startDate').value = start.toISOString().slice(0, 10);
        document.getElementById('endDate').value = end.toISOString().slice(0, 10);
        loadTopSold();
    });
    document.getElementById('last30Btn').addEventListener('click', function() {
        setDefaultDates();
        loadTopSold();
    });

    document.getElementById('exportCsv').addEventListener('click', function() {
        const rows = window._lastStats || [];
        if (!rows.length) {
            alert('Aucune donnée à exporter');
            return;
        }
        const header = ['product_id', 'product_name', 'total_qty', 'revenue'];
        const csv = [header.join(',')].concat(rows.map(r => [r.product_id, '"' + r.product_name.replace(/"/g, '""') + '"', r.total_qty || 0, r.revenue || 0].join(','))).join('\n');
        const blob = new Blob([csv], {
            type: 'text/csv;charset=utf-8;'
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'top_sold_' + document.getElementById('startDate').value + '_' + document.getElementById('endDate').value + '.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    });

    // Chargement initial
    loadTopSold();
    loadTimeSeries();
</script>

<?php include('./includes/footer.php'); ?>