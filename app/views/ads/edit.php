<main>
  <div class="card">
    <h2>Modifier l'annonce</h2>

    <?php if (!empty($error)): ?>
        <p style="color:red"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post" action="/e-bazar/index.php?url=ad/update/<?php echo (int)$ad['id']; ?>" enctype="multipart/form-data" onsubmit="showConfirm('Êtes-vous sûr de vouloir enregistrer les modifications ?', this); return false;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        <div class="form-row">
            <label>Titre</label>
            <input type="text" name="title" required minlength="5" maxlength="30" value="<?php echo htmlspecialchars($ad['title']); ?>">
        </div>
        <div class="form-row">
            <label>Catégorie</label>
            <select name="category_id" required>
                <option value="">-- Choisir une catégorie --</option>
                <?php if (!empty($categories) && is_array($categories)): foreach ($categories as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>" <?php echo ($ad['category_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                <?php endforeach; endif; ?>
            </select>
        </div>
        <div class="form-row">
            <label>Description</label>
            <textarea name="description" required minlength="5" maxlength="200"><?php echo htmlspecialchars($ad['description']); ?></textarea>
        </div>
        <div class="form-row">
            <label>Prix (€)</label>
            <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($ad['price']); ?>">
        </div>
        <div class="form-row">
            <label>Modes de livraison</label>
            <?php $del = explode(',', $ad['delivery_modes'] ?? ''); ?>
            <div><label><input type="checkbox" name="delivery[]" value="postal" <?php echo in_array('postal', $del) ? 'checked' : ''; ?>> Postal</label></div>
            <div><label><input type="checkbox" name="delivery[]" value="hand" <?php echo in_array('hand', $del) ? 'checked' : ''; ?>> Remise en main propre</label></div>
        </div>

        <?php if (!empty($images) && is_array($images)): ?>
        <div class="form-row">
            <label>Photos actuelles (cocher pour supprimer)</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <?php foreach ($images as $img): $base = basename($img['filename']); ?>
                    <label style="display:flex;flex-direction:column;align-items:center;font-size:12px">
                        <img src="<?php echo htmlspecialchars($img['filename']); ?>" style="width:120px;height:90px;object-fit:cover;border-radius:6px">
                        <input type="checkbox" name="remove_images[]" value="<?php echo htmlspecialchars($base); ?>"> Supprimer
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-row">
            <label>Ajouter des photos (JPEG, max 5, 200 KiB chacune). La 1ère sera la vignette si ajoutée.</label>
            <input id="photos-input" type="file" name="photos[]" accept="image/jpeg" multiple>
            <small class="muted">Laisser vide si vous ne souhaitez pas ajouter de photos.</small>
            <div id="upload-errors" style="color:#b00020;margin-top:8px"></div>
        </div>

        <div class="form-row">
            <button class="btn" type="submit">Enregistrer</button>
            <a class="btn" href="/e-bazar/index.php?url=ad/show/<?php echo (int)$ad['id']; ?>">Annuler</a>
        </div>
    </form>
        <?php
        $jsPath = __DIR__ . '/../../../public/assets/js/adUpload.js';
        $jsVer = file_exists($jsPath) ? filemtime($jsPath) : time();
        ?>
        <script src="/e-bazar/public/assets/js/adUpload.js?v=<?php echo $jsVer; ?>"></script>
        <script>
            (function(){
                const form = document.querySelector('form[action*="ad/update/"]');
                if (!form) return;

                // When a checkbox is checked to remove image, visually mark it
                const checkboxes = form.querySelectorAll('input[type="checkbox"][name="remove_images[]"]');
                checkboxes.forEach(function(cb){
                    cb.addEventListener('change', function(e){
                        if (cb.checked) {
                            // Visually hide the image container
                            const label = cb.closest('label');
                            if (label) {
                                label.style.opacity = '0.3';
                                label.style.textDecoration = 'line-through';
                            }
                        } else {
                            // Restore visual state
                            const label = cb.closest('label');
                            if (label) {
                                label.style.opacity = '1';
                                label.style.textDecoration = 'none';
                            }
                        }
                        } else {
                            // Restore if unchecked
                            const label = cb.closest('label');
                            if (label) {
                                label.style.opacity = '1';
                                label.style.textDecoration = 'none';
                            }
                        }
                    });
                });
            })();
        </script>
  </div>
</main>
