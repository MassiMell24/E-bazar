<main>
  <div class="card">
    <h2><?php echo htmlspecialchars($ad['title']); ?></h2>
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
            <img id="main-image" src="<?php echo htmlspecialchars($mainImg); ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>" style="width:100%;height:auto;border-radius:8px;object-fit:cover">
          <?php else: ?>
            <div style="width:100%;height:360px;display:flex;align-items:center;justify-content:center;color:#777;border-radius:8px;background:#fbfdff">Pas de photo</div>
          <?php endif; ?>
        </div>

        <?php if (!empty($images) && count($images) > 1): ?>
          <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
            <?php foreach ($images as $img): ?>
              <button type="button" class="thumb-btn" data-src="<?php echo htmlspecialchars($img['filename']); ?>" style="border:0;background:transparent;padding:0;">
                <img src="<?php echo htmlspecialchars($img['filename']); ?>" alt="mini" style="width:84px;height:64px;object-fit:cover;border-radius:6px;box-shadow:0 3px 10px rgba(15,23,42,0.06)">
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div style="flex:0 0 320px">
        <p class="ad-price"><?php echo htmlspecialchars($ad['price']); ?> €</p>
        <p><?php echo nl2br(htmlspecialchars($ad['description'])); ?></p>
        <?php 
          $modes = array_filter(array_map('trim', explode(',', $ad['delivery_modes'] ?? '')));
        ?>
        <div class="delivery-section">
          <strong>Modes de livraison:</strong>
          <?php if (!empty($modes)): ?>
            <ul class="delivery-modes">
              <?php foreach ($modes as $mode): ?>
                <li><?php echo htmlspecialchars($mode); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <div class="muted" style="font-size:13px">Non précisé</div>
          <?php endif; ?>
        </div>
        <?php
          $isOwner = !empty($_SESSION['user_id']) && ($_SESSION['user_id'] == $ad['owner_id']);
          $isAdmin = !empty($_SESSION['is_admin']);
          $isLoggedIn = !empty($_SESSION['user_id']);
          $isSold = !empty($ad['sold']);
          $canShowBuyButton = !$isOwner && !$isAdmin && !$isSold;
        ?>
        <?php if ($canShowBuyButton): ?>
        <div style="margin-top:12px">
          <?php if ($isLoggedIn): ?>
            <a class="btn" href="/e-bazar/index.php?url=ad/buy/<?php echo (int)$ad['id']; ?>">Acheter</a>
          <?php else: ?>
            <button type="button" class="btn" onclick="showLoginRequired()">Acheter</button>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['user_id'])): ?>
          <?php if (($_SESSION['user_id'] == $ad['owner_id']) || !empty($_SESSION['is_admin'])): ?>
            <div style="margin-top:12px">
              <form method="post" action="/e-bazar/index.php?url=ad/delete/<?php echo (int)$ad['id']; ?>" onsubmit="showConfirm('⚠️ Supprimer définitivement cette annonce ?\n\nCette action est irréversible.', this); return false;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <button type="submit" class="btn" style="background:#e53e3e;color:#fff;border:none;padding:8px 12px;border-radius:6px">Supprimer l'annonce</button>
              </form>
            </div>
          <?php endif; ?>

          <?php if (($_SESSION['user_id'] == $ad['owner_id'])): ?>
            <div style="margin-top:12px">
              <a class="btn" href="/e-bazar/index.php?url=ad/edit/<?php echo (int)$ad['id']; ?>" style="background:#3182ce;color:#fff;padding:8px 12px;border-radius:6px;display:inline-block;margin-top:8px">Modifier</a>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Modal de connexion requise -->
  <div id="auth-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;z-index:9999;align-items:center;justify-content:center">
    <div style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7)" onclick="closeAuthModal()"></div>
    <div style="position:relative;background:white;padding:30px;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.3);max-width:450px;width:90%;text-align:center;z-index:1">
      <button onclick="closeAuthModal()" style="position:absolute;top:15px;right:15px;background:none;border:none;font-size:28px;color:#999;cursor:pointer">&times;</button>
      <h2 style="color:#333;margin-bottom:15px;font-size:24px">🔒 Connexion requise</h2>
      <p style="color:#666;margin-bottom:25px;font-size:16px">Vous devez être connecté pour acheter cette annonce.</p>
      <div style="display:flex;gap:15px;justify-content:center">
        <a href="/e-bazar/index.php?url=auth/login" class="btn" style="flex:1;padding:12px 20px;text-decoration:none;border-radius:6px;background:#4CAF50;color:white">Se connecter</a>
        <a href="/e-bazar/index.php?url=auth/register" class="btn" style="flex:1;padding:12px 20px;text-decoration:none;border-radius:6px;background:#2196F3;color:white">S'inscrire</a>
      </div>
    </div>
  </div>

  <script>
    // Minimal thumbnail -> main image swap
    (function(){
      var buttons = document.querySelectorAll('.thumb-btn');
      var main = document.getElementById('main-image');
      if (!main || !buttons) return;
      buttons.forEach(function(b){
        b.addEventListener('click', function(){
          var src = b.getAttribute('data-src');
          if (src) {
            main.src = src;
          }
        });
      });
    })();

    // Modal functions
    function showLoginRequired() {
      var modal = document.getElementById('auth-modal');
      if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
      }
    }

    function closeAuthModal() {
      var modal = document.getElementById('auth-modal');
      if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
      }
    }

    // Close with ESC key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeAuthModal();
      }
    });
  </script>
</main>
