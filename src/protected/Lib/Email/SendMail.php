<?php

namespace PHPMailer\PHPMailer;

require_once __DIR__ . DS . 'PHPMailer.php';
require_once __DIR__ . DS . 'SMTP.php';
require_once __DIR__ . DS . 'Exception.php';


class SendMail
{
    protected $mail = null;
    protected $sender = ['username' => 'market@hxkjmedia.com', 'password' => '659BwH77PQhh5N9s', 'name' => '红星科技'];
    protected $email = [];

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->init();
    }

    /**
     * 初始化
     */
    private function init()
    {
        //服务器配置
        $this->mail->CharSet   = "UTF-8";//设定邮件编码
        $this->mail->SMTPDebug = 0;// 调试模式输出
        $this->mail->isSMTP();// 使用SMTP
        $this->mail->Host       = 'smtp.exmail.qq.com';// SMTP服务器
        $this->mail->SMTPAuth   = true;// 允许 SMTP 认证
        $this->mail->SMTPSecure = 'ssl';// 允许 TLS 或者ssl协议
        $this->mail->Port       = 465;// 服务器端口 25 或者465 具体要看邮箱服务器支持

        //需要配置
        $this->mail->Username = $this->sender['username'];//SMTP 用户名  即邮箱的用户名
        $this->mail->Password = $this->sender['password'];//SMTP 密码  部分邮箱是授权码(例如163邮箱)
    }

    /**
     * 发送邮件
     * @param $params
     * @return bool
     */
    public function send($params)
    {
        try
        {
            //发件人,如果不需要重命名,则不要第二个参数
            $this->mail->setFrom($this->sender['username'], $this->sender['name']);

            //收件人,如果不需要重命名,则不要第二个参数
            foreach ($params['tos'] as $to)
            {
                if (!empty($this->email[$to]))
                {
                    $this->mail->addAddress($to, $this->email[$to]);
                }
                else
                {
                    $this->mail->addAddress($to);
                }
            }
            //回复的时候回复给哪个邮箱 建议和发件人一致
            if (!empty($params['reply']))
            {
                $this->mail->addReplyTo($params['reply']);
            }
            //抄送
            //$this->mail->addCC('cc@example.com');
            //密送
            //$this->mail->addBCC('bcc@example.com');
            //发送附件,如果不需要重命名,则不要第二个参数
            if (!empty($params['file']))
            {
                $this->mail->addAttachment($params['file']);
            }

            //Content
            $this->mail->isHTML(true);// 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
            $this->mail->Subject = $params['title'] ?? '';//标题
            $this->mail->Body    = $params['content'] ?? '';//内容
            $this->mail->AltBody = '不支持的消息';

            $this->mail->send();
            return true;
        }
        catch (Exception $e)
        {
            return false;
        }
    }
}
