<main>
  <div class="card">
    <h2>Gestion des catégories</h2>
    
    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-error">
        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/e-bazar/index.php?url=admin/createCategory" style="display:flex;gap:8px;align-items:center;margin-bottom:16px">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
      <label>Nom
        <input type="text" name="name" required minlength="2" maxlength="100" placeholder="Nouvelle catégorie">
      </label>
      <button class="btn" type="submit">Ajouter</button>
    </form>

    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">ID</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Nom</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Annonces actives</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($categories)): foreach ($categories as $cat): ?>
          <tr>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo (int)$cat['id']; ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo htmlspecialchars($cat['name']); ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4">
              <?php if ($cat['ads_count'] > 0): ?>
                <span class="pill"><?php echo (int)$cat['ads_count']; ?> produit(s)</span>
              <?php else: ?>
                <span style="color:var(--muted)">—</span>
              <?php endif; ?>
            </td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4">
              <?php if ($cat['ads_count'] > 0): ?>
                <button class="btn btn-disabled" type="button" title="Cette catégorie contient <?php echo (int)$cat['ads_count']; ?> produit(s)" onclick="showWarning('⚠️ Impossible de supprimer cette catégorie !', 'Cette catégorie contient encore <?php echo (int)$cat['ads_count']; ?> produit(s). Veuillez d\'abord supprimer ou déplacer ces annonces.')">
                  Supprimer
                </button>
              <?php else: ?>
                <form method="post" action="/e-bazar/index.php?url=admin/deleteCategory/<?php echo (int)$cat['id']; ?>" style="display:inline" onsubmit="showConfirm('⚠️ Supprimer la catégorie « <?php echo htmlspecialchars($cat['name']); ?> » ?\n\nCette action est irréversible.', this); return false;">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                  <button class="btn" type="submit" style="background:#e53e3e;color:#fff">Supprimer</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="4" style="padding:8px">Aucune catégorie.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
