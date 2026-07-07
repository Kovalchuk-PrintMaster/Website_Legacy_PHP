</main>
<footer class="footer" xmlns="http://www.w3.org/1999/html">
    <div class="container">
        <div class="footer__wrapper">
            <div class="footer__top">
                <div class="footer__top_logo">
                    <img src="<?=PATH . 'userfiles/logo/Mast_LogN_square.png' ?>" alt="">
                </div>
                <div class="footer__top_menu">
                    <ul>

                        <li>
                            <a href="#"><span>Каталог</span></a>
                        </li>

                        <li>
                            <a href="#"><span>Про нас</span></a>
                        </li>

                        <li>
                            <a href="#"><span>Доставка и оплата</span></a>
                        </li>

                        <li>
                            <a href="#"><span>Контакти</span></a>
                        </li>

                        <li>
                            <a href="https://maps.app.goo.gl/9qVWMQqJbTaJEoLh8"><span>Як нас знайти</span></a>
                        </li>

                        <li>
                            <a href="#"><span>Карта сайта</span></a>
                        </li>

                    </ul>
                </div>
                <div class="footer__top_contacts">
                    <div><a href="mailto:druk.smile@gmail.com">work.printmaster@gmail.com</a></div>
                    <div><a href="tel:+380 96 053 00 51">+380 96 053 00 51</a></div>
                    <div><a class="js-callback">Зв'язатися з нами</a></div>
                </div>
            </div>
            <div class="footer__bottom">
                <div class="footer__bottom_copy">Copyright - Print Master 2025</div>
            </div>
        </div>
    </div>
</footer>

<div class="hide-elems">
    <svg>
        <defs>
            <linearGradient id="rainbow" x1="0" y1="0" x2="50%" y2="50%">
                <stop offset="0%" stop-color="#7282bc" />s
                <stop offset="100%" stop-color="#7abfcc" />
            </linearGradient>
        </defs>
    </svg>
</div>


    <div class="login-popup">

        <div class="order-popup__inner">

            <h2><span>Реєстрація/</span><span>Вхід</span></h2>

            <form action="<?=$this->alias(['login' => 'registration'])?>" method="post" >
                <input type="text" name="name" required placeholder="Введіть, будь ласка, ваш ім'я" value="<?=$this->setFormValues('name', 'userData')?>">

                <input type="tel" name="phone" required placeholder="Введіть, будь ласка, телефон у форматі +38 0ХХ ХХХ ХХ ХХ" value="<?=$this->setFormValues('phone', 'userData')?>">
                <input type="email" name="email" required placeholder="Введіть, будь ласка, ваш e-mail" value="<?=$this->setFormValues('email', 'userData')?>">

                <input type="password" name="password" required placeholder="Введіть ваш пароль">
                <input type="password" name="confirm_password" required placeholder="Підтвердження пароля">

                <div class="send-order">
                    <input class="execute-order_btn" type="submit" value="Зареєструватися">
                </div>
            </form>

            <form action="<?=$this->alias(['login' => 'login'])?>" method="post" style="display: none">

                <input type="text" name="login" required placeholder="Введіть, будь ласка, ваш e-mail" value="<?=$this->setFormValues('email')?>">

                <input type="password" name="password" required placeholder="Введіть ваш пароль">

                <div class="send-order">
                    <input class="execute-order_btn" type="submit" value="Увійти">
                </div>

            </form>



        </div>

    </div>


<?php $this->getScripts()?>

<!-- убрать -->
<!--<script src="assets/js/freeHost.js"></script>-->
<!-- убрать -->

<?php if (!empty($_SESSION['res']['answer'])):?>

    <div class="wq-message__wrap"><?=$_SESSION['res']['answer']?></div>

<?php endif;?>

<?php unset($_SESSION['res']);?>

</body>

</html>
