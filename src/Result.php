<?php
/**
 * Created by PhpStorm.
 * User: ice
 * Date: 2019/8/19
 * Time: 下午3:17
 */

namespace Swoft\Captcha;

/**
 * 验证码结果类
 * Class Result
 * @author : Ice <709896100@qq.com>
 * @package Swoft\Captcha
 */
class Result
{
    private $captchaByte;  // 验证码图片
    private $captchaCode;  // 验证码内容
    private $captchaFile;  // 验证码文件

    function __construct($Byte, $Code, $File)
    {
        $this->captchaByte = $Byte;
        $this->captchaCode = $Code;
        $this->captchaFile = $File;
    }

    /**
     * 获取验证码图片
     * @return mixed
     */
    function getImageByte()
    {
        return $this->captchaByte;
    }

    /**
     * 返回图片Base64字符串
     * @return string
     */
    function getImgBase64()
    {
        $base64Data = base64_encode($this->captchaByte);
        return "data:png;base64,{$base64Data}";
    }

    /**
     * 获取验证码内容
     * @return mixed
     */
    function getImgCode()
    {
        return $this->captchaCode;
    }

    /**
     * 获取验证码文件路径
     */
    function getImgFile()
    {
        if(!file_exists($this->captchaFile)){
            file_put_contents($this->captchaFile, $this->captchaByte);
        }
        return $this->captchaFile;
    }
}