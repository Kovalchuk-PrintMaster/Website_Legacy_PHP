<?php

    namespace core\admin\controllers;

    use core\admin\models\UserModel;
    use core\base\controllers\BaseController;
    use core\base\settings\Settings;

    class LoginController extends BaseController
    {
        protected $model;

        protected function inputData(){

        $this->model = UserModel::instance();

        $this->model->setAdmin();

        if (isset($this->parameters['logout'])){
            $this->checkAuth(true);
            $userLog = 'Logout user' . $this->userId['name'];
            $this->writeLog($userLog, 'user_log.txt', 'Access user');
            $this->model->logout();
            $this->redirect(PATH);
        }




        if ($this->isPost()){

            if (
                empty($_POST['token'])
                || empty($_SESSION['token'])
                || !hash_equals((string)$_SESSION['token'], (string)$_POST['token'])
            ) {
                http_response_code(400);
                exit('Invalid request token');
            }

            $timeClean = (new \DateTime())->modify('-' . BLOCK_TIME . ' hour')->format('Y-m-d H:i:s');

            $this->model->delete($this->model->getBlockedTable(), [
                'where' => ['time' => $timeClean],
                'operand' => ['<']
            ]);

            $ipUser = filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP) ? :
                (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP) ?:
                    @$_SERVER['REMOTE_ADDR']);

            $trying = $this->model->get($this->model->getBlockedTable(), [
                'field' => ['trying'],
                'where' => ['ip' => $ipUser]
            ]);

            $trying = !empty($trying) ? $this->clearNum($trying[0]['trying']) : 0;

            $success = 0;

            if (!empty($_POST['login']) && !empty($_POST['password']) && $trying<10){

                $login = trim($this->clearStr((string)$_POST['login']));
                $password = (string)$_POST['password'];
                $userData = $this->model->get($this->model->getAdminTable(), [
                    'fields' => ['id', 'name', 'password'],
                    'where' => [
                        'login' => $login,
                    ],
                    'limit' => 1,
                ]);

                $passwordAccepted = false;
                $legacyPassword = false;

                if (!empty($userData[0]['password'])) {
                    $storedPassword = (string)$userData[0]['password'];
                    $passwordInfo = password_get_info($storedPassword);

                    if (!empty($passwordInfo['algo'])) {
                        $passwordAccepted = password_verify($password, $storedPassword);
                    } elseif (preg_match('/^[a-f0-9]{32}$/i', $storedPassword)) {
                        $legacyPassword = hash_equals(
                            strtolower($storedPassword),
                            md5($password)
                        );
                        $passwordAccepted = $legacyPassword;
                    }
                }

                if (!$userData || !$passwordAccepted){

                    $method = 'add';

                    $where = [];

                    if ($trying){
                        $method = 'edit';

                        $where['ip'] = $ipUser;
                    }

                    $this->model->$method($this->model->getBlockedTable(),[

                        'fields' => [
                            'login' => $login,
                            'ip' => $ipUser,
                            'time' => 'NOW()',
                            'trying' => ++$trying],
                            'where' => $where
                    ]);

                    $error = 'No correct login or password - ' . $ipUser . ', login - ' . $login;

                }else{
                    $currentHash = (string)($userData[0]['password'] ?? '');

                    if (
                        $legacyPassword
                        || password_needs_rehash($currentHash, PASSWORD_DEFAULT)
                    ) {
                        $this->model->edit($this->model->getAdminTable(), [
                            'fields' => [
                                'password' => password_hash($password, PASSWORD_DEFAULT),
                            ],
                            'where' => [
                                'id' => (int)$userData[0]['id'],
                            ],
                        ]);
                    }

                    if (!$this->model->checkUser($userData[0]['id'])){

                        $error = $this->model->getLastError();
                    }else{
                        if (session_status() === PHP_SESSION_ACTIVE) {
                            session_regenerate_id(true);
                        }

                        $error = 'User login - ' . $login;
                        $success = 1;
                    }
                }

            }elseif ($trying>=10){

                $this->model->logout();

                $error = 'Maximum trying password input, try after 3 hour - ' . $ipUser;

            }else{

                $error = 'Fill request fields';
            }

            $displayName = trim((string)($userData[0]['name'] ?? $login ?? 'admin'));

            $_SESSION['res']['answer'] = $success
                ? '<div class="success">Вітаємо — '
                    . htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8')
                    . '</div>'
                : (preg_split('/\s*\-/', $error, 2, PREG_SPLIT_NO_EMPTY)[0] ?? 'Access denied');

            $this->writeLog($error, 'user_log.txt', 'Access user');

            $path = null;

            $success && $path = PATH . Settings::get('routes')['admin']['alias'];

            $this->redirect($path);

        }

        return $this->render('', ['adminPath' => Settings::get('routes')['admin']['alias']]);
    }

    }
