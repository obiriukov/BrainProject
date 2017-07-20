<?php
/**
 * Created by PhpStorm.
 * User: CaguCT
 * Date: 7/17/17
 * Time: 17:17
 */

namespace Modules\Users\Model;

use Core\System\DB;
use Core\System\Request;
use Core\System\Setup;

class Api
{
    const DB_TABLE_USERS = 'users';
    const DB_TABLE_USERS_HASH = 'users_hash';
    const DB_TABLE_USERS_INFO = 'users_info';
    //
    protected static $_instance;

    static function getInstance()
    {
        if( self::$_instance === null )
            self::$_instance = new self();

        return self::$_instance;
    }

    function autoLogin($hash)
    {
        $user = DB::getRow('SELECT user.* 
                         FROM ' . Setup::$DB_PREFIX . self::DB_TABLE_USERS_HASH . ' AS user_hash
                         LEFT JOIN ' . Setup::$DB_PREFIX . self::DB_TABLE_USERS . ' AS user
                            ON user_hash.user_id = user.user_id
                         WHERE user_hash.hash = ?', [$hash]);

        if( $user )
        {
            return new User($user);
        }

        return false;
    }

    function login($login, $pass)
    {
        if( !empty($login) )
        {
            $user = DB::getRow(self::DB_TABLE_USERS, 'login = ?', [$login]);

            if( $user )
            {
                if( $user->pass === self::passHash($pass, $user->salt) )
                {
                    // todo-caguct: cookie + session
                    $hash = urlencode(self::generateSalt(10));
                    $this->setHash($user->user_id, $hash);

                    return true;
                }
            }
        }

        return false;
    }

    function setHash($user_id, $hash)
    {
        DB::insert(self::DB_TABLE_USERS_HASH, [
            'user_id' => $user_id,
            'hash' => $hash,
        ]);
        Request::getInstance()->setCookie('hash', $hash, time() + 60 * 60 * 24 * 30, '/');
    }

    function loginByHash()
    {
    }

    function logout($redirect)
    {
        $redirect = !empty($redirect) ? $redirect : '/';

        if( $redirect === Setup::$HOME . '/auth/logout' )
            $redirect = '/';

        Request::getInstance()->setCookie('hash', '', time() - 3600, '/');
        \Core\System\Request::redirect($redirect);
    }

    function getUserByLogin($login)
    {
        return DB::getRow(self::DB_TABLE_USERS, 'login = ?', [$login]);
    }

    function createUser($user)
    {
        if( $user )
            DB::insert(self::DB_TABLE_USERS, $user);
    }

    static function isLogin()
    {
        return User::$is_login;
    }

    static function authProcessing($redirect = '/')
    {
        $redirect = !empty($redirect) ? $redirect : '/';

        if( $redirect === Setup::$HOME . '/auth/login' )
            $redirect = '/';

        $login = \Core\System\Request::getInstance()->get('login', 'post', \Core\System\Request::TYPE_STRING);
        $pass = \Core\System\Request::getInstance()->get('pass', 'post', \Core\System\Request::TYPE_STRING);

        if( !empty($login) )
        {
            if( \Modules\Users\Model\Api::getInstance()->login($login, $pass) )
            {
                \Core\System\Request::redirect($redirect);
            }
            else
                \Core\System\SmartyProcessor::getInstance()->assign('error', 'Wrong login or password');
        }
    }

    static function registerProcessing()
    {
        $error = '';

        $params = [
            'login' => \Core\System\Request::getInstance()->get('login', 'post', \Core\System\Request::TYPE_STRING),
            'mail' => \Core\System\Request::getInstance()->get('mail', 'post', \Core\System\Request::TYPE_STRING),
            'pass' => \Core\System\Request::getInstance()->get('pass', 'post', \Core\System\Request::TYPE_STRING),
            'pass_repeat' => \Core\System\Request::getInstance()->get('pass_repeat', 'post', \Core\System\Request::TYPE_STRING),
        ];

        if( empty($params['login']) )
            $error = 'Login is empty';

        if( empty($params['mail']) )
            $error = 'Mail is empty';

        if( empty($params['pass']) )
            $error = 'Password is empty';

        if( empty($params['pass_repeat']) )
            $error = 'Password repeat is empty';

        if( $params['pass'] !== $params['pass_repeat'] )
            $error = 'Password is not equal to password repeat';

        if( empty($error) )
        {
            unset($params['pass_repeat']);
            $register = \Modules\Users\Model\Api::getInstance()->register($params);

            if( $register === true )
            {
                \Core\System\SmartyProcessor::getInstance()->assign('info', 'Registration success');
            }
            else
                $error = $register;
        }

        \Core\System\SmartyProcessor::getInstance()->assign('error', $error);
    }

    function register($user)
    {
        $return = true;

        // todo-caguct: test for params

        if( true ) // todo-caguct: change if
        {
            $user['salt'] = \Modules\Users\Model\Api::generateSalt(40);
            $user['pass'] = self::passHash($user['pass'], $user['salt']);

            self::getInstance()->createUser($user);

            return $return;
        }

        return $return;
    }

    static function passHash($pass, $user_salt)
    {
        $pass = sha1($pass . $user_salt . SALT);

        return $pass;
    }

    static function generateSalt($num = 10, $sign = true)
    {
        $a = range(0, 9);
        $b = range('a', 'z');
        $c = range('A', 'Z');
        $d = range('!', '@');

        $arr = array_merge($a, $b);
        $arr = array_merge($arr, $a);
        $arr = array_merge($arr, $c);

        if( $sign === true )
            $arr = array_merge($arr, $d);

        $key = '';
        $rand = microtime(true);

        for( $i = 0; $i < $num; ++$i )
        {
            shuffle($arr);
            $countArr = count($arr) - 1;
            $key .= $arr[ceil(round(($rand * 1000 - floor($rand * 1000)), 2) * $countArr)];
            $rand = microtime(true);
        }

        return $key;
    }
}