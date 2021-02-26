<?php
/**
 * This PHP file is AJAX-called by the DYMO print service to store calibration information
 *
 * It expects a standard form POST with three parameters:
 * - cal_name : Name of the entry (the printer's name)
 * - cal_dx : X-offset
 * - cal_dy : Y-offset
 * The offsets are in units of 0.1 mm and must be within the range -30 to +30.
 */

$cal_filename = './calibration/cal_data.json';
$json = file_get_contents('php://input');
$newValues = json_decode($json, true);

if (isset($newValues['name']))
{
    // Get parameters, enforce coerce range
    $name = $newValues['name'];
    $cal_dx = isset($newValues['dx']) ? (int)$newValues['dx'] : 0;
    $cal_dx = min(30, max(-30, $cal_dx));
    $cal_dy = isset($newValues['dy']) ? (int)$newValues['dy'] : 0;
    $cal_dy = min(30, max(-30, $cal_dy));

    $data = array ();
    // Keep track if calibration data for the given printer was already present
    $replaced = false;
    // Load and parse calibration data file (if it exists)
    if (file_exists($cal_filename))
    {
        $jsonString = file_get_contents($cal_filename);
        $data = json_decode($jsonString, true);
        for ($i = 0; $i < count($data); $i++)
        {
            if ($data[$i]['name'] == $name)
            {
                $data[$i]['dx'] = $cal_dx;
                $data[$i]['dy'] = $cal_dy;
                $replaced = true;
                break;
            }
        }
    }
    // A new entry needs to be made
    if (!$replaced)
    {
        $data[count($data)] = array (
            'name' => $name,
            'dx' => $cal_dx,
            'dy' => $cal_dy
        );
    }
    // Encode and save
    $jsonString = json_encode($data);
    file_put_contents('calibration/cal_data.json', $jsonString);
}