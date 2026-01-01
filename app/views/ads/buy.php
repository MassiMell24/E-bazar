<main>
  <div class="card">
    <h2>Confirmer l'achat</h2>
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:12px">
      <div style="flex:1 1 360px;max-width:720px">
        <?php
          $mainImg = null;
          if (!empty($images) && is_array($images)) {
            $mainImg = $images[0]['filename'];
          }
        ?>
        <div style="background:#fff;border-radius:10px;padding:12px;box-shadow:0 6px 18px rgba(15,23,42,0.06)">
          <?php if ($mainImg): ?>
            <img src="<?php echo htmlspecialchars($mainImg); ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>" style="width:100%;height:auto;border-radius:8px;object-fit:cover">
          <?php else: ?>
            <div style="width:100%;height:360px;display:flex;align-items:center;justify-content:center;color:#777;border-radius:8px;background:#fbfdff">Pas de photo</div>
          <?php endif; ?>
        </div>
      </div>

      <div style="flex:0 0 320px">
        <h3 style="margin-top:0"><?php echo htmlspecialchars($ad['title']); ?></h3>
        <p class="ad-price"><?php echo htmlspecialchars($ad['price']); ?> €</p>
        <p><?php echo nl2br(htmlspecialchars($ad['description'])); ?></p>

        <?php if (!empty($error)): ?>
          <div class="error" style="margin-bottom:16px;padding:12px;background:#fee;border:1px solid #fcc;border-radius:6px;color:#c00">
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <form method="POST" style="margin-top:20px">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          
          <div style="margin-bottom:16px">
            <label style="display:block;font-weight:600;margin-bottom:8px">Choisir un mode de livraison:</label>
            <?php if (!empty($acceptedModes)): ?>
              <?php foreach ($acceptedModes as $mode): ?>
                <label style="display:block;margin-bottom:6px">
                  <input type="radio" name="delivery_mode" value="<?php echo htmlspecialchars($mode); ?>" required>
                  <?php echo htmlspecialchars($mode); ?>
                </label>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="muted" style="font-size:13px">Aucun mode de livraison disponible</div>
            <?php endif; ?>
          </div>

          <div style="display:flex;gap:10px;margin-top:20px">
            <button type="submit" class="btn btn-primary" <?php echo empty($acceptedModes) ? 'disabled' : ''; ?>>
              Confirmer l'achat
            </button>
            <a href="/e-bazar/index.php?url=ad/show/<?php echo $ad['id']; ?>" class="btn btn-secondary">
              Annuler
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>
