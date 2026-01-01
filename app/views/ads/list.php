<main>
	<div class="card">
		<h2>Catégories</h2>
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:12px 0">
			<?php if (!empty($categories)): foreach ($categories as $c): ?>
				<a href="/e-bazar/index.php?url=ad&category=<?php echo (int)$c['id']; ?>" style="text-decoration:none;color:inherit;border:1px solid #e5e7eb;border-radius:8px;padding:10px;display:flex;flex-direction:column;gap:4px;box-shadow:0 4px 10px rgba(15,23,42,0.06)">
					<strong><?php echo htmlspecialchars($c['name']); ?></strong>
					<span class="muted"><?php echo (int)($c['ads_count'] ?? 0); ?> biens en vente</span>
				</a>
			<?php endforeach; else: ?>
				<p>Aucune catégorie.</p>
			<?php endif; ?>
		</div>
	</div>

	<div class="card">
		<h2>Dernières mises en vente</h2>
		<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,220px));gap:12px;margin-top:12px">
			<?php if (!empty($latest)): foreach ($latest as $item): ?>
				<a href="/e-bazar/index.php?url=ad/show/<?php echo (int)$item['id']; ?>" style="text-decoration:none;color:inherit;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;box-shadow:0 6px 12px rgba(15,23,42,0.06);display:flex;flex-direction:column">
					<?php if (!empty($item['thumbnail'])): ?>
						<img src="<?php echo htmlspecialchars($item['thumbnail']); ?>" alt="vignette" style="width:100%;height:140px;object-fit:cover">
					<?php else: ?>
						<div style="width:100%;height:140px;display:flex;align-items:center;justify-content:center;background:#f8fafc;color:#94a3b8">Pas de photo</div>
					<?php endif; ?>
					<div style="padding:10px;display:flex;flex-direction:column;gap:6px">
						<strong><?php echo htmlspecialchars($item['title']); ?></strong>
						<span class="ad-price"><?php echo htmlspecialchars($item['price']); ?> €</span>
					</div>
				</a>
			<?php endforeach; else: ?>
				<p>Aucune annonce récente.</p>
			<?php endif; ?>
		</div>
	</div>

	<div class="card">
		<h2>Annonces</h2>
		<form method="get" action="/e-bazar/index.php" style="display:flex;gap:8px;align-items:center;margin-bottom:12px;flex-wrap:wrap">
				<input type="hidden" name="url" value="ad">
				<label style="font-size:14px">Catégorie:
						<select name="category">
								<option value="">Toutes</option>
								<?php if (!empty($categories) && is_array($categories)): foreach ($categories as $c): ?>
										<option value="<?php echo (int)$c['id']; ?>" <?php echo (!empty($selectedCategory) && (int)$selectedCategory === (int)$c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
								<?php endforeach; endif; ?>
						</select>
				</label>
				<label style="font-size:14px">Trier:
						<select name="sort">
								<option value="date_desc" <?php echo (($_GET['sort'] ?? '') === 'date_desc') ? 'selected' : ''; ?>>Nouveautés</option>
								<option value="price_asc" <?php echo (($_GET['sort'] ?? '') === 'price_asc') ? 'selected' : ''; ?>>Prix croissant</option>
								<option value="price_desc" <?php echo (($_GET['sort'] ?? '') === 'price_desc') ? 'selected' : ''; ?>>Prix décroissant</option>
						</select>
				</label>
				<button class="btn" type="submit">Filtrer</button>
		</form>

		<ul id="ads" class="ad-list">
		<?php foreach ($ads as $ad):
				$thumb = $ad['thumbnail'] ?? null;
				$snippet = mb_substr($ad['description'], 0, 120) . (mb_strlen($ad['description']) > 120 ? '…' : '');
		?>
				<li style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;">
						<a href="/e-bazar/index.php?url=ad/show/<?php echo (int)$ad['id']; ?>" style="display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;flex:1">
								<?php if ($thumb): ?>
										<img src="<?php echo htmlspecialchars($thumb); ?>" alt="vignette" style="width:100px;height:75px;object-fit:cover;border-radius:6px">
								<?php else: ?>
										<div style="width:100px;height:75px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px">Pas de photo</div>
								<?php endif; ?>
								<div style="display:flex;flex-direction:column;gap:4px;flex:1">
										<strong class="ad-title"><?php echo htmlspecialchars($ad['title']); ?></strong>
										<span class="ad-price"><?php echo htmlspecialchars($ad['price']); ?> €</span>
										<span class="muted" style="font-size:12px;line-height:1.4"><?php echo htmlspecialchars($snippet); ?></span>
								</div>
						</a>

						<div style="display:flex;gap:6px">
								<?php if (!empty($_SESSION['user_id']) && ($_SESSION['user_id'] == $ad['owner_id'])): ?>
										<a class="btn" href="/e-bazar/index.php?url=ad/edit/<?php echo (int)$ad['id']; ?>" style="background:#3182ce;color:#fff;padding:6px 8px;border-radius:6px;display:inline-block">Modifier</a>
								<?php endif; ?>

								<?php if (!empty($_SESSION['user_id']) && ($_SESSION['user_id'] == $ad['owner_id'] || !empty($_SESSION['is_admin']))): ?>
										<form method="post" action="/e-bazar/index.php?url=ad/delete/<?php echo (int)$ad['id']; ?>" style="margin:0" onsubmit="return confirm('Voulez-vous vraiment supprimer cette annonce ?');">
												<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
												<button type="submit" class="btn" style="background:#e53e3e;color:#fff;border:none;padding:6px 8px;border-radius:6px">Suppr.</button>
										</form>
								<?php endif; ?>
						</div>
				</li>
		<?php endforeach; ?>
		</ul>

		<?php if (!empty($totalPages) && $totalPages > 1): ?>
			<div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap">
				<?php for ($p = 1; $p <= $totalPages; $p++):
						$query = http_build_query([
								'url' => 'ad',
								'category' => $selectedCategory,
								'sort' => $_GET['sort'] ?? null,
								'page' => $p
						]);
				?>
					<a class="btn" href="/e-bazar/index.php?<?php echo htmlspecialchars($query); ?>" style="<?php echo ($p == ($currentPage ?? 1)) ? 'background:#0ea5e9;color:#fff' : ''; ?>">Page <?php echo $p; ?></a>
				<?php endfor; ?>
			</div>
		<?php endif; ?>
	</div>
</main>
