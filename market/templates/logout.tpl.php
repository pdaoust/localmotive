<h2>You are now logged out.</h2>
<p>If you still want to spend time on the website, you can go back to our <a href="<?= $config['docRoot'] ?>">home page</a>. If you didn't intend to log out, you can log back in:</p>
<? $fromOtherTemplate = true;
$loginError = false;
include ($path . '/market/templates/login.tpl.php'); ?>
