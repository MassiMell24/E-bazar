export async function fetchAds() {
    const res = await fetch('index.php?url=api/ads');
    return res.json();
}
