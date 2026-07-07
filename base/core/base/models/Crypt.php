<?php


namespace core\base\models;


use core\base\controllers\Singleton;

class Crypt
{
    use Singleton;

    private $cryptMethod = 'AES-128-CBC';
    private $hasheAlgoritm = 'sha256';
    private $hasheLength = 32;
    private $strongResult = null;

    public function encrypt($str){
        $iv_length = openssl_cipher_iv_length($this->cryptMethod);
        $iv = openssl_random_pseudo_bytes($iv_length);
        $cipherText = openssl_encrypt($str, $this->cryptMethod, CRYPT_KEY, OPENSSL_RAW_DATA, $iv);

        $hmac = hash_hmac($this->hasheAlgoritm, $cipherText, CRYPT_KEY, true);

        return $this->cryptCombine($cipherText, $iv, $hmac);

    }

    public function decrypt($str){

        $iv_length = openssl_cipher_iv_length($this->cryptMethod);
        $crypt_data = $this->cryptUnCombine($str, $iv_length);
        $original_plaintext = openssl_decrypt($crypt_data['str'], $this->cryptMethod, CRYPT_KEY, OPENSSL_RAW_DATA, $crypt_data['iv']);
        $calmac = hash_hmac($this->hasheAlgoritm, $crypt_data['str'], CRYPT_KEY, true);

            if(hash_equals($crypt_data['hmac'], $calmac)) return $original_plaintext;
        return false;
    }

    protected function  cryptCombine($str, $iv, $hmac){
        $new_str = '';
        $str_len = strlen($str);

        $counter = (int)ceil(strlen(CRYPT_KEY)/($str_len + $this->hasheLength));

        $progress= 1;

            if($counter >= $str_len) $counter=1;
                for($i=0; $i < $str_len; $i++){

                    if($counter < $str_len){

                        if($counter === $i){
                            $new_str .= substr($iv, $progress - 1,1);
                            $progress ++;
                            $counter += $progress;
                        }
                    }else{
                        break;
                    }
                    $new_str .= substr($str, $i, 1);
                }
                $new_str .=substr($str, $i);
                $new_str .= substr($iv, $progress -1);

                $new_str_half = (int)ceil(strlen($new_str)/2);

                $new_str= substr($new_str, 0, $new_str_half) . $hmac . substr($new_str, $new_str_half);

                return base64_encode($new_str);
    }

    protected function cryptUnCombine($str, $iv_length){

        $crypt_data=[];

        $str = base64_decode($str);

        $hashe_position = (int)ceil((strlen($str) / 2) - ($this->hasheLength / 2));

        $crypt_data['hmac'] = substr($str, $hashe_position, $this->hasheLength);

        $str = str_replace($crypt_data['hmac'], '', $str );

        $counter = (int)ceil(strlen(CRYPT_KEY)/(strlen($str)- $iv_length + $this->hasheLength));
        $progress = 2;
        $crypt_data['str'] = '';
        $crypt_data['iv'] = '';

        for($i=0, $iMax = strlen($str); $i < $iMax; $i++) {

            if ($iv_length + strlen($crypt_data['str']) < strlen($str)) {

                if ($i === $counter) {
                    $crypt_data['iv'] .= substr($str, $counter, 1);
                    $progress++;
                    $counter += $progress;
                } else {
                    $crypt_data['str'] .= substr($str, $i, 1);
                }
            } else {
                $crypt_data_length = strlen($crypt_data['str']);
                $crypt_data['str'] .= substr($str, $i, strlen($str) - $iv_length - $crypt_data_length);
                $crypt_data['iv'] .= substr($str, $i + (strlen($str) - $iv_length - $crypt_data_length));
                break;
            }
        }

        return $crypt_data;
    }
}
