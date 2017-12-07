<?php

namespace app\system\controller;

use think\Controller;
use think\Db;

class Component extends Controller {
    // 上传图片
    public function uploader() {
        // 获取表单上传文件
        if (count(request()->file()) > 1) {
            $files = request()->file();
            foreach ($files as $file) {
                $path[] = $this->handlerFile($file);
            }
        } else {
            $path[] = $this->handlerFile(current(request()->file()));
        }

        // wangEditor2富文本编辑器 要求返回格式
        if ($path[0]['valid'] == 1) {
            return $path[0]['url'];
        } else {
            return 'error|' . $path[0]['msg'];
        }
    }

    // 处理图片
    public function handlerFile($file) {
        // 获取文件 md5 散列值
        $realPath = $file->getRealPath();
        $sha1Value = sha1_file($realPath);
        $md5Value = md5_file($realPath);
        // 判断是否已经上传过 [根据文件散列值判断]
        $images = Db::name('ls_attachment')->where('md5', $md5Value)->select();
        if ($images) {
            return $images[0]['path'];
        } else {
            // 移动到框架应用根目录/public/uploads/ 目录下 [ 3M == 3145728 ]
            $info = $file->validate(['size' => 3145728, 'ext' => 'jpg,jpeg,png'])->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                // 上传成功
                $data = [
                    'filename' => $info->getFileName(),
                    'path' => DS . 'uploads' . DS . $info->getSaveName(),
                    'extension' => $info->getExtension(),
                    'createtime' => time(),
                    'size' => $info->getSize(),
                    'md5' => $info->hash('md5'),
                    'sha1' => $info->hash('sha1'),
                ];
                Db::name('ls_attachment')->insert($data);

                return ['valid' => 1, 'url' => DS . 'uploads' . DS . $info->getSaveName()];
            } else {
                // 上传失败
                return ['valid' => 0, 'msg' => $file->getError()];
            }
        }
    }
}
