<?php

namespace core\user\helpers;

trait ValidationHelper
{

    protected function emptyField($value, $answer){

        $value = $this->clearStr($value);

        if (empty($value)){

            $this->sendError('Не заповнено поле '. $answer);
        }

        return $value;
    }

    protected function numericField($value, $answer){

        $value = preg_replace('/\D/', '', $value);

        !$value && $this->sendError('Ой, здається це не коректне поле ', $answer);

        return $value;

    }

    protected function phoneField($value, $answer = null){

        $value = preg_replace('/\D/', '', $value);

        if(strlen($value) === 12){

            $value = preg_replace('/^0/', '+', $value ); // lesson 145

            return $value;
        }

    }

    protected function emailField($value, $answer){

        $value = $this->clearStr($value);

        if (!preg_match('/^[\w\-\.]+@[\w\-]+\.[\w\-]+/', $value)){

            $this->sendError('Упс! Здається ви ввели не вірній email. Спробуйте ще раз :) не заповнено поле ' . $answer);
        }

        return $value;
    }

    protected function sendError($text, $class = 'error'){

        $_SESSION['res']['answer'] = '<div class="' . $class . '">' . $text . '</div>';

        if ($class === 'error'){

            $this->addSessionData();

        }

        $this->redirect();

    }

    protected function sendSuccess($text, $class = 'success'){

        $this->sendError($text, $class);
    }

}
