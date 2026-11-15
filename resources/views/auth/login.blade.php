<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar — Cianita</title>
    @vite(['resources/css/app.css'])
</head>
<body class="login-screen">
    <main class="login-card">
        <div class="brand-mark">◆</div>
        <h1>Cianita</h1>
        <p class="muted">Seu conhecimento, sob seu controle.</p>
        <form method="POST" action="{{ route('login') }}" class="login-form">
            @csrf
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            <label for="password">Senha</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
            @error('email')
                <p class="error" role="alert">{{ $message }}</p>
            @enderror
            <button type="submit">Entrar</button>
        </form>
        <p class="muted small">Contas sao criadas pelo administrador da instancia.</p>
    </main>
</body>
</html>
