<header>
  @auth
  <form action="/logout" method="post">
    @csrf
    <button type="submit">
      Выйти
    </button>
  </form>
  @else
  <a href="/login">Войти</a>
  <a href="/register">Регистрация</a>
  @endauth
</header>