<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegLogController extends Controller
{
  // Метод, который возвращает страницу входа в аккаунт
  public function loginPage()
  {
    return view('pages.login');
  }

  // Метод для входа в аккаунт по почте и паролю
  public function login(Request $request)
  {
    // Валидация полей
    $request->validate([
      // Проверка поля ('название_поля' => 'правила|валидации|отделённые|прямыми|линиями')
      // Например [дословный перевод] 
      // ('email' => 'обязательное_поле|поле_типа_email|существует_в:таблице_users,поле_email')
      'email' => 'required|email|exists:users,email',
      'password' => 'required'
    ]);

    // Берём из переменной, хранящей данные запроса только поля email и password
    $crentials = $request->only(['email', 'password']);

    // Если попытка входа (auth) с добавлением токена успешна, то
    if (Auth::attempt($crentials, true)) {
      // Перенаправляем (redirect, редирект) на главную страницу
      return redirect('/');
    }

    // Если всё же авторизация неуспешна, 
    // то исполнение кода дойдёт до этого места
    // и будет возврат назад
    // с ошибками (под input с name="email" и с текстом ошибки 'Invalid email or password')
    // с данными ввода в поля ввода (<input/>)
    return back()
      ->withErrors(['email', 'Invalid email or password'])
      ->withInput();
  }

  // Метод, который возвращает страницу регистрации
  public function registerPage()
  {
    return view('pages.register');
  }

  // Метод для регистрации (создание нового пользователя)
  public function register(Request $request)
  {
    $request->validate([
      'name' => 'required|min:2',
      'surname' => 'required|min:2',
      'patronymic' => 'required|min:2',
      'email' => 'required|email|unique:users,email',
      'phone' => 'required|string|regex:/^7\d{10}$/|unique:users,phone',
      'bday' => 'required|date',
      // Правила валидации поля с именем password
      'password' => [
        'required',
        // Пароль должен совпадать с полем "Подтверждения пароля"
        'confirmed',
        // Доп правила валидации пароля:
        // 1. Мининмум 6 символов
        // 2. В составе пароля должны быть буквы
        // 3. В составе пароля должны быть цифры
        // 4. В составе пароля должны быть символы ($,& и т.д.)
        // 5. Пароль должен состоять из строчных и заглавных букв
        Password::min(6)
          ->letters()
          ->numbers()
          ->symbols()
          ->mixedCase()
      ]
    ]);

    // Создаётся пользователь с помощью модели User
    // и резульат записывается в переменную $user
    $user = User::create([
      // Заполняем поля следующим образом (не забываем поля)
      'name' =>  $request->name,
      'surname' =>  $request->surname,
      'patronymic' => $request->patronymic,
      'email' => $request->email,
      'phone' => $request->phone,
      'bday' => $request->bday,
      // Шифруем и записываем пароль (тут с помощью bcrypt)
      'password' => bcrypt($request->password),
      'role' => 'user',
    ]);

    $crentials = $request->only(['email', 'password']);

    if (Auth::attempt($crentials, true)) {
      return redirect('/');
    }

    return back()
      ->withErrors(['email', 'Invalid email or password'])
      ->withInput();
  }

  // Метод для выхода из аккаунта
  public function logout(Request $request)
  {
    // Выходим из аккаунта
    Auth::logout();

    // Удаляем CSRF токены и инвалидируем авторизацию
    // (для безопасности)
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
  }
}
