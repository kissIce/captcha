<?php
/**
 * Created by PhpStorm.
 * User: angellce
 * Date: 2019/8/16
 * Time: 下午5:23
 */

namespace Angellce\Captcha;

/**
 * 验证码生成类
 * Class Result
 * @author : Ice <709896100@qq.com>
 * @package Angellce\Captcha
 */
class Captcha
{
    protected $conf = [
        'codeSet' => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY', // 去除容易混淆的字符
        'fontSize' => 26,              // 验证码字体大小(px)
        'useCurve' => true,            // 是否画混淆曲线
        'useNoise' => true,            // 是否添加杂点
        'imgH' => 40,               // 验证码图片高度
        'imgW' => 120,               // 验证码图片宽度
        'length' => 4,               // 验证码位数
        'ttf' => '',              // 验证码字体，不设置随机获取
        'bg' => [243, 251, 254],  // 背景颜色
        'tmp' => '/tmp'
    ];
    protected $_img;
    private $_color   = NULL;

    /**
     * 设置参数
     * @param array $config 配置参数
     */
    public function __construct(Array $config = [])
    {
        $this->conf = array_merge($this->conf, $config);
    }

    /**
     * 使用 $this->name 获取配置
     * @access public
     * @param  string $name 配置名称
     * @return multitype    配置值
     */
    public function __get($name)
    {
        return $this->conf[$name];
    }

    /**
     * 设置验证码配置
     * @access public
     * @param  string $name 配置名称
     * @param  string $value 配置值
     * @return void
     */
    public function __set($name, $value)
    {
        if (isset($this->conf[$name])) {
            $this->conf[$name] = $value;
        }
    }

    /**
     * 检查配置
     * @access public
     * @param  string $name 配置名称
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->conf[$name]);
    }

    /**
     * 画出验证码
     * @return Result
     */
    public function draw()
    {
        // 图片宽(px)
        $this->imgW || $this->imgW = $this->length * $this->fontSize * 1.5 + $this->length * $this->fontSize / 2;
        // 图片高(px)
        $this->imgH || $this->imgH = $this->fontSize * 2;
        // 创建空白画布
        $this->_img = imagecreate($this->imgW, $this->imgH);
        // 设置背景
        imagecolorallocate($this->_img, $this->bg[0], $this->bg[1], $this->bg[2]);
        // 验证码字体随机颜色
        $this->_color = imagecolorallocate($this->_img, mt_rand(1, 150), mt_rand(1, 150), mt_rand(1, 150));
        // 验证码使用随机字体
        $ttfPath = dirname(__FILE__).'/ttfs'.'/';
        if (empty($this->ttf)) {
            $dir = dir($ttfPath);
            $ttfs = array();
            while (false !== ($file = $dir->read())) {
                if ($file[0] != '.' && substr($file, -4) == '.ttf') {
                    $ttfs[] = $file;
                }
            }
            $dir->close();
            $this->ttf = $ttfs[array_rand($ttfs)];
        }
        $this->ttf = $ttfPath . $this->ttf;
        // 画噪点
        if ($this->useNoise) { $this->_writeNoise(); }
        // 画干扰曲线
        if ($this->useCurve) { $this->_writeCurve(); }
        // 绘验证码
        $code = array(); // 验证码
        $codeNX = 0; // 验证码第N个字符的左边距
        for ($i = 0; $i < $this->length; $i++) {
            // 验证码字体随机颜色
            $this->_color = imagecolorallocate($this->_img, mt_rand(1, 150), mt_rand(1, 150), mt_rand(1, 150));
            $code[$i] = $this->codeSet[mt_rand(0, strlen($this->codeSet) - 1)];
            $codeNX += $i != 0 ? $this->fontSize * mt_rand(7, 12) / 10 : 14;
            imagettftext($this->_img, $this->fontSize, mt_rand(0, 20), $codeNX, $this->fontSize * 1.3, $this->_color, $this->ttf, $code[$i]);
        }
        $this->checkPath();
        // 输出验证码结果集
        $this->tmp = rtrim(str_replace('\\', '/', $this->tmp), '/') . '/';
        mt_srand();
        $filePath = $this->tmp . date('YmdHis') . rand(1000,9999) .'.png';
        ob_start();
        imagepng($this->_img);
        $file = ob_get_contents();
        ob_end_clean();
        imagedestroy($this->_img);
        return new Result($file, implode($code), $filePath);
    }

    /**
     * 检查tmp路径
     */
    private function checkPath()
    {
        if (!is_dir($this->tmp)) mkdir($this->tmp,0755) && chmod($this->tmp,0755);
    }

    /**
     * 画干扰线
     */
    private function _writeCurve()
    {
        $px = $py = 0;
        // 曲线前部分
        $A = mt_rand(1, $this->imgH / 2);                  // 振幅
        $b = mt_rand(-$this->imgH / 4, $this->imgH / 4);   // Y轴方向偏移量
        $f = mt_rand(-$this->imgH / 4, $this->imgH / 4);   // X轴方向偏移量
        $T = mt_rand($this->imgH, $this->imgW * 2);  // 周期
        $w = (2 * M_PI) / $T;
        $px1 = 0;  // 曲线横坐标起始位置
        $px2 = mt_rand($this->imgW / 2, $this->imgW * 0.8);  // 曲线横坐标结束位置
        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if ($w != 0) {
                $py = $A * sin($w * $px + $f) + $b + $this->imgH / 2;  // y = Asin(ωx+φ) + b
                $i = (int)($this->fontSize / 5);
                while ($i > 0) {
                    imagesetpixel($this->_img, $px + $i, $py + $i, $this->_color);
                    $i--;
                }
            }
        }

        // 曲线后部分
        $A = mt_rand(1, $this->imgH / 2);                  // 振幅
        $f = mt_rand(-$this->imgH / 4, $this->imgH / 4);   // X轴方向偏移量
        $T = mt_rand($this->imgH, $this->imgW * 2);  // 周期
        $w = (2 * M_PI) / $T;
        $b = $py - $A * sin($w * $px + $f) - $this->imgH / 2;
        $px1 = $px2;
        $px2 = $this->imgW;

        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if ($w != 0) {
                $py = $A * sin($w * $px + $f) + $b + $this->imgH / 2;  // y = Asin(ωx+φ) + b
                $i = (int)($this->fontSize / 5);
                while ($i > 0) {
                    imagesetpixel($this->_img, $px + $i, $py + $i, $this->_color);
                    $i--;
                }
            }
        }
    }

    /**
     * 画杂点
     * 往图片上写不同颜色的字母或数字
     */
    private function _writeNoise()
    {
        $codeSet = '-_&￥%#@！*2345678abcdefhijkmnpqrstuvwxyz~?[]{}<>';
        for ($i = 0; $i < 10; $i++) {
            //杂点颜色
            $noiseColor = imagecolorallocate($this->_img, mt_rand(150, 225), mt_rand(150, 225), mt_rand(150, 225));
            for ($j = 0; $j < 5; $j++) {
                // 绘杂点
                imagestring($this->_img, 5, mt_rand(-10, $this->imgW), mt_rand(-10, $this->imgH), $codeSet[mt_rand(0, 29)], $noiseColor);
            }
        }
    }
}