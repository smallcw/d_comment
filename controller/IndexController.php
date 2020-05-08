<?php
// +----------------------------------------------------------------------
// | d_comment [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 DaliyCode All rights reserved.
// +----------------------------------------------------------------------
// | Author: DaliyCode <3471677985@qq.com> <author_url:dalicode.com>
// +----------------------------------------------------------------------
namespace plugins\d_comment\controller;

use app\admin\model\PluginModel;
use app\portal\model\PortalPostModel;
use plugins\d_comment\model\CommentModel;
use cmf\controller\PluginBaseController;

class IndexController extends PluginBaseController {

    public function add() {
        $config = $this->getPlugin()->getConfig();
        if (!$config || $config['comment_type'] == 2) {
            $this->error('评论已关闭！');
        }

        $userid = cmf_get_current_user_id();
        if ($userid) {
            $i = (int) $config['comment_interval'] >= 0 ? (int) $config['comment_interval'] : 5;
            if (session('com') && $i && (time() - session('com')) < $i) {
                $this->error('请' . $i . '秒后再评论！');
            }

            $data                = $this->request->param();
            $data['user_id']     = $userid;
            $data['create_time'] = time();
            $data['more']        = json_encode([
                'title' => $data['object_title']
            ]);
            $config['comment_check'] == 1 && $data['status'] = 0;

            $result = $this->validate($data,
                [
                    'object_id'  => 'number',
                    'table_name' => 'alphaDash',
                    'more'       => 'require',
                    'url'        => 'require',
                    'to_user_id' => 'number',
                    'parent_id'  => 'number',
                    'content'    => 'require',
                ],
                [
                    'content' => '请填写正确的内容',
                ]);

            if ($result !== true) {
                $this->error($result);
            }

            $c      = new CommentModel();
            $result = $c->allowField(true)->save($data);

            if (false === $result) {

                $this->error($c->getError());
            }

            PortalPostModel::where('id', $data['object_id'])->setInc('comment_count');
            session('com', time());
            $this->success("评论成功");

        } else {

            $this->error('请登录！');
        }
    }
}