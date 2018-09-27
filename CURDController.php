<?php

namespace simplify\curd;

use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\base\Module;
use Yii;
use yii\helpers\Url;

abstract class CURDController extends Controller
{
    public $db                        = null;
    public $model                     = null;
    public $editScenario              = null;
    public $editQueryModel            = null;
    public $createScenario            = null;
    public $selectScenario            = null;
    public $selectConfig              = [];
    public $editConfig                = [];
    protected $createSuccessRedirect  = null;
    protected $createLogDesc          = '未设置';
    protected $deleteLogDesc          = '未设置';
    protected $updateLogDesc          = '未设置';

    abstract function initSetConfig();
    public function afterEdit(){}
    public function beforeSelect(&$query){}
    public function beforeCreate(&$model){}
    public function beforeLoadEdit(&$model,$id){}
    public function beforeEdit(&$model){}
    public function beforeDelete(&$model){}

    public function __construct($id, Module $module, array $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->initSetConfig();
    }

    public function actionIndex()
    {
        $model = $this->model;
        $query = $this->model->find();
        !empty($this->selectScenario) && $model->setScenario($this->selectScenario);
        $this->beforeSelect($query);

        if(empty($this->db)){
            $parame = [
                'query' => $query,
                'pagination' => [
                    'pageSize' => empty($this->selectConfig['pageSize']) ? 10 : $this->selectConfig['pageSize'],
                ],
                'sort' => [
                    'defaultOrder' => empty($this->selectConfig['defaultOrder']) ? ['id' => SORT_DESC] : $this->selectConfig['defaultOrder']
                ]
            ];
        }else{
            $parame = [
                'db' => $this->db,
                'query' => $query,
                'pagination' => [
                    'pageSize' => empty($this->selectConfig['pageSize']) ? 10 : $this->selectConfig['pageSize'],
                ],
                'sort' => [
                    'defaultOrder' => empty($this->selectConfig['defaultOrder']) ? ['id' => SORT_DESC] : $this->selectConfig['defaultOrder']
                ]
            ];
        }

        $provider = new ActiveDataProvider($parame);

        if(isset($this->selectConfig['screen']) && isset($this->selectConfig['anyParams'])){
            $anyParams = $this->selectConfig['anyParams'] ;
            $compact = compact('provider','model','anyParams');
        }
        if(isset($this->selectConfig['anyParams']) && !isset($this->selectConfig['screen'])){
            $anyParams = $this->selectConfig['anyParams'];
            $compact = compact('provider','anyParams');
        }
        if(!isset($this->selectConfig['anyParams']) && isset($this->selectConfig['screen'])){
            $compact = compact('provider','model');
        }
        !isset($this->selectConfig['screen']) && !isset($this->selectConfig['anyParams']) && $compact = compact('provider');

        return $this->render(empty($this->selectConfig['template']) ? 'index' : $this->selectConfig['template'], $compact);
    }

    public function actionDelete($id)
    {
        $model = $this->model;
        $data = $model->findOne($id);

        $this->beforeDelete($model);

        $office_copy = $model->find()->where(['id' => $id])->asArray()->one();

        if (!empty($data)) {
            if ($data->delete()) {
                Yii::$app->session->setFlash('success', '删除成功');
            } else {
                Yii::$app->session->setFlash('error', '删除失败'.(empty($data->firstErrors[array_rand($data->firstErrors)]) ? '' : ':'.$data->firstErrors[array_rand($data->firstErrors)]));
            }
        }
        $this->redirect($this->goBack('index'));
    }

    public function actionCreate()
    {
        $model = $this->model;

        !empty($this->createScenario) && $model->setScenario($this->createScenario);

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $this->beforeCreate($model);
            if ($model->save()) {
                Yii::$app->session->setFlash('success', '添加成功');

                if(!empty($this->createSuccessRedirect)){
                    return $this->redirect([$this->createSuccessRedirect]);
                }
            } else {
                Yii::$app->session->setFlash('error', '添加失败'.(empty($model->firstErrors[array_rand($model->firstErrors)]) ? '' : ':'.$model->firstErrors[array_rand($model->firstErrors)]));
            }
        }
        return $this->render(empty($this->editConfig['template']) ? 'create' : $this->editConfig['template'], compact('model'));
    }

    public function actionEdit($id)
    {
        $init_model = $this->model;
        $model = $init_model->findOne($id);

        !empty($this->editScenario) && $model->setScenario($this->editScenario);

        $this->setEditQueryModel($model);

        $this->beforeLoadEdit($model,$id);
        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $this->beforeEdit($model);
            if ($model->save()) {
                Yii::$app->session->setFlash('success', '修改成功');
            } else {
                Yii::$app->session->setFlash('error', '修改失败'.(empty($model->firstErrors[array_rand($model->firstErrors)]) ? '' : ':'.$model->firstErrors[array_rand($model->firstErrors)]));
            }
        }

        $this->afterEdit();

        if(isset($this->editConfig['anyParams'])){
            $params = $this->editConfig['anyParams'];
            return $this->render(empty($this->editConfig['template']) ? 'create' : $this->editConfig['template'], compact('model','params'));
        }else{
            return $this->render(empty($this->editConfig['template']) ? 'create' : $this->editConfig['template'], compact('model'));
        }
    }

    protected function setEditQueryModel($model)
    {
        $this->editQueryModel = $model;
    }

    private function myGoBack($target)
    {
        if(empty($_SERVER['HTTP_REFERER'])){
            return Url::to(["$target"]);
        }else{
            return Url::to(["$target?".Yii::$app->getRequest()->get('queryString')]);
        }
    }
}
