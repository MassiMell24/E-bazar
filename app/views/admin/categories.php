<main>
  <div class="card">
    <h2>Gestion des catégories</h2>

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
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($categories)): foreach ($categories as $cat): ?>
          <tr>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo (int)$cat['id']; ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo htmlspecialchars($cat['name']); ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4">
              <form method="post" action="/e-bazar/index.php?url=admin/deleteCategory/<?php echo (int)$cat['id']; ?>" style="display:inline" onsubmit="return confirm('Supprimer la catégorie \"<?php echo htmlspecialchars($cat['name']); ?>\" ?');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <button class="btn" type="submit" style="background:#e53e3e;color:#fff">Supprimer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="3" style="padding:8px">Aucune catégorie.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
