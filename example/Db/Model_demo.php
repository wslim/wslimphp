<?php
use Wslim\Db\Model;
use Wslim\Ioc;
use Wslim\Util\DataHelper;

include '../bootstrap.php';

$app = Ioc::web();

class FuckYouModel extends Model
{
    
}

function testFormat()
{
    $model = Model::instance('demo');
    
    $data = [
        //'text'=> '<script onerror="" abc">alert(1)</script>',
        //'text' => '<script>https://c.com/b.php?cookie=window.cookie</script>',
        //'content' => '<script onerror="" abc">alert(1)</script>',
        
    ];
    $data = DataHelper::filter_xss($data);
    print_r($data);
    $sql = [
        $model->where($data)->parse(),
        $model->insert()->set($data)->save(),
    ];
    print_r($sql);
    
}
testFormat(); exit;

// test new
function testModel()
{
    $options = [
        'table_name' => 'demo'
    ];
    
    // $model = new Model();
    $model = new Model($options);
    print_r($model);
    
    $model = new Model('api/ModelModel');
    print_r($model);
    
    $model = new Model('test.user');
    print_r($model);
    
    $model = new Model('#ws_demo');
    print_r($model);
    
}
//testModel();exit;


// test extend new
function testFuckYouModel()
{
    $model = new FuckYouModel();
    print_r($model);
}
//testFuckYouModel();exit;


function testInstance()
{
    global $model;
    
    $model = Model::instance('demo');
    print_r($model);
    
    //$result = $model->find();
    //$model->cacheSet('aaa', $result);
    //print_r($result);
    
    $result = $model->cacheGet('aaa');
    print_r($result);
    
    $cacheKey = $model->getCache()->formatKey('aaa');
    print_r($cacheKey);
}
//testInstance(); exit;


function testInstance2()
{
    // 方式1: Model::instance('table_name')
    $model = Model::instance('demo');
    var_dump($model);
    
    //
    $model = Model::instance('common/demo');
    var_dump($model);
    
    // 方式2: Model::instance('class_name')
    $model = Model::instance('Common\\Model\\DemoModel');
    var_dump($model);
    
    // 方式3: DemoModel::instance()
    Ioc::load(__DIR__ . '/DemoModel.php');
    $model = \Demo\Db\TestModel::instance();
    var_dump($model);
}
//testInstance2(); exit;

function testQuery()
{
    $model = Ioc::model('demo');
    $result = $model->select('id, title')->where(4)->find();
    print_r($result);
}
testQuery();exit;

// $result = $model->getDatabase() . '.' . $model->getTablePrefix() . '|' . $model->getTableName();
// print_r($result);exit;

// $result = $model->getPrimarykey();
// print_r($result);exit;

//$result = $model->buildRealTableName('test');
//print_r($result);exit;

//$result = $model->result_key('id')->query('select * from ws_demo');
//print_r($result);exit;

//$result = $model->count();
//print_r($result);exit;

//$result = $model->fields('id')->limit(12)->result_key('id')->count();
//print_r($result);exit;

//$result = $model->select('id,name')->where(['id'=>3])->query();
//print_r($result);exit;

//$result = $model->select('id,name')->where(['id'=>3])->find();
//print_r($result);exit;

//$result = $model->select('id,name')->findById(2);
//print_r($result);exit;

//$result = $model->where('id=3')->findField('title');
//print_r($result);exit;

function testAdd()
{
    global $model;
    $data = array(
        'title'	=> '测试标题x',
    );
    $where = array(
        'id' => ['=', 1]
    );
    $model->add($data);
    //$model->add($data, $where);
    
    print_r($model->fetchAll());
}

function testSave()
{
    global $model;
    $data = array(
        'id' => 4,
        'title'	=> '测试标题6',
    );
    $where = ['id' => 4];
    $model->where($where)->save($data);
    //$model->save($data);
    print_r($model->fetchAll());
}


function testRemove()
{
    global $model;
    $where = ['id' => 4];
    $model->remove($where);
    //$model->where($where)->remove();
    print_r($model->fetchAll());
}

function testFetchKeyValues()
{
    global $model;
    
    $where = ['id' => ['in', [1,2,3]]];
    //$result = $model->select('id, name')->where($where)->fetchKeyValues();
    //$result = $model->where($where)->fetchKeyValues('id, name');
    $result = $model->where($where)->fetchKeyValues(['id', 'title']);
    print_r($result);
}

function testPagerList()
{
    global $model;
    
    $result = $model->where($where)->pagesize(1)->fetchPager();
    print_r($result);exit;
}

function testPage()
{
    global $model;
    
    $result = $model->select('id, name')->where($where)->pagesize(1)->parse();
    print_r($result);

    $sql = $model->select()->where($where)->page(1)->pagesize(1)->order('title')->result_key('id')->parse();
    $result = $model->select()->where($where)->page(1)->pagesize(1)->order('title')->result_key('id')->query();
    print_r($result);

    $result = $model->query();
    print_r($result);
}

function testMoreTableQuery()
{
    global $model;
    
    $result = $model->select('t.name, t.id, t2.name name2')->from('demo as t, demo as t2')->where('t.id=t2.id')->find();
    print_r($result);
}

function testDdlQuery()
{
    $res = Ioc::db()->createDatabase('test2');
    print_r($res);
    
}
testDdlQuery(); exit;


