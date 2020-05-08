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
use cmf\controller\PluginAdminBaseController;
use plugins\d_comment\model\CommentModel;
use think\Db;

class AdminIndexController extends PluginAdminBaseController {

    public function initialize() {
        $config = $this->getPlugin()->getConfig();
        if (!$config || $config['comment_type'] == 2) {
            $this->error('评论已关闭！');
        }
        $this->assign("admin_id", cmf_get_current_admin_id());
    }

    /**
     * 评论管理
     * @adminMenu(
     *     'name'   => '评论管理',
     *     'parent' => 'admin/Plugin/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '',
     *     'param'  => ''
     * )
     */
    public function index() {

        $param = $this->request->param();

        $where[] = ['c.delete_time', 'eq', 0];
        $where[] = ['c.table_name', 'eq', 'portal_post'];

        $startTime = empty($param['start_time']) ? '' : strtotime($param['start_time']);
        $where[]   = ['c.create_time', '>=', intval($startTime)];

        $endTime = empty($param['end_time']) ? '' : strtotime($param['end_time']);
        if (!empty($endTime)) {
            $where[] = ['c.create_time', '<=', $endTime];
        }

        $keyword = empty($param['keyword']) ? '' : $param['keyword'];
        if (!empty($keyword)) {
            $where[] = ['c.content', "%$keyword%"];
        }

        $status = isset($param['status']) ? intval($param['status']) : -1;
        if ($status > -1) {
            $where[] = ['c.status', 'eq', $status];
        } else {
            $where[] = ['c.status', 'in', [0, 1]];
        }

        $username = empty($param['username']) ? '' : $param['username'];
        if (!empty($username)) {
            $where[] = ['u.user_nickname', 'eq', trim($username)];
        }

        $c        = new CommentModel;
        $comments = $c->alias('c')
            ->join('user u', 'c.user_id = u.id', 'left')
            ->join('user ut', 'c.to_user_id = ut.id', 'left')
            ->field('c.*,u.user_nickname as username,ut.user_nickname as to_username')
            ->json(['more'])
            ->where($where)
            ->order('c.create_time DESC')
            ->paginate(10);

        $comments->appends($param);

        $this->assign('start_time', $startTime);
        $this->assign('end_time', $endTime);
        $this->assign('keyword', $keyword);
        $this->assign('username', $username);
        $this->assign('status', $status);
        $this->assign('list', $comments);
        $this->assign('page', $comments->render());
        return $this->fetch('/admin_index');
    }

    public function del() {
        $id  = $this->request->id;
        $oid = $this->request->oid;
        if (Db::name('comment')->update(['id' => (int) $id, 'delete_time' => time(), 'status' => 2])) {
            PortalPostModel::where('id', (int) $oid)->setDec('comment_count');
            $this->success('删除成功');
        }
        $this->error('删除失败');
    }

    public function pass() {
        $ids = $this->request->param('ids/a', []);
        if (Db::name('comment')->where('id', 'in', $ids)->update(['status' => 1]) !== false) {
            $this->success('审核成功');
        }
        $this->error('审核失败');
    }

    public function delall() {
        $ids = $this->request->param('ids/a');
        if (is_array($ids)) {
            $d = Db::name('comment')->field('id,object_id')->where('id', 'in', $ids)->select()->toArray();
            Db::name('comment')->where('id', 'in', array_map('reset', $d))->update(['delete_time' => time(), 'status' => 2]);
            $r = array_count_values(array_map('end', $d));
            foreach ($r as $key => $v) {
                PortalPostModel::where('id', $key)->setDec('comment_count', $v);
            }
            $this->success('删除成功！');
        }
        $this->success('删除失败！');
    }
}
