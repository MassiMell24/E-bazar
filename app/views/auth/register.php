<main>
  <div class="card">
    <h2>Inscription</h2>

    <?php if (!empty($error)): ?>
        <p style="color:red"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post" action="/e-bazar/index.php?url=auth/register" onsubmit="showConfirm('✨ Créer votre compte ?\n\nVous allez créer un nouveau compte sur E-Bazar.\nVous pourrez ensuite acheter et vendre des articles.', this); return false;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        <div class="form-row">
            <label>Nom d'utilisateur (optionnel)</label>
            <input type="text" name="username" placeholder="Votre nom d'utilisateur">
        </div>
        <div class="form-row">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-row">
            <label>Mot de passe</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-row">
            <label>Confirmer le mot de passe</label>
            <input type="password" name="password_confirm" required>
        </div>
        <div class="form-row">
            <button class="btn" type="submit">S'inscrire</button>
        </div>
    </form>
  </div>
</main>
