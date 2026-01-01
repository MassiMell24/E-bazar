<main>
  <div class="card">
    <h2>Transactions effectuées</h2>

    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">ID</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Produit</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Vendeur</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Acheteur</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Prix</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Livraison</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Date</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($transactions)): foreach ($transactions as $t): ?>
          <tr>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo (int)$t['id']; ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><a href="/e-bazar/index.php?url=ad/show/<?php echo (int)$t['id']; ?>" style="color:#2c3e50;text-decoration:none;font-weight:600;transition:color 0.2s" onmouseover="this.style.color='#3498db'" onmouseout="this.style.color='#2c3e50'"><?php echo htmlspecialchars($t['title']); ?></a></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo htmlspecialchars($t['seller_email'] ?? ''); ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo htmlspecialchars($t['buyer_email'] ?? ''); ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo htmlspecialchars($t['price']); ?> €</td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo htmlspecialchars($t['sold_delivery_mode'] ?? ''); ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4"><?php echo isset($t['sold_at']) ? htmlspecialchars(date('d/m/Y', strtotime($t['sold_at']))) : ''; ?></td>
            <td style="padding:8px;border-bottom:1px solid #f4f4f4">
              <form method="post" action="/e-bazar/index.php?url=admin/deleteTransaction/<?php echo (int)$t['id']; ?>" style="display:inline" onsubmit="showConfirm('⚠️ Supprimer la transaction « <?php echo htmlspecialchars($t['title']); ?> » ?\n\nCette action est irréversible.', this); return false;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <button class="btn" type="submit" style="background:#e53e3e;color:#fff">Supprimer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="8" style="padding:8px">Aucune transaction.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
