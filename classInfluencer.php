<?php
/*
	Febrero de 2021
	Metodos:
		public static function create($id_langs = null)
		public static function delete($id_langs = null)
		public static function exist($id, $bool = true)
		public static function getProducts($id)
		public static function getCategoryDetails($id = null)
		public static function getCategory($id = null)
		public static function hasCategory($id, $id_category)
		public static function freeCategory($id_category)
		public static function addCategory($id, $id_category)
		public static function deleteCategory($id, $id_category)
*/

	require 'classProfileCustom.php';

	class Influencer extends ProfileCustom{

		public static function create($id_langs = null){
			return self::createProfile('Influencer', $id_langs);
		}

		public static function delete($id_langs = null){
			return self::deleteProfile('Influencer', $id_langs);
		}

		public static function exist($id, $bool = true){
			if( self::existProfile('Influencer') ) {
				$Influencer = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'employee` WHERE id_employee=\''.$id.'\'');

				return $bool?!!($Influencer):$Influencer;
			}
			return false;
		}

		public static function getGain($id, $gain, $date = '', $debug = false){
			$lang = (int)Configuration::get('PS_LANG_DEFAULT');
			$categories = self::getCategory($id);
			$result = 0;
			foreach ($categories as $category) {
				$sql = 'SELECT 
					o.delivery_number as quantity,
					p.price, 
					l.id_product, 
					l.name, 
					c.id_category, 
					cl.name as category_name, 
					o.invoice_date,
					o.delivery_date,
					o.date_upd 
				FROM '._DB_PREFIX_.'product AS p 
				LEFT JOIN  '._DB_PREFIX_.'product_lang AS l 
					ON p.id_product = l.id_product 
				LEFT JOIN '._DB_PREFIX_.'category_product AS c 
					ON p.id_product = c.id_product 
				LEFT JOIN '._DB_PREFIX_.'category_lang AS cl 
					ON c.id_category = cl.id_category 
				LEFT JOIN '._DB_PREFIX_.'product_sale AS ps 
					ON p.id_product = ps.id_product 
				LEFT JOIN '._DB_PREFIX_.'order_detail AS od 
					ON p.id_product = od.product_id 
				LEFT JOIN '._DB_PREFIX_.'orders AS o 
					ON od.id_order = o.id_order				
				WHERE 
					l.id_lang = '.$lang.' AND 
					l.name<>\'\' AND 
					c.id_category='.$category.' AND
					o.delivery_date>\''.$date.'\' 
				GROUP BY o.id_order';
				if($debug){
					return $sql;
				}
				$aux = Db::getInstance()->executeS($sql);
				foreach ($aux as $item) {
					if( ($item['quantity']>0) ){
							$result += $item['quantity']*$item['price']*$gain/100;
						}
				}
			}
			return $result;
		}

		public static function getProducts($id, $orderby = 'id_product', $orderway = 'ASC' , $filter = ''){
			$lang = (int)Configuration::get('PS_LANG_DEFAULT');
			$categories = self::getCategory($id);
			$result = [];
			
			foreach ($categories as $category) {
				$sql = 'SELECT 
					od.product_quantity as quantity,
					p.price, 
					l.id_product, 
					l.name, 
					c.id_category, 
					cl.name as category_name, 
					o.invoice_date,
					o.delivery_date,
					o.date_upd 
				FROM '._DB_PREFIX_.'product AS p 
				LEFT JOIN  '._DB_PREFIX_.'product_lang AS l 
					ON p.id_product = l.id_product 
				LEFT JOIN '._DB_PREFIX_.'category_product AS c 
					ON p.id_product = c.id_product 
				LEFT JOIN '._DB_PREFIX_.'category_lang AS cl 
					ON c.id_category = cl.id_category 
				LEFT JOIN '._DB_PREFIX_.'product_sale AS ps 
					ON p.id_product = ps.id_product 
				LEFT JOIN '._DB_PREFIX_.'order_detail AS od 
					ON p.id_product = od.product_id 
				LEFT JOIN '._DB_PREFIX_.'orders AS o 
					ON od.id_order = o.id_order				
				WHERE 
					l.id_lang = '.$lang.' AND 
					l.name<>\'\' AND 
					c.id_category='.$category.'
				GROUP BY o.id_order 
				ORDER BY '.$orderby.' '.$orderway;
				$aux = Db::getInstance()->executeS($sql);
				$total = 0;
				foreach ($aux as $key => $item) {
					$date = $item['delivery_date'];
					$date = explode(' ', $date)[0];
					//$item['date_beauty'] =  strftime('%A, %d de %B de %G', strtotime('2020/11/10'));
					$item['day'] = explode('-', $date)[2];
					$item['month'] = explode('-', $date)[1]; 
					$item['year'] = explode('-', $date)[0];
					$item['month_name'] = array(
					        1 => 'Enero',
					        2 => 'Febrero',
					        3 => 'Marzo',
					        4 => 'Abril',
					        5 => 'Mayo',
					        6 => 'junio',
					        7 => 'Julio',
					        8 => 'Agosto',
					        9 => 'Septiembre',
					        10 => 'Octubre',
					        11 => 'Noviembre',
					        12 => 'Diciembre'
					    )[ intval($item['month'])];
					$item['day_week'] = array(
					        7 => 'Domingo',
					        1 => 'Lunes',
					        2 => 'Martes',
					        3 => 'Miercoles',
					        4 => 'Jueves',
					        5 => 'Viernes',
					        6 => 'Sábado'
					    )[date('N', strtotime($date))];
					
					$item['date_beauty'] = $item['day_week'].', '.$item['day'].' de '.$item['month_name'].' de '.$item['year'];
					$item['sub'] = $item['quantity']*$item['price'];
					//$item['date_beauty'] = $date;
					
					if($filter == ''){
						$total += $item['sub'];
						if( ($item['quantity']>0) && ($date != '') ){
							$result[] = $item;
						}
					}
					else{
						$filter = str_replace( ['á','é','í','ó','ú'],['a','e','i','o','u'], strtolower($filter) );
						$str_date = str_replace( ['á','é','í','ó','ú'],['a','e','i','o','u'], strtolower($item['date_beauty']) );
						if(json_encode(strpos($str_date, $filter)) != 'false'){
							$total += $item['sub'];
							if( ($item['quantity']>0) && ($date != '') ){
								$result[] = $item;
							}
						}
					}

				}
				
				if(count($result)>0){
					$result[] = array(
						'id_product' => '',
						'name' => '',
						'category_name' => '',
						'price' => '',
						'quantity' => 'TOTAL : ',
						'sub' => $total
					);
					$result[] = array(
						'id_product' => '',
						'name' => '',
						'category_name' => '',
						'price' => '',
						'quantity' => 'Comision por Cobrar ('.intval(Configuration::get('rate_gain_'.$id)).'%) : ',
						'sub' => self::getGain($id, intval(Configuration::get('rate_gain_'.$id)), Configuration::get('last_liq_'.$id))
					);					
					$result[] = array(
						'id_product' => '',
						'name' => '',
						'category_name' => '',
						'price' => '',
						'quantity' => 'Ultimo cobro el: '.Configuration::get('last_liq_'.$id),
						'sub' => ''
					);					
				}
			}
		
			return $result;
		}

		public static function categoryDetails($id, $name , $value = null){
			//$lang = (int)Configuration::get('PS_LANG_DEFAULT');
			$id_category = self::getCategory($id)[0];
			if($value){
				return Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'category_lang` SET `'.$name.'`=\''.$value.'\'  WHERE id_category='.$id_category);
			}
			else{
				return Db::getInstance()->executeS('SELECT '.$name.' FROM `'._DB_PREFIX_.'category_lang` WHERE id_category='.$id_category)[0][$name];
			}
			return null;
		}

		public static function getCategory($id = null){
			if( self::existProfile('Influencer') ) {
				$result = [];
				if($id){
					$idCategory = Db::getInstance()->executeS('SELECT id_category FROM `'._DB_PREFIX_.'category_influencer` WHERE id_influencer='.$id);
				}
				else{
					$idCategory = Db::getInstance()->executeS('SELECT id_category FROM `'._DB_PREFIX_.'category` WHERE 1');	
				}

				foreach ( $idCategory as $cat ) {
					if( Category::categoryExists($cat) ){
						$result[] = $cat['id_category'];
					}
				}
				return $result;
			}
			return false;
		}

		public static function hasCategory($id, $id_category){
			return (count( Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'category_influencer` WHERE ( id_influencer='.$id.' and id_category='.$id_category.' ) ') ) != 0);
		}		

		public static function freeCategory($id_category){
			return (count( Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'category_influencer` WHERE ( id_category='.$id_category.' ) ') ) == 0);		
		}

		public static function addCategory($id, $id_category){
			//if( self::freeCategory($id_category) ){
				if( self::existProfile('Influencer') ) {
					if( self::hasCategory($id, $id_category) ){
						return Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'category_influencer` SET id_category='.$id_category.' WHERE id_influencer='.$id);
					}
					else{
						return Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'category_influencer` (id_category,id_influencer) VALUES(\''.$id_category.'\',\''.$id.'\' )');
					}
					return $update;
				}
			//}
			return false;
		}

		public static function deleteCategory($id, $id_category = null){
			if($id_category == null){
				return Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'category_influencer` WHERE ( id_influencer='.$id.' )');
			}
			if(self::hasCategory($id, $id_category)){
				if( self::existProfile('Influencer') ) {
					return Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'category_influencer` WHERE ( id_category='.$id_category.' and id_influencer='.$id.' )');
				}
			}
			return false;
		}


	}