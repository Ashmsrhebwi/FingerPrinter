<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Girişi</title>

  <link rel="stylesheet" href="{{ asset('assets/style.css') }}" />
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
  />
</head>
<body>
  <div class="login-container">
    <div class="login-icon"><i class="bi bi-person-fill-lock"></i></div>
    <h2>Yönetici Girişi</h2>

    {{-- ✅ Laravel login form --}}
    <form method="POST" action="/login">
      @csrf
      <label for="email">Kullanıcı Adı</label>
      <input type="email" class="input-form-user" id="email" name="email" placeholder="admin@example.com" required />

      <label for="password">Şifre</label>
      <input type="password" class="input-form-ps" id="password" name="password" placeholder="••••••••" required />

      <button type="submit">Giriş Yap</button>

      {{-- رسالة الخطأ --}}
      @if(session('error'))
        <p id="errorMsg">{{ session('error') }}</p>
      @endif
    </form>

    <div class="footer">
      © 2025 Akıllı Giriş Sistemi · <a href="#">Destek</a>
    </div>
  </div>

  <!-- <script src="{{ asset('assets/login.js') }}"></script> -->
</body>
</html>
