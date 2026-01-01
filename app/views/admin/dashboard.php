<h2>Administration</h2>

<p>Nombre d'utilisateurs : <?php echo htmlspecialchars($usersCount); ?></p>
<p>Nombre d'annonces : <?php echo htmlspecialchars($adsCount); ?></p>

<nav style="margin-top:12px;display:flex;gap:10px">
	<a class="btn" href="/e-bazar/index.php?url=admin/categories">Gérer les catégories</a>
	<a class="btn" href="/e-bazar/index.php?url=admin/users">Gérer les utilisateurs</a>
	<a class="btn" href="/e-bazar/index.php?url=admin/ads">Gérer les annonces</a>
	<a class="btn" href="/e-bazar/index.php?url=admin/transactions">Voir les transactions</a>
</nav>
