<main>
  <div class="card">
    <h2>Paramètres du compte</h2>
    
    <?php if ($message): ?>
      <div style="margin-bottom:12px;padding:10px;border-radius:6px;background:<?php echo strpos($message, 'succès') !== false || strpos($message, 'à jour') !== false ? 'rgba(34,197,94,0.1)' : 'rgba(239,68,68,0.1)'; ?>;color:<?php echo strpos($message, 'succès') !== false || strpos($message, 'à jour') !== false ? '#22c55e' : '#ef4444'; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/e-bazar/index.php?url=dashboard/settings" style="max-width:500px">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

      <div class="form-row">
        <label>Nom d'utilisateur</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
      </div>

      <div class="form-row">
        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
      </div>

      <div class="form-row">
        <label>Nouveau mot de passe <span style="color:var(--muted);font-weight:400">(laissez vide pour ne pas changer)</span></label>
        <input type="password" name="password" placeholder="Laissez vide pour conserver votre mot de passe">
      </div>

      <div class="form-row">
        <label>Confirmer le mot de passe</label>
        <input type="password" name="password_confirm" placeholder="Confirmez votre nouveau mot de passe">
      </div>

      <button class="btn" type="submit" style="margin-top:8px;margin-right:12px">Mettre à jour</button>
    </form>

    <form method="post" action="/e-bazar/index.php?url=dashboard/deleteAccount" style="display:inline;margin-top:8px" onsubmit="showConfirm('⚠️ ATTENTION : Supprimer définitivement votre compte ?\n\nCette action supprimera :\n• Votre compte\n• TOUTES vos annonces\n• Votre historique\n\nCette action est IRRÉVERSIBLE.', this); return false;">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
      <button class="btn danger" type="submit">Supprimer mon compte</button>
    </form>
  </div>
</main>

