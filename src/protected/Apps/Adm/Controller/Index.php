<?php

namespace Apps\Adm\Controller;

use eBaocd\Common\xFun;

class Index extends BaseController
{
    public function index()
    {
        exit('网站建设中...');
    }

    //返回系统各种配置
    public function getConfig()
    {
        $type  = xFun::reqstr('type', 0, 105);
        $white = ['monitor', 'company', 'media'];
        if (!in_array($type, $white))
        {
            xFun::output(105);
        }

        $config = [];
        if ($type == 'monitor')
        {
            $config = $this->getSystemConfig('enumerate')[ENUMERATE_PID_MONITOR] ?? [];
        }

        if ($type == 'company')
        {
            $result = $this->getSystemConfig('company');
            foreach ($result as $key => $value)
            {
                $config[] = [
                    'id'   => $key,
                    'name' => $value,
                ];
            }
        }

        if ($type == 'media')
        {
            $config = $this->getSystemConfig('media');
            sort($config);
        }

        xFun::output(0, $config);
    }
}
