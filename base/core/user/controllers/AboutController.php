<?php

namespace core\user\controllers;

class AboutController extends BaseUser
{
    protected function inputData()
    {
        parent::inputData();

        $this->frontendSurface = 'about';
        $this->frontendProfile = $this->resolveFrontendProfile();

        $this->styles[] = PATH
            . TEMPLATE
            . 'assets/css/forprint-about.css?v=20260726-05g11b-v4';

        $this->scripts[] = PATH
            . TEMPLATE
            . 'assets/js/surfaces/about.js?v=20260726-05g11b-v4';

        $about = is_array($this->set)
            ? $this->set
            : [];

        return compact('about');
    }
}
