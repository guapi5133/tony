<?php
/**
 * 文件分片model
 */

namespace Apps\Model;

use eBaocd\AbsMvc\Model as AbsModel;

class UploaderModel extends AbsModel
{
    // 上传状态(未合成)
    const STATUS_INCOMPLETE = 0;
    // 上传状态(已合成)
    const STATUS_COMPLETE = 1;

    // 分片允许最大(m)
    const CHUNK_MAX_SIZE = 2 * 1024 * 1024;

    // 图片配置
    private $img_config = [
        'size'         => 10 * 1024 * 1024, // 大小(10m)
        'thumb_width'  => 420,
        'thumb_height' => 560
    ];

    // 文件配置
    private $file_config = [
        'size'         => 2000 * 1024 * 1024, // 大小(2G)
        'thumb_width'  => 420,
        'thumb_height' => 560
    ];

    // 允许的扩展名
    private $ext_map = [
        'jpg'   => ['icon' => 1, 'dir' => 'img'],
        'jpeg'  => ['icon' => 1, 'dir' => 'img'],
        'png'   => ['icon' => 1, 'dir' => 'img'],
        'gif'   => ['icon' => 1, 'dir' => 'img'],

        'bmp'   => ['icon' => 0, 'dir' => 'img'],
        'webp'   => ['icon' => 0, 'dir' => 'img'],

        'html'   => ['icon' => 6, 'dir' => 'text'],
        'htm'   => ['icon' => 6, 'dir' => 'text'],

        'mp4'   => ['icon' => 8, 'dir' => 'video'],
        /*'avi'   => ['icon' => 8, 'dir' => 'video'],
        'flv'   => ['icon' => 8, 'dir' => 'video'],
        'mkv'   => ['icon' => 0, 'dir' => 'video'],
        'mpeg'   => ['icon' => 0, 'dir' => 'video'],
        'mp3'   => ['icon' => 9, 'dir' => 'audio'],*/

        'zip'   => ['icon' => 0, 'dir' => 'text'],
        'rar'   => ['icon' => 0, 'dir' => 'rar'],
        'txt'   => ['icon' => 0, 'dir' => 'text'],
        'csv'   => ['icon' => 0, 'dir' => 'text'],
        'docx'   => ['icon' => 0, 'dir' => 'text'],
        'doc'   => ['icon' => 0, 'dir' => 'text'],
        'xlsx'   => ['icon' => 0, 'dir' => 'text'],
        'xls'   => ['icon' => 0, 'dir' => 'text'],
        'pptx'   => ['icon' => 0, 'dir' => 'text'],
        'ppt'   => ['icon' => 0, 'dir' => 'text'],
        'pdf'   => ['icon' => 6, 'dir' => 'text'],
        'rft'   => ['icon' => 0, 'dir' => 'text'],
        'wps'   => ['icon' => 0, 'dir' => 'text']
    ];

    // 允许的扩展名方式表
    private $ext_flag_map = [
        1 => ['jpg', 'jpeg', 'png', 'gif'],
        2 => ['mp4'],
        3 => ['mp3'],
        4 => ['pdf']
    ];

    // 源文件信息
    private $file_infos = '';
    // 源文件名
    private $file_origin_name = '';
    // 源文件md5
    private $file_md5 = '';
    // 当前分片索引
    private $chunk_num = 0;
    // 分片总数
    private $chunk_total = 0;
    // 分片文件识别ID
    private $file_guid = '';
    // 文件大小
    private $file_size = 0;
    // 文件扩展名
    private $file_ext = '';
    // 当前分片文件对象
    private $file_temp = '';
    // 分片文件名
    private $file_temp_name = '';
    // 分片文件临时存储目录
    private $file_temp_dir = '';
    // 分片文件临时存储物理路径
    private $file_temp_path = '';
    // 文件存储名
    private $file_name = '';
    // 文件存储目录
    private $file_save_dir = '';
    // 文件存储物理路径
    private $file_save_path = '';
    // 当前分片记录ID
    private $file_log_id = 0;
    // 当前分片记录
    private $file_log_info = [];
    // 上传者user_id
    private $file_user_id = 0;
    // 上传文件开始时间
    private $file_create_time = 0;
    // 处理异常信息
    private $error_msg = '';

    public function __construct()
    {
        parent::__construct();
    }

    // 上传文件处理
    public function uSave($form = [])
    {
        exit();
        $this->file_user_id     = $form['user_id'];
        $this->file_infos       = $form['file_chunk'];
        $this->file_md5         = strtolower($form['file_md5']);
        $this->file_create_time = time();

        // 参数验证
        if (TRUE !== $this->formValueValid($form))
        {
            return FALSE;
        }

        // 传文件格式验证
        if (TRUE !== $this->formFileValid())
        {
            return FALSE;
        }

        // 上传标识记录保存
        if (empty($this->file_log_info))
        {
            if (TRUE !== $this->saveData())
            {
                return FALSE;
            }
        }

        // 分片文件保存
        if (TRUE !== $this->filePartSave())
        {
            return FALSE;
        }

        // 分片文件合成
        if (TRUE !== $this->filePartMerge())
        {
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }

    // 参数验证
    public function formValueValid($form)
    {
        if (0 == strlen($form['file_name']) || mb_strlen($form['file_name']) > 150)
        {
            $this->error_msg = '文件名不能为空或不合法！';

            //xFun::write_log('1:' . var_dump($form), 'chunk_err_log');
            return FALSE;
        }

        if (0 == strlen($form['file_ext']))
        {
            $this->error_msg = '上传文件扩展名不能为空！';

            //xFun::write_log('2:' . var_dump($form), 'chunk_err_log');
            return FALSE;
        } else {
            $form['file_ext'] = strtolower($form['file_ext']);
        }

        // 允许扩展名验证方式
        if (isset($this->ext_flag_map[$form['ext_flag']])) {
            $maps = $this->ext_flag_map[$form['ext_flag']];

            if (!in_array($form['file_ext'], $maps)) {
                $this->error_msg = sprintf('上传文件扩展名只支持：%s', implode(', ', $maps));
                return FALSE;
            }
        }

        /*if (!preg_match('/^[0-9a-z]{32}$/i', $form['file_md5'])) {
            $this->error_msg = '上传文件参数错误！';
            return false;
        }*/

        if (!preg_match('/^([1-9]|[1-9][0-9]+)$/', $form['file_size']))
        {
            $this->error_msg = '上传文件参数错误！';

            //xFun::write_log('3:' . var_dump($form), 'chunk_err_log');
            return FALSE;
        }

        if (!preg_match('/^([1-9]|[1-9][0-9]+)$/', $form['chunk_num']))
        {
            $this->error_msg = '上传文件参数错误！';

            //xFun::write_log('4:' . var_dump($form), 'chunk_err_log');
            return FALSE;
        }

        if (!preg_match('/^([1-9]|[1-9][0-9]+)$/', $form['chunk_total']))
        {
            $this->error_msg = '上传文件参数错误！';

            //xFun::write_log('5:' . var_dump($form), 'chunk_err_log');
            return FALSE;
        }

        // 分片索引不能大于总数
        if ($form['chunk_num'] > $form['chunk_total'])
        {
            $this->error_msg = '上传文件参数错误！';

            //xFun::write_log('6:' . var_dump($form), 'chunk_err_log');
            return FALSE;
        }

        // 图片大小限制
        if ($this->isImg($form['file_ext']))
        {
            if ($form['file_size'] > $this->img_config['size'])
            {
                $this->error_msg = '上传图片大小不能超出10M。';

                return FALSE;
            }
        }
        else
        {
            // 文件大小限制
            if ($form['file_size'] > $this->file_config['size'])
            {
                $this->error_msg = '上传文件大小不能超出2GB。';

                return FALSE;
            }
        }

        // 如果不是第一个分片索引, 则需要验证guid
        if ('1' != $form['chunk_num'])
        {
            if (!preg_match('/^[0-9a-z]{32}$/i', $form['guid']))
            {
                $this->error_msg = '上传文件参数错误！';

                //xFun::write_log('7:' . var_dump($form), 'chunk_err_log');
                return FALSE;
            }
            else
            {
                $form['guid'] = strtolower($form['guid']);
            }

            // guid记录是否存在, 且为当前用户自己所上传
            $uploadLog = $this->getLogByGuid($form['guid'], 'id,temp_dir,temp_path,save_dir,save_path,file_md5,origin_name,filename,extension,user_id,create_time');
            if (!isset($uploadLog['user_id']) || $uploadLog['user_id'] != $form['user_id'])
            {
                $this->error_msg = '上传文件参数错误！';

                //xFun::write_log('8:' . var_dump($form), 'chunk_err_log');
                return FALSE;
            }

            $this->file_guid        = $form['guid'];
            $this->file_log_id      = $uploadLog['id'];
            $this->file_log_info    = $uploadLog;
            $this->file_create_time = $uploadLog['create_time'];
        }
        else
        {
            $this->file_guid = $this->getGenerateGuid();
        }

        $this->file_origin_name = $form['file_name'];
        $this->file_ext         = $form['file_ext'];
        $this->chunk_num        = $form['chunk_num'];
        $this->chunk_total      = $form['chunk_total'];
        $this->file_size        = $form['file_size'];

        return TRUE;
    }

    /**
     * 上传文件格式验证
     * @return bool|string
     */
    public function formFileValid()
    {
        exit;
        // 取得上传文件
        $name     = $this->file_infos['name'] ?? '';
        $tmp_name = $this->file_infos['tmp_name'] ?? '';
        $tmp_size = $this->file_infos['size'] ?? 0;

        // 若文件为空
        if (/*'' == $name || */ '' == $tmp_name)
        {
            $this->error_msg = '上传文件不存在！';

            //xFun::write_log('9:' . var_dump($this->file_infos), 'chunk_err_log');
            return FALSE;
        }

        // 分片大小
        //$tmp_size_m = $tmp_size / 1024 / 1024;
        if ($tmp_size > self::CHUNK_MAX_SIZE)
        {
            $this->error_msg = '上传文件流超出限制！';

            //xFun::write_log('10:' . var_dump($this->file_infos), 'chunk_err_log');
            return FALSE;
        }

        // 文件名格式
        /*$info = pathinfo($name);

        $origin_name = $info['filename'] ?? '';
        $ext = isset($info['extension']) ? strtolower($info['extension']) : '';*/

        if (/*'' == $ext || !isset($this->ext_map[$ext])*/ !isset($this->ext_map[$this->file_ext]))
        {
            $this->error_msg = '不支持的文件扩展名格式！';

            //xFun::write_log('11:' . var_dump($this->file_infos), 'chunk_err_log');
            return FALSE;
        }
        else
        {
            /*$this->file_ext = $ext;
            $this->file_origin_name = $origin_name;*/
            $this->file_temp = $tmp_name;

            // 生成分片临时文件存储目录
            if (TRUE !== $this->createTempDir())
            {
                $this->error_msg = '上传文件存储目录申请失败！';

                //xFun::write_log('12:' . var_dump($this->file_infos), 'chunk_err_log');
                return FALSE;
            }

            // 生成文件存储目录
            if (TRUE !== $this->createSaveDir())
            {
                $this->error_msg = '上传文件存储目录申请失败！';

                //xFun::write_log('13:' . var_dump($this->file_infos), 'chunk_err_log');
                return FALSE;
            }

            // 分片文件名
            $this->file_temp_name = sprintf('%s_%s.%s', $this->file_guid, $this->chunk_num, $this->file_ext);
            // 保存文件名
            $this->file_name = sprintf('%s.%s', $this->file_guid, $this->file_ext);

            return TRUE;
        }
    }

    /**
     * 生成分片临时文件存储目录
     * @return bool
     */
    public function createTempDir()
    {
        if (empty($this->file_log_info))
        {
            $ext_dir  = $this->ext_map[$this->file_ext]['dir'];
            $date_dir = date('Ym/d');

            $dir  = sprintf('upload/chunk_temp01/%s/%s', $ext_dir, $date_dir); // 相对路径
            $path = PUBLIC_DIR . $dir; // 保存路径

            $this->file_temp_dir  = $dir;
            $this->file_temp_path = $path;

            if (!is_dir($path))
            {
                return mkdir($path, 0755, TRUE);
            }
        }
        else
        {
            $this->file_temp_dir  = $this->file_log_info['temp_dir'];
            $this->file_temp_path = $this->file_log_info['temp_path'];
        }

        return TRUE;
    }

    /**
     * 生成文件存储目录
     * @return bool
     */
    public function createSaveDir()
    {
        if (empty($this->file_log_info))
        {
            $ext_dir  = $this->ext_map[$this->file_ext]['dir'];
            $date_dir = date('Ym/d');

            $dir  = sprintf('upload/%s/%s', $ext_dir, $date_dir); // 相对路径
            $path = PUBLIC_DIR . $dir; // 保存路径

            $this->file_save_dir  = $dir;
            $this->file_save_path = $path;

            if (!is_dir($path))
            {
                return mkdir($path, 0755, TRUE);
            }
        }
        else
        {
            $this->file_save_dir  = $this->file_log_info['save_dir'];
            $this->file_save_path = $this->file_log_info['save_path'];
        }

        return TRUE;
    }

    /**
     * 分片文件保存
     * @return bool
     */
    public function filePartSave()
    {
        $filename = sprintf('%s/%s', $this->file_temp_path, $this->file_temp_name);

        try
        {
            if (!move_uploaded_file($this->file_temp, $filename))
            {
                $this->error_msg = '上传失败！';

                //xFun::write_log('14:' . var_dump($this->file_infos), 'chunk_err_log');
                return FALSE;
            }
            else
            {
                return TRUE;
            }
        }
        catch (\Exception $e)
        {
            $this->error_msg = '上传失败！';

            //xFun::write_log('15:' . var_dump($this->file_infos), 'chunk_err_log');
            return FALSE;
        }
    }

    // 上传文件合并
    public function filePartMerge()
    {
        try
        {
            // 分片文件~
            $temp_file    = sprintf('%s/%s', $this->file_temp_path, $this->file_temp_name);
            $temp_content = file_get_contents($temp_file);
            unlink($temp_file); // 清除分片文件

            // 分片合成(边上传边合成)
            $filename = sprintf('%s/%s', $this->file_save_path, $this->file_name);
            file_put_contents($filename, $temp_content, FILE_APPEND);

            // 判断分片最后一片: 10/10 | 1/1
            if ($this->chunk_num == $this->chunk_total)
            {

                //由于MD5性能暂关闭MD5校验
                /*// 合成文件md5
                $file_md5 = md5_file($filename);

                // md5校验
                if ($this->file_md5 !== $file_md5) {
                    unlink($filename); // 清除合成非法文件

                    $this->error_msg = '上传文件不合法！';
                    return false;
                }*/

                // 文件上传记录状态变更
                if (TRUE !== $this->updateStatus($this->file_log_id, self::STATUS_COMPLETE))
                {
                    return FALSE;
                }
            }
        }
        catch (\Exception $e)
        {
            $this->error_msg = '文件上传失败！';

            //xFun::write_log('16:' . var_dump($this->file_infos), 'chunk_err_log');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * 获取文件mime
     *
     * @param $filename
     *
     * @return string
     */
    public function getFileMime($filename)
    {
        $mime = '';

        if (is_file($filename))
        {
            $F    = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $F->finfo($filename);
        }

        return $mime;
    }

    /**
     * 获取图片宽高尺寸
     *
     * @param string $filename
     *
     * @return int[]
     */
    public function getImageWidthHeight($filename)
    {
        $arr = [0, 0];

        if (is_file($filename) && in_array($this->file_ext, ['jpg', 'jpeg', 'png', 'gif']))
        {
            try
            {
                $I = new \Imagick($filename);

                $arr[0] = $I->getImageWidth();
                $arr[1] = $I->getImageHeight();
            }
            catch (\Exception $ex)
            {
                xFun::write_log($ex->getMessage(), 'chunk_err_log');
            }
        }

        return $arr;
    }

    /**
     * @param string $filename
     *
     * @return array
     */
    public function createThumb($filename)
    {
        $arr = [
            'name'   => '',
            'width'  => 0,
            'height' => 0
        ];

        if (is_file($filename) && in_array($this->file_ext, ['jpg', 'jpeg', 'png', 'gif']))
        {
            try
            {
                $I = new \Imagick($filename);

                // thumb w|h
                $thumbWH = $this->scaleImage($I->getImageWidth(), $I->getImageHeight(), $this->img_config['thumb_width'], $this->img_config['thumb_height']);

                $name       = sprintf('%s_t0_%sx%s.%s', $this->file_name, $thumbWH[0], $thumbWH[1], $this->file_ext);
                $thumb_file = sprintf('%s/%s', $this->file_save_path, $name);

                $I->setImageCompressionQuality(90);
                $I->thumbnailImage($thumbWH[0], $thumbWH[1], TRUE);
                $I->writeImage($thumb_file);

                // return value
                $arr['width']  = $thumbWH[0];
                $arr['height'] = $thumbWH[1];
                $arr['name']   = sprintf('/%s/%s', $this->file_save_dir, $name);

            }
            catch (\Exception $ex)
            {
                $this->error_msg = '图片处理失败！';
                xFun::write_log($ex->getMessage(), 'chunk_err_log');
            }
        }

        return $arr;
    }

    /**
     * @param int $x
     * @param int $y
     * @param int $cx
     * @param int $cy
     *
     * @return array
     */
    public function scaleImage($x, $y, $cx, $cy)
    {
        list($nx, $ny) = array($x, $y);

        if ($x >= $cx || $y >= $cx)
        {

            if ($x > 0)
            {
                $rx = $cx / $x;
            }
            if ($y > 0)
            {
                $ry = $cy / $y;
            }

            if ($rx > $ry)
            {
                $r = $ry;
            }
            else
            {
                $r = $rx;
            }

            $nx = round($x * $r);
            $ny = round($y * $r);
        }

        return array($nx, $ny);
    }

    /**
     * 生成guid
     * @return string
     */
    public function getGenerateGuid()
    {
        $str = mt_rand(10000, 99999);
        $str .= xFun::guid();

        return md5($str);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getThatVar($name)
    {
        if (property_exists($this, $name))
        {
            return $this->{$name};
        }
        else
        {
            return NULL;
        }
    }

    /**
     * @return array
     */
    public function getUploadedFileVars()
    {
        $vars = [
            'guid' => $this->file_guid
        ];

        // 分片文件上传完成, 返回成功文件信息
        if ($this->chunk_num == $this->chunk_total)
        {
            $filename_path = sprintf('%s/%s', $this->file_save_path, $this->file_name);
            $imgWH         = $this->getImageWidthHeight($filename_path);
            $thumb         = $this->createThumb($filename_path);

            $vars['url']          = sprintf('/%s/%s', $this->file_save_dir, $this->file_name);
            $vars['name']         = $this->file_origin_name;
            $vars['ext']          = $this->file_ext;
            $vars['size']         = $this->file_size;
            $vars['size_format']  = xFun::toSize($this->file_size);
            $vars['width']        = $imgWH[0];
            $vars['height']       = $imgWH[1];
            $vars['thumb_width']  = $thumb['width'];
            $vars['thumb_height'] = $thumb['height'];
            $vars['thumb']        = $thumb['name'];
            $vars['thumb_url']    = DOMAIN_ASSET . $vars['thumb'];
            $vars['type']         = $this->file_infos['type'] ?? '';
            $vars['uploader']     = $this->file_user_id;
            $vars['create_time']  = $this->file_create_time;
            $vars['domain_url']   = DOMAIN_ASSET . $vars['url'];
        }

        return $vars;
    }

    /**
     * @param string $ext
     *
     * @return bool
     */
    public function isImg($ext)
    {
        if (!preg_match('/^(jpg|jpeg|png|gif)$/i', $ext))
        {
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }

    /**
     * error msg
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->error_msg;
    }

    /**
     * 获取guid对象
     *
     * @param string $guid
     * @param string $field
     *
     * @return mixed
     */
    public function getLogByGuid($guid, $field = '*')
    {
        $where = ['guid' => $guid];

        return xDb::uploadLog()->filed($field)->where($where)->findOne();
    }

    /**
     * 记录添加
     * @return bool
     */
    public function saveData()
    {
        try
        {
            $data = [
                'save_dir'    => $this->file_save_dir,
                'save_path'   => $this->file_save_path,
                'temp_dir'    => $this->file_temp_dir,
                'temp_path'   => $this->file_temp_path,
                'file_md5'    => $this->file_md5,
                'origin_size' => $this->file_size,
                'origin_name' => $this->file_origin_name,
                'filename'    => $this->file_name,
                'extension'   => $this->file_ext,
                'file_type'   => $this->file_infos['type'] ?? '',
                'chunk_total' => $this->chunk_total,
                'guid'        => $this->file_guid,
                'user_id'     => $this->file_user_id,
                'status'      => self::STATUS_INCOMPLETE,
                'create_time' => $this->file_create_time
            ];

            $this->file_log_id = xDb::uploadLog()->insert($data);

            return TRUE;
        }
        catch (\Exception $e)
        {
            return FALSE;
        }
    }

    /**
     * 状态修改
     *
     * @param int $id
     * @param int $status
     *
     * @return bool
     */
    public function updateStatus($id, $status)
    {
        try
        {
            $data = [
                'status' => $status
            ];

            xDb::uploadLog()->where(['id' => $id])->update($data);

            return TRUE;
        }
        catch (\Exception $e)
        {
            return FALSE;
        }
    }
}
