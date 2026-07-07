document.addEventListener('submit', function (event) {
  const form = event.target;
  if (!form.matches('.inquiry-form')) return;
  event.preventDefault();

  const api = form.getAttribute('data-api');
  if (!api || location.protocol === 'file:') {
    alert('演示站已收到询盘。部署后会提交到后台留言列表。');
    return;
  }

  const formData = new FormData(form);
  const payload = {
    form_key: formData.get('form_key') || 'contact',
    source_url: location.pathname,
    data: {}
  };

  formData.forEach(function (value, key) {
    if (key !== 'form_key') {
      payload.data[key] = value;
    }
  });

  fetch(api, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(function (response) { return response.json(); })
    .then(function (result) {
      if (!result.success) throw new Error(result.message || '提交失败');
      form.reset();
      alert('提交成功，我们会尽快联系你。');
    })
    .catch(function (error) {
      alert(error.message || '提交失败，请稍后再试。');
    });
});

function escapeHtml(value) {
  return String(value || '').replace(/[&<>"']/g, function (char) {
    return {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }[char];
  });
}

function highlightText(value, keyword) {
  const text = escapeHtml(value);
  if (!keyword) return text;
  const escapedKeyword = keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return text.replace(new RegExp('(' + escapedKeyword + ')', 'gi'), '<mark>$1</mark>');
}

function initStaticSearch() {
  const page = document.querySelector('[data-search-page]');
  if (!page) return;

  const form = page.querySelector('.search-form');
  const input = form.querySelector('input[name="q"]');
  const status = page.querySelector('[data-search-status]');
  const resultsEl = page.querySelector('[data-search-results]');
  const filterButtons = Array.from(page.querySelectorAll('[data-type]'));
  const params = new URLSearchParams(location.search);
  let items = [];
  let activeType = 'all';

  function render() {
    const keyword = input.value.trim();
    const normalized = keyword.toLowerCase();
    const filtered = items.filter(function (item) {
      const inType = activeType === 'all' || item.type === activeType;
      const haystack = [item.title, item.summary].join(' ').toLowerCase();
      return inType && (!normalized || haystack.indexOf(normalized) !== -1);
    });

    status.textContent = keyword ? '找到 ' + filtered.length + ' 条相关内容。' : '请输入关键词开始搜索。';
    resultsEl.innerHTML = filtered.map(function (item) {
      const label = item.type === 'product' ? '产品' : '文章';
      return [
        '<a class="search-result" href="' + escapeHtml(item.url) + '">',
        '<span>' + label + '</span>',
        '<strong>' + highlightText(item.title, keyword) + '</strong>',
        '<p>' + highlightText(item.summary, keyword) + '</p>',
        '</a>'
      ].join('');
    }).join('');
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    const keyword = input.value.trim();
    const nextUrl = keyword ? '?q=' + encodeURIComponent(keyword) : location.pathname;
    history.replaceState(null, '', nextUrl);
    render();
  });

  filterButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      activeType = button.getAttribute('data-type') || 'all';
      filterButtons.forEach(function (item) {
        item.classList.toggle('active', item === button);
      });
      render();
    });
  });

  input.value = params.get('q') || '';
  fetch('search.json')
    .then(function (response) { return response.json(); })
    .then(function (data) {
      items = Array.isArray(data) ? data : [];
      render();
    })
    .catch(function () {
      status.textContent = '搜索索引暂时不可用，请重新发布静态站。';
    });
}

document.addEventListener('DOMContentLoaded', initStaticSearch);
