export function renderAds(ads) {
    try {
        const ul = document.getElementById('ads');

        // If the container isn't present yet, decide how to proceed
        if (!ul) {
            // If DOM still loading, wait for it
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function onReady() {
                    document.removeEventListener('DOMContentLoaded', onReady);
                    renderAds(ads);
                });
                return;
            }

            // DOM is ready but element missing (wrong page) — don't throw in promise contexts
            console.warn('renderAds: #ads element not found in DOM');
            return;
        }

        ul.innerHTML = '';

        ads.forEach(ad => {
            const li = document.createElement('li');
            li.textContent = ad.title + ' - ' + ad.price + '€';
            ul.appendChild(li);
        });
    } catch (err) {
        // Prevent uncaught exceptions inside promise chains
        console.error('renderAds error', err);
    }
}
