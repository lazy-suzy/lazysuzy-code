<?php

namespace App\Services;

use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

/**
 * Manage Inventory Functions
 * Create, Update
 * @author Jatin Parmar <jatinparmar96@gmail.com>
 */
class DimensionService{
    // Define all private Fields
    public $CLEAN_SYMBOLS = ['.'];
    public $DIMS = [
        'w' => 'width',
        'h' => 'height',
        'd' => 'depth',
        'l' => 'length',
        'h.' => 'height',
        'dia' => 'diameter',
        'dia.' => 'diameter',
        'diam' => 'diameter',
        'diam.' => 'diameter',
        'sq' => 'square',
        'sq.' => 'square',
        'd.' => 'depth',
    ];
    public function get_dims($product)
    {
        $dims = [];
        switch ($product->site_name) {

            case 'cb2':
                $dims = $this->format_cb2($product->product_dimension);
                break;
            case 'cab':
                $dims = $this->format_cab($product->product_dimension);
                break;
            case 'pier1':
                $dims = $this->format_pier1($product->product_dimension);
                break;
            case 'westelm':
                $dims_all = $this->format_westelm($product->product_dimension);
                $dims = $this->convert($dims_all, true);
                break;
            case 'nw':
                $dims_all = $this->format_new_world($product->product_feature);
                $dims = $this->convert($dims_all, false);
                //echo '$dims_all: ' , json_encode($dims_all) , "\n";
                //echo '$dims: ', json_encode($dims), "\n";
                break;
            default:
                $dims = null;
                break;
        }

        $dims_str = [
            'length',
            'width',
            'height',
            'depth',
            'diameter',
            'square',
        ];

        $dims_val = [];
        foreach ($dims_str as $str) {
            $dims_val[$str] = [];
        }
        if (is_array($dims)) {
            foreach ($dims_str as $value) {
                $i = 1;
                foreach ($dims as $key => $dim) {
                    if (isset($dims['dimension_' . $i]->$value)) {
                        if (!in_array($dims['dimension_' . $i]->$value, $dims_val[$value]) && $dims['dimension_' . $i]->$value) {
                            array_push($dims_val[$value], $dims['dimension_' . $i]->$value);
                            $i++;
                        }
                    }
                }
            }

            foreach ($dims_val as $key => $val) {
                $dims_val[$key] = implode(",", $dims_val[$key]);
            }
        } else {
            foreach ($dims_val as $key => $val) {
                $dims_val[$key] = null;
            }
        }

        return $this->normalise_dims($dims_val);

    }

    public function format_cb2($str)
    {
        $json_string = $str;
        if ($json_string === "null") {
            return [];
        }

        $dim = json_decode($json_string);
        if (json_last_error()) {
            return [];
        }

        $d_arr = [];
        $i = 1;
        foreach ($dim as $d) {
            if ($d->hasDimensions && $d->description == "Overall Dimensions") {
                $d_arr['dimension_' . $i++] = $d;
            }
        }

        //return $json_string;
        return $d_arr;
    }

    public function clean_str($str)
    {
        return str_replace($this->CLEAN_SYMBOLS, '', $str);
    }

    public function format_cab($str)
    {
        return $this->format_cb2($str);
    }

    public function format_pier1($str)
    {

        $str = $this->clean_str($str);

        $dim_arr = explode(",", $str);
        $i = 1;
        $dims = [];
        $dim_seq = ['Width', 'Depth', 'Height', 'Diameter'];
        foreach ($dim_arr as $dim) {
            $dim_values = [];
            $d = explode(":", $dim);
            $d_label = isset($d[0]) ? $d[0] : null;
            $d_val = isset($d[1]) ? $d[1] : null;

            if ($d_val == null) {
                $d_val = $d[0];
            }

            $d_val_arr = explode("x", strtolower($d_val));

            $x = 0;

            foreach ($d_val_arr as $val) {

                $val_pair = explode("\"", trim($val));
                if (isset($val_pair[0]) && isset($val_pair[1])) {
                    $val = $val_pair[0];

                    if (isset($this->DIMS[$val_pair[1]])) {
                        $label = $this->DIMS[$val_pair[1]];
                        $x++;
                    } else {
                        $label = $val_pair[1];
                    }

                    if (strlen($val_pair[1]) == 0 || !isset($val_pair[1])) {
                        $label = $dim_seq[$x];
                    }

                    $dim_values[$label] = $val;
                    $x++;
                }
            }

            if (isset($d[1])) {
                $dim_values['label'] = $d_label;
            }

            $dim_values['filter'] = 1;
            array_push($dims, [
                'dimension_' . $i++=> $dim_values,
            ]);
        }

        return $dims;
    }

    public function format_westelm($str)
    {
        return $this->format_pier1($this->clean_str($str));
    }

    public function format_new_world($str)
    {
        $feature_arr = explode("|", $str);
        $dims = [];
        $lines = [];
        foreach ($feature_arr as $line) {
            $line = strtolower($line);
            if (
                (strpos($line, ":") !== false
                    && strpos($line, "\"") !== false)

            ) {
                $dims_ext = $this->format_pier1(($line), false);

                if ($dims_ext != null && gettype($dims_ext) == "array") {
                    $dims = array_merge($dims, $dims_ext);
                }

            } else if (strpos($line, " x ") !== false) {
                $dims_ext = $this->format_pier1(($line), false);

                if ($dims_ext != null && gettype($dims_ext) == "array") {
                    $dims = array_merge($dims, $dims_ext);
                }

                if (sizeof($dims) > 0) {
                    $dims[0]['dimension_1']['label'] = "overall";
                }
            }
        }

        return $dims;
    }

    public function normalise_dims($dims)
    {

        /*
        $arr['dim_width'] = strlen($dims['width']) > 0 ? (float)$dims['width'] : null;
        $arr['dim_height'] = strlen($dims['height']) > 0 ? (float)explode(",", $dims['height'])[0] : null;
        $arr['dim_depth'] = strlen($dims['depth']) > 0 ? (float)$dims['depth'] : null;
        $arr['dim_length'] = strlen($dims['length']) > 0 ? (float)$dims['length'] : null;
        $arr['dim_diameter'] = strlen($dims['diameter']) > 0 ? (float)$dims['diameter'] : null;
        $arr['dim_square'] = strlen($dims['square']) > 0 ? (float)$dims['square'] : null;
         */

        // Make adjustments to conform dimensions data between retailers.
        // Conform to depth and width convention and remove length + square measurements.
        if (strlen($dims['square']) > 0) {

            $dims['width'] = $dims['depth'] = $dims['square'];
            $dims['square'] = "";
        }

        if (strlen($dims['length']) > 0) {
            if (strlen($dims['width']) > 0) {
                $dims['depth'] = $dims['width'];
            }

            $dims['width'] = $dims['length'];
            $dims['length'] = "";
        }

        return $dims;
    }

    public function convert($dims_all, $is_westelm = false)
    {
        $dims = [];
        foreach ($dims_all as $dim) {
            foreach ($dim as $dv => $d) {
                if (!$is_westelm && strtolower($d['label']) == "overall") {
                    $dims[] = $d;
                    break;
                } else if ($is_westelm) {
                    $dims[] = $d;
                    break;
                }
            }
        }

        // dims fill have w,d,h like symbols so update those.
        foreach ($this->DIMS as $key => $value) {

            if (!empty($dims) && isset($dims[0][$key])) {
                $dims[0][$value] = (float) $dims[0][$key];
            }
        }

        return ["dimension_1" => (object) $dims[0]];
    }
}
