<?php

class Cpanel
{
    private $username;
    private $password;
    private $auth_type;
    private $host; //LIKE http://myhost.ir:2086

    
    public function __construct($options = array())
    {
        if(isset($options['host']) &&  isset($options['auth_type']) && isset($options['password']) && isset($options['username']))
        {
            $this->host = $options['host'];
            $this->username = $options['username'];
            $this->password = $options['password'];
            $this->auth_type = $options['auth_type'];

        }else{
            trigger_error("User concention data is missing", E_USER_WARNING);
        }

    }

    /*
     *
     * Cpanel version 2 API EXAMPLE
     * $cpanel->runQuery('2', 'cpanel', array(
     *      'module'   => 'Bandwidth',
     *      'function' => 'query',
     *      'username' => 'subdl',
     *      'grouping' => 'domain|year',
     *      'interval' => 'daily'
     *  ));
     *
     * WHM version 1 API EXAMPLE
     * $cpanel->runQuery('0', 'applist', array());
     *
     */
    public function run($api_type,$action,$options = array())
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);

        //@$api_type  (0 = WHM API 1 , 1 = cPanel API 1, 2 = cPanel API 2, 3 = UAPI)
        $options_extra = "";

        if($api_type === '0')
        {
            $options_extra = array(
                'api.version'=>1
            );

        }elseif($api_type == '1' || $api_type == '2' || $api_type == '3')
        {
            $options_extra = array(
                'cpanel_jsonapi_apiversion' => $api_type,
                'cpanel_jsonapi_module' => $options['module'],
                'cpanel_jsonapi_func' => $options['function'],
                'cpanel_jsonapi_user' => $options['username'],
            );
            unset($options['module']);
            unset($options['function']);
            unset($options['username']);
        }else{
            trigger_error("api_type is invalid. It can be 0 = WHM API 1 , 1 = cPanel API 1, 2 = cPanel API 2, 3 = UAPI", E_USER_WARNING);
            exit;
        }

        $options = array_merge($options_extra,$options);
        $fi = http_build_query($options);
        $query = $this->host.'/json-api/'.$action.'?'.$fi;
//        echo $query;
        $header = array();
        if ('hash' == $this->auth_type) {
            $header[] = 'Authorization: WHM ' . $this->username . ':' . preg_replace("'(\r|\n|\s|\t)'", '', $this->password);
        } elseif ('password' == $this->auth_type) {
            $header[] = 'Authorization: Basic ' . base64_encode($this->username . ':' .$this->password);
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER,$header);
        curl_setopt($curl, CURLOPT_URL, $query);

        $result = curl_exec($curl);
        return $result;
    }

    public function __call($key,$args)
    {
        return $this->run('0',$key,$args[0]);
    }


    public function api2($module,$function,$username,$options = array())
    {
        $options = array_merge(array(
            'module'   => $module,
            'function' => $function,
            'username' => $username
        ),$options);
        return $this->run('2', 'cpanel',$options);
    }


    public function uapi($module,$function,$username,$options = array())
    {
        $options = array_merge(array(
            'module'   => $module,
            'function' => $function,
            'username' => $username
        ),$options);

        return $this->run('3', 'cpanel',$options);
    }

    private function http_build_query($params = array())
    {
        $paramsJoined = array();
        foreach($params as $param => $value) {
            $paramsJoined[] = "$param=$value";
        }
        $query = implode('&', $paramsJoined);

        return $query;
    }


}