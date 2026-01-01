<main>
  <div class="card">
    <h2>Mes annonces</h2>
    <?php if (empty($ads)): ?>
        <p class="muted">Aucune annonce pour le moment.</p>
    <?php else: ?>
        <ul class="ad-list">
        <?php foreach ($ads as $a): ?>
            <li style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;">
                <div style="display:flex;flex-direction:column;gap:4px;flex:1">
                    <a href="/e-bazar/index.php?url=ad/show/<?php echo (int)$a['id']; ?>" class="ad-title" style="text-decoration:none;color:inherit"><?php echo htmlspecialchars($a['title']); ?></a>
                    <span class="ad-price"><?php echo htmlspecialchars($a['price']); ?> €</span>
                    <span style="color:var(--muted);font-size:12px"><?php echo $a['sold'] ? '✓ Vendu' : '○ En vente'; ?></span>
                </div>
                <div style="display:flex;gap:8px;align-items:center">
                    <a href="/e-bazar/index.php?url=ad/edit/<?php echo (int)$a['id']; ?>" class="btn secondary" style="padding:6px 12px;font-size:12px;text-decoration:none">Éditer</a>
                    <form method="post" action="/e-bazar/index.php?url=ad/delete/<?php echo (int)$a['id']; ?>" style="margin:0" onsubmit="showConfirm('⚠️ Supprimer définitivement l\'annonce « <?php echo htmlspecialchars($a['title']); ?> » ?\n\nCette action est irréversible.', this); return false;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        <button type="submit" class="btn danger" style="padding:6px 12px;font-size:12px">Supprimer</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Articles vendus</h2>
    <?php if (empty($sold)): ?>
        <p class="muted">Vous n'avez pas d'articles vendus.</p>
    <?php else: ?>
        <ul class="ad-list">
        <?php foreach ($sold as $a): ?>
            <li style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;">
                <div style="display:flex;flex-direction:column;gap:4px;flex:1">
                    <span class="ad-title"><?php echo htmlspecialchars($a['title']); ?></span>
                    <span class="ad-price"><?php echo htmlspecialchars($a['price']); ?> €</span>
                    <span style="color:var(--muted);font-size:12px">Livraison: <?php echo htmlspecialchars($a['sold_delivery_mode'] ?? '-'); ?></span>
                </div>
                <form method="post" action="/e-bazar/index.php?url=dashboard/deleteSoldAd/<?php echo (int)$a['id']; ?>" style="margin:0" onsubmit="showConfirm('Supprimer cet article vendu de votre historique ?', this); return false;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <button type="submit" class="btn danger" style="padding:6px 12px;font-size:12px">Supprimer</button>
                </form>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Articles achetés</h2>
    <?php if (empty($purchased)): ?>
        <p class="muted">Vous n'avez pas d'articles achetés.</p>
    <?php else: ?>
        <ul class="ad-list">
        <?php foreach ($purchased as $a): ?>
            <li style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;">
                <div style="display:flex;flex-direction:column;gap:4px;flex:1">
                    <span class="ad-title"><?php echo htmlspecialchars($a['title']); ?></span>
                    <span class="ad-price"><?php echo htmlspecialchars($a['price']); ?> €</span>
                    <span style="color:var(--muted);font-size:12px">Livraison: <?php echo htmlspecialchars($a['sold_delivery_mode'] ?? '-'); ?></span>
                    <?php if (!empty($a['buyer_confirmed_reception'])): ?>
                        <span style="color:#2f855a;font-weight:bold;font-size:12px">✓ Réception confirmée</span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:8px">
                    <?php if (empty($a['buyer_confirmed_reception'])): ?>
                        <form method="post" action="/e-bazar/index.php?url=dashboard/confirmReceived/<?php echo (int)$a['id']; ?>" style="margin:0" onsubmit="showConfirm('Confirmer la réception de cet article ?', this); return false;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                            <button type="submit" class="btn" style="padding:6px 12px;font-size:12px;background:#2f855a;color:#fff">✓ Reçu</button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="/e-bazar/index.php?url=dashboard/deletePurchasedAd/<?php echo (int)$a['id']; ?>" style="margin:0" onsubmit="showConfirm('Supprimer cet article de votre historique ?', this); return false;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        <button type="submit" class="btn danger" style="padding:6px 12px;font-size:12px;background:#e53e3e;color:#fff">Supprimer</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
  </div>
</main>
