;(function(window, document) {
  'use strict';
  window.handleDecrease = id => window.decrement(id);
  window.handleIncrease = id => window.increment(id);

  const BASE_PATH      = `${window.location.origin}/healthy/views/layout.php`;
  const CART_ENDPOINT  = `${BASE_PATH}?page=cart`;
  const LOGIN_ENDPOINT = `${BASE_PATH}?page=login`;

  function checkLoginStatus() {
    if (!window.isLoggedIn) {
      alert('Vui lòng đăng nhập');
      window.location.href = LOGIN_ENDPOINT;
      return false;
    }
    return true;
  }

  function debugLog(...args) {
    console.log('[CartSync]', ...args);
  }

  function getStock(container) {
    const s = parseInt(container.dataset.stock, 10);
    return isNaN(s) || s < 0 ? Number.MAX_SAFE_INTEGER : s;
  }

  function updateCartIcon() {
    const cart  = JSON.parse(localStorage.getItem('cart')) || {};
    const total = Object.values(cart).reduce((sum, it) => sum + (it.qty||0), 0);
    const icon  = document.querySelector('#cart-icon');
    if (!icon) return;
    icon.innerHTML = `
      <i class="fa fa-shopping-cart"></i>
      ${total>0?`<span class="cart-badge">${total}</span>`:''}
    `;
  }

  function syncWithServer(id, qty) {
    debugLog('sync', id, qty);
    fetch(CART_ENDPOINT, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: `ajax=1&id=${id}&qty=${qty}`
    })
    .then(r => r.text())
    .then(text => {
      debugLog('resp raw', text);
      let data;
      try { data = JSON.parse(text); }
      catch (e) {
        console.error('Invalid JSON', text);
        return alert('Server trả về không hợp lệ');
      }
      handleServerResponse(id, data);
    })
    .catch(err => {
      console.error('AJAX error', err);
      alert('Không thể kết nối server');
    });
  }

  function handleServerResponse(id, data) {
    if (data.status === 'ok') {
      const line = document.querySelector(`.cart-line-total[data-id="${id}"]`);
      if (line) line.textContent = data.line_total;
    }
    else if (data.status === 'max_limit') {
      alert(`Chỉ còn ${data.stock_qty} sản phẩm`);
      forceQty(id, data.stock_qty);
    }
    else {
      alert('Cập nhật thất bại');
    }

    updateCartIcon();
    initializeControls(); 
  }

  function forceQty(id, max) {
    const inp = document.getElementById(`qty-input-${id}`);
    if (inp) inp.value = max;
    const cart = JSON.parse(localStorage.getItem('cart'))||{};
    cart[id] = {qty:max, stock_qty:max};
    localStorage.setItem('cart', JSON.stringify(cart));
  }
  function renderContainer(container, id, qty) {
    const stock = getStock(container);

    // chưa login → show lock
    if (!window.isLoggedIn) {
      container.innerHTML = `
        <button class="lock-btn"
                onclick="location.href='${LOGIN_ENDPOINT}'">
          <i class="fa-solid fa-lock"></i>
        </button>`;
      return;
    }

    //login & qty=0 → thêm vào giỏ
    if (qty <= 0) {
      container.innerHTML = `
        <button class="add-btn"
                onclick="event.stopPropagation(); addToCart(${id});"
                style="background:none;border:none;cursor:pointer;">
          <i class="fa fa-shopping-cart" style="font-size:1.5em;color:#4caf50;"></i>
        </button>`;
      return;
    }

    // login & qty>0 → ± input
    container.innerHTML = `
      <button onclick="event.stopPropagation(); decrement(${id});">
        <i class="fa-solid fa-minus"></i>
      </button>
      <input
        id="qty-input-${id}"
        class="qty-display"
        type="number"
        min="1"
        max="${stock}"
        value="${qty}"
        oninput="handleQtyInput(${id});"
        onblur="handleQtyBlur(${id});"
      />
      <button onclick="event.stopPropagation(); increment(${id});">
        <i class="fa-solid fa-plus"></i>
      </button>`;
  }

  // re-scan và render lại **tất cả** containers + patch modal
  function initializeControls() {
    const cart = JSON.parse(localStorage.getItem('cart'))||{};

    // dynamic containers
    document.querySelectorAll('[id^="cart-controls-"]').forEach(c => {
      const id  = c.id.replace('cart-controls-','');
      const q   = cart[id]?.qty||0;
      renderContainer(c, id, q);
    });

    // patch những input cứng trong modal item.php
    document.querySelectorAll('input[id^="qty-input-"]').forEach(inp => {
      const id  = inp.id.replace('qty-input-','');
      const q   = cart[id]?.qty||0;
      inp.value = q;
      // gán nút trước và sau
      const dec = inp.previousElementSibling;
      const inc = inp.nextElementSibling;
      if (dec) dec.onclick = e => { e.stopPropagation(); decrement(id); };
      if (inc) inc.onclick = e => { e.stopPropagation(); increment(id); };
      inp.oninput = () => handleQtyInput(id);
      inp.onblur  = () => handleQtyBlur(id);
    });

    // patch icon thêm trong modal
    document.querySelectorAll('.add-to-cart-icon').forEach(icon => {
      const m = icon.getAttribute('onclick')?.match(/addToCart\((\d+)\)/);
      if (m) icon.onclick = e => { e.stopPropagation(); addToCart(m[1]); };
    });
  }

  window.addToCart = id => {
    if (!checkLoginStatus()) return;
    const cont = document.getElementById(`cart-controls-${id}`);
    const stock= getStock(cont);
    const cart = JSON.parse(localStorage.getItem('cart'))||{};
    cart[id] = cart[id]||{qty:0,stock_qty:stock};
    cart[id].qty = Math.min(cart[id].qty+1, stock);
    localStorage.setItem('cart', JSON.stringify(cart));
    syncWithServer(id, cart[id].qty);
  };

  window.increment = id => {
    const cont = document.getElementById(`cart-controls-${id}`);
    const stock= getStock(cont);
    const cart = JSON.parse(localStorage.getItem('cart'))||{};
    cart[id] = cart[id]||{qty:0,stock_qty:stock};
    if (cart[id].qty < stock) {
      cart[id].qty++;
      localStorage.setItem('cart', JSON.stringify(cart));
      syncWithServer(id, cart[id].qty);
    } else {
      alert(`Chỉ còn ${stock} sản phẩm`);
    }
  };

  window.decrement = id => {
    const cont = document.getElementById(`cart-controls-${id}`);
    const cart = JSON.parse(localStorage.getItem('cart'))||{};
    if (!cart[id] || cart[id].qty<=1) {
      cart[id] = {qty:0,stock_qty:cart[id]?.stock_qty||getStock(cont)};
      localStorage.setItem('cart', JSON.stringify(cart));
      syncWithServer(id, 0);
    } else {
      cart[id].qty--;
      localStorage.setItem('cart', JSON.stringify(cart));
      syncWithServer(id, cart[id].qty);
    }
  };
    window.handleQuantity = (id, delta, stock) => {
    // kiểm tra login
    if (!window.isLoggedIn) {
      alert('Vui lòng đăng nhập');
      return window.location.href = LOGIN_ENDPOINT;
    }

    // load cart, khởi tạo nếu chưa có
    const cart = JSON.parse(localStorage.getItem('cart')) || {};
    cart[id] = cart[id] || { qty: 0, stock_qty: stock };

    // tính newQty và clamp vào [0, stock]
    let newQty = (cart[id].qty || 0) + delta;
    if (newQty > stock) {
      alert(`Chỉ còn ${stock} sản phẩm`);
      newQty = stock;
    }
    if (newQty < 0) {
      newQty = 0;
    }

    // cập nhật localStorage
    cart[id].qty = newQty;
    localStorage.setItem('cart', JSON.stringify(cart));

    // cập nhật ngay giá trị trên input (nếu có)
    const inp = document.getElementById(`qty-input-${id}`);
    if (inp) inp.value = newQty;

    // gửi lên server
    syncWithServer(id, newQty);
  };

  // alias cho 2 tên cũ
  window.handleIncrease = (id, delta, stock) => window.handleQuantity(id, delta, stock);
  window.handleDecrease = (id, stock)        => window.handleQuantity(id, -1, stock);
  function bootstrap() {
    if (!window.isLoggedIn) {
      // nếu chưa login thì xóa cache cart
      localStorage.removeItem('cart');
      updateCartIcon();
      initializeControls();
      return;
    }
    // load cart từ server
    fetch(`${CART_ENDPOINT}&ajax=load`,{credentials:'same-origin'})
      .then(r=>r.json())
      .then(data=>{
        if (data.status==='ok' && data.cart) {
          localStorage.setItem('cart',JSON.stringify(data.cart));
        } else {
          localStorage.removeItem('cart');
        }
      })
      .catch(()=> localStorage.removeItem('cart'))
      .finally(()=>{
        updateCartIcon();
        initializeControls();
      });
  }

  // DOMContentLoaded
  document.addEventListener('DOMContentLoaded', bootstrap);

  // Nếu script load sau DOMContentLoaded (ví dụ inject modal), khởi chạy ngay
  if (document.readyState!=='loading') bootstrap();
  function showStockInfo(stock) {
    alert(`Hiện còn ${stock} sản phẩm trong kho.`);
  }
  function loadReviews(id_food) {
    console.log('GỌI loadReviews với id:', id_food);
    const reviewList = document.getElementById('review-list');
    if (!reviewList) {
      console.log('Không tìm thấy #review-list');
      return;
    }
    reviewList.innerHTML = '<div>Đang tải đánh giá...</div>';
    fetch(`/healthy/api/get_reviews.php?id_food=${id_food}`)
      .then(r => r.json())
      .then(data => {
        if (!data.reviews || !data.reviews.length) {
          reviewList.innerHTML = '<div>Chưa có đánh giá nào cho món này.</div>';
          return;
        }
        reviewList.innerHTML = data.reviews.map(rv => {
          let photosHtml = '';
          if (rv.photos) {
            try {
              let arr = [];
              if (rv.photos.trim().startsWith('[')) {
                arr = JSON.parse(rv.photos);
              } else if (rv.photos.trim() !== '') {
                arr = [rv.photos.trim()];
              }
              if (Array.isArray(arr) && arr.length) {
                photosHtml = '<div class="review-photos" style="margin:6px 0 0 4px;display:flex;gap:6px;flex-wrap:wrap;">' +
                  arr.map(url => `<img src="${url}" style="max-width:70px;max-height:70px;border-radius:6px;border:1px solid #eee;">`).join('') +
                  '</div>';
              }
            } catch(e){}
          }
          return `
            <div class="review-item" style="border-bottom:1px solid #eee;padding:8px 0;">
              <div class="review-head" style="display:flex;align-items:center;gap:10px;">
                <span class="review-user" style="font-weight:600;">${rv.username}</span>
                <span class="review-star" style="color:#f5b301;">${'★'.repeat(rv.star)}${'☆'.repeat(5-rv.star)}</span>
                <span class="review-date" style="color:#888;font-size:0.95em;">${rv.date}</span>
              </div>
              <div class="review-detail" style="margin-left:4px;">${rv.detail}</div>
              ${photosHtml}
            </div>
          `;
        }).join('');
      })
      .catch(() => {
        reviewList.innerHTML = '<div>Lỗi tải đánh giá.</div>';
      });
  }
  window.loadReviews = loadReviews;
  function addReviewHandler(id_food) {
    const form = document.getElementById('add-review-form');
    if (!form) return;
    form.onsubmit = function(e) {
      e.preventDefault();
      const username = form.username.value.trim();
      const star     = parseInt(form.star.value, 10);
      const detail   = form.detail.value.trim();
      if (!username || !star || !detail) {
        alert('Vui lòng nhập đầy đủ thông tin đánh giá!');
        return;
      }
      fetch('/healthy/api/add_review.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `id_food=${id_food}&username=${encodeURIComponent(username)}&star=${star}&detail=${encodeURIComponent(detail)}`
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          form.reset();
          loadReviews(id_food);
        } else {
          alert(data.msg || 'Lỗi gửi đánh giá!');
        }
      })
      .catch(() => alert('Lỗi kết nối server!'));
    };
  }
  window.addReviewHandler = addReviewHandler;
})(window, document);
