<?php
    
    namespace core\admin\models;
    
    use core\base\controllers\BaseMethods;
    use core\base\controllers\Singleton;
    use core\base\exceptions\AuthExeption;
    use core\base\models\BaseModel;
    use core\base\models\Crypt;

    class UserModel extends BaseModel
    {
        use Singleton;
    
        use BaseMethods;
    
        private $cookieName = 'identifier';
    
        private $cookieAdminName = 'WQEngineCache';
    
        private $userData = [];
    
        private $error;
    
        private $userTable = 'visitors';
    
        private $adminTable = 'user';
    
        private $blockedTable = 'blocked_access';
    
        public function getAdminTable()
        {
            return $this->adminTable;
        }
    
        public function getBlockedTable()
        {
            return $this->blockedTable;
        }
    
        public function getLastError()
        {
            return $this->error;
        }
    
        public function setAdmin()
        {
        
            $this->cookieName = $this->cookieAdminName;
            $this->userTable = $this->adminTable;
        
            if (!in_array($this->userTable, $this->showTables())) {
            
                $query = 'create table ' . $this->userTable . '
                    (
                        id int auto_increment primary key,
                        name varchar (255) null,
                        login varchar (255) null,
                        password varchar (32) null,
                        credentials text null
                    )
                    charset = utf8
                ';
            
                if (!$this->query($query, 'u')) {
                    exit('Error create table ' . $this->userTable);
                }
            
                // ForPrint safety:
// Automatic hardcoded default admin user creation is disabled.
// Admin bootstrap must be performed through a controlled local
// maintenance step with local-only credentials.
// Public admin remains blocked until auth/session hardening is complete.
            }
        
            if (!in_array($this->blockedTable, $this->showTables())) {
            
                $query = 'create table ' . $this->blockedTable . '
                    (
                        id int auto_increment primary key,
                        login varchar (255) null,
                        ip varchar (32) null,
                        try tinyint(1) null,
                        time datetime null
                    )
                    charset = utf8
                ';
            
                if (!$this->query($query, 'u')) {
                    exit('Error create table ' . $this->blockedTable);
                
                }
            }
        }
    
        public function checkUser($id = false, $admin = false)
        {
        
            $admin && $this->userTable !== $this->adminTable && $this->setAdmin();
        
            $method = 'unPackage';
        
            if ($id) {
                $this->userData['id'] = $id;
            
                $method = 'set';
            }
        
            try {
            
                $this->$method();
            
            } catch (AuthExeption $e) {
            
                $this->error = $e->getMessage();
            
                !empty($e->getCode()) && $this->writeLog($this->error, 'log_user.txt');
            
                return false;
            }
        
            return $this->userData;
        
        }
    
        private function set()
        {
        
            $cookieString = $this->package();
        
            if ($cookieString) {
                setcookie($this->cookieName, $cookieString, time() + 60 * 60 * 24 * 365 * 10, PATH);
                return true;
            
            }
            throw new AuthExeption('Error create cookie', 1);
        }
    
        private function package()
        {
        
            if (!empty($this->userData['id'])) {
            
                $data['id'] = $this->userData['id'];
            
                $data['version'] = COOKIE_VERSION;
            
                $data['cookieTime'] = date('Y-m-d H:i:s');
            
                return Crypt::instance()->encrypt(json_encode($data));
            }
            throw new AuthExeption('Incorrect identificator of user - ' . $this->userData['id'], 1);
        }
    
        private function unPackage()
        {
            if (empty($_COOKIE[$this->cookieName]))
                throw new AuthExeption('Absent cookie of user');
        
            $data = json_decode(Crypt::instance()-> decrypt($_COOKIE[$this->cookieName]), true);
        
            if (empty($data['id']) || empty($data['version']) || empty($data['cookieTime'])) {
            
                $this->logout();
                
                throw new AuthExeption('Incorrect data in cookie of user', 1);
            
            }
            $this->validate($data);
            
            $this->userData = $this->get($this->userTable,[
                'where' => ['id' => $data['id']]
            ]);
            
            if (!$this->userData){
           
                $this->logout();
                
                throw new AuthExeption('Not find data in table' . $this->userTable. 'on indeficator' , $data['id'], 1);
            }
            $this->userData = $this->userData['0'];
            return true;
        }
    
        private function validate($data)
        {
            if (!empty(COOKIE_VERSION)) {
            
                if ($data['version'] !== COOKIE_VERSION) {
                
                    $this->logout();
                
                    throw new AuthExeption('Incorrect version of cookie');
                }
            }
            if (!empty(COOKIE_TIME)){
                
                if ((new \DateTime()) > (new \DateTime($data['cookieTime']))->modify(COOKIE_TIME . 'minutes')){
                
                    throw new AuthExeption('Up limit time no active user');
                }
            }
        }
        public function logout(){
            
            setcookie($this->cookieName, '', 1, PATH);
        }
    }