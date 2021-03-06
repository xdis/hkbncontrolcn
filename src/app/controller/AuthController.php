<?php

namespace app\controller;

use app\db\LogDb;
use app\domain\Auth;
use app\view\SmartyTemplateView;
use lib\framework\Helper;
use lib\helper\HttpHelper;
use app\controller\session\PhpSession;

class AuthController
{
    public function loginForm() {
        $view = new SmartyTemplateView();
        echo $view->render('login_form');
    }

    public function login($username = '', $password = '')
    {
        $auth = new Auth();
        $row = $auth->isAuthenticate($username, $password);
        if ($row) {
            $this->setLogin($row);
            $this->log('Auth', 'Login: ' . $username);
        } else {
            $this->setLogout();
            $this->log('Auth', 'Login Fail: ' . $username);
        }
        $success = $this->isLogin();
		if(!$success) {
			$error = '用户名称或密码不正确／帐号过期／帐号已登入';
			HttpHelper::redirect('login.php?msg='.$error);
		}else {
			$this->redirect($success);
		}
    }

    private function setLogin($rowUserData) {
        $session = new PhpSession();
        $session->setVar('userId', $rowUserData['User_Id']);
        $session->setVar('username', $rowUserData['User_Username']);
        $session->setVar('userDisplayName', $rowUserData['User_DisplayName']);
        $session->setVar('userLevel', $rowUserData['User_Level']);
    }

    private function setLogout() {
        $session = new PhpSession();
		$session->setVar('userId', '');
		$session->setVar('username', '');
		$session->setVar('userDisplayName', '');
        $session->setVar('userLevel', '');
        session_destroy();
        setcookie(session_name(), '', time() - 300, '/', '', 0);
    }

    public function logout()
    {
        $session = new PhpSession();
        $username = $session->getVar('username');

        $this->setLogout();

        $this->log('Auth', 'Logout: ' . $username);
        $this->redirect($this->isLogin());
    }

    public function isLogin() {
        $session = new PhpSession();
        $sessionUserId = $session->getVar('userId');
        if (empty($sessionUserId)) {
            return false;
        }

        //temp before a more security way
        //
        // check also the ip
        //
        //$this->user->setUserByUsername($sessionUserId);

        return true;
        //TODO need a more security way
//		if ($sessionUserId == $this->user->getUserId()) {
//			return true;
//		} else {
//			return false;
//		}
    }

    private function redirect($isLogin) {
        if ($isLogin) {
            HttpHelper::redirect('index.php');
        } else {
            HttpHelper::redirect('login.php');
        }
    }

    private function log($type, $message)
    {
        $db = Helper::createDb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        $log = new LogDb($db);
        $log->log($type, $message);
    }

}