<?php
// views/address.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
$pdo = getDb();

// Nếu chưa login
if (empty($_SESSION['user_id'])) {
  header('Location: /healthy/views/layout.php?page=login');
  exit;
}
$userId = $_SESSION['user_id'];

// Lấy profile
$stmt = $pdo->prepare(
    "SELECT p.avatar, p.fullname
     FROM profiles p
     WHERE p.user_id = :uid"
);
$stmt->execute([':uid' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['avatar'=>null,'fullname'=>''];
$avatarUrl = $user['avatar']
    ? '/healthy/' . ltrim($user['avatar'], '/')
    : '/healthy/img/default-avatar.png';

// Xử lý POST
$errors = [];
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $action = $_POST['action'] ?? '';
      if ($action === 'set_default') {
        $addrId = (int)$_POST['address_id'];
        // Reset lại tất cả về 0 rồi set riêng địa chỉ này về 1
        $pdo->beginTransaction();
        $pdo->exec("UPDATE user_addresses SET is_default = 0 WHERE user_id = $userId");
        $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = :aid AND user_id = :uid");
        $stmt->execute([':aid' => $addrId, ':uid' => $userId]);
        $pdo->commit();
        header('Location: layout.php?page=address');
        exit;
    }
        if ($action === 'unset_default') {
        $addrId = (int)$_POST['address_id'];
        // Chỉ cần set is_default = 0 cho địa chỉ này
        $stmt = $pdo->prepare("
          UPDATE user_addresses
             SET is_default = 0
           WHERE id = :aid AND user_id = :uid
        ");
        $stmt->execute([':aid'=>$addrId,':uid'=>$userId]);
        header('Location: layout.php?page=address');
        exit;
    }
        if ($action === 'delete') {
        $addrId = (int)$_POST['address_id'];
        $stmt = $pdo->prepare(
            "DELETE FROM user_addresses
             WHERE id = :aid AND user_id = :uid"
        );
        $stmt->execute([':aid' => $addrId, ':uid' => $userId]);
        header('Location: layout.php?page=address');
        exit;
    }
  // Gom address lại thành chuỗi
  $district  = $_POST['district'] ?? '';
  $ward      = $_POST['ward']     ?? '';
  $street    = trim($_POST['street']    ?? '');
  $house_no  = trim($_POST['house_no']  ?? '');
  $fullname  = trim($_POST['fullname']  ?? '');
  $phone     = trim($_POST['phone']     ?? '');

  if (!$district||!$ward||!$street||!$house_no||!$fullname||!$phone) {
    $errors[] = 'Vui lòng điền đầy đủ thông tin.';
  } else {
    $address = sprintf(
      '%s, %s, %s, %s, Hồ Chí Minh',
      $house_no, $street, $ward, $district
    );

    if ($action==='add') {
      $ins = $pdo->prepare(
        "INSERT INTO user_addresses (user_id, fullname, phone, address)
         VALUES (:uid,:fn,:ph,:ad)"
      );
      $ins->execute([
        ':uid'=>$userId,':fn'=>$fullname,
        ':ph'=>$phone, ':ad'=>$address
      ]);
    }
    if ($action==='edit') {
      $addrId = (int)$_POST['address_id'];
      $upd = $pdo->prepare(
        "UPDATE user_addresses
           SET fullname=:fn, phone=:ph, address=:ad
         WHERE id=:aid AND user_id=:uid"
      );
      $upd->execute([
        ':fn'=>$fullname, ':ph'=>$phone,
        ':ad'=>$address, ':aid'=>$addrId,
        ':uid'=>$userId
      ]);
    }
    header('Location: layout.php?page=address');
    exit;
  }
}

$stmt = $pdo->prepare("
  SELECT id, fullname, phone, address, is_default
    FROM user_addresses
   WHERE user_id=:uid
   ORDER BY is_default DESC, id ASC
");
$stmt->execute([':uid'=>$userId]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<link rel="stylesheet" href="../css/address.css">
<div class="container">
  <nav class="menu">
    <div class="menu-profile">
      <img
        src="<?= htmlspecialchars($avatarUrl) ?>"
        class="avatar-preview"
        alt="Avatar">
      <p><?= htmlspecialchars($user['fullname'] ?: $_SESSION['username']) ?></p>
    </div>
    <a href="layout.php?page=info" class="active">Thông tin tài khoản</a>
    <a href="layout.php?page=points">Điểm & voucher</a>
    <a href="layout.php?page=address">Địa chỉ giao hàng</a>
    <a href="layout.php?page=orders">Đơn hàng hiện tại</a>
  </nav>
  <section class="main">
    <h2>Địa chỉ giao hàng</h2>
    <p>Quản lý địa chỉ giao hàng của bạn</p>
    <hr/>
    <?php if (!empty($errors)): ?>
      <ul class="errors">
      <?php foreach($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
<div class="address-list">
  <?php foreach($addresses as $addr): 
    // Tách chuỗi address thành 4 phần:
    $parts    = array_map('trim', explode(',', $addr['address']));
    $houseNo  = $parts[0] ?? '';
    $street   = $parts[1] ?? '';
    $ward     = $parts[2] ?? '';
    $district = $parts[3] ?? '';
  ?>
    <div class="address-item <?= $addr['is_default'] ? 'default' : '' ?>">
      <p><strong><?=htmlspecialchars($addr['fullname'])?></strong> – <?=htmlspecialchars($addr['phone'])?></p>
      <p><?=nl2br(htmlspecialchars($addr['address']))?></p>
      <div class="actions">
        <!-- Đặt hoặc bỏ mặc định -->
        <?php if (!$addr['is_default']): ?>
          <form method="post" class="d-inline">
            <input type="hidden" name="action" value="set_default">
            <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
            <button type="submit" class="btn-default">Đặt làm mặc định</button>
          </form>
        <?php else: ?>
          <form method="post" class="d-inline">
            <input type="hidden" name="action" value="unset_default">
            <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
            <button type="submit" class="btn-unset">Bỏ mặc định</button>
          </form>
        <?php endif; ?>

        <!-- Nút Sửa -->
        <button
          class="btn-edit"
          data-id="<?= $addr['id'] ?>"
          data-fullname="<?= htmlspecialchars($addr['fullname'], ENT_QUOTES) ?>"
          data-phone="<?= htmlspecialchars($addr['phone'], ENT_QUOTES) ?>"
          data-district="<?= htmlspecialchars($district, ENT_QUOTES) ?>"
          data-ward="<?= htmlspecialchars($ward, ENT_QUOTES) ?>"
          data-street="<?= htmlspecialchars($street, ENT_QUOTES) ?>"
          data-house-no="<?= htmlspecialchars($houseNo, ENT_QUOTES) ?>"
        >Sửa</button>

        <!-- Nút Xóa -->
        <form method="post" class="d-inline">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="address_id" value="<?=$addr['id']?>">
          <button type="submit" class="btn-delete" onclick="return confirm('Xác nhận xóa?')">Xóa</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<button id="btnAdd" class="btn-add">Thêm địa chỉ mới</button>

<!-- Modal add/edit -->
<div id="addressModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3 id="modalTitle">Thêm địa chỉ</h3>
    <form method="post" id="addressForm">
      <input type="hidden" name="action" id="formAction" value="add">
      <input type="hidden" name="address_id" id="addressId" value="">
      <!-- Họ & tên -->
      <div class="form-group">
        <label>Họ & tên</label>
        <input type="text" name="fullname" id="fullname" required>
      </div>
      <!-- SDT -->
      <div class="form-group">
        <label>Số điện thoại</label>
        <input type="text" name="phone" id="phone" required>
      </div>
      <!-- Quận/Huyện & Phường/Xã -->
      <div class="form-row">
        <div class="form-group">
          <label>Quận/Huyện</label>
          <select name="district" id="district" required>
            <option value="">Chọn quận/huyện</option>
                <option>Quận 1</option>
                <option>Quận 3</option>
                <option>Quận 4</option>
                <option>Quận 5</option>
                <option>Quận 6</option>
                <option>Quận 7</option>
                <option>Quận 8</option>
                <option>Huyện Bình Chánh</option>
                <option>Huyện Cần Giờ</option>
                <option>Huyện Củ Chi</option>
                <option>Huyện Hóc Môn</option>
                <option>Huyện Nhà Bè</option>
                <option>Thành phố Thủ Đức</option>
                <option>Quận 10</option>
                <option>Quận 11</option>
                <option>Quận 12</option>
                <option>Quận Bình Thạnh</option>
                <option>Quận Gò Vấp</option>
                <option>Quận Phú Nhuận</option>
                <option>Quận Tân Bình</option>
                <option>Quận Tân Phú</option>
                <option>Quận Bình Tân</option>
          </select>
        </div>
        <div class="form-group">
          <label>Phường/Xã</label>
          <select name="ward" id="ward" required>
            <option value="">Chọn phường/xã</option>
          </select>
        </div>
      </div>
      <!-- Đường & Số nhà -->
      <div class="form-group">
        <label>Đường</label>
        <input type="text" name="street" id="street" required>
      </div>
      <div class="form-group">
        <label>Số nhà, căn hộ</label>
        <input type="text" name="house_no" id="house_no" required>
      </div>
      <button type="submit" class="btn-save">Lưu</button>
    </form>
  </div>
</div>

<script>
const wardsByDistrict = {
  'Quận 1': [
    'Phường Tân Định',
    'Phường Bến Thành',
    'Phường Sài Gòn',
    'Phường Cầu Ông Lãnh'
  ],
  'Quận 3': [
    'Phường Bàn Cờ',
    'Phường Xuân Hòa',
    'Phường Nhiêu Lộc'
  ],
  'Quận 4': [
    'Phường Vĩnh Hội',
    'Phường Khánh Hội',
    'Phường Xóm Chiếu'
  ],
  'Quận 5': [
    'Phường Chợ Quán',
    'Phường An Đông',
    'Phường Chợ Lớn'
  ],
  'Quận 6': [
    'Phường Bình Tiên',
    'Phường Bình Tây',
    'Phường Bình Phú',
    'Phường Phú Lâm'
  ],
  'Quận 7': [
    'Phường Tân Mỹ',
    'Phường Tân Hưng',
    'Phường Tân Thuận',
    'Phường Phú Thuận'
  ],
  'Quận 8': [
    'Phường Chánh Hưng',
    'Phường Bình Đông',
    'Phường Phú Định'
  ],
  'Quận 10': [
    'Phường Vườn Lài',
    'Phường Diên Hồng',
    'Phường Hòa Hưng'
  ],
  'Quận 11': [
    'Phường Hòa Bình',
    'Phường Phú Thọ',
    'Phường Bình Thới',
    'Phường Minh Phụng'
  ],
  'Quận 12': [
    'Phường Đông Hưng Thuận',
    'Phường Trung Mỹ Tây',
    'Phường Tân Thới Hiệp',
    'Phường An Phú Đông'
  ],
  'Quận Bình Thạnh': [
    'Phường Gia Định',
    'Phường Bình Thạnh',
    'Phường Bình Lợi Trung',
    'Phường Bình Quới'
  ],
  'Quận Bình Tân': [
    'Phường Bình Tân',
    'Phường Bình Trị Đông',
    'Phường Bình Hưng Hòa',
    'Phường An Lạc',
    'Phường Tân Tạo'
  ],
  'Quận Gò Vấp': [
    'Phường Hạnh Thông',
    'Phường Gò Vấp',
    'Phường An Hội Tây',
    'Phường An Hội Đông'
  ],
  'Quận Phú Nhuận': [
    'Phường Đức Nhuận',
    'Phường Cầu Kiệu'
  ],
  'Quận Tân Bình': [
    'Phường Tân Sơn Hòa',
    'Phường Tân Sơn Nhất',
    'Phường Tân Hòa',
    'Phường Bảy Hiền',
    'Phường Tân Bình',
    'Phường Tân Sơn'
  ],
  'Quận Tân Phú': [
    'Phường Tây Thạnh',
    'Phường Tân Sơn Nhì',
    'Phường Phú Thọ Hòa',
    'Phường Phú Thạnh',
    'Phường Tân Phú'
  ],
  'Huyện Bình Chánh': [
    'Xã Vĩnh Lộc',
    'Xã Tân Vĩnh Lộc',
    'Xã Bình Lợi',
    'Xã Tân Nhựt',
    'Xã Bình Chánh',
    'Xã Hưng Long',
    'Xã Bình Hưng'
  ],
  'Huyện Cần Giờ': [
    'Xã Bình Khánh',
    'Xã Cần Giờ',
    'Xã An Thới Đông',
    'Xã Thạnh An'
  ],
  'Huyện Củ Chi': [
    'Xã An Nhơn Tây',
    'Xã Thái Mỹ',
    'Xã Nhuận Đức',
    'Xã Tân An Hội',
    'Xã Củ Chi',
    'Xã Phú Hòa Đông',
    'Xã Bình Mỹ'
  ],
  'Huyện Hóc Môn': [
    'Xã Hóc Môn',
    'Xã Bà Điểm',
    'Xã Xuân Thới Sơn',
    'Xã Đông Thạnh'
  ],
  'Huyện Nhà Bè': [
    'Xã Nhà Bè',
    'Xã Hiệp Phước'
  ],
  'Thành phố Thủ Đức': [
    'Phường Hiệp Bình',
    'Phường Tam Bình',
    'Phường Thủ Đức',
    'Phường Linh Xuân',
    'Phường Long Bình',
    'Phường Tăng Nhơn Phú',
    'Phường Phước Long',
    'Phường Long Phước',
    'Phường Long Trường',
    'Phường An Khánh',
    'Phường Bình Trưng',
    'Phường Cát Lái'
  ]
};
const modal    = document.getElementById('addressModal');
const btnAdd   = document.getElementById('btnAdd');
const closeBtn = modal.querySelector('.close');
const form     = document.getElementById('addressForm');
const title    = document.getElementById('modalTitle');

// Đổ phường/xã tương ứng với quận
function updateWards() {
  const distSel = document.getElementById('district');
  const wardSel = document.getElementById('ward');
  wardSel.innerHTML = '<option value="">Chọn phường/xã</option>';
  const list = wardsByDistrict[distSel.value] || [];
  list.forEach(name => {
    const opt = document.createElement('option');
    opt.value = name;
    opt.text  = name;
    wardSel.appendChild(opt);
  });
}

// Gắn listener khi user chọn quận thủ công (add case)
document.getElementById('district')
        .addEventListener('change', updateWards);

// Mở modal thêm
btnAdd.onclick = () => {
  title.textContent   = 'Thêm địa chỉ';
  form.reset();
  form.action.value   = 'add';
  document.getElementById('addressId').value = '';
  modal.style.display = 'block';
};

// Mở modal sửa
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.onclick = () => {
    title.textContent   = 'Sửa địa chỉ';
    form.action.value   = 'edit';
    document.getElementById('addressId').value = btn.dataset.id;
    document.getElementById('fullname').value  = btn.dataset.fullname;
    document.getElementById('phone').value     = btn.dataset.phone;

    // Prefill quận
    document.getElementById('district').value = btn.dataset.district;
    // Đổ phường/xã tương ứng
    updateWards();
    // Prefill phường
    document.getElementById('ward').value     = btn.dataset.ward;

    // Prefill đường + số nhà
    document.getElementById('street').value    = btn.dataset.street;
    document.getElementById('house_no').value  = btn.dataset.houseNo;

    modal.style.display = 'block';
  };
});

// Đóng modal
closeBtn.onclick = () => modal.style.display = 'none';
window.onclick = e => {
  if (e.target === modal) modal.style.display = 'none';
};

</script>