<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of HttpClient
 *
 * @author jorge
 */

namespace com\synergy\web\unidentalBundle\Util;

use Guzzle\Service\Client;
use Symfony\Component\DependencyInjection\ContainerAware;

class HttpClient extends ContainerAware{
    private $session;
    function __construct(\Symfony\Component\Security\Core\SecurityContext $session) {
        $this->session = $session->getToken()->getUser();
    }

    public static $base_url = "http://uniser.dev/app_dev.php/api/";
    public static $headers = array(
        "Content-Type" => "text/plain",
        "Accept" => "application/json",
    );
    public static $errors = array(
        "400" => array("error" => "session_expired", "error_code" => "400", "error_message" => "Error en solicitud de procesamiento"),
        "900" => array("error" => "connexion_fail", "error_code" => "900", "error_message" => "Conexión Fallida"),
        "401" => array("error" => "invalid_login", "error_code" => "401", "error_message" => "Usuario no conectado, Identificacion o clave INVALIDA"),
        "506" => array("error" => "invalid_login", "error_code" => "500", "error_message" => "Usuario no conectado, Identificacion o clave INVALIDA"),
        "503" => array("error" => "bad_operation", "error_code" => "503", "error_message" => "Operacion no reconocida/implementada"),
        "500" => array("error" => "bad_operation", "error_code" => "500", "error_message" => "Error General de la Aplicación")
    );

    

    public static function buildResponse($data, $code = "500", $success = false) {
        if ($success)
            $result["status"] = array("code" => "200");
        else
            $result["status"] = array("code" => $code);

        $result["data"] = $data;

        return $result;
    }
    
    public function makeRequest($url, $body = "", $method = "GET", $file_path = "", $getParams=null) {
        $client = new Client(HttpClient::$base_url);
        
        try {
            if ($method === "POST") {
                if (is_object($body) || is_array($body)) {
                    $json = json_encode($body);
                } else {
                    $json = $body;
                } 
                $request = $client->post($url, HttpClient::$headers, $json);
                if ($file_path != "") {

                    $request->addPostFields($body)
                            ->addPostFiles(array('file' => $file_path));
                }
            } else if ($method === "GET") {
                $request = $client->get($url, HttpClient::$headers, $body);
            } else if ($method === "DELETE") {
                $request = $client->delete($url, HttpClient::$headers, $body);
            }
            if(isset($getParams) || is_array($getParams)){
                foreach ($getParams as $key => $value) {
                    $request->getQuery()->set($key, $value);   
                }
            }
            $response = $request->send();
            $tmp = $response->getBody();
            $data = json_decode($tmp, true);
            if (array_key_exists('status', $data)) {
                if ($data["status"]["code"] != "200") {
                    $keys = array_keys(HttpClient::$errors);
                    foreach ($keys as $key) {
                        if ($key == $data["data"]) {
                            return json_decode($tmp);
                        }
                    }
                    return json_decode($tmp);
                }
            } else {
                return json_decode($tmp);
            }
            $json = json_decode($response->getBody());
            $error = json_last_error();
            return $json;
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            return (object) HttpClient::buildResponse(HttpClient::$errors["900"]["error_message"], $code = "900", $success = false);
        }
    }

    public function buildResponseNonStatic($data, $code = "500", $success = false) {
        if ($success)
            $result["status"] = array("code" => "200");
        else
            $result["status"] = array("code" => $code);

        $result["data"] = $data;

        return $result;
    }

}

?>
