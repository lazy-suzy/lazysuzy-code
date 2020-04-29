<?php

namespace App\Board\Controllers;

use App\Http\Controllers\Controller;
use App\Board\Models\Database;

require (__DIR__ . "/../Models/Database.php");
require (__DIR__ . "/../Models/Constants.php");

class Server extends Controller {

    private $db = null;

    public function __construct() {
        $db =  new Database(env('DB_HOST'), env('DB_USERNAME'), env('DB_PASSWORD'), env('DB_DATABASE'));
    }

    public static function mimeToText($mime) {
        $mime_map = ['image/bmp' => 'bmp', 'image/x-bmp' => 'bmp', 'image/x-bitmap' => 'bmp', 'image/x-xbitmap' => 'bmp', 'image/x-win-bitmap' => 'bmp', 'image/x-windows-bmp' => 'bmp', 'image/ms-bmp' => 'bmp', 'image/x-ms-bmp' => 'bmp', 'image/gif' => 'gif', 'image/x-icon' => 'ico', 'image/x-ico' => 'ico', 'image/vnd.microsoft.icon' => 'ico', 'image/jpx' => 'jp2', 'image/jpm' => 'jp2', 'image/jpeg' => 'jpeg', 'image/pjpeg' => 'jpeg', 'image/png' => 'png', 'image/x-png' => 'png', 'image/tiff' => 'tiff'];

        return isset($mime_map[$mime]) ? $mime_map[$mime] : false;
    }

    public static function generateGUID() {
        $s = strtoupper(md5(uniqid(rand(), true)));
        $guidText = substr($s, 0, 8) . '-' . substr($s, 8, 4) . '-' . substr($s, 12, 4) . '-' . substr($s, 16, 4) . '-' . substr($s, 20);
        return $guidText;
    }

    public static function removeBackground($db, $user_id, $asset_id) {

        $assetQueryID = (int) $user_id;

        // Fetch the object from database
        $asset = $db->selectOne("*", 'asset', array("asset_id" => $asset_id));
        // Check if object is valid with the required feild
        if ($asset && empty($asset['transparent_path'])) {
        
            // Get the transparent image
            if (ENVIROMENT == "DEV") 
            $response = file_get_contents("response.png");
            else if (ENVIROMENT == "PROD") {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.remove.bg/v1.0/removebg", 
                CURLOPT_RETURNTRANSFER => true, 
                CURLOPT_CUSTOMREQUEST => "POST", 
                CURLOPT_POSTFIELDS => array(
                    "image_url" => PROJECT_URL . $asset['path'], 
                    "format" => "png"
                ), CURLOPT_HTTPHEADER => array("X-API-Key: " . REMOVE_BG_API_KEY)));
                $response = curl_exec($curl);
                curl_close($curl);
            }
            
            $asset['transparent_path'] = STORAGE_FOLDER_PATH . '/' . generateGUID() . '.png';
            if (file_put_contents($asset['transparent_path'], $response)) {
                // Update database
                $db->update("asset", array("transparent_path" => $asset['transparent_path']), array("asset_id" => $asset_id));
            }
        }

        // Return response
        return $asset;
    }

    public static function moveImageAndInsert($db, $user_id, $filePath, $additionalData = array()){

        $imageMeta = getimagesize($filePath);
        
        if(isset($imageMeta['mime'])) {
            $imageExtension = Server::mimeToText($imageMeta['mime']);
            // is a valid image file
            if($imageExtension){
            $newFileName = Server::generateGUID() .'.'. $imageExtension;
            
                if(rename($filePath, realpath(STORAGE_FOLDER_PATH) . DIRECTORY_SEPARATOR . $newFileName)){
                    
                    chmod(realpath(STORAGE_FOLDER_PATH) . DIRECTORY_SEPARATOR . $newFileName, 0644);
                    $insertArray = array("user_id" => $user_id, "path" => STORAGE_FOLDER_PATH . '/' . $newFileName);
                    
                    if(!empty($additionalData) && is_array($additionalData))
                    $insertArray = array_merge($insertArray, $additionalData);
                
                    $insertID = $db->insert('asset', $insertArray);
                    if ($insertID)
                    return $db->selectOne("*", 'asset', array("asset_id" => $insertID));
                }
            }
            else
                unlink($filePath);
    }
    else
        unlink($filePath);
    }

    public static function fetchImage($db, $user_id, $url, $additionalData = array()){
        global $db;

        $remoteImage = file_get_contents($url, false, stream_context_create(array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        )));
        
        $filePath = realpath(STORAGE_FOLDER_PATH);
        $fileName = Server::generateGUID();
        
        $completeFilePath = $filePath . DIRECTORY_SEPARATOR . $fileName;
        
        if(file_put_contents($completeFilePath, $remoteImage))
        return Server::moveImageAndInsert($db, $user_id, $completeFilePath, $additionalData);
    }

    public static function get_output() {
        $db = new Database(env('DB_HOST'), env('DB_USERNAME'), env('DB_PASSWORD'), env('DB_DATABASE'));
        $response = false;

        $operation = isset($_POST['operation']) ? $_POST['operation'] : false;

        $column = isset($_POST['column']) ? $_POST['column'] : '*';
        $entity = isset($_POST['entity']) ? $_POST['entity'] : false;
        $where = isset($_POST['where']) ? $_POST['where'] : array();
        $data = isset($_POST['data']) ? $_POST['data'] : array();
        $order = isset($_POST['order']) ? $_POST['order'] : '';

        if ($operation) {
            if ($entity) {
                if ($operation == 'select')
                    $response = $db->select($column, $entity, $where, $order);
                else if ($operation == 'update' && !empty($where))
                    $response = $db->update($entity, $data, $where);
                else if ($operation == 'insert' && !empty($data))
                    $response = $db->insert($entity, $data);
                else if ($operation == 'delete' && !empty($data))
                    $response = $db->delete($entity, $where);
            } else {
                if ($operation == 'fetch' && isset($_POST['user_id']) && isset($_POST['url']))
                    $response = Server::fetchImage($db, $_POST['user_id'], $_POST['url'], $data);
                else if ($operation == 'file' && isset($_POST['user_id']) && isset($_FILES['file']['tmp_name']))
                    $response = Server::moveImageAndInsert($db, $_POST['user_id'], $_FILES['file']['tmp_name'], $data);
                else if ($operation == 'transparent' && isset($_POST['user_id']) && isset($_POST['asset_id']))
                    $response = Server::removeBackground($db, $_POST['user_id'], $_POST['asset_id']);
            }
        }

        header('Content-Type: application/json');
        return json_encode($response);
    }

}