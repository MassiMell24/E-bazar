<main>
  <div class="card">
    <h2>Gestion des utilisateurs</h2>

    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">ID</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Nom d'utilisateur</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Email</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Admin</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Créé</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($users)): foreach ($users as $u): ?>
          <tr>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo (int)$u['id']; ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo htmlspecialchars($u['username']); ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo htmlspecialchars($u['email']); ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo !empty($u['is_admin']) ? 'Oui' : 'Non'; ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo htmlspecialchars($u['created_at']); ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4">
              <?php if (empty($u['is_admin'])): ?>
              <form method="post" action="/e-bazar/index.php?url=admin/deleteUser/<?php echo (int)$u['id']; ?>" style="display:inline" onsubmit="showConfirm('⚠️ Supprimer l\'utilisateur « <?php echo htmlspecialchars($u['username']); ?> » ?\n\nCette action supprimera également TOUTES ses annonces.\nCette action est irréversible.', this); return false;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <button class="btn danger" type="submit">Supprimer</button>
              </form>
              <?php else: ?>
                <span class="muted">Admin non supprimable</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="6" style="padding:8px">Aucun utilisateur.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
