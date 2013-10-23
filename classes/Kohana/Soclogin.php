<?php

defined('SYSPATH') or die('No direct script access.');

abstract class Kohana_Soclogin {

// Kohana_Soclogin instance
    protected static $_instance;
// soclogin config
    protected $_config;
    protected $networks;

    public static function factory(array $config = array()) {
        return new Soclogin($config);
    }

    /**
     * Loads configuration options.
     *
     * @param array $config
     */
    public function __construct($config = array()) {
// Save the config in the object
        $this->_config = $config;
        $this->netwokrs = $config['networks'];
    }

    /**
     * Singleton pattern.
     *
     * @return $this
     */
    public static function instance() {
        if (!isset(Soclogin::$_instance)) {
            $config = Kohana::$config->load('soclogin');
            Soclogin::$_instance = new Soclogin($config);
//             Soclogin::$networks = $config['networks'];
        }

        return Soclogin::$_instance;
    }

    public function __toString() {
        try {
            return $this->render();
        } catch (Exception $e) {
            Kohana_Exception::handler($e);
            return '';
        }
    }

    /**
     * Singleton pattern.
     *
     * @return $this
     */
    public static function networks() {

        return Kohana::$config->load('soclogin.networks');
    }

    public static function render($tpl='soclogin/soclogin') {
        $networks = Soclogin::instance()->networks();

        if (Auth::instance()->logged_in()) {
            $soclogins = DB::select('*')->from('soclogins')->where('user_id', '=', Auth::instance()->get_user()->id)->execute();
            if ($soclogins->count() > 0) {
                foreach ($soclogins as $slogin) {
                    $networks[$slogin['network']] = true;
                }
            }
        }

        $view = View::factory($tpl)->set('networks', $networks);
        return $view->render();
    }

    public static function render_form($message = 'Заполните, пожалуйста, эти поля.') {
        $fields = array('email');

        $view = View::factory('soclogin/fields')->set('fields', $fields)->set('message', $message);
        return $view->render();
    }

    public function vkontakte_auth() {
        $session = array();
        $member = FALSE;
        $valid_keys = array('expire', 'mid', 'secret', 'sid', 'sig');
        $cookieName = 'vk_app_' . $this->_config['vkID'];
//
        if (isset($_COOKIE[$cookieName])) {
//        $app_cookie = $_COOKIE[$cookieName];
            $session_data = explode('&', $_COOKIE[$cookieName], 10);
            foreach ($session_data as $pair) {
                list($key, $value) = explode('=', $pair, 2);
                if (empty($key) || empty($value) || !in_array($key, $valid_keys)) {
                    continue;
                }
                $session[$key] = $value;
            }
            foreach ($valid_keys as $key) {
                if (!isset($session[$key]))
                    return $member;
            }
            ksort($session);

            $sign = '';
            foreach ($session as $key => $value) {
                if ($key != 'sig') {
                    $sign .= ($key . '=' . $value);
                }
            }
            $sign .= $this->_config['vkSKey'];
            $sign = md5($sign);
            if ($session['sig'] == $sign && $session['expire'] > time()) {
                $member = array(
                    'identity' => intval($session['mid']),
                    'secret' => $session['secret'],
                    'sid' => $session['sid'],
                    'first_name' => $_GET['first_name'],
                    'last_name' => $_GET['last_name'],
                    'email' => (isset($_GET['email']) ? $_GET['email'] : ''),
                );
            }
        }
        return $member;
    }

    public function facebook_auth() {
        $member = FALSE;
        if (!isset($_GET['token']))
            return false;

        try {
            $c = curl_init("https://graph.facebook.com/me?access_token=" . $_GET['token']);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

            $response = curl_exec($c);
            $err = curl_getinfo($c, CURLINFO_HTTP_CODE);
            curl_close($c);
            $decoded_response = json_decode($response);
        } catch (ORM_Validation_Exception $e) {
            return false;
        }
        if (isset($decoded_response->error))
            return false;

        $member = array(
            'identity' => $decoded_response->id,
            'first_name' => $decoded_response->first_name,
            'last_name' => $decoded_response->last_name,
            'email' => $decoded_response->email,
            'gender' => $decoded_response->gender
        );

        return $member;
    }

    public function twitter_auth() {

        define('CONSUMER_KEY', $this->_config['twitter_key']); 
        define('CONSUMER_SECRET', $this->_config['twitter_secret']);
        define('OAUTH_CALLBACK', 'http://' . $_SERVER['HTTP_HOST'] . '/soclogin/login/?network=twitter');

        require Kohana::find_file('vendor', 'twitteroauth/twitteroauth');

        //если старый токен
        if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
            $_SESSION['oauth_status'] = 'oldtoken';
            //зачистим данные из сессии
            unset($_SESSION['oauth_token']);
            unset($_SESSION['oauth_token_secret']);
            unset($_SESSION['access_token']);
            //
            header('Location: ' . __FILE__);
        } elseif (isset($_REQUEST['oauth_token']) && isset($_REQUEST['oauth_verifier'])) {

            /* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
            $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

            /* Request access tokens from twitter */
            $access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

            /* Save the access tokens. Normally these would be saved in a database for future use. */
            $_SESSION['access_token'] = $access_token;

            /* Remove no longer needed request tokens */
            unset($_SESSION['oauth_token']);
            unset($_SESSION['oauth_token_secret']);

            /* If HTTP response is 200 continue otherwise send to connect page to retry */
            if (200 == $connection->http_code) {
                /* The user has been verified and the access tokens can be saved for future use */
                $_SESSION['status'] = 'verified';
                header('Location: ./index.php');
            } else {
                //зачистим данные из сессии
                unset($_SESSION['oauth_token']);
                unset($_SESSION['oauth_token_secret']);
                unset($_SESSION['access_token']);
            }
        }

        if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
            /* Build TwitterOAuth object with client credentials. */
            $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
            /* Get temporary credentials. */
            $request_token = $connection->getRequestToken(OAUTH_CALLBACK);
            /* Save temporary credentials to session. */
            $_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
            $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

            /* If last connection failed don't display authorization link. */
            switch ($connection->http_code) {
                case 200:
                    /* Build authorize URL and redirect user to Twitter. */
                    $url = $connection->getAuthorizeURL($token);
                    header('Location: ' . $url);
                    break;
                default:
                    /* Show notification if something went wrong. */
                    echo 'Could not connect to Twitter. Refresh the page or try again later.';
            }
            exit();
        } else {
            $access_token = $_SESSION['access_token'];

            /* Create a TwitterOauth object with consumer/user tokens. */
            $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

            /* If method is set change API call made. Test is called by default. */
            $content = $connection->get('account/verify_credentials');

            $member = array(
                'identity' => $content->id,
                'first_name' => $content->name,
                'last_name' => '',
                'email' => '',
            );
            return $member;
        }
        return false;
    }

    public function odnoklassniki_auth() {
//            var_dump();
//        echo "odn";
//            exit();
        if (!isset($_GET['code']))
            return false;

        $member = false;

        $AUTH['client_id'] = $this->_config['odklID'];
        $AUTH['client_secret'] = $this->_config['odklSecret'];
        $AUTH['application_key'] = $this->_config['odklAPkey'];
        try {
            $curl = curl_init('http://api.odnoklassniki.ru/oauth/token.do');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, 'code=' . $_GET['code'] . '&redirect_uri=' . urlencode('http://' . $_SERVER['HTTP_HOST'] . '/soclogin/login/?network=odnoklassniki') . '&grant_type=authorization_code&client_id=' . $AUTH['client_id'] . '&client_secret=' . $AUTH['client_secret']);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $s = curl_exec($curl);
            curl_close($curl);
            $auth = json_decode($s, true);

            if (!isset($auth['access_token']))
                return false;

            $curl = curl_init('http://api.odnoklassniki.ru/fb.do?access_token=' . $auth['access_token'] . '&application_key=' . $AUTH['application_key'] . '&method=users.getCurrentUser&sig=' . md5('application_key=' . $AUTH['application_key'] . 'method=users.getCurrentUser' . md5($auth['access_token'] . $AUTH['client_secret'])));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $s = curl_exec($curl);
            curl_close($curl);
            $user = json_decode($s, true);
        } catch (ORM_Validation_Exception $e) {

            return false;
        }
// var_dump($user);
//            exit();


        $member = array(
            'identity' => $user['uid'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => '',
            'gender' => $user['gender'],
            'birthday' => $user['birthday']
        );
        return $member;
    }

}
