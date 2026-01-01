<main>
  <div class="card">
    <h2>Articles achetés</h2>
    <?php if (empty($purchased)): ?>
      <p>Vous n'avez acheté aucun article pour le moment.</p>
    <?php else: ?>
      <ul class="ad-list">
      <?php foreach ($purchased as $p): ?>
        <li>
          <a href="/e-bazar/index.php?url=ad/show/<?php echo (int)$p['id']; ?>"><strong><?php echo htmlspecialchars($p['title']); ?></strong></a>
          <span style="margin-left:8px;color:var(--muted)">Acheté le <?php echo htmlspecialchars($p['sold_at']); ?></span>
          <?php if (!empty($p['sold_delivery_mode'])): ?>
            <span style="margin-left:8px;color:var(--muted)">Livraison : <?php echo htmlspecialchars($p['sold_delivery_mode']); ?></span>
          <?php endif; ?>
          <?php if (!empty($p['buyer_confirmed_reception'])): ?>
            <span style="margin-left:8px;color:#2f855a;font-weight:bold">✓ Produit reçu</span>
          <?php endif; ?>
          <div style="float:right"><?php echo htmlspecialchars($p['price']); ?>€</div>
          <div style="margin-top:6px">
            <?php if (empty($p['buyer_confirmed_reception'])): ?>
              <form method="post" action="/e-bazar/index.php?url=dashboard/confirmReceived/<?php echo (int)$p['id']; ?>" onsubmit="return confirm('Confirmer la réception du produit ?');" style="display:inline;margin-right:8px">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <button class="btn" type="submit" style="background:#2f855a;color:#fff;border:none;padding:6px 8px;border-radius:6px">Confirmer la réception</button>
              </form>
            <?php endif; ?>
            <form method="post" action="/e-bazar/index.php?url=dashboard/deletePurchasedAd/<?php echo (int)$p['id']; ?>" onsubmit="return confirm('Supprimer définitivement cet article de votre historique ?');" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
              <button class="btn" type="submit" style="background:#e53e3e;color:#fff;border:none;padding:6px 8px;border-radius:6px">Supprimer</button>
            </form>
          </div>
        </li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</main>
