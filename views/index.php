<ul id="index-list">
<?php foreach ($articles as $article): ?>
<li><a href="<?= $article['desc'] ?>"><?= $article['title']?></a> <span class="date">(<?= $article['timestamp'] ?>)</span></li>
<?php endforeach; ?>
</li>