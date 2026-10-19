<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>

<body>
    @include('components.header')
    <h1>Register</h1>
    <form action="/register" method="post">
        @csrf

        @include('components.input', ['name' => 'name', 'placeholder' => 'Имя', 'type' => 'text'])
        @include('components.input', ['name' => 'name', 'placeholder' => 'Фамилия', 'type' => 'text'])
        @include('components.input', ['name' => 'name', 'placeholder' => 'Отчество', 'type' => 'text'])
        @include('components.input', ['name' => 'name', 'placeholder' => 'Почта', 'type' => 'text'])
        @include('components.input', ['name' => 'name', 'placeholder' => 'Телефон', 'type' => 'text'])
        @include('components.input', ['name' => 'name', 'placeholder' => 'Пароль', 'type' => 'password'])
        @include('components.input', [
            'name' => 'name',
            'placeholder' => 'Пароль повторно',
            'type' => 'password',
        ])
        @include('components.input', [
            'name' => 'name',
            'placeholder' => 'Дата рождения',
            'type' => 'bday',
        ])

        <button type="submit">Зарегистрироваться</button>
    </form>
</body>

</html>
