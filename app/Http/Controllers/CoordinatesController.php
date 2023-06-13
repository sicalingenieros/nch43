<?php

namespace App\Http\Controllers;

use App\Models\Coordinate;
use App\Models\Procedure;
use Illuminate\Http\Request;

class CoordinatesController extends Controller
{
    public function nch43()
    {
        $lote = 520;

        $procedimiento = Procedure::where('max','>=',$lote)->where('min','<=',$lote)->first();
        $row = 4;
        $column = 10;
        $out = null;
        $columnas_a_usar = round($procedimiento->digits/2);

        $salida= collect();

        for ($j=0; $j < 10; $j++) { 
            for ($i=1; $i < $columnas_a_usar; $i++) { 
                $out =  Coordinate::where('row',$row)->where('column', $column)->first()->number.Coordinate::where('row',$row)->where('column', $column+$i)->first()->number;
            }
            $salida->push(str_split($out, $procedimiento->digits)[0]);

            $row++;
            $out = "";
        }

        dd($salida);

        //intval(filter_var($number, FILTER_SANITIZE_NUMBER_INT))

    }
}
