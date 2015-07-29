<?php

require_once(LIB_PATH . "/Util/phpmailer/class.phpmailer.php");
require_once(LIB_PATH . "/Util/phpmailer/class.smtp.php");

/**
 * Mail
 */
class Mail {
    // 邮件对象
    protected $mail;

    /**
     * 构造初始化
     */
    public function __construct() {
        $this->mail = new PHPMailer(); //new一个PHPMailer对象出来
        $this->setCharset();
        $this->setFrom();
    }

    /**
     * 设置字符集
     * @param string $charset 字符集
     */
    public function setCharset($charset = 'UTF-8') {
        $this->mail->CharSet = $charset;//设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    }

    /**
     * 设置发件人信息
     * @param array $config 配置信息
     */
    public function setFrom($config = array()) {
        if (empty($config)) { $config = C('MAIL_CONFIG'); }

        $this->mail->Host = $config['host'];   // SMTP servers
        $this->mail->Username = $config['username'];     // SMTP username  注意：普通邮件认证不需要加 @域名
        $this->mail->Password = $config['password']; // SMTP password
        $this->mail->From = $config['username'];      // 发件人邮箱
        $this->mail->FromName =  $config['fromname'] ? $config['fromname'] : $config['username'];  // 发件人
    }

    /**
     * 使用smtp发送邮件
     * @param $sendToEmail 发送的邮箱
     * @param $subject 邮件主题
     * @param $body 邮件内容
     */
    public function sendSmtpMail($sendToEmail, $subject, $body) {
        $this->mail->IsSMTP();                  // send via SMTP
        $this->mail->SMTPAuth = true;           // turn on SMTP authentication
        $this->mail->Encoding = "base64";

        $this->mail->AddAddress($sendToEmail);  // 收件人邮箱和姓名
        $this->mail->AddReplyTo($this->mail->From,$this->mail->FromName); // 回复邮箱和姓名
        //$this->mail->WordWrap = 50; // set word wrap 换行字数
        //$this->mail->AddAttachment("/var/tmp/file.tar.gz"); // attachment 附件
        //$this->mail->AddAttachment("/tmp/image.jpg", "new.jpg");
        $this->mail->IsHTML(true);  // send as HTML
        // 邮件主题
        $this->mail->Subject = $subject;
        // 邮件内容
        $this->mail->Body = $body;
        $this->mail->AltBody ="text/html";
        if(!$this->mail->Send()) {
            exit("邮件发送有误, 邮件错误信息: " . $this->mail->ErrorInfo);
        } else {
            echo "{$sendToEmail} 邮件发送成功!<br />";
        }
    }
}