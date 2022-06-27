<?php

namespace Apps\Common\Controller;

use Apps\Config\Config;
use Apps\Model\PurposeModel;
use eBaocd\Common\xDb;
use eBaocd\Common\xFun;
use eBaocd\Common\xRedis;

class AbsController
{
    public int $cur_user_id = 0;
    public array $cur_user = [];
    public array $header = [];
    public string $controller = '';
    public string $action = '';
    public string $url = '';
    public string $token = '';
    public string $system = '';
    public string $noPost = '';
    public string $noHeader = '';
    public string $noLogin = '';
    public string $platform = ''; //帐户平台，adm，user
    public bool $isPost = FALSE;
    public int $page = 1;
    public int $pageSize = PAGE_SIZE;

    public function __construct()
    {
        $this->controller = xFun::getGlobalConfig('controller');
        $this->action     = xFun::getGlobalConfig('action');
        $this->url        = xFun::getGlobalConfig('url');
        $this->isPost     = xFun::is_post();
        $this->init();

        //页码参数
        $this->page     = xFun::reqnum('page', 1);
        $this->pageSize = xFun::reqnum('pagesize', PAGE_SIZE);
        if (!preg_match('/^[1-9](\d+)?$/', $this->page))
        {
            xFun::output('请输入正确的页码');
        }
        if (!preg_match('/^[1-9](\d+)?$/', $this->pageSize))
        {
            xFun::output('请输入正确的每页条数');
        }
    }

    /**
     * 初始化
     */
    protected function init()
    {
        if (!$this->isPost && $this->noPost != '*' && !in_array($this->action, explode(',', $this->noPost)))
        {
            xFun::output('请求方式不合法');
        }

        if ($this->noHeader != '*' && !in_array($this->action, explode(',', $this->noHeader)))
        {
            $this->getHeader();
            $accounts = xFun::getGlobalConfig('header.account');
            $systems  = array_column($accounts, 'system', 'account');
            if (empty($systems[$this->header['account']]))
            {
                xFun::output(101);
            }
            $this->system   = $systems[$this->header['account']];
            $this->token    = $this->header['token'];
            $this->platform = xFun::getGlobalConfig("header.account." . $this->system . ".platform");

            //校验随机码
            if (!preg_match('/^[0-9a-zA-Z]{8}$/', $this->header['nonce']))
            {
                xFun::output(101);
            }

            //校验客户端与服务器时差 5分钟
            if (abs($this->header['created'] - time()) > 300)
            {
                xFun::output(104);
            }

            //校验sign
            $sign = xFun::createSign($this->header, $this->header['account']);
            if ($this->header['sign'] != $sign)
            {
                xFun::output(101);
            }

            //查询用户信息
            if ($this->token)
            {
                //获取user_id
                $tokenVal = xRedis::get("token:$this->token");
                if (empty($tokenVal))
                {
                    xFun::output(998, 'Token is invalid');
                }

                //从$system_user_id解析出user_id
                $tokenVal = explode('-', $tokenVal);
                $system   = $tokenVal[0] ?? '';
                $user_id  = $tokenVal[1] ?? 0;

                //platform和user_id是否为空
                if (empty($this->platform) || empty($user_id))
                {
                    xRedis::del(["token:$this->token"]);
                    xFun::output(998, 'Token is expired');
                }

                //获取token
                $token = xRedis::get("userinfo:" . $this->platform . "-$user_id");
                if ($this->token != $token)
                {
                    xRedis::del(["token:$this->token"]);
                    xFun::output(998, 'Login from other devices');
                }

                //查询用户信息
                $user = $this->getUserinfo(['id' => $user_id]);
                if (!$user)
                {
                    xFun::output(998, 'User is invalid');
                }

                $this->cur_user    = $user;
                $this->cur_user_id = $this->cur_user['id'];

                //刷新token有效期
                $this->createToken($system);
            }

            //验证是否需要登陆
            if (!$this->cur_user && $this->noLogin != '*' && !in_array($this->action, explode(',', $this->noLogin)))
            {
                xFun::output(998);
            }
        }
    }

    /**
     * 获取用户信息
     */
    protected function getUserinfo($where)
    {
        switch ($this->platform)
        {
            case 'admin'://管理后台
                $user = xDb::admin()->where($where)->statusStrong()->findOne();
            break;
            default:
                $user = xDb::user()->where($where)->statusStrong()->findOne();
            break;
        }

        return $user;
    }

    /**
     * 获取header
     */
    protected function getHeader()
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (empty($auth) && SERVER_ENV == 'local')
        {
            //本地环境
            $account = xFun::getGlobalConfig('header.account.local.account');
            $header  = ['account' => $account, 'created' => time(), 'nonce' => xFun::create_nonce_str(8)];

            //sign和token
            $header['sign']  = xFun::createSign($header, $account);
            $header['token'] = xFun::reqstr('token');

            $str  = xFun::array_to_str($header);
            $auth = base64_encode($str);
        }
        if (empty($auth) || !xFun::is_base64($auth))
        {
            xFun::output(101);
        }
        $auth = base64_decode($auth);
        parse_str($auth, $this->header);
    }

    /**
     * 创建token
     *
     * @param string $system
     */
    protected function createToken(string $system = '')
    {
        if (empty($system))
        {
            $system = $this->system;
        }
        if (empty($this->token))
        {
            $this->token = md5(xFun::guid());
        }

        if (empty($accountInfo = xFun::getGlobalConfig("header.account.$system")))
        {
            xFun::output(101);
        }
        $platform = $accountInfo['platform'];
        $expire   = $accountInfo['expire'];

        xRedis::set("token:$this->token", "$system-$this->cur_user_id", $expire);
        xRedis::set("userinfo:$platform-$this->cur_user_id", $this->token, $expire);
    }

    public function __destruct()
    {
        $data = [
            'datetime' => date('Y-m-d H:i:s'),
            'app'      => APP_NAME,
            'system'   => $this->system,
            'user_id'  => $this->cur_user['id'] ?? 0,
            'url'      => $this->url,
            'ip'       => xFun::real_ip(),
            'code'     => Config::$output_error_code['code'] ?? '',
            'msg'      => Config::$output_error_code['msg'] ?? '',
            'token'    => $this->token
        ];
        xFun::write_log($data, 'request_log');
    }

    //获取项目配置信息  $reload为真则强制刷新
    public function getSystemConfig($filed = '', $reload = FALSE)
    {
        $model = new PurposeModel();

        return $model->getSystemConfig($filed, $reload);
    }

    //------------------ 登录注册 -----------------

    /**
     * 登录
     */
    public function commonLogin()
    {
        $username = xFun::reqstr('username', 0, 105);
        $password = xFun::reqstr('password', 0, 105);

        $user = $this->getUserinfo(['username' => $username]);
        if (empty($user) || $user['password'] != xFun::encryptPass($password))
        {
            xFun::output('用户名密码错误');
        }

        unset($user['password']);
        $this->cur_user    = $user;
        $this->cur_user_id = $user['id'];
        $this->createToken();

        return TRUE;
    }

    /**
     * 用户信息
     */
    public function commonUserinfo($append = [])
    {
        $data = $this->cur_user;

        if (!empty($append)) //追加内容
        {
            $data = array_merge($data, $append);
        }

        $data['company_ids'] = trim($data['company_ids'],',');

        return $data;
    }

    //登出
    public function commonLogout()
    {
        xRedis::del(["token:$this->token"]);
        xRedis::del(["userinfo:" . $this->platform . "-$this->cur_user_id"]);

        return TRUE;
    }

    //改密
    public function commonChangePassword()
    {
        $old = xFun::reqstr('old', 0, 105);
        $new = xFun::reqstr('new', 0, 105);
        if (!preg_match('/^[a-zA-Z0-9]{6,15}$/', $new))
        {
            xFun::output('密码需由6-15位英文、数字组成');
        }

        if ($old == $new)
        {
            xFun::output('新旧密码不能一样');
        }

        $userinfo = xDb::admin()->filed('id,password')->findById($this->cur_user_id);
        if ($userinfo['password'] != xFun::encryptPass($old))
        {
            xFun::output('旧密码错误');
        }

        $update = ['password' => xFun::encryptPass($new), 'update_time' => time()];

        return xDb::admin()->updateById($update, $this->cur_user_id);
    }

    //修改用户状态
    public function commonChangeStatus()
    {
        $id     = xFun::reqnum('id');
        $status = xFun::reqstr('status'); //1启用,98禁用 99删除
        if ($id < 1 || !in_array($status, [STATUS_ENABLE, STATUS_DISABLE, STATUS_DELETE]))
        {
            xFun::output(105);
        }

        $user_status = xDb::admin()->filed('status')->findById($id) ?? '';
        if ($user_status === '')
        {
            xFun::output(107);
        }

        if ($user_status == STATUS_DELETE || $status == $user_status)//已删除的不能修改，状态相同不用修改
        {
            xFun::output(0);
        }

        $update = ['status' => $status, 'update_time' => time()];

        return xDb::admin()->updateById($update, $id);
    }

    //重置密码
    public function commonResetPassword()
    {
        $id = xFun::reqnum('id');
        if (empty($id))
        {
            xFun::output(105);
        }

        if (!$this->isAdmin()) //只有管理员能操作
        {
            xFun::output(102);
        }

        if (!xDb::user()->where(['status_<>' => STATUS_DELETE])->count())
        {
            xFun::output(107);
        }

        $update = ['password' => xFun::encryptPass(xFun::getGlobalConfig('init_passwd')), 'update_time' => time()];

        return xDb::admin()->updateById($update, $id);
    }
}
