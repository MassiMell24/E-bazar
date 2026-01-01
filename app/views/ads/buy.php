<main>
  <div class="card">
    <h2>Confirmation d'achat</h2>
    <p>Vous êtes sur le point d'acheter l'annonce : <strong><?php echo htmlspecialchars($ad['title']); ?></strong></p>
    <p>Prix : <strong><?php echo htmlspecialchars($ad['price']); ?> €</strong></p>
    <?php if (!empty($images)): ?>
      <div style="display:flex;gap:8px;margin:8px 0;flex-wrap:wrap">
        <?php foreach ($images as $img): ?>
          <img src="<?php echo htmlspecialchars($img['filename']); ?>" style="width:120px;height:90px;object-fit:cover;border-radius:6px">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <p style="color:#e53e3e"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post" action="/e-bazar/index.php?url=ad/buy/<?php echo (int)$ad['id']; ?>" onsubmit="return confirm('Confirmer l\'achat de cet article ?');">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
      <div class="form-row">
        <label>Choisissez un mode de livraison</label>
        <?php
          $modes = array_filter(array_map('trim', explode(',', $ad['delivery_modes'] ?? '')));
        ?>
        <?php if (!empty($modes)): ?>
          <?php foreach ($modes as $m): ?>
            <label style="display:block;margin:4px 0">
              <input type="radio" name="delivery_mode" value="<?php echo htmlspecialchars($m); ?>" required> <?php echo htmlspecialchars($m); ?>
            </label>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="muted">Aucun mode de livraison indiqué.</p>
        <?php endif; ?>
      </div>
      <p>Confirmez-vous l'achat ?</p>
      <button class="btn" type="submit" style="background:#2f855a;color:#fff;padding:8px 12px;border-radius:6px">Confirmer l'achat</button>
      <a class="btn" href="/e-bazar/index.php?url=ad/show/<?php echo (int)$ad['id']; ?>">Annuler</a>
    </form>
  </div>
</main>
