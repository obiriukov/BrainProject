<?php
/**
 * Created by PhpStorm.
 * User: CaguCT
 * Date: 8/7/17
 * Time: 11:11
 */

namespace modules\users\controller\admin;

use Core\System\DB;
use Core\System\Meta;
use Core\System\Setup;
use Core\System\SmartyProcessor;

class User
{
    function actionIndex($params = [])
    {
        if( \Modules\Admin\Model\Api::checkAccess() )
        {
            if( !empty($params['a']) )
            {
                if( method_exists($this, $params['a']) )
                {
                    if( \Modules\Users\Model\User::$is_login )
                    {
                        $params['b'] = !empty($params['b']) ? $params['b'] : null;
                        $this->{$params['a']}($params['b']);
                    }
                    else
                    {
                        $this->view->unAuth(Handler::MODULE_NAME);
                    }
                }
                else
                    \Core\System\Request::e404('Not found module');
            }
        }
    }

    function edit($id)
    {
        if( empty($id) )
        {
            $meta = [
                'title' => 'Ваши резюме',
                'description' => 'Список резюме на сайте ' . Setup::$SITEURL,
            ];
            Meta::getInstance()->setMetaArray($meta);

            $list = DB::getRows(\Modules\Users\Model\Api::DB_TABLE_USERS);
            SmartyProcessor::getInstance()->assign('list', $list);

            SmartyProcessor::getInstance()->moduleDisplay('admin/list.tpl', \Modules\Users\Controller\Handler::MODULE_NAME);
        }
        else
        {
            $user_id = User::getInstance()->getUserId();
            $resume = DB::getRow(Api::DB_TABLE_RESUME, 'user_id = ? AND resume_id = ?', [$user_id, $id]);

            if( $resume )
            {
                $meta = [
                    'title' => 'Резюме ' . $resume['position'],
                    'description' => 'Список резюме на сайте ' . Setup::$SITEURL,
                ];
                Meta::getInstance()->setMetaArray($meta);

                $user_info = DB::getRow(\Modules\Users\Model\Api::DB_TABLE_USERS_INFO, 'user_id = ?', [User::getInstance()->getUserId()]);
                $education = DB::getRows(Api::DB_TABLE_RESUME_EDUCATION, 'resume_id = ?', [$resume['resume_id']]);
                $experience = DB::getRows(Api::DB_TABLE_RESUME_EXPERIENCE, 'resume_id = ?', [$resume['resume_id']]);

                $assign = [
                    'resume' => $resume,
                    'user_info' => $user_info,
                    'education' => $education,
                    'experience' => $experience,
                ];
                SmartyProcessor::getInstance()->assign($assign);

                $this->view->viewSingle(Handler::MODULE_NAME);
            }
            else
                \Core\System\Request::e404();
        }
    }
}