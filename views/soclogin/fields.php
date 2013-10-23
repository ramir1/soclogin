<? echo $message; ?>

<? echo Form::open(NULL, array('method'=>'get')) ?>
<br><br>
<? 
foreach ($_GET as $name=>$value){
echo Form::hidden($name,$value); 

}
foreach ($fields as $field){
$val=(isset($_GET[$field])?$_GET[$field]:null);
echo Form::input($field,$val,array('placeholder'=>$field)); 

}
echo Form::submit('submit', 'Продолжить')
?>
<? echo Form::close() ?>