<?php
/**
 *
 * @author    Lunar <support@lunar.app>
 * @copyright Copyright (c) permanent, Lunar
 * @link      https://lunar.app
 *
 */

if ( ! defined( '_TB_VERSION_' ) ) {
	exit;
}

require_once __DIR__.'/vendor/autoload.php';


use Lunar\Lunar as ApiClient;
use Lunar\Payment\classes\AdminOrderHelper;
use Lunar\Payment\methods\LunarCardMethod;
use Lunar\Payment\methods\LunarMobilePayMethod;

/**
 * 
 */
class LunarPayment extends PaymentModule 
{
	public LunarCardMethod $cardMethod;
	public LunarMobilePayMethod $mobilePayMethod;
	private AdminOrderHelper $adminOrderHelper;

	/**
	 * 
	 */
	public function __construct() {
		$this->name      = 'lunarpayment';
		$this->tab       = 'payments_gateways';
		$this->version   = json_decode(file_get_contents(__DIR__ . '/composer.json'))->version;
		$this->author    = 'Lunar';
		$this->bootstrap = true;

		$this->currencies      = true;
		$this->currencies_mode = 'checkbox';

		$this->displayName      = 'Lunar';
		$this->description      = $this->l( 'Receive payments with Lunar' );
		$this->confirmUninstall = $this->l( 'Are you sure about removing Lunar?' );

		parent::__construct();

		$this->adminOrderHelper = new AdminOrderHelper($this);
		$this->cardMethod = new LunarCardMethod($this);
		$this->mobilePayMethod = new LunarMobilePayMethod($this);
	}

	/**
	 * 
	 */
	public function install() 
	{
		return ( 
			parent::install()
			&& $this->registerHook( 'paymentOptions' )
			&& $this->registerHook( 'paymentReturn' )
			&& $this->registerHook( 'DisplayAdminOrder' )
			&& $this->registerHook( 'BackOfficeHeader' )
			&& $this->registerHook( 'actionOrderStatusPostUpdate' )
			&& $this->registerHook( 'actionOrderSlipAdd' )
			&& $this->createDbTables()
			&& $this->cardMethod->install()
			&& $this->mobilePayMethod->install()
		);
	}

	public function createDbTables() {
		return (
			Db::getInstance()->execute( 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . "lunar_transactions` (
                `id`				INT(11) NOT NULL AUTO_INCREMENT,
                `lunar_tid`			VARCHAR(255) NOT NULL,
                `order_id`			INT(11) NOT NULL,
                `payed_at`			DATETIME NOT NULL,
                `payed_amount`		DECIMAL(20,6) NOT NULL,
                `refunded_amount`	DECIMAL(20,6) NOT NULL,
                `captured`		    VARCHAR(255) NOT NULL,
                `method`		    VARCHAR(50) NOT NULL,
                PRIMARY KEY			(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;" )

			&& $this->cardMethod->createSeedLogosTable()
		);
	}

	public function uninstall()
	{
		return (
			parent::uninstall()
			&& $this->cardMethod->uninstall()
			&& $this->mobilePayMethod->uninstall()
			&& Db::getInstance()->execute( 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . "lunar_transactions`" )
			&& Db::getInstance()->execute( 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . "lunar_logos`" )
		);
	}

	/**
	 * 
	 */
	public function getPaymentMethodByName(string $methodName)
	{
        switch($methodName) {
            case LunarCardMethod::METHOD_NAME:
                return $this->cardMethod;
                break;
            case LunarMobilePayMethod::METHOD_NAME:
                return $this->mobilePayMethod;
                break;
            default:
                return null;  
        }
	}

	public function getPSV() {
		return Tools::substr( _TB_VERSION_, 0, 5 );
	}


	/**
	 * 
	 */
	public function getContent() {
		if ( Tools::isSubmit( 'submitLunar' ) ) {
			if (
				$this->cardMethod->updateConfiguration()
				&& $this->mobilePayMethod->updateConfiguration()
			) {
				$this->context->controller->confirmations[] = $this->l( 'Settings were saved successfully' );
			}
		}
	
		// $this->context->controller->addJS( $this->_path . 'views/js/backoffice.js' );
		
		//Get configuration form
		// return $this->renderForm(); // . $this->getModalForAddMoreLogo(); // disabled for the moment because of an error
		return $this->renderForm() . $this->renderScript();
	}

	public function renderForm()
	{
		$card_fields_form = $this->cardMethod->getFormFields();
		$mobilepay_fields_form = $this->mobilePayMethod->getFormFields();

		// we want only inputs to be merged
		$form_fields['form']['legend'] = $card_fields_form['form']['legend'];
		$form_fields['form']['tabs'] = [
			'lunar_card' => $this->l('Card Configuration'),
			'lunar_mobilepay' => $this->l('Mobile Pay Configuration'),
		];
		$form_fields['form']['input'] = array_merge_recursive($card_fields_form['form']['input'], $mobilepay_fields_form['form']['input']);
		$form_fields['form']['submit'] = $card_fields_form['form']['submit'];


		$lang                              = new Language( (int) Configuration::get( 'PS_LANG_DEFAULT' ) );
		$helper                            = new HelperForm();
		$helper->default_form_language     = $lang->id;
		$helper->allow_employee_form_lang  = Configuration::get( 'PS_BO_ALLOW_EMPLOYEE_FORM_LANG' ) ?? 0;
		$helper->show_toolbar              = false;
		$helper->table                     = $this->table;
		$helper->identifier    			   = $this->identifier;
		$helper->token         			   = Tools::getAdminTokenLite( 'AdminModules' );
		$helper->submit_action 			   = 'submitLunar';
		$helper->currentIndex  			   = $this->context->link->getAdminLink( 'AdminModules', false ) 
												. '&configure=lunarpayment&tab_module=' 
												. $this->tab . '&module_name=lunarpayment';
		$helper->tpl_vars      			   = [
			'fields_value' => $this->getConfigFieldsValues(),
			'id_language'  => $this->context->language->id
		];


		$errors = $this->context->controller->errors;
		foreach ( $form_fields['form']['input'] as $key => $field ) {
			if ( array_key_exists( $field['name'], $errors ) ) {
				$form_fields['form']['input'][ $key ]['class'] .= ' has-error';
			}
		}

		return $helper->generateForm([$form_fields]);
	}

	/**
	 * 
	 */
	public function getConfigFieldsValues() {
		$lunarCardConfigValues = $this->cardMethod->getConfiguration();
		$lunarMobilePayConfigValues = $this->mobilePayMethod->getConfiguration();

		return array_merge(
			$lunarCardConfigValues,
			$lunarMobilePayConfigValues
		);
	}

	/**
     * @param $params
     *
     * @return array
     *
     * @throws Exception
     * @throws SmartyException
	 */
	public function hookPaymentOptions( $params )
	{
		if (!$this->active) {
            return;
        }
	
		$this->context->smarty->assign([
			'module_path' => $this->_path,
			'lunar_card_title' => Configuration::get($this->cardMethod->METHOD_TITLE),
			'lunar_card_desc' => Configuration::get($this->cardMethod->METHOD_DESCRIPTION),
			'accepted_cards' => explode( ',', Configuration::get( $this->cardMethod->ACCEPTED_CARDS ) ),
			'lunar_mobilepay_title'	=> Configuration::get($this->mobilePayMethod->METHOD_TITLE),
			'lunar_mobilepay_desc' => Configuration::get($this->mobilePayMethod->METHOD_DESCRIPTION),
		]);

		$payment_options = [];

		if (
			'enabled' == Configuration::get( $this->cardMethod->METHOD_STATUS)
			&& $this->cardMethod->isConfigured()
		) {
			$payment_options[] = $this->cardMethod->getPaymentOption();
		}

		if (
			'enabled' == Configuration::get( $this->mobilePayMethod->METHOD_STATUS)
			&& $this->mobilePayMethod->isConfigured()
		) {
			$payment_options[] = $this->mobilePayMethod->getPaymentOption();
		}

		return $payment_options;
	}

	/**
	 * @TODO is this used?
	 */
	public function hookPaymentReturn( $params ) {
		if ( ! $this->active || ! isset( $params['objOrder'] ) || $params['objOrder']->module != $this->name ) {
			return false;
		}

		if ( isset( $params['objOrder'] ) && Validate::isLoadedObject( $params['objOrder'] ) && isset( $params['objOrder']->valid ) && isset( $params['objOrder']->reference ) ) {
			$this->smarty->assign(
				"lunar_order",
				array(
					'id'        => $params['objOrder']->id,
					'reference' => $params['objOrder']->reference,
					'valid'     => $params['objOrder']->valid
				)
			);

			return $this->display( __FILE__, 'views/templates/hook/payment-return.tpl' );
		}
	}

	public function storeTransaction( $lunar_txn_id, $order_id, $total, $method, $captured = 'NO' ) {
		$query = 'INSERT INTO ' . _DB_PREFIX_ . 'lunar_transactions ('
					. '`lunar_tid`, `order_id`, `payed_amount`, `payed_at`, `captured`, `method`) VALUES ("'
					. pSQL( $lunar_txn_id ) . '", "' 
					. pSQL( $order_id ) . '", "' 
					. pSQL( $total ) 
					. '" , NOW(), "' 
					. pSQL( $captured ) . '", "' 
					. pSQL( $method ) . '")';

		return Db::getInstance()->execute( $query );
	}

	public function hookDisplayAdminOrder( $params ) {
		$id_order = $params['id_order'];
		$order    = new Order( (int) $id_order );

		if ( $order->module == $this->name ) {
			$order_token = Tools::getAdminToken( 'AdminOrders' . (int)  Tab::getIdFromClassName('AdminOrders') . (int) $this->context->employee->id );
			$dbLunarTransaction = $this->getLunarTransactionByOrderId($id_order);

			$this->context->smarty->assign( array(
				'id_order'           			  => $id_order,
				'order_token'        			  => $order_token,
				"lunartransaction" 				  => $dbLunarTransaction,
				'not_captured_text'	  			  => $this->l('Capture Transaction prior to Refund via Lunar'),
				'checkbox_text' 	  			  => $this->l('Refund Lunar')
			) );

			return $this->display( __FILE__, 'views/templates/hook/admin-order.tpl' );
		}
	}

	public function getLunarTransactionByOrderId($orderId)
	{
		return Db::getInstance()
				->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'lunar_transactions WHERE order_id = ' . (int) $orderId);
	}

	public function hookActionOrderSlipAdd( $params ){
		return $this->adminOrderHelper->refundPaymentOnSlipAdd($params);
	}

	public function hookActionOrderStatusPostUpdate( $params )
	{
		return $this->adminOrderHelper->paymentActionOnOrderStatusChange($params);
	}

	public function hookBackOfficeHeader() {
		if ($this->context->cookie->__isset('response_error')) {
			/** Display persistent */
			$this->context->controller->errors[] = '<p>'.$this->context->cookie->__get('response_error').'</p>';
			/** Clean persistent error */
			$this->context->cookie->__unset('response_error');
		}

		if ($this->context->cookie->__isset('response_warnings')) {
			/** Display persistent */
			$this->context->controller->warnings[] = '<p>'.$this->context->cookie->__get('response_warnings').'</p>';
			/** Clean persistent */
			$this->context->cookie->__unset('response_warnings');
		}

		if ($this->context->cookie->__isset('response_confirmations')) {
			/** Display persistent */
			$this->context->controller->confirmations[] = '<p>'.$this->context->cookie->__get('response_confirmations').'</p>';
			/** Clean persistent */
			$this->context->cookie->__unset('response_confirmations');
		}

		if ( Tools::getIsset( 'vieworder' ) && Tools::getIsset( 'id_order' ) && Tools::getIsset( "lunar_action" ) ) {
			$plugin_action = Tools::getValue( "lunar_action" );
			$id_order = (int) Tools::getValue( 'id_order' );
			$response = $this->doPaymentAction($id_order, $plugin_action, Tools::getValue( "lunar_amount_to_refund" ));
			die( json_encode( $response ) );
		}

		// if ( Tools::getIsset( 'upload_logo' ) ) {
		// 	$logo_name = Tools::getValue( 'logo_name' );

		// 	if ( empty( $logo_name ) ) {
		// 		$response = array(
		// 			'status'  => 0,
		// 			'message' => 'Please give logo name.'
		// 		);
		// 		die( json_encode( $response ) );
		// 	}

		// 	$logo_slug = Tools::strtolower( str_replace( ' ', '-', $logo_name ) );
		// 	$sql       = new DbQuery();
		// 	$sql->select( '*' );
		// 	$sql->from( "lunar_logos", 'PL' );
		// 	$sql->where( 'PL.slug = "' . pSQL($logo_slug) . '"' );
		// 	$logos = Db::getInstance()->executes( $sql );
		// 	if ( ! empty( $logos ) ) {
		// 		$response = array(
		// 			'status'  => 0,
		// 			'message' => 'This name already exists.'
		// 		);
		// 		die( json_encode( $response ) );
		// 	}

		// 	if ( ! empty( $_FILES['logo_file']['name'] ) ) {
		// 		$target_dir    = _PS_MODULE_DIR_ . $this->name . '/views/img/';
		// 		$name          = basename( $_FILES['logo_file']["name"] );
		// 		$path_parts    = pathinfo( $name );
		// 		$extension     = $path_parts['extension'];
		// 		$file_name     = $logo_slug . '.' . $extension;
		// 		$target_file   = $target_dir . basename( $file_name );
		// 		$imageFileType = pathinfo( $target_file, PATHINFO_EXTENSION );

		// 		/*$check = getimagesize($_FILES['logo_file']["tmp_name"]);
        //         if($check === false) {
        //             $response = array(
        //                 'status' => 0,
        //                 'message' => 'File is not an image. Please upload JPG, JPEG, PNG or GIF file.'
        //             );
        //             die(json_encode($response));
        //         }*/

		// 		// Check if file already exists
		// 		if ( file_exists( $target_file ) ) {
		// 			$response = array(
		// 				'status'  => 0,
		// 				'message' => 'Sorry, file already exists.'
		// 			);
		// 			die( json_encode( $response ) );
		// 		}

		// 		// Allow certain file formats
		// 		if ( $imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
		// 		     && $imageFileType != "gif" && $imageFileType != "svg" ) {
		// 			$response = array(
		// 				'status'  => 0,
		// 				'message' => 'Sorry, only JPG, JPEG, PNG, GIF & SVG files are allowed.'
		// 			);
		// 			die( json_encode( $response ) );
		// 		}

		// 		if ( move_uploaded_file( $_FILES['logo_file']["tmp_name"], $target_file ) ) {
		// 			$query = 'INSERT INTO ' . _DB_PREFIX_ . 'lunar_logos (`name`, `slug`, `file_name`, `default_logo`, `created_at`)
		// 						VALUES ("' . pSQL( $logo_name ) . '", "' . pSQL( $logo_slug ) . '", "' . pSQL( $file_name ) . '", 0, NOW())';

		// 			if ( Db::getInstance()->execute( $query ) ) {
		// 				$response = array(
		// 					'status'  => 1,
		// 					'message' => "The file " . basename( $file_name ) . " has been uploaded."
		// 				);
		// 				//Configuration::updateValue(self::ACCEPTED_CARDS, basename($file_name));
		// 				die( json_encode( $response ) );
		// 			} else {
		// 				unlink( $target_file );
		// 				$response = array(
		// 					'status'  => 0,
		// 					'message' => "Oops! An error occured while save logo."
		// 				);
		// 				die( json_encode( $response ) );
		// 			}
		// 		} else {
		// 			$response = array(
		// 				'status'  => 0,
		// 				'message' => 'Sorry, there was an error uploading your file.'
		// 			);
		// 			die( json_encode( $response ) );
		// 		}
		// 	} else {
		// 		$response = array(
		// 			'status'  => 0,
		// 			'message' => 'Please select a file for upload.'
		// 		);
		// 		die( json_encode( $response ) );
		// 	}
		// }

		if ( Tools::getValue( 'configure' ) == $this->name ) {
			$this->context->controller->addCSS( $this->_path . 'views/css/backoffice.css' );
		}
	}


	/**
	 * 
	 */
	private function doPaymentAction($id_order, $plugin_action, $plugin_amount_to_refund = 0)
	{
		return $this->adminOrderHelper->processOrderPayment($id_order, $plugin_action, $plugin_amount_to_refund);
	}


	/**
	 * 
	 */
	public function renderScript() {
		$this->context->smarty->assign([
			'request_uri' => $this->context->link->getAdminLink( 'AdminOrders', false ),
		]);

		return $this->display( __FILE__, 'views/templates/admin/script.tpl' );
	}

	/**
	 * 
	 */
	public function t($string) {
		return $this->l($string);
	}

	/**
	 * 
	 */
	// public function getModalForAddMoreLogo() {
	// 	$this->context->smarty->assign( array(
	// 		'request_uri' => $this->context->link->getAdminLink( 'AdminOrders', false ),
	// 		'tok'         => Tools::getAdminToken( 'AdminOrders' )
	// 	) );

	// 	return $this->display( __FILE__, 'views/templates/admin/modal.tpl' );
	// }
}
