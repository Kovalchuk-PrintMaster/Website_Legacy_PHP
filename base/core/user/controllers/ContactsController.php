<?php

namespace core\user\controllers;

class ContactsController extends BaseUser
{
    protected function inputData()
    {
        parent::inputData();

        $contactsPage = $this->model->get('information', [
            'where' => [
                'alias' => 'contacts',
                'visible' => 1,
            ],
            'limit' => 1,
        ]);

        $contactsPage = $contactsPage ? $contactsPage[0] : [
            'name' => 'Контакти',
            'content' => '',
            'description' => '',
            'keywords' => '',
        ];

        return compact('contactsPage');
    }
}
