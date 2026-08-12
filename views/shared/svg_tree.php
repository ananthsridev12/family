<?php
$profileRoute = (string)($profileRoute ?? 'member/person-view');
$wikiRoute    = (string)($wikiRoute    ?? 'member/wiki-view');
$rootId       = (int)($root_id         ?? 0);
$rootName     = (string)($root_name    ?? '');
$ajaxRoute    = (string)($childrenAjaxRoute ?? 'person/children');
$nodeInfoRoute = 'person/node-info';
?>
<?php include __DIR__ . '/../layouts/app_start.php'; ?>

<div class="page-header d-flex align-items-center justify-content-between mb-3">
  <h1 class="h4 mb-0">Visual Tree</h1>
</div>

<div class="card mb-3">
  <div class="card-body">
    <div class="row g-3 align-items-end">
      <div class="col-md-8 position-relative">
        <label class="form-label fw-semibold">Root Person</label>
        <input
          class="form-control person-search"
          id="svgRootDisplay"
          data-target="svgRootId"
          placeholder="Type name (2+ chars)…"
          value="<?= htmlspecialchars($rootName, ENT_QUOTES, 'UTF-8') ?>"
          autocomplete="off">
        <input id="svgRootId" type="hidden" value="<?= $rootId ?>">
        <div class="list-group mt-1 search-results position-absolute w-100" style="z-index:1000;top:100%;left:0;"></div>
      </div>
      <div class="col-md-4">
        <button id="drawTreeBtn" class="btn btn-primary w-100">Draw Tree</button>
      </div>
    </div>
  </div>
</div>

<div id="svgStatus" class="mb-2"></div>

<div id="svgWrap" class="border rounded bg-light position-relative" style="height:560px;overflow:hidden;cursor:grab;">
  <svg id="treeSVG" width="100%" height="100%" style="display:block;">
    <g id="treeG"></g>
  </svg>
  <div id="svgHelp" class="position-absolute bottom-0 end-0 text-muted small p-2" style="pointer-events:none;">
    Drag to pan &nbsp;|&nbsp; Scroll to zoom
  </div>
</div>

<?php include __DIR__ . '/../layouts/app_end.php'; ?>

<style>
#svgWrap { background: #f8fafc; }
#treeSVG { touch-action: none; }
.node-rect { transition: filter 0.15s; }
.node-rect:hover { filter: brightness(0.93); }
.svg-spinner {
  display: inline-block;
  width: 18px; height: 18px;
  border: 2px solid #cbd5e1;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: svgSpin 0.7s linear infinite;
  vertical-align: middle;
  margin-right: 6px;
}
@keyframes svgSpin { to { transform: rotate(360deg); } }
</style>

<script>
(function () {
  'use strict';

  /* ── Configuration ─────────────────────────────────────── */
  var NODE_W  = 164;
  var NODE_H  = 64;
  var H_GAP   = 28;   // extra space between sibling subtrees
  var V_GAP   = 120;  // vertical distance between generations
  var MAX_LVL = 4;    // maximum levels to load

  var PROFILE_ROUTE    = <?= json_encode('/index.php?route=' . $profileRoute, JSON_UNESCAPED_SLASHES) ?>;
  var WIKI_ROUTE       = <?= json_encode('/index.php?route=' . $wikiRoute, JSON_UNESCAPED_SLASHES) ?>;
  var CHILDREN_ROUTE   = <?= json_encode('/index.php?route=' . $ajaxRoute, JSON_UNESCAPED_SLASHES) ?>;
  var NODE_INFO_ROUTE  = <?= json_encode('/index.php?route=' . $nodeInfoRoute, JSON_UNESCAPED_SLASHES) ?>;

  /* ── State ─────────────────────────────────────────────── */
  var nodes    = {};   // id -> node object
  var rootId   = null;
  var tx = 40, ty = 30, sc = 1.0;
  var dragging = false, dragX = 0, dragY = 0;
  var moved    = false;

  /* ── DOM refs ───────────────────────────────────────────── */
  var svgEl     = document.getElementById('treeSVG');
  var treeG     = document.getElementById('treeG');
  var statusEl  = document.getElementById('svgStatus');
  var drawBtn   = document.getElementById('drawTreeBtn');
  var rootInput = document.getElementById('svgRootId');

  /* ── Utilities ──────────────────────────────────────────── */
  function svgEl_(tag, attrs) {
    var el = document.createElementNS('http://www.w3.org/2000/svg', tag);
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        el.setAttribute(k, attrs[k]);
      });
    }
    return el;
  }

  function truncate(str, max) {
    str = String(str || '');
    return str.length > max ? str.slice(0, max - 1) + '…' : str;
  }

  function setStatus(html) {
    statusEl.innerHTML = html;
  }

  function applyTransform() {
    treeG.setAttribute('transform', 'translate(' + tx + ',' + ty + ') scale(' + sc + ')');
  }

  /* ── Fetch helpers ──────────────────────────────────────── */
  function fetchJSON(url) {
    return fetch(url, { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); });
  }

  function fetchNodeInfo(personId) {
    return fetchJSON(NODE_INFO_ROUTE + '&person_id=' + encodeURIComponent(personId));
  }

  function fetchChildren(personId) {
    return fetchJSON(CHILDREN_ROUTE + '&person_id=' + encodeURIComponent(personId));
  }

  /* ── Tree loading (BFS) ─────────────────────────────────── */
  function loadTree(rootPersonId) {
    nodes    = {};
    rootId   = null;
    treeG.innerHTML = '';
    setStatus('<span class="svg-spinner"></span> Loading tree…');
    drawBtn.disabled = true;

    fetchNodeInfo(rootPersonId)
      .then(function (info) {
        if (!info || !info.id) {
          setStatus('<div class="alert alert-warning py-2">Person not found.</div>');
          drawBtn.disabled = false;
          return;
        }

        rootId = info.id;
        nodes[rootId] = makeNode(info, 0);

        // BFS queue: [{id, level}]
        var queue = [{ id: rootId, level: 0 }];
        var idx   = 0;

        function processNext() {
          if (idx >= queue.length) {
            // Done loading – layout and render
            computeLayout();
            renderTree();
            setStatus('');
            drawBtn.disabled = false;
            return;
          }

          var item  = queue[idx++];
          var lvl   = item.level;

          if (lvl >= MAX_LVL) {
            processNext();
            return;
          }

          fetchChildren(item.id)
            .then(function (kids) {
              if (!Array.isArray(kids)) { kids = []; }
              kids.forEach(function (kid) {
                if (!kid || !kid.id) { return; }
                if (!nodes[kid.id]) {
                  nodes[kid.id] = makeNode(kid, lvl + 1);
                  queue.push({ id: kid.id, level: lvl + 1 });
                }
                nodes[item.id].children.push(kid.id);
              });
              processNext();
            })
            .catch(function () { processNext(); });
        }

        processNext();
      })
      .catch(function () {
        setStatus('<div class="alert alert-danger py-2">Failed to load tree data.</div>');
        drawBtn.disabled = false;
      });
  }

  function makeNode(data, level) {
    return {
      id:          data.id,
      full_name:   String(data.full_name || data.name || ''),
      name:        String(data.name || data.full_name || ''),
      spouse_name: String(data.spouse_name || ''),
      spouse_id:   Number(data.spouse_id  || 0),
      gender:      String(data.gender     || ''),
      birth_year:  Number(data.birth_year || 0),
      level:       level,
      children:    [],
      x:           0,
      y:           0,
    };
  }

  /* ── Layout ─────────────────────────────────────────────── */
  function subtreeWidth(id) {
    var node = nodes[id];
    if (!node) { return NODE_W + H_GAP; }
    if (node.children.length === 0) {
      return NODE_W + H_GAP;
    }
    var total = 0;
    node.children.forEach(function (cid) { total += subtreeWidth(cid); });
    return Math.max(NODE_W + H_GAP, total);
  }

  function assignPositions(id, x, y) {
    var node = nodes[id];
    if (!node) { return; }

    if (node.children.length === 0) {
      node.x = x + (NODE_W + H_GAP) / 2;
      node.y = y;
      return;
    }

    var cx = x;
    node.children.forEach(function (cid) {
      var w = subtreeWidth(cid);
      assignPositions(cid, cx, y + V_GAP);
      cx += w;
    });

    var firstChild = nodes[node.children[0]];
    var lastChild  = nodes[node.children[node.children.length - 1]];
    node.x = (firstChild.x + lastChild.x) / 2;
    node.y = y;
  }

  function computeLayout() {
    var totalW = subtreeWidth(rootId);
    assignPositions(rootId, 0, NODE_H / 2);

    // Center tree in viewport
    var svgW = svgEl.clientWidth  || 800;
    var svgH = svgEl.clientHeight || 560;
    var rootNode = nodes[rootId];
    tx = svgW / 2 - rootNode.x * sc;
    ty = 40;
  }

  /* ── Rendering ───────────────────────────────────────────── */
  function genderIcon(g) {
    if (g === 'male')   { return '♂'; }
    if (g === 'female') { return '♀'; }
    return '';
  }

  function renderTree() {
    treeG.innerHTML = '';

    var isRoot = {};
    isRoot[rootId] = true;

    // Edges first
    Object.keys(nodes).forEach(function (id) {
      var node = nodes[id];
      node.children.forEach(function (cid) {
        var child = nodes[cid];
        var x1 = node.x,  y1 = node.y + NODE_H;
        var x2 = child.x, y2 = child.y;
        var cy = (y1 + y2) / 2;
        var path = svgEl_('path', {
          d: 'M ' + x1 + ' ' + y1 +
             ' C ' + x1 + ' ' + cy + ' ' + x2 + ' ' + cy + ' ' + x2 + ' ' + y2,
          fill: 'none',
          stroke: '#94a3b8',
          'stroke-width': '1.5',
        });
        treeG.appendChild(path);
      });
    });

    // Nodes
    Object.keys(nodes).forEach(function (id) {
      var node = nodes[id];
      var isRootNode = (Number(id) === rootId);
      var g = renderNode(node, isRootNode);
      treeG.appendChild(g);
    });

    applyTransform();

    // Check for empty tree
    if (nodes[rootId] && nodes[rootId].children.length === 0) {
      setStatus('<div class="alert alert-info py-2 mb-0">This person has no recorded children in the tree.</div>');
    }
  }

  function renderNode(node, isRootNode) {
    var nx = node.x - NODE_W / 2;
    var ny = node.y;

    var g = svgEl_('g', {
      transform: 'translate(' + nx + ',' + ny + ')',
      'class': 'tree-node',
    });

    var fill   = isRootNode ? '#e0e7ff' : '#ffffff';
    var stroke = isRootNode ? '#6366f1' : '#e2e8f0';
    var sw     = isRootNode ? '2'       : '1';

    var rect = svgEl_('rect', {
      x: 0, y: 0,
      width:  NODE_W,
      height: NODE_H,
      rx: 8,
      fill:          fill,
      stroke:        stroke,
      'stroke-width': sw,
      'class': 'node-rect',
    });

    // Name text (line 1)
    var displayName = node.full_name || node.name;
    // Strip composite label suffix if present
    var dashIdx = displayName.indexOf(' — ');
    if (dashIdx > 0) { displayName = displayName.slice(0, dashIdx); }
    displayName = truncate(displayName, 22);

    var nameText = svgEl_('text', {
      x: NODE_W / 2, y: 24,
      'font-size': '13',
      'font-weight': 'bold',
      'text-anchor': 'middle',
      fill: '#1e293b',
    });
    nameText.textContent = displayName;

    // Detail text: birth year + gender icon (line 2)
    var parts = [];
    if (node.birth_year > 0) { parts.push('b.' + node.birth_year); }
    var gi = genderIcon(node.gender);
    if (gi) { parts.push(gi); }

    var detailText = svgEl_('text', {
      x: NODE_W / 2, y: 42,
      'font-size': '11',
      'text-anchor': 'middle',
      fill: '#64748b',
    });
    detailText.textContent = parts.join(' ');

    // Profile link wrapping rect + main texts
    var aProfile = svgEl_('a');
    aProfile.setAttributeNS('http://www.w3.org/1999/xlink', 'href', PROFILE_ROUTE + '&id=' + node.id);
    aProfile.setAttribute('href', PROFILE_ROUTE + '&id=' + node.id);
    aProfile.setAttribute('style', 'cursor:pointer;');
    aProfile.appendChild(rect);
    aProfile.appendChild(nameText);
    aProfile.appendChild(detailText);
    g.appendChild(aProfile);

    // Wiki link (📖) in bottom-right corner
    var aWiki = svgEl_('a');
    aWiki.setAttributeNS('http://www.w3.org/1999/xlink', 'href', WIKI_ROUTE + '&id=' + node.id);
    aWiki.setAttribute('href', WIKI_ROUTE + '&id=' + node.id);
    aWiki.setAttribute('title', 'Wiki');
    var wikiText = svgEl_('text', {
      x: NODE_W - 6, y: NODE_H - 5,
      'font-size': '12',
      'text-anchor': 'end',
      fill: '#6366f1',
      style: 'cursor:pointer;',
    });
    wikiText.textContent = '📖'; // 📖
    aWiki.appendChild(wikiText);
    g.appendChild(aWiki);

    return g;
  }

  /* ── Pan & Zoom ─────────────────────────────────────────── */
  svgEl.addEventListener('mousedown', function (e) {
    if (e.button !== 0) { return; }
    dragging = true;
    moved    = false;
    dragX    = e.clientX - tx;
    dragY    = e.clientY - ty;
    svgEl.style.cursor = 'grabbing';
    e.preventDefault();
  });

  window.addEventListener('mousemove', function (e) {
    if (!dragging) { return; }
    var dx = e.clientX - dragX;
    var dy = e.clientY - dragY;
    if (Math.abs(dx - tx) > 3 || Math.abs(dy - ty) > 3) { moved = true; }
    tx = dx;
    ty = dy;
    applyTransform();
  });

  window.addEventListener('mouseup', function () {
    dragging = false;
    svgEl.style.cursor = 'grab';
  });

  svgEl.addEventListener('wheel', function (e) {
    e.preventDefault();
    var rect  = svgEl.getBoundingClientRect();
    var mx    = e.clientX - rect.left;
    var my    = e.clientY - rect.top;
    var delta = e.deltaY > 0 ? 0.88 : 1.14;
    var newSc = Math.min(3, Math.max(0.3, sc * delta));
    tx = mx - (mx - tx) * (newSc / sc);
    ty = my - (my - ty) * (newSc / sc);
    sc = newSc;
    applyTransform();
  }, { passive: false });

  /* ── Touch pan ───────────────────────────────────────────── */
  var touchStart = null;
  svgEl.addEventListener('touchstart', function (e) {
    if (e.touches.length === 1) {
      touchStart = { x: e.touches[0].clientX - tx, y: e.touches[0].clientY - ty };
    }
  }, { passive: true });

  svgEl.addEventListener('touchmove', function (e) {
    if (e.touches.length === 1 && touchStart) {
      tx = e.touches[0].clientX - touchStart.x;
      ty = e.touches[0].clientY - touchStart.y;
      applyTransform();
      e.preventDefault();
    }
  }, { passive: false });

  svgEl.addEventListener('touchend', function () { touchStart = null; });

  /* ── Draw button ─────────────────────────────────────────── */
  drawBtn.addEventListener('click', function () {
    var id = (rootInput.value || '').trim();
    if (!id) {
      setStatus('<div class="alert alert-warning py-2">Please select a person first.</div>');
      return;
    }
    sc = 1.0;
    loadTree(Number(id));
  });

  /* ── Auto-load if root_id provided ──────────────────────── */
  (function () {
    var presetId = (rootInput.value || '').trim();
    if (presetId && Number(presetId) > 0) {
      drawBtn.click();
    }
  })();

})();
</script>
