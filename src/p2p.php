<?php

namespace p2p;

use p2p\Model\Bank;
use p2p\Model\Authentication;
use p2p\Model\Transaccion;
use p2p\Model\PSETransactionRequest;
use p2p\Model\PSETransactionResponse;
use p2p\Model\TransactionInformation;
use p2p\Model\Person;
date_default_timezone_set('America/Bogota');
date_default_timezone_set('America/Bogota');
ini_set('max_execution_time', 180);
ini_set("default_socket_timeout", 180);
class P2P
{

    private static $auth;
    private static $PSETransactionRequest;
    private static $wsdl;
    
    public static function getListBanks() {
    	
    	$listaBancos = [];

    	try{

            $banks = self::callWebService('getBankList',[]);
            $pos = 0;   
            foreach ($banks as $banco) {
            	$newBank = new Bank($banco->bankCode, $banco->bankName);
                $listaBancos[$pos]['bankCode'] = $newBank->getBankCode();
                $listaBancos[$pos]['bankName'] = $newBank->getBankName();
                $pos++;
            }

        } catch(Exception $e){
           return 'Error 2:'.$e->getMessage();
        }

        return json_encode($listaBancos);
    }

    private static function callWebService($function,$parameters){

        $parameters["auth"] = self::$auth;
        $service = new \SoapClient(self::$wsdl);
        $response = $service->__soapCall($function, array($parameters));
        switch ($function){
            case "getTransactionInformation"://consultar informacion de transaccion
                return get_object_vars($response->getTransactionInformationResult);
                break;
            case "createTransaction"://crear nueva transaccion
                return get_object_vars($response->createTransactionResult);
                break;
            case "getBankList"://obtener lista de bancos
                return $response->getBankListResult->item;
                break;
            default:
                return null;
                break;
        }


    }

    public static function executeTransaction() {

        $resultado = [];

        try {

            $atributos = self::callWebService('createTransaction',['transaction'=>self::$PSETransactionRequest]);
            $RespuestaPSE = new PSETransactionResponse();
	        $RespuestaPSE->load($atributos);

	        if($RespuestaPSE->isComplet()){

	            $Transaccion = new Transaccion;

	            if ($RespuestaPSE->getReturnCode() == 'SUCCESS') {
	            	
                    $Transaccion->status = 'PENDING';

                    $resultado['Success'] = $RespuestaPSE->getBankURL();

	            } else {

                    $Transaccion->status = 'FAILED';

                    $ERROR = 'Mensaje de Error: '.$RespuestaPSE->getReturnCode();
                    $ERROR.='<br>*ResponseCode: '.$RespuestaPSE->getResponseReasonCode();
                    $ERROR.='<br>*ResponseReason: '.$RespuestaPSE->getResponseReasonText();
                    $resultado['Error'] = $ERROR;

                }

                $Transaccion->transaccion_id = $RespuestaPSE->getTransactionID();
                $Transaccion->responseCode = $RespuestaPSE->getResponseCode();
                $Transaccion->responseReasonCode = $RespuestaPSE->getResponseReasonCode();
                $Transaccion->responseReasonText = $RespuestaPSE->getResponseReasonText();
	            $Transaccion->save();
                
	        } else {
                $resultado['Error'] = "Error 1: Validation Not Successfull";
            }

        } catch (Exception $e) {
            $resultado['Error'] = "Error 1:".$e->getMessage();
        }

        return $resultado;
    }

    public static function getTransacciones($atribute=null,$value=null)
    {
    	$transacciones = Transaccion::all();
    	if($atribute && $value){
    		$transacciones = Transaccion::where($atribute, $value)->get();
    	}
        
        return $transacciones;
    }

    public static function verifyEstatusTransactions() //PARA SER LLAMADO AL MENOS CADA 12 MIN.
    {
        //SELECT * FROM TRANSACCIONES WHERE TIMESTAMPDIFF(SECOND, INSERTED_AT, CURRENT_TIMESTAMP) > 12;
        $lastTime = strtotime('-7 minutes');
        $transactions = Transaccion::where([
    	['status', '=', 'PENDING'],
    	['created_at', '<=', date('Y-m-d H:i:s', $lastTime)],
		])->get();

		foreach ($transactions as $transaction) {
			self::getTransactionInformation($transaction->transaccion_id);
		}
    }
    
    public static function getTransactionInformation($id)  {
        $resultado = [];
        try {

            $atributos = self::callWebService('getTransactionInformation',["transactionID" => $id]);
            $Respuesta = new TransactionInformation();
            $Respuesta->load($atributos);

            if ($Respuesta->isComplet()) {
                   
                $transaccion = Transaccion::findOrFail($id);
                $transaccion->status = $Respuesta->getTransactionState();
                $transaccion->responseCode = $Respuesta->getResponseCode();;
                $transaccion->responseReasonCode = $Respuesta->getResponseReasonCode();;
                $transaccion->responseReasonText = $Respuesta->getResponseReasonText();;
                $transaccion->save();
            }

        } catch (Exception $e) {
            $resultado['Error'] = "Error 1:".$e->getMessage();
            return json_encode($resultado);
        }

        return "OK";
    }

    public static function createPerson($atributtes)
    {
    	$person = new Person();
    	$person->load($atributtes);
    	return ($person->isComplet()) ? $person : "FAIL";
    }

    public static function createPSETransactionRequest($atributtes)
    {
    	$Request = new PSETransactionRequest();
    	$Request->load($atributtes);
    	
        if ($Request->isComplet()) {
    		self::$PSETransactionRequest = $Request;
            return "OK";
    	} 
    	
        return "FAIL";
    }

    public static function createAuth($wsdl, $login, $tranKey,$additional)
    {
    	self::$wsdl = $wsdl;
        $auth = new Authentication($login, $tranKey, $additional);
        self::$auth = $auth;
        return $auth;
    }


}
