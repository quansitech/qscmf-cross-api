<?php

namespace QscmfCrossApi\Model;

use Gy_Library\GyListModel;

class CrossApiRegisterModel extends GyListModel
{

    public function fetchApiById($id, $ip){
        return $this->where(['id' => $id, 'ip' => $ip])->getField('api');
    }

    public function isExistsApiById($id, $ip, $module_name, $controller_name, $action_name){
        $api = $this->fetchApiById($id, $ip);
        $api_list = json_decode($api, true);

        if (empty($api) || !is_array($api_list)){
            return false;
        }else{
            return in_array(implode(',', [strtolower($module_name),strtolower($controller_name),strtolower($action_name)]),$api_list);
        }
    }

}