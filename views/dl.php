
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
        <?php foreach ($posts as $post): ?>
          <li>
            <a href="<?php echo 'layout.php?page=' . urlencode($post['page']); ?>">
              <img src="<?php echo '../ct/' . htmlspecialchars($post['img']); ?>" alt="Ảnh bài viết <?php echo htmlspecialchars($post['title']); ?>">
              <span><?php echo htmlspecialchars($post['title']); ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </aside>
  </main>
