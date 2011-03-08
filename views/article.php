<article>
  <header>
    <h1 class="title"><?= $article['title'] ?></h1>
    <span class="article-date"><?= $article['timestamp'] ?></span>
  </header>

  <section>
    <?= $article['body'] ?>
  </section>
</article>

<div id="disqus_thread"></div>
<script type="text/javascript" src="http://lackingrefinement.disqus.com/embed.js"></script>
<noscript>View the <a href="http://disqus.com/?ref_noscript">discussion thread.</a></noscript>
<a href="http://disqus.com" class="dsq-brlink">blog comments powered by <span class="logo-disqus">Disqus</span></a>

