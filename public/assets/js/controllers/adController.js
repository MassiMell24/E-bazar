import { fetchAds } from '../models/adModel.js';
import { renderAds } from '../views/adView.js';

// Only attempt to render ads if the target container exists on the page.
fetchAds().then((ads) => {
	const ul = document.getElementById('ads');
	if (!ul) {
		console.debug('adController: #ads not present, skipping render');
		return;
	}

	// Respect server-side rendered lists — only client-render if explicitly requested
	// Add `data-client="1"` to the <ul id="ads"> when you want client-side rendering.
	if (ul.dataset.client === '1') {
		renderAds(ads);
	} else {
		console.debug('adController: #ads present but not marked for client render; skipping');
	}
}).catch(err => {
	console.error('Failed to fetch ads', err);
});
