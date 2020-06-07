<?php
if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

if (empty($_SESSION['mgrInternalKey'])) {
    return;
}

if (!$ownerTplTable) $ownerTplTable = '@CODE: '.file_get_contents(MODX_BASE_PATH.'/assets/plugins/evoShkDashboard/tpl/ownerTplTable.tpl');
if (!$rowTplTable) $rowTplTable = '@CODE: '.file_get_contents(MODX_BASE_PATH.'/assets/plugins/evoShkDashboard/tpl/rowTplTable.tpl');
if ($prepareTable) $prepareTable = ','.$prepareTable;

if (!$ownerTplForm) $ownerTplForm = '@CODE: '.file_get_contents(MODX_BASE_PATH.'/assets/plugins/evoShkDashboard/tpl/ownerTplForm.tpl');
if (!$rowTplForm) $rowTplForm = '@CODE: '.file_get_contents(MODX_BASE_PATH.'/assets/plugins/evoShkDashboard/tpl/rowTplForm.tpl');

if (!$statusNames) $statusNames='Новый==C5CAFE||Принят к оплате==B1F2FC||Отправлен==F3FDB0||Выполнен==BEFAB4||Отменен==FFAEAE||Оплата получена==FFE1A4';
$_SESSION['statusNamesArr']	= array();
$i=1;
foreach(explode('||',$statusNames) as $name)
{
	$col = explode('==',$name);
	$_SESSION['statusNamesArr'][$i] = array('name'=>$col[0],'color'=>$col[1]);
	$i++;	
}


if (!function_exists('ShopDashboardPrepare'))
{
	function ShkDashboardPrepare($data)
	{
		global $modx;		
		$info = unserialize($data['short_txt']);	
		if (count($info)) foreach($info as $key => $val) $data[$key] = $val;			
		$data['statusname'] = $_SESSION['statusNamesArr'][$data['status']]['name'];
		return $data;
	}
}
if ($modx->event->name=='OnPageNotFound')
{
	if ($_REQUEST['q']=='deleteOrder')
	{
		header("HTTP/1.1 200 OK");
		$oid = (int) $_GET['oid'];
		$modx->db->query('Delete from '.$modx->getFullTableName('manager_shopkeeper').' where id = '.$oid);
		echo 'OK';
		exit();
	}
	
	if ($_REQUEST['q']=='updateOrder')
	{
		header("HTTP/1.1 200 OK");
		$oid = (int) $_GET['oid'];
		$ser = $modx->db->getValue('Select short_txt from '.$modx->getFullTableName('manager_shopkeeper').' where id = '.$oid);
		$info = unserialize($ser);
		foreach($info as $key => $val) if ($_POST[$key]) 
		{
			$info[$key]=$modx->db->escape($_POST[$key]);
			echo $_POST[$key];
		}
		
		$modx->db->update(array('short_txt'=>serialize($info),'status'=>$_POST['status']),$modx->getFullTableName('manager_shopkeeper'),'id='.$oid);
		
		echo 'OK';
		exit();
	}
	if ($_REQUEST['q']=='getInfoForOrder')
	{
		header("HTTP/1.1 200 OK");
		$oid = (int) $_GET['oid'];
		
		echo '<form method="post" action="./../updateOrder?oid='.$oid.'" id="updateOrder"><h2 style="    position: absolute;    top: 10px;"><span style="border-bottom:1px dashed black;">Заказ #'.$oid.'</span></h2><h3>Список товаров</h3>';
		echo $modx->runSnippet('shkLister',array('ownerTPL'=>$ownerTplForm,
												  'tpl'=>$rowTplForm,
												  'tvPrefix'=>'',
												  'prepare'=>$prepareForm,
												  'tvList'=>$tvListForm,//'brand,volume,articul,img',										  
												  'orderId'=>$oid));
		echo '<h3 style="margin-top:20px;">Данные о клиенте</h3>';
		$ser = $modx->db->getValue('Select short_txt from '.$modx->getFullTableName('manager_shopkeeper').' where id = '.$oid);
		$info = unserialize($ser);
		$ff = explode('||',$form_fields);
		echo '<table style="width:100%;">';
		foreach($ff as $str)
		{
			$col = explode('==',$str);
			echo '<tr><td>'.$col[1].'</td><td><input type="text" name="'.$col[0].'" value="'.$info[$col[0]].'"></td></tr>';
		}
		echo '</table>';		
		$st = $ser = $modx->db->getValue('Select status from '.$modx->getFullTableName('manager_shopkeeper').' where id = '.$oid);
		
		
		echo '<h3 style="margin-top:20px;">Статус заказа</h3>
		<div class="row statuses" style="padding:0 15px 50px 15px;">';
		foreach($_SESSION['statusNamesArr'] as $key => $status)
		{
			if ($st == $key) $act = 'checked="checked"';
			else $act='';
			echo '<div class="col-xs-2">
			<label>
			<input type="radio" name="status" value="'.$key.'" '.$act.'>
			<span class="status status-'.$key.'"></span> <span class="name">'.$status['name'].'</span>
			</label>			
			</div>';
		}
			
			
		echo '</div>
		<div class="col-xs-12 text-center">
			<input type="submit" value="Сохранить" class="btn btn-success">
		</div>
		</form>
		';
		
		exit();
	}
}
if ($modx->event->name=='OnManagerWelcomeHome')
{
	$out = '<style>
	.status{display: inline-block;background-color: #2DCE89; width: 10px; height: 10px;  border-radius: 50%; cursor:pointer;}
	.more-info{cursor:pointer;}
	.evo-popup.alert .evo-popup-header {border:none !important;}';	
	foreach($_SESSION['statusNamesArr'] as $key => $status) $out.='.status-'.$key.'{background-color:#'.$status['color'].';}';		
	
	$out.='.statuses input{display:none;}
	.trash-item{cursor:pointer;}
	.statuses input:checked ~ .name {font-weight:700; border-bottom:1px dashed;}
	
	</style>';
	$out.="
	
	<script>
	
	
	document.addEventListener('DOMContentLoaded', function(){
		
		$('.trash-item').click(function(){						
			var oid = $(this).data('id');
			if(confirm('Вы уверены?'))
			{
				$(this).closest('tr').hide();
				$.ajax({type: 'get',url: './../deleteOrder?oid='+oid,success: function(result){}});
			}
		});
		
		$('.more-info').click(function(){			
			var oid = $(this).data('id');
			
			modx = parent.modx;
			
			$.ajax({
                type: 'get',
                url: './../getInfoForOrder?oid='+oid,
                success: function(result){  				
					modx.popup({
					  content: result,
					  title: ' ',
					  draggable: false,
					  'width': '90%',
					  'hide': 0,
					  'hover': 0
					});
					              		
                }
            });
		});
		
		$(document).on('submit','#updateOrder',function(e){
            e.preventDefault();
            var m_method=$(this).attr('method');
            var m_action=$(this).attr('action');
            var m_data=$(this).serialize();
            $.ajax({
                type: m_method,
                url: m_action,
                data: m_data,
                resetForm: 'true',
                success: function(result){
                    
                }
            });
			});
		
	});
	</script>";
	
	
	if (isset($_POST['date_from']))
	{
		$_SESSION['date_from'] = $_POST['date_from'];
		$_SESSION['date_to'] = $_POST['date_to'];
	}
	$aw = 1;
	if (isset($_SESSION['date_from']))
	{
		$aw = "DATE(date) BETWEEN '".$_SESSION['date_from']."' AND '".$_SESSION['date_to']."'";
	}
	
	
	$out.= $modx->runSnippet('DocLister', array(
	'controller'=>'onetable',
	'table'=>'manager_shopkeeper',
	'idField'=>'id',
	'orderBy'=>'id desc',		
	'display'=>15,
	'parents'=>'1,2,3,4,5,6',
	'parentField'=>'status',
	'showParent'=>-1,
	'prepare'=>'ShkDashboardPrepare'.$prepareTable,
	'addWhereList'=>$aw,
	'id'=>'list',
	'paginate'=>'pages',
	'TplNextP'=>'',
	'TplPrevP'=>'',
	'TplPage'=>'@CODE: <li><a href="/manager/index.php?a=2&list_page=[+num+]">[+num+]</a></li>',
	'TplCurrentPage'=>'@CODE: <li class="active"><a>[+num+]</a></li>',
	'TplWrapPaginate'=>'@CODE: <ul id="pagination">[+wrap+]</ul>',
	'TplDotsPage'=>'@CODE: <li><a>...</a></li>',
	'ownerTPL'=>$ownerTplTable,	
	'tpl'=>$rowTplTable));



	$widgets['welcome']['hide']='1';
	$widgets['onlineinfo']['hide']='1';
	$widgets['recentinfo']['hide']='1';
	$widgets['news']['hide']='1';
	$widgets['security']['hide']='1';                 

	$widgets['shkfilter'] = array(
	'menuindex' =>'-2',
	'id' => 'shkfilter',
	'cols' => 'col-sm-12',
	'icon' => '',
	'title' => 'Фильтрация',
	'body' => '<div class="card-body">	
	<form method="post" action="">
	<table style="width:100%;">
	<tr>
	<td><input type="date" name="date_from" style="width:100% !important;" value="'.$_SESSION['date_from'].'"></td>
	<td><input type="date" name="date_to"  style="width:100% !important;"  value="'.$_SESSION['date_to'].'"></td>
	<td><input type="submit" value="Отобразить" class="btn btn-success" style="height: 33.59px; width:100% !important;"></td>
	</tr>
	</table>
	</form>
	</div>',
	'hide'=>'0'
	);
	
	

	$all = $modx->db->getValue('Select count(*) from '.$modx->getFullTableName('manager_shopkeeper').' WHERE '.$aw);
	$widgets['order'] = array(
	'menuindex' =>'1',
	'id' => 'shop',
	'cols' => 'col-sm-3',
	'icon' => '',
	'title' => 'ЗАКАЗОВ',
	'body' => '<div class="card-body" style="font-size:32px; background: #e0e0e0; color: #8a8a8a;">
		<i class="fa fa-shopping-cart" aria-hidden="true"></i> 
		<span style="float:right;">'.$all.'</span>
	</div>',
	'hide'=>'0'
	);
	$summ = $modx->db->getValue('Select sum(price) from '.$modx->getFullTableName('manager_shopkeeper').' WHERE '.$aw);
	$summ = number_format($summ, 0, '', ' ');
	$widgets['earnings'] = array(
	'menuindex' =>'4',
	'id' => 'earnings',
	'cols' => 'col-sm-3',
	'icon' => '',
	'title' => 'ПРОДАЖИ (руб.)',
	'body' => '<div class="card-body" style="font-size:32px; background: #e0e0e0; color: #8a8a8a;">
		<i class="fa fa-money" aria-hidden="true"></i> 
		<span style="float:right;">'.$summ.'</span>
	</div>',
	'hide'=>'0'
	);


	$new = $modx->db->getValue('SELECT count(*) FROM '.$modx->getFullTableName('manager_shopkeeper').'  where phone in
								(SELECT phone FROM '.$modx->getFullTableName('manager_shopkeeper').' GROUP BY phone HAVING COUNT(phone)=1)
								and '.$aw);
	
	
	$widgets['clients'] = array(
	'menuindex' =>'2',
	'id' => 'clients',
	'cols' => 'col-sm-3',
	'icon' => '',
	'title' => 'НОВЫЕ КЛИЕНТЫ',
	'body' => '<div class="card-body" style="font-size:32px; background: #e0e0e0; color: #8a8a8a;">
		<i class="fa fa-users" aria-hidden="true"></i> 
		<span style="float:right;">'.$new.'</span>
	</div>',
	'hide'=>'0'
	);

	$old = $all-$new;
	$widgets['online'] = array(
	'menuindex' =>'3',
	'id' => 'clients',
	'cols' => 'col-sm-3',
	'icon' => 'fa-users',
	'title' => 'ПОВТОРНЫЕ КЛИЕНТЫ',
	'body' => '<div class="card-body" style="font-size:32px; background: #e0e0e0; color: #8a8a8a;">
		<i class="fa fa-eye" aria-hidden="true"></i>
		<span style="float:right;">'.$old.'</span>
	</div>',
	'hide'=>'0'
	);

	$widgets['shop'] = array(
	'menuindex' =>'5',
	'id' => 'shop',
	'cols' => 'col-sm-12',
	'icon' => 'fa-shopping-cart',
	'title' => 'Управление заказами',
	'body' => '<div class="card-body">'.$out.'<div align="center">'.$modx->getPlaceholder('list.pages').'</div></div>',
	'hide'=>'0'
	);
	$modx->event->output(serialize($widgets));
}