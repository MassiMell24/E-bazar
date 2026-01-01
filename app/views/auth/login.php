<main>
  <div class="card">
    <h2>Connexion</h2>

    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <p style="color:red"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post" action="/e-bazar/index.php?url=auth/login">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        <div class="form-row">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-row">
            <label>Mot de passe</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-row">
            <button class="btn" type="submit">Se connecter</button>
        </div>
    </form>
  </div>
</main>
