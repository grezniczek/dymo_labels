<?php namespace DE\RUB\DYMOLabelsExternalModule;

use Throwable;

require_once ("classes/Crypto.php");

/**
 * This is the AJAX endpoint for all authenticated module operations
 */
class ajaxEndpoint { 
    
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
        $crypto = Crypto::init($m);
        $verification = $crypto->decrypt($data["verification"]);
        $verified = $verification && $verification["pid"] == $GLOBALS["Proj"]->project_id && $verification["userid"] == $GLOBALS["userid"];
        if ($verified) {
            switch ($data["action"]) {
                case "add-label":
                    try {
                        $payload = json_decode($data["payload"], true);
                        $label = $m->addLabel($payload);
                        $response = array (
                            "success" => true,
                            "label" => $label,
                        );
                    }
                    catch (Throwable $ex) {
                        $response["error"] = $ex->getMessage();
                    }
                break;
                case "rename-label":
                    try {
                        $payload = json_decode($data["payload"], true);
                        $label = $m->renameLabel($payload);
                        $response = array (
                            "success" => true,
                            "label" => $label,
                        );
                    }
                    catch (Throwable $ex) {
                        $response["error"] = $ex->getMessage();
                    }
                break;
                case "update-label":
                    try {
                        $payload = json_decode($data["payload"], true);
                        $label = $m->updateLAbel($payload);
                        $response = array (
                            "success" => true,
                            "label" => $label,
                        );
                    }
                    catch (Throwable $ex) {
                        $response["error"] = $ex->getMessage();
                    }
                break;
                case "get-labels":
                    $labels = $m->getLabels();
                    $response = array (
                        "success" => true,
                        "count" => count($labels),
                        "labels" => $labels,
                    );
                break;
                case "delete-label":
                    $payload = json_decode($data["payload"], true);
                    $error = $m->deleteLabel($payload);
                    if (strlen($error)) {
                        $response = array (
                            "success" => false,
                            "error" => $error,
                        );
                    }
                    else {
                        $response = array (
                            "success" => true,
                            "id" => $payload,
                        );
                    }
                break;
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
                default:
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
ajaxEndpoint::process($module);
