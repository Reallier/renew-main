<?php
if (!defined('WHMCS')) {
    exit('This file cannot be accessed directly');
}
// NeWorld Manager 开始

// 引入文件
require  ROOTDIR . '/modules/addons/NeWorld/library/class/NeWorld.Common.Class.php';

// NeWorld Manager 结束

use WHMCS\Database\Capsule;

// 判断函数是否不存在
if (!function_exists('ReNew_config')) {
	function ReNew_config() {
	    $configarray = [
	        'name'        => 'Renewal Products',
	        'description' => 'Allow customers to renew their products!',
	        'version'     => '2.0',
	        'author'      => '<a href="http://neworld.org" target="_blank">NeWorld</a>',
	        'fields'      => []
	    ];
		
	    return $configarray;
	}
}

// 判断函数是否不存在
if (!function_exists('ReNew_activate')) {
    // 插件激活
	function ReNew_activate() {
			try {
				if (!Capsule::schema()->hasTable('mod_renewal')) {
					Capsule::schema()->create('mod_renewal', function ($table) {
						$table->increments('id');
						$table->unsignedInteger('userid');
						$table->unsignedInteger('serviceid');
						$table->unsignedInteger('invoiceid');
						$table->text('subtotal');
						$table->text('type');
						$table->dateTime('date')->default('0000-00-00 00:00:00');
						$table->dateTime('datepaid')->default('0000-00-00 00:00:00');
						$table->text('status');
					});
				}
			} catch (Exception $e) {
				return [
					'status' => 'error',
					'description' => '不能创建表 mod_renewal: ' . $e->getMessage()
				];
			}
			return [
				'status' => 'success',
				'description' => '模块激活成功. 点击 配置 对模块进行设置。'
			];
	}
}

// 判断函数是否不存在
if (!function_exists('ReNew_deactivate')) {
    // 插件卸载
	function ReNew_deactivate() {
		try {
			Capsule::schema()->dropIfExists('mod_renewal');
			return [
				'status' => 'success',
				'description' => '模块卸载成功'
			];
		} catch (Exception $e) {
			return [
				'status' => 'error',
				'description' => 'Unable to drop tables: ' . $e->getMessage()
			];
		}
	}
}

// 判断函数是否不存在
if (!function_exists('ReNew_clientarea')) {
	function ReNew_clientarea($vars) {
		

	        
	        if ( function_exists('bcmul') ) {
		        $results["alert"] = '请安装 bcmul 组件';
	        }
	
	        $productID 		= trim($_REQUEST['id']);
	        $userID 		= trim($_SESSION['uid']);
	        $invoiceID		= trim($_SESSION['ReNewInvoice']); //获取账单ID
	        
	        
			if ( !empty( $productID ) ) {
		        // 有商品ID
		        $templatefile = 'renew';
			} else {
				// 无商品ID
		        $templatefile = 'index';
		        $products = Capsule::table('tblhosting')->where('userid', $userID)->get();
		        $product = [];
						
			    //print_r($products);die();
				foreach ($products as $key => $values) {
					$package = Capsule::table('tblproducts')->where('id', $values->packageid)->first();
					if ( $values->billingcycle != 'Free Account' && $values->billingcycle != 'One Time' ) {
						if ( $values->domainstatus == "Active" || $values->domainstatus == "Suspended") {
							$product[$key]['id'] 			= $values->id;
							$product[$key]['productname'] 	= $package->name;
							$product[$key]['domain'] 		= $values->domain;
							$product[$key]['regdate'] 		= $values->regdate;
							$product[$key]['nextduedate'] 	= $values->nextduedate;
							$product[$key]['amount'] 		= $values->amount;
					    }
					}
				}
		
			}
			
	        // 当前产品信息
	        $products = Capsule::table('tblhosting')->where('userid', $userID)->where('domainstatus', 'Active')->where('id', $productID)->first();
	
	        // 匹配产品信息
	        $package = Capsule::table('tblproducts')->where('id', $products->packageid)->first();
	
	        // 匹配产品价格信息
	        $pricing = Capsule::table('tblpricing')->where('relid', $products->packageid)->where('type', 'product')->where('currency', Capsule::table('tblclients')->where('id', $userID)->first()->currency)->first();
	
	        // 匹配产品支付名称
	        $gateway = Capsule::table('tblpaymentgateways')->where('gateway', $products->paymentmethod)->where('setting', 'name')->first();
	
	        switch ($products->billingcycle) {
	            case 'Free Account':
	                $results['billingcycle']	= LANG::trans('orderpaymenttermfreeaccount');
					$results['months']=1;
	                break;
	            case 'One Time':
	                $results['billingcycle']	= LANG::trans('orderpaymenttermonetime');
					$results['months']=1;
	                break;
	            case 'Monthly':
	                $results['billingcycle']	= LANG::trans('orderpaymenttermmonthly');
	                $results['billingCycle']	= '<li data-time="1" data-date="1" class="active before">1</li><li data-time="2" data-date="2">2</li><li data-time="3" data-date="3">3</li><li data-time="4" data-date="4">4</li><li data-time="5" data-date="5">5</li><li data-time="6" data-date="6">6</li><li data-time="7" data-date="7">7</li><li data-time="8" data-date="8">8</li><li data-time="9" data-date="9">9</li><li data-time="12" data-date="12">1 年</li><li data-time="24" data-date="24">2 年</li><li data-time="36" data-date="36">3 年</li>';
					$results['months']=1;
	                break;
	            case 'Quarterly':
	                $results['billingcycle']	= LANG::trans('orderpaymenttermquarterly');
	                $results['billingCycle']	= '<li class="active before" data-time="3" data-date="1">3</li><li data-time="6" data-date="2">6</li><li data-time="9" data-date="3">9</li><li data-time="12" data-date="4">1 年</li><li data-time="24" data-date="8">2 年</li><li data-time="36" data-date="12">3 年</li>';
					$results['months']=3;
	                break;
	            case 'Semi-Annually':
	                $results['billingcycle']	= LANG::trans('orderpaymenttermsemiannually');
	                $results['billingCycle']	= '<li class="active before" data-time="6" data-date="1">6</li><li data-time="12" data-date="2">1 年</li><li data-time="24" data-date="4">2 年</li><li data-time="36" data-date="8">3 年</li>';
					$results['months']=6;
	                break;
	            case 'Annually':
	                $results['billingcycle']	= LANG::trans('orderpaymenttermannually');
	                $results['billingCycle']	= '<li class="active" data-time="12" data-date="1">1 年</li><li data-time="24" data-date="2">2 年</li><li data-time="36" data-date="3">3 年</li>';
					$results['months']=12;
	                break;
	            case 'Biennially':
	                $results['billingcycle']	= LANG::trans('orderpaymenttermbiennially');
	                $results['billingCycle']	= '<li class="active" data-time="24" data-date="1">2 年</li>';
					$results['months']=24;
	                break;
	            case 'Triennially':
	                $results['billingcycle']	= LANG::trans('orderpaymenttermtriennially');
	                $results['billingCycle']	= '<li class="active" data-time="36" data-date="1">3 年</li>';
					$results['months']=36;
	                break;
	            default:
	        }
	        
	        $results['productname'] 		= $package->name; // 模块名称
	        $results['productid'] 			= $package->id; // 模块ID
	        $results['domain'] 				= $products->domain; // 产品简称
	        $results['id'] 					= $products->id; // 购买产品ID
	        $results['regdate']				= $products->regdate; // 产品购买时间
			// 必须进行调用转换为数字,否则下面啥都不是
	        $results['firstpaymentamount']	= formatCurrency( $products->firstpaymentamount )->toNumeric(); // 第一次付款
			// 换算下来每个月的价格
			// firstpaymentamount 使用优惠价格(即第一次付款的价格)
	        $results['amount']				= bcdiv($results['firstpaymentamount'],$results['months'],2); // 续费金额
	        $results['paytype'] 			= $package->paytype; // 付款周期
	        $results['paymentmethod']		= $gateway->value; // 支付方式
	        $results['payment']				= $products->paymentmethod;
	        $results['nextduedate']			= $products->nextduedate; // 下次付款日期
	        $FormatDate 					= strtotime($products->nextduedate); // 转换时间戳

	
	        //获取续费后到期时间
	        $time = trim($_GET['time']);
	        $date = trim($_GET['date']);
	        
	        // switch ( $time ) {
		    // 	case '12':
			//         if ( $pricing->annually <= '0.00' ) {
			//         	$results['amounts'] = bcmul($time , $results['amount'], 2);
			//         } else {
			//         	$results['amounts'] = $pricing->annually;
			//         }
	        //         break;
		    // 	case '24':
			//         if ( $pricing->biennially <= '0.00' ) {
			//         	$results['amounts'] = bcmul($time , $results['amount'], 2);
			//         } else {
			//         	$results['amounts'] = $pricing->biennially;
			//         }
	        //         break;
		    // 	case '36':
			//         if ( $pricing->triennially <= '0.00' ) {
			//         	$results['amounts'] = bcmul($time , $results['amount'], 2);
			//         } else {
			//         	$results['amounts'] = $pricing->triennially;
			//         }
	        //         break;
	        //     default:
	        // 		$results['amounts'] = bcmul($time , $results['amount'], 2);
	        //         break;
		    // }
			$results['amounts'] = bcmul($time , $results['amount'], 2);

	
	        if ( !empty( $time ) ) {
	            $code = [
	                'status' => 'success',
	                'date'	 => date('Y-m-d', strtotime('+'.$time.' months', $FormatDate)),
	                'price'	 => $results['amounts'],
					'months'=>$results['months'],
					'firstpaymentamount'=>$results['firstpaymentamount'],
	            ];
	            //print_r($code);die();
	            die(json_encode($code));
	        }
	
	        // 生成账单
	        if ( !empty($_POST) ) {
	            $templatefile	= 'confirm';
	            
	            $id	 			= trim($_POST['id']);
	            $time	 		= trim($_POST['timeCycle']);
	            $num			= trim($_POST['priceCycle']);
	            
		        $ReNewInvoice = Capsule::table('mod_renewal')->where('serviceid', $id)->where('status','UnPaid')->first();
		        
		        if ( !empty($ReNewInvoice) ) {
			        Capsule::table('mod_renewal')->where('serviceid', $id)->where('status','UnPaid')->update([
	                    'status' => 'Cancelled',
	                ]);
			        Capsule::table('tblinvoices')->where('id', $ReNewInvoice->invoiceid)->update([
	                    'status' => 'Cancelled',
	                ]);
		        }
	            
	            // switch ( $time ) {
			    // 	case '12':
				//         if ( $pricing->annually <= '0.00' ) {
				//         	$results['amounts'] = bcmul($num , $results['amount'], 2);
				//         } else {
				//         	$results['amounts'] = $pricing->annually;
				//         }
		        //         break;
			    // 	case '24':
				//         if ( $pricing->biennially <= '0.00' ) {
				//         	$results['amounts'] = bcmul($num , $results['amount'], 2);
				//         } else {
				//         	$results['amounts'] = $pricing->biennially;
				//         }
		        //         break;
			    // 	case '36':
				//         if ( $pricing->triennially <= '0.00' ) {
				//         	$results['amounts'] = bcmul($num , $results['amount'], 2);
				//         } else {
				//         	$results['amounts'] = $pricing->triennially;
				//         }
		        //         break;
		        //     default:
		        // 		$results['amounts'] = bcmul($num , $results['amount'], 2);
		        //         break;
			    // }
				$results['amounts'] = bcmul($time , $results['amount'], 2);
		        
	            // 产品价格
	            $billingcycle = $results['amounts'];
				
				// print_r($billingcycle);die();
				
	            // 格式化到期时间
	            $endtime = date('Y-m-d', strtotime('+'.$time.' months', $FormatDate));
	
	            // 创建账单
	            $admins = Capsule::table('tbladmins')->select('username')->first();
	            $command = "CreateInvoice";
	            $adminuser = $admins->username;
	
	            $values["userid"] 			= $userID;
	            $values["date"] 			= date('Y-m-d', time());
	            $values["duedate"] 			= $products->nextduedate;
	            $values["paymentmethod"] 	= $products->paymentmethod;
	            $values["sendinvoice"] 		= true;
	            $values["itemdescription1"] = $package->name . ' - ' . $products->domain . ' (' . $products->nextduedate . ' - ' . $endtime . ')';
	            $values["itemamount1"] 		= $billingcycle;
	            $values["itemtaxed1"] 		= 0;
	
	            // localAPI 创建账单
	            $results = localAPI($command,$values,$adminuser);
	
	            if ( $results['result'] == 'success' ) {
	                $invoiceid = $results['invoiceid'];
	                if ($invoiceid == "0") {
	                    $results['info'] = '<i class="alico icon-warning-2"></i> 您所选择的产品或服务目前无法续费。';
	                } else {
	                    
/*
	                    mod_renewal', function ($table) {
						$table->increments('id');
						$table->unsignedInteger('userid');
						$table->unsignedInteger('serviceid');
						$table->unsignedInteger('invoiceid');
						$table->text('subtotal');
						$table->text('type');
						$table->dateTime('date')->default('0000-00-00 00:00:00');
						$table->dateTime('datepaid')->default('0000-00-00 00:00:00');
						$table->text('status');
*/
	                    
	                    Capsule::table('mod_renewal')->insert([
			            	'userid' 		=> $userID, 
			            	'serviceid' 	=> $id, // serviceid
			            	'invoiceid' 	=> $invoiceid, // invoiceid
			            	'subtotal' 		=> $billingcycle, 
			            	'type' 			=> $time, 
			            	'date' 			=> date('Y-m-d H:i:s'), 
			            	'status' 		=> 'UnPaid'
			            ]);
			            
	                    header('refresh:3;url='.$WEB_ROOT.'viewinvoice.php?id='.$invoiceid);
	                }
	            }
	        }
	        $results['amount'] = formatCurrency( $products->amount ); // 续费金额
	
	    unset($_POST);
	
	    return [
	        'pagetitle'    			=> '续费管理',
	        'breadcrumb'   			=> ['index.php?m=ReNew' => '续费管理'],
	        'templatefile' 			=> 'templates/'.$templatefile,
	        'requirelogin'			=> true,
	        'vars'         			=> [
	            'results' 			=> $results,
	            'product'			=> $product,
	            'price' 			=> empty($price) ? false : $price,
	            'id' 				=> empty($productID) ? false : $productID,
	            'invoiceid' 		=> empty($invoiceID) ? false : $invoiceID,
	        ]
	    ];
	}
}

// 判断函数是否不存在
if (!function_exists('ReNew_output')) {
    // 插件输出
    function ReNew_output($vars) {
	    $modulelink = $vars['modulelink'];
        try {
            // 实例化扩展类
            $ext = new NeWorld\Extended;
            
            // 实例化数据库类
            $db = new NeWorld\Database;
            
            // 读取数据库中已激活的产品
            $getData = $db->runSQL([
                'action' => [
                    'list' => [
                        'sql' => 'SELECT * FROM mod_renewal',
                        'all' => true,
                    ],
                ],
                'trans' => false,
            ]);
            
            // 返回给模板
            $result['renew'] = $getData['list']['result'];

            // 遍历产品数组
            foreach ($result['renew'] as $key => $value) {
                try {
	                $clients = Capsule::table('tblclients')->where('id',$value['userid'])->first();
                    $result['renew'][$key]['name'] = $clients->firstname . ' ' . $clients->lastname;
					//print_r($result['renew']);die();
                }
                catch (Exception $e) {
                    // 销毁要返回的数组
                    unset($result['promo'][$key]);
                }
            }
				
			$result['assets'] = $ext->getSystemURL().'modules/addons/'.$vars['module'].'/templates/';
			$result['version'] = $vars['version'];
			$result['module'] = $vars['modulelink'];

            // 把 $result 放入模板需要输出的变量组中
            $result = $ext->getSmarty([
				'dir' => __DIR__ . '/templates/',
                'file' => 'home',
                'vars' => $result,
            ]);
            
        }
        catch (Exception $e) {
            // 如果报错则终止并输出错误
            die($e->getMessage());
        }
        finally {
            echo $result;
        }
    }
}