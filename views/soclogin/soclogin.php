<? if (Auth::instance()->logged_in()) { ?>

    <div class="soclogin log-in">
        Привяжите свой аккаунт к соцсети для быстрого входа на сайт:
        <br>
        <ul class="">
            <? if (!$networks['vkontakte']) { ?> <li><a class="vk_button login_vk" title="ВКонтакте" rel="nofollow" href="#" alt="Vkontakte"></a></li><? } ?>
            <? if (!$networks['odnoklassniki']) { ?>  <li><a class="ok_button login_odkl" title="Одноклассники" rel="nofollow" href="#"></a></li><? } ?>
            <? if (!$networks['facebook']) { ?> <li><a class="fb_button login_fb" title="Facebook" rel="nofollow" href="#"></a></li><? } ?>
            <? if (!$networks['twitter']) { ?> <li><a class="tw_button login_twitter" title="Twitter" rel="nofollow" href="#"></a></li><? } ?>
        </ul>
    </div>

    <div class="soclogin log-in">
        На данный момент у Вас привязаны такие соцсети:
        <br>
        <ul class="">
            <? if ($networks['vkontakte']) { ?> <li><a class="vk_button soclogin_dislogin_vk" title="ВКонтакте" rel="nofollow" href="/soclogin/dislogin/?network=vkontakte" alt="Vkontakte"></a></li><? } ?>
            <? if ($networks['odnoklassniki']) { ?>  <li><a class="ok_button soclogin_dislogin_odkl" title="Одноклассники" rel="nofollow" href="/soclogin/dislogin/?network=odnoklassniki"></a></li><? } ?>
            <? if ($networks['facebook']) { ?> <li><a class="fb_button soclogin_dislogin_fb" title="Facebook" rel="nofollow" href="/soclogin/dislogin/?network=facebook"></a></li><? } ?>
            <? if ($networks['twitter']) { ?> <li><a class="tw_button soclogin_dislogin_twitter" title="Twitter" rel="nofollow" href="/soclogin/dislogin/?network=twitter"></a></li><? } ?>
        </ul>
    </div>
<? } else { ?>


    <br>
    <div class="soclogin log-in">
        Зарегистрируйтесь или войдите с помощью соцсетей<br>
        <ul class="">
            <li><a class="vk_button login_vk" title="ВКонтакте" rel="nofollow" href="/soclogin/login/?network=vkontakte" alt="Vkontakte"></a></li>
            <li><a class="ok_button login_odkl" title="Одноклассники" rel="nofollow" href="/soclogin/login/?network=odnoklassniki"></a></li>
            <li><a class="fb_button login_fb" title="Facebook" rel="nofollow" href="/soclogin/login/?network=facebook"></a></li>
            <li><a class="tw_button login_twitter" title="Twitter" rel="nofollow" href="/soclogin/login/?network=twitter"></a></li>
        </ul>
    </div>
<? }
?>
<style>
    .soclogin ul{
        list-style: none;
    }
    .soclogin{
        clear: both;
    }
    #main .soclogin ul li {
        float:left;
        margin: 5px;
        background: none;
    }
</style>
<script>
    $(document).ready(function() {
        $(document).on('click', '.soclogin_dis_vkontakte', function() {
            window.location = '/soclogin/dislogin/vkontakte';
        });
        $(document).on('click', '.soclogin_dis_facebook', function() {
            window.location = '/soclogin/dislogin/facebook';
        });
        $(document).on('click', '.soclogin_dis_twitter', function() {
            window.location = '/soclogin/dislogin/twitter';
        });
        $(document).on('click', '.soclogin_dis_odnoklassniki', function() {
            window.location = '/soclogin/dislogin/odnoklassniki';
        });
    });
</script>