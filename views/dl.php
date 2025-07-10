
  <link rel="stylesheet" href="../css/dl.css">

  <main class="container">
    <!-- Danh sách bài viết chính -->
    <section class="main-posts" aria-label="Danh sách bài viết">
      <?php
        $posts = [
          [
            'page'  => 'monc',
            'title' => 'Mì ống nướng chay - Dư vị thơm lành',
            'img'   => 'monc.png',
            'desc'  => 'Salad chưa bao giờ chỉ là một món ăn. Nó là một khoảng lặng giữa...'
          ],
          [
            'page'  => 'BBCX',
            'title' => 'BÁNH BÔNG CẢI XANH',
            'img'   => 'BBCX.png',
            'desc'  => 'Bông cải siêu bổ dưỡng trở thành tâm điểm trong những chiếc bánh chiên lành mạnh này, ăn kèm roti, salad xanh và sữa chua.'
          ],
          [
            'page'  => 'post3',
            'title' => 'Salad & những “lần” được bỏ qua...',
            'img'   => 'post3.jpg',
            'desc'  => '...'
          ],
        ];

        foreach ($posts as $post) {
          $url   = 'layout.php?page=' . urlencode($post['page']);
          $img   = '../ct/' . htmlspecialchars($post['img']);
          $title = htmlspecialchars($post['title']);
          $desc  = htmlspecialchars($post['desc']);

          echo "<a href=\"{$url}\" class=\"post-link\">";
          echo "  <article class=\"post\">";
          echo "    <div class=\"thumb\" style=\"background-image: url('{$img}');\"></div>";
          echo "    <h3>{$title}</h3>";
          echo "    <p>{$desc}</p>";
          echo "  </article>";
          echo "</a>";
        }
      ?>
    </section>

    <!-- Sidebar bài viết mới -->
    <aside class="sidebar" aria-label="Bài viết mới">
      <h2>BÀI VIẾT MỚI</h2>
      <ul class="recent-posts">
        <li>
          <a href="#">
            <img src="../img/ava1.jpg" alt="Ảnh bài viết Chiều nay ăn gì?">
            <span>Chiều nay ăn gì? Bữa healthy đóng gói sẵn chuẩn Gu “Sống Khỏe”</span>
          </a>
        </li>
        <li>
          <a href="#">
            <img src="../img/ava2.jpg" alt="Ảnh bài viết Salad – Dư vị của sự an yên">
            <span>Salad – Dư vị của sự an yên</span>
          </a>
        </li>
        <li>
          <a href="#">
            <img src="../img/ava3.jpg" alt="Ảnh bài viết Salad & Tình Yêu">
            <span>Salad & Tình Yêu: Buông lòng để thưởng thức</span>
          </a>
        </li>
        <li>
          <a href="#">
            <img src="../img/ava4.jpg" alt="Ảnh bài viết Salad & những lần được bỏ qua">
            <span>Salad & những “lần” được bỏ qua...</span>
          </a>
        </li>
        <li>
          <a href="#">
            <img src="../img/ava5.jpg" alt="Ảnh bài viết Sự thật phũ phàng về bữa trưa công sở">
            <span>Sự thật phũ phàng về bữa trưa công sở...</span>
          </a>
        </li>
        <li>
          <a href="#">
            <img src="../img/ava6.jpg" alt="Ảnh bài viết Say bye bye đồ ăn dầu mỡ">
            <span>"Say bye bye" đồ ăn dầu mỡ...</span>
          </a>
        </li>
      </ul>
    </aside>
  </main>
