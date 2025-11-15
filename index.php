<?php
session_start();
include("./includes/header.php");
?>
<div class="container mt-4" style="position:relative;">
    <!-- Ligne avec hauteur minimale pour permettre l'alignement top/bottom des images -->
    <div class="row" style="min-height:400px;">
        <!-- Formulaire de login à l'extrême gauche -->
        <div class="col-12 col-md-3 d-flex justify-content-center align-items-center">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Affichage si l'utilisateur est connecté -->
                <div class="text-center">
                    <h5>Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?> !</h5>
                    <p class="text-muted">Vous êtes authentifié(e).</p>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Déconnexion</a>
                </div>
            <?php else: ?>
                <!-- Formulaire de login si pas connecté -->
                <form id="loginForm" class="p-4" style="width:100%; max-width:340px; background-color:#f9f9f9; box-shadow:0 0 16px 2px #bbb; border:none; border-radius:18px;">
                    <h4 class="mb-3 text-center">Connexion</h4>
                    <div class="mb-2">
                        <label class="form-label">Identifiant</label>
                        <input type="text" id="username" name="username" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" id="password" name="password" class="form-control form-control-sm" required>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" value="1" id="rememberMe" name="remember">
                        <label class="form-check-label small" for="rememberMe">Se souvenir de moi</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">Connexion</button>
                    <div id="loginError" class="text-danger small mt-2" style="display:none;"></div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <a href="#" id="openRegister" class="small">Créer un compte</a>
                        <a href="#" class="small text-muted">Mot de passe oublié ?</a>
                    </div>
                </form>
            <?php endif; ?>

            <!-- Modal de création de compte -->
            <div id="registerModal" style="display:none; position:fixed; left:0; top:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:1050; align-items:center; justify-content:center;">
                <div style="background:#fff; max-width:400px; width:100%; margin:auto; padding:30px 24px; border-radius:18px; box-shadow:0 0 24px 4px #bbb; position:relative;">
                    <h4 class="mb-3 text-center">Créer un compte</h4>
                    <form id="registerForm">
                        <div class="mb-2">
                            <label class="form-label">Nom d'utilisateur</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Adresse e-mail</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Confirmer le mot de passe</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Créer le compte</button>
                        <div id="registerError" class="text-danger small mt-2" style="display:none;"></div>
                    </form>
                    <button type="button" id="closeRegisterModal" class="btn btn-link w-100 mt-2">Annuler</button>
                </div>
            </div>
        </div>

        <!-- Titre central -->
        <div class="col-12 col-md-6 d-flex justify-content-center align-items-center">
            <h2 class="my-4">Bienvenue dans l'application web "Epicerie".</h2>
        </div>

        <!-- Colonne vide à droite (pour l'espace) -->
        <div class="col-12 col-md-3"></div>
    </div>

    <!-- Image fusionnée avec le fond à l'extrême droite -->
    <img src="uploads/ChatGPT Image 11 nov. 2025, 16_42_52.png" alt="Image droite" class="img-fluid" style="position:absolute; right:0; top:0; max-height:500px; border:0; box-shadow:none; opacity:0.8; mix-blend-mode:multiply;">
</div>

<script>
    // Gestion du formulaire de login
    document.getElementById('loginForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        var username = document.getElementById('username').value;
        var password = document.getElementById('password').value;
        var errorDiv = document.getElementById('loginError');

        // récupère l'option "se souvenir"
        var remember = document.getElementById('rememberMe') ? document.getElementById('rememberMe').checked : false;

        fetch('auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password) + '&remember=' + (remember ? '1' : '0')
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    errorDiv.style.display = 'block';
                    errorDiv.innerText = data.error || 'Erreur de connexion';
                }
            })
            .catch(err => {
                errorDiv.style.display = 'block';
                errorDiv.innerText = 'Erreur réseau';
                console.error(err);
            });
    });

    // Ouvre le modal de création de compte
    document.getElementById('openRegister')?.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('registerModal').style.display = 'flex';
    });

    // Ferme le modal de création de compte
    document.getElementById('closeRegisterModal')?.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('registerModal').style.display = 'none';
    });

    // Gestion du formulaire de création de compte
    document.getElementById('registerForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = e.target;
        var errorDiv = document.getElementById('registerError');
        var fd = new FormData(form);
        errorDiv.style.display = 'none';
        if (fd.get('password') !== fd.get('confirm_password')) {
            errorDiv.style.display = 'block';
            errorDiv.innerText = 'Les mots de passe ne correspondent pas.';
            return;
        }
        fetch('register.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('registerModal').style.display = 'none';
                    window.location.reload();
                } else {
                    errorDiv.style.display = 'block';
                    errorDiv.innerText = data.error || 'Erreur lors de la création du compte.';
                }
            })
            .catch(err => {
                errorDiv.style.display = 'block';
                errorDiv.innerText = 'Erreur réseau';
            });
    });

    // Prépare le bouton "Mot de passe oublié ?" (à compléter)
    document.querySelector('a.text-muted')?.addEventListener('click', function(e) {
        e.preventDefault();
        alert('Fonctionnalité à venir : récupération du mot de passe.');
    });
</script>

<p class="footer">Copyright by <b> Sariaka RAZAFIMAHEFA</b></p>
<?php include("./includes/footer.php"); ?>