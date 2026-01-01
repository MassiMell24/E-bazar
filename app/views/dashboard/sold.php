<main>
  <div class="card">
    <h2>Articles vendus</h2>
    <?php if (empty($sold)): ?>
      <p>Vous n'avez vendu aucun article pour le moment.</p>
    <?php else: ?>
      <ul class="ad-list">
      <?php foreach ($sold as $s): ?>
        <li>
          <strong><?php echo htmlspecialchars($s['title']); ?></strong>
          <span style="margin-left:8px;color:var(--muted)">Vendu le <?php echo htmlspecialchars($s['sold_at']); ?></span>
          <?php if (!empty($s['sold_delivery_mode'])): ?>
            <span style="margin-left:8px;color:var(--muted)">Livraison : <?php echo htmlspecialchars($s['sold_delivery_mode']); ?></span>
          <?php endif; ?>
          <div style="float:right"><?php echo htmlspecialchars($s['price']); ?>€</div>
          <div style="margin-top:6px">
            <form method="post" action="/e-bazar/index.php?url=dashboard/deleteSoldAd/<?php echo (int)$s['id']; ?>" onsubmit="showConfirm('Retirer cet article de votre liste de vendus ?', this); return false;" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
              <button class="btn" type="submit" style="background:#e53e3e;color:#fff;border:none;padding:6px 8px;border-radius:6px">Retirer de ma liste</button>
            </form>
          </div>
        </li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</main>
