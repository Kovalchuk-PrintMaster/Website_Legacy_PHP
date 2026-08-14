<?php


namespace core\base\controllers;


trait BaseMethods
{

    protected function clearStr($str)
    {
        if (is_array($str)) {
//            foreach ($str as $key => $item) $str[$key] = trim(strip_tags($item));
            foreach ($str as $key => $item) $str[$key] = $this->clearStr($item);
            return $str;
        } else {
            return $str = trim(strip_tags($str));
        }
    }

    protected function clearNum($num){
        return (!empty($num) && preg_match('/\d/', $num)) ?
            preg_replace('/[^\d.]/', '', $num)*1 : 0;
    }
    protected function isPost(){
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }
    protected function isAjax(){
        return isset ($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH']) === 'XMLHttpRequest';

    }

    protected function redirect($http = false, $code = false){
        if($code){
            $codes = ['301'=>'HTTP/1.1 301 Move Permanently'];

            if ($codes[$code]) header($codes[$code]);
        }

            if($http) $redirect = $http;
            else $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : PATH;
            header ("Location: $redirect");
            exit;
    }

    protected function getStyles(){
        if($this->styles){
            foreach ($this->styles as $style) echo '<link rel="stylesheet" href="' . $style . '">';
        }
    }

    protected function getScripts(){
        if($this->scripts){
            foreach ($this->scripts as $script) echo '<script src="' . $script . '"></script>';
        }
    }

    protected function writeLog($message, $file = 'log.txt', $event = 'Fault'){
        $dateTime = new \dateTime();
        $str = $event . ':' . $dateTime-> format('d-m-y G:i:s') . ' - ' . $message . "\r\n";
        file_put_contents('log/'.$file, $str, FILE_APPEND);
    }

    protected function getController(){
        return $this->controller ? :
            $this->controller = preg_split('/_?controller/', strtolower(preg_replace('/([^A-Z])([A-Z])/',
                '$1_$2',(new \ReflectionClass($this))->getShortName())), 0, PREG_SPLIT_NO_EMPTY)[0];
    }

    protected function addSessionData($arr=[]){
        if(!$arr) $arr = $_POST;

        foreach ($arr as $key=>$item) {
            $_SESSION['res'][$key] = $item;
        }
        $this->redirect();
    }

    protected function dateFormat($date){

        if (!$date){

            return $date;

        }

        $daysArr = [
            'Sunday' => 'Неділя',
            'Monday' => 'Понеділок',
            'Tuesday' => 'Вівторок',
            'Wednesday' => 'Середа',
            'Thursday' => 'Червер',
            'Friday' => 'П`ятниця',
            'Saturday' => 'Субота',
        ];

        $monthersArr = [

            1 => 'Січень',
            2 => 'Лютий',
            3 => 'Березень',
            4 => 'Квітень',
            5 => 'Травень',
            6 => 'Червень',
            7 => 'Липень',
            8 => 'Серпень',
            9 => 'Вересень',
            10 => 'Жовтень',
            11 => 'Листопад',
            12 => 'Грудень',

        ];

        $dateArr = [];

        $dateData = new \DateTime($date);

        $dateArr['year'] = $dateData->format('Y');

        $dateArr['month'] = $monthersArr[$this->clearNum($dateData->format('m'))];

        $dateArr['monthFormat'] = preg_match('/д$/u', $dateArr['month']) ? $dateArr['month'] . 'а' :

            preg_replace('/ень/u', 'ня',  $dateArr['month']);

        $dateArr['weekDay'] = $daysArr[$dateData->format('l')];

        $dateArr['day'] = $dateData->format('d');

//        $dateData['time'] =  $dateData->format('H:i:s');
//        data base no return time lesson 128

        $dateArr['format'] = mb_strtolower($dateArr['day'] . ' ' .

            $dateArr['monthFormat'] . ' ' . $dateArr['year']);

        return $dateArr;

    }
}
