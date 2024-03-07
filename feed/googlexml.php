<?php
set_time_limit(6000);

function getsub($parents, $object){
	global $modx;
	$arr = Array();
	$collection = $modx->getCollection($object, array('parent:IN' => $parents));
	foreach ($collection as $key => $value) {
		if (!$value->getTVValue('export.exclude')){
			$arr[] = $value->get('id');	
		}		
	}	
	return $arr;	
}

define('MODX_API_MODE', true);
include_once '../index.php';

$where = $modx->newQuery('msCategory');
$where->leftJoin('modTemplateVarResource', 'TemplateVarResources');
$where->leftJoin('modTemplateVar', 'tv', "tv.id=TemplateVarResources.tmplvarid");
$where->limit(0);
$where->where(array(
    array(
        'tv.name'   => 'export.use',
        'TemplateVarResources.value'    => '1'
    )
));
$cid = $modx->getCollection('msCategory',$where);
$i = 0;
foreach ($cid as $key => $value) {
	$categories_id[$i][] = $value->get('id');
}
while (count($categories_id[$i])) {	
	$categories_id[$i + 1] = getsub($categories_id[$i], 'msCategory');
	$i++;
}
$products_id = Array();
$i = 0;
foreach ($categories_id as $key => $value) {
	$products_id[$i] = getsub($value, 'msProduct');
	$i++;
}

$cats = Array();
$goods = Array();
foreach ($categories_id as $key => $value) {
	foreach ($value as $key1 => $value1) {
		$cats[] = $value1;
	}
}
foreach ($products_id as $key => $value) {
	foreach ($value as $key1 => $value1) {
		$goods[] = $value1;
	}
}

$where = $modx->newQuery('msProduct');
$where->leftJoin('modTemplateVarResource', 'TemplateVarResources');
$where->leftJoin('modTemplateVar', 'tv', "tv.id=TemplateVarResources.tmplvarid");
$where->limit(0);
$where->where(array(
    array(
        'tv.name'   => 'export.use',
        'TemplateVarResources.value'    => '1'
    )
));
$pid = $modx->getCollection('msProduct',$where);
foreach ($pid as $key => $value) {
	if (!$value->getTVValue('export.exclude')){
		$cats[] = $value->get('parent');
		$goods[] = $value->get('id');
	}
}
$cats = array_unique($cats);
echo "<pre>";
echo "categories_id <br>";
print_r($categories_id);
echo "cats <br>";
print_r($cats);
echo "products <br>";
print_r($goods);
echo "</pre>";
$no_array = array("&", "'", ">", "<", "«", "»");
$yes_array   = array("&amp;", "&apos;", "&gt;", "&lt;", "&quot;", "&quot;");

$filename = 'googlemerchant.xml';
unlink($filename);
$file = fopen($filename, 'w');
fclose($file);
$file = fopen($filename, 'a');

fwrite($file, 
    '<?xml version="1.0" encoding="UTF-8"?>
	<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
    <channel>
    <title>Телеком-Про</title>
    <description>telecom-pro.ru</description>
    <link>https://telecom-pro.ru/</link>'
);

$x = $modx->getCollection('msProduct', array('id:IN' => $goods));
$output = "";
foreach ($x as $key => $value) {
    $p = $modx->getObject('modResource', $value->get('parent'));
    if ($p){
        $parent = $p->get('pagetitle');
    } else {
        $parent = "";
    }

    if ($image = $value->get('thumb')){
        $image = 'http://telecom-pro.ru' . $image;
    } else {
        $image = ''; // нет пикчи
    }
    if (strlen($value->get('content'))){
        $desc = strip_tags($value->get('content'));
        $desc = str_replace($no_array, $yes_array, $value->get('content'));
    } else {
    	$desc = '';
    }
    $output .= '
    	<item>
        	<title>' . $value->get('pagetitle') . '</title>
			<link>http://telecom-pro.ru/' . $value->get('uri') .'</link>
			<description>' . $desc . '</description>
			<g:id>' . $value->get('id') . '</g:id>			
			<g:brand>'. $value->get('vendor.name') . '</g:brand>
			<g:product_type>' . $parent . '</g:product_type>    
			<g:image_link>' . $image . '</g:image_link>
			<g:google_product_category>' . $parent . '</g:google_product_category>         
			<g:price>' . $value->get('price') . ' RUB</g:price>
			<g:availability>in stock</g:availability>
			<g:condition>новый</g:condition>			
		</item>';
}
fwrite($file, $output);
fwrite($file, '</channel></rss>');  
fclose($file);  
echo "TOTAL: " . count($x) . "<br/>";
echo "SUCCESS";