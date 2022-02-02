<?php
/*
	Febrero de 2021
	Metodos:
		public static function createProfile($nameProfile, $id_langs = null)
		public static function deleteProfile($nameProfile, $id_langs = null)
		public static function existProfile($nameProfile, $bool = true)

*/


class ProfileCustom {
		public static function createProfile($nameProfile, $id_langs = null){
			$profileid = null;

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
			
			$profileid = $id_profile;
			
			if($id_profile == ''){
				$band_add_profile = Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'profile` (id_profile) VALUES(\'\')');

				if($band_add_profile){
					$lastProfile = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'profile` WHERE 1 ORDER BY id_profile DESC');
		
					$profileid = $lastProfile['id_profile'];

					foreach($langs as $lang){
						Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'profile_lang` (id_lang,id_profile,name) VALUES(\''.$lang['id_lang'].'\',\''.$lastProfile['id_profile'].'\',\''.$nameProfile.'\')');
					}
				}
			}

			return $profileid;
		}


		public static function deleteProfile($nameProfile, $id_langs = null){
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

		public static function existProfile($nameProfile, $bool = true){
			$id_profile = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'profile_lang` WHERE name=\''.$nameProfile.'\'')['id_profile'];

			return $bool?$id_profile!='':$id_profile;
		}
	}