<?php
$profileRoute = (string)($profileRoute ?? 'member/person-view');
$wikiRoute    = (string)($wikiRoute    ?? 'member/wiki-view');
$locations    = is_array($locations ?? null) ? $locations : [];
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<?php include __DIR__ . '/../layouts/app_start.php'; ?>

<div class="page-header d-flex align-items-center justify-content-between mb-3">
  <h1 class="h4 mb-0">&#127758; Family Map</h1>
</div>

<?php if (empty($locations)): ?>
<div class="alert alert-info">No location data found. Add current or native locations to family members to see them here.</div>
<?php else: ?>

<!-- Tab toggle -->
<div class="btn-group mb-3" role="group" aria-label="Location type">
  <button id="tabCurrent" class="btn btn-primary btn-sm" onclick="switchTab('current')">Current Locations</button>
  <button id="tabNative"  class="btn btn-outline-secondary btn-sm" onclick="switchTab('native')">Native Locations</button>
</div>

<!-- Progress bar -->
<div id="geocodeProgress" class="mb-2" style="display:none;">
  <div class="d-flex align-items-center gap-2">
    <div class="progress flex-grow-1" style="height:8px;">
      <div id="progressBar" class="progress-bar bg-primary" role="progressbar" style="width:0%"></div>
    </div>
    <small id="progressText" class="text-muted text-nowrap">0 / 0</small>
  </div>
  <small class="text-muted">Geocoding locations…</small>
</div>

<!-- Map -->
<div id="mapDiv" class="border rounded mb-3" style="height:500px;z-index:1;"></div>

<!-- Location list -->
<div id="locationList"></div>

<?php endif; ?>

<?php include __DIR__ . '/../layouts/app_end.php'; ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<?php if (!empty($locations)): ?>
<style>
.loc-marker {
  background: #6366f1;
  color: #fff;
  border-radius: 50%;
  width: 32px; height: 32px;
  display: flex; align-items: center; justify-content: center;
  font-weight: bold; font-size: 12px;
  border: 2px solid #fff;
  box-shadow: 0 1px 4px rgba(0,0,0,.3);
}
.loc-popup-name { display: inline-block; margin: 1px 0; }
</style>
<script>
(function () {
  'use strict';

  /* ── PHP data ─────────────────────────────────────────── */
  var rawLocations = <?= json_encode(
    array_map(function ($r) {
      return [
        'person_id'        => (int)$r['person_id'],
        'full_name'        => (string)($r['full_name']        ?? ''),
        'current_location' => (string)($r['current_location'] ?? ''),
        'native_location'  => (string)($r['native_location']  ?? ''),
      ];
    }, $locations),
    JSON_UNESCAPED_UNICODE
  ) ?>;

  var PROFILE_ROUTE = <?= json_encode('/index.php?route=' . $profileRoute, JSON_UNESCAPED_SLASHES) ?>;

  /* ── State ──────────────────────────────────────────────── */
  var currentTab  = 'current';
  var geocodeCache = {}; // location string -> {lat, lng} or null
  var map, markerLayer;
  var geocodeQueue = [];
  var geocodeTimer = null;

  /* ── Init map ─────────────────────────────────────────── */
  map = L.map('mapDiv').setView([20, 0], 2);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  }).addTo(map);
  markerLayer = L.layerGroup().addTo(map);

  /* ── Get location string for current tab ─────────────── */
  function getLocField(row) {
    return currentTab === 'current'
      ? row.current_location
      : row.native_location;
  }

  /* ── Group persons by location ────────────────────────── */
  function groupByLocation(rows) {
    var groups = {}; // location string -> [person row, ...]
    rows.forEach(function (row) {
      var loc = (getLocField(row) || '').trim();
      if (!loc) { return; }
      if (!groups[loc]) { groups[loc] = []; }
      groups[loc].push(row);
    });
    return groups;
  }

  /* ── Geocode a location via Nominatim ─────────────────── */
  function geocode(location, callback) {
    if (geocodeCache.hasOwnProperty(location)) {
      callback(geocodeCache[location]);
      return;
    }
    var url = 'https://nominatim.openstreetmap.org/search?q=' +
              encodeURIComponent(location) + '&format=json&limit=1';
    fetch(url, {
      headers: {
        'Accept': 'application/json',
        'User-Agent': 'FamilyTreeApp/1.0',
      }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (Array.isArray(data) && data.length > 0) {
          var result = { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) };
          geocodeCache[location] = result;
          callback(result);
        } else {
          geocodeCache[location] = null;
          callback(null);
        }
      })
      .catch(function () {
        geocodeCache[location] = null;
        callback(null);
      });
  }

  /* ── Render markers ──────────────────────────────────── */
  function renderMarkers(groups, geocoded) {
    markerLayer.clearLayers();
    Object.keys(groups).forEach(function (loc) {
      var coord = geocoded[loc];
      if (!coord) { return; }
      var persons = groups[loc];

      var icon = L.divIcon({
        className: '',
        html: '<div class="loc-marker">' + persons.length + '</div>',
        iconSize: [32, 32],
        iconAnchor: [16, 16],
      });

      var popup = '<strong>' + escHtml(loc) + '</strong><br>' +
        persons.map(function (p) {
          return '<a class="loc-popup-name" href="' +
            escHtml(PROFILE_ROUTE + '&id=' + p.person_id) + '">' +
            escHtml(p.full_name) + '</a>';
        }).join('<br>');

      L.marker([coord.lat, coord.lng], { icon: icon })
        .bindPopup(popup)
        .addTo(markerLayer);
    });

    // Fit bounds if any markers placed
    var bounds = [];
    Object.keys(groups).forEach(function (loc) {
      var c = geocoded[loc];
      if (c) { bounds.push([c.lat, c.lng]); }
    });
    if (bounds.length > 0) {
      if (bounds.length === 1) {
        map.setView(bounds[0], 6);
      } else {
        map.fitBounds(bounds, { padding: [40, 40] });
      }
    }
  }

  /* ── Render location list ────────────────────────────── */
  function renderList(groups) {
    var listEl = document.getElementById('locationList');
    var locs = Object.keys(groups).sort();
    if (locs.length === 0) {
      listEl.innerHTML = '<div class="alert alert-info">No ' + currentTab + ' locations recorded.</div>';
      return;
    }
    var html = '<div class="card"><div class="card-body p-0"><table class="table table-sm mb-0">' +
      '<thead><tr><th>Location</th><th>Members</th></tr></thead><tbody>';
    locs.forEach(function (loc) {
      var persons = groups[loc];
      html += '<tr><td class="fw-semibold">' + escHtml(loc) + '</td><td>' +
        persons.map(function (p) {
          return '<a href="' + escHtml(PROFILE_ROUTE + '&id=' + p.person_id) + '">' +
                 escHtml(p.full_name) + '</a>';
        }).join(', ') + '</td></tr>';
    });
    html += '</tbody></table></div></div>';
    listEl.innerHTML = html;
  }

  /* ── Main geocoding pipeline ─────────────────────────── */
  function loadMap() {
    var groups = groupByLocation(rawLocations);
    var locs   = Object.keys(groups);
    renderList(groups);

    if (locs.length === 0) {
      markerLayer.clearLayers();
      return;
    }

    var total    = locs.length;
    var done     = 0;
    var geocoded = {};

    // Clear previous queue
    if (geocodeTimer !== null) {
      clearTimeout(geocodeTimer);
      geocodeTimer = null;
    }

    var progressEl    = document.getElementById('geocodeProgress');
    var progressBar   = document.getElementById('progressBar');
    var progressText  = document.getElementById('progressText');
    progressEl.style.display = 'block';
    progressBar.style.width  = '0%';
    progressText.textContent = '0 / ' + total;

    // Check if all are already cached
    var allCached = locs.every(function (l) { return geocodeCache.hasOwnProperty(l); });
    if (allCached) {
      locs.forEach(function (l) { geocoded[l] = geocodeCache[l]; });
      renderMarkers(groups, geocoded);
      progressEl.style.display = 'none';
      return;
    }

    // Queue geocoding with 1200ms delay
    var idx = 0;
    function next() {
      if (idx >= locs.length) {
        renderMarkers(groups, geocoded);
        progressEl.style.display = 'none';
        return;
      }
      var loc = locs[idx++];
      geocode(loc, function (coord) {
        geocoded[loc] = coord;
        done++;
        var pct = Math.round((done / total) * 100);
        progressBar.style.width  = pct + '%';
        progressText.textContent = done + ' / ' + total;
        geocodeTimer = setTimeout(next, 1200);
      });
    }
    next();
  }

  /* ── Tab switch ──────────────────────────────────────── */
  window.switchTab = function (tab) {
    currentTab = tab;
    document.getElementById('tabCurrent').className =
      tab === 'current' ? 'btn btn-primary btn-sm' : 'btn btn-outline-secondary btn-sm';
    document.getElementById('tabNative').className =
      tab === 'native'  ? 'btn btn-primary btn-sm' : 'btn btn-outline-secondary btn-sm';
    loadMap();
  };

  /* ── Helpers ─────────────────────────────────────────── */
  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ── Boot ────────────────────────────────────────────── */
  loadMap();
})();
</script>
<?php endif; ?>
