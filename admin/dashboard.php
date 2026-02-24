<?php

declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';

auth_required();
require __DIR__ . '/../config/database.php';

$user = auth_user();
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->query('SELECT COUNT(*) FROM reservations');
$totalReservations = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($totalReservations / $perPage);

$limit  = (int) $perPage;
$offset = (int) $offset;
$stmt = $pdo->query("
  SELECT r.*, c.name AS car_name
  FROM reservations r
  JOIN cars c ON c.id = r.car_id
  ORDER BY r.created_at DESC
  LIMIT {$limit} OFFSET {$offset}
");
$reservations = $stmt->fetchAll();

$msgStmt = $pdo->query('SELECT * FROM messages ORDER BY created_at DESC LIMIT 50');
$messages = $msgStmt->fetchAll();

// Statistiques simples
$statusCounts = [
    'pending'   => 0,
    'confirmed' => 0,
    'canceled'  => 0,
];
$statusStmt = $pdo->query("SELECT status, COUNT(*) AS c FROM reservations GROUP BY status");
foreach ($statusStmt as $row) {
    $s = $row['status'] ?? '';
    if (isset($statusCounts[$s])) {
        $statusCounts[$s] = (int) $row['c'];
    }
}

$paymentCounts = [
    'online' => 0,
    'cash'   => 0,
];
$paymentStmt = $pdo->query("SELECT COALESCE(payment_method, 'cash') AS pm, COUNT(*) AS c FROM reservations GROUP BY pm");
foreach ($paymentStmt as $row) {
    $pm = $row['pm'] ?? 'cash';
    if (isset($paymentCounts[$pm])) {
        $paymentCounts[$pm] = (int) $row['c'];
    }
}

$revenueLabels = [];
$revenueValues = [];
$revenueStmt = $pdo->query("
  SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, SUM(total) AS total
  FROM reservations
  WHERE status = 'confirmed'
  GROUP BY ym
  ORDER BY ym ASC
  LIMIT 12
");
foreach ($revenueStmt as $row) {
    $revenueLabels[] = $row['ym'];
    $revenueValues[] = (float) $row['total'];
}
$totalRevenue = array_sum($revenueValues);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Tableau de bord</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .admin-header h1 { margin: 0; }
        .section { margin-bottom: 2rem; }
        .section h2 { margin-bottom: 1rem; font-size: 1.25rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.5rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background:rgb(0, 0, 0); }
        .badge { display: inline-block; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.85rem; }
        .badge-pending { background: #fff3cd; }
        .badge-confirmed { background: #d4edda; }
        .badge-canceled { background: #f8d7da; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.85rem; cursor: pointer; border: none; border-radius: 4px; }
        .btn-danger { background: #dc3545; color: #fff; }
        .pagination { margin-top: 1rem; }
        .pagination a, .pagination span { margin-right: 0.5rem; }
        .messages-table { font-size: 0.9rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { padding: 1rem; border-radius: 8px; border: 1px solid #2d333b; background: #161b22; }
        .stat-label { font-size: 0.85rem; color: #8b949e; }
        .stat-value { margin-top: 0.35rem; font-size: 1.4rem; font-weight: 600; }
        .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; }
        .chart-card { background: #161b22; border-radius: 8px; border: 1px solid #2d333b; padding: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-header">
            <h1>Tableau de bord</h1>
            <div>
                <span><?= htmlspecialchars($user['username']) ?></span>
                <a href="logout.php" class="btn btn-sm" style="margin-left:1rem">Déconnexion</a>
            </div>
        </div>

        <section class="section">
            <h2>Statistiques</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total réservations</div>
                    <div class="stat-value"><?= (int) $totalReservations ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Confirmées</div>
                    <div class="stat-value"><?= (int) $statusCounts['confirmed'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">En attente</div>
                    <div class="stat-value"><?= (int) $statusCounts['pending'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Annulées</div>
                    <div class="stat-value"><?= (int) $statusCounts['canceled'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Chiffre d'affaires (confirmées)</div>
                    <div class="stat-value"><?= number_format((float) $totalRevenue, 2, ',', ' ') ?> €</div>
                </div>
            </div>
            <div class="charts-grid">
                <div class="chart-card">
                    <canvas id="chart-status"></canvas>
                </div>
                <div class="chart-card">
                    <canvas id="chart-revenue"></canvas>
                </div>
            </div>
        </section>

        <section class="section">
            <h2>Réservations</h2>
            <div class="table-responsive">
                <table>
                    <thead class="thead-dark">
                        <tr class="text-white bg-dark">
                            <th class="text-white">ID</th>
                            <th class="text-white">Voiture</th>
                            <th class="text-white">Client</th>
                            <th class="text-white">Dates</th>
                            <th class="text-white">Jours</th>
                            <th class="text-white">Total</th>
                            <th class="text-white">Paiement</th>
                            <th>Statut</th>
                            <th class="text-white">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $r): ?>
                        <tr data-id="<?= (int) $r['id'] ?>">
                            <td><?= (int) $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['car_name']) ?></td>
                            <td><?= htmlspecialchars($r['full_name']) ?><br><small><?= htmlspecialchars($r['email']) ?></small></td>
                            <td><?= htmlspecialchars($r['start_date']) ?> → <?= htmlspecialchars($r['end_date']) ?></td>
                            <td><?= (int) $r['days'] ?></td>
                            <td><?= number_format((float) $r['total'], 2, ',', ' ') ?> €</td>
                            <td><?= isset($r['payment_method']) && $r['payment_method'] === 'cash' ? 'Espèces' : 'En ligne' ?></td>
                            <td>
                                <select class="status-select" data-id="<?= (int) $r['id'] ?>">
                                    <option value="pending"   <?= $r['status'] === 'pending'   ? 'selected' : '' ?>>En attente</option>
                                    <option value="confirmed" <?= $r['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmée</option>
                                    <option value="canceled"  <?= $r['status'] === 'canceled'  ? 'selected' : '' ?>>Annulée</option>
                                </select>
                            </td>
                            <td>
                                <button type="button" class="btn-sm btn-danger btn-delete" data-id="<?= (int) $r['id'] ?>">Supprimer</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>">← Précédent</a>
                <?php endif; ?>
                <span>Page <?= $page ?> / <?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>">Suivant →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </section>

        <section class="section">
            <h2>Messages contact</h2>
            <table class="messages-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Sujet</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['created_at']) ?></td>
                        <td><?= htmlspecialchars($m['full_name']) ?></td>
                        <td><?= htmlspecialchars($m['email']) ?></td>
                        <td><?= htmlspecialchars($m['subject']) ?></td>
                        <td><?= htmlspecialchars(mb_substr($m['message'], 0, 80)) ?><?= mb_strlen($m['message']) > 80 ? '…' : '' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <script>
        // Données stats depuis PHP
        const statsByStatus = <?= json_encode($statusCounts, JSON_UNESCAPED_UNICODE); ?>;
        const revenueLabels = <?= json_encode($revenueLabels, JSON_UNESCAPED_UNICODE); ?>;
        const revenueValues = <?= json_encode($revenueValues); ?>;

        // Graphique répartition statuts
        if (window.Chart && document.getElementById('chart-status')) {
            const ctxStatus = document.getElementById('chart-status').getContext('2d');
            new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: ['En attente', 'Confirmées', 'Annulées'],
                    datasets: [{
                        data: [
                            statsByStatus.pending || 0,
                            statsByStatus.confirmed || 0,
                            statsByStatus.canceled || 0
                        ],
                        backgroundColor: ['#f1c40f', '#27ae60', '#e74c3c']
                    }]
                },
                options: {
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        // Graphique CA mensuel
        if (window.Chart && document.getElementById('chart-revenue')) {
            const ctxRev = document.getElementById('chart-revenue').getContext('2d');
            new Chart(ctxRev, {
                type: 'line',
                data: {
                    labels: revenueLabels,
                    datasets: [{
                        label: 'CA confirmé (€)',
                        data: revenueValues,
                        borderColor: '#58a6ff',
                        backgroundColor: 'rgba(88,166,255,0.2)',
                        tension: 0.25
                    }]
                },
                options: {
                    scales: {
                        y: { beginAtZero: true }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        // Actions sur les réservations
        document.querySelectorAll('.status-select').forEach(function(sel) {
            sel.addEventListener('change', function() {
                var id = this.dataset.id;
                var status = this.value;
                var fd = new FormData();
                fd.append('action', 'update_status');
                fd.append('id', id);
                fd.append('status', status);
                fetch('actions.php', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        if (!res.ok) alert(res.error || 'Erreur');
                    });
            });
        });
        document.querySelectorAll('.btn-delete').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm('Supprimer cette réservation ?')) return;
                var id = this.dataset.id;
                var fd = new FormData();
                fd.append('action', 'delete_reservation');
                fd.append('id', id);
                fetch('actions.php', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        if (res.ok) btn.closest('tr').remove();
                        else alert(res.error || 'Erreur');
                    });
            });
        });
    </script>
</body>
</html>
