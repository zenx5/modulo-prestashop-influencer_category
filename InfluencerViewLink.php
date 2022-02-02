<?php

class InfluencerViewLink extends ModuleAdminController {

    public function __construct()
    {
        parent::__construct();
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure=influencer_category'.'&page=1');
    }
}