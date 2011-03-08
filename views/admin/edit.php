<a href="<?= site_page('admin/')?>">Admin index</a> | 
<a href="<?= site_page('admin/create')?>">Create new article</a>

<form method="post">
<input type="text" name="title" value="<?= $article['title'] ?>"/>
<textarea name="body"><?= $article['body'] ?></textarea>
<input type="password" name="password" />
<input type="submit" name="submit" value="Save changes" />
</form>