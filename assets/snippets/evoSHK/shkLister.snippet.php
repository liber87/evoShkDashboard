<?php
/**
 * shkLister
 *
 * DocLister wrapper for Shopkeeper 
 *
 * @category    snippet
 * @version    1.0.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @properties
 * @internal    @modx_category Shop
 * @author      Alexey Liber (alexey@liber.pro)
 */
 
if (!function_exists('shkListerPrepare'))	
{
	function shkListerPrepare($data)
	{
		global $modx;
		$purchases = $modx->getPlaceholder('purchases');

		$price_total = $modx->getPlaceholder('price_total');
		$total_items = $modx->getPlaceholder('total_items');

		foreach($purchases as $val) 
		{		
			if ($val[0]==$data['id']) 
			{
				$data['count'] = $val[1];
				$modx->setPlaceholder('total_items',$data['count']+$total_items);
				$data['price'] = $val[2];
				$evtOut = $modx->invokeEvent('OnSHKgetProductPrice',array('price' => $data['price'],'purchaseArray' => $purchases));
				if(is_array($evtOut)) $data['price'] = $evtOut[0];				
				$data['summ'] = $data['count']*$data['price'];				
				$modx->setPlaceholder('price_total',$data['summ']+$price_total);
				if (count($val['tv_add'])) foreach($val['tv_add'] as $k => $v) $data['add.'.$k] = $v;

			}
		}		

		if ($data['iteration']==(count($purchases)-1))
		{
			$evtOut = $modx->invokeEvent('OnSHKcalcTotalPrice',array('totalPrice' => $modx->getPlaceholder('price_total'),'purchases' => $purchases));
			if(!empty($evtOut[0])) $modx->setPlaceholder('price_total',$evtOut[0]);
		}		
		return $data;
	}
}


if ((empty($_SESSION['purchases'])) && (empty($orderId))) $empt = '-1';
if (isset($orderId)) 
{
	$orderId = (int) $orderId;
	$purchases = $modx->db->getValue('Select `content` from '.$modx->getFullTableName('manager_shopkeeper').' where id='.$orderId);
	if (!$purchases) $empt = '-1';
	else $purchases = unserialize($purchases);
}
else $purchases = unserialize($_SESSION['purchases']);
$modx->setPlaceholder('purchases',$purchases);

if (!isset($empt))
{
	$ids = array();
	foreach($purchases as $val) $ids[] = $val[0];
	$params['documents'] = implode(',',$ids);
	if (isset($prepare)) $params['prepare'] = 'shkListerPrepare,'.$params['prepare'];
	else $params['prepare'] = 'shkListerPrepare';
}
else $params['documents'] = '-1';

if ($params['ownerTPL'])
{
	$ownerTPLExt = $params['ownerTPL'];
	unset($params['ownerTPL']);	
}

$out =  $modx->runSnippet('DocLister', $params);
include_once(MODX_BASE_PATH.'assets/snippets/DocLister/lib/DLTemplate.class.php');
$tpl = DLTemplate::getInstance($modx);
if ($ownerTPLExt) echo $tpl->parsechunk($ownerTPLExt,array('dl.wrap'=>$out,
										   'price_total'=>$modx->getPlaceholder('price_total'),
										   'total_items'=>$modx->getPlaceholder('total_items')));
else echo $out;
