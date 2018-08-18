db library
===================

### example

#### chained methods
```
$node = Ioc::model('demo')->where('name', 'aaa')->find();
print_r($node);
```

#### directly sql 
```
$sql = "select * from demo limit 5";
$rows = Ioc::model()->query($sql);
print_r($rows);
```

### about sql escape_string

* 自己拼装sql或条件，需要对值进行显式的 addslashes() 或使用数据库的 escape_string 相关方法。
```
$sql = 'select * from demo where title = \'' . DataHelper::addslashes($value) . '\'';
$rows = Ioc::model()->query($sql);

//or
$rows = Ioc::model()->table('demo')->where(title = \'' . DataHelper::addslashes($value) . '\'')->query();
```

* 使用Db或model的生成语句，会自动进行 escape_string, 见 `\Wslim\Db\Parser\AbstractQueryParser`
```
$rows = Ioc::model()->table('demo')->where('title', $value)->query();
```


