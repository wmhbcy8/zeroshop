const latestOrderStorageKey = 'huajian_latest_order';

document.addEventListener('submit', function (event) {
  const form = event.target;
  if (form.matches('.order-form')) {
    event.preventDefault();
    submitOrderForm(form);
    return;
  }

  if (form.matches('.order-lookup-form')) {
    event.preventDefault();
    submitOrderLookup(form);
    return;
  }

  if (form.matches('.customer-note-form')) {
    event.preventDefault();
    submitCustomerNote(form);
    return;
  }

  if (form.matches('.payment-proof-form')) {
    event.preventDefault();
    submitPaymentProof(form);
    return;
  }

  if (form.matches('.service-request-form')) {
    event.preventDefault();
    submitServiceRequest(form);
    return;
  }

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

function getOrderLookupHref(orderNo, phone) {
  const params = new URLSearchParams();
  if (orderNo) params.set('order_no', orderNo);
  if (phone) params.set('phone', phone);
  return '/order.html' + (params.toString() ? '?' + params.toString() : '');
}

function saveLatestOrder(orderNo, phone) {
  if (!orderNo || !phone) return;
  try {
    localStorage.setItem(latestOrderStorageKey, JSON.stringify({
      order_no: orderNo,
      phone: phone,
      saved_at: new Date().toISOString()
    }));
  } catch {}
}

function readLatestOrder() {
  try {
    return JSON.parse(localStorage.getItem(latestOrderStorageKey) || '{}') || {};
  } catch {
    return {};
  }
}

function renderOrderReceiptLegacy(form, order, phone) {
  const receipt = form.querySelector('[data-order-receipt]');
  if (!receipt) return;
  const orderNo = order.order_no || order.id || '';
  const href = getOrderLookupHref(orderNo, phone);
  receipt.hidden = false;
  receipt.innerHTML = [
    '<strong>订单提交成功</strong>',
    '<p>订单号：' + escapeHtml(orderNo || '-') + '</p>',
    '<p>金额：' + escapeHtml(order.currency || 'CNY') + ' ' + escapeHtml(order.total_amount || '-') + '</p>',
    '<a class="btn primary" href="' + escapeHtml(href) + '">查看订单状态</a>'
  ].join('');
}

function submitOrderForm(form) {
  const api = form.getAttribute('data-api');
  const status = form.querySelector('[data-order-status]');
  if (!api || location.protocol === 'file:') {
    alert('演示站已收到订单。部署后会提交到后台订单列表。');
    return;
  }

  const formData = new FormData(form);
  const quantity = Math.max(1, Number(formData.get('quantity') || 1));
  const price = Math.max(0, Number(formData.get('price') || 0));
  const payload = {
    customer_name: formData.get('customer_name') || '',
    phone: formData.get('phone') || '',
    email: formData.get('email') || '',
    address: formData.get('address') || '',
    currency: formData.get('currency') || 'CNY',
    payment_method: 'manual',
    source_url: location.pathname,
    remark: formData.get('remark') || '',
    items: [{
      product_id: Number(formData.get('product_id') || 0),
      title: formData.get('title') || '',
      sku: formData.get('sku') || '',
      quantity: quantity,
      price: price
    }]
  };

  if (status) status.textContent = '正在提交订单...';
  form.querySelectorAll('button, input, textarea').forEach(function (item) {
    item.disabled = true;
  });

  fetch(api, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(function (response) { return response.json(); })
    .then(function (result) {
      if (!result.success) throw new Error(result.message || '订单提交失败');
      const order = result.data || {};
      const orderNo = order.order_no || order.id || '';
      saveLatestOrder(orderNo, payload.phone);
      form.reset();
      renderOrderReceipt(form, order, payload.phone);
      if (status) status.textContent = '订单提交成功，订单号：' + (orderNo || '-');
    })
    .catch(function (error) {
      if (status) status.textContent = error.message || '订单提交失败，请稍后再试。';
      alert(error.message || '订单提交失败，请稍后再试。');
    })
    .finally(function () {
      form.querySelectorAll('button, input, textarea').forEach(function (item) {
        item.disabled = false;
      });
    });
}

function orderStatusLabel(type, status) {
  const labels = {
    payment: {
      pending: '待支付',
      paid: '已支付',
      failed: '支付失败',
      refunded: '已退款'
    },
    fulfillment: {
      new: '新订单',
      confirmed: '已确认',
      shipped: '已发货',
      finished: '已完成',
      closed: '已关闭'
    }
  };
  return (labels[type] && labels[type][status]) || status || '-';
}

function renderOrderLookupLegacy(order) {
  const items = Array.isArray(order.items) ? order.items : [];
  const tracking = [order.tracking_company, order.tracking_no].filter(Boolean).join(' / ') || '暂无物流信息';
  const latest = readLatestOrder();
  const phone = latest.phone || new URLSearchParams(location.search).get('phone') || '';
  return [
    '<div class="order-status-grid">',
    '<div><span>订单号</span><strong>' + escapeHtml(order.order_no) + '</strong></div>',
    '<div><span>支付状态</span><strong>' + escapeHtml(orderStatusLabel('payment', order.payment_status)) + '</strong></div>',
    '<div><span>履约状态</span><strong>' + escapeHtml(orderStatusLabel('fulfillment', order.fulfillment_status)) + '</strong></div>',
    '<div><span>订单金额</span><strong>' + escapeHtml(order.currency || 'CNY') + ' ' + escapeHtml(order.total_amount || '0.00') + '</strong></div>',
    '</div>',
    '<div class="order-lookup-block"><h2>商品明细</h2>',
    items.map(function (item) {
      return '<p>' + escapeHtml(item.title || '未命名商品') + ' × ' + escapeHtml(item.quantity || 1) + '，' + escapeHtml(item.price || 0) + '</p>';
    }).join('') || '<p>暂无商品明细</p>',
    '</div>',
    '<div class="order-lookup-block"><h2>物流信息</h2>',
    '<p>' + escapeHtml(tracking) + '</p>',
    '<p>支付时间：' + escapeHtml(order.paid_at || '-') + '</p>',
    '<p>发货时间：' + escapeHtml(order.shipped_at || '-') + '</p>',
    '</div>',
    '<form class="customer-note-form order-lookup-block" data-api="/api/orders/customer-note">',
    '<h2>补充说明</h2>',
    '<input type="hidden" name="order_no" value="' + escapeHtml(order.order_no || '') + '">',
    '<input type="hidden" name="phone" value="' + escapeHtml(phone) + '">',
    '<select name="type">',
    '<option value="付款说明">付款说明</option>',
    '<option value="开票需求">开票需求</option>',
    '<option value="售后说明">售后说明</option>',
    '<option value="补充说明">补充说明</option>',
    '</select>',
    '<textarea name="note" maxlength="500" placeholder="填写付款截图编号、开票抬头、售后问题或其他补充说明" required></textarea>',
    '<button type="submit">提交说明</button>',
    '<p class="form-status" data-customer-note-status></p>',
    '</form>'
  ].join('');
}

function readPaymentGuide(scope) {
  const root = scope || document;
  const source = root.closest?.('[data-payment-instructions]') || root.querySelector?.('[data-payment-instructions]') || document.querySelector('[data-payment-instructions]');
  return {
    account: source?.getAttribute('data-payment-account') || '',
    instructions: source?.getAttribute('data-payment-instructions') || ''
  };
}

function renderPaymentGuide(guide, order) {
  const instructions = guide.instructions || '';
  const account = guide.account || '';
  if (!instructions && !account) return '';
  return [
    '<div class="payment-guide">',
    '<h2>付款指引</h2>',
    instructions ? '<p>' + escapeHtml(instructions) + '</p>' : '',
    account ? '<p>收款账户：' + escapeHtml(account) + '</p>' : '',
    order?.order_no ? '<p>付款备注建议填写订单号：' + escapeHtml(order.order_no) + '</p>' : '',
    '</div>'
  ].join('');
}

function renderOrderReceipt(form, order, phone) {
  const receipt = form.querySelector('[data-order-receipt]');
  if (!receipt) return;
  const orderNo = order.order_no || order.id || '';
  const href = getOrderLookupHref(orderNo, phone);
  const guideHtml = renderPaymentGuide(readPaymentGuide(form), { ...order, order_no: orderNo });
  receipt.hidden = false;
  receipt.innerHTML = [
    '<strong>订单提交成功</strong>',
    '<p>订单号：' + escapeHtml(orderNo || '-') + '</p>',
    '<p>金额：' + escapeHtml(order.currency || 'CNY') + ' ' + escapeHtml(order.total_amount || '-') + '</p>',
    guideHtml,
    '<a class="btn primary" href="' + escapeHtml(href) + '">查看订单状态</a>'
  ].join('');
}

function renderPaymentProofForm(order, phone) {
  if (order.payment_status !== 'pending') return '';
  return [
    '<form class="payment-proof-form order-lookup-block" data-api="/api/orders/payment-proof">',
    '<h2>提交付款凭证</h2>',
    '<input type="hidden" name="order_no" value="' + escapeHtml(order.order_no || '') + '">',
    '<input type="hidden" name="phone" value="' + escapeHtml(phone || '') + '">',
    '<div class="form-grid compact">',
    '<input type="number" name="amount" min="0.01" step="0.01" placeholder="实际付款金额" required>',
    '<input type="text" name="reference" maxlength="120" placeholder="流水号或截图编号" required>',
    '</div>',
    '<textarea name="note" maxlength="500" placeholder="补充付款账户、付款时间或其他说明"></textarea>',
    '<button type="submit">提交付款凭证</button>',
    '<p class="form-status" data-payment-proof-status></p>',
    '</form>'
  ].join('');
}

function renderServiceRequestForm(order, phone) {
  return [
    '<form class="service-request-form order-lookup-block" data-api="/api/orders/service-request">',
    '<h2>订单服务</h2>',
    '<input type="hidden" name="order_no" value="' + escapeHtml(order.order_no || '') + '">',
    '<input type="hidden" name="phone" value="' + escapeHtml(phone || '') + '">',
    '<select name="type">',
    '<option value="催发货">催发货</option>',
    '<option value="修改收货信息">修改收货信息</option>',
    '<option value="售后问题">售后问题</option>',
    '<option value="其他服务">其他服务</option>',
    '</select>',
    '<textarea name="message" maxlength="500" placeholder="请填写需要客服处理的内容，例如新的收货地址、售后问题或催发货说明" required></textarea>',
    '<button type="submit">提交服务请求</button>',
    '<p class="form-status" data-service-request-status></p>',
    '</form>'
  ].join('');
}

function renderServiceTimeline(order) {
  const items = Array.isArray(order.service_timeline) ? order.service_timeline : [];
  if (!items.length) return '';
  return [
    '<div class="service-timeline order-lookup-block">',
    '<h2>服务进度</h2>',
    items.map(function (item) {
      return [
        '<div class="service-timeline-item">',
        '<span></span>',
        '<div><small>' + escapeHtml(item.time || '') + '</small>',
        '<p>' + escapeHtml(item.text || '') + '</p></div>',
        '</div>'
      ].join('');
    }).join(''),
    '</div>'
  ].join('');
}

function renderShipmentCard(order) {
  const tracking = [order.tracking_company, order.tracking_no].filter(Boolean).join(' / ') || '';
  let title = '等待付款确认';
  let text = '客服会在核对付款后安排备货。';
  if (order.payment_status === 'paid' && !['shipped', 'finished'].includes(order.fulfillment_status)) {
    title = '已收款，等待发货';
    text = '订单已进入备货流程，发货后会显示物流信息。';
  }
  if (order.fulfillment_status === 'shipped') {
    title = '订单已发货';
    text = tracking || '订单已发货，物流单号稍后同步。';
  }
  if (order.fulfillment_status === 'finished') {
    title = '订单已完成';
    text = tracking || '订单已完成。';
  }
  return [
    '<div class="shipment-card order-lookup-block">',
    '<h2>配送进度</h2>',
    '<strong>' + escapeHtml(title) + '</strong>',
    '<p>' + escapeHtml(text) + '</p>',
    '<p>支付时间：' + escapeHtml(order.paid_at || '-') + '</p>',
    '<p>发货时间：' + escapeHtml(order.shipped_at || '-') + '</p>',
    '</div>'
  ].join('');
}

function renderOrderLookup(order) {
  const items = Array.isArray(order.items) ? order.items : [];
  const tracking = [order.tracking_company, order.tracking_no].filter(Boolean).join(' / ') || '暂无物流信息';
  const latest = readLatestOrder();
  const phone = latest.phone || new URLSearchParams(location.search).get('phone') || '';
  const guideHtml = order.payment_status === 'pending' ? renderPaymentGuide(readPaymentGuide(document), order) : '';
  const proofHtml = renderPaymentProofForm(order, phone);
  const serviceHtml = renderServiceRequestForm(order, phone);
  const timelineHtml = renderServiceTimeline(order);
  return [
    '<div class="order-status-grid">',
    '<div><span>订单号</span><strong>' + escapeHtml(order.order_no) + '</strong></div>',
    '<div><span>支付状态</span><strong>' + escapeHtml(orderStatusLabel('payment', order.payment_status)) + '</strong></div>',
    '<div><span>履约状态</span><strong>' + escapeHtml(orderStatusLabel('fulfillment', order.fulfillment_status)) + '</strong></div>',
    '<div><span>订单金额</span><strong>' + escapeHtml(order.currency || 'CNY') + ' ' + escapeHtml(order.total_amount || '0.00') + '</strong></div>',
    '</div>',
    '<div class="order-lookup-block"><h2>商品明细</h2>',
    items.map(function (item) {
      return '<p>' + escapeHtml(item.title || '未命名商品') + ' x ' + escapeHtml(item.quantity || 1) + '，' + escapeHtml(item.price || 0) + '</p>';
    }).join('') || '<p>暂无商品明细</p>',
    '</div>',
    '<div class="order-lookup-block"><h2>物流信息</h2>',
    '<p>' + escapeHtml(tracking) + '</p>',
    '<p>支付时间：' + escapeHtml(order.paid_at || '-') + '</p>',
    '<p>发货时间：' + escapeHtml(order.shipped_at || '-') + '</p>',
    '</div>',
    renderShipmentCard(order),
    timelineHtml,
    guideHtml,
    proofHtml,
    serviceHtml,
    '<form class="customer-note-form order-lookup-block" data-api="/api/orders/customer-note">',
    '<h2>补充说明</h2>',
    '<input type="hidden" name="order_no" value="' + escapeHtml(order.order_no || '') + '">',
    '<input type="hidden" name="phone" value="' + escapeHtml(phone) + '">',
    '<select name="type">',
    '<option value="付款说明">付款说明</option>',
    '<option value="开票需求">开票需求</option>',
    '<option value="售后说明">售后说明</option>',
    '<option value="补充说明">补充说明</option>',
    '</select>',
    '<textarea name="note" maxlength="500" placeholder="填写付款截图编号、开票抬头、售后问题或其他补充说明" required></textarea>',
    '<button type="submit">提交说明</button>',
    '<p class="form-status" data-customer-note-status></p>',
    '</form>'
  ].join('');
}

function submitCustomerNote(form) {
  const api = form.getAttribute('data-api');
  const status = form.querySelector('[data-customer-note-status]');
  if (!api || location.protocol === 'file:') {
    if (status) status.textContent = '演示站暂不能提交说明，部署后会同步到后台订单时间线。';
    return;
  }

  const formData = new FormData(form);
  const payload = {
    order_no: formData.get('order_no') || '',
    phone: formData.get('phone') || '',
    type: formData.get('type') || '补充说明',
    note: formData.get('note') || ''
  };
  if (status) status.textContent = '正在提交说明...';
  form.querySelectorAll('button, textarea, select').forEach(function (item) {
    item.disabled = true;
  });

  fetch(api, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(function (response) { return response.json(); })
    .then(function (result) {
      if (!result.success) throw new Error(result.message || '说明提交失败');
      form.note.value = '';
      if (status) status.textContent = '说明已提交，客服会在后台订单时间线中查看。';
    })
    .catch(function (error) {
      if (status) status.textContent = error.message || '说明提交失败，请稍后再试。';
    })
    .finally(function () {
      form.querySelectorAll('button, textarea, select').forEach(function (item) {
        item.disabled = false;
      });
    });
}

function submitPaymentProof(form) {
  const api = form.getAttribute('data-api');
  const status = form.querySelector('[data-payment-proof-status]');
  if (!api || location.protocol === 'file:') {
    if (status) status.textContent = '演示站暂不能提交付款凭证，部署后会同步到后台订单时间线。';
    return;
  }

  const formData = new FormData(form);
  const payload = {
    order_no: formData.get('order_no') || '',
    phone: formData.get('phone') || '',
    amount: formData.get('amount') || '',
    reference: formData.get('reference') || '',
    note: formData.get('note') || ''
  };
  if (status) status.textContent = '正在提交付款凭证...';
  form.querySelectorAll('button, input, textarea').forEach(function (item) {
    item.disabled = true;
  });

  fetch(api, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(function (response) { return response.json(); })
    .then(function (result) {
      if (!result.success) throw new Error(result.message || '付款凭证提交失败');
      form.amount.value = '';
      form.reference.value = '';
      form.note.value = '';
      if (status) status.textContent = '付款凭证已提交，客服核对后会更新支付状态。';
    })
    .catch(function (error) {
      if (status) status.textContent = error.message || '付款凭证提交失败，请稍后再试。';
    })
    .finally(function () {
      form.querySelectorAll('button, input, textarea').forEach(function (item) {
        item.disabled = false;
      });
    });
}

function submitServiceRequest(form) {
  const api = form.getAttribute('data-api');
  const status = form.querySelector('[data-service-request-status]');
  if (!api || location.protocol === 'file:') {
    if (status) status.textContent = '演示站暂不能提交服务请求，部署后会同步到后台订单时间线。';
    return;
  }

  const formData = new FormData(form);
  const payload = {
    order_no: formData.get('order_no') || '',
    phone: formData.get('phone') || '',
    type: formData.get('type') || '其他服务',
    message: formData.get('message') || ''
  };
  if (status) status.textContent = '正在提交服务请求...';
  form.querySelectorAll('button, select, textarea').forEach(function (item) {
    item.disabled = true;
  });

  fetch(api, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(function (response) { return response.json(); })
    .then(function (result) {
      if (!result.success) throw new Error(result.message || '服务请求提交失败');
      form.message.value = '';
      if (status) status.textContent = '服务请求已提交，客服会在后台订单时间线中处理。';
    })
    .catch(function (error) {
      if (status) status.textContent = error.message || '服务请求提交失败，请稍后再试。';
    })
    .finally(function () {
      form.querySelectorAll('button, select, textarea').forEach(function (item) {
        item.disabled = false;
      });
    });
}

function submitOrderLookup(form) {
  const api = form.getAttribute('data-api');
  const status = document.querySelector('[data-order-lookup-status]');
  const resultBox = document.querySelector('[data-order-lookup-result]');
  if (!api || location.protocol === 'file:') {
    if (status) status.textContent = '演示站暂不能查询订单，部署后会连接后台订单系统。';
    return;
  }

  const formData = new FormData(form);
  const payload = {
    order_no: formData.get('order_no') || '',
    phone: formData.get('phone') || ''
  };
  if (status) status.textContent = '正在查询订单...';
  if (resultBox) resultBox.innerHTML = '';

  fetch(api, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(function (response) { return response.json(); })
    .then(function (result) {
      if (!result.success) throw new Error(result.message || '订单查询失败');
      saveLatestOrder(payload.order_no, payload.phone);
      if (status) status.textContent = '查询成功';
      if (resultBox) resultBox.innerHTML = renderOrderLookup(result.data || {});
    })
    .catch(function (error) {
      if (status) status.textContent = error.message || '订单查询失败，请检查订单号和手机号。';
    });
}

function initOrderLookup() {
  const page = document.querySelector('[data-order-lookup-page]');
  if (!page) return;

  const form = page.querySelector('.order-lookup-form');
  const params = new URLSearchParams(location.search);
  const latest = readLatestOrder();
  const orderNo = params.get('order_no') || latest.order_no || '';
  const phone = params.get('phone') || latest.phone || '';
  if (orderNo) form.order_no.value = orderNo;
  if (phone) form.phone.value = phone;
  if (orderNo && phone) {
    submitOrderLookup(form);
  }
}

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

document.addEventListener('DOMContentLoaded', function () {
  initStaticSearch();
  initOrderLookup();
});
