<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Template extends MX_Controller {
	protected $asset_url;

	protected $pageTitle;
	protected $pageDescription;
	protected $contentView;
	protected $contentViewData;
	protected $metaData;

	function __construct(){
		$this->asset_url = $this->config->item('assets_url');
		$this->load->library('Assets', $this->config);
	}
	function adminTemplate(){
		$data['page_css'] = $this->assets->css;
		$data['page_js'] = $this->assets->js;

		$this->load->model('Auth/auth_m');
		$user_details = $this->auth_m->findUserByIdentifier('uuid', $this->session->userdata('uuid'));

		$data['javascript_file'] = $this->assets->javascript_file;
		$data['javascript_data'] = $this->assets->javascript_data;
		$data['user_details'] = $user_details;
		$data['menu'] = $this->createSideBar();
		$data['pagetitle'] = $this->pageTitle;
		$data['pagedescription'] = $this->pageDescription;
		$data['partial'] = $this->contentView;
		$data['partialData'] = $this->contentViewData;

		$this->load->view('Template/backend_template_v', $data);
	}


	function authTemplate(){
		$data['page_css'] = $this->assets->css;
		$data['page_js'] = $this->assets->js;


		$data['javascript_file'] = $this->assets->javascript_file;
		$data['javascript_data'] = $this->assets->javascript_data;
		
		$data['pagetitle'] = $this->pageTitle;
		$data['pagedescription'] = $this->pageDescription;
		$data['partial'] = $this->contentView;
		$data['partialData'] = $this->contentViewData;

		$this->load->view('Template/auth_template_v', $data);
	}

	function frontEndTemplate(){
		$data['metadata'] = $this->metaData;

		$data['page_css'] = $this->assets->css;
		$data['page_js'] = $this->assets->js;

		$data['pagetitle'] = $this->pageTitle;
		$data['pagedescription'] = $this->pageDescription;

		$data['partial'] = $this->contentView;
		$data['partialData'] = $this->contentViewData;

		$data['javascript_file'] = $this->assets->javascript_file;
		$data['javascript_data'] = $this->assets->javascript_data;
		$this->load->view('Template/frontend_template_v', $data);
	}

	function createSideBar($selected = null){

		$class = $this->router->class;
		$menus = [];
		$menu_list = "";
		// ,
		// 	'orders'	=>	[
		// 		'icon'	=>	'fa fa-shopping-cart',
		// 		'text'	=>	'Orders',
		// 		'link'	=>	'Backend/Orders'
		// 	]
		$menus = [
			'participants' => [
				'icon'	=>	'icon-people',
				'text'	=>	'Participants',
				'link'	=>	'Particpants/list',
				'users'	=>	['participant']
			],
			'users'		=>	[
				'icon'	=>	'icon-user-follow',
				'text'	=>	'User Accounts',
				'link'	=>	'Accounts/list',
				'users'	=>	['participant']
			]
		];

		if (count($menus) > 0) {
			foreach ($menus as $key => $item) {
				if(in_array($this->session->userdata('type'), $item['users'])){
					$active = "";
					if ($key == strtolower($class)) {
						$active = "active";
					}
					$menu_list .= "<li class = 'nav-item'>
						<a class = 'nav-link' href = '".base_url($item['link'])."'><i class = '{$item['icon']}'></i> {$item['text']}</a>
					</li>";
				}
			}
		}

		return $menu_list;
	}

	function setPageTitle($page_title = ""){
		$this->pageTitle = $page_title;

		return $this;
	}

	function setPageDescription($pageDescription){
		$this->pageDescription = $pageDescription;

		return $this;
	}

	function setPartial($view, $data = []){
		$this->contentView = $view;
		$this->contentViewData = $data;

		return $this;
	}

	function setMetaData($key, $value){
		$metadata_string = "";

		$metadata_string = "<meta property = '{$key}' content = '{$value}'>\r\n";

		$this->metaData .= $metadata_string;

		return $this;
	}
}

/* End of file Template.php */
/* Location: ./application/modules/Template/controllers/Template.php */