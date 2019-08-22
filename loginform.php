<html>
<body>
<form method="POST">
  <?php if( $_SERVER['REQUEST_METHOD'] == 'POST' ) { ?>
    Неправильный пароль!
  <?php } ?>
  <p>Введите пароль для получения доступа:</p>
  <input type="password" name="password">
  <button type="submit">Войти</button>
</form>
</body>
</html>