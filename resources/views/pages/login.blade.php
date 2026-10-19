<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>

<body>
    @include('components.header')
    <h1>Login</h1>
    <form action="/login" method="post">
        @csrf

        @include('components.input', ['name' => 'name', 'placeholder' => 'Почта', 'type' => 'text'])
        @include('components.input', ['name' => 'name', 'placeholder' => 'Пароль', 'type' => 'password'])

        <button type="submit">Войти</button>
    </form>
</body>

</html>
