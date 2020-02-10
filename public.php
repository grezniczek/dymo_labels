<?php

$fw = $module->framework;
// Check whether anonymous access is allowed.
if (!$fw->getSystemSetting("system-allow-anonymous")) {
    // It's not.
    header('HTTP/1.1 403 Forbidden');
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DYMO Labels</title>
</head>
<body>
    <h3>DYMO LabelWriter Integration (Public)</h3>
    <p>
        Coming soon ...
    </p>
</body>
</html>