<?php namespace DE\RUB\DYMOLabelsExternalModule;

use DE\RUB\REDCapEMLib\Crypto;

/**
 * This is the AJAX endpoint for all module operations
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
        if (!class_exists("\DE\RUB\REDCapEMLib\Crypto")) include_once ("classes/Crypto.php");
        $crypto = Crypto::init();
        $verification = $crypto->decrypt($data["verification"]);
        $verified = $verification && $verification["pid"] == $GLOBALS["Proj"]->project_id && $verification["userid"] == $GLOBALS["userid"];
        if ($verified) {
            switch ($data["action"]) {
                case "add-label":
                    $payload = json_decode($data["payload"], true);
                    $id = $m->addLabel($payload);
                    $response = array (
                        "success" => true,
                        "id" => $id,
                    );
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
