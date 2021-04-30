<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

use Auth;
use Illuminate\Support\Facades\DB;

class Utility extends Model
{
    public static function is_mobile($useragent)
    {

        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
            return true;
        }

        return false;
    }

    public static function mimeToText($mime)
    {
        $mime_map = ['image/bmp' => 'bmp', 'image/x-bmp' => 'bmp', 'image/x-bitmap' => 'bmp', 'image/x-xbitmap' => 'bmp', 'image/x-win-bitmap' => 'bmp', 'image/x-windows-bmp' => 'bmp', 'image/ms-bmp' => 'bmp', 'image/x-ms-bmp' => 'bmp', 'image/gif' => 'gif', 'image/x-icon' => 'ico', 'image/x-ico' => 'ico', 'image/vnd.microsoft.icon' => 'ico', 'image/jpx' => 'jp2', 'image/jpm' => 'jp2', 'image/jpeg' => 'jpeg', 'image/pjpeg' => 'jpeg', 'image/png' => 'png', 'image/x-png' => 'png', 'image/tiff' => 'tiff'];

        return isset($mime_map[$mime]) ? $mime_map[$mime] : false;
    }

    public static function get_user_id()
    {
        if (Auth::check()) {
            return Auth::user()->id;
        }

        return "";
    }

    public static function generateID()
    {
        $s = strtoupper(md5(uniqid(rand(), true)));
        $guidText = substr($s, 0, 8) . '-' . substr($s, 8, 4) . '-' . substr($s, 12, 4) . '-' . substr($s, 16, 4) . '-' . substr($s, 20);
        return $guidText;
    }


    // this is used to format strings like 
    // http:/lazysuzy.com/image/ggimggg_xbgs.php
    // to "ggimggg"
    public static function get_core_image_name($img)
    {
        $img = explode("/", $img);
        $img = end($img);
        $img = current(explode(".", $img));
        $img = substr($img, 0, strlen($img) - 5);

        return $img;
    }

    // this takes in the normal image path and converts it 
    // to path that will be saved for xbg images 
    // for primary and secondary images
    public static function make_new_path($image, $type)
    {

        $image_process_columns = config('admin.image_process_columns');
        $image_process_folders = config('admin.image_process_folders');

        // construct the new location path 
        $image_path_elements =  explode("/", $image);
        $image_path_elements[2] = $image_process_folders[$type]; // new folder

        $image_name = explode(".", $image_path_elements[3]);
        $new_image_name = $image_name[0] . "_" . $image_process_folders[$type];
        $image_name[0] = $new_image_name;

        $new_image_name = implode(".", $image_name);
        $image_path_elements[3] = $new_image_name;
        $new_image_path = implode("/", $image_path_elements);

        // new image will be like /westelm/xbgs/image.jpg
        return $new_image_path;
    }

    public static function get_sql_raw($query)
    {
        return vsprintf(str_replace('?', '%s', $query->toSql()), collect($query->getBindings())->map(function ($binding) {
            $binding = addslashes($binding);
            return is_numeric($binding) ? $binding : "'{$binding}'";
        })->toArray());
    }

    public static function get_sets_enabled_brand_table($brand)
    {

        return Config::get('meta.sets_enabled_tables')[$brand];
    }

    public static function rm_comma($str)
    {
        return str_replace(["$", ",", " "], "", $str);
    }

    public static function match_exclude_LDIS($LS_ID)
    {

        // 552
        $ls_ids = explode(",", $LS_ID);
        if (sizeof($ls_ids) == 0)
            return false;

        foreach ($ls_ids as $id) {

            if (strlen($id) == 4 && $id[0] == "1")
                return true;

            if ((int)$id >= 223 && (int)$id <= 330)
                return true;

            if ((int)$id >= 400 && (int)$id <= 403)
                return true;

            if ((int)$id >= 501 && (int)$id <= 509)
                return true;

            if ((int)$id >= 510 && (int)$id <= 514)
                return true;

            if ((int)$id >= 602 && (int)$id <= 605)
                return true;

            if ((int)$id >= 822 && (int)$id <= 826)
                return true;

            if ((int)$id >= 930 && (int)$id <= 930)
                return true;

            if ((int)$id >= 940 && (int)$id <= 944)
                return true;
        }

        return false;
    }

    public static function restructure_str($str)
    {
        $str = str_replace("\\n", "", $str);
        $pre_delimeters = ["*", "#"];
        $i = 0;
        $new_str = "";
        $new_Arr = [];
        while ($i < strlen($str)) {

            if (($str[$i] === "*" && $str[$i + 1] === "*") || ($str[$i] === "*" && $str[$i - 1] === "*")) {

                if ($str[$i - 1] == "*") $i += 1;
                else $i += 2;

                while (isset($str[$i]) && $str[$i] != "*" && ord($str[$i]) != 13) {
                    ///echo $str[$i];
                    $new_str .= $str[$i++];
                }
                //echo "<br>";
                $i += 2;

                if (strlen($new_str) >= 3) {
                    $new_str = "**" . $new_str . "**";
                    $new_Arr[] = $new_str;
                    $new_str = "";
                }
            } else if (($str[$i] === "#" && $str[$i + 1] === "#") || ($str[$i] === "#" && $str[$i - 1] === "#")) {
                while (isset($str[$i]) && $str[$i] != "\n") {
                    $new_str .= $str[$i++];
                }

                $new_str = "**" . str_replace("#", "", $new_str) . "**";
                if (strlen($new_str) > 6) {
                    $new_Arr[] = $new_str;
                    $new_str = "";
                }
            } else if (($str[$i] === "*" && $str[$i + 1] !== "*") || ($str[$i] != "*" && $str[$i - 1] === "*")) {
                while (isset($str[$i]) && $str[$i] != "\n") {
                    // echo $str[$i];
                    $new_str .= $str[$i++];
                }
                //echo "<br>";
                //echo "I = > $i" . $str[$i] . "<br>";
                if (strlen($new_str) > 6) {
                    $new_Arr[] = "*" . $new_str;
                    $new_str = "";
                }
            } else {

                while (isset($str[$i]) && !in_array($str[$i], $pre_delimeters)) {
                    //echo $str[$i];
                    $new_str .= $str[$i++];
                }
                //echo "<br>";

                if (strlen($new_str) > 6) {
                    $new_Arr[] = $new_str;
                    $new_str = "";
                }
            }

            $i++;
        }

        for ($i = 0; $i < sizeof($new_Arr); $i++) {
            $new_Arr[$i] = str_replace([chr(13), "\n", " "], " ", $new_Arr[$i]);
        }

        return $new_Arr;
    }


    public static function format_desc_new($desc)
    {
        $desc_arr = Utility::restructure_str($desc);
        $new_desc = [];
        foreach ($desc_arr as $line) {
            if (strlen($line) > 0) {
                if (strrpos($line, "**") == true) {
                    $arr = explode("**", $line)[1];
                    array_push($new_desc, "<h6 style='font-weight: bold'>" . $arr . "</h6>");
                } else if (strrpos($line, "[")) {
                    preg_match("/\[[^\]]*\]/", $line, $matched_texts);
                    preg_match('/\([^\]]*\)/', $line, $matched_links);

                    if (sizeof($matched_links) == sizeof($matched_texts)) {
                        for ($i = 0; $i < sizeof($matched_links); $i++) {
                            $str = "<a href='" . trim(substr($matched_links[$i], 1, -1)) . "'> " . trim(substr($matched_texts[$i], 1, -1)) . " </a> ";

                            $line = str_replace($matched_links[$i], "", $line);
                            $line = str_replace($matched_texts[$i], $str, $line);

                            array_push($new_desc, $line);
                        }
                    }
                } else {
                    array_push($new_desc, $line);
                }
            }
        }

        return  $new_desc;
    }

    public static function get_main_image($sku) {
        $table = Config::get('tables.master_table');
        $col_name = "main_product_images";
        $row = DB::table($table)->select($col_name)->where('product_sku', $sku)->get();
        if(sizeof($row) > 0) {
            return Product::$base_siteurl . $row[0]->$col_name;
        }

        return null;
    }
}
