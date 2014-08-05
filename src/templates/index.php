<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=$title?></title>

    <link rel="stylesheet" type="text/css" href="/web/css/style.css" />

    <!--[if lt IE 9]>
            <script>
                var e = ("article,aside,figcaption,figure,footer,header,hgroup,nav,section,time").split(',');
                for (var i = 0; i < e.length; i++) {
                    document.createElement(e[i]);
                }
            </script>
    <![endif]-->

    <script src="/web/js/jquery.js" type="text/javascript"></script>
    <script src="/web/js/library.js" type="text/javascript"></script>

</head>
<body>

<header>
    <div class="logo"></div>
    <nav>
        <ul>
            <?php
                if ($role == 'client') { 
                    include(ROOT . DS . 'templates' . DS . 'menu-client.php'); 
                } elseif ($role == 'executor') {
                    include(ROOT . DS . 'templates' . DS . 'menu-exec.php'); 
                } else {
                    include(ROOT . DS . 'templates' . DS . 'menu-unauth.php'); 
                }
            ?>
        </ul>
    </nav>
    <div class="right-block">
        <div class="role">
            <?php if ($role == 'client') { ?>
            Заказчик <?=$userName?>
            <?php } elseif ($role == 'executor') { ?>
            Исполнитель <?=$userName?>
            <?php } ?>

        </div>
        <div class="accCaption">Счёт</div>
        <div class="account"><?=$account?></div>
    </div>
</header>

<section id="content">
    <?php if (!$isAuthorized) { ?>
    Для продолжения работы, авторизуйтесь, пожалуйста.
    <?php } ?>
    <?=$content?>
</section>

<footer>
    Тестовое задание
</footer>

<div class="modal">
    <a href="#" class="close">✕</a>
    <div class="text">
    </div>
</div>

<script>(function () { doLaunch(); })();</script>

</body>
</html>