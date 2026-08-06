<?php

namespace core\user\controllers;

/**
 * ForPrint public services overview.
 *
 * Stable route:
 * /nashi-posluhy/
 */
class NashiposluhyController extends BaseUser
{
    protected function inputData()
    {
        parent::inputData();

        $this->frontendSurface = 'services';
        $this->frontendProfile = $this->resolveFrontendProfile();

        $this->styles[] = PATH
            . TEMPLATE
            . 'assets/css/forprint-services.css?v=20260804-1315';

        $data = [
            'name' => 'Наші послуги',
            'description' => (
                'Поліграфія, друк, зовнішня реклама, '
                . 'брендування текстилю, посуду та сувенірної '
                . 'продукції, а також індивідуальні замовлення ForPrint.'
            ),
            'content' => '',
        ];

        $serviceGroups = [
            [
                'id' => 'business-print',
                'title' => 'Поліграфія для бізнесу',
                'description' => (
                    'Візитки, листівки, буклети, меню, каталоги, '
                    . 'наклейки, етикетки, папки та інша ділова поліграфія.'
                ),
                'url' => 'catalog',
                'link_label' => 'Перейти до каталогу',
            ],
            [
                'id' => 'photo-posters',
                'title' => 'Фото, постери та картини',
                'description' => (
                    'Друк фотографій, постерів, плакатів, '
                    . 'фотокниг і картин із вашим зображенням.'
                ),
                'url' => 'catalog/foto-kartini',
                'link_label' => 'Переглянути варіанти',
            ],
            [
                'id' => 'large-format',
                'title' => 'Зовнішня реклама',
                'description' => (
                    'Банери, вивіски, таблички, штендери, '
                    . 'оформлення вікон, фасадів і рекламних поверхонь.'
                ),
                'url' => 'catalog/zovnshnya-reklama',
                'link_label' => 'Переглянути продукцію',
            ],
            [
                'id' => 'textile-branding',
                'title' => 'Брендування текстилю',
                'description' => (
                    'Друк і вишивка на футболках, кепках, '
                    . 'еко-шоперах, спецодязі та іншій текстильній продукції.'
                ),
                'url' => 'catalog/print-on-garment',
                'link_label' => 'Переглянути текстиль',
            ],
            [
                'id' => 'drinkware-branding',
                'title' => 'Брендування посуду',
                'description' => (
                    'Чашки, стакани, термоси та інший посуд '
                    . 'із логотипом, написом або індивідуальним дизайном.'
                ),
                'url' => 'catalog/brenduvannya-posudu',
                'link_label' => 'Переглянути посуд',
            ],
            [
                'id' => 'flags-advertising',
                'title' => 'Прапори та рекламна продукція',
                'description' => (
                    'Класичні, настільні, фасадні й мобільні '
                    . 'прапори, віндери, вимпели та прапорна фурнітура.'
                ),
                'url' => 'catalog/prapori-vnderi',
                'link_label' => 'Переглянути прапори',
            ],
            [
                'id' => 'custom-orders',
                'title' => 'Індивідуальні замовлення',
                'description' => (
                    'Не знайшли потрібний виріб у каталозі? '
                    . 'Опишіть завдання, тираж, строки й бажаний результат.'
                ),
                'url' => 'contacts',
                'link_label' => 'Зв’язатися з нами',
            ],
        ];

        $serviceContact = [
            'phone' => trim((string)(
                $this->set['contacts_phone']
                ?? $this->set['phone']
                ?? ''
            )),
            'email' => trim((string)(
                $this->set['contacts_email']
                ?? $this->set['email']
                ?? ''
            )),
            'address' => trim(strip_tags((string)(
                $this->set['contacts_address']
                ?? $this->set['address']
                ?? ''
            ))),
        ];

        $serviceContact['map_url'] =
            $serviceContact['address'] !== ''
                ? (
                    'https://www.google.com/maps/search/?api=1&query='
                    . rawurlencode($serviceContact['address'])
                )
                : '';

        return compact(
            'data',
            'serviceGroups',
            'serviceContact'
        );
    }
}
