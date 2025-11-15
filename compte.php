<?php
include('./includes/header.php');
// Protéger la page (redirige vers index si pas connecté)
if (function_exists('protegerPage')) protegerPage();

// Récupère les informations utilisateur
require_once __DIR__ . '/db/connexion.php';
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$user = null;
if ($userId) {
    $stmt = $cnx->prepare('SELECT id, username, email, created_at FROM utilisateurs WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) $user = $res->fetch_assoc();
        $stmt->close();
    }
}
?>
<div class="container mt-4">
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="card p-3 mb-3">
                <h5>Mon profil</h5>
                <?php if (!$user): ?>
                    <?php
                    // debug info utile si l'utilisateur connecté n'existe plus en base
                    if (isset($_SESSION['user_id'])) {
                        error_log('compte.php: utilisateur introuvable en base pour user_id=' . intval($_SESSION['user_id']));
                    } else {
                        error_log('compte.php: pas de session user_id');
                    }
                    ?>
                    <div class="alert alert-danger">
                        Impossible de charger les informations utilisateur.<br>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            (session user_id = <?php echo intval($_SESSION['user_id']); ?>) <br>
                            Si vous voyez ce message alors que vous êtes connecté, votre compte a peut‑être été supprimé.
                        <?php else: ?>
                            Vous n'êtes pas connecté. Veuillez vous connecter depuis la page d'accueil.
                        <?php endif; ?>
                        <div class="mt-2"><a href="index.php" class="btn btn-sm btn-outline-primary">Aller à l'accueil</a>
                            <?php if (isset($_SESSION['user_id'])): ?> <a href="logout.php" class="btn btn-sm btn-outline-secondary ms-2">Se déconnecter</a><?php endif; ?></div>
                    </div>
                <?php else: ?>
                    <form id="profileForm">
                        <div class="mb-2">
                            <label class="form-label">Nom d'utilisateur</label>
                            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" id="saveProfile">Enregistrer</button>
                            <button type="button" class="btn btn-secondary" id="cancelProfile">Annuler</button>
                        </div>
                        <div id="profileMsg" class="mt-2"></div>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card p-3 mb-3">
                <h5>Changer le mot de passe</h5>
                <form id="passwordForm">
                    <div class="mb-2">
                        <label class="form-label">Mot de passe actuel</label>
                        <input type="password" id="currentPassword" name="currentPassword" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="password" id="newPassword" name="newPassword" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-warning" id="changePassword">Modifier le mot de passe</button>
                    </div>
                    <div id="passwordMsg" class="mt-2"></div>
                </form>
            </div>

            <div class="card p-3 mb-3">
                <h5>Supprimer le compte</h5>
                <p class="text-muted small">Cette action est irréversible. Toutes vos données seront supprimées.</p>
                <button id="deleteAccount" class="btn btn-danger">Supprimer mon compte</button>
                <div id="deleteMsg" class="mt-2"></div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card p-3 mb-3">
                <h5>Informations</h5>
                <?php if ($user): ?>
                    <p><strong>Inscrit le:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
                    <p><strong>ID:</strong> <?php echo (int)$user['id']; ?></p>
                    <?php
                    // Récupère statistiques commandes pour l'utilisateur
                    $ordersCount = 0;
                    $ordersTotal = 0;
                    $lastOrder = null;
                    $s = $cnx->prepare('SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS total, MAX(created_at) AS last_order FROM orders WHERE user_id = ?');
                    if ($s) {
                        $s->bind_param('i', $userId);
                        $s->execute();
                        $r = $s->get_result();
                        if ($r && $row = $r->fetch_assoc()) {
                            $ordersCount = (int)$row['cnt'];
                            $ordersTotal = (float)$row['total'];
                            $lastOrder = $row['last_order'];
                        }
                        $s->close();
                    }
                    ?>
                    <p><strong>Commandes passées:</strong> <?php echo $ordersCount; ?></p>
                    <p><strong>Total dépensé:</strong> <?php echo number_format($ordersTotal, 0, ',', ' '); ?> Ar</p>
                    <p><strong>Dernière commande:</strong> <?php echo $lastOrder ? htmlspecialchars($lastOrder) : 'Aucune'; ?></p>
                <?php else: ?>
                    <p class="text-muted">Pas d'information disponible.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Soumission du formulaire de profil
    document.getElementById('profileForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('saveProfile');
        btn.disabled = true;
        const data = new FormData(this);
        try {
            const res = await fetch('update_profile.php', {
                method: 'POST',
                body: data
            });
            const json = await res.json();
            const msg = document.getElementById('profileMsg');
            if (json.success) {
                msg.innerHTML = '<div class="alert alert-success">Profil mis à jour.</div>';
                const navName = document.querySelector('.navbar .text-muted');
                if (navName) navName.textContent = 'Connecté: ' + (document.getElementById('username').value);
            } else {
                msg.innerHTML = '<div class="alert alert-danger">' + (json.error || 'Erreur') + '</div>';
            }
        } catch (err) {
            document.getElementById('profileMsg').innerHTML = '<div class="alert alert-danger">Erreur réseau</div>';
        }
        btn.disabled = false;
    });

    // Changement de mot de passe
    document.getElementById('passwordForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('changePassword');
        btn.disabled = true;
        const data = new FormData(this);
        try {
            const res = await fetch('change_password.php', {
                method: 'POST',
                body: data
            });
            const json = await res.json();
            const msg = document.getElementById('passwordMsg');
            if (json.success) {
                msg.innerHTML = '<div class="alert alert-success">Mot de passe mis à jour.</div>';
                this.reset();
            } else {
                msg.innerHTML = '<div class="alert alert-danger">' + (json.error || 'Erreur') + '</div>';
            }
        } catch (err) {
            document.getElementById('passwordMsg').innerHTML = '<div class="alert alert-danger">Erreur réseau</div>';
        }
        btn.disabled = false;
    });

    // Suppression du compte
    document.getElementById('deleteAccount')?.addEventListener('click', function() {
        if (!confirm('Confirmez-vous la suppression de votre compte ? Cette action est irréversible.')) return;
        this.disabled = true;
        fetch('delete_account.php', {
                method: 'POST'
            })
            .then(r => r.json())
            .then(json => {
                const msg = document.getElementById('deleteMsg');
                if (json.success) {
                    msg.innerHTML = '<div class="alert alert-success">Compte supprimé. Redirection...</div>';
                    setTimeout(() => location.href = 'index.php', 1500);
                } else {
                    msg.innerHTML = '<div class="alert alert-danger">' + (json.error || 'Erreur') + '</div>';
                    document.getElementById('deleteAccount').disabled = false;
                }
            })
            .catch(err => {
                document.getElementById('deleteMsg').innerHTML = '<div class="alert alert-danger">Erreur réseau</div>';
                document.getElementById('deleteAccount').disabled = false;
            });
    });
</script>

<?php include('./includes/footer.php'); ?>