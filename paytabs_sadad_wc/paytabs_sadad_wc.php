<?php
ob_start();
/******************************************************************************************************************************
*    
*     Plugin Name: WooCommerce Payment Gateway - SADAD
*     Plugin URI: http://www.paytabs.com/plugin
*     Description: SADAD Secure Payment for woocommerce. This plugin support on woocommerce version 2.0.0 or greater version.
*     Version: 1.0
*     Author: PayTabs
*     Author URI: http://www.paytabs.com
*
********************************************************************************************************************************/
@session_start();

//load plugin finction when woocommerce loaded
add_action('plugins_loaded', 'woocommerce_paytabs_sadad_wc_init', 0);
//paytab plugin function
function woocommerce_paytabs_sadad_wc_init(){
  if(!class_exists('WC_Payment_Gateway')) return;
//extend wc_payment_gateway class and create ad class
  class WC_Gateway_Paytabs_sadad_wc extends WC_Payment_Gateway{

    public function __construct(){
          $pluginpath          =   WC()->plugin_url();
          $pluginpath          =   explode('plugins', $pluginpath);
          $this->id            =   'sadad';
          $this->icon          =   apply_filters( 'woocommerce_paytabs_icon', $pluginpath[0] . 'plugins/paytabs_sadad_wc/icons/sadad.png' );
          $this->medthod_title =   'SADAD Secure Payments';
          $this->has_fields    =   false;   
          $this->init_form_fields();
          $this->init_settings();
      
          //fetch data from admin setting
          $this->title            = $this->settings['title'];
          $this->description      = $this->settings['description'];
          $this->website          = $this->settings['website'];
          $this->merchant_id      = $this->settings['merchant_id'];
          $this->password         = $this->settings['password'];
          $this->redirect_page_id = $this->settings['redirect_page_id'];
          //live payment url
          $this->liveurl          = 'https://www.paytabs.com/';
          $this->form_submission_method = $this->get_option( 'form_submission_method' ) == 'yes' ? true : false;
          $this-> msg['message']  = "";
          $this-> msg['class']    = "";      

          //call initially check_paymnet_response funtion when checkout process call and before payment process
          add_action('init', array(&$this, 'check_paytabs_response'));

          //when payment done and redirected with payment reference code
     
          if(isset($_REQUEST['payment_reference'])){
            $this->complete_transaction($this->id);
          }

          if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            } 
      }
   //admin form fields or setting on wocommerce
    function init_form_fields(){
       $this -> form_fields = array(
                'enabled'   => array(
                    'title'   => __('Enable/Disable', 'SADAD Secure Payment.'),
                    'type'    => 'checkbox',
                    'label'   => __('Enable SADAD Secure Payment.', 'SADAD'),
                    'default' => 'yes'),
              
                'description' => array(
                    'title'       => __('Description:', 'SADAD'),
                    'type'        => 'textarea',
                    'description' => __('Making any changes to the above may result in suspension or termination of your PayTabs Merchant Account.', 'SADAD'),
                    'default'     => __('ادخل اسم المستخدم لحساب سداد‎', 'SADAD')),
                'merchant_id' => array(
                    'title'       => __('Email', 'PayTabs'),
                    'type'        => 'text',
                    'value'       => '',
                    'description' => __( ' Please enter the email id of your PayTabs merchant account.','woocommerce' ),
                    'default'     => '',
                    'required'    =>true),
                
                'password' => array(
                    'title'       => __('Secret Key', 'PayTabs'),
                    'type'        => 'text',
                    'value'       => '',
                    'size'       => '120',
                    'description' => __( 'Please enter your PayTabs Secret Key. You can find the secret key on your Merchant’s Dashboard >> PayTabs Services >> ecommerce Plugins and API.', 'woocommerce' ),
                    'default'     => '',
                    'required'    => true),
                   'website' => array(
                    'title'       => __('WebSite', 'PayTabs'),
                    'type'        => 'text',
                    'value'       => '',
                    'description' => __( 'Please enter your Website; Your SITE URL MUST match to the website URL you provided in your PayTabs merchant account.
For Demo Users: You can edit your site URL by going to “My Profile” and clicking on edit, enter your correct site URL and click on Save.
For Live Merchants: You can use the website that you have submitted in the Go-Live application. If you need to edit/change the site URL, you can send a request to customercare@paytabs.com.','woocommerce' ),
                    'default'     => '',
                    'required'    =>true
                   )
                
            );
    }
    //admin option on woocommerce setting 
      public function admin_options(){
        echo '<h3>'.__('SADAD', 'SADAD').'</h3>';
        echo '<p>'.__('').'</p>';
        echo ' <script type="text/javascript">
                jQuery("#mainform").submit(function(){
                  var marchantid=jQuery("#woocommerce_PayTabs_merchant_id").val();
                  var marchantpass=jQuery("#woocommerce_PayTabs_password").val();
                  var err_flag=0;
                  var errormsg="Required fields \t\n";          
                  if(marchantid==""){
                            errormsg+="\tPlease enter merchant id";
                  err_flag=1;
                          }
                          if(marchantpass==""){
                            errormsg+="\t\nPlease enter merchant password";
                            err_flag=1;
                          } 
                  if(err_flag==1){                  
                      alert(errormsg) ;
                    return false;
                  }
                  else{
                  return true;
                  } 
                }); 
             </script>';
        echo '<table class="form-table">';
        // Generate the HTML For the settings form.
        $this -> generate_settings_html();
        echo '</table>';

    }

      /**
     *  There are no payment fields for paytabs, but we want to show the description if set.
     **/
    function payment_fields(){
    
        if($this -> description) echo wpautop(wptexturize($this -> description));
        echo "<input type='text' name='sadad' placeholder='ادخل اسم المستخدم لحساب سداد‎' size=25>";
    }
    
    


  /**
   * Get Paytabs Args for passing to PP
   *
   * @access public
   * @param mixed $order
   * @return array
   */
  function get_paytabs_args( $order ) {


        $order_id         = $order->id;
        $txnid            = $order_id.'_'.date("ymds");
        $redirect         = $order->get_checkout_payment_url( true );
        //array values for authentication

    
        if (empty($order->order_shipping) OR $order->order_shipping == '') {
          $order->order_shipping=0;
        }

        $_SESSION['secret_key']=$this->password;
        $_SESSION['merchant_id']=$this->merchant_id;
        $paytabs_args = array(
          'txnid'            =>  $txnid,
          'merchant_email'   => $this->merchant_id,
          'secret_key'       => $this->password,
          'productinfo'      =>  $productinfo,
          'firstname'        =>  $order->billing_first_name,
          'lastname'         =>  $order->billing_last_name,
          'address1'         =>  $order->billing_address_1,
          'address2'         =>  $order->billing_address_2,           
          'zipcode'          =>  $order->billing_postcode,          
          'cc_phone_number'  =>  $order->billing_phone,
          'phone'            =>  $order->billing_phone,
          "cc_first_name"    =>  $order->billing_first_name,
          "cc_last_name"     =>  $order->billing_last_name,
          "phone_number"     =>  $order->billing_phone,
          "billing_address"  =>  $order->billing_address_1,
          'state'            =>  $order->billing_state,
          'city'             =>  $order->billing_city,
          "postal_code"      =>  $order->billing_postcode,  
          'country'          =>  $this->getCountryIsoCode($order->billing_country),   
          'email'            =>  $order->billing_email,   
          'amount'           =>  $order->order_total, 
          'discount'         =>  $order->get_total_discount(),
          'reference_no'     =>  $txnid,          
          "currency"         =>  strtoupper(get_woocommerce_currency()),
          "title"            =>  $order->billing_first_name.' '.$order ->billing_last_name,
          'ip_customer'      =>  $_SERVER['REMOTE_ADDR'],
          'ip_merchant'      =>  $_SERVER['SERVER_ADDR'],
          "return_url"       =>  $redirect,
          'CustomerId'       => $txnid,
          'other_charges'    =>$order->order_shipping,
          'cms_with_version' =>' WooCommerce  :'.WOOCOMMERCE_VERSION,
          'reference_no'     => $txnid,
          'payment_type'     => 'SADAD',
          'olp_id'           => strip_tags($_POST['sadad']),
          'site_url'         => $this->website,
          'msg_lang'         => 'English'
          );

        // Shipping
        if ( 'yes' == $this->send_shipping ) {
              $paytabs_args['address_shipping']    = $order->shipping_address_1.' '.$order->shipping_address_2;          
              $paytabs_args['city_shipping']       = $order->shipping_city;
              $paytabs_args['state_shipping']      = $order->shipping_state ;
              $paytabs_args['country_shipping']    = $this->getCountryIsoCode($order->shipping_country);
              $paytabs_args['postal_code_shipping']= $order->shipping_postcode;
        } else {
              $paytabs_args['address_shipping']     = $order->shipping_address_1.' '.$order->shipping_address_2;          
              $paytabs_args['city_shipping']       = $order->shipping_city;
              $paytabs_args['state_shipping']      = $order->shipping_state ;
              $paytabs_args['country_shipping']    = $this->getCountryIsoCode($order->shipping_country);
              $paytabs_args['postal_code_shipping']= $order->shipping_postcode;
              //$paytabs_args['discount']             = $order->get_order_discount();

        }


        
      // Cart Contents
      $item_loop = 0;     
        foreach ( $order->get_items() as $item ) {         
          if ( $item['qty'] ) {
                  $item_loop++;
                  $product = $order->get_product_from_item( $item );
                  $item_name  = $item['name'];
                  $item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
                      if ( $meta = $item_meta->display( true, true ) ) {
                        $item_name .= ' ( ' . $meta . ' )';
                      }
                      //product description
                      if($paytabs_args['products_per_title']!=''){
                            $paytabs_args['products_per_title']   = $paytabs_args['products_per_title'].' || '.$item_name ;
                      }else{
                            $paytabs_args[ 'products_per_title']=$item_name;
                      }
                      //product description
                      if($paytabs_args['ProductName']!=''){
                            $paytabs_args['ProductName']   = $paytabs_args['ProductName'].' || '.$item_name ;
                      }else{
                            $paytabs_args['ProductName']=$item_name;
                      }
                      //product quantity
                      if($paytabs_args['quantity']!=''){
                            $paytabs_args['quantity']   = $paytabs_args['quantity'].' || '.$item['qty'] ;
                      }else{
                            $paytabs_args['quantity']  = $item['qty'];
                      }
                      //product  unit price
                      if($paytabs_args['unit_price']!=''){
                            $paytabs_args['unit_price']   = $paytabs_args[ 'unit_price'].' || '.$order->get_item_subtotal( $item, false ); 
                      }else{
                            $paytabs_args['unit_price']=$order->get_item_subtotal( $item, false );
                      }
                      //product category name
                      if($paytabs_args['ProductCategory']!=''){
                            $paytabs_args['ProductCategory']   = $paytabs_args['ProductCategory'].'||'.$item['type'] ;
                      }else{
                            $paytabs_args['ProductCategory']=$item['type'];
                      }             
             }
        }
          

              $paytabs_args["ShippingMethod"]=$order->get_shipping_method();
              $paytabs_args["DeliveryType"]=$order->get_shipping_method();
              $paytabs_args["CustomerID"]=get_current_user_id( );
              $paytabs_args["channelOfOperations"]="channelOfOperations";
              $paytabs_args['amount'] = $paytabs_args['amount']+$paytabs_args['discount'];

              $paytabs_args = apply_filters( 'woocommerce_paytabs_args', $paytabs_args );
              $pay_url=$this->before_process($paytabs_args);
     
          return $pay_url;


  }




  function process_payment($order_id){
          global $woocommerce;
          $order                    = new WC_Order($order_id);   
          $_SESSION['order_id']     = $order_id;
          if ( ! $this->form_submission_method ) {
        
              $paytabs_payment_url  = $this->get_paytabs_args( $order);              
              $paytabs_adr          = $paytabs_payment_url->payment_url;

              
              //check if api is wrong or dont get payment url
              if($paytabs_adr==''){
                $this->msg['class'] = 'woocommerce_message';
                // Change the status to pending / unpaid
               // $order->update_status('failed', __('Payment failed', 'woocommerce'));  
                $order->update_status('failed', __('Payment failed', 'error'));                   
                 
                // Add error for the customer when we return back to the cart
                $message=$paytabs_payment_url->result;
                wc_add_notice(__($message, 'error' ), 'error' );                       

                  return array(
                        'result'    => 'success',
                        'redirect'  => $order->get_checkout_payment_url(true)
                      
                    );
              }else{                
                  return array(
                        'result'    => 'success',
                        'redirect'  => $paytabs_adr
                    );
              }
              
            }else {
            

            //  wc_add_notice( 'Transaction declined', 'error' );

                  return array(
                        'result'   => 'success', 
                        'redirect' => $order->get_checkout_payment_url( true )
                    );
            }
  }


 /**
     * Check process for form submittion
  **/ 
  function before_process($array) {
          $gateway_url    = $this->liveurl;        
          $request_string = http_build_query($array);
          $response_data  = $this->sendRequest($gateway_url . 'apiv2/create_sadad_payment', $request_string);
          $object = json_decode($response_data);
          return $object;

          
  }

  /**
     * Get response throgh 3 rd party
  **/ 
  function sendRequest($gateway_url, $request_string){
          $ch = @curl_init();
          @curl_setopt($ch, CURLOPT_URL, $gateway_url);
          @curl_setopt($ch, CURLOPT_POST, true);
          @curl_setopt($ch, CURLOPT_POSTFIELDS, $request_string);
          @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          @curl_setopt($ch, CURLOPT_HEADER, false);
          @curl_setopt($ch, CURLOPT_TIMEOUT, 30);
          @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
          @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
          @curl_setopt($ch, CURLOPT_VERBOSE, true);
          $result = @curl_exec($ch);
          if (!$result)
            die(curl_error($ch));

          @curl_close($ch);
          
          return $result;
  }
  
  //show message when success or not success or payment status
  function showMessage($content){
            return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
            //return $this -> msg['class'].$this -> msg['message'].$content;
  }



   function get_cancel_endpoint() {

        $cancel_endpoint = wc_get_page_permalink( 'cart' );
        if ( ! $cancel_endpoint ) {
             $cancel_endpoint = home_url();
        }
 
         if ( false === strpos( $cancel_endpoint, '?' ) ) {
             $cancel_endpoint = trailingslashit( $cancel_endpoint );
         } 
         return $cancel_endpoint;
     }
 
 
   //Cancel order
    function get_cancel_order_url( $redirect = '' ) {

        // Get cancel endpoint
        $cancel_endpoint = $this->get_cancel_endpoint();

        return apply_filters( 'woocommerce_get_cancel_order_url', wp_nonce_url( add_query_arg( array(
          'cancel_order' => 'true',
          'order'        => $this->order_key,
          'order_id'     => $this->id,
          'redirect'     => $redirect
        ), $cancel_endpoint ), 'woocommerce-cancel_order' ) );
      }


  /*
  When transaction completed it is check the status 
  is transaction completed or rejected
  */
  function complete_transaction() {
        global $woocommerce;       
        $order = new WC_Order( $_SESSION['order_id']);
      
        $request_string=array(
                    'secret_key'        => $_SESSION['secret_key'],
                    'merchant_email'=>$_SESSION['merchant_id'],
                    'payment_reference' => $_REQUEST['payment_reference']
                );
        $gateway_url=$this->liveurl.'apiv2/verify_payment';
        $getdataresponse=$this->sendRequest($gateway_url, $request_string);
        $object=json_decode($getdataresponse);
   
      if (isset($object->response_code)) {
        //if get response successfull
        if($object->response_code == '100'){
         //  thankyou and set error message 
            $this->msg['class'] = 'woocommerce_message';
            $check=$order->payment_complete();
            // Remove cart
            $woocommerce->cart->empty_cart();   
            
            wc_add_notice( '' . __( 'Thank you for shopping with us. Your account has been charged and your transaction is successful.
            We will be shipping your order to you soon.', 'woocommerce' ), 'success' );     

            wp_redirect( $this->get_return_url( $order ) );
            //exit;

         }else{
            wp_redirect( $this->get_return_url( $order ) );

            $message = $object->result;
                // Change the status to pending / unpaid
            $order->update_status('cancelled', __('Payment Cancelled', 'error'));                   
            // Add error for the customer when we return back to the cart

            wc_add_notice( '<strong></strong> ' . __($message, 'error' ), 'error' );     
            // Redirect back to the last step in the checkout process
            wp_redirect( $this->get_cancel_order_url( $order ) );
           // exit;          
        }
    
     }


  }


    
 

    /*
    Get country code function 
    */
    function getCountryIsoCode($code){
        $countries = array(
          "AF" => array("AFGHANISTAN", "AF", "AFG", "004"),
          "AL" => array("ALBANIA", "AL", "ALB", "008"),
          "DZ" => array("ALGERIA", "DZ", "DZA", "012"),
          "AS" => array("AMERICAN SAMOA", "AS", "ASM", "016"),
          "AD" => array("ANDORRA", "AD", "AND", "020"),
          "AO" => array("ANGOLA", "AO", "AGO", "024"),
          "AI" => array("ANGUILLA", "AI", "AIA", "660"),
          "AQ" => array("ANTARCTICA", "AQ", "ATA", "010"),
          "AG" => array("ANTIGUA AND BARBUDA", "AG", "ATG", "028"),
          "AR" => array("ARGENTINA", "AR", "ARG", "032"),
          "AM" => array("ARMENIA", "AM", "ARM", "051"),
          "AW" => array("ARUBA", "AW", "ABW", "533"),
          "AU" => array("AUSTRALIA", "AU", "AUS", "036"),
          "AT" => array("AUSTRIA", "AT", "AUT", "040"),
          "AZ" => array("AZERBAIJAN", "AZ", "AZE", "031"),
          "BS" => array("BAHAMAS", "BS", "BHS", "044"),
          "BH" => array("BAHRAIN", "BH", "BHR", "048"),
          "BD" => array("BANGLADESH", "BD", "BGD", "050"),
          "BB" => array("BARBADOS", "BB", "BRB", "052"),
          "BY" => array("BELARUS", "BY", "BLR", "112"),
          "BE" => array("BELGIUM", "BE", "BEL", "056"),
          "BZ" => array("BELIZE", "BZ", "BLZ", "084"),
          "BJ" => array("BENIN", "BJ", "BEN", "204"),
          "BM" => array("BERMUDA", "BM", "BMU", "060"),
          "BT" => array("BHUTAN", "BT", "BTN", "064"),
          "BO" => array("BOLIVIA", "BO", "BOL", "068"),
          "BA" => array("BOSNIA AND HERZEGOVINA", "BA", "BIH", "070"),
          "BW" => array("BOTSWANA", "BW", "BWA", "072"),
          "BV" => array("BOUVET ISLAND", "BV", "BVT", "074"),
          "BR" => array("BRAZIL", "BR", "BRA", "076"),
          "IO" => array("BRITISH INDIAN OCEAN TERRITORY", "IO", "IOT", "086"),
          "BN" => array("BRUNEI DARUSSALAM", "BN", "BRN", "096"),
          "BG" => array("BULGARIA", "BG", "BGR", "100"),
          "BF" => array("BURKINA FASO", "BF", "BFA", "854"),
          "BI" => array("BURUNDI", "BI", "BDI", "108"),
          "KH" => array("CAMBODIA", "KH", "KHM", "116"),
          "CM" => array("CAMEROON", "CM", "CMR", "120"),
          "CA" => array("CANADA", "CA", "CAN", "124"),
          "CV" => array("CAPE VERDE", "CV", "CPV", "132"),
          "KY" => array("CAYMAN ISLANDS", "KY", "CYM", "136"),
          "CF" => array("CENTRAL AFRICAN REPUBLIC", "CF", "CAF", "140"),
          "TD" => array("CHAD", "TD", "TCD", "148"),
          "CL" => array("CHILE", "CL", "CHL", "152"),
          "CN" => array("CHINA", "CN", "CHN", "156"),
          "CX" => array("CHRISTMAS ISLAND", "CX", "CXR", "162"),
          "CC" => array("COCOS (KEELING) ISLANDS", "CC", "CCK", "166"),
          "CO" => array("COLOMBIA", "CO", "COL", "170"),
          "KM" => array("COMOROS", "KM", "COM", "174"),
          "CG" => array("CONGO", "CG", "COG", "178"),
          "CK" => array("COOK ISLANDS", "CK", "COK", "184"),
          "CR" => array("COSTA RICA", "CR", "CRI", "188"),
          "CI" => array("COTE D'IVOIRE", "CI", "CIV", "384"),
          "HR" => array("CROATIA (local name: Hrvatska)", "HR", "HRV", "191"),
          "CU" => array("CUBA", "CU", "CUB", "192"),
          "CY" => array("CYPRUS", "CY", "CYP", "196"),
          "CZ" => array("CZECH REPUBLIC", "CZ", "CZE", "203"),
          "DK" => array("DENMARK", "DK", "DNK", "208"),
          "DJ" => array("DJIBOUTI", "DJ", "DJI", "262"),
          "DM" => array("DOMINICA", "DM", "DMA", "212"),
          "DO" => array("DOMINICAN REPUBLIC", "DO", "DOM", "214"),
          "TL" => array("EAST TIMOR", "TL", "TLS", "626"),
          "EC" => array("ECUADOR", "EC", "ECU", "218"),
          "EG" => array("EGYPT", "EG", "EGY", "818"),
          "SV" => array("EL SALVADOR", "SV", "SLV", "222"),
          "GQ" => array("EQUATORIAL GUINEA", "GQ", "GNQ", "226"),
          "ER" => array("ERITREA", "ER", "ERI", "232"),
          "EE" => array("ESTONIA", "EE", "EST", "233"),
          "ET" => array("ETHIOPIA", "ET", "ETH", "210"),
          "FK" => array("FALKLAND ISLANDS (MALVINAS)", "FK", "FLK", "238"),
          "FO" => array("FAROE ISLANDS", "FO", "FRO", "234"),
          "FJ" => array("FIJI", "FJ", "FJI", "242"),
          "FI" => array("FINLAND", "FI", "FIN", "246"),
          "FR" => array("FRANCE", "FR", "FRA", "250"),
          "FX" => array("FRANCE, METROPOLITAN", "FX", "FXX", "249"),
          "GF" => array("FRENCH GUIANA", "GF", "GUF", "254"),
          "PF" => array("FRENCH POLYNESIA", "PF", "PYF", "258"),
          "TF" => array("FRENCH SOUTHERN TERRITORIES", "TF", "ATF", "260"),
          "GA" => array("GABON", "GA", "GAB", "266"),
          "GM" => array("GAMBIA", "GM", "GMB", "270"),
          "GE" => array("GEORGIA", "GE", "GEO", "268"),
          "DE" => array("GERMANY", "DE", "DEU", "276"),
          "GH" => array("GHANA", "GH", "GHA", "288"),
          "GI" => array("GIBRALTAR", "GI", "GIB", "292"),
          "GR" => array("GREECE", "GR", "GRC", "300"),
          "GL" => array("GREENLAND", "GL", "GRL", "304"),
          "GD" => array("GRENADA", "GD", "GRD", "308"),
          "GP" => array("GUADELOUPE", "GP", "GLP", "312"),
          "GU" => array("GUAM", "GU", "GUM", "316"),
          "GT" => array("GUATEMALA", "GT", "GTM", "320"),
          "GN" => array("GUINEA", "GN", "GIN", "324"),
          "GW" => array("GUINEA-BISSAU", "GW", "GNB", "624"),
          "GY" => array("GUYANA", "GY", "GUY", "328"),
          "HT" => array("HAITI", "HT", "HTI", "332"),
          "HM" => array("HEARD ISLAND & MCDONALD ISLANDS", "HM", "HMD", "334"),
          "HN" => array("HONDURAS", "HN", "HND", "340"),
          "HK" => array("HONG KONG", "HK", "HKG", "344"),
          "HU" => array("HUNGARY", "HU", "HUN", "348"),
          "IS" => array("ICELAND", "IS", "ISL", "352"),
          "IN" => array("INDIA", "IN", "IND", "356"),
          "ID" => array("INDONESIA", "ID", "IDN", "360"),
          "IR" => array("IRAN, ISLAMIC REPUBLIC OF", "IR", "IRN", "364"),
          "IQ" => array("IRAQ", "IQ", "IRQ", "368"),
          "IE" => array("IRELAND", "IE", "IRL", "372"),
          "IL" => array("ISRAEL", "IL", "ISR", "376"),
          "IT" => array("ITALY", "IT", "ITA", "380"),
          "JM" => array("JAMAICA", "JM", "JAM", "388"),
          "JP" => array("JAPAN", "JP", "JPN", "392"),
          "JO" => array("JORDAN", "JO", "JOR", "400"),
          "KZ" => array("KAZAKHSTAN", "KZ", "KAZ", "398"),
          "KE" => array("KENYA", "KE", "KEN", "404"),
          "KI" => array("KIRIBATI", "KI", "KIR", "296"),
          "KP" => array("KOREA, DEMOCRATIC PEOPLE'S REPUBLIC OF", "KP", "PRK", "408"),
          "KR" => array("KOREA, REPUBLIC OF", "KR", "KOR", "410"),
          "KW" => array("KUWAIT", "KW", "KWT", "414"),
          "KG" => array("KYRGYZSTAN", "KG", "KGZ", "417"),
          "LA" => array("LAO PEOPLE'S DEMOCRATIC REPUBLIC", "LA", "LAO", "418"),
          "LV" => array("LATVIA", "LV", "LVA", "428"),
          "LB" => array("LEBANON", "LB", "LBN", "422"),
          "LS" => array("LESOTHO", "LS", "LSO", "426"),
          "LR" => array("LIBERIA", "LR", "LBR", "430"),
          "LY" => array("LIBYAN ARAB JAMAHIRIYA", "LY", "LBY", "434"),
          "LI" => array("LIECHTENSTEIN", "LI", "LIE", "438"),
          "LT" => array("LITHUANIA", "LT", "LTU", "440"),
          "LU" => array("LUXEMBOURG", "LU", "LUX", "442"),
          "MO" => array("MACAU", "MO", "MAC", "446"),
          "MK" => array("MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF", "MK", "MKD", "807"),
          "MG" => array("MADAGASCAR", "MG", "MDG", "450"),
          "MW" => array("MALAWI", "MW", "MWI", "454"),
          "MY" => array("MALAYSIA", "MY", "MYS", "458"),
          "MV" => array("MALDIVES", "MV", "MDV", "462"),
          "ML" => array("MALI", "ML", "MLI", "466"),
          "MT" => array("MALTA", "MT", "MLT", "470"),
          "MH" => array("MARSHALL ISLANDS", "MH", "MHL", "584"),
          "MQ" => array("MARTINIQUE", "MQ", "MTQ", "474"),
          "MR" => array("MAURITANIA", "MR", "MRT", "478"),
          "MU" => array("MAURITIUS", "MU", "MUS", "480"),
          "YT" => array("MAYOTTE", "YT", "MYT", "175"),
          "MX" => array("MEXICO", "MX", "MEX", "484"),
          "FM" => array("MICRONESIA, FEDERATED STATES OF", "FM", "FSM", "583"),
          "MD" => array("MOLDOVA, REPUBLIC OF", "MD", "MDA", "498"),
          "MC" => array("MONACO", "MC", "MCO", "492"),
          "MN" => array("MONGOLIA", "MN", "MNG", "496"),
          "MS" => array("MONTSERRAT", "MS", "MSR", "500"),
          "MA" => array("MOROCCO", "MA", "MAR", "504"),
          "MZ" => array("MOZAMBIQUE", "MZ", "MOZ", "508"),
          "MM" => array("MYANMAR", "MM", "MMR", "104"),
          "NA" => array("NAMIBIA", "NA", "NAM", "516"),
          "NR" => array("NAURU", "NR", "NRU", "520"),
          "NP" => array("NEPAL", "NP", "NPL", "524"),
          "NL" => array("NETHERLANDS", "NL", "NLD", "528"),
          "AN" => array("NETHERLANDS ANTILLES", "AN", "ANT", "530"),
          "NC" => array("NEW CALEDONIA", "NC", "NCL", "540"),
          "NZ" => array("NEW ZEALAND", "NZ", "NZL", "554"),
          "NI" => array("NICARAGUA", "NI", "NIC", "558"),
          "NE" => array("NIGER", "NE", "NER", "562"),
          "NG" => array("NIGERIA", "NG", "NGA", "566"),
          "NU" => array("NIUE", "NU", "NIU", "570"),
          "NF" => array("NORFOLK ISLAND", "NF", "NFK", "574"),
          "MP" => array("NORTHERN MARIANA ISLANDS", "MP", "MNP", "580"),
          "NO" => array("NORWAY", "NO", "NOR", "578"),
          "OM" => array("OMAN", "OM", "OMN", "512"),
          "PK" => array("PAKISTAN", "PK", "PAK", "586"),
          "PW" => array("PALAU", "PW", "PLW", "585"),
          "PA" => array("PANAMA", "PA", "PAN", "591"),
          "PG" => array("PAPUA NEW GUINEA", "PG", "PNG", "598"),
          "PY" => array("PARAGUAY", "PY", "PRY", "600"),
          "PE" => array("PERU", "PE", "PER", "604"),
          "PH" => array("PHILIPPINES", "PH", "PHL", "608"),
          "PN" => array("PITCAIRN", "PN", "PCN", "612"),
          "PL" => array("POLAND", "PL", "POL", "616"),
          "PT" => array("PORTUGAL", "PT", "PRT", "620"),
          "PR" => array("PUERTO RICO", "PR", "PRI", "630"),
          "QA" => array("QATAR", "QA", "QAT", "634"),
          "RE" => array("REUNION", "RE", "REU", "638"),
          "RO" => array("ROMANIA", "RO", "ROU", "642"),
          "RU" => array("RUSSIAN FEDERATION", "RU", "RUS", "643"),
          "RW" => array("RWANDA", "RW", "RWA", "646"),
          "KN" => array("SAINT KITTS AND NEVIS", "KN", "KNA", "659"),
          "LC" => array("SAINT LUCIA", "LC", "LCA", "662"),
          "VC" => array("SAINT VINCENT AND THE GRENADINES", "VC", "VCT", "670"),
          "WS" => array("SAMOA", "WS", "WSM", "882"),
          "SM" => array("SAN MARINO", "SM", "SMR", "674"),
          "ST" => array("SAO TOME AND PRINCIPE", "ST", "STP", "678"),
          "SA" => array("SAUDI ARABIA", "SA", "SAU", "682"),
          "SN" => array("SENEGAL", "SN", "SEN", "686"),
          "RS" => array("SERBIA", "RS", "SRB", "688"),
          "SC" => array("SEYCHELLES", "SC", "SYC", "690"),
          "SL" => array("SIERRA LEONE", "SL", "SLE", "694"),
          "SG" => array("SINGAPORE", "SG", "SGP", "702"),
          "SK" => array("SLOVAKIA (Slovak Republic)", "SK", "SVK", "703"),
          "SI" => array("SLOVENIA", "SI", "SVN", "705"),
          "SB" => array("SOLOMON ISLANDS", "SB", "SLB", "90"),
          "SO" => array("SOMALIA", "SO", "SOM", "706"),
          "ZA" => array("SOUTH AFRICA", "ZA", "ZAF", "710"),
          "ES" => array("SPAIN", "ES", "ESP", "724"),
          "LK" => array("SRI LANKA", "LK", "LKA", "144"),
          "SH" => array("SAINT HELENA", "SH", "SHN", "654"),
          "PM" => array("SAINT PIERRE AND MIQUELON", "PM", "SPM", "666"),
          "SD" => array("SUDAN", "SD", "SDN", "736"),
          "SR" => array("SURINAME", "SR", "SUR", "740"),
          "SJ" => array("SVALBARD AND JAN MAYEN ISLANDS", "SJ", "SJM", "744"),
          "SZ" => array("SWAZILAND", "SZ", "SWZ", "748"),
          "SE" => array("SWEDEN", "SE", "SWE", "752"),
          "CH" => array("SWITZERLAND", "CH", "CHE", "756"),
          "SY" => array("SYRIAN ARAB REPUBLIC", "SY", "SYR", "760"),
          "TW" => array("TAIWAN, PROVINCE OF CHINA", "TW", "TWN", "158"),
          "TJ" => array("TAJIKISTAN", "TJ", "TJK", "762"),
          "TZ" => array("TANZANIA, UNITED REPUBLIC OF", "TZ", "TZA", "834"),
          "TH" => array("THAILAND", "TH", "THA", "764"),
          "TG" => array("TOGO", "TG", "TGO", "768"),
          "TK" => array("TOKELAU", "TK", "TKL", "772"),
          "TO" => array("TONGA", "TO", "TON", "776"),
          "TT" => array("TRINIDAD AND TOBAGO", "TT", "TTO", "780"),
          "TN" => array("TUNISIA", "TN", "TUN", "788"),
          "TR" => array("TURKEY", "TR", "TUR", "792"),
          "TM" => array("TURKMENISTAN", "TM", "TKM", "795"),
          "TC" => array("TURKS AND CAICOS ISLANDS", "TC", "TCA", "796"),
          "TV" => array("TUVALU", "TV", "TUV", "798"),
          "UG" => array("UGANDA", "UG", "UGA", "800"),
          "UA" => array("UKRAINE", "UA", "UKR", "804"),
          "AE" => array("UNITED ARAB EMIRATES", "AE", "ARE", "784"),
          "GB" => array("UNITED KINGDOM", "GB", "GBR", "826"),
          "US" => array("UNITED STATES", "US", "USA", "840"),
          "UM" => array("UNITED STATES MINOR OUTLYING ISLANDS", "UM", "UMI", "581"),
          "UY" => array("URUGUAY", "UY", "URY", "858"),
          "UZ" => array("UZBEKISTAN", "UZ", "UZB", "860"),
          "VU" => array("VANUATU", "VU", "VUT", "548"),
          "VA" => array("VATICAN CITY STATE (HOLY SEE)", "VA", "VAT", "336"),
          "VE" => array("VENEZUELA", "VE", "VEN", "862"),
          "VN" => array("VIET NAM", "VN", "VNM", "704"),
          "VG" => array("VIRGIN ISLANDS (BRITISH)", "VG", "VGB", "92"),
          "VI" => array("VIRGIN ISLANDS (U.S.)", "VI", "VIR", "850"),
          "WF" => array("WALLIS AND FUTUNA ISLANDS", "WF", "WLF", "876"),
          "EH" => array("WESTERN SAHARA", "EH", "ESH", "732"),
          "YE" => array("YEMEN", "YE", "YEM", "887"),
          "YU" => array("YUGOSLAVIA", "YU", "YUG", "891"),
          "ZR" => array("ZAIRE", "ZR", "ZAR", "180"),
          "ZM" => array("ZAMBIA", "ZM", "ZMB", "894"),
          "ZW" => array("ZIMBABWE", "ZW", "ZWE", "716"),
        );
    
      return $countries[$code][2];
    }
  }
   /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_paytabs_sadad_wc_gateway($methods) {
        $methods[] = 'WC_Gateway_Paytabs_sadad_wc';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_paytabs_sadad_wc_gateway' );
}