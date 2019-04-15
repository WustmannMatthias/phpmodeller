<aside>
	<ul class="list-group">
		<a href="index.php?home">
			<li class="list-group-item <?php if (isset($_GET['home'])
				|| $_SERVER['QUERY_STRING'] == "") echo 'active '; ?>">
				Home
			</li>
		</a>
		<a href="index.php?model_project">
			<li class="list-group-item <?php if (isset($_GET['model_project'])) echo 'active'; ?>">Model project</li>
		</a>
		</a>
		<a href="index.php?features">
			<li class="list-group-item <?php if (isset($_GET['features'])) echo 'active'; ?>">Features</li>
		</a>

	</ul>
</aside>
