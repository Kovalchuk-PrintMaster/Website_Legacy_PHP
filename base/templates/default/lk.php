
<main class="main">

    <div class="container">
        <div class="wrapper wrapper_center">
            <h1 class="page-title">Мої зазамовлення</h1>
        </div>
    </div>


    <?php ?>

        <section class="section">
            <div class="container">
                <div class="wrapper_internal wrapper_lk">
                    <aside class="internal-aside internal-aside_lk">
                        <div class="internal-aside-items">
                            <a href="#" data-popup="login-popup" data-user-info class="internal-aside-item internal-aside-item_active">
                                Мій аккаунт </a>
                            <a href="<?=$this->alias('lk')?>" class="internal-aside-item ">
                                Мої замовлення </a>
                            <a href="<?=$this->alias(['login'=>'logout'])?>" class="internal-aside-item ">
                                Вихід </a>

                            <script>

                                document.addEventListener('DOMContentLoaded', ()=>{

                                    document.querySelector('[data-user-info]').addEventListener('click', ()=> {

                                     let popup = document.querySelector('.login-popup')

                                        if(popup){

                                            let forms = popup.querySelectorAll('form')

                                            if (typeof forms[1] !== 'undefined'){

                                                 forms[1].remove()
                                            }

                                            forms[0].querySelector('input[type="submit"]').value = 'Зберегти'

                                            popup.querySelector('h2')?.remove()

                                            popup.querySelectorAll('[type="password"]').forEach(item=> item.removeAttribute('required'))
                                        }
                                    }, true)

                                })

                            </script>

                        </div>
                    </aside>

                    <?php if (empty($orders)) : ?>

                        <p>Упс, здається ви ще не робили замовлень в нашій друкарні.</p>

                    <?php else : ?>

                    <div class="cabinet cabinet_switches lk-body">

                        <div class="switchTitles second">

                            <?php if (!empty($currentOrder)):?>

                            <div class="active" data-innerswitch="order_switch1">Поточне замоавлення</div>

                            <?php endif; ?>

                            <div data-innerswitch="order_switch2" class="">Всі замовлення (<?= count($orders) ?>)</div>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', () => {

                                let innerSwitch = document.querySelectorAll('[data-innerswitch]')

                                let innerSwitched = document.querySelectorAll('[data-innerswitched]')

                                if (innerSwitched.length) {

                                    innerSwitch.forEach((item, index) => {

                                        item.addEventListener('click', () => {

                                            if (typeof innerSwitched[index] !== 'undefined' && !item.classList.contains('active')) {

                                                innerSwitch.forEach(el => el.classList.remove('active'))
                                                innerSwitched.forEach(el => el.classList.remove('active'))

                                                item.classList.add('active')
                                                innerSwitched[index].classList.add('active')

                                            }

                                        })

                                    })

                                }

                            })
                        </script>

                        <?php if (!empty($currentOrder)):?>

                            <div class="active" data-innerswitched="order_switch1">
                            <div class="order_title">
                                <h3>
                                    Замовлення №<?= $currentOrder['id'] ?>
                                </h3>
                            </div>
                            <ul class="info_panel">
                                <li>
                                <span class="name">
                                В замовленні:
                                </span>
                                    <span class="info">
                                    Загальна кількість товарів - <?=count($currentOrder['join']['orders_goods'] ?? [])?>
                                </span>
                                </li>
                                <li>
                                <span class="name">
                                    Вартість:
                                </span>
                                    <span class="info">
                                    <?=$currentOrder['total_sum']?> грн
                                </span>
                                </li>
                                <li>
                                <span class="name">
                                    Дата оформлення:
                                </span>
                                    <span class="info">
                                        <?=$currentOrder['date']?>
                                </span>
                                </li>
                                <li>
                                <span class="name">
                                    Статус:
                                </span>
                                    <span class="info">
                                             <?php foreach ($currentOrder['join']['orders_statuses'] as $item) : ?>

                                                 <?=$item['name']?>

                                             <?php endforeach; ?>
                                </span>
                                </li>
                            </ul>

                                <?php if (!empty($currentOrder['join']['orders_goods'])) : ?>

                            <div class="table_wrap">
                                <table>
                                    <thead>
                                    <tr>
                                        <th>Ідентифікатор</th>
                                        <th>Назва</th>
                                        <th>Кількість</th>
                                        <th>Вартість</th>
                                    </tr>
                                    </thead>
                                    <tbody>

                                    <?php foreach ($currentOrder['join']['orders_goods'] as $item) : ?>

                                    <tr>
                                        <td><?=$item['id']?></td>
                                        <td>
                                            <div class="flex_wrap">
                                                <b>
                                                    <?=$item['name']?>
                                                </b>
                                            </div>
                                        </td>
                                        <td>
                                            <?=$item['qty']?>шт
                                        </td>
                                        <td>
                                            <?=$item['price']?> грн
                                        </td>
                                    </tr>
                                    <tr>

                                    <?php endforeach; ?>

                                    </tbody>
                                </table>
                            </div>

                                <?php endif; ?>

                            <div class="table_total">
                                <div class="delivery">
                                    <div>
                                        <b>
                                            Спосіб оплати:
                                        </b>
                                        <span>

                                             <?php foreach ($currentOrder['join']['payments'] as $item) : ?>

                                                    <?=$item['name']?>

                                    <?php endforeach; ?>

                                        </span>
                                    </div>
                                    <div>
                                        <b>
                                            Спосіб отримання:
                                        </b>
                                        <span>
                                            <?php foreach ($currentOrder['join']['delivery'] as $item) : ?>

                                                <?=$item['name']?>

                                                    <?php endforeach; ?>
                                    </span>
                                    </div>
                                </div>
                                <div class="totally">
                                    <b>Загальна вартість:</b>
                                    <?=$currentOrder['total_sum']?> грн
                                </div>
                            </div>

                        </div>

                        <?php endif; ?>

                        <div data-innerswitched="order_switch2" class="<?=empty($currentOrder) ? 'active' : '' ?>">
                            <div class="table_wrap">
                                <table class="second_table">
                                    <thead>
                                    <tr>
                                        <th>Номер</th>
                                        <th>Дата створення</th>
                                        <th>Вартість</th>
                                        <th>Статус</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>

                                    <?php foreach ($orders as $item): ?>

                                        <tr>
                                            <td>№<?=$item['id'] ?></td>
                                            <td>
                                                <?=$item['date'] ?>
                                            </td>
                                            <td>
                                                <?=$item['total_sum'] ?> грн
                                            </td>
                                            <td>
                                                <span class="status "><?=$item['join']['orders_statuses'][1]['name'] ?></span>
                                            </td>
                                            <td>
                                                <a href="<?=$this->alias(['lk'=>'order', 'id'=>$item['id']])?>>">Детальніше</a>
                                            </td>
                                        </tr>

                                    <?php endforeach;?>


                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <?php endif; ?>

                </div>
            </div>
        </section>





</main>
