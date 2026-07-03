<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero mb-4">
  <div class="hero-eyebrow">Pickme-style vendor discovery</div>
  <h1>Find vegetables, fish, and bread vendors around you.</h1>
  <p class="lead">Enable your location, pick a nearby vendor, order available stock, and watch inventory update instantly.</p>
  <button id="useLocationBtn" class="btn btn-brand me-2">📍 Use my location</button>
  <button id="refreshBtn" class="btn btn-outline-light">🔄 Refresh vendors</button>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card p-3 mb-3">
      <h6>Search radius: <span id="radiusLabel">5</span> km</h6>
      <input type="range" min="1" max="10" value="5" class="form-range" id="radiusSlider">
    </div>

    <div class="card p-3 mb-3">
      <h6 class="mb-2">🔍 Vendor filter</h6>
      <div class="d-grid gap-2">
        <button class="btn tag-chip active cat-btn" data-cat="all">🏪 All</button>
        <button class="btn tag-chip cat-btn" data-cat="vegetables">🥬 Vegetables</button>
        <button class="btn tag-chip cat-btn" data-cat="fish">🐟 Fish</button>
        <button class="btn tag-chip cat-btn" data-cat="bread">🍞 Bread</button>
      </div>
    </div>

    <div class="counter-tag mb-3">
      <span class="num" id="vendorCountNum">0</span>
      <span class="label" id="vendorCountLabel">vendors within 5 km</span>
    </div>

    <div class="card p-3">
      <h6>Nearby shops</h6>
      <div id="vendorList" class="list-group mt-2"></div>
    </div>
  </div>

  <div class="col-lg-8">
    <div id="map"></div>
  </div>
</div>

<style>
.cat-btn { width: 100%; }
</style>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let map, userMarker, userCircle, vendorMarkers = [];
let userLat = null, userLng = null;
let currentCategory = 'all';
let currentRadius = 5;

// Colombo default center
const defaultLoc = [6.9271, 79.8612];

function initMap() {
    map = L.map('map').setView(defaultLoc, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
}
initMap();

function useMyLocation() {
    if (!navigator.geolocation) { alert('Geolocation not supported on this browser'); return; }
    navigator.geolocation.getCurrentPosition(function(pos) {
        userLat = pos.coords.latitude;
        userLng = pos.coords.longitude;
        placeUserMarker();
        loadVendors();
    }, function() {
        alert('Could not get your location. Please allow location access.');
    });
}

function placeUserMarker() {
    map.setView([userLat, userLng], 15);
    if (userMarker) map.removeLayer(userMarker);
    if (userCircle) map.removeLayer(userCircle);

    const youIcon = L.divIcon({
        className: '',
        html: `
            <div style="position:relative; width:46px; height:60px;">
                <div style="position:absolute; top:0; left:50%; transform:translateX(-50%);
                            width:20px; height:20px; border-radius:50%;
                            background:#EC8B34; border:3px solid #fff;
                            box-shadow:0 2px 8px rgba(0,0,0,0.4); z-index:2;"></div>
                <div style="position:absolute; top:9px; left:50%; transform:translateX(-50%);
                            width:44px; height:44px; border-radius:50%;
                            background:rgba(236,139,52,0.35);
                            animation: pulseRing 1.6s ease-out infinite; z-index:1;"></div>
                <div style="position:absolute; top:-24px; left:50%; transform:translateX(-50%);
                            background:#1F4D2E; color:#fff; padding:3px 10px; border-radius:10px;
                            font-family:'IBM Plex Mono', monospace; font-size:11px; font-weight:600;
                            white-space:nowrap; box-shadow:0 2px 6px rgba(0,0,0,0.3); z-index:3;">📍 You are here</div>
            </div>
            <style>
                @keyframes pulseRing {
                    0%   { transform: translateX(-50%) scale(0.6); opacity: 0.9; }
                    100% { transform: translateX(-50%) scale(1.8); opacity: 0; }
                }
            </style>
        `,
        iconSize: [46, 60],
        iconAnchor: [23, 20]
    });

    userMarker = L.marker([userLat, userLng], { icon: youIcon, zIndexOffset: 1000 }).addTo(map);
    userCircle = L.circle([userLat, userLng], {
        radius: currentRadius * 1000,
        color: '#1F4D2E',
        weight: 1,
        fillColor: '#EC8B34',
        fillOpacity: 0.10
    }).addTo(map);
}

function clearVendorMarkers() {
    vendorMarkers.forEach(m => map.removeLayer(m));
    vendorMarkers = [];
}

function loadVendors() {
    if (userLat === null) { return; }
    fetch(`api/get_nearby_vendors.php?lat=${userLat}&lng=${userLng}&radius=${currentRadius}&category=${currentCategory}`)
        .then(r => r.json())
        .then(data => {
            clearVendorMarkers();
            const list = document.getElementById('vendorList');
            list.innerHTML = '';
            document.getElementById('vendorCountNum').innerText = data.count ?? 0;
            document.getElementById('vendorCountLabel').innerText = 'vendors within ' + currentRadius + ' km';

            if (!data.success || data.count === 0) {
                list.innerHTML = '<p class="text-muted small">No vendors found nearby. Try increasing radius.</p>';
                return;
            }

            data.vendors.forEach(v => {
                const marker = L.marker([parseFloat(v.latitude), parseFloat(v.longitude)]).addTo(map);
                marker.bindPopup(`<b>${v.shop_name}</b><br>${v.category}<br>${v.distance_km} km away<br>${v.product_count} products`);
                vendorMarkers.push(marker);

                const item = document.createElement('a');
                item.href = `customer/vendor_view.php?id=${v.id}`;
                item.className = 'list-group-item list-group-item-action';
                item.innerHTML = `<div class="d-flex justify-content-between">
                        <div><b>${v.shop_name}</b><br><small class="text-muted">${v.category} • ${v.product_count} products</small></div>
                        <span class="badge-pill align-self-center">${v.distance_km} km</span>
                    </div>`;
                list.appendChild(item);
            });
        });
}

document.getElementById('useLocationBtn').addEventListener('click', useMyLocation);
document.getElementById('refreshBtn').addEventListener('click', loadVendors);
document.getElementById('radiusSlider').addEventListener('input', function() {
    currentRadius = this.value;
    document.getElementById('radiusLabel').innerText = currentRadius;
    if (userLat !== null) { placeUserMarker(); loadVendors(); }
});
document.querySelectorAll('.cat-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentCategory = this.dataset.cat;
        loadVendors();
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>