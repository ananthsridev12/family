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
    wrap.style.marginLeft = (level * 14) + 'px';

    var header = document.createElement('div');
    header.className = 'd-flex align-items-center gap-2';

    var toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'btn btn-sm btn-outline-secondary';
    toggle.textContent = '+';

    var title = document.createElement('strong');
    title.textContent = person.name + ' #' + person.id;

    var childrenWrap = document.createElement('div');
    childrenWrap.className = 'mt-2';
    childrenWrap.hidden = true;

    toggle.addEventListener('click', function () {
      if (childrenWrap.dataset.loading === '1') {
        return;
      }
      if (!childrenWrap.dataset.loaded) {
        childrenWrap.dataset.loading = '1';
        var containerEl = childrenWrap.closest('#treeContainer');
        var route = (containerEl && containerEl.getAttribute('data-children-route')) || '/index.php?route=person/children';
        var url = route + '&person_id=' + encodeURIComponent(person.id);
        fetch(url, {
          headers: { 'Accept': 'application/json' }
        })
          .then(function (res) { return res.json(); })
          .then(function (kids) {
            if (!Array.isArray(kids)) {
              kids = [];
            }
            childrenWrap.innerHTML = '';
            var seen = {};
            kids.forEach(function (kid) {
              if (seen[kid.id]) {
                return;
              }
              seen[kid.id] = true;
              childrenWrap.appendChild(createNode(kid, level + 1));
            });
            childrenWrap.dataset.loaded = '1';
            childrenWrap.dataset.loading = '0';
            childrenWrap.hidden = kids.length === 0;
            if (kids.length === 0) {
              toggle.textContent = '.';
              toggle.disabled = true;
            } else {
              toggle.textContent = '-';
            }
          })
          .catch(function () {
            childrenWrap.dataset.loaded = '1';
            childrenWrap.dataset.loading = '0';
            childrenWrap.hidden = false;
            childrenWrap.innerHTML = '<div class="text-danger small">Failed to load children.</div>';
            toggle.textContent = '!';
          });
      } else {
        childrenWrap.hidden = !childrenWrap.hidden;
        toggle.textContent = childrenWrap.hidden ? '+' : '-';
      }
    });

    header.appendChild(toggle);
    header.appendChild(title);
    wrap.appendChild(header);
    wrap.appendChild(childrenWrap);

    return wrap;
  }

  function bindTree() {
    var rootInput = document.getElementById('treeRootId');
    var button = document.getElementById('loadTreeBtn');
    var container = document.getElementById('treeContainer');
    var rootDisplay = document.getElementById('treeRootDisplay');
    if (!rootInput || !button || !container) {
      return;
    }

    button.addEventListener('click', function () {
      var rootId = rootInput.value.trim();
      container.innerHTML = '';
      if (!rootId && rootDisplay) {
        var raw = rootDisplay.value.trim();
        var match = raw.match(/#(\\d+)/) || raw.match(/\\b(\\d+)\\b/);
        if (match) {
          rootId = match[1];
          rootInput.value = rootId;
        }
      }
      if (!rootId) {
        container.innerHTML = '<div class="text-muted">Select a person from search or enter an ID.</div>';
        return;
      }
      var root = { id: rootId, name: (rootDisplay && rootDisplay.value.trim()) ? rootDisplay.value.trim() : 'Selected Person' };
      container.appendChild(createNode(root, 0));
    });

    if (rootInput.value && rootInput.value.trim() !== '') {
      button.click();
    }
  }

  document.querySelectorAll('.person-search').forEach(bindPersonSearch);
  bindTree();
})();
