<main>
  <div class="card">
    <h2>Déposer une annonce</h2>

    <?php if (!empty($error)): ?>
        <p style="color:red"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post" action="/e-bazar/index.php?url=ad/store" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        <div class="form-row">
            <label>Titre</label>
            <input type="text" name="title" required minlength="5" maxlength="30">
        </div>
        <div class="form-row">
            <label>Catégorie</label>
            <select name="category_id" required>
                <option value="">-- Choisir une catégorie --</option>
                <?php if (!empty($categories) && is_array($categories)): foreach ($categories as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                <?php endforeach; endif; ?>
            </select>
        </div>
        <div class="form-row">
            <label>Description</label>
            <textarea name="description" required minlength="5" maxlength="200"></textarea>
        </div>
        <div class="form-row">
            <label>Prix (€)</label>
            <input type="number" step="0.01" name="price" value="0">
        </div>
        <div class="form-row">
            <label>Modes de livraison</label>
            <div><label><input type="checkbox" name="delivery[]" value="postal"> Postal</label></div>
            <div><label><input type="checkbox" name="delivery[]" value="hand"> Remise en main propre</label></div>
        </div>
        <div class="form-row">
            <label>Photos (JPEG, max 5, 200 KiB chacune). La 1ère sera la vignette.</label>
            <input id="photos-input" type="file" name="photos[]" accept="image/jpeg" multiple>
            <small class="muted">Laisser vide si vous ne souhaitez pas ajouter de photos.</small>
            <div id="photo-previews" style="display:flex;gap:8px;margin-top:8px"></div>
            <div id="upload-errors" style="color:#b00020;margin-top:8px"></div>
        </div>
        <div class="form-row">
            <button class="btn" type="submit">Publier</button>
        </div>
    </form>
        <?php
        $jsPath = __DIR__ . '/../../../public/assets/js/adUpload.js';
        $jsVer = file_exists($jsPath) ? filemtime($jsPath) : time();
        ?>
        <script src="/e-bazar/public/assets/js/adUpload.js?v=<?php echo $jsVer; ?>"></script>
  </div>
</main>
