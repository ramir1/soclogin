<?php

defined('SYSPATH') OR die('No direct script access.');

class Controller_Soclogin extends Controller_Site {

    public function action_index() {
        $view = Soclogin::render();
        $this->template->block_center = array($view);
    }

    public function action_dislogin($network = false) {
        if (isset($_GET['network']))
            $network = $_GET['network'];

        if (Auth::instance()->logged_in() && $network) {
            $soclogin = ORM::factory('Soclogin', array('user_id' => $this->user->id, 'network' => $network));
            $soclogin->delete();
        }
        HTTP::redirect('/soclogin/');
    }

    public function action_login() {
        if (isset($_GET['network']))
            $network = $_GET['network'];
        else
            HTTP::redirect('/register');

        switch ($network) {
            case 'vkontakte':
                $member = Soclogin::instance()->vkontakte_auth();
                break;
            case 'odnoklassniki':
                $member = Soclogin::instance()->odnoklassniki_auth();
                break;
            case 'twitter':
                $member = Soclogin::instance()->twitter_auth();
                break;
            case 'facebook':
                $member = Soclogin::instance()->facebook_auth();
                break;
            default:
                $member = false;
                break;
        }

        if (!$member)
            HTTP::redirect('/register/?no_member');


        if (Auth::instance()->logged_in()) {
            $user = Auth::instance()->get_user();

            //привязана ли эта сеть к этому пользователю
            $soclogin = ORM::factory('Soclogin', array('user_id' => $user->id, 'network' => $network));
                        
            //проверим нет ли уже для такого мембера
            $soclogin2 = ORM::factory('Soclogin', array('identity' => $member['identity'], 'network' => $network));
            
//нет - привязываем
            if (!$soclogin->loaded() && !$soclogin2->loaded()) {

                $user_n['user_id'] = $user->id;

                $user_n['network'] = $network;
                $user_n['identity'] = $member['identity'];
                $soclogin->values($user_n, array(
                    'user_id',
                    'identity',
                    'network',
                ))->create();
                HTTP::redirect('/cabinet/personal');
            } 
            
            if($soclogin->loaded()){
                //привязана, проверяем тот ли идентификатор, иначе меняем
                if ($soclogin->identity != $member['identity']) {
                    $soclogin->identity = $member['identity'];
                    $soclogin->save();
                }
                HTTP::redirect('/cabinet/personal');
            }
            //если этот идентификатор привязан к другому аккаунту, меняем на текущего
            if($soclogin2->loaded()){
                //привязана, проверяем тот ли аккаунт, иначе меняем
                if ($soclogin2->user_id != $user->id) {
                    $soclogin2->user_id = $user->id;
                    $soclogin2->save();
                }
                HTTP::redirect('/cabinet/personal');
            }


            HTTP::redirect('/cabinet/personal');
        } else {
            $soclogin = ORM::factory('Soclogin', array('identity' => $member['identity'], 'network' => $network));

            if ($soclogin->loaded()) {

                //Пользователь авториз
                $getUser = ORM::factory('User', $soclogin->user_id);
                if ($getUser->loaded()) {
                    Auth::instance()->force_login($getUser);
                    HTTP::redirect('/cabinet/personal');
                } else {
                    //Запись о пользователе есть в таблице Soclogin, но нет такого пользователя. Удаляем.
                    $soclogin->delete();
                    $params = '?';
                    foreach ($_GET as $k => $v) {
                        $params.='&' . $k . '=' . $v;
                    }
                    HTTP::redirect(Request::initial()->uri() . $params);
                }
            } else {
                //проверить введенный емаил
                if (isset($_GET['email']))
                    $member['email'] = $_GET['email'];
//                var_dump($member);
//                exit();
                if ($member['email'] == '') {
                    $view = Soclogin::render_form();
                    $this->template->block_center = array($view);
                    return;
                }
//проверим нет ли пользователя с таким же эл. адресом
                $user_email = ORM::factory('User', array('email' => $member['email']));
                if ($user_email->loaded()) {
                    $message = 'Пользователь с таким адресом уже зарегистрирован. <br/>'
                            . 'Войдите на сайт используя свой эл.адрес и пароль. <br/>'
                            . ' Или укажите другой эл. адрес.';
                    $view = Soclogin::render_form($message);
                    $this->template->block_center = array($view);
                    return;
                }

                try {
                    $user_new = array(
                        'username' => $member['email'],
                        'password' => rand(10000000, 99999999),
                        'fi_name' => $member['first_name'],
                        'la_name' => $member['last_name'],
                        'email' => $member['email']
                    );
                    $user_new['password_confirm'] = $user_new['password'];

                    $user = ORM::factory('User')->values($user_new, array(
                                'username',
                                'password',
                                'fi_name',
                                'la_name',
                                'email'
                            ))->create();
                    $user->add('roles', ORM::factory('Role', array('name' => 'login')));  // Добавляем роль login

                    $this->auth->force_login($user);

                    $user_n['user_id'] = $user->id;
                    $user_n['network'] = $network;
                    $user_n['identity'] = $member['identity'];


                    $soclogin->values($user_n, array(
                        'user_id',
                        'identity',
                        'network',
                    ))->create();
                    HTTP::redirect('/cabinet/personal');
                } catch (ORM_Validation_Exception $e) {
                    $message = 'Ошибка регистрации. Обратитесь к администратору.';
                    $view = Soclogin::render_form()->bind('message', $message);
                    $this->template->block_center = array($view);
                }
            }
        }
        HTTP::redirect('/');
    }

//
//    public function _action_copy() {
////Конвертор
//        $start = (isset($_GET['start'])?$_GET['start']:0);
//        $limit = (isset($_GET['limit'])?$_GET['limit']:10);
//        echo "<a href='?start={$start}+{$limit}&limit={$limit}'>Дальше</a>";
//
//        $users = DB::query(Database::SELECT, 'SELECT * FROM users LIMIT ' . $start . ',' . $limit . '')->execute();
//        $networks = array(
//            'vk' => 'vkontakte',
//            'fb' => 'facebook',
//            'tw' => 'twitter',
//            'od' => 'odnoklassniki',
//        );
//        foreach ($users as $user) {
//            echo '<br>' . $user['username'];
//            $all = array();
//            if (preg_match("/^(vk|fb|tw|od)([0-9]{6,20})$/", $user['username'], $all)) {
//                $network = $networks[$all[1]];
//                $identity = $all[2];
//
//                echo ' <b>' . $user['id'] . ' ' . $network . ' ' . $identity . '</b> ';
//
//                $soclogin = ORM::factory('Soclogin', array('user_id' => $user['id'], 'network' => $network));
//
//                if (!$soclogin->loaded()) {
//                    echo ' Добавляем';
//                    $user_n['user_id'] = $user['id'];
//                    $user_n['network'] = $network;
//                    $user_n['identity'] = $identity;
//
//                    $soclogin->values($user_n, array(
//                        'user_id',
//                        'identity',
//                        'network',
//                    ))->create();
//                }
//            }
//        }
//
//        exit();
//    }
}
