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
        // set mới cho cart
        $_SESSION['selected_address_id'] = $pdo->lastInsertId();
    }
    // trở lại cart để refresh modal
    header('Location: '.$_SERVER['REQUEST_URI']);
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
  const modal    = document.getElementById('addressModal');
  const openBtn  = document.getElementById('btnSelectAddress');
  const closeBtn = modal.querySelector('.close');
  const list     = document.getElementById('address-list');
  const selForm  = document.getElementById('selectAddressForm');
  const hidId    = document.getElementById('selectedAddressId');
  const btnConfirmAll = document.getElementById('btnConfirmAddress');
  const btnAdd       = document.getElementById('btnAddAddress');
  const addForm      = document.getElementById('addAddressForm');
  const cancelAdd    = document.getElementById('cancelAdd');
document.addEventListener('DOMContentLoaded', () => {
  const AJAX_URL = '<?= htmlspecialchars($_SERVER['PHP_SELF'] . "?page=cart") ?>';

  document.getElementById('address-list')?.addEventListener('click', e => {
    // Hủy thì như cũ
    if (e.target.matches('.btn-cancel')) {
      const item = e.target.closest('.address-item');
      item.querySelector('.edit-form').reset();
      item.querySelector('.edit-form').style.display = 'none';
      item.querySelector('.view-mode').style.display   = '';
    }

    // Lưu qua AJAX
    if (e.target.matches('.btn-save')) {
      const form = e.target.closest('.edit-form');
      const data = new FormData(form);
      data.append('ajax', '1');
      data.append('action', 'edit_checkout');

      fetch(AJAX_URL, {
        method: 'POST',
        body: data
      })
      .then(res => res.json())
      .then(json => {
        if (json.status === 'ok') {
          // cập nhật view-mode
          const item = form.closest('.address-item');
          const vm   = item.querySelector('.view-mode');
          vm.innerHTML = `
            <label>
              <input type="radio" name="addr" value="${json.id}">
              <strong>${json.fullname}</strong><br>
              ${json.address.replace(/\n/g,'<br>')}<br>
              ${json.phone}
            </label>
            <button type="button" class="btn-edit">Sửa</button>
          `;
          // ẩn form, show view-mode
          form.style.display = 'none';
          vm.style.display   = '';
        } else {
          alert('Lưu thất bại, thử lại.');
        }
      })
      .catch(() => alert('Lỗi mạng, không thể lưu.'));
    }
  });
});
  // Mở modal
  openBtn?.addEventListener('click', e => {
    e.preventDefault();
    modal.classList.add('show');
  });
  // Đóng modal
  closeBtn.addEventListener('click', () => modal.classList.remove('show'));
  modal.addEventListener('click', e => { if (e.target===modal) modal.classList.remove('show'); });

  // Xác nhận 1 item
  list.addEventListener('click', e => {
    const btn = e.target.closest('.btn-confirm-item');
    if (!btn) return;
    const item = btn.closest('.address-item');
    const id   = item.dataset.id;
    // chọn radio
    item.querySelector('input[name="addr"]').checked = true;
    // submit
    hidId.value = id;
    selForm.submit();
  });

  // Xác nhận chung
  btnConfirmAll?.addEventListener('click', () => {
    const checked = list.querySelector('input[name="addr"]:checked');
    if (!checked) return alert('Vui lòng chọn 1 địa chỉ!');
    hidId.value = checked.value;
    selForm.submit();
  });

  // Thêm mới: show form add, ẩn list + buttons
  btnAdd?.addEventListener('click', () => {
    addForm.style.display = 'block';
    btnAdd.style.display  = 'none';
    btnConfirmAll.style.display = 'none';
  });
  cancelAdd?.addEventListener('click', () => {
    addForm.style.display = 'none';
    btnAdd.style.display  = '';
    btnConfirmAll.style.display = '';
  });

  // Sửa inline
  list.addEventListener('click', e => {
    if (e.target.matches('.btn-edit')) {
      const item = e.target.closest('.address-item');
      item.querySelector('.view-mode').style.display = 'none';
      item.querySelector('.edit-form').style.display = 'block';
    }
    if (e.target.matches('.btn-cancel')) {
      const item = e.target.closest('.address-item');
      item.querySelector('.edit-form').style.display   = 'none';
      item.querySelector('.view-mode').style.display    = '';
    }
  });
});
</script>