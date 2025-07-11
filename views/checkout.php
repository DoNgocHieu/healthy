<?php
// views/checkout.php (include ở cuối cart.php)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
$pdo = getDb();
if ($_SERVER['REQUEST_METHOD']==='POST'
    && ($_POST['ajax'] ?? '')==='1'
    && ($_POST['action'] ?? '')==='edit_checkout'
) {
    // thực hiện UPDATE như cũ…
    $stmt = $pdo->prepare("UPDATE user_addresses
                              SET fullname=:fn, address=:ad, phone=:ph
                            WHERE id=:aid AND user_id=:uid");
    $stmt->execute([ /* ... */ ]);

    header('Content-Type: application/json');
    echo json_encode([
      'status'   => 'ok',
      'id'       => (int)$_POST['address_id'],
      'fullname' => $_POST['fullname'],
      'address'  => $_POST['address'],
      'phone'    => $_POST['phone']
    ]);
    exit;
}
// Nếu chưa login, redirect
if (empty($_SESSION['user_id'])) {
    header('Location: layout.php?page=login');
    exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST'
    && isset($_POST['ajax'])
    && $_POST['ajax']==='1'
    && ($_POST['action'] ?? '')==='edit_checkout'
) {
    $stmt = $pdo->prepare("
      UPDATE user_addresses
         SET fullname = :fn,
             address  = :ad,
             phone    = :ph
       WHERE id = :aid
         AND user_id = :uid
    ");
    $stmt->execute([
      ':fn'  => $_POST['fullname'],
      ':ad'  => $_POST['address'],
      ':ph'  => $_POST['phone'],
      ':aid' => (int)$_POST['address_id'],
      ':uid' => $_SESSION['user_id']
    ]);
    // Trả về JSON chứa dữ liệu đã lưu
    header('Content-Type: application/json');
    echo json_encode([
      'status'   => 'ok',
      'id'       => (int)$_POST['address_id'],
      'fullname' => $_POST['fullname'],
      'address'  => $_POST['address'],
      'phone'    => $_POST['phone']
    ]);
    exit;
}
// Xử lý POST từ form add/edit/confirm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'select_address') {
        // chọn địa chỉ cho cart
        $_SESSION['selected_address_id'] = (int)$_POST['address_id'];
    }
    elseif ($action === 'edit_checkout') {
        // edit địa chỉ
        $stmt = $pdo->prepare("
          UPDATE user_addresses
             SET fullname=:fn, address=:ad, phone=:ph
           WHERE id=:aid AND user_id=:uid
        ");
        $stmt->execute([
          ':fn'   => $_POST['fullname'],
          ':ad'   => $_POST['address'],
          ':ph'   => $_POST['phone'],
          ':aid'  => (int)$_POST['address_id'],
          ':uid'  => $_SESSION['user_id']
        ]);
        // giữ nguyên selected
        $_SESSION['selected_address_id'] = (int)$_POST['address_id'];

        // Nếu là AJAX request thì trả về JSON
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'ok',
                'id' => (int)$_POST['address_id'],
                'fullname' => $_POST['fullname'],
                'address' => $_POST['address'],
                'phone' => $_POST['phone']
            ]);
            exit;
        }
    }
    elseif ($action === 'add_checkout') {
        // thêm địa chỉ
        $ins = $pdo->prepare("
          INSERT INTO user_addresses (user_id,fullname,address,phone)
          VALUES (:uid,:fn,:ad,:ph)
        ");
        $ins->execute([
          ':uid'=>$_SESSION['user_id'],
          ':fn' =>$_POST['fullname'],
          ':ad' =>$_POST['address'],
          ':ph' =>$_POST['phone']
        ]);

        $newId = $pdo->lastInsertId();
        $_SESSION['selected_address_id'] = $newId;

        // Nếu là AJAX request thì trả về JSON
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'ok',
                'id' => $newId,
                'fullname' => $_POST['fullname'],
                'address' => $_POST['address'],
                'phone' => $_POST['phone']
            ]);
            exit;
        }
    }

    // Nếu là form submit thường, chuyển đến trang order_confirm
    header('Location: layout.php?page=order_confirm');
    exit;
}

// Lấy danh sách địa chỉ
$stmt = $pdo->prepare("
    SELECT id, fullname, phone, address, is_default
      FROM user_addresses
     WHERE user_id = :uid
  ORDER BY is_default DESC, id ASC
");
$stmt->execute([':uid'=>$_SESSION['user_id']]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xác định cái radio đang chọn
$selectedId = $_SESSION['selected_address_id']
            ?? ($addresses[0]['id'] ?? null);
?>
<link rel="stylesheet" href="../css/checkout.css">

<!-- Form ẩn submit chọn địa chỉ -->
<form id="selectAddressForm" method="post" style="display:none">
  <input type="hidden" name="action" value="select_address">
  <input type="hidden" id="selectedAddressId" name="address_id">
</form>

<!-- Nút chuyển đến trang xác nhận đơn hàng -->
<a href="layout.php?page=order_confirm" class="btn-confirm-order">Xác nhận đơn hàng</a>

<!-- Modal chọn/add/edit địa chỉ -->
<div id="addressModal" class="modal">
  <div class="modal-content">
    <button class="close">&times;</button>
    <h4>Chọn / Thêm / Sửa địa chỉ giao hàng</h4>

<div id="address-list">
  <?php foreach($addresses as $a): ?>
    <div class="address-item <?= $a['is_default'] ? 'default' : '' ?>" data-id="<?= $a['id'] ?>">
      <!-- VIEW MODE -->
      <div class="view-mode">
        <label>
          <input type="radio" name="addr"
                 value="<?= $a['id'] ?>"
                 <?= $a['id'] == $selectedId ? 'checked' : '' ?>>
          <div class="address-field"><b>Họ & tên:</b> <?= htmlspecialchars($a['fullname']) ?></div>
          <div class="address-field"><b>Địa chỉ:</b> <?= nl2br(htmlspecialchars($a['address'])) ?></div>
          <div class="address-field"><b>SĐT:</b> <?= htmlspecialchars($a['phone']) ?></div>
          <?php if ($a['is_default']): ?>
            <span class="badge">Mặc định</span>
          <?php endif; ?>
        </label>
        <button type="button" class="btn-edit">Sửa</button>
      </div>

        <!-- EDIT FORM -->
 <form class="edit-form" method="post" style="display:none; margin-top:0.5rem;">
    <input type="hidden" name="action" value="edit_checkout">
    <input type="hidden" name="address_id" value="<?= $a['id']?>">
    <div>
      <label>
        Họ & tên:
        <input type="text" name="fullname"
          value="<?=htmlspecialchars($a['fullname'],ENT_QUOTES)?>" required>
      </label>
    </div>
    <div>
      <label>
        Địa chỉ:
        <input type="text" name="address"
          value="<?=htmlspecialchars($a['address'],ENT_QUOTES)?>" required>
      </label>
    </div>
    <div>
      <label>
        SDT:
        <input type="text" name="phone"
          value="<?=htmlspecialchars($a['phone'],ENT_QUOTES)?>" required>
      </label>
    </div>
    <button type="submit" class="btn-save">Lưu</button>
    <button type="button" class="btn-cancel">Hủy</button>
  </form>

      </div>
      <?php endforeach; ?>
    </div>

    <!-- ADD NEW -->
    <button type="button" id="btnAddAddress" class="btn btn-add">Thêm địa chỉ mới</button>
    <button type="button" id="btnConfirmAddress" class="btn btn-save-final">Xác nhận địa chỉ</button>

    <!-- ADD FORM -->
    <form id="addAddressForm" method="post" action="?page=checkout" style="display:none; margin-top:1rem;">
      <input type="hidden" name="action" value="add_checkout">
      <div><label>Họ & tên: <input type="text" name="fullname" required></label></div>
      <div><label>Địa chỉ: <input type="text" name="address" required></label></div>
      <div><label>SDT: <input type="text" name="phone" required></label></div>
      <button type="submit" class="btn-save">Lưu mới</button>
      <button type="button" id="cancelAdd" class="btn-cancel">Hủy</button>
    </form>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('addressModal');
  const openBtn = document.getElementById('btnSelectAddress');
  const closeBtn = modal.querySelector('.close');
  const list = document.getElementById('address-list');
  const selForm = document.getElementById('selectAddressForm');
  const hidId = document.getElementById('selectedAddressId');
  const btnConfirmAll = document.getElementById('btnConfirmAddress');
  const btnAdd = document.getElementById('btnAddAddress');
  const addForm = document.getElementById('addAddressForm');
  const cancelAdd = document.getElementById('cancelAdd');

  // Xử lý đóng/mở modal
  openBtn?.addEventListener('click', e => {
    e.preventDefault();
    modal.classList.add('show');
  });

  closeBtn?.addEventListener('click', () => {
    modal.classList.remove('show');
  });

  modal?.addEventListener('click', e => {
    if (e.target === modal) {
      modal.classList.remove('show');
    }
  });

  // Xử lý form thêm địa chỉ
  addForm?.addEventListener('submit', e => {
    e.preventDefault();
    const formData = new FormData(addForm);
    formData.append('ajax', '1');

    fetch(window.location.href, {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'ok') {
        // Tự động chọn địa chỉ mới
        hidId.value = data.id;
        selForm.submit();

        // Đóng modal và chuyển trang
        modal.classList.remove('show');
        setTimeout(() => {
          window.location.href = 'layout.php?page=order_confirm';
        }, 500);
      } else {
        alert('Thêm địa chỉ thất bại');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Đã có lỗi xảy ra');
    });
  });

  // Xử lý nút xác nhận
  btnConfirmAll?.addEventListener('click', () => {
    const checked = list.querySelector('input[name="addr"]:checked');
    if (!checked) {
      alert('Vui lòng chọn 1 địa chỉ!');
      return;
    }

    hidId.value = checked.value;
    selForm.submit();

    modal.classList.remove('show');
    setTimeout(() => {
      window.location.href = 'layout.php?page=order_confirm';
    }, 500);
  });

  // Ẩn/hiện form thêm mới
  btnAdd?.addEventListener('click', () => {
    addForm.style.display = 'block';
    btnAdd.style.display = 'none';
    btnConfirmAll.style.display = 'none';
  });

  cancelAdd?.addEventListener('click', () => {
    addForm.style.display = 'none';
    btnAdd.style.display = '';
    btnConfirmAll.style.display = '';
  });

  // Xử lý sửa địa chỉ
  list?.addEventListener('click', e => {
    if (e.target.matches('.btn-edit')) {
      const item = e.target.closest('.address-item');
      item.querySelector('.view-mode').style.display = 'none';
      item.querySelector('.edit-form').style.display = 'block';
    }

    if (e.target.matches('.btn-cancel')) {
      const item = e.target.closest('.address-item');
      const form = item.querySelector('.edit-form');
      form.reset();
      form.style.display = 'none';
      item.querySelector('.view-mode').style.display = '';
    }

    if (e.target.matches('.btn-save')) {
      const form = e.target.closest('.edit-form');
      const formData = new FormData(form);
      formData.append('ajax', '1');

      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'ok') {
          const item = form.closest('.address-item');
          const vm = item.querySelector('.view-mode');

          vm.innerHTML = `
            <label>
              <input type="radio" name="addr" value="${data.id}">
              <strong>${data.fullname}</strong><br>
              ${data.address.replace(/\n/g,'<br>')}<br>
              ${data.phone}
            </label>
            <button type="button" class="btn-edit">Sửa</button>
          `;

          form.style.display = 'none';
          vm.style.display = '';
        } else {
          alert('Cập nhật thất bại');
        }
      })
      .catch(err => {
        console.error(err);
        alert('Đã có lỗi xảy ra');
      });
    }
  });
});
</script>
