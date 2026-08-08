(function () {
  function debounce(fn, wait) {
    var timer = null;
    return function () {
      var ctx = this;
      var args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () {
        fn.apply(ctx, args);
      }, wait);
    };
  }

  function bindPersonSearch(input) {
    var targetId = input.getAttribute('data-target');
    var hidden = document.getElementById(targetId);
    var results = input.parentElement.querySelector('.search-results');
    if (!hidden || !results) {
      return;
    }

    var runSearch = debounce(function () {
      var q = input.value.trim();
      results.innerHTML = '';
      hidden.value = '';

      if (q.length < 2) {
        return;
      }

      fetch('/index.php?route=person/search&q=' + encodeURIComponent(q), {
        headers: { 'Accept': 'application/json' }
      })
        .then(function (res) { return res.json(); })
        .then(function (list) {
          results.innerHTML = '';
          list.forEach(function (item) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action';
            btn.textContent = item.name;
            btn.addEventListener('click', function () {
              input.value = item.name;
              hidden.value = item.id;
              results.innerHTML = '';
            });
            results.appendChild(btn);
          });
        })
        .catch(function () {
          results.innerHTML = '';
        });
    }, 300);

    input.addEventListener('input', runSearch);
  }

  function createNode(person, level) {
    var wrap = document.createElement('div');
    wrap.className = 'tree-node border rounded p-2 mb-2';
    wrap.style.marginLeft = (level * 16) + 'px';

    var header = document.createElement('div');
    header.className = 'd-flex align-items-start gap-2 flex-wrap';

    var toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'btn btn-sm btn-outline-secondary flex-shrink-0';
    toggle.style.cssText = 'min-width:28px;height:28px;padding:0;font-size:.8rem;line-height:1;';
    toggle.textContent = '+';

    var containerEl = wrap.closest('#treeContainer');
    var profileRoute = (containerEl && containerEl.getAttribute('data-profile-route')) || '';
    var wikiRoute    = (containerEl && containerEl.getAttribute('data-wiki-route'))    || '';

    // Main info block
    var infoBlock = document.createElement('div');
    infoBlock.style.cssText = 'flex:1;min-width:0;';

    // Row 1: name + birth year
    var nameRow = document.createElement('div');
    nameRow.className = 'd-flex align-items-center gap-2 flex-wrap';

    var titleLink = document.createElement('a');
    titleLink.href = profileRoute ? profileRoute + '&id=' + encodeURIComponent(person.id) : '#';
    titleLink.textContent = person.name;
    titleLink.className = 'text-decoration-none fw-semibold';
    titleLink.style.fontSize = '.92rem';

    nameRow.appendChild(titleLink);

    if (person.birth_year) {
      var yearBadge = document.createElement('span');
      yearBadge.textContent = 'b.' + person.birth_year;
      yearBadge.style.cssText = 'font-size:.7rem;color:#94a3b8;background:#f1f5f9;border-radius:999px;padding:1px 7px;';
      nameRow.appendChild(yearBadge);
    }

    infoBlock.appendChild(nameRow);

    // Row 2: spouse (if any)
    if (person.spouse_name && person.spouse_name !== '') {
      var spouseRow = document.createElement('div');
      spouseRow.className = 'd-flex align-items-center gap-1';
      spouseRow.style.marginTop = '2px';

      var heart = document.createElement('span');
      heart.textContent = '♥';
      heart.style.cssText = 'color:#f43f5e;font-size:.78rem;';

      var spouseLink = document.createElement('a');
      spouseLink.href = person.spouse_id && profileRoute
        ? profileRoute + '&id=' + encodeURIComponent(person.spouse_id)
        : '#';
      spouseLink.textContent = person.spouse_name;
      spouseLink.className = 'text-decoration-none';
      spouseLink.style.cssText = 'font-size:.8rem;color:#0891b2;font-weight:500;';

      spouseRow.appendChild(heart);
      spouseRow.appendChild(spouseLink);
      infoBlock.appendChild(spouseRow);
    }

    // Row 3: action links (wiki)
    if (wikiRoute && person.id) {
      var actRow = document.createElement('div');
      actRow.style.marginTop = '3px';
      var wikiLink = document.createElement('a');
      wikiLink.href = wikiRoute + '&id=' + encodeURIComponent(person.id);
      wikiLink.textContent = '📖 Wiki';
      wikiLink.style.cssText = 'font-size:.7rem;color:#6366f1;text-decoration:none;';
      wikiLink.title = 'Wikipedia-style family view';
      actRow.appendChild(wikiLink);
      infoBlock.appendChild(actRow);
    }

    header.appendChild(toggle);
    header.appendChild(infoBlock);

    var childrenWrap = document.createElement('div');
    childrenWrap.className = 'mt-1';
    childrenWrap.hidden = true;

    toggle.addEventListener('click', function () {
      if (childrenWrap.dataset.loading === '1') return;
      if (!childrenWrap.dataset.loaded) {
        childrenWrap.dataset.loading = '1';
        toggle.textContent = '…';
        var cEl = childrenWrap.closest('#treeContainer');
        var route = (cEl && cEl.getAttribute('data-children-route')) || '/index.php?route=person/children';
        var url = route + '&person_id=' + encodeURIComponent(person.id);
        fetch(url, { headers: { 'Accept': 'application/json' } })
          .then(function (res) { return res.json(); })
          .then(function (kids) {
            if (!Array.isArray(kids)) kids = [];
            childrenWrap.innerHTML = '';
            var seen = {};
            kids.forEach(function (kid) {
              if (seen[kid.id]) return;
              seen[kid.id] = true;
              childrenWrap.appendChild(createNode(kid, level + 1));
            });
            childrenWrap.dataset.loaded = '1';
            childrenWrap.dataset.loading = '0';
            childrenWrap.hidden = kids.length === 0;
            toggle.textContent = kids.length === 0 ? '·' : '−';
            if (kids.length === 0) toggle.disabled = true;
          })
          .catch(function () {
            childrenWrap.dataset.loaded = '1';
            childrenWrap.dataset.loading = '0';
            childrenWrap.hidden = false;
            childrenWrap.innerHTML = '<div class="text-danger small ps-2">Failed to load.</div>';
            toggle.textContent = '!';
          });
      } else {
        childrenWrap.hidden = !childrenWrap.hidden;
        toggle.textContent = childrenWrap.hidden ? '+' : '−';
      }
    });

    wrap.appendChild(header);
    wrap.appendChild(childrenWrap);
    return wrap;
  }

  function bindTree() {
    var rootInput   = document.getElementById('treeRootId');
    var button      = document.getElementById('loadTreeBtn');
    var container   = document.getElementById('treeContainer');
    var rootDisplay = document.getElementById('treeRootDisplay');
    if (!rootInput || !button || !container) return;

    button.addEventListener('click', function () {
      var rootId = rootInput.value.trim();
      container.innerHTML = '';
      if (!rootId && rootDisplay) {
        var raw = rootDisplay.value.trim();
        var match = raw.match(/#(\d+)/) || raw.match(/\b(\d+)\b/);
        if (match) { rootId = match[1]; rootInput.value = rootId; }
      }
      if (!rootId) {
        container.innerHTML = '<div class="text-muted small">Select a person from search or enter an ID.</div>';
        return;
      }
      container.innerHTML = '<div class="text-muted small">Loading…</div>';
      var infoRoute = '/index.php?route=person/node-info&person_id=' + encodeURIComponent(rootId);
      fetch(infoRoute, { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (info) {
          container.innerHTML = '';
          var root = info && info.id
            ? info
            : { id: rootId, name: (rootDisplay && rootDisplay.value.trim()) || 'Person #' + rootId, spouse_name: '', spouse_id: 0, birth_year: '' };
          container.appendChild(createNode(root, 0));
        })
        .catch(function () {
          container.innerHTML = '';
          var root = { id: rootId, name: (rootDisplay && rootDisplay.value.trim()) || 'Person #' + rootId, spouse_name: '', spouse_id: 0, birth_year: '' };
          container.appendChild(createNode(root, 0));
        });
    });

    if (rootInput.value && rootInput.value.trim() !== '') {
      button.click();
    }
  }

  document.querySelectorAll('.person-search').forEach(bindPersonSearch);
  bindTree();
})();
