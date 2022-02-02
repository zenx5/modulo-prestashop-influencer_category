<?php
	
	if(!defined('_PS_VERSION_')) {
		exit;
	}	

	require 'classInfluencer.php';

	class influencer_category extends Module
	{
		public static $nameProfile = 'Influencer';
		public static $gain_min = 300;
		public $profileInfluencerId;
		
		public function __construct()
		{
			$this->name = 'influencer_category';
			$this->tab = 'SEO';
			$this->version = '1.0.0';
			$this->author = 'Octavio Martinez';
			$this->need_instance = 0;
			$this->ps_versions_compliancy = [
				'min' => '1.6',
				'max' => _PS_VERSION_
			];
			$this->bootstrap = true;
			$this->tabs = array(
			    array(
			        'name' => 'Influencer Menu', // One name for all langs
			        'class_name' => 'InfluencerView_Link',
			        'visible' => true,
			        'parent_class_name' => 'ShopParameters',
			));

			parent::__construct();

			$this->displayName = 'Influencer Category';
			$this->description = '...';
			$this->confirmUninstall = 'Are you sure you want to Uninstall?';
			if(!Configuration::get('MYMODULE_NAME')) {
				$this->warning = 'No name provided';
			}

			$this->profileInfluencerId = $this->existProfile(self::$nameProfile,false);
		}

		public function install()
		{
			if( $this->existProfile(self::$nameProfile) ){
				$this->createProfile(self::$nameProfile);
			}

			if(Shop::isFeatureActive()) {
				Shop::setContext(Shop::CONTEXT_ALL);
			}

			if($this->ConfigurationValues(null)){
				$this->installTab();
				return parent::install() && 
						$this->meHooks() && 
						$this->createTable();
			}
		}

		public function meHooks(){
			return true;
		}

		public function ConfigurationValues($values) {
			if( is_null($values) ) {
				return true;
			}
			else{
				$result = true;
				foreach ($values as $key => $value) {
					$result = $result && Configuration::updateValue($key, $value);
				}
				return $result;
			}
		}

		public function createTable(){
			return(Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'category_influencer` (`id_category` INT UNSIGNED NOT NULL, `id_influencer` INT UNSIGNED NOT NULL) ENGINE='._MYSQL_ENGINE_.' CHARACTER SET utf8 COLLATE utf8_general_ci;'));
		}

		public function createProfile($nameProfile, $id_langs = null){
			$this->profileInfluencerId = Influencer::create($id_langs);
			$langs = array();
			if(!$id_langs){
				$langs = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'lang` WHERE 1');
			}
			else{
				foreach ($id_langs as $id ) {
					$langs[] = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'lang` WHERE id_lang='.$id);
				}
			}

			$id_profile = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'profile_lang` WHERE name=\''.$nameProfile.'\'')['id_profile'];
			
			$this->profileInfluencerId = $id_profile;
			
			if($id_profile == ''){
				$band_add_profile = Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'profile` (id_profile) VALUES(\'\')');

				if($band_add_profile){
					$lastProfile = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'profile` WHERE 1 ORDER BY id_profile DESC');
		
					$this->profileInfluencerId = $lastProfile['id_profile'];

					foreach($langs as $lang){
						Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'profile_lang` (id_lang,id_profile,name) VALUES(\''.$lang['id_lang'].'\',\''.$lastProfile['id_profile'].'\',\''.$nameProfile.'\')');
					}
				}
			}			
		}

		public function uninstall()	{
			$this->uninstallTab();
			return 	parent::uninstall() && 
					$this->clearConfiguration() && 
					$this->unmeHooks() &&
					$this->deleteTable();
		}

		public function clearConfiguration() {
			return true;
		}
		public function unmeHooks(){
			return true;
		}

		public function deleteTable(){
			return(Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'category_influencer`'));
		}

		public function existProfile($nameProfile, $bool = true){
			$id_profile = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'profile_lang` WHERE name=\''.$nameProfile.'\'')['id_profile'];

			return $bool?$id_profile!='':$id_profile;
		}

		public function deleteProfile($nameProfile, $id_langs = null){
			$langs = array();
			if(!$id_langs){
				$langs = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'lang` WHERE 1');
			}
			else{
				foreach ($id_langs as $id ) {
					$langs[] = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'lang` WHERE id_lang='.$id);
				}
			}

			$id_profile = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'profile_lang` WHERE name=\''.$nameProfile.'\'')['id_profile'];
			if($id_profile != ''){
				Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'profile` WHERE id_profile='.$id_profile.'');

				foreach ($langs as $lang) {
					Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'profile_lang` WHERE (id_profile='.$id_profile.' and id_lang='.$lang['id_lang'].')');
				}
			}
		}
		/*
			PAGINA DE CONFIGURACION
		*/	
		public function getContent(){
			$output = null;

			if(Tools::isSubmit('submitCampaing')) {
				$idUser = (int)Tools::getValue('Influencer');
				$categoryBox = Tools::getValue('categoryBox');
				$rate_gain = Tools::getValue('rate_gain_'.$idUser);
				Configuration::updateValue('rate_gain_'.$idUser, $rate_gain);
				$result = true;
				if( count($categoryBox) == 1 ){
					foreach (Influencer::getCategory() as $cat) {
						if( in_array($cat, $categoryBox) ) {
							if(!Influencer::hasCategory($idUser, $cat)){
								$result = $result && (bool) Influencer::addCategory($idUser, $cat);
							}
						}
						else{
							Influencer::deleteCategory($idUser, $cat);
						}
					}					
				}
				elseif( count($categoryBox) == 0 ){
					Influencer::deleteCategory($idUser);
				}
				else{
					$result = false;
				}
				if($result){
					$output .= $this->displayConfirmation('Se actualizo la Configuracion del Modulo');
				}
				else{
					$output .= $this->displayError('Algunos datos no fueron actualizados. Recuerde no asignar la misma Categoria a mas de un Influencer');
				}
			}
			elseif(Tools::isSubmit('submitDescripcion')){
				$idUser = Tools::getValue('Influencer');
				Influencer::categoryDetails($idUser, 'description', Tools::getValue('DescripcionCategoria'));
				$output .= $this->displayConfirmation('Se actualizo la Configuracion del Modulo');
			}
			return $output.$this->displayForm();
		}

		public function displayForm() {			
			global $cookie;
			$defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');
			$user = new Employee($cookie->id_employee);
			//die(json_encode(Tools::getValue('page')));
			if($user->id_profile == $this->profileInfluencerId){
				if( Tools::getValue('page')==1 ){
					return $this->renderFormInfluencer_page1($user);
				}
				else{
					return $this->renderFormInfluencer_page2($user);					
				}
			}	
			elseif($user->id_profile == '1'){
				$id_employee = (int)Tools::getValue('id_employee');
				$options = (int)Tools::getValue('options');
				if( Tools::getIsset('updateinfluencer_category') ){
					return $this->renderFormAdminAssign($id_employee);
				}
				elseif( Tools::getIsset('viewinfluencer_category') ){
					if( Tools::getValue('page')==1 ){
						return $this->renderFormInfluencer_page1( new Employee($id_employee) );
					}
					else{
						return $this->renderFormInfluencer_page2( new Employee($id_employee) );
					}
				}
				else{
					return $this->renderFormAdmin();				
				}
			}
		}

		public function liquidar($Influencer){
			
			$gain = Influencer::getGain($Influencer->id,Configuration::get('rate_gain_'.$Influencer->id),Configuration::get('last_liq_'.$Influencer->id));
			if($gain >= self::$gain_min ){
				$content = '
				<table class="table">
					<tr>	
						<th>Usuario</th>
						<td>'.$Influencer->firstname.' '.$Influencer->lastname.'</td>
					</tr>
					<tr>	
						<th>Ganancia</th>
						<td>'.Configuration::get('rate_gain_'.$Influencer->id).' %</td>
					</tr>
					<tr>	
						<th>Monto de la Solicitud</th>					
						<td>'.$gain.' Euros</td>
					</tr>
				</table>';

				Mail::Send(
					(int)(Configuration::get('PS_LANG_DEFAULT')), // defaut language id
					'contact', // email template file to be use
					'SOLICITUD DE LIQUIDACION', // email subject
					array(
						'{email}' => $Influencer->email, // sender email address
						'{message}' => $content, // email content
						' {order_name}' => '',
						' {attached_file}' => '',
					),
					Configuration::get('PS_SHOP_EMAIL'), // receiver email address
					NULL, //receiver name
					NULL, //from email address
					NULL  //from name
		        );
				Configuration::updateValue('last_liq_'.$Influencer->id, date('Y-m-d'));
			}
		}

		public function renderFormInfluencer_page1($Influencer){
			global $cookie;
			$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
			$currentUser = new Employee($cookie->id_employee);
			$aux = Influencer::getProducts($Influencer->id);
			if(count($aux)>0) {
				$nameCategoty = $aux[0]['category_name'];
			}
			else{
				return $this->displayError('Ninguna Categoria Asignada');
			}
			
			if(Tools::getIsset('liquidar')){
				$this->liquidar($Influencer);
			}


			if ($_GET['page']==2){
	            return $this->renderFormInfluencer_page2($Influencer);
	        }
			$descripcion = Influencer::categoryDetails($Influencer->id, 'description');
			
			$gain = Influencer::getGain($Influencer->id,Configuration::get('rate_gain_'.$Influencer->id),Configuration::get('last_liq_'.$Influencer->id));

			$inputs = array(
				array(
					'type' => 'text',
					'label' => 'Nombre de la Categoria',
					'name' => 'Categoria',
					'disabled' => true,
				),
				array(
					'type' => 'text',
					'label' => 'Descripcion de la Categoria',
					'name' => 'DescripcionCategoria',
					'desc' => 'Aqui ira la descripcion de la categoria',
				),
				array(
					'type' => 'text',
					'label' => 'Comisiones Acumuladas',
					'name' => 'gain',
					'desc' => 'Solo podra liquidar sus comisiones cuando superen los '.self::$gain_min.' Euros',
					'disabled' => true,
				),
				array(
					'type' => 'html',
					'html_content' => '<a class="btn btn-info" href="'.$this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name.'&page=2&id_employee=2&token='.Tools::getAdminTokenLite('AdminModules').'">Ver Historial</a>',
					'label' => '', 
					'name' => 'VerComisiones',
					'desc' => $this->trans('Ver el Historial de Comisiones'),
				),
				array(
					'type' => 'hidden',
					'name' => 'Influencer',
				),
            );

			if( $gain > self::$gain_min ){
				$inputs[] = array(
					'type' => 'html',
					'html_content' => '<a class="btn btn-success" href="'.$this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name.'&page=1&liquidar&id_employee=2&token='.Tools::getAdminTokenLite('AdminModules').'">Liquidar</a> (Ultimo liquidacion: <b>'.Configuration::get('last_liq_'.$Influencer->id).'</b>)',
					'label' => '', 
					'name' => 'LiquidarComisiones',
					'desc' => $this->trans('Liquidar las comisiones Acumuladas'),
				);
			}

			$fields_form = array(
				'form' => array(
		            'legend' => array(
						'title' => $this->trans('Influencer: '.$Influencer->firstname.' '.$Influencer->lastname, array(), 'Modules.InfluencerCampaing.Admin'),
						'icon' => 'icon-cogs'
		            ),
		            'input' => $inputs,
		            'submit' => array(
		                'name' => 'submitDescripcion',
		                'title' => $this->trans('Save', array(), 'Admin.Actions')
		            ),
		        ),
        	);


			$helper = new HelperForm();
	        $helper->module = $this;
	        $helper->table = $this->name;
	        $helper->token = Tools::getAdminTokenLite('AdminModules');
	        $helper->currentIndex = $this->getModuleConfigurationPageLink().'&viewinfluencer_category&page=1';
	        
	        $helper->default_form_language = $lang->id;
	        
	        $helper->title = $this->displayName;
	        $helper->show_toolbar = false;
	        $helper->toolbar_scroll = false;
	        
	        $helper->submit_action = 'submitDescripcion';
	        
			$helper->identifier = $this->identifier;

			$helper->tpl_vars = array(
	            'fields_value' => array(
	            	'Categoria' => $nameCategoty,
	            	'DescripcionCategoria' => $descripcion,
	            	'Influencer' => $Influencer->id,
	            	'gain' => $gain
	            ),         
	        );
	        return $helper->generateForm(array($fields_form));
		}

		public function renderFormInfluencer_page2($user){
			$filter = Tools::getValue('influencer_categoryFilter_date_beauty', '');
			if (Tools::isSubmit('submitResetinfluencer_category')){
				$filter = '';				
	            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name.'&id_employee='.$user->id.'&page=2&token='.Tools::getAdminTokenLite('AdminModules'));
	        }
			$orderby = Tools::getValue('influencer_categoryOrderby', 'id_product');
			$orderway = Tools::getValue('influencer_categoryOrderway', 'ASC');
			if($orderby == 'date_beauty') $orderby = 'delivery_date';
			$products = Influencer::getProducts($user->id, $orderby, $orderway , $filter );
			
			$this->fields_list = array(
		      'id_product' => array(
		          'title' => $this->trans('ID', array(), 'Admin.Global'),
		          'search' => false,
		      ),
		      'name' => array(
		          'title' => $this->trans('Name', array(), 'Admin.Global'),
		          'search' => false,
		      ),
		      'category_name' => array(
		          'title' => $this->trans('Category', array(), 'Admin.Global'),
		          'search' => false,
		      ),
		      'price' => array(
		          'title' => $this->trans('Price', array(), 'Admin.Global'),
		          'search' => false,
		      ),
		      'date_beauty' => array(
		          'title' => $this->trans('Date', array(), 'Admin.Global'),
		          'search' => true,
		      ),
		      'quantity' => array(
		          'title' => $this->trans('Vendidos', array(), 'Admin.Global'),
		          'search' => false,
		      ),
		      'sub' => array(
		          'title' => $this->trans('Total Vendidos', array(), 'Admin.Global'),
		          'search' => false,
		      ),
		    );	

		    $name = $user->firstname.' '.$user->lastname;

		    $helper_list = new HelperList();
		    $helper_list->module = $this;
		    $helper_list->title = $this->getTranslator()->trans('Influencer: '.$name, array(), 'Modules.Customerweather.Admin');
		    $helper_list->shopLinkType = '';
		    $helper_list->no_link = true;
		    $helper_list->show_toolbar = true;
		    $helper_list->toolbar_btn = array(
		    	'back' => array(
		    		'href' => $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&page=1',
		    		'desc' => 'Volver'
		    	),
		    );
		    $helper_list->simple_header = false;
		    $helper_list->identifier = 'id_product';
		    $helper_list->table = $this->name;
		    $helper_list->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name.'&id_employee='.$user->id.'&page=2';
		    $helper_list->token = Tools::getAdminTokenLite('AdminModules');
		    $helper_list->actions = array();
		    $helper_list->listTotal = count($products);
		    return $helper_list->generateList($products, $this->fields_list);
		}
		
		public function BtnView($id){
			return '<span class="btn-group-action">
                <span class="btn-group">
                    <a class="btn btn-default" href="'.self::$currentIndex.'&token='.$this->token.'&view'.$this->table.'&id='.$id.'"><i class="icon-search-plus"></i>&nbsp;'.$this->l('View').'
                    </a>
                </span>
            </span>';
		}

		public function renderFormAdmin(){
			$employeeClass = new Employee();
    		$influencers = $employeeClass->getEmployeesByProfile($this->profileInfluencerId);
    		foreach ($influencers as $key => $influencer) {
    			$influencer['id_temp'] = $influencer['id_employee'];
    			//$id_category = count(Influencer::getCategory($influencer['id_employee']))?;
    			//$aux = Influencer::getProducts($influencer['id_employee']);
    			$influencers[$key]['description'] = Influencer::categoryDetails($influencer['id_employee'],'description');
    			$influencers[$key]['category'] = Influencer::categoryDetails($influencer['id_employee'],'name');
    			
    			/*if(count($aux)>0){
    				$influencers[$key]['category'] = $aux[0]['category_name'];
    			}
    			else{
    				$influencers[$key]['category'] = $aux['category_name'];
    			}*/	
    		}
    		

			$this->fields_list = array(
				'id_employee' => array(
		          	'title' => $this->trans('ID', array(), 'Admin.Global'),
		          	'search' => false,
		      	),
		      	'lastname' => array(
		          	'title' => $this->trans('Lastname', array(), 'Admin.Global'),
		          	'search' => false,
		      	),
		      	'firstname' => array(
		          	'title' => $this->trans('Firstname', array(), 'Admin.Global'),
		         	'search' => false,
		      	),
		      	'email' => array(
					'title' => $this->trans('Email', array(), 'Admin.Global'),
		          	'search' => false,
		      	),
		      	'category' => array(
					'title' => $this->trans('Category', array(), 'Admin.Global'),
		          	'search' => false,
		      	),
		      	'description' => array(
					'title' => $this->trans('Description', array(), 'Admin.Global'),
		          	'search' => false,
		      	),
		    );

		    $helper_list = new HelperList();
		    $helper_list->module = $this;
		    $helper_list->title = $this->getTranslator()->trans('Influencer users', array(), 'Modules.Customerweather.Admin');
		    $helper_list->shopLinkType = '';
		    $helper_list->no_link = true;
		    $helper_list->show_toolbar = true;
		    $helper_list->toolbar_btn = array(
		    	'back' => array(
		    		'href' => $this->context->link->getAdminLink('AdminDashboard', false).'&token='.Tools::getAdminTokenLite('AdminDashboard'),
		    		'desc' => 'Salir'
		    	)
		    );
		    $helper_list->simple_header = false;
		    $helper_list->identifier = 'id_employee';
		    $helper_list->table = $this->name;
		    $helper_list->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;
		    $helper_list->token = Tools::getAdminTokenLite('AdminModules');
		    $helper_list->actions = array('edit','view');

		    $helper_list->listTotal = count($influencers);
    		
		    return $helper_list->generateList($influencers, $this->fields_list);
		}

		

		public function renderFormAdminAssign($id_employee){
			$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
			//OBTENEMOS TIENDA

			$id_shop = (int) Tools::getValue('id_shop');
			$shop = new Shop($id_shop);
			//OBTENEMOS CATEGORIAS
			$selected_cat = Shop::getCategories($id_shop);
			//SI ESTA VACIO ENTONCES SE OBTIENE LA CATEGORIA RAIZ
      		if (empty($selected_cat)) {
          		// get first category root and preselect all these children
          		$root_categories = Category::getRootCategories();
          		$root_category = new Category($root_categories[0]['id_category']);
          		$children = $root_category->getAllChildren($this->context->language->id);
          		$selected_cat[] = $root_categories[0]['id_category'];
          		foreach ($children as $child) {
              		$selected_cat[] = $child->id;
          		}
      		}

        	$Influencer =  new Employee($id_employee);

      		$categoryBox = Influencer::getCategory($id_employee);
      		
        	$data_hidden = array(
        		'type' => 'hidden',
        		'name' => 'Influencer'
			);

			$input_rate_gain = array(
				'type' => 'html',
				'html_content' => '<input type="number" min="0" max="100" step="0.1" name="rate_gain_'.$id_employee.'" value="'.Configuration::get('rate_gain_'.$id_employee,0).'"/>',
				'label' => $this->trans('Ganancia (%)'), 
				'name' => 'rate_gain_'.$id_employee,
				'value' => Configuration::get('rate_gain_'.$id_employee,0),
				'desc' => $this->trans('Selecciona la Ganancia del Usuario'),
			);

      		//ARBOL DE CATEGORIAS
        	foreach (Profile::getProfiles($lang->id) as $key => $value) {
        		if($value['name'] == self::$nameProfile){
        			$idProfile = $value['id_profile'];
        		}
        	}

	        $field_category_tree = array(
	            'type' => 'categories',
	            'label' => $this->trans('Categorias'),
	            'name' => 'categoryBox',
	            'desc' => $this->trans('Marca la categoria que asignara al usuario'),
	            'tree' => array(
	                'use_search' => false,
	                'id' => 'categoryBox',
	                'use_checkbox' => true,
	                //'selected_categories' => $selected_cat,
	                'selected_categories' => $categoryBox,
	            ),
	            //retro compat 1.5 for category tree
	            'values' => array(
	                'trads' => array(
	                    'Root' => Category::getTopCategory(),
	                    'selected' => $this->l('Selected'),
	                    'Collapse All' => $this->l('Collapse All'),
	                    'Expand All' => $this->l('Expand All'),
	                    'Check All' => $this->l('Check All'),
	                    'Uncheck All' => $this->l('Uncheck All'),
	                ),
	                'selected_cat' => $selected_cat,
	                'input_name' => 'categoryBox[]',
	                'use_radio' => false,
	                'use_search' => false,
	                'disabled_categories' => array(),
	                'top_category' => Category::getTopCategory(),
	                'use_context' => true,
	        	),
	        );

			$fields_form = array(
				'form' => array(
		            'legend' => array(
						'title' => $this->trans('Influencer: '.$Influencer->firstname.' '.$Influencer->lastname, array(), 'Modules.InfluencerCampaing.Admin'),
						'icon' => 'icon-cogs'
		            ),
		            'input' => array(
		            	$input_rate_gain,
						$field_category_tree,
						$data_hidden
		            ),
		            'submit' => array(
		                'name' => 'submitCampaing',
		                'title' => $this->trans('Save', array(), 'Admin.Actions')
		            ),
		        ),
        	);

        	$fieldsValue = array(
				'input' =>	$input_rate_gain,
        		'Influencer' => $id_employee
        	);

	        $helper = new HelperForm();
	        $helper->module = $this;
	        $helper->table = $this->name;
	        $helper->token = Tools::getAdminTokenLite('AdminModules');
	        $helper->currentIndex = $this->getModuleConfigurationPageLink();
	        
	        $helper->default_form_language = $lang->id;
	        
	        $helper->title = $this->displayName;
	        $helper->show_toolbar = false;
	        $helper->toolbar_scroll = false;
	        
	        $helper->submit_action = 'submitCampaing';
	        

			$helper->identifier = $this->identifier;


	        $helper->tpl_vars = array(
	            'fields_value' => $fieldsValue,
	            'languages' => $this->context->controller->getLanguages(),
	            'id_language' => $this->context->language->id,            
	        );

	        return $helper->generateForm(array($fields_form));
    }

    protected function getModuleConfigurationPageLink(){
        $parsedUrl = parse_url($this->context->link->getAdminLink('AdminModules', false));

        $urlParams = http_build_query([
			'configure' => $this->name,
			'tab_module' => $this->tab,
			'module_name' => $this->name,
		]);

        if (!empty($parsedUrl['query'])) {
            $parsedUrl['query'] .= "&$urlParams";
        }
        else{
            $parsedUrl['query'] = $urlParams;
        }
    	
    	return http_build_url($parsedUrl);
	}
	
	
	private function installTab(){
		//return true;
		$tab = new Tab();
	    $tab->class_name = 'InfluencerViewLink';
	    $tab->id_parent = Tab::getIdFromClassName('AdminParentModulesSf');
		$tab->module = $this->name;
	    $languages = Language::getLanguages();
	    foreach ($languages as $language)
	      $tab->name[$language['id_lang']] = 'Influencer Menu';
	    return $tab->add();
	}

	private function uninstallTab(){
		//return true;
		$tab_id = (int)Tab::getIdFromClassName('InfluencerViewLink');
		if(!$tab_id){
			return true;
		}
		$tab = new Tab($tab_id);
		return $tab->delete();
	}
}

