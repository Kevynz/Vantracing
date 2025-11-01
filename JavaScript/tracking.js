/*
 * VanTracing - Real-time Tracking (Leaflet + LocalStorage demo)
 * Rastreamento em tempo real (demo) usando Leaflet + LocalStorage
 *
 * This implementation creates a working map without API keys.
 * It reads the driver's last known position from localStorage key 'driverPosition'
 * and shows it on a Leaflet map. If no position is available, it centers
 * on a default coordinate and simulates movement for demo purposes.
 *
 * Esta implementação cria um mapa funcional sem chaves de API.
 * Lê a última posição do motorista de localStorage (chave 'driverPosition')
 * e exibe no mapa Leaflet. Se não houver posição disponível, centraliza
 * em uma coordenada padrão e simula movimento (apenas para demonstração).
 */

(function(){
  const STORAGE_KEY = 'driverPosition';
  const DEFAULT_POS = { lat: -23.55052, lng: -46.633308 }; // São Paulo centro
  let map, marker, accuracyCircle;
  let followDriver = true;

  function getDriverId(){
    const params = new URLSearchParams(location.search);
    const id = parseInt(params.get('driver_id') || '1', 10);
    return isNaN(id) || id <= 0 ? 1 : id;
  }

  // Formats lat/lng nicely
  function fmt(n){ return Number(n).toFixed(5); }

  // Reads position from localStorage
  function readStoredPosition(){
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      const obj = JSON.parse(raw);
      if (obj && typeof obj.lat === 'number' && typeof obj.lng === 'number') return obj;
    } catch(_){}
    return null;
  }

  // Saves position to localStorage
  function saveStoredPosition(pos){
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
      lat: pos.lat,
      lng: pos.lng,
      accuracy: pos.accuracy || null,
      ts: Date.now()
    }));
  }

  // Initialize map on rota-tempo-real.html
  window.initMapTracking = function initMapTracking(){
    if (!window.L) {
      console.warn('Leaflet not loaded.');
      return;
    }

    const existing = readStoredPosition() || DEFAULT_POS;

    map = L.map('map').setView([existing.lat, existing.lng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    marker = L.marker([existing.lat, existing.lng]).addTo(map).bindPopup('Motorista');

    // UI: follow toggle
    const followCtrl = L.control({ position: 'topright' });
    followCtrl.onAdd = function(){
      const div = L.DomUtil.create('div', 'leaflet-bar');
      const btn = L.DomUtil.create('a', '', div);
      btn.href = '#';
      btn.title = 'Seguir motorista';
      btn.textContent = '▶';
      btn.style.padding = '6px 10px';
      btn.style.fontWeight = 'bold';
      btn.onclick = (e)=>{
        e.preventDefault();
        followDriver = !followDriver;
        btn.textContent = followDriver ? '▶' : '⏸';
      };
      return div;
    };
    followCtrl.addTo(map);

    const driverId = getDriverId();

    // Poll backend first; fallback to localStorage
    setInterval(async ()=>{
      try {
        const res = await fetch(`api/get_location.php?driver_id=${driverId}`, { cache: 'no-store' });
        if (res.ok) {
          const json = await res.json();
          if (json && json.success && json.data) {
            const { lat, lng, accuracy } = json.data;
            if (lat && lng) {
              updateMarker({ lat: parseFloat(lat), lng: parseFloat(lng), accuracy: accuracy ? parseFloat(accuracy) : null });
              return;
            }
          }
        }
      } catch(_) {}

      const pos = readStoredPosition();
      if (pos) updateMarker(pos);
    }, 2000);

    // Also react to storage events (other tabs/windows)
    window.addEventListener('storage', (ev)=>{
      if (ev.key === STORAGE_KEY && ev.newValue) {
        try {
          const pos = JSON.parse(ev.newValue);
          updateMarker(pos);
        } catch(_){}
      }
    });

    // If no real position, simulate small movements so the map is not empty
    if (!readStoredPosition()) {
      simulateDemoPath(existing);
    }
  };

  function updateMarker(pos){
    const { lat, lng, accuracy } = pos;
    marker.setLatLng([lat, lng]);
    marker.setPopupContent(`Motorista<br>Lat: ${fmt(lat)} Lng: ${fmt(lng)}`);

    if (accuracy && !isNaN(accuracy)) {
      if (!accuracyCircle) {
        accuracyCircle = L.circle([lat, lng], { radius: accuracy, color: '#0d6efd', opacity: 0.3 });
        accuracyCircle.addTo(map);
      } else {
        accuracyCircle.setLatLng([lat, lng]);
        accuracyCircle.setRadius(accuracy);
      }
    }

    if (followDriver) {
      map.panTo([lat, lng], { animate: true });
    }
  }

  // On driver page, start geolocation and write position to localStorage
  window.initDriverTracking = function initDriverTracking(){
    if (!('geolocation' in navigator)) {
      console.warn('Geolocation not supported in this browser.');
      return;
    }
    const driverId = getDriverId();
    navigator.geolocation.watchPosition((pos)=>{
      const { latitude, longitude, accuracy } = pos.coords;
      saveStoredPosition({ lat: latitude, lng: longitude, accuracy });
      // Send to backend as well (best-effort)
      const body = new URLSearchParams({
        driver_id: String(driverId),
        lat: String(latitude),
        lng: String(longitude),
        accuracy: accuracy != null ? String(accuracy) : ''
      });
      fetch('api/update_location.php', { method: 'POST', body, keepalive: true }).catch(()=>{});
    }, (err)=>{
      console.warn('Geolocation error:', err.message);
    }, { enableHighAccuracy: true, maximumAge: 5000, timeout: 10000 });
  };

  // Simple demo path around starting point when no real data
  function simulateDemoPath(center){
    let angle = 0;
    setInterval(()=>{
      angle += 10; // degrees
      const rad = angle * Math.PI / 180;
      const dLat = Math.cos(rad) * 0.0008;
      const dLng = Math.sin(rad) * 0.0008;
      const lat = center.lat + dLat;
      const lng = center.lng + dLng;
      saveStoredPosition({ lat, lng, accuracy: 20 });
    }, 1500);
  }
})();
