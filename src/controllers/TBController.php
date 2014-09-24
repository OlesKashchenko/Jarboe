<?php 

namespace Yaro\TableBuilder;


class TBController extends \Controller
{

    public function showLogin()
    {
        if (\Sentry::check()) {
            return \Redirect::to(\Config::get('table-builder::admin.uri'));
        }
        
        return \View::make('admin::login');
    } // end showLogin
 
    public function postLogin()
    {
        try {
            \Sentry::authenticate(
                array(
                    'email'    => \Input::get('email'), 
                    'password' => \Input::get('password')
                ), 
                \Input::has('rememberme')
            );
            
            $onLogin = \Config::get('table-builder::admin.on_login'); 
            $onLogin();
            
            return \Redirect::to(\Config::get('table-builder::admin.uri'));
            
        } catch (\Cartalyst\Sentry\Users\UserNotFoundException $e) {
            // FIXME: show errors
            return \Redirect::to(\Config::get('table-builder::admin.uri'));
        }
    } // end 
 
    public function doLogout()
    {
        Sentry::logout();
        $onLogout = \Config::get('table-builder::admin.on_logout');
        
        return $onLogout();
    } // end doLogout
    
    
    public function fetchByUrl()
    {
        $url = \Input::get('url');

        $embera = new \Embera\Embera();
        $info = $embera->getUrlInfo($url);
        
        $info['status'] = true;
        
        return \Response::json($info);
    } // end fetchByUrl
    
    public function doEmbedToText()
    {
        $text = \Input::get('text');

        $config = array(
            'params' => array(
                'width'  => 640,
                'height' => 360
            )
        );
        $embera = new \Embera\Embera($config);
        $res = $embera->autoEmbed($text);
        
        $info = array(
            'status' => true,
            'html' => $res
        );
        return \Response::json($info);
    } // end doEmbedToText

}