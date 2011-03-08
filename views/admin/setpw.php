<?php if($confirm_failed): ?>
<p>The passwords you entered don't match.</p>
<?php endif; ?>

<form method="post">

<?php if($pass_exists): ?>
<input name="password" type="password" placeholder="Current password" \>
<?php endif; ?>

<input name="newpass" type="password" placeholder="New password"\>
<input name="passconfirm" type="password" placeholder="Confirm new password" \>
<input type="submit" value="Set Password" />
</form>