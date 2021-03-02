<?php namespace DE\RUB\DYMOLabelsExternalModule;

use DE\RUB\REDCapEMLib\Crypto;

/**
 * This is the AJAX endpoint for all public (non-authenticated) module operations
 */
class ajaxEndpointNoAuth { 
    
    /**
     * Processes the request.
     * @param DYMOLabelsExternalModule $m 
     * @return void 
     */
    static function process($m) {

        // Get payloads. REDCap adds CSRF tokens to $.ajax() - no idea how to verify them, so we just discard
        $raw = file_get_contents("php://input");
        $data = array();
        foreach (explode("&", $raw) as $item) {
            $parts = explode("=", $item, 2);
            if (count($parts) == 2) {
                $data[urldecode($parts[0])] = urldecode($parts[1]);
            }
        }
        // Default response
        $response = array(
            "success" => false,
            "error" => "Invalid request."
        );
        // Check verification
        if (!class_exists("\DE\RUB\REDCapEMLib\Crypto")) include_once ("classes/Crypto.php");
        $crypto = Crypto::init($m);
        $verification = $crypto->decrypt($data["verification"]);
        $verified = $verification && $verification["pid"] == $GLOBALS["Proj"]->project_id && $verification["noauth"] == "noauth";
        if ($verified) {
            switch ($data["action"]) {
                case "store-calibration":
                    $payload = json_decode($data["payload"], true);
                    try {
                        $m->storeCalibration($payload);
                        $response = array (
                            "success" => true,
                        );
                    } 
                    catch (\Throwable $err) {
                        $response = array (
                            "success" => false,
                            "error" => $err->getMessage(),
                        );
                    }
                break;
                case "get-calibration":
                    $payload = json_decode($data["payload"], true);
                    try {
                        $calData = $m->getCalibration($payload);
                        $response = array (
                            "success" => true,
                            "calData" => $calData
                        );
                    } 
                    catch (\Throwable $err) {
                        $response = array (
                            "success" => false,
                            "error" => $err->getMessage(),
                        );
                    }
                break;
            }
            // Update timestamp.
            $verification["timestamp"] = time();
            $response["verification"] = $crypto->encrypt($verification);
        }
        // Send response.
        print json_encode($response);
    }
}
ajaxEndpointNoAuth::process($module);
