<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    <link href="/css/material.blue-light_blue.min.css" rel="stylesheet" />

    <link href="/css/style.css" rel="stylesheet" />

    <link rel="stylesheet" href="/css/fontawesome.min.css" />
    <link rel="stylesheet" href="/css/brands.min.css" />
    <link rel="stylesheet" href="/css/regular.min.css" />
    <link rel="stylesheet" href="/css/solid.min.css" />
</head>
<body>
    <div class="mdl-layout mdl-js-layout mdl-layout--fixedheader">
        <header class="mdl-layout__header">
            <div class="mdl-layout__header-row">
                @if (isset($back))
                    <a class="back" href="{{ $back  }}">
                        <i class="material-icons">keyboard_backspace</i>
                    </a>
                @endif
                <ul class="icons">
                    <li>
                        <span class="mdl-layout-title">Homepage</span>
                    </li>
                    @include('favorites')
                </ul>

                <div class="mdl-layout-spacer"></div>
                <nav class="mdl-navigation mdl-layout--large-screen-only">
                    @auth()
                        <form method="POST" action="/removeAll">
                            @csrf
                            <button type="submit" class="mdl-button mdl-button--primary mdl-button--raised">Logout</button>
                        </form>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="mdl-layout__content">
            @yield('content')
        </main>

        <footer>
            <br/>
            Définir cette page d'accueil par défaut pour
            <a target="_blank" href='https://support.google.com/chrome/answer/95314?hl=fr'></a>
            <a href='https://chrome.google.com/webstore/detail/new-tab-redirect/icpgjfneehieebagbmdbhnlpiopdcmna' target="_blank">Chrome</a>,
            <a target="_blank" href='https://support.mozilla.org/fr/kb/comment-definir-page-accueil'>Firefox</a>
            <br/>
            <a href='/privacy.php'>Politique de confidentialité</a>
            &nbsp;<a href='/tos.php'>Conditions de service</a>
        </footer>
    </div>
</body>
</html>
