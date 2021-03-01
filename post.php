<?php namespace DE\RUB\DYMOLabelsExternalModule;

/**
 * This is the public POST endpoint
 */
class postEndpoint { 
    
    /**
     * Processes the request.
     * @param DYMOLabelsExternalModule $m 
     * @return void 
     */
    static function process($m) {

        $fw = $m->framework;
        $pid = $fw->getProjectId();
        $errors = array();
        $doc_start = 
'<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $fw->tt("module_name") . '</title>
</head>
<body>
    <h3>' . $fw->tt("module_name") . '</h3>
';
        $doc_end = '
</body>
</html>';

        // Check whether anonymous access is allowed.
        if ($pid !== null || !$fw->getSystemSetting("system-enable-post")) {
            // It's not.
            header("HTTP/1.1 403 Forbidden");
            exit;
        }
        // POST?
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method !== 'POST')
        {
            header("HTTP/1.1 405 Method Not Allowed");
            print $doc_start;
            print "<p>{$fw->tt("error_postonly")}</p>";
            print $doc_end;
            exit;
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // Perform some checks:
        // A template XML is required
        if (!isset($data['templateXml']))
        {
            array_push($errors, "Missing required parameter 'templateXml'.");
        }

        // Were there any errors?
        if (count($errors))
        {
            header("HTTP/1.1 400 Bad Request");
            foreach ($errors as $error) {
                print $error."\n";
            }
            exit;
        }

        // TODO - do actually something


    }
}
postEndpoint::process($module);
