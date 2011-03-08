
<a href="<?= site_page('admin/create')?>">new article</a>

<ul id="index-list">
<?php foreach ($articles as $article): ?>
<li>
  <a href="<?= site_page($article['desc']) ?>"><?= $article['title']?></a>
  <span class="date">(<?= $article['timestamp'] ?>)</span>
  <span class="options">
    <a href="<?= site_page("admin/{$article['desc']}/edit")?>">edit</a>
    <a href="<?= site_page("admin/{$article['desc']}/delete")?>">delete</a>
  </span>
</li>
<?php endforeach; ?>
</ul>