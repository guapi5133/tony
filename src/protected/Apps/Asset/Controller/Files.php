<?php

namespace Apps\Asset\Controller;

class Files extends BaseController
{
    private $config = [
        'jpg'   => ['icon' => 1, 'dir' => 'img'],
        'jpeg'  => ['icon' => 1, 'dir' => 'img'],
        'png'   => ['icon' => 1, 'dir' => 'img'],
        'txt'   => ['icon' => 2, 'dir' => 'text'],
        'doc'   => ['icon' => 3, 'dir' => 'text'],
        'docx'  => ['icon' => 3, 'dir' => 'text'],
        'xls'   => ['icon' => 4, 'dir' => 'text'],
        'xlsx'  => ['icon' => 4, 'dir' => 'text'],
        'ppt'   => ['icon' => 5, 'dir' => 'text'],
        'pptx'  => ['icon' => 5, 'dir' => 'text'],
        'pdf'   => ['icon' => 6, 'dir' => 'text'],
        'rar'   => ['icon' => 7, 'dir' => 'zip'],
        'zip'   => ['icon' => 7, 'dir' => 'zip'],
        'mp4'   => ['icon' => 8, 'dir' => 'video'],
        'mp3'   => ['icon' => 9, 'dir' => 'audio'],
        'trace' => ['icon' => 2, 'dir' => 'trace'],
    ];

    public function index()
    {
       exit();
    }

    /**
     * 文件分片上传
     */
    public function upload()
    {
        exit();
        $sign = xFun::reqstr('sign'); // 当前分片签名
        $file_md5 = xFun::reqstr('file_md5'); // 完整文件md5
        $guid = xFun::reqstr('guid'); // 文件名guid(由第一次请求服务端生成返回的值)
        $file_chunk = $_FILES['file_chunk'] ?? null; // 文件分片
        $file_name = xFun::reqstr('file_name'); // 文件名
        $file_ext = xFun::reqstr('file_ext'); // 文件扩展名
        $file_size = xFun::reqstr('file_size'); // 文件总大小
        $chunk_num = xFun::reqstr('chunk_num'); // 当前分片序号
        $chunk_total = xFun::reqstr('chunk_total'); // 分片总个数
        $ext_flag = xFun::reqstr('ext_flag'); // 验证文件扩展名方式(0全部, 1图片, 2视频, 3音频, 4文件)

        // form
        $form = [
            'file_md5' => $file_md5,
            'guid' => $guid,
            'file_chunk' => $file_chunk,
            'file_name' => $file_name,
            'file_ext' => $file_ext,
            'file_size' => $file_size,
            'chunk_num' => $chunk_num,
            'chunk_total' => $chunk_total,
            'ext_flag' => $ext_flag,
            'user_id' => $this->cur_user_id
        ];

        $uploader = new UploaderModel();
        if (true !== $uploader->uSave($form)) {
            xFun::output($uploader->getErrorMsg());
        } else {
            $infos = $uploader->getUploadedFileVars();

            // 分片合并成功
            if ($uploader->getThatVar('chunk_num') == $uploader->getThatVar('chunk_total')) {
                // 写入 file_upload 表
                try {
                    // insert
                    $upload_id = xDb::fileUpload()->insert([
                        'url' => $infos['url'],
                        'name' => $infos['name'],
                        'ext' => $infos['ext'],
                        'size' => $infos['size'],
                        'size_format' => $infos['size_format'],
                        'width' => $infos['width'],
                        'height' => $infos['height'],
                        'thumb' => $infos['thumb'],
                        'thumb_width' => $infos['thumb_width'],
                        'thumb_height' => $infos['thumb_height'],
                        'type' => $infos['type'],
                        'from_type' => /*$this->userTypeName*/0,
                        'uploader' => $infos['uploader'],
                        'create_time' => $infos['create_time']
                    ], 'file_upload');

                    $infos['file_id'] = $upload_id;

                    unset($infos['guid']);
                } catch (\Exception $e) {
                    xFun::output('上传文件，写入数据失败！');
                }
            }

            xFun::output(0, $infos);
        }
    }
}
