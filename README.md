# CURD类库说明文档

CURDController继承自Controller控制器，为后台开发定制而产生，减少重复和垃圾代码。该控制器集成了增删改查通用方法，新的功能板块只需要继承该控制器进行实例化并覆盖CURD类的
model属性以及一系列的配置后既可以以最少的控制器代码实现增删改查功能。

示例：
    
    <?php
    
    namespace app\controllers;
    
    use app\models\Admin;
    use sihe\curd\CURDController;
    
    class TestController extends CURDController
    {
        public function initSetConfig()
        {
            $this->model = new Admin();
            $this->selectConfig = [
                'screen'   => true,
                'pageSize' => 20,
            ];
        }
    }
    
上述极少的代码即完成了增删除改查。如果不希望某项方法被使用覆盖对应方法即可。Yii2的灵活性多数数
据操作都可以在model中完成，比如beforeSave、afterSave、afterDelete等方法中完成，搭配着使用本类
可以大幅度提高工程速度，工程师多数只需要关注view层面。

- - -

配置参数详解
============

可覆盖配置方法
-------------------
所有的配置方法都是给予子类覆盖用途，从而引导设置相应属性以及做出特殊操作。
+ [initSetConfig()](#initsetconfig)
+ [beforeSelect(&amp;$query)](#beforeselect)
+ [beforeCreate(&amp;$model)](#beforecreate)
+ [beforeEdit(&amp;$model)](#beforeedit)
+ [beforeDelete(&amp;$model)](#beforedelete)
+ [afterEdit()](#afteredit)

可覆盖配置属性
-------------------
一些属性是通过配置方法中进行实例引入，一些这是直接进行子类覆盖。
+ [public $model](#model)
+ [public $editScenario](#edit)
+ [public $editQueryModel](#editquerymodel)
+ [public $createScenario](#createscenario)
+ [public $selectScenario](#selectscenario)
+ [public $selectConfig](#selectconfig)
+ [public $editConfig](#editconfig)
+ [protected $createSuccessRedirect](#createsuccessredirect)

详细配置参数详解
==============

initSetConfig()
-------------------
> initSetConfig()是必须实现的方法，initSetConfig()方法是一个注入容器方法，通过initSetConfig()方法可以注入实例化的model,来  
覆盖的方式定义父类使用的数据model。也可将一些配置参数配置到其中，当然也可以不在此方法中配置，而是直接在公共变量中定义。

基础示例：

    class BannerController extends CURDController
    {
    
        public function initSetConfig()
        {
            $this->model = new BoxBanner();
            $this->selectConfig = [
                'pageSize' => 20,
            ];
        }
    
    }
    
在initSetConfig()方法中中定义配置参数：

    class BannerController extends CURDController
    {
        public function initSetConfig()
        {
            $this->model = new BoxBanner();
            $this->selectConfig = [
                'pageSize' => 20,
                'screen'   => true
            ];
            $this->editScenario          = 'edit';
            $this->createSuccessRedirect = 'user/index';
        }
    
    }
    
不在initSetConfig()方法中定义配置参数：

    class BannerController extends CURDController
    {
        public $editScenario     = 'edit';
        public $createScenario   = 'create';
        public $selectScenario   = 'select';
    
        public function initSetConfig()
        {
            $this->model = new BoxBanner(); // 实例化数据model是一定要在initSetConfig()中完成的
        }
    
    }
    
<a name="beforeselect"></a>

beforeSelect(&amp;$query)
--------------------------
> 顾名思义查询前所调用的方法,该方法可以在实际应用中作为联表查询、对搜索条件做出操作。

示例：

    public function beforeSelect(&$query)
    {
        $query->joinWith(['gameInfo','gsApplicant']);

        if(MyGet::isGet() && $this->model->load(MyGet::get())){
            $query->andFilterWhere(['game_info_id' => $this->model->game_info_id]);
            $query->andFilterWhere(['area'         => $this->model->area]);
            $query->andFilterWhere(['like','role_name',$this->model->role_name]);
            $query->andFilterWhere(['account'      => $this->model->account]);
            $query->andFilterWhere(['channel'      => $this->model->channel]);
            $query->andFilterWhere(['applicant_id' => $this->model->applicant_id]);
            $query->andFilterWhere(['>=',GsSpecialCharge::tableName().'.created_at',$this->model->date_start]);
            $query->andFilterWhere(['<=',GsSpecialCharge::tableName().'.created_at',$this->model->date_end]);
        }
    }
    
<a name="beforecreate"></a>
    
beforeCreate(&amp;$model)
---------------------------
> 顾名思义在新增中执行保存前所调用的方法，该方法可以在实际的应用中在即将入库的数据进行预处理，也可进行其他操作。也可以不使用此方法  
而使用继承ActiveRecord的model使用beforeSave来应对。

示例：

    public function beforeCreate(&$model)
    {

        $merge_area_a      = $model->merge_area_a;
        $merge_area_date_i = $model->merge_area_date_i;

        $area_arr = array();

        for($i = 0; $i < sizeof($model->merge_area_a); $i++){
            $area_arr[$i]['area'] = $merge_area_a[$i];
            $area_arr[$i]['date'] = $merge_area_date_i[$i];
        }

        $model->merge_area = json_encode($area_arr);
    }
    
<a name="beforeedit"></a>
    
beforeEdit(&amp;$model)
-------------------------
> 同样在修改操作的时候执行保存前所调用的方法，该方法可以在实际的应用中在即将入库的数据进行预处理，也可进行其他操作。也可以不使用此方法  
而使用继承ActiveRecord的model使用beforeEdit来应对。

示例：

    public function beforeedit(&$model)
    {
        $merge_area_a      = $model->merge_area_a;
        $merge_area_date_i = $model->merge_area_date_i;

        $area_arr = array();

        for($i = 0; $i < sizeof($model->merge_area_a); $i++){
            $area_arr[$i]['area'] = $merge_area_a[$i];
            $area_arr[$i]['date'] = $merge_area_date_i[$i];
        }

        $model->merge_area = json_encode($area_arr);
    }
    
<a name="beforedelete"></a>
    
beforeDelete(&amp;$model)
-------------------------
> 同样在删除操作的时候执行保存前所调用的方法，该方法可以在实际的应用中在即将入库的数据进行预处理或者进行是否有权利进行删除的判断，  
也可进行其他操作。也可以不使用此方法而使用继承ActiveRecord的model使用beforeDelete来应对。

示例：

    public function beforeDelete(&$model)
    {
        if(!in_array(1,$roles = ArrayHelper::getColumn(RbacAdminRole::findAll(['admin_id' => Yii::$app->user->id]),'role_id'))){
            die('listen! Kid, you don\'t have this permission');
        }
    }
    
afterEdit()
-----------
> 一般情况下很少会使用到本方法，而是直接使用继承自ActiveRecord的model，使用afterSave()方法来实现具体的业务，但是有些场景业务会  
在更新结束后向视图中传递一些参数，此时即可以使用本方法了。

示例:

    public function afterEdit()
    {
        $this->editConfig['anyParams']['filename'] = $this->editQueryModel->tpl_save_path;
    }
    
<a name="model"></a>
    
public $model
-------------
> 这是一个必须被赋值的属性，通过实例化后的数据Model赋值给这个属性，从而实现基础的增删改查；本属性赋值的硬性要求是必须在initConfig()  
方法中完成。

示例：

    public function initSetConfig()
    {
        $this->model = new BoxBanner();
    }
    
<a name="editscenario"></a>
    
public $editScenario
--------------------
> 这个属性是一个选择性的参数，当修改的操作的时需要指定model场景的时候可以进行赋值配置

两种赋值方法：

    // 第一种
    class A extends CURDController
    {
        public $editScenario = 'edit';
    }
    
    // 另一种
    class A extends CURDController
    {
        public function initSetConfig(){
            $this->editScenario = 'edit';
        }
    }
    
<a name="editquerymodel"></a>
    
public $editQueryModel
----------------------
> 这个属性是一个不需要进行配置的属性，本属性是在修改操作是在load方法执行前所调用的方法，注意这是个查询对象实体，即findOne()后的  
数据对象，也就是没有被修改掉的原数据，即可以在beforeEdit中使用，也可以在afterEdit中使用，可能做出的业务逻辑由很多种。

示例：

    public function beforeEdit(&$model){
        $this->model->auth .= $this->editQueryModel->auth;
    }
    
<a name="createscenario"></a>
    
public $createScenario
----------------------
> 新增时选择性需要指定的场景，用法同$editScenario,不在复述。

<a name="selectscenario"></a>

public $selectScenario
-------------------------
> 查询时选择性需要指定的场景，用法同$editScenario,不在复述。

<a name="selectconfig"></a>

public $selectConfig
-------------------------
> 查询时的定制化配置属性，目前支持4种配置项。

1. pageSize：设定分页数量，默认一页10条数据

2. defaultOrder：设定排序规则，默认id倒序排序

3. screen：设定是否向view中传递model

4. template：指定模板文件，默认指定index

示例：

    public function initSetConfig()
    {
        $this->model = new A();
        $this->selectConfig = [
            'pageSize'     => 20,
            'defaultOrder' => ['created_at' => SORT_DESC],
            'screnn'       => true,
            'template'     => 'select'
        ];
    }
    
<a name="editconfig"></a>

public $editConfig
-------------------------
> editConfig是一个很少被使用配置属性，目前仅支持1个配置项。

1. anyParams：当需要在修改操作结束后向模板传递一个或多个参数的时候，可以使用anyParams进行设置。

示例：

    public function afterEdit()
    {
        $this->editConfig['anyParams']['filename'] = $this->editQueryModel->tpl_save_path;
    }

示例中产生的效果是会在模板文件中传递filename值，模板中调用的方式是params['filename']，这个功能是可以传递没有上限限制参数。

模板文件中使用示例：

    <a id="insert" class="upload-insert">
        <?php if(isset($params['filename'])): ?>
            <?= "<span class='layui-inline layui-upload-choose'>".substr($params['filename'],-21)."</span>"?>
        <?php endif;?>
    </a>
    

<a name="createsuccessredirect"></a>

protected $createSuccessRedirect
-------------------------
> 设置添加成功后跳转的路径，默认跳转本页。
