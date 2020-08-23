<?php 
defined('BASEPATH') OR exit('No direct script access allowed'); 
 
/** 
 * Stripe Library for CodeIgniter 3.x 
 * 
 * Library for Stripe payment gateway. It helps to integrate Stripe payment gateway 
 * in CodeIgniter application. 
 * 
 * This library requires the Stripe PHP bindings and it should be placed in the third_party folder. 
 * It also requires Stripe API configuration file and it should be placed in the config directory. 
 * 
 * @package     CodeIgniter 
 * @category    Libraries 
 * @author      CodexWorld 
 * @license     http://www.codexworld.com/license/ 
 * @link        http://www.codexworld.com 
 * @version     3.0 
 */ 
 
class Stripe_lib{ 
    var $CI; 
    var $api_error; 
     
    public function __construct(){ 
        $this->api_error = ''; 
        $this->CI =& get_instance(); 
        $this->CI->load->config('stripe'); 
         
        // Include the Stripe PHP bindings library 
        require APPPATH .'third_party/stripe-php/init.php'; 
         
        // Set API key 
        \Stripe\Stripe::setApiKey($this->CI->config->item('stripe_api_key')); 
    } 
 
    public function addCustomer($email, $token){ 
        try { 
            // Add customer to stripe 
            $customer = \Stripe\Customer::create(array( 
                'email' => $email, 
                'source'  => $token 
            )); 
            return $customer; 
        }catch(Exception $e) { 
            $this->api_error = $e->getMessage(); 
            return false; 
        } 
    }
     
    public function createCharge($token, $itemName, $itemPrice){ 
        // Convert price to cents 
        $results = array();
        //$itemPriceCents = ($itemPrice*100); 
        $currency = $this->CI->config->item('stripe_currency'); 
         
        try { 
            // Charge a credit or a debit card 
            $charge = \Stripe\Charge::create([
                'amount' => $itemPrice,
                'currency' => $currency,
                'description' => $itemName,
                'source' => $token,
              ]);

            $results['error'] = false;
            $results['response'] = "Pago Realizado";
            $results['striperesponse'] = $charge->jsonSerialize();
             
            // Retrieve charge details 
            return $results; 
        } catch(\Stripe\Exception\CardException $e) {
            // Since it's a decline, \Stripe\Exception\CardException will be caught
            $results['error'] = true;
            $results['response'] = "Error";

            $e_json = $e->getJsonBody();
            $error_code_decline = $e_json['error'];

            if(array_key_exists('decline_code', $e_json['error'])){
                
                if($error_code_decline['decline_code'] == "generic_decline"){
                    $results['message'] = "Su tarjeta ha sido declinada";
                }
    
                if($error_code_decline['decline_code'] == "insufficient_funds"){
                    $results['message'] = "Su tarjeta tiene fondos insuficientes.";
                }
    
                if($error_code_decline['decline_code'] == "lost_card"){
                    $results['message'] = "Tu tarjeta fue rechazada.";
                }
    
                if($error_code_decline['decline_code'] == "stolen_card"){
                    $results['message'] = "Esta tarjeta esta reportada como robada.";
                }
            }else{
                if($e->getError()->code == "expired_card"){
                    $results['message'] = "Su tarjeta ha expirado.";
                }
    
                if($e->getError()->code == "incorrect_cvc"){
                    $results['message'] = "El código de seguridad de su tarjeta (CVC) es incorrecto.";
                }
    
                if($e->getError()->code == "processing_error"){
                    $results['message'] = "Se produjo un error al procesar su tarjeta. Inténtalo de nuevo en un momento.";
                }
    
                if($e->getError()->code == "incorrect_number"){
                    $results['message'] = $e->getError()->code;
                }
            }
            return $results;  
        } catch (\Stripe\Exception\RateLimitException $e) {
          // Too many requests made to the API too quickly
            $results['error'] = true;
            $results['response'] = "Error";
            $results['message'] = "Demasiadas solicitudes hechas a la API demasiado rápido, pago no realizado.";
            return $results; 
        } catch (\Stripe\Exception\InvalidRequestException $e) {
          // Invalid parameters were supplied to Stripe's API
            $results['error'] = true;
            $results['response'] = "Error";
            $results['message'] = "Se proporcionaron parámetros no válidos a la API de Stripe, pago no realizado.";
            return $results; 
        } catch (\Stripe\Exception\AuthenticationException $e) {
          // Authentication with Stripe's API failed
          // (maybe you changed API keys recently)
            $results['error'] = true;
            $results['response'] = "Error";
            $results['message'] = "La autenticación con la API de Stripe falló, pago no realizado.";
            return $results; 
        } catch (\Stripe\Exception\ApiConnectionException $e) {
          // Network communication with Stripe failed
            $results['error'] = true;
            $results['response'] = "Error";
            $results['message'] = "La comunicación de red con Stripe falló, pago no realizado.";
            return $results; 
        } catch (\Stripe\Exception\ApiErrorException $e) {
          // Display a very generic error to the user, and maybe send
          // yourself an email
            $results['error'] = true;
            $results['response'] = "Error";
            $results['message'] = "Error del Servidor, pago no realizado.";
            return $results; 
        } 
    } 
}