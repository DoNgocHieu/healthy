<link rel="stylesheet" href="../css/hd1.css">

<section class="hd1-banner">
    <div class="hd1-overlay">
        <h2>KHÁM PHÁ<br>THỰC ĐƠN CHAY</h2>
        <div class="hd1-buttons">
            <a href="#" class="hd1-btn">MÓN LẨU</a>
            <a href="#" class="hd1-btn">MÓN NẤM</a>
            <a href="#" class="hd1-btn">TRÀ & BÁNH</a>
        </div>
        <p class="hd1-desc">
            Được xem là linh hồn của Broccoli, mỗi món chay phục vụ 
            đến thực khách đều mang trong mình những ý nghĩa riêng.<br><br>
            Có khi là lời gửi gắm sức khỏe qua những món chay mát lành, 
            bổ dưỡng, có khi là thông điệp cho tâm hồn với những bữa 
            cơm chay giúp tâm thanh tịnh...<br><br>
            Trên hết là sự ân cần, chu đáo và lòng biết ơn thực khách đã 
            tin tưởng và luôn ủng hộ bếp chay Broccoli.
        </p>
    </div>
    <img id="hd1Bg" class="hd1-bg-img" src="../img/hd1.jpg" alt="Banner" />
</section>
<script>
const images = [
  '../img/hd1.jpg',
  '../img/hd2.png',
  '../img/hd3.png',
  '../img/hd4.png',
];
let idx = 0;
const img = document.getElementById('hd1Bg');
function nextImg() {
  img.classList.add('blur');
  setTimeout(() => {
    idx = (idx + 1) % images.length;
    img.src = images[idx];
    img.classList.remove('blur');
  }, 300);
}
setInterval(nextImg, 5000);
</script>

<style>
.hd1-bg-img {
  position: absolute;
  top: 0; left: 0;
  width: 100vw;
  height: 100%;
  min-height: 90vh;
  object-fit: cover;
  z-index: 0;
  transition: opacity 0.7s, filter 0.8s;
  opacity: 1;
}
.hd1-bg-img.blur {
  filter: blur(8px);
}
.hd1-overlay {
  position: relative;
  z-index: 1;
}
</style>
