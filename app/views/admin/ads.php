<main>
  <div class="card">
    <h2>Gestion des annonces</h2>

    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">ID</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Titre</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Prix</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Propriétaire</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Créée</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($ads)): foreach ($ads as $ad): ?>
          <tr>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo (int)$ad['id']; ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><a href="/e-bazar/index.php?url=ad/show/<?php echo (int)$ad['id']; ?>" style="color:#2c3e50;text-decoration:none;font-weight:600;transition:color 0.2s" onmouseover="this.style.color='#3498db'" onmouseout="this.style.color='#2c3e50'"><?php echo htmlspecialchars($ad['title']); ?></a></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo htmlspecialchars($ad['price']); ?> €</td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo htmlspecialchars($ad['owner_email'] ?? ''); ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo htmlspecialchars($ad['created_at']); ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4">
              <form method="post" action="/e-bazar/index.php?url=admin/deleteAd/<?php echo (int)$ad['id']; ?>" style="display:inline" onsubmit="showConfirm('⚠️ Supprimer l\'annonce « <?php echo htmlspecialchars($ad['title']); ?> » ?\n\nCette action est irréversible.', this); return false;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <button class="btn" type="submit" style="background:#e53e3e;color:#fff">Supprimer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="6" style="padding:8px">Aucune annonce.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
